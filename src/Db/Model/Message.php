<?php
/**
 * Message - the representation of a single message
 */

namespace Db\Model;

class Message extends ModelAbstract
{
    /**
     * Maximum number of characters per message
     */
    const MESSAGE_LENGTH = 1500;

    /**
     * Maximum number of characters per challenge message
     */
    const CHALLENGE_LENGTH = 250;

    /**
     * Chat message length
     */
    const CHAT_LENGTH = 300;

    /**
     * Number of messages that are displayed per one page
     */
    const MESSAGES_PER_PAGE = 15;

    /**
     * @return int
     */
    public function getMessageId()
    {
        return $this->getFieldValue('message_id');
    }

    /**
     * @return string
     */
    public function getAuthor()
    {
        return $this->getFieldValue('author');
    }

    /**
     * @return string
     */
    public function getRecipient()
    {
        return $this->getFieldValue('recipient');
    }

    /**
     * @return string
     */
    public function getSubject()
    {
        return $this->getFieldValue('subject');
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->getFieldValue('content');
    }

    /**
     * @return int
     */
    public function getIsDeletedAuthor()
    {
        return $this->getFieldValue('is_deleted_author');
    }

    /**
     * @return int
     */
    public function getIsDeletedRecipient()
    {
        return $this->getFieldValue('is_deleted_recipient');
    }

    /**
     * @return int
     */
    public function getUnread()
    {
        return $this->getFieldValue('is_unread');
    }

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
    public function getCreated()
    {
        return $this->getFieldValue('created_at');
    }

    /**
     * @param string $author
     * @return $this
     */
    public function setAuthor($author)
    {
        return $this->setFieldValue('author', $author);
    }

    /**
     * @param string $recipient
     * @return $this
     */
    public function setRecipient($recipient)
    {
        return $this->setFieldValue('recipient', $recipient);
    }

    /**
     * @param string $subject
     * @return $this
     */
    public function setSubject($subject)
    {
        return $this->setFieldValue('subject', $subject);
    }

    /**
     * @param string $content
     * @return $this
     */
    public function setContent($content)
    {
        return $this->setFieldValue('content', $content);
    }

    /**
     * @param int $authorDelete
     * @return $this
     */
    public function setIsDeletedAuthor($authorDelete)
    {
        return $this->setFieldValue('is_deleted_author', $authorDelete);
    }

    /**
     * @param int $recipientDelete
     * @return $this
     */
    public function setIsDeletedRecipient($recipientDelete)
    {
        return $this->setFieldValue('is_deleted_recipient', $recipientDelete);
    }

    /**
     * @param int $unread
     * @return $this
     */
    public function setUnread($unread)
    {
        return $this->setFieldValue('is_unread', $unread);
    }

    /**
     * @param int $gameId
     * @return $this
     */
    public function setGameId($gameId)
    {
        return $this->setFieldValue('game_id', $gameId);
    }

    /**
     * @param string $created
     * @return $this
     */
    public function setCreated($created)
    {
        return $this->setFieldValue('created_at', $created);
    }
}
