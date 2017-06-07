<?php

/**
 * Class DeckTest
 */
class DeckTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    private $playerName = '';

    protected function setUp()
    {
        parent::setUp();

        $this->playerName = mb_substr(md5(time() . mt_rand(1, 1000)), 0, 20);

        $dic = Dic::getInstance();
        $servicePlayer = $dic->serviceFactory()->player();
        $servicePlayer->register($this->playerName, 'test');
    }

    protected function tearDown()
    {
        parent::tearDown();

        $dic = Dic::getInstance();
        $servicePlayer = $dic->serviceFactory()->player();
        $servicePlayer->deletePlayer($this->playerName);
    }

    public function testValidCards()
    {
        $dic = Dic::getInstance();
        $serviceDeck = $dic->serviceFactory()->deck();

        $cards = [
            'Common' => [
                1 => 54, 240, 71, 256, 250, 259, 261, 113, 247, 79, 57, 140, 7, 236, 387
            ],
            'Uncommon' => [
                1 => 28, 189, 83, 10, 204, 211, 230, 36, 216, 201, 53, 96, 146, 164, 208
            ],
            'Rare' => [
                1 => 32, 197, 75, 74, 151, 61, 69, 66, 232, 506, 291, 21, 126, 542, 181
            ]
        ];

        $serviceDeck->validateCards($this->playerName, $cards);
    }

    public function testInvalidNumberOfCards()
    {
        $dic = Dic::getInstance();
        $serviceDeck = $dic->serviceFactory()->deck();

        $cards = [
            'Common' => [
                1 => 1, 54, 240, 71, 256, 250, 259, 261, 113, 247, 79, 57, 140, 7, 236, 387
            ],
            'Uncommon' => [
                1 => 28, 189, 83, 10, 204, 211, 230, 36, 216, 201, 53, 96, 146, 164, 208
            ],
            'Rare' => [
                1 => 32, 197, 75, 74, 151, 61, 69, 66, 232, 506, 291, 21, 126, 542, 181
            ]
        ];

        $this->expectException(ArcomageException::class);
        $serviceDeck->validateCards($this->playerName, $cards);
    }

    public function testInvalidCardsDuplicates()
    {
        $dic = Dic::getInstance();
        $serviceDeck = $dic->serviceFactory()->deck();

        $cards = [
            'Common' => [
                1 => 54, 54, 71, 256, 250, 259, 261, 113, 247, 79, 57, 140, 7, 236, 387
            ],
            'Uncommon' => [
                1 => 28, 189, 83, 10, 204, 211, 230, 36, 216, 201, 53, 96, 146, 164, 208
            ],
            'Rare' => [
                1 => 32, 197, 75, 74, 151, 61, 69, 66, 232, 506, 291, 21, 126, 542, 181
            ]
        ];

        $this->expectException(ArcomageException::class);
        $serviceDeck->validateCards($this->playerName, $cards);
    }

    public function testInvalidCardsForbidden()
    {
        $dic = Dic::getInstance();
        $serviceDeck = $dic->serviceFactory()->deck();

        $cards = [
            'Common' => [
                1 => 248, 240, 71, 256, 250, 259, 261, 113, 247, 79, 57, 140, 7, 236, 387
            ],
            'Uncommon' => [
                1 => 28, 189, 83, 10, 204, 211, 230, 36, 216, 201, 53, 96, 146, 164, 208
            ],
            'Rare' => [
                1 => 32, 197, 75, 74, 151, 61, 69, 66, 232, 506, 291, 21, 126, 542, 181
            ]
        ];

        $this->expectException(ArcomageException::class);
        $serviceDeck->validateCards($this->playerName, $cards);
    }

    public function testInvalidCardsLevelRequirement()
    {
        $dic = Dic::getInstance();
        $serviceDeck = $dic->serviceFactory()->deck();

        $cards = [
            'Common' => [
                1 => 54, 240, 71, 256, 250, 259, 261, 113, 247, 79, 57, 140, 7, 236, 387
            ],
            'Uncommon' => [
                1 => 28, 189, 83, 10, 204, 211, 230, 36, 216, 201, 53, 96, 146, 164, 208
            ],
            'Rare' => [
                1 => 753, 197, 75, 74, 151, 61, 69, 66, 232, 506, 291, 21, 126, 542, 181
            ]
        ];

        $this->expectException(ArcomageException::class);
        $serviceDeck->validateCards($this->playerName, $cards);
    }
}
