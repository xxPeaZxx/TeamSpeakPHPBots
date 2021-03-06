<?php
/**
 * Copyright (c) 2016 by Botorabi. All rights reserved.
 * https://github.com/botorabi/TeamSpeakPHPBots
 * 
 * License: MIT License (MIT), read the LICENSE text in
 *          main directory for more details.
 */

namespace com\examples\bots\chatbot;
use com\examples\bots\chatbot\ChatBotModel;
use com\tsphpbots\bots\BotBase;
use com\tsphpbots\utils\Log;


/**
 * Class for chat bot
 * 
 * @created:  4nd August 2016
 * @author:   Botorabi (boto)
 */
class ChatBot extends BotBase {

    /**
     * @var string  This tag is used for logs
     */
    protected static $TAG = "ChatBot";

    /**
     *
     * @var ChatBotModel  Database model of the bot. All bot paramerers are hold here.
     */
    protected $model = null;

    /**
     * @var array A queue used for replying to messages
     */
    protected $replyQueue = [];

    /**
     * @var boolean  Flag for issuring an initial greet.
     */
    protected $initialGreet = false;

    /**
     * @var array A queue for fresh entered users in bot's channel
     */
    protected $enterChannelQueue = [];
    
    /**
     * Construct a chat bot.
     * 
     * @param  $server      TS3 server object
     * @throws Exception    Throws exception if the given server is invalid.
     */
    public function __construct($server) {
        BotBase::__construct($server);
        $this->model = new ChatBotModel;
    }

    /**
     * Get all available bot IDs.
     * This is used by bot manager for loading all available bots from database.
     * 
     * @implements base class method
     * 
     * @return array    Array of all available bot IDs, or null if there is no corresponding table in database.
     */
    static public function getAllIDs() {
        return (new ChatBotModel)->getAllObjectIDs();
    }

    /**
     * Create a new bot instance.
     * 
     * @implements base class method
     * 
     * @param $server       TS3 Server object
     * @return              New instance of the bot.
     */
    public static function create($server) {
        return new ChatBot($server);
    }

    /**
     * Load the bot from database and check its data.
     * 
     * @implements base class method
     * 
     * @param int $botId    Bot ID (database table row ID)
     * @return boolean      Return true if the bot was initialized successfully, otherwise false.
     */
    public function initialize($botId) {

        Log::debug(self::$TAG, "loading bot type: " . $this->getType() . ", id " . $botId);

        if ($this->model->loadObject($botId) === false) {
            Log::warning(self::$TAG, "could not load bot from database: id " . $botId);
            return false;
        }

        $this->model->greetingText = trim($this->model->greetingText);
        $this->model->nickName = trim($this->model->nickName);

        if (strlen(trim($this->model->nickName)) === 0) {
            Log::warning(self::$TAG, "empty nick name detected, deactivating the bot!");
            $this->model->active = 0;
        }
        else {
            Log::debug(self::$TAG, " bot succesfully loaded, name: '" . $this->getName() . "'");
        }

        // check if there is a geeting text
        if (strlen($this->model->greetingText) > 0) {
            $this->initialGreet = true;
        }

        return true;
    }

    /**
     * Get the bot type.
     * 
     * @implements base class method
     * 
     * @return string       The bot type
     */
    public function getType() {
        return $this->model->botType;
    }

    /**
     * Get the bot name.
     * 
     * @implements base class method
     * 
     * @return string       The bot name, may be empty if the bot is still not initialized.
     */
    public function getName() {
        return $this->model->name;
    }

    /**
     * Get the unique bot ID.
     * 
     * @implements base class method
     * 
     * @return int          The unique bot ID > 0, or 0 if the bot is not setup
     *                       or loaded from database yet.
     */
    public function getID() {
        return $this->model->id;
    }

    /**
     * Return the database model.
     * 
     * @implements base class method
     *
     * @return ChatBotModel  The database model of this bot.
     */
    public function getModel() {
        return $this->model;
    }

    /**
     * The bot configuration was changed.
     * 
     * @implements base class method
     */
    public function onConfigUpdate() {

        // this is just for being on the safe side
        if ($this->getID() > 0) {
            Log::debug(self::$TAG, "reloading bot configuration, type: " . $this->getType() . ", name: " .
                                   $this->getName() . ", id: " . $this->getID());

            $this->initialize($this->getID());
        }
        else {
            Log::warning(self::$TAG, "the bot was not loaded before, cannot handle its config update!");
        }
    }

    /**
     * This method is called whenever a server event was received.
     * 
     * @implements base class method
     * 
     * @param Object $event        Event received from ts3 server
     * @param Object $host         Server host
     */
    public function onServerEvent($event, $host) {

        // skip event handling if the bot is not active
        if ($this->model->active == 0) {
            return;
        }
       
        Log::verbose(self::$TAG, "bot '" . $this->model->name . "' got event: " . $event->getType());

        if (strcmp($event->getType(), "cliententerview") === 0) {
            $data = $event->getData();
            $clientid = $data["clid"];

            // did the client directly entered bot's channel?
            if ($this->isInChannel($clientid, $this->model->channelID)) {
                $cnick = $data["client_nickname"];
                $greet = "Hello " . $cnick . "!";
                // enqueue a greeting for next update step
                $this->replyQueue[] = ["targetId" => $clientid, "msg" => $greet];
            }
            return;
        }
        else if (strcmp($event->getType(), "clientmoved") === 0) {
            $data      = $event->getData();
            $clientid  = (int)$data["clid"];
            $channelid = (int)$data["ctid"];
            // check if a new client was moved to bot's channel
            if((strlen($this->model->greetingText) > 0) &&
               ($this->model->channelID == $channelid) &&
                $this->isInChannel($clientid, $this->model->channelID)) {

                // unfortunately, the t3 server query sends this event twice, we have to deal with it
                $this->enterChannelQueue[$clientid] = ["targetId" => $clientid, "msg" => $this->model->greetingText];
                //Log::verbose(self::$TAG, "client entered my channel: " . $clientid);
            }
            return;
        }
        else if (strcmp($event->getType(), "textmessage") === 0) {
            $data    = $event->getData();
            $target  = (int)$data["target"];
            $source  = (int)$data["invokerid"];
            $me      = (int)$host->whoami()["client_id"];

            Log::verbose(self::$TAG, "me: " . $me . ", source: " . $source . ", target: " . $target);

            // consider echos!
            if(($source !== $target) && ($source !== $me)) {
                $text = $data["msg"];
                $reply = $this->replyMessage($text);
                if (!is_null($reply)) {
                    // enqueue the reply for next update step
                    $this->replyQueue[] = ["targetId" => $source, "msg" => $reply];
                }
            }
        }
    }

    /**
     * Update the bot.
     *
     * @implements base class method
     */
    public function update() {

        // skip updating if the bot is not active
        if ($this->model->active == 0) {
            return;
        }

        if ($this->initialGreet === true) {
            $this->initialGreet = false;
            $this->sendMessage($this->model->channelID, null, $this->model->greetingText);
        }

        // this avoids 'clientmoved' event duplication
        foreach($this->enterChannelQueue as $q) {
            $this->replyQueue[] = $q;
        }
        $this->enterChannelQueue = [];

        foreach($this->replyQueue as $q) {
            $targetId = $q["targetId"];
            $reply    = $q["msg"];
            $this->sendMessage($this->model->channelID, [$targetId], $reply);
        }
        $this->replyQueue = [];
    }

    /**
     * Check if a client is in channel with given ID.
     * 
     * @param int $clientID     Client ID
     * @param int $channelID    Channel ID
     * @return boolean          True if the client is in given channel, otherwise false.
     */
    protected function isInChannel($clientID, $channelID) {
        if (!$channelID) {
            return false;
        }
        $channel = $this->ts3Server->channelGetById($channelID);
        $clients = $channel->clientList();
        foreach($clients as $client) {
            if ($client["clid"] == $clientID) {
                return true;
            }
        }
        return false;
    }

    /**
     * Send a message to clients in a channel.
     * 
     * @param int $channelID        Channel ID
     * @param mixed $clientIDs      Client ID list of recipients in channel, or null for all clients in channel.
     * @param string $msg           Message to send
     */
    protected function sendMessage($channelID, $clientIDs, $msg) {

        $channel = $this->ts3Server->channelGetById($channelID);
        $clients = $channel->clientList();
        $text = "[" . $this->model->nickName . "]: " . $msg;
        foreach($clients as $client) {
            if (is_null($clientIDs) || in_array($client["clid"], $clientIDs)) {
                $client->message($text);
            }
        }
    }

    /**
     * Check if the given haystack string contains the needle string.
     * 
     * @param string $haystack  The source string
     * @param string $needle    The string to search for
     * @return boolean          True if the haystack string contrins the given needle, otherwise false.
     */
    protected function strContains($haystack, $needle) {
        return (strpos($haystack, $needle) !== false);
    }

    /**
     * Reply to incoming message.
     * 
     * @param string $msg       Incoming message
     * @return string           Reply text, or null if there is no reply to given message.
     */
    protected function replyMessage($msg) {
        // limit the text length
        $STR_MAX_LEN = 256;
        $text = strtolower((strlen($msg) > $STR_MAX_LEN) ? substr($msg, 0, $STR_MAX_LEN) : $msg);
        
        $reply = null;
        if ($this->strContains($text, "hi") || $this->strContains($text, "hello")) {
            $reply = "Hi my friend. I am a chat bot, tell me something and I try to sound smart.";
        }
        else if ($this->strContains($text, "how") &&
                 $this->strContains($text, "are") &&
                 $this->strContains($text, "you")) {
            $reply = "I am well, thank you. How are you?";
        }
        return $reply;
    }
}
