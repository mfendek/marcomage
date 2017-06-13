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
            'entity_name' => 'game',
            'primary_fields' => [
                'game_id',
            ],
            'fields' => [
                'game_id' => [
                    'type' => EntityAbstract::TYPE_INT,
                    'default' => 0,
                    'options' => [
                        EntityAbstract::OPT_UNSIGNED,
                        EntityAbstract::OPT_AUTO_ID,
                    ],
                ],
                'player1' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                ],
                'player2' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                ],
                'state' => [
                    // 'waiting' / 'in progress' / 'finished' / 'P1 over' / 'P2 over'
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => 'waiting',
                ],
                'current' => [
                    // name of the player whose turn it currently is
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                ],
                'round' => [
                    // incremented after each play/discard action
                    'type' => EntityAbstract::TYPE_INT,
                    'default' => 1,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'winner' => [
                    // if not empty, name of the winner
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                ],
                'surrender' => [
                    // if not empty, name of the player who requested to surrender
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                ],
                'outcome_type' => [
                    // game outcome type: 'Pending', 'Construction', 'Destruction', 'Resource', 'Timeout', 'Draw', 'Surrender', 'Abort', 'Abandon'
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => 'Pending',
                ],
                'last_action_at' => [
                    'type' => EntityAbstract::TYPE_DATETIME,
                    'default' => Date::DATETIME_ZERO,
                    'options' => [EntityAbstract::OPT_INSERT_DATETIME],
                ],
                'chat_notification1' => [
                    // timestamp of the last chat view for Player1
                    'type' => EntityAbstract::TYPE_DATETIME,
                    'default' => Date::DATETIME_ZERO,
                ],
                'chat_notification2' => [
                    // timestamp of the last chat view for Player2
                    'type' => EntityAbstract::TYPE_DATETIME,
                    'default' => Date::DATETIME_ZERO,
                ],
                'data' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                    'options' => [
                        EntityAbstract::OPT_NO_DIFF,
                        EntityAbstract::OPT_SERIALIZE_PHP,
                    ],
                ],
                'deck_id1' => [
                    // player's 1 deck slot reference ID (statistics purposes only)
                    'type' => EntityAbstract::TYPE_INT,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'deck_id2' => [
                    // player's 2 deck slot reference ID (statistics purposes only)
                    'type' => EntityAbstract::TYPE_INT,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'note1' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                ],
                'note2' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                ],
                'game_modes' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                    'options' => [
                        EntityAbstract::OPT_NO_DIFF,
                        EntityAbstract::OPT_SERIALIZE_LIST,
                    ],
                ],
                'turn_timeout' => [
                    'type' => EntityAbstract::TYPE_INT,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'ai_name' => [
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
            'player1' => $player1,
            'player2' => $player2,
            'deck_id1' => $deck->getDeckId(),
            'turn_timeout' => $timeout,
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
        return parent::getModel(['game_id' => $gameId], $asserted);
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

        return $db->query('SELECT `game_id` FROM `game` WHERE `player1` = ? OR `player2` = ?', [
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

        return $db->query('DELETE FROM `game` WHERE `game_id` IN (' . implode(",", $queryString) . ')', $params);
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
        $outgoing = '`player1` = ? AND `state` = "waiting"';

        // active games 1
        $games1 = '`player1` = ? AND `state` != "waiting" AND `state` != "P1 over"';

        // active games 2
        $games2 = '`player2` = ? AND `state` != "waiting" AND `state` != "P2 over"';

        // common query params
        $params = [$player, $player, $player];
        $challengesQuery = '';

        // process optional incoming challenges
        if (!$omitChallenges) {
            $challengesTo = '`player2` = ? AND `state` = "waiting"';
            $challengesQuery = ' OR (' . $challengesTo . ')';

            $params[] = $player;
        }

        return $db->query(
            'SELECT COUNT(`game_id`) as `count` FROM `game` WHERE ('
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

        return $db->query('SELECT `player2` FROM `game` WHERE `player1` = ? AND `player2` != "" AND `state` = "waiting"', [
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

        return $db->query('SELECT `player1` FROM `game` WHERE `player2` = ? AND `state` = "waiting"', [
            $player
        ]);
    }

    /**
     * List hosted games, where player can join
     * @param string $player player name
     * @param string array $data
     * @return \Db\Util\Result
     */
    public function listFreeGames($player, array $data)
    {
        $db = $this->db();

        $hidden = (isset($data['hidden'])) ? $data['hidden'] : 'none';
        $friendly = (isset($data['friendly'])) ? $data['friendly'] : 'none';
        $long = (isset($data['long'])) ? $data['long'] : 'ignore';

        $hiddenQuery = ($hidden != "none")
            ? ' AND FIND_IN_SET("HiddenCards", `game_modes`) ' . (($hidden == 'include') ? '>' : '=') . ' 0' : '';
        $friendlyQuery = ($friendly != "none")
            ? ' AND FIND_IN_SET("FriendlyPlay", `game_modes`) ' . (($friendly == 'include') ? '>' : '=') . ' 0' : '';
        $longQuery = ($long != "none")
            ? ' AND FIND_IN_SET("LongMode", `game_modes`) ' . (($long == 'include') ? '>' : '=') . ' 0' : '';

        return $db->query(
            'SELECT `game_id`, `player1`, `last_action_at`, `game_modes`, `turn_timeout` FROM `game`'
            . ' WHERE `player1` != ? AND `player2` = "" AND `state` = "waiting"'
            . $hiddenQuery . $friendlyQuery . $longQuery . ' ORDER BY `last_action_at` DESC'
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
            'SELECT `game_id`, `last_action_at`, `game_modes`, `turn_timeout` FROM `game`'
            . ' WHERE `player1` = ? AND `player2` = "" AND `state` = "waiting" ORDER BY `last_action_at` DESC'
            , [$player]
        );
    }

    /**
     * List active games ids for specific player
     * (player is either on the left or right side and Status != 'waiting' or 'P? over')
     * @param string $player player name
     * @return \Db\Util\Result
     */
    public function listActiveGames($player)
    {
        $db = $this->db();

        return $db->query(
            'SELECT `game_id` FROM `game` WHERE (`player1` = ? AND `state` != "waiting" AND `state` != "P1 over")'
            . ' OR (`player2` = ? AND `state` != "waiting" AND `state` != "P2 over")'
            , [$player, $player]
        );
    }

    /**
     * List active games for specific player
     * (player is either on the left or right side and Status != 'waiting' or 'P? over')
     * @param string $player player name
     * @return \Db\Util\Result
     */
    public function listGamesData($player)
    {
        $db = $this->db();

        return $db->query(
            'SELECT `game_id`, `player1`, `player2`, `state`, `current`, `round`, `last_action_at`, `game_modes`, `turn_timeout`, `ai_name` FROM `game`'
            . ' WHERE (`player1` = ? AND `state` != "waiting" AND `state` != "P1 over")'
            . ' OR (`player2` = ? AND `state` != "waiting" AND `state` != "P2 over") ORDER BY `last_action_at` DESC'
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

        return $db->query('SELECT COUNT(`game_id`) as `count` FROM `game` WHERE `current` = ? AND `state` = "in progress"', [
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
            'SELECT `game_id`, (CASE WHEN `player1` = ? THEN `player2` ELSE `player1` END) as `Opponent` FROM `game`'
            . ' WHERE (`player1` = ? OR `player2` = ?) AND ((`state` = "in progress" AND ((`current` = ? AND `surrender` = "")'
            . ' OR (`surrender` != ? AND `surrender` != "") OR (`turn_timeout` > 0 AND `last_action_at` <= NOW() - INTERVAL `turn_timeout` SECOND'
            . ' AND `current` != ? AND `player2` != ?))) OR `player2` = ? OR `state` = "finished"'
            . ' OR (`state` = "P1 over" AND `player2` = ?) OR (`state` = "P2 over" AND `player1` = ?))'
            , [
            $player, $player, $player, $player, $player, $player
            , Player::SYSTEM_NAME, Player::SYSTEM_NAME, $player, $player
        ]);
    }

    /**
     * Check if there is already a game between two specified players
     * @param string $player1 player 1 name
     * @param string $player2 player 2 name
     * @return \Db\Util\Result
     */
    public function checkGame($player1, $player2)
    {
        $db = $this->db();

        return $db->query(
            'SELECT 1 FROM `game` WHERE `state` = "in progress" AND ((`player1` = ? AND `player2` = ?)'
            . ' OR (`player1` = ? AND `player2` = ?)) LIMIT 1'
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

        return $db->query('UPDATE `game` SET `player1` = ? WHERE `player1` = ?', [
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

        return $db->query('UPDATE `game` SET `player2` = ? WHERE `player2` = ?', [
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

        return $db->query('UPDATE `game` SET `current` = ? WHERE `current` = ?', [
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

        return $db->query('UPDATE `game` SET `winner` = ? WHERE `winner` = ?', [
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

        return $db->query('UPDATE `game` SET `surrender` = ? WHERE `surrender` = ?', [
            $newName, $player
        ]);
    }
}
