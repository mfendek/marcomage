<?php
/**
 * Replay - game replays database
 */

namespace Db\Entity;

use Db\Model\Game;
use Db\Model\Player;
use Db\Model\Replay;
use Util\Date;

class PdoReplay extends PdoAbstract
{
    /**
     * DB schema
     * @return array
     */
    protected function schema()
    {
        return [
            'entity_name' => 'replays',
            'model_name' => 'replay',
            'primary_fields' => [
                'GameID',
            ],
            'fields' => [
                'GameID' => [
                    'type' => EntityAbstract::TYPE_INT32,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'Player1' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                ],
                'Player2' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                ],
                'Rounds' => [
                    'type' => EntityAbstract::TYPE_INT32,
                    'default' => 1,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'Turns' => [
                    'type' => EntityAbstract::TYPE_INT32,
                    'default' => 1,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'Winner' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                ],
                'EndType' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => 'Pending',
                ],
                'GameModes' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                    'options' => [
                        EntityAbstract::OPT_NO_DIFF,
                        EntityAbstract::OPT_SERIALIZE_LIST,
                    ],
                ],
                'AI' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                ],
                'Started' => [
                    'type' => EntityAbstract::TYPE_DATETIME,
                    'default' => Date::DATETIME_ZERO,
                    'options' => [EntityAbstract::OPT_INSERT_DATETIME],
                ],
                'Finished' => [
                    'type' => EntityAbstract::TYPE_DATETIME,
                    'default' => Date::DATETIME_ZERO,
                ],
                'Deleted' => [
                    'type' => EntityAbstract::TYPE_INT32,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'Views' => [
                    'type' => EntityAbstract::TYPE_INT32,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'ThreadID' => [
                    'type' => EntityAbstract::TYPE_INT32,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'Data' => [
                    'type' => EntityAbstract::TYPE_BINARY,
                    'default' => '',
                    'options' => [
                        EntityAbstract::OPT_NO_DIFF,
                        EntityAbstract::OPT_SERIALIZE_PHP,
                        EntityAbstract::OPT_SERIALIZE_GZIP,
                    ],
                ],
            ],
        ];
    }

    /**
     * Create new replay
     * @param Game $game
     * @return Replay
     */
    public function createReplay(Game $game)
    {
        $player1 = $game->getPlayer1();

        // clone prevents original game object from being damaged
        $gameData = $game->getData();
        $gameDataClone = array();
        $gameDataClone[1] = clone $gameData[1];
        $gameDataClone[2] = clone $gameData[2];

        // remove decks (replays don't need them)
        unset($gameDataClone[1]->Deck);
        unset($gameDataClone[2]->Deck);

        // prepare data of the first turn of the replay
        $turnData = new \CReplayTurn();
        $turnData->Current = ($game->getCurrent() == $player1) ? 1 : 2;

        // rounds counter starts at 1
        $turnData->Round = 1;
        $turnData->GameData = $gameDataClone;
        $replayData = array();
        $replayData[1] = $turnData;

        $gameModes = array();
        if ($game->checkGameMode('HiddenCards')) {
            $gameModes[] = 'HiddenCards';
        }
        if ($game->checkGameMode('FriendlyPlay')) {
            $gameModes[] = 'FriendlyPlay';
        }
        if ($game->checkGameMode('LongMode')) {
            $gameModes[] = 'LongMode';
        }
        if ($game->checkGameMode('AIMode')) {
            $gameModes[] = 'AIMode';
        }

        /* @var $replay Replay */
        $replay = parent::createModel([
            'GameID' => $game->getGameId(),
            'Player1' => $player1,
            'Player2' => $game->getPlayer2(),
            'AI' => $game->getAI(),
        ]);

        return $replay
                ->setData($replayData)
                ->setGameModes($gameModes);
    }

    /**
     * @param int $gameId game id
     * @param bool [$asserted]
     * @return Replay
     */
    public function getReplay($gameId, $asserted = false)
    {
        return parent::getModel(['GameID' => $gameId], $asserted);
    }

    /**
     * @param int $gameId
     * @return Replay
     */
    public function getReplayAsserted($gameId)
    {
        return $this->getReplay($gameId, true);
    }

    /**
     * Delete all replays attached to specified games
     * @param array $ids game ids
     * @return \Db\Util\Result
     */
    public function deleteReplays(array $ids)
    {
        $db = $this->db();

        $queryString = $params = array();
        foreach ($ids as $gameId) {
            $queryString[] = '?';
            $params[] = $gameId;
        }

        return $db->query('DELETE FROM `replays` WHERE `GameID` IN (' . implode(",", $queryString) . ')', $params);
    }

    /**
     * List replays according to specified filters
     * @param string $player player filter
     * @param string $hidden hidden mode filter
     * @param string $friendly friendly mode filter
     * @param string $long long mode filter
     * @param string $ai ai mode filter
     * @param string $challenge challenge mode filter
     * @param string $victory victory filter
     * @param string $condition order condition
     * @param string $order order option
     * @param int $page current page
     * @return \Db\Util\Result
     */
    public function listReplays($player, $hidden, $friendly, $long, $ai, $challenge, $victory, $condition, $order, $page)
    {
        $db = $this->db();

        // TODO optimize: merge all filters into one condition?

        $playerQuery = ($player != '') ? 'AND (`Player1` LIKE ? OR `Player2` LIKE ?)' : '';
        $hiddenQuery = ($hidden != 'none') ? ' AND FIND_IN_SET("HiddenCards", `GameModes`) ' . (($hidden == 'include') ? '>' : '=') . ' 0' : '';
        $friendlyQuery = ($friendly != "none") ? ' AND FIND_IN_SET("FriendlyPlay", `GameModes`) ' . (($friendly == 'include') ? '>' : '=') . ' 0' : '';
        $longQuery = ($long != 'none') ? ' AND FIND_IN_SET("LongMode", `GameModes`) ' . (($long == 'include') ? '>' : '=') . ' 0' : '';
        $aiQuery = ($ai != 'none') ? ' AND FIND_IN_SET("AIMode", `GameModes`) ' . (($ai == 'include') ? '>' : '=') . ' 0' : '';
        $chQuery = ($challenge != 'none') ? (($challenge == 'include') ? ' AND `AI` != ""' : (($challenge == 'exclude') ? ' AND `AI` = ""' : ' AND `AI` = ?')) : '';
        $victoryQuery = ($victory != 'none') ? '`EndType` = ?' : '`EndType` != "Pending"';

        $params = array();
        if ($victory != 'none') {
            $params[] = $victory;
        }
        if ($player != '') {
            $params[] = '%' . $player . '%';
            $params[] = '%' . $player . '%';
        }
        if (!in_array($challenge, ['none', 'include', 'exclude'])) {
            $params[] = $challenge;
        }

        $condition = (in_array($condition, [
            'Winner', 'Rounds', 'Turns', 'Started', 'Finished'
        ])) ? $condition : 'Finished';
        $order = ($order == 'ASC') ? 'ASC' : 'DESC';
        $page = (is_numeric($page)) ? $page : 0;

        return $db->query(
            'SELECT `GameID`, `Player1`, `Player2`, `Started`, `Finished`, `Rounds`, `Turns`, `GameModes`, `AI`'
            . ', `Winner`, `EndType`, (CASE WHEN `Deleted` = TRUE THEN "yes" ELSE "no" END) as `Deleted`, `Views` FROM `replays`'
            . ' WHERE ' . $victoryQuery . $playerQuery . $hiddenQuery . $friendlyQuery . $longQuery . $aiQuery . $chQuery
            . ' ORDER BY `' . $condition . '` ' . $order . ' LIMIT '
            . (Replay::REPLAYS_PER_PAGE * $page) . ' , ' . Replay::REPLAYS_PER_PAGE . ''
            , $params
        );
    }

    /**
     * Count pages for replays list
     * @param string $player player filter
     * @param string $hidden hidden mode filter
     * @param string $friendly friendly mode filter
     * @param string $long long mode filter
     * @param string $ai ai mode filter
     * @param string $challenge challenge mode filter
     * @param string $victory victory filter
     * @return \Db\Util\Result
     */
    public function countPages($player, $hidden, $friendly, $long, $ai, $challenge, $victory)
    {
        $db = $this->db();

        $playerQuery = ($player != '') ? 'AND (`Player1` LIKE ? OR `Player2` LIKE ?)' : '';
        $hiddenQuery = ($hidden != 'none') ? ' AND FIND_IN_SET("HiddenCards", `GameModes`) ' . (($hidden == 'include') ? '>' : '=') . ' 0' : '';
        $friendlyQuery = ($friendly != "none") ? ' AND FIND_IN_SET("FriendlyPlay", `GameModes`) ' . (($friendly == 'include') ? '>' : '=') . ' 0' : '';
        $longQuery = ($long != 'none') ? ' AND FIND_IN_SET("LongMode", `GameModes`) ' . (($long == 'include') ? '>' : '=') . ' 0' : '';
        $aiQuery = ($ai != 'none') ? ' AND FIND_IN_SET("AIMode", `GameModes`) ' . (($ai == 'include') ? '>' : '=') . ' 0' : '';
        $chQuery = ($challenge != 'none') ? (($challenge == 'include') ? ' AND `AI` != ""' : (($challenge == 'exclude') ? ' AND `AI` = ""' : ' AND `AI` = ?')) : '';
        $victoryQuery = ($victory != 'none') ? '`EndType` = ?' : '`EndType` != "Pending"';

        $params = array();
        if ($victory != 'none') {
            $params[] = $victory;
        }
        if ($player != '') {
            $params[] = '%' . $player . '%';
            $params[] = '%' . $player . '%';
        }
        if (!in_array($challenge, ['none', 'include', 'exclude'])) {
            $params[] = $challenge;
        }

        return $db->query(
            'SELECT COUNT(`GameID`) as `Count` FROM `replays` WHERE '
            . $victoryQuery . $playerQuery . $hiddenQuery . $friendlyQuery . $longQuery . $aiQuery . $chQuery . ''
            , $params
        );
    }

    /**
     * Find replay by forum thread id
     * @param int $threadId
     * @return \Db\Util\Result
     */
    public function findReplay($threadId)
    {
        $db = $this->db();

        return $db->query('SELECT `GameID` FROM `replays` WHERE `ThreadID` = ?', [$threadId]);
    }

    /**
     * Rename all player name instances in replays (player 1)
     * @param string $player player name
     * @param string $newName new name
     * @return \Db\Util\Result
     */
    public function renamePlayer1($player, $newName)
    {
        $db = $this->db();

        return $db->query('UPDATE `replays` SET `Player1` = ? WHERE `Player1` = ?', [$newName, $player]);
    }

    /**
     * Rename all player name instances in replays (player 2)
     * @param string $player player name
     * @param string $newName new name
     * @return \Db\Util\Result
     */
    public function renamePlayer2($player, $newName)
    {
        $db = $this->db();

        return $db->query('UPDATE `replays` SET `Player2` = ? WHERE `Player2` = ?', [$newName, $player]);
    }

    /**
     * Rename all player name instances in replays (winner)
     * @param string $player player name
     * @param string $newName new name
     * @return \Db\Util\Result
     */
    public function renameWinner($player, $newName)
    {
        $db = $this->db();

        return $db->query('UPDATE `replays` SET `Winner` = ? WHERE `Winner` = ?', [$newName, $player]);
    }

    /**
     * List victory types with their counts
     * @return \Db\Util\Result
     */
    public function listVictoryTypes()
    {
        $db = $this->db();

        // get number of different victory types
        return $db->query(
            'SELECT `EndType`, COUNT(`EndType`) as `count` FROM `replays` WHERE `EndType` != "Pending" GROUP BY `EndType`'
        );
    }

    /**
     * List game modes with their counts
     * @return \Db\Util\Result
     */
    public function listGameModes()
    {
        $db = $this->db();

        return $db->query(
            'SELECT `GameModes`, COUNT(`GameModes`) as `count` FROM `replays`'
            . ' WHERE `EndType` != "Pending" GROUP BY `GameModes`'
        );
    }

    /**
     * Count number of AI mode games (exclude AI challenges)
     * @return \Db\Util\Result
     */
    public function countAiGames()
    {
        $db = $this->db();

        return $db->query(
            'SELECT COUNT(`GameID`) as `ai` FROM `replays`'
            . ' WHERE `EndType` != "Pending" AND FIND_IN_SET("AIMode", `GameModes`) > 0 AND `AI` = ""'
        );
    }

    /**
     * Count number of AI victories (exclude AI challenges)
     * @return \Db\Util\Result
     */
    public function countAiVictories()
    {
        $db = $this->db();

        return $db->query(
            'SELECT COUNT(`GameID`) as `ai_wins` FROM `replays`'
            . ' WHERE `EndType` != "Pending" AND FIND_IN_SET("AIMode", `GameModes`) > 0 AND `AI` = "" AND `Winner` = ?'
            , [Player::SYSTEM_NAME]
        );
    }

    /**
     * Count number of AI challenge games
     * @return \Db\Util\Result
     */
    public function countAiChallenges()
    {
        $db = $this->db();

        return $db->query(
            'SELECT COUNT(`GameID`) as `challenge` FROM `replays` WHERE `EndType` != "Pending" AND `AI` != ""'
        );
    }

    /**
     * Count number of AI challenge victories
     * @return \Db\Util\Result
     */
    public function countAiChallengeVictories()
    {
        $db = $this->db();

        return $db->query(
            'SELECT COUNT(`GameID`) as `challenge_wins` FROM `replays` WHERE `EndType` != "Pending" AND `AI` != "" AND `Winner` = ?'
            , [Player::SYSTEM_NAME]
        );
    }

    /**
     * List game modes with their counts for specified players and game outcome
     * @param string $player1
     * @param string $player2
     * @param string $winner
     * @return \Db\Util\Result
     */
    public function listGameModesVersus($player1, $player2, $winner)
    {
        $db = $this->db();

        return $db->query(
            'SELECT `EndType`, COUNT(`EndType`) as `count` FROM `replays`'
            . ' WHERE `EndType` != "Pending" AND ((`Player1` = ? AND `Player2` = ?)'
            . ' OR (`Player1` = ? AND `Player2` = ?)) AND `Winner` = ? GROUP BY `EndType` ORDER BY `count` DESC'
            , [$player1, $player2, $player2, $player1, $winner]
        );
    }

    /**
     * Count average game duration (normal mode) versus other player
     * @param string $player1
     * @param string $player2
     * @return \Db\Util\Result
     */
    public function versusGameDurationNormal($player1, $player2)
    {
        $db = $this->db();

        return $db->query(
            'SELECT ROUND(IFNULL(AVG(`Turns`), 0), 1) as `Turns`, ROUND(IFNULL(AVG(`Rounds`), 0), 1) as `Rounds` FROM `replays`'
            . ' WHERE `EndType` != "Pending" AND FIND_IN_SET("LongMode", `GameModes`) = 0 AND ((`Player1` = ? AND `Player2` = ?)'
            . ' OR (`Player1` = ? AND `Player2` = ?))'
            , [$player1, $player2, $player2, $player1]
        );
    }

    /**
     * Count average game duration (long mode) versus other player
     * @param string $player1
     * @param string $player2
     * @return \Db\Util\Result
     */
    public function versusGameDurationLong($player1, $player2)
    {
        $db = $this->db();

        return $db->query(
            'SELECT ROUND(IFNULL(AVG(`Turns`), 0), 1) as `Turns`, ROUND(IFNULL(AVG(`Rounds`), 0), 1) as `Rounds` FROM `replays`'
            . ' WHERE `EndType` != "Pending" AND FIND_IN_SET("LongMode", `GameModes`) > 0'
            . ' AND ((`Player1` = ? AND `Player2` = ?) OR (`Player1` = ? AND `Player2` = ?))'
            , [$player1, $player2, $player2, $player1]
        );
    }

    /**
     * Count wins for specified player
     * @param string $player
     * @return \Db\Util\Result
     */
    public function countPlayerWins($player)
    {
        $db = $this->db();

        return $db->query(
            'SELECT `EndType`, COUNT(`EndType`) as `count` FROM `replays`'
            . ' WHERE `EndType` != "Pending" AND (`Player1` = ? OR `Player2` = ?) AND `Winner` = ? GROUP BY `EndType`'
            . ' ORDER BY `count` DESC'
            , [$player, $player, $player]
        );
    }

    /**
     * Count losses for specified player
     * @param string $player
     * @return \Db\Util\Result
     */
    public function countPlayerLosses($player)
    {
        $db = $this->db();

        return $db->query(
            'SELECT `EndType`, COUNT(`EndType`) as `count` FROM `replays`'
            . ' WHERE `EndType` != "Pending" AND (`Player1` = ? OR `Player2` = ?) AND `Winner` != ? AND `Winner` != ""'
            . ' GROUP BY `EndType` ORDER BY `count` DESC'
            , [$player, $player, $player]
        );
    }

    /**
     * Count draws (and other non-wins non-losses) for specified player
     * @param string $player
     * @return \Db\Util\Result
     */
    public function countPlayerDraws($player)
    {
        $db = $this->db();

        return $db->query(
            'SELECT `EndType`, COUNT(`EndType`) as `count` FROM `replays`'
            . ' WHERE `EndType` != "Pending" AND (`Player1` = ? OR `Player2` = ?) AND `Winner` = "" GROUP BY `EndType`'
            . ' ORDER BY `count` DESC'
            , [$player, $player]
        );
    }

    /**
     * Count average game duration (normal mode) in total
     * @param string $player
     * @return \Db\Util\Result
     */
    public function totalGameDurationNormal($player)
    {
        $db = $this->db();

        return $db->query(
            'SELECT ROUND(IFNULL(AVG(`Turns`), 0), 1) as `Turns`, ROUND(IFNULL(AVG(`Rounds`), 0), 1) as `Rounds` FROM `replays`'
            . ' WHERE `EndType` != "Pending" AND FIND_IN_SET("LongMode", `GameModes`) = 0 AND (`Player1` = ? OR `Player2` = ?)'
            , [$player, $player]
        );
    }

    /**
     * Count average game duration (long mode) in total
     * @param string $player
     * @return \Db\Util\Result
     */
    public function totalGameDurationLong($player)
    {
        $db = $this->db();

        return $db->query(
            'SELECT ROUND(IFNULL(AVG(`Turns`), 0), 1) as `Turns`, ROUND(IFNULL(AVG(`Rounds`), 0), 1) as `Rounds` FROM `replays`'
            . ' WHERE `EndType` != "Pending" AND FIND_IN_SET("LongMode", `GameModes`) > 0 AND (`Player1` = ? OR `Player2` = ?)'
            , [$player, $player]
        );
    }
}
