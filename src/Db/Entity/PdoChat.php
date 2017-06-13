<?php
/**
 * Chat - player conversation during a game
 */

namespace Db\Entity;

use Util\Date;

class PdoChat extends PdoAbstract
{
    /**
     * DB schema
     * @return array
     */
    protected function schema()
    {
        return [
            'entity_name' => 'chat',
            'primary_fields' => [
                'game_id',
            ],
            'fields' => [
                'game_id' => [
                    'type' => EntityAbstract::TYPE_INT,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'created_at' => [
                    'type' => EntityAbstract::TYPE_DATETIME,
                    'default' => Date::DATETIME_ZERO,
                    'options' => [EntityAbstract::OPT_INSERT_DATETIME],
                ],
                'author' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                ],
                'message' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                ],
            ],
        ];
    }

    /**
     * Save a new chat message
     * @param int $gameId game id
     * @param string $message message body
     * @param string $name player name
     * @return \Db\Util\Result
     */
    public function saveChatMessage($gameId, $message, $name)
    {
        $db = $this->db();

        return $db->query('INSERT INTO `chat` (`game_id`, `author`, `message`) VALUES (?, ?, ?)', [
            $gameId, $name, $message
        ]);
    }

    /**
     * Rename all player name instances in chat messages
     * @param string $player player name
     * @param string $newName new name
     * @return \Db\Util\Result
     */
    public function renameChats($player, $newName)
    {
        $db = $this->db();

        return $db->query('UPDATE `chat` SET `author` = ? WHERE `author` = ?', [
            $newName, $player
        ]);
    }

    /**
     * Delete all chat messages for specified game
     * @param int $gameId game id
     * @return \Db\Util\Result
     */
    public function deleteChat($gameId)
    {
        $db = $this->db();

        return $db->query('DELETE FROM `chat` WHERE `game_id` = ?', [$gameId]);
    }

    /**
     * Delete all chat messages attached to specified games
     * @param array $ids game ids
     * @return \Db\Util\Result
     */
    public function deleteChats(array $ids)
    {
        $db = $this->db();

        $qString = $params = array();
        foreach ($ids as $gameId) {
            $qString[] = '?';
            $params[] = $gameId;
        }

        return $db->query('DELETE FROM `chat` WHERE `game_id` IN (' . implode(",", $qString) . ')', $params);
    }

    /**
     * List chat messages for specified game
     * @param int $gameId game id
     * @param string [$order] message order
     * @return \Db\Util\Result
     */
    public function listChatMessages($gameId, $order = 'ASC')
    {
        $db = $this->db();

        $order = ($order == 'ASC') ? 'ASC' : 'DESC';

        return $db->query('SELECT `author`, `message`, `created_at` FROM `chat` WHERE `game_id` = ? ORDER BY `created_at` ' . $order . '', [
            $gameId
        ]);
    }

    /**
     * Check if there are new messages for specified player
     * @param int $gameId game id
     * @param string $player player name
     * @param string $time player's last activity
     * @return \Db\Util\Result
     */
    public function newMessages($gameId, $player, $time)
    {
        $db = $this->db();

        return $db->query('SELECT 1 FROM `chat` WHERE `game_id` = ? AND `author` != ? AND `created_at` > ? LIMIT 1', [
            $gameId, $player, $time
        ]);
    }
}
