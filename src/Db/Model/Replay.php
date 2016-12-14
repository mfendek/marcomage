<?php
/**
 * Replay - game replay
 */

namespace Db\Model;

use Util\Date;

class Replay extends ModelAbstract
{
    /**
     * Number of replays that are displayed per one page
     */
    const REPLAYS_PER_PAGE = 20;

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
     * @return int
     */
    public function getRounds()
    {
        return $this->getFieldValue('Rounds');
    }

    /**
     * @return int
     */
    public function getTurns()
    {
        return $this->getFieldValue('Turns');
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
    public function getEndType()
    {
        return $this->getFieldValue('EndType');
    }

    /**
     * @return array
     */
    public function getGameModes()
    {
        return $this->getFieldValue('GameModes');
    }

    /**
     * @return string
     */
    public function getAi()
    {
        return $this->getFieldValue('AI');
    }

    /**
     * @return string
     */
    public function getStarted()
    {
        return $this->getFieldValue('Started');
    }

    /**
     * @return string
     */
    public function getFinished()
    {
        return $this->getFieldValue('Finished');
    }

    /**
     * @return string
     */
    public function getDeleted()
    {
        return $this->getFieldValue('Deleted');
    }

    /**
     * @return int
     */
    public function getViews()
    {
        return $this->getFieldValue('Views');
    }

    /**
     * @return int
     */
    public function getThreadId()
    {
        return $this->getFieldValue('ThreadID');
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->getFieldValue('Data');
    }

    /**
     * @param string $player1
     * @return $this
     */
    public function setPlayer1($player1)
    {
        return $this->setFieldValue('Player1', $player1);
    }

    /**
     * @param string $player2
     * @return $this
     */
    public function setPlayer2($player2)
    {
        return $this->setFieldValue('Player2', $player2);
    }

    /**
     * @param int $rounds
     * @return $this
     */
    public function setRounds($rounds)
    {
        return $this->setFieldValue('Rounds', $rounds);
    }

    /**
     * @param int $turns
     * @return $this
     */
    public function setTurns($turns)
    {
        return $this->setFieldValue('Turns', $turns);
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
     * @param string $endType
     * @return $this
     */
    public function setEndType($endType)
    {
        return $this->setFieldValue('EndType', $endType);
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
     * @param string $ai
     * @return $this
     */
    public function setAi($ai)
    {
        return $this->setFieldValue('AI', $ai);
    }

    /**
     * @param string $started
     * @return $this
     */
    public function setStarted($started)
    {
        return $this->setFieldValue('Started', $started);
    }

    /**
     * @param string $finished
     * @return $this
     */
    public function setFinished($finished)
    {
        return $this->setFieldValue('Finished', $finished);
    }

    /**
     * @param int $deleted
     * @return $this
     */
    public function setDeleted($deleted)
    {
        return $this->setFieldValue('Deleted', $deleted);
    }

    /**
     * @param int $views
     * @return $this
     */
    public function setViews($views)
    {
        return $this->setFieldValue('Views', $views);
    }

    /**
     * @param int $threadId
     * @return $this
     */
    public function setThreadId($threadId)
    {
        return $this->setFieldValue('ThreadID', $threadId);
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
     * Checks if game mode is active for current game
     * @param string $mode game mode
     * @return bool true if game mode is present, false otherwise
     */
    public function checkGameMode($mode)
    {
        return in_array($mode, $this->getGameModes());
    }

    /**
     * Update replay data
     * @param Game $game
     */
    public function update(Game $game)
    {
        $this->setTurns($this->getTurns() + 1);
        $this->setRounds($game->getRound());
        $player1 = $game->getPlayer1();

        // clone prevents original game object from being damaged
        $gameData = $game->getData();
        $gameDataClone = array();
        $gameDataClone[1] = clone $gameData[1];
        $gameDataClone[2] = clone $gameData[2];

        // remove decks (replays don't need them)
        unset($gameDataClone[1]->Deck);
        unset($gameDataClone[2]->Deck);

        // prepare data of the current turn of the replay
        $turnData = new \CReplayTurn();
        $turnData->Current = ($game->getCurrent() == $player1) ? 1 : 2;
        $turnData->Round = $game->getRound();
        $turnData->GameData = $gameDataClone;

        $replayData = $this->getData();
        $replayData[$this->getTurns()] = $turnData;
        $this->setData($replayData);

        // finish replay in case the game is finished
        if ($game->getState() == 'finished') {
            $this->setWinner($game->getWinner());
            $this->setEndType($game->getEndType());
            $this->setFinished(Date::timeToStr());
        }
    }

    /**
     * Get specific turn data
     * @param int $turnNumber turn number
     * @return \CReplayTurn if operation was successful, false otherwise
     */
    public function getTurn($turnNumber)
    {
        if (!is_numeric($turnNumber) || $turnNumber < 1 || $turnNumber > $this->getTurns() || !isset($this->getData()[$turnNumber])) {
            return null;
        }

        $turnData = clone $this->getData()[$turnNumber];

        // transform symbolic names to real names
        $turnData->Current = ($turnData->Current == 1) ? $this->getPlayer1() : $this->getPlayer2();

        return $turnData;
    }

    /**
     * Search for the first turn of the current round
     * @return \CReplayTurn if operation was successful, false otherwise
     */
    public function lastRound()
    {
        $turn = $this->getTurns();
        $round = $this->getRounds();
        $result = null;

        while ($round == $this->getRounds() && $turn >= 1) {
            $turnData = $this->getTurn($turn);
            if (empty($turnData)) {
                return null;
            }

            $round = $turnData->Round;
            if ($round == $this->getRounds()) {
                $result = $turnData;
            }

            $turn--;
        }

        return $result;
    }
}
