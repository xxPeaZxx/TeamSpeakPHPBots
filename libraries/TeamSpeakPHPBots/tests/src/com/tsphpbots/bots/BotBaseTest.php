<?php

namespace com\tsphpbots\bots;
require_once(__DIR__ . '/TestBot.php');


/**
 * Generated by PHPUnit_SkeletonGenerator on 2016-08-06 at 07:51:38.
 */
class BotBaseTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TestBot  The bot used for testing
     */
    protected $bot;

    /**
     * @var int  The bot ID
     */
    protected $botId;

    /**
     *
     * @var Object TS3 server stub
     */
    protected $ts3Server = "TS3 Server STUB";

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp() {
        // create a new bot for the test
        $this->bot = TestBot::create($this->ts3Server);
        $this->bot->model->create();
        $this->botId = $this->bot->getID();
        // setup the bot
        $this->bot->model->name         = "TestBot";
        $this->bot->model->description  = "I was created only for testing.";
        $this->bot->model->active       = 1;
        $this->bot->model->nickName     = "TestBot NickName";
        $this->bot->model->update();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown() {
        // delete the bot from database
        $this->bot->getModel()->delete();
    }

    /**
     * @covers com\tsphpbots\bots\BotBase::getAllIDs
     */   
    public function getAllIDs() {
        $ids = TestBot::getAllIDs();
        $this->assertTrue(!is_null($ids) && (count($ids) > 0), "Could not find bot IDs!");
    }

    /**
     * @covers com\tsphpbots\bots\BotBase::create
     */
    public function testCreate() {
        $newbot = TestBot::create($this->ts3Server);
        $this->assertTrue(!is_null($newbot), "Could not create new bot!");
    }

    /**
     * @covers com\tsphpbots\bots\BotBase::initialize
     */
    public function testInitialize() {
        $res = $this->bot->initialize($this->botId);
        $this->assertTrue($res === true, "Bot initialization failed: " . $this->botId);
    }

    /**
     * @covers com\tsphpbots\bots\BotBase::getType
     */
    public function testGetType() {
        $type = $this->bot->getType();
        $this->assertTrue(!is_null($type) && (strlen($type) > 0), "Invalid bot type!");
    }

    /**
     * @covers com\tsphpbots\bots\BotBase::getName
     */
    public function testGetName() {
        $name = $this->bot->getName();
        $this->assertTrue(!is_null($name) && (strlen($name) > 0), "Invalid bot name!");
    }

    /**
     * @covers com\tsphpbots\bots\BotBase::getModel
     */
    public function testGetModel() {
        $model = $this->bot->getModel();
        $this->assertTrue(!is_null($model), "Invalid bot model!");
    }

    /**
     * @covers com\tsphpbots\bots\BotBase::getID
     */
    public function testGetID() {
        $id = $this->bot->getID();
        $this->assertTrue(!is_null($id) && ($id > 0), "Invalid bot ID!");
    }

    /**
     * @covers com\tsphpbots\bots\BotBase::update
     */
    public function testUpdate() {}

    /**
     * @covers com\tsphpbots\bots\BotBase::onServerEvent
     */
    public function testOnServerEvent() {
        $this->bot->onServerEvent([], []);
    }

    /**
     * @covers com\tsphpbots\bots\BotBase::onConfigUpdate
     */
    public function testOnConfigUpdate() {
        $this->bot->onConfigUpdate();
    }
}