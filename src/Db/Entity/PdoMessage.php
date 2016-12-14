<?php
/**
 * Message - message database
 */

namespace Db\Entity;

use Db\Model\Message;
use Db\Model\Player;
use Util\Date;

class PdoMessage extends PdoAbstract
{
    /**
     * DB schema
     * @return array
     */
    protected function schema()
    {
        return [
            'entity_name' => 'messages',
            'model_name' => 'message',
            'primary_fields' => [
                'MessageID',
            ],
            'fields' => [
                'MessageID' => [
                    'type' => EntityAbstract::TYPE_INT32,
                    'default' => 0,
                    'options' => [
                        EntityAbstract::OPT_UNSIGNED,
                        EntityAbstract::OPT_AUTO_ID,
                    ],
                ],
                'Author' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                ],
                'Recipient' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                ],
                'Subject' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                ],
                'Content' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                ],
                'AuthorDelete' => [
                    'type' => EntityAbstract::TYPE_INT32,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'RecipientDelete' => [
                    'type' => EntityAbstract::TYPE_INT32,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'Unread' => [
                    'type' => EntityAbstract::TYPE_INT32,
                    'default' => 1,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'GameID' => [
                    'type' => EntityAbstract::TYPE_INT32,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'Created' => [
                    'type' => EntityAbstract::TYPE_DATETIME,
                    'default' => Date::DATETIME_ZERO,
                    'options' => [EntityAbstract::OPT_INSERT_DATETIME],
                ],
            ],
        ];
    }

    /**
     * Create new message
     * @param array $data message data
     * @return Message
     */
    private function createMessage(array $data)
    {
        return parent::createModel([
            'Author' => $data['author'],
            'Recipient' => $data['recipient'],
            'Subject' => $data['subject'],
            'Content' => $data['content'],
            'GameID' => $data['game_id'],
        ]);
    }

    /**
     * @param int $messageId
     * @param bool [$asserted]
     * @return Message
     */
    public function getMessage($messageId, $asserted = false)
    {
        return parent::getModel(['MessageID' => $messageId], $asserted);
    }

    /**
     * @param int $messageId
     * @return Message
     */
    public function getMessageAsserted($messageId)
    {
        return $this->getMessage($messageId, true);
    }

    /**
     * Send message
     * @param string $author
     * @param string $recipient
     * @param string $subject
     * @param string $content
     * @return Message
     */
    public function sendMessage($author, $recipient, $subject, $content)
    {
        return $this->createMessage([
            'author' => $author,
            'recipient' => $recipient,
            'subject' => $subject,
            'content' => $content,
            'game_id' => 0,
        ]);
    }

    /**
     * Send welcome message
     * @param string $player player name
     * @return Message
     */
    public function welcomeMessage($player)
    {
        $msg = 'Welcome ' . $player . ',' . "\n" . "\n";
        $msg.= 'MArcomage team has created three starter decks for you. ';
        $msg.= 'To quickly start a game against a computer player, go to "Games" section and click on the "Quick game vs AI" button. ';
        $msg.= 'To quickly start a game against a human player, go to "Games" section, "Hosted games" subsection where you can either host or join a game. ';
        $msg.= 'If you want to play a game with a specific player, you can find his profile in the "Players" section where you can challenge him directly.' . "\n" . "\n";
        $msg.= 'To improve your play strategy you need to improve your decks. You can do this in the "Decks" section, which will be unlocked after several games. ';
        $msg.= 'In addition to three starter decks which can be modified as you see fit, there are multiple empty decks that are awaiting your customization.' . "\n" . "\n";
        $msg.= 'MArcomage can be configured to your best liking in the "Settings" section. Be sure to check it out. ';
        $msg.= 'There are many interesting features that are just waiting to be discovered. ';
        $msg.= 'To learn more about them, seek them out in the "Help" section.' . "\n" . "\n";
        $msg.= 'Good luck and have fun,' . "\n" . "\n";
        $msg.= 'MArcomage development team' . "\n";

        return $this->sendMessage(Player::SYSTEM_NAME, $player, 'Welcome to MArcomage', $msg);
    }

    /**
     * Send level up message
     * @param string $player player name
     * @param int $newLevel new level gained
     * @return Message
     */
    public function levelUp($player, $newLevel)
    {
        return $this->sendMessage(
            Player::SYSTEM_NAME,
            $player,
            'Level up (' . $newLevel . ')',
            'Congratulations, you have reached level ' . $newLevel . '.'
        );
    }

    /**
     * Send achievement notification
     * @param string $player player name
     * @param string $achievement achievement name
     * @param int $gold gold earned
     * @return Message
     */
    public function achievementNotification($player, $achievement, $gold)
    {
        return $this->sendMessage(
            Player::SYSTEM_NAME,
            $player,
            'Achievement gained',
            'Congratulations, you have gained the ' . $achievement . ' achievement and ' . $gold . ' gold reward.'
        );
    }

    /**
     * Send battle report
     * @param string $player player name
     * @param string $opponent opponent name
     * @param string $outcome game outcome message
     * @param bool $hidden hidden card mode
     * @param string $message game finish message
     * @param string $winner winner
     * @return Message
     */
    public function sendBattleReport($player, $opponent, $outcome, $hidden, $message, $winner)
    {
        $body = '';
        $body.= 'Opponent: [link=?location=Players_details&Profile=' . urlencode($opponent) . ']' . $opponent . '[/link]' . "\n";
        $body.= 'Outcome: ' . $outcome . "\n";
        $body.= ($winner != '') ? 'Winner: ' . $winner . "\n" : '';
        $body.= 'Hide opponent\'s cards: ' . (($hidden) ? 'yes' : 'no') . "\n";
        $body.= $message;

        return $this->sendMessage(Player::SYSTEM_NAME, $player, 'Battle report', $body);
    }

    /**
     * Find system messages for specified player
     * @param array $messageIds message ids
     * @param string $player player name
     * @return \Db\Util\Result
     */
    public function findSystemMessages(array $messageIds, $player)
    {
        $db = $this->db();

        $placeholders = $params = array();
        foreach ($messageIds as $id) {
            $placeholders[] = '?';
            $params[] = $id;
        }

        $params[] = Player::SYSTEM_NAME;
        $params[] = $player;

        return $db->query(
            'SELECT `MessageID` FROM `messages` WHERE `MessageID` IN ('. implode(',', $placeholders) .')'
            . ' AND `Author` = ? AND `Recipient` = ?'
            , $params
        );
    }

    /**
     * Delete messages
     * @param array $messageIds message ids
     * @return \Db\Util\Result
     */
    public function deleteMessagesList(array $messageIds)
    {
        $db = $this->db();

        $placeholders = $params = array();
        foreach ($messageIds as $id) {
            $placeholders[] = '?';
            $params[] = $id;
        }

        return $db->query('DELETE FROM `messages` WHERE `MessageID` IN ('. implode(',', $placeholders) .')', $params);
    }

    /**
     * Hide messages
     * @param array $messageIds message ids
     * @param string $player player name
     * @return \Db\Util\Result
     */
    public function hideMessages(array $messageIds, $player)
    {
        $db = $this->db();

        $placeholders = $params = array();
        $params[] = $player;
        $params[] = $player;

        foreach ($messageIds as $id) {
            $placeholders[] = '?';
            $params[] = $id;
        }

        return $db->query(
            'UPDATE `messages` SET `AuthorDelete` = (CASE WHEN `Author` = ? THEN TRUE ELSE `AuthorDelete` END)'
            . ', `RecipientDelete` = (CASE WHEN `Recipient` = ? THEN TRUE ELSE `RecipientDelete` END)'
            . ' WHERE `MessageID` IN ('. implode(',', $placeholders) .')'
            , $params
        );
    }

    /**
     * Delete messages for specified player
     * @param string $player player name
     * @return \Db\Util\Result
     */
    public function deleteMessages($player)
    {
        $db = $this->db();

        return $db->query(
            'DELETE FROM `messages` WHERE (`GameID` = 0 AND ((`Author` = ? AND `Recipient` = ?)'
            . ' OR (`Author` = ? AND `RecipientDelete` = TRUE) OR (`Recipient` = ? AND `AuthorDelete` = TRUE)))'
            . ' OR (`GameID` > 0 AND (`Author` = ? OR `Recipient` = ?))'
            , [Player::SYSTEM_NAME, $player, $player, $player, $player, $player]
        );
    }

    /**
     * Delete message that is attached to specified game
     * @param int $gameId
     * @return \Db\Util\Result
     */
    public function cancelChallenge($gameId)
    {
        $db = $this->db();

        return $db->query('DELETE FROM `messages` WHERE `GameID` = ?', [$gameId]);
    }

    /**
     * Rename all player name instances in messages (author)
     * @param string $player player name
     * @param string $newName new name
     * @return \Db\Util\Result
     */
    public function renameAuthor($player, $newName)
    {
        $db = $this->db();

        return $db->query('UPDATE `messages` SET `Author` = ? WHERE `Author` = ?', [$newName, $player]);
    }

    /**
     * Rename all player name instances in messages (recipient)
     * @param string $player player name
     * @param string $newName new name
     * @return \Db\Util\Result
     */
    public function renameRecipient($player, $newName)
    {
        $db = $this->db();

        return $db->query('UPDATE `messages` SET `Recipient` = ? WHERE `Recipient` = ?', [$newName, $player]);
    }

    /**
     * List messages to player
     * @param string $player player name
     * @param string $date date filter
     * @param string $name name filter
     * @param string $condition order condition
     * @param string $order order option
     * @param int $page current page
     * @return \Db\Util\Result
     */
    public function listMessagesTo($player, $date, $name, $condition, $order, $page)
    {
        $db = $this->db();

        $nameQuery = ($name != '') ? ' AND `Author` LIKE ?' : '';
        $dateQuery = ($date != 'none') ? ' AND `Created` >= NOW() - INTERVAL ? DAY' : '';

        $params = [$player];
        if ($name != '') {
            $params[] = '%' . $name . '%';
        }
        if ($date != 'none') {
            $params[] = $date;
        }

        $condition = (in_array($condition, ['Author', 'Created'])) ? $condition : 'Created';
        $order = ($order == 'ASC') ? 'ASC' : 'DESC';
        $page = (is_numeric($page)) ? $page : 0;

        return $db->query(
            'SELECT `MessageID`, `Author`, `Recipient`, `Subject`, `Content`, (CASE WHEN `Unread` = TRUE THEN "yes" ELSE "no" END) as `Unread`'
            . ', `Created` FROM `messages` WHERE `GameID` = 0 AND `Recipient` = ? AND `RecipientDelete` = FALSE'
            . $nameQuery . $dateQuery . ' ORDER BY `' . $condition . '` ' . $order
            . ' LIMIT ' . (Message::MESSAGES_PER_PAGE * $page) . ' , ' . Message::MESSAGES_PER_PAGE . ''
            , $params
        );
    }

    /**
     * Count pages for message list to
     * @param string $player player name
     * @param string $date date filter
     * @param string $name name filter
     * @return \Db\Util\Result
     */
    public function countPagesTo($player, $date, $name)
    {
        $db = $this->db();

        $nameQuery = ($name != '') ? ' AND `Author` LIKE ?' : '';
        $dateQuery = ($date != 'none') ? ' AND `Created` >= NOW() - INTERVAL ? DAY' : '';

        $params = [$player];
        if ($name != '') {
            $params[] = '%' . $name . '%';
        }
        if ($date != 'none') {
            $params[] = $date;
        }

        return $db->query(
            'SELECT COUNT(`MessageID`) as `Count` FROM `messages` WHERE `GameID` = 0'
            . ' AND `Recipient` = ? AND `RecipientDelete` = FALSE' . $nameQuery . $dateQuery . ''
            , $params
        );
    }

    /**
     * List messages from player
     * @param string $player player name
     * @param string $date date filter
     * @param string $name name filter
     * @param string $condition order condition
     * @param string $order order option
     * @param int $page current page
     * @return \Db\Util\Result
     */
    public function listMessagesFrom($player, $date, $name, $condition, $order, $page)
    {
        $db = $this->db();

        $nameQuery = ($name != '') ? ' AND `Recipient` LIKE ?' : '';
        $dateQuery = ($date != 'none') ? ' AND `Created` >= NOW() - INTERVAL ? DAY' : '';

        $params = [$player];
        if ($name != '') {
            $params[] = '%' . $name . '%';
        }
        if ($date != "none") {
            $params[] = $date;
        }

        $condition = (in_array($condition, ['Recipient', 'Created'])) ? $condition : 'Created';
        $order = ($order == 'ASC') ? 'ASC' : 'DESC';
        $page = (is_numeric($page)) ? $page : 0;

        return $db->query(
            'SELECT `MessageID`, `Author`, `Recipient`, `Subject`, `Content`, (CASE WHEN `Unread` = TRUE THEN "yes" ELSE "no" END) as `Unread`'
            . ', `Created` FROM `messages` WHERE `GameID` = 0 AND `Author` = ? AND `AuthorDelete` = FALSE'
            . $nameQuery . $dateQuery . ' ORDER BY `' . $condition . '` ' . $order
            . ' LIMIT ' . (Message::MESSAGES_PER_PAGE * $page) . ' , ' . Message::MESSAGES_PER_PAGE . ''
            , $params
        );
    }

    /**
     * Count pages for message list from
     * @param string $player player name
     * @param string $date date filter
     * @param string $name name filter
     * @return \Db\Util\Result
     */
    public function countPagesFrom($player, $date, $name)
    {
        $db = $this->db();

        $nameQuery = ($name != '') ? ' AND `Recipient` LIKE ?' : '';
        $dateQuery = ($date != 'none') ? ' AND `Created` >= NOW() - INTERVAL ? DAY' : '';

        $params = [$player];
        if ($name != '') {
            $params[] = '%' . $name . '%';
        }
        if ($date != 'none') {
            $params[] = $date;
        }

        return $db->query(
            'SELECT COUNT(`MessageID`) as `Count` FROM `messages` WHERE `GameID` = 0'
            . ' AND `Author` = ? AND `AuthorDelete` = FALSE' . $nameQuery . $dateQuery . ''
            , $params
        );
    }

    /**
     * List all messages (even deleted)
     * @param string $date date filter
     * @param string $name name filter
     * @param string $condition order condition
     * @param string $order order option
     * @param string $page current page
     * @return \Db\Util\Result
     */
    public function listAllMessages($date, $name, $condition, $order, $page)
    {
        $db = $this->db();

        $nameQuery = ($name != '') ? ' AND `Author` LIKE ?' : '';
        $dateQuery = ($date != 'none') ? ' AND `Created` >= NOW() - INTERVAL ? DAY' : '';

        $params = [Player::SYSTEM_NAME];
        if ($name != '') {
            $params[] = '%' . $name . '%';
        }
        if ($date != 'none') {
            $params[] = $date;
        }

        $condition = (in_array($condition, ['Author', 'Created'])) ? $condition : 'Created';
        $order = ($order == 'ASC') ? 'ASC' : 'DESC';
        $page = (is_numeric($page)) ? $page : 0;

        return $db->query(
            'SELECT `MessageID`, `Author`, `Recipient`, `Subject`, `Content`, (CASE WHEN `Unread` = TRUE THEN "yes" ELSE "no" END) as `Unread`'
            . ', `Created` FROM `messages` WHERE `GameID` = 0 AND `Author` != ?' . $nameQuery . $dateQuery
            . ' ORDER BY `' . $condition . '` ' . $order . ' LIMIT '
            . (Message::MESSAGES_PER_PAGE * $page) . ' , ' . Message::MESSAGES_PER_PAGE . ''
            , $params
        );
    }

    /**
     * Count pages for message list all
     * @param string $date date filter
     * @param string $name name filter
     * @return \Db\Util\Result
     */
    public function countPagesAll($date, $name)
    {
        $db = $this->db();

        $nameQuery = ($name != '') ? ' AND `Author` LIKE ?' : '';
        $dateQuery = ($date != 'none') ? ' AND `Created` >= NOW() - INTERVAL ? DAY' : '';

        $params = [Player::SYSTEM_NAME];
        if ($name != '') {
            $params[] = '%' . $name . '%';
        }
        if ($date != 'none') {
            $params[] = $date;
        }

        return $db->query(
            'SELECT COUNT(`MessageID`) as `Count` FROM `messages` WHERE `GameID` = 0 AND `Author` != ?'
            . $nameQuery . $dateQuery . ''
            , $params
        );
    }

    /**
     * Count unread messages for specified player
     * @param string $player player name
     * @return \Db\Util\Result
     */
    public function countUnreadMessages($player)
    {
        $db = $this->db();

        return $db->query(
            'SELECT COUNT(`MessageID`) as `CountUnread` FROM `messages` WHERE `GameID` = 0'
            . ' AND `Recipient` = ? AND `RecipientDelete` = FALSE AND `Unread` = TRUE'
            , [$player]
        );
    }

    /**
     * Send challenge
     * @param string $author
     * @param string $recipient
     * @param string $content
     * @param int $gameId
     * @return Message
     */
    public function sendChallenge($author, $recipient, $content, $gameId)
    {
        return $this->createMessage([
            'author' => $author,
            'recipient' => $recipient,
            'subject' => '',
            'content' => $content,
            'game_id' => $gameId,
        ]);
    }

    /**
     * List challenges from specified player
     * @param string $player player name
     * @return \Db\Util\Result
     */
    public function listChallengesFrom($player)
    {
        $db = $this->db();

        return $db->query(
            'SELECT `GameID`, `Recipient`, `Content`, `Created`'
            . ', (CASE WHEN `Last Query` >= NOW() - INTERVAL 10 MINUTE THEN "yes" ELSE "no" END) as `Online` FROM'
            . ' (SELECT `Recipient`, `Content`, `Created`, `GameID` FROM `messages` WHERE `GameID` > 0 AND `Author` = ?) as `messages`'
            . ' INNER JOIN `logins` ON `messages`.`Recipient` = `logins`.`Username` ORDER BY `Created` DESC'
            , [$player]
        );
    }

    /**
     * List challenges to specified player
     * @param string $player player name
     * @return \Db\Util\Result
     */
    public function listChallengesTo($player)
    {
        $db = $this->db();

        return $db->query(
            'SELECT `GameID`, `Author`, `Content`, `Created`'
            . ', (CASE WHEN `Last Query` >= NOW() - INTERVAL 10 MINUTE THEN "yes" ELSE "no" END) as `Online` FROM'
            . ' (SELECT `Author`, `Content`, `Created`, `GameID` FROM `messages` WHERE `GameID` > 0 AND `Recipient` = ?) as `messages`'
            . ' INNER JOIN `logins` ON `messages`.`Author` = `logins`.`Username` ORDER BY `Created` DESC'
            , [$player]
        );
    }
}
