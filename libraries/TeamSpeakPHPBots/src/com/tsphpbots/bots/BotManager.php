<?php
/**
 * Copyright (c) 2016 by Botorabi. All rights reserved.
 * https://github.com/botorabi/TeamSpeakPHPBots
 * 
 * License: MIT License (MIT), read the LICENSE text in
 *          main directory for more details.
 */

namespace com\tsphpbots\bots;
use com\tsphpbots\utils\Log;

/**
 * This class manages the creation and the lifecycle of bots.
 * 
 * @package   com\tsphpbots\web\controller
 * @created   23th July 2016
 * @author    Botorabi
 */
class BotManager {

    /**
     * @var string Class tag for logging
     */
    protected static $TAG = "BotManager";

    /**
     * @var array  Registered bot classes (usually a bot type is its class)
     */
    protected $botClasses = [];

    /**
     * @var array  All active bots
     */
    protected $bots = [];

    /**
     * @var Object  TS3 server
     */
    protected $ts3Server = null;

    /**
     * Construct the bot manager.
     * 
     * @param  Object $server   TS3 server object
     * @throws Exception        Throws exception if the given server is invalid.
     */
    public function __construct($server) {

        if ($server == null) {
            throw new Exception("Invalid TS3 server object!");
        }
        $this->ts3Server = $server;
    }

    /**
     * Register a new bot class. This is usually called during application initialization for
     * bots which wish to have the automatic database and house-keeping functinoality
     * of the BotManager. 
     * 
     * @param string $botClass      The full qualified name (including package path) of the bot class to register.
     *                              For package name separation one can also use forward slashes.
     */
    public function registerBotClass($botClass) {
        $cleanpath = str_replace("/", "\\", $botClass);
        $this->botClasses[] = $cleanpath;
    }
    
    /**
     * Given a bot type try to find its class in registered classes.
     * 
     * @param string $botType       Bot type
     * @return sting                Return null if the class could not be found.
     */
    public function findBotClass($botType) {
        $foundclass = null;
        foreach($this->botClasses as $botclass) {
            if (strpos($botType, $botclass) !== false) {
                $foundclass = $botclass;
                break;
            }
        }
        return $foundclass;
    }

    /**
     * Periodically call this update method.
     */
    public function update() {
        foreach($this->bots as $bot) {
            $bot->update();
        }
    }

    /**
     * Load all bots of registered bot classes from database.
     */
    public function loadBots() {
        foreach($this->botClasses as $botclass) {
            try {
                $bot = $botclass::create($this->ts3Server);
            }
            catch (Exception $e) {
                Log::warning(self::$TAG, "could not create instance of bot class: " . $botclass);
                Log::warning(self::$TAG, "  reason: " . $e->getMessage());
                continue;
            }
            $model = $bot->getModel();
            // does the bot have a database model?
            if (is_null($model)) {
                continue;
            }
            $ids = $model->getAllObjectIDs();
            foreach($ids as $id) {
                $newbot = $botclass::create($this->ts3Server);
                if ($newbot->load($id) === true) {
                    $this->bots[] = $newbot;
                }
            }
        }
    }

    /**
     * Manually add a new bot.
     * 
     * @param Object $bot    The new bot to add
     */
    public function addBot($bot) {
        $this->bots[] = $bot;
    }

    /**
     * Call this method whenever a server event was received.
     * 
     * @param Object $event        Event received from ts3 server
     * @param Object $host         Server host
     */
    public function notifyServerEvent($event, $host) {

        $TYPE_CHANNEL = "channel";
        $TYPE_CLIENT  = "client";

        $type = $event->getType();

        //Log::debug(self::$TAG, "ts3 server event received, type: " . $type . ", host: " . $host);

        // check if the event was something about channels
        if (strlen($type) >= strlen($TYPE_CHANNEL) &&
            strcmp(substr($type, 0, strlen($TYPE_CHANNEL)), $TYPE_CHANNEL) === 0) {

            //Log::debug(self::$TAG, " updating the channel list");
            $this->ts3Server->channelListReset();
        }
        if (strlen($type) >= strlen($TYPE_CLIENT) &&
            strcmp(substr($type, 0, strlen($TYPE_CLIENT)), $TYPE_CLIENT) === 0) {

            //Log::debug(self::$TAG, " updating the client list");
            $this->ts3Server->clientListReset();
        }

        // notify now all bots
        foreach($this->bots as $bot) {
            $bot->onServerEvent($event, $host);
        }
    }

    /**
     * Notify about an update of bot configuration. Usually this means that the bot
     * config was changed in the database, the bot should load it and reflect
     * the changes, if it exists.
     * 
     * @param string $botType   The bot type
     * @param int $id           The bot ID
     * @return boolean          Return true if the bot was found and updated successfully, otherwise false.
     */
    public function notifyBotUpdateConfig($botType, $id) {
        foreach($this->bots as $bot) {
            if ((strcmp($botType, $bot->getType()) === 0) && ($bot->getID() == $id)) {
                $bot->onConfigUpdate();
                return true;
            }
        }
        return false;
    }

    /**
     * Notify about a bot creation in database. The bot will be loaded and added to the bot manager.
     * 
     * @param string $botType   The bot type
     * @param int $id           The bot ID
     * @return boolean          Return true if the bot was found and deleted successfully, otherwise false.
     */
    public function notifyBotAdd($botType, $id) {
        $botclass = $this->findBotClass($botType);
        if (is_null($botclass)) {
            return false;
        }

        try {
            $bot = $botclass::create($this->ts3Server);
        }
        catch (Exception $e) {
            Log::warning(self::$TAG, "could not create a new instance of bot class: " . $botclass);
            Log::warning(self::$TAG, "  reason: " . $e->getMessage());
            return false;
        }
        
        if ($bot->load($id) === true) {
            Log::debug(self::$TAG, "new bot was added: " . $botType);
            $this->bots[] = $bot;
        }
        else {
            Log::warning(self::$TAG, "cannot add new bot, it failed to load");
            return false;
        }

        return true;
    }

    /**
     * Notify about a bot deletion in database. The bot will be removed from the bot manager.
     * 
     * @param string $botType   The bot type
     * @param int $id           The bot ID
     * @return boolean          Return true if the bot was found and deleted successfully, otherwise false.
     */
    public function notifyBotDelete($botType, $id) {
        foreach($this->bots as $key => $bot) {
            if ((strcmp($botType, $bot->getType()) === 0) && ($bot->getID() == $id)) {
                unset($this->bots[$key]);
                return true;
            }
        }
        return false;
    }
}