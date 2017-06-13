<?php

use Db\Model\Deck as DeckModel;

/**
 * Class PlayerTest
 */
class PlayerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    private $playerName = '';

    /**
     * @var string
     */
    private $password = 'test';

    protected function setUp()
    {
        parent::setUp();

        $this->playerName = mb_substr(md5(time() . mt_rand(1, 1000)), 0, 20);

        $dic = Dic::getInstance();
        $servicePlayer = $dic->serviceFactory()->player();
        $servicePlayer->register($this->playerName, $this->password);
    }

    protected function tearDown()
    {
        parent::tearDown();

        $dic = Dic::getInstance();
        $servicePlayer = $dic->serviceFactory()->player();
        $servicePlayer->deletePlayer($this->playerName);
    }

    public function testPlayerData()
    {
        $dic = Dic::getInstance();
        $dbEntityPlayer = $dic->dbEntityFactory()->player();

        $player = $dbEntityPlayer->getPlayer($this->playerName);
        $this->assertNotNull($player);
        $this->assertEquals(md5($this->password), $player->getPassword());
    }

    public function testScoreData()
    {
        $dic = Dic::getInstance();
        $dbEntityScore = $dic->dbEntityFactory()->score();

        $score = $dbEntityScore->getScore($this->playerName);
        $this->assertNotNull($score);
    }

    public function testDeckData()
    {
        $dic = Dic::getInstance();
        $dbEntityDeck = $dic->dbEntityFactory()->deck();

        $decks = $dbEntityDeck->listDecks($this->playerName);
        $this->assertEquals(DeckModel::DECK_SLOTS, count($decks->data()));
    }

    public function testSettingsData()
    {
        $dic = Dic::getInstance();
        $dbEntitySetting = $dic->dbEntityFactory()->setting();

        $setting = $dbEntitySetting->getSetting($this->playerName);
        $this->assertNotNull($setting);
    }

    public function testWelcomeMessage()
    {
        $dic = Dic::getInstance();
        $dbEntityMessage = $dic->dbEntityFactory()->message();

        $messages = $dbEntityMessage->listMessagesTo($this->playerName);
        $this->assertEquals(1, count($messages->data()));
    }
}
