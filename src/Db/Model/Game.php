<?php
/**
 * Game - representation of a game between two players
 */

namespace Db\Model;

use Util\Date;

class Game extends ModelAbstract
{
    /**
     * Maximum number of active games (base number, can be extended by extra game slots)
     */
    const MAX_GAMES = 15;

    /**
     * Bonus game slot cost in gold
     */
    const GAME_SLOT_COST = 200;

    /**
     * Number of cards in hand
     */
    const HAND_SIZE = 8;

    /**
     * @return int
     */
    public function getGameId()
    {
        return $this->getFieldValue('GameID');
    }

    /**
     * @return string
     */
    public function getPlayer1()
    {
        return $this->getFieldValue('Player1');
    }

    /**
     * @return string
     */
    public function getPlayer2()
    {
        return $this->getFieldValue('Player2');
    }

    /**
     * @return string
     */
    public function getState()
    {
        return $this->getFieldValue('State');
    }

    /**
     * @return string
     */
    public function getCurrent()
    {
        return $this->getFieldValue('Current');
    }

    /**
     * @return int
     */
    public function getRound()
    {
        return $this->getFieldValue('Round');
    }

    /**
     * @return string
     */
    public function getWinner()
    {
        return $this->getFieldValue('Winner');
    }

    /**
     * @return string
     */
    public function getSurrender()
    {
        return $this->getFieldValue('Surrender');
    }

    /**
     * @return string
     */
    public function getEndType()
    {
        return $this->getFieldValue('EndType');
    }

    /**
     * @return string
     */
    public function getLastAction()
    {
        return $this->getFieldValue('Last Action');
    }

    /**
     * @return string
     */
    public function getChatNotification1()
    {
        return $this->getFieldValue('ChatNotification1');
    }

    /**
     * @return string
     */
    public function getChatNotification2()
    {
        return $this->getFieldValue('ChatNotification2');
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->getFieldValue('Data');
    }

    /**
     * @return int
     */
    public function getDeckId1()
    {
        return $this->getFieldValue('DeckID1');
    }

    /**
     * @return int
     */
    public function getDeckId2()
    {
        return $this->getFieldValue('DeckID2');
    }

    /**
     * @param string $player
     * @return string
     */
    public function getNote($player)
    {
        return (($this->getPlayer1() == $player) ? $this->getFieldValue('Note1') : $this->getFieldValue('Note2'));
    }

    /**
     * @return array
     */
    public function getGameModes()
    {
        return $this->getFieldValue('GameModes');
    }

    /**
     * @return int
     */
    public function getTimeout()
    {
        return $this->getFieldValue('Timeout');
    }

    /**
     * @return string
     */
    public function getAI()
    {
        return $this->getFieldValue('AI');
    }

    /**
     * @param string $player
     * @return $this
     */
    public function setPlayer1($player)
    {
        return $this->setFieldValue('Player1', $player);
    }

    /**
     * @param string $player
     * @return $this
     */
    public function setPlayer2($player)
    {
        return $this->setFieldValue('Player2', $player);
    }

    /**
     * @param string $state
     * @return $this
     */
    public function setState($state)
    {
        return $this->setFieldValue('State', $state);
    }

    /**
     * @param string $current
     * @return $this
     */
    public function setCurrent($current)
    {
        return $this->setFieldValue('Current', $current);
    }

    /**
     * @param int $round
     * @return $this
     */
    public function setRound($round)
    {
        return $this->setFieldValue('Round', $round);
    }

    /**
     * @param string $winner
     * @return $this
     */
    public function setWinner($winner)
    {
        return $this->setFieldValue('Winner', $winner);
    }

    /**
     * @param string $surrender
     * @return $this
     */
    public function setSurrender($surrender)
    {
        return $this->setFieldValue('Surrender', $surrender);
    }

    /**
     * @param string $endType
     * @return $this
     */
    public function setEndType($endType)
    {
        return $this->setFieldValue('EndType', $endType);
    }

    /**
     * @param string $lastAction
     * @return $this
     */
    public function setLastAction($lastAction)
    {
        return $this->setFieldValue('Last Action', $lastAction);
    }

    /**
     * @param string $chatNotification
     * @return $this
     */
    public function setChatNotification1($chatNotification)
    {
        return $this->setFieldValue('ChatNotification1', $chatNotification);
    }

    /**
     * @param string $chatNotification
     * @return $this
     */
    public function setChatNotification2($chatNotification)
    {
        return $this->setFieldValue('ChatNotification2', $chatNotification);
    }

    /**
     * @param array $data
     * @return $this
     */
    public function setData(array $data)
    {
        return $this->setFieldValue('Data', $data);
    }

    /**
     * @param int $deckId
     * @return $this
     */
    public function setDeckId1($deckId)
    {
        return $this->setFieldValue('DeckID1', $deckId);
    }

    /**
     * @param int $deckId
     * @return $this
     */
    public function setDeckId2($deckId)
    {
        return $this->setFieldValue('DeckID2', $deckId);
    }

    /**
     * @param string $player
     * @param string $newContent
     * @return $this
     */
    public function setNote($player, $newContent)
    {
        if ($this->getPlayer1() == $player) {
            return $this->setFieldValue('Note1', $newContent);
        }
        else {
            return $this->setFieldValue('Note2', $newContent);
        }
    }

    /**
     * @param string $player
     * @return $this
     */
    public function resetChatNotification($player)
    {
        if ($this->getPlayer1() == $player) {
            return $this->setFieldValue('ChatNotification1', Date::timeToStr());
        }
        else {
            return $this->setFieldValue('ChatNotification2', Date::timeToStr());
        }
    }

    /**
     * @param array $gameModes
     * @return $this
     */
    public function setGameModes(array $gameModes)
    {
        return $this->setFieldValue('GameModes', $gameModes);
    }

    /**
     * @param int $timeout
     * @return $this
     */
    public function setTimeout($timeout)
    {
        return $this->setFieldValue('Timeout', $timeout);
    }

    /**
     * @param string $ai
     * @return $this
     */
    public function setAI($ai)
    {
        return $this->setFieldValue('AI', $ai);
    }

    /**
     * @return string
     */
    public function determineOpponent()
    {
        return ($this->getPlayer1() == $this->getCurrent()) ? $this->getPlayer2() : $this->getPlayer1();
    }

    /**
     * Checks if game mode is active for current game
     * @param string $mode game mode
     * @return bool true if game mode is present, false otherwise
     */
    public function checkGameMode($mode)
    {
        return in_array($mode, $this->getGameModes());
    }

    /**
     * Get player data
     * @param string $player player name
     * @return \CGamePlayerData
     */
    public function playerData($player)
    {
        $dataIndex = ($this->getPlayer1() == $player) ? 1 : 2;

        return $this->getData()[$dataIndex];
    }

    /**
     * Get game config setting value
     * @param string $key setting key
     * @return string setting value
     */
    public function config($key)
    {
        $gameConfig = self::gameConfig();

        // determine game mode (normal or long)
        $gameMode = ($this->checkGameMode('LongMode')) ? 'long' : 'normal';

        return (isset($gameConfig[$gameMode][$key])) ? $gameConfig[$gameMode][$key] : false;
    }

    /**
     * @return array
     */
    public static function listTimeoutValues()
    {
        return [
            0 => 'unlimited',
            86400 => '1 day',
            43200 => '12 hours',
            21600 => '6 hours',
            10800 => '3 hours',
            3600 => '1 hour',
            1800 => '30 minutes',
            300 => '5 minutes',
        ];
    }

    /**
     * @return array
     */
    public static function gameConfig()
    {
        // game configuration
        return [
            'normal' => [
                // starting tower height
                'init_tower' => 30,
                // maximum tower height
                'max_tower' => 100,
                // starting wall height
                'init_wall' => 25,
                // maximum wall height
                'max_wall' => 150,
                // sum of all resources
                'res_victory' => 400,
                // maximum number of rounds
                'time_victory' => 250,
            ],
            'long' => [
                // starting tower height
                'init_tower' => 45,
                // maximum tower height
                'max_tower' => 150,
                // starting wall height
                'init_wall' => 38,
                // maximum wall height
                'max_wall' => 225,
                // sum of all resources
                'res_victory' => 600,
                // maximum number of rounds
                'time_victory' => 375,
            ],
        ];
    }
}
