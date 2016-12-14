<?php
/**
 * Game - representation of games database
 */

namespace Db\Entity;

use Db\Model\Deck;
use Db\Model\Player;
use Util\Date;

class PdoGame extends PdoAbstract
{
    /**
     * DB schema
     * @return array
     */
    protected function schema()
    {
        return [
            'entity_name' => 'games',
            'model_name' => 'game',
            'primary_fields' => [
                'GameID',
            ],
            'fields' => [
                'GameID' => [
                    'type' => EntityAbstract::TYPE_INT32,
                    'default' => 0,
                    'options' => [
                        EntityAbstract::OPT_UNSIGNED,
                        EntityAbstract::OPT_AUTO_ID,
                    ],
                ],
                'Player1' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                ],
                'Player2' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                ],
                'State' => [
                    // 'waiting' / 'in progress' / 'finished' / 'P1 over' / 'P2 over'
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => 'waiting',
                ],
                'Current' => [
                    // name of the player whose turn it currently is
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                ],
                'Round' => [
                    // incremented after each play/discard action
                    'type' => EntityAbstract::TYPE_INT32,
                    'default' => 1,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'Winner' => [
                    // if not empty, name of the winner
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                ],
                'Surrender' => [
                    // if not empty, name of the player who requested to surrender
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                ],
                'EndType' => [
                    // game end type: 'Pending', 'Construction', 'Destruction', 'Resource', 'Timeout', 'Draw', 'Surrender', 'Abort', 'Abandon'
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => 'Pending',
                ],
                'Last Action' => [
                    'type' => EntityAbstract::TYPE_DATETIME,
                    'default' => Date::DATETIME_ZERO,
                    'options' => [EntityAbstract::OPT_INSERT_DATETIME],
                ],
                'ChatNotification1' => [
                    // timestamp of the last chat view for Player1
                    'type' => EntityAbstract::TYPE_DATETIME,
                    'default' => Date::DATETIME_ZERO,
                ],
                'ChatNotification2' => [
                    // timestamp of the last chat view for Player2
                    'type' => EntityAbstract::TYPE_DATETIME,
                    'default' => Date::DATETIME_ZERO,
                ],
                'Data' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                    'options' => [
                        EntityAbstract::OPT_NO_DIFF,
                        EntityAbstract::OPT_SERIALIZE_PHP,
                    ],
                ],
                'DeckID1' => [
                    // player's 1 deck slot reference ID (statistics purposes only)
                    'type' => EntityAbstract::TYPE_INT32,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'DeckID2' => [
                    // player's 2 deck slot reference ID (statistics purposes only)
                    'type' => EntityAbstract::TYPE_INT32,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'Note1' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                ],
                'Note2' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                ],
                'GameModes' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                    'options' => [
                        EntityAbstract::OPT_NO_DIFF,
                        EntityAbstract::OPT_SERIALIZE_LIST,
                    ],
                ],
                'Timeout' => [
                    'type' => EntityAbstract::TYPE_INT32,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'AI' => [
                    // AI challenge name (optional)
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                ],
            ],
        ];
    }

    /**
     * Create a new game
     * @param string $player1 player 1 name
     * @param string $player2 player 2 name
     * @param Deck $deck player 1 deck
     * @param array $gameModes game modes
     * @param int [$timeout] turn timeout setting
     * @return \Db\Model\Game
     */
    public function createGame($player1, $player2, Deck $deck, array $gameModes, $timeout = 0)
    {
        $player1Data = new \CGamePlayerData();
        $player1Data->Deck = $deck->getData();

        /* @var \Db\Model\Game $game */
        $game = parent::createModel([
            'Player1' => $player1,
            'Player2' => $player2,
            'DeckID1' => $deck->getDeckId(),
            'Timeout' => $timeout,
        ]);

        return $game
                ->setGameModes($gameModes)
                ->setData([
                    1 => $player1Data,
                    2 => new \CGamePlayerData(),
                ]);
    }

    /**
     * @param int $gameId game id
     * @param bool [$asserted]
     * @return \Db\Model\Game
     */
    public function getGame($gameId, $asserted = false)
    {
        return parent::getModel(['GameID' => $gameId], $asserted);
    }

    /**
     * @param int $gameId
     * @return \Db\Model\Game
     */
    public function getGameAsserted($gameId)
    {
        return $this->getGame($gameId, true);
    }

    /**
     * List all game ids for specified player
     * @param string $player player name
     * @return \Db\Util\Result
     */
    public function listGameIds($player)
    {
        $db = $this->db();

        return $db->query('SELECT `GameID` FROM `games` WHERE `Player1` = ? OR `Player2` = ?', [
            $player, $player
        ]);
    }

    /**
     * Delete all games specified by game ids
     * @param array $ids game ids
     * @return \Db\Util\Result
     */
    public function deleteGames($ids)
    {
        $db = $this->db();

        $queryString = $params = array();
        foreach ($ids as $gameId) {
            $queryString[] = '?';
            $params[] = $gameId;
        }

        return $db->query('DELETE FROM `games` WHERE `GameID` IN (' . implode(",", $queryString) . ')', $params);
    }

    /**
     * Counts active games for specified player
     * challenges are omitted when accepting a challenge
     * in all other cases they are counted as well
     * @param string $player player name
     * @param bool [$omitChallenges]
     * @return \Db\Util\Result
     */
    public function countActiveGames($player, $omitChallenges = false)
    {
        $db = $this->db();

        // outgoing = challenges from + hosted_games
        $outgoing = '`Player1` = ? AND `State` = "waiting"';

        // active games 1
        $games1 = '`Player1` = ? AND `State` != "waiting" AND `State` != "P1 over"';

        // active games 2
        $games2 = '`Player2` = ? AND `State` != "waiting" AND `State` != "P2 over"';

        // common query params
        $params = [$player, $player, $player];
        $challengesQuery = '';

        // process optional incoming challenges
        if (!$omitChallenges) {
            $challengesTo = '`Player2` = ? AND `State` = "waiting"';
            $challengesQuery = ' OR (' . $challengesTo . ')';

            $params[] = $player;
        }

        return $db->query(
            'SELECT COUNT(`GameID`) as `count` FROM `games` WHERE ('
            . $outgoing . ') OR (' . $games1 . ') OR (' . $games2 . ')'.$challengesQuery
            , $params
        );
    }

    /**
     * List challenges from player (player is on the left side and $Status = "waiting")
     * @param string $player player name
     * @return \Db\Util\Result
     */
    public function listChallengesFrom($player)
    {
        $db = $this->db();

        return $db->query('SELECT `Player2` FROM `games` WHERE `Player1` = ? AND `Player2` != "" AND `State` = "waiting"', [
            $player
        ]);
    }

    /**
     * List challenges to player (player is on the right side and $Status = "waiting")
     * @param string $player player name
     * @return \Db\Util\Result
     */
    public function listChallengesTo($player)
    {
        $db = $this->db();

        return $db->query('SELECT `Player1` FROM `games` WHERE `Player2` = ? AND `State` = "waiting"', [
            $player
        ]);
    }

    /**
     * List hosted games, where player can join
     * @param string $player player name
     * @param string [$hidden] hidden game mode filter
     * @param string [$friendly] friendly game mode filter
     * @param string [$long] long game mode filter
     * @return \Db\Util\Result
     */
    public function listFreeGames($player, $hidden = 'none', $friendly = 'none', $long = 'ignore')
    {
        $db = $this->db();

        $hiddenQuery = ($hidden != "none") ? ' AND FIND_IN_SET("HiddenCards", `GameModes`) ' . (($hidden == 'include') ? '>' : '=') . ' 0' : '';
        $friendlyQuery = ($friendly != "none") ? ' AND FIND_IN_SET("FriendlyPlay", `GameModes`) ' . (($friendly == 'include') ? '>' : '=') . ' 0' : '';
        $longQuery = ($long != "none") ? ' AND FIND_IN_SET("LongMode", `GameModes`) ' . (($long == 'include') ? '>' : '=') . ' 0' : '';

        return $db->query(
            'SELECT `GameID`, `Player1`, `Last Action`, `GameModes`, `Timeout` FROM `games`'
            . ' WHERE `Player1` != ? AND `Player2` = "" AND `State` = "waiting"'
            . $hiddenQuery . $friendlyQuery . $longQuery . ' ORDER BY `Last Action` DESC'
            , [$player]
        );
    }
    /**
     * List hosted games, hosted by specific player
     * @param string $player player name
     * @return \Db\Util\Result
     */
    public function listHostedGames($player)
    {
        $db = $this->db();

        return $db->query(
            'SELECT `GameID`, `Last Action`, `GameModes`, `Timeout` FROM `games`'
            . ' WHERE `Player1` = ? AND `Player2` = "" AND `State` = "waiting" ORDER BY `Last Action` DESC'
            , [$player]
        );
    }

    /**
     * List active games ids for specific player (player is either on the left or right side and Status != 'waiting' or 'P? over')
     * @param string $player player name
     * @return \Db\Util\Result
     */
    public function listActiveGames($player)
    {
        $db = $this->db();

        return $db->query(
            'SELECT `GameID` FROM `games` WHERE (`Player1` = ? AND `State` != "waiting" AND `State` != "P1 over")'
            . ' OR (`Player2` = ? AND `State` != "waiting" AND `State` != "P2 over")'
            , [$player, $player]
        );
    }

    /**
     * List active games for specific player (player is either on the left or right side and Status != 'waiting' or 'P? over')
     * @param string $player player name
     * @return \Db\Util\Result
     */
    public function listGamesData($player)
    {
        $db = $this->db();

        return $db->query(
            'SELECT `GameID`, `Player1`, `Player2`, `State`, `Current`, `Round`, `Last Action`, `GameModes`, `Timeout`, `AI` FROM `games`'
            . ' WHERE (`Player1` = ? AND `State` != "waiting" AND `State` != "P1 over")'
            . ' OR (`Player2` = ? AND `State` != "waiting" AND `State` != "P2 over") ORDER BY `Last Action` DESC'
            , [$player, $player]
        );
    }

    /**
     * Return number of games where it's specified player's turn
     * @param string $player player name
     * @return \Db\Util\Result
     */
    public function countCurrentGames($player)
    {
        $db = $this->db();

        return $db->query('SELECT COUNT(`GameID`) as `count` FROM `games` WHERE `Current` = ? AND `State` = "in progress"', [
            $player
        ]);
    }

    /**
     * Provide list of active games with opponent names
     * @param string $player player name
     * @return \Db\Util\Result
     */
    public function nextGameList($player)
    {
        $db = $this->db();

        return $db->query(
            'SELECT `GameID`, (CASE WHEN `Player1` = ? THEN `Player2` ELSE `Player1` END) as `Opponent` FROM `games`'
            . ' WHERE (`Player1` = ? OR `Player2` = ?) AND ((`State` = "in progress" AND ((`Current` = ? AND `Surrender` = "")'
            . ' OR (`Surrender` != ? AND `Surrender` != "") OR (`Timeout` > 0 AND `Last Action` <= NOW() - INTERVAL `Timeout` SECOND'
            . ' AND `Current` != ? AND `Player2` != ?))) OR `Player2` = ? OR `State` = "finished"'
            . ' OR (`State` = "P1 over" AND `Player2` = ?) OR (`State` = "P2 over" AND `Player1` = ?))'
            , [
            $player, $player, $player, $player, $player, $player
            , Player::SYSTEM_NAME, Player::SYSTEM_NAME, $player, $player
        ]);
    }

    /**
     * Check if there is already a game between two specified players
     * @param string$player1 player 1 name
     * @param string $player2 player 2 name
     * @return \Db\Util\Result
     */
    public function checkGame($player1, $player2)
    {
        $db = $this->db();

        return $db->query(
            'SELECT 1 FROM `games` WHERE `State` = "in progress" AND ((`Player1` = ? AND `Player2` = ?)'
            . ' OR (`Player1` = ? AND `Player2` = ?)) LIMIT 1'
            , [$player1, $player2, $player2, $player1]
        );
    }

    /**
     * Rename all player name instances in games (player 1)
     * @param string $player player name
     * @param string $new_name new name
     * @return \Db\Util\Result
     */
    public function renamePlayer1($player, $new_name)
    {
        $db = $this->db();

        return $db->query('UPDATE `games` SET `Player1` = ? WHERE `Player1` = ?', [
            $new_name, $player
        ]);
    }

    /**
     * Rename all player name instances in games (player 2)
     * @param string $player player name
     * @param string $new_name new name
     * @return \Db\Util\Result
     */
    public function renamePlayer2($player, $new_name)
    {
        $db = $this->db();

        return $db->query('UPDATE `games` SET `Player2` = ? WHERE `Player2` = ?', [
            $new_name, $player
        ]);
    }

    /**
     * Rename all player name instances in games (current)
     * @param string $player player name
     * @param string $newName new name
     * @return \Db\Util\Result
     */
    public function renameCurrent($player, $newName)
    {
        $db = $this->db();

        return $db->query('UPDATE `games` SET `Current` = ? WHERE `Current` = ?', [
            $newName, $player
        ]);
    }

    /**
     * Rename all player name instances in games (winner)
     * @param string $player player name
     * @param string $newName new name
     * @return \Db\Util\Result
     */
    public function renameWinner($player, $newName)
    {
        $db = $this->db();

        return $db->query('UPDATE `games` SET `Winner` = ? WHERE `Winner` = ?', [
            $newName, $player
        ]);
    }

    /**
     * Rename all player name instances in games (surrender)
     * @param string $player player name
     * @param string $newName new name
     * @return \Db\Util\Result
     */
    public function renameSurrender($player, $newName)
    {
        $db = $this->db();

        return $db->query('UPDATE `games` SET `Surrender` = ? WHERE `Surrender` = ?', [
            $newName, $player
        ]);
    }
}
