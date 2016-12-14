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
    const MESSAGE_LENGTH = 1000;

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
        return $this->getFieldValue('MessageID');
    }

    /**
     * @return string
     */
    public function getAuthor()
    {
        return $this->getFieldValue('Author');
    }

    /**
     * @return string
     */
    public function getRecipient()
    {
        return $this->getFieldValue('Recipient');
    }

    /**
     * @return string
     */
    public function getSubject()
    {
        return $this->getFieldValue('Subject');
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->getFieldValue('Content');
    }

    /**
     * @return int
     */
    public function getAuthorDelete()
    {
        return $this->getFieldValue('AuthorDelete');
    }

    /**
     * @return int
     */
    public function getRecipientDelete()
    {
        return $this->getFieldValue('RecipientDelete');
    }

    /**
     * @return int
     */
    public function getUnread()
    {
        return $this->getFieldValue('Unread');
    }

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
    public function getCreated()
    {
        return $this->getFieldValue('Created');
    }

    /**
     * @param string $author
     * @return $this
     */
    public function setAuthor($author)
    {
        return $this->setFieldValue('Author', $author);
    }

    /**
     * @param string $recipient
     * @return $this
     */
    public function setRecipient($recipient)
    {
        return $this->setFieldValue('Recipient', $recipient);
    }

    /**
     * @param string $subject
     * @return $this
     */
    public function setSubject($subject)
    {
        return $this->setFieldValue('Subject', $subject);
    }

    /**
     * @param string $content
     * @return $this
     */
    public function setContent($content)
    {
        return $this->setFieldValue('Content', $content);
    }

    /**
     * @param int $authorDelete
     * @return $this
     */
    public function setAuthorDelete($authorDelete)
    {
        return $this->setFieldValue('AuthorDelete', $authorDelete);
    }

    /**
     * @param int $recipientDelete
     * @return $this
     */
    public function setRecipientDelete($recipientDelete)
    {
        return $this->setFieldValue('RecipientDelete', $recipientDelete);
    }

    /**
     * @param int $unread
     * @return $this
     */
    public function setUnread($unread)
    {
        return $this->setFieldValue('Unread', $unread);
    }

    /**
     * @param int $gameId
     * @return $this
     */
    public function setGameId($gameId)
    {
        return $this->setFieldValue('GameID', $gameId);
    }

    /**
     * @param string $created
     * @return $this
     */
    public function setCreated($created)
    {
        return $this->setFieldValue('Created', $created);
    }
}
