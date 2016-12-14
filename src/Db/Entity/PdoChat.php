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
            'entity_name' => 'chats',
            'primary_fields' => [
                'GameID',
            ],
            'fields' => [
                'GameID' => [
                    'type' => EntityAbstract::TYPE_INT32,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'Timestamp' => [
                    // created timestamp
                    'type' => EntityAbstract::TYPE_DATETIME,
                    'default' => Date::DATETIME_ZERO,
                    'options' => [EntityAbstract::OPT_INSERT_DATETIME],
                ],
                'Name' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                ],
                'Message' => [
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

        return $db->query('INSERT INTO `chats` (`GameID`, `Name`, `Message`) VALUES (?, ?, ?)', [
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

        return $db->query('UPDATE `chats` SET `Name` = ? WHERE `Name` = ?', [
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

        return $db->query('DELETE FROM `chats` WHERE `GameID` = ?', [$gameId]);
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

        return $db->query('DELETE FROM `chats` WHERE `GameID` IN (' . implode(",", $qString) . ')', $params);
    }

    /**
     * List chat messages for specified game
     * @param int $gameId game id
     * @param string $order message order
     * @return \Db\Util\Result
     */
    public function listChatMessages($gameId, $order)
    {
        $db = $this->db();

        $order = ($order == 'ASC') ? 'ASC' : 'DESC';

        return $db->query('SELECT `Name`, `Message`, `Timestamp` FROM `chats` WHERE `GameID` = ? ORDER BY `Timestamp` ' . $order . '', [
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

        return $db->query('SELECT 1 FROM `chats` WHERE `GameID` = ? AND `Name` != ? AND `Timestamp` > ? LIMIT 1', [
            $gameId, $player, $time
        ]);
    }
}
