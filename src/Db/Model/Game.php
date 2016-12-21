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
        return $this->getFieldValue('game_id');
    }

    /**
     * @return string
     */
    public function getPlayer1()
    {
        return $this->getFieldValue('player1');
    }

    /**
     * @return string
     */
    public function getPlayer2()
    {
        return $this->getFieldValue('player2');
    }

    /**
     * @return string
     */
    public function getState()
    {
        return $this->getFieldValue('state');
    }

    /**
     * @return string
     */
    public function getCurrent()
    {
        return $this->getFieldValue('current');
    }

    /**
     * @return int
     */
    public function getRound()
    {
        return $this->getFieldValue('round');
    }

    /**
     * @return string
     */
    public function getWinner()
    {
        return $this->getFieldValue('winner');
    }

    /**
     * @return string
     */
    public function getSurrender()
    {
        return $this->getFieldValue('surrender');
    }

    /**
     * @return string
     */
    public function getOutcomeType()
    {
        return $this->getFieldValue('outcome_type');
    }

    /**
     * @return string
     */
    public function getLastAction()
    {
        return $this->getFieldValue('last_action_at');
    }

    /**
     * @return string
     */
    public function getChatNotification1()
    {
        return $this->getFieldValue('chat_notification1');
    }

    /**
     * @return string
     */
    public function getChatNotification2()
    {
        return $this->getFieldValue('chat_notification2');
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->getFieldValue('data');
    }

    /**
     * @return int
     */
    public function getDeckId1()
    {
        return $this->getFieldValue('deck_id1');
    }

    /**
     * @return int
     */
    public function getDeckId2()
    {
        return $this->getFieldValue('deck_id2');
    }

    /**
     * @param string $player
     * @return string
     */
    public function getNote($player)
    {
        return (($this->getPlayer1() == $player) ? $this->getFieldValue('note1') : $this->getFieldValue('note2'));
    }

    /**
     * @return array
     */
    public function getGameModes()
    {
        return $this->getFieldValue('game_modes');
    }

    /**
     * @return int
     */
    public function getTimeout()
    {
        return $this->getFieldValue('turn_timeout');
    }

    /**
     * @return string
     */
    public function getAiName()
    {
        return $this->getFieldValue('ai_name');
    }

    /**
     * @param string $player
     * @return $this
     */
    public function setPlayer1($player)
    {
        return $this->setFieldValue('player1', $player);
    }

    /**
     * @param string $player
     * @return $this
     */
    public function setPlayer2($player)
    {
        return $this->setFieldValue('player2', $player);
    }

    /**
     * @param string $state
     * @return $this
     */
    public function setState($state)
    {
        return $this->setFieldValue('state', $state);
    }

    /**
     * @param string $current
     * @return $this
     */
    public function setCurrent($current)
    {
        return $this->setFieldValue('current', $current);
    }

    /**
     * @param int $round
     * @return $this
     */
    public function setRound($round)
    {
        return $this->setFieldValue('round', $round);
    }

    /**
     * @param string $winner
     * @return $this
     */
    public function setWinner($winner)
    {
        return $this->setFieldValue('winner', $winner);
    }

    /**
     * @param string $surrender
     * @return $this
     */
    public function setSurrender($surrender)
    {
        return $this->setFieldValue('surrender', $surrender);
    }

    /**
     * @param string $outcomeType
     * @return $this
     */
    public function setOutcomeType($outcomeType)
    {
        return $this->setFieldValue('outcome_type', $outcomeType);
    }

    /**
     * @param string $lastAction
     * @return $this
     */
    public function setLastAction($lastAction)
    {
        return $this->setFieldValue('last_action_at', $lastAction);
    }

    /**
     * @param string $chatNotification
     * @return $this
     */
    public function setChatNotification1($chatNotification)
    {
        return $this->setFieldValue('chat_notification1', $chatNotification);
    }

    /**
     * @param string $chatNotification
     * @return $this
     */
    public function setChatNotification2($chatNotification)
    {
        return $this->setFieldValue('chat_notification2', $chatNotification);
    }

    /**
     * @param array $data
     * @return $this
     */
    public function setData(array $data)
    {
        return $this->setFieldValue('data', $data);
    }

    /**
     * @param int $deckId
     * @return $this
     */
    public function setDeckId1($deckId)
    {
        return $this->setFieldValue('deck_id1', $deckId);
    }

    /**
     * @param int $deckId
     * @return $this
     */
    public function setDeckId2($deckId)
    {
        return $this->setFieldValue('deck_id2', $deckId);
    }

    /**
     * @param string $player
     * @param string $newContent
     * @return $this
     */
    public function setNote($player, $newContent)
    {
        if ($this->getPlayer1() == $player) {
            return $this->setFieldValue('note1', $newContent);
        }
        else {
            return $this->setFieldValue('note2', $newContent);
        }
    }

    /**
     * @param string $player
     * @return $this
     */
    public function resetChatNotification($player)
    {
        if ($this->getPlayer1() == $player) {
            return $this->setFieldValue('chat_notification1', Date::timeToStr());
        }
        else {
            return $this->setFieldValue('chat_notification2', Date::timeToStr());
        }
    }

    /**
     * @param array $gameModes
     * @return $this
     */
    public function setGameModes(array $gameModes)
    {
        return $this->setFieldValue('game_modes', $gameModes);
    }

    /**
     * @param int $timeout
     * @return $this
     */
    public function setTimeout($timeout)
    {
        return $this->setFieldValue('turn_timeout', $timeout);
    }

    /**
     * @param string $ai
     * @return $this
     */
    public function setAiName($ai)
    {
        return $this->setFieldValue('ai_name', $ai);
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
