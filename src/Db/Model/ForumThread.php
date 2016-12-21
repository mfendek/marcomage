<?php
/**
 * Thread - the representation of a single forum thread
 */

namespace Db\Model;

class ForumThread extends ModelAbstract
{
    /**
     * Number of threads per section in the forum main page
     */
    const NUM_THREADS = 4;

    /**
     * Number of threads that are displayed per one page
     */
    const THREADS_PER_PAGE = 30;

    const CARDS_SECTION_ID = 7;
    const CONCEPTS_SECTION_ID = 6;
    const REPLAYS_SECTION_ID = 9;
    const DECKS_SECTION_ID = 10;

    /**
     * @return int
     */
    public function getThreadId()
    {
        return $this->getFieldValue('thread_id');
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->getFieldValue('title');
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
    public function getPriority()
    {
        return $this->getFieldValue('priority');
    }

    /**
     * @return int
     */
    public function getLocked()
    {
        return $this->getFieldValue('is_locked');
    }

    /**
     * @return int
     */
    public function getIsDeleted()
    {
        return $this->getFieldValue('is_deleted');
    }

    /**
     * @return int
     */
    public function getSectionId()
    {
        return $this->getFieldValue('section_id');
    }

    /**
     * @return string
     */
    public function getCreated()
    {
        return $this->getFieldValue('created_at');
    }

    /**
     * @return int
     */
    public function getPostCount()
    {
        return $this->getFieldValue('post_count');
    }

    /**
     * @return string
     */
    public function getLastAuthor()
    {
        return $this->getFieldValue('last_author');
    }

    /**
     * @return string
     */
    public function getLastPost()
    {
        return $this->getFieldValue('last_post');
    }

    /**
     * @return int
     */
    public function getReferenceId()
    {
        return $this->getFieldValue('reference_id');
    }

    /**
     * @return array
     */
    public function getData()
    {
        $data = array();
        $data['thread_id'] = $this->getThreadId();
        $data['title'] = $this->getTitle();
        $data['author'] = $this->getAuthor();
        $data['priority'] = $this->getPriority();
        $data['is_locked'] = ($this->getLocked() == 1) ? 'yes' : 'no';
        $data['section_id'] = $this->getSectionId();
        $data['created_at'] = $this->getCreated();
        $data['reference_card'] = ($this->getSectionId() == self::CARDS_SECTION_ID) ? $this->getReferenceId() : 0;
        $data['reference_deck'] = ($this->getSectionId() == self::DECKS_SECTION_ID) ? $this->getReferenceId() : 0;
        $data['reference_concept'] = ($this->getSectionId() == self::CONCEPTS_SECTION_ID) ? $this->getReferenceId() : 0;
        $data['reference_replay'] = ($this->getSectionId() == self::REPLAYS_SECTION_ID) ? $this->getReferenceId() : 0;

        return $data;
    }

    /**
     * @param string $title
     * @return $this
     */
    public function setTitle($title)
    {
        return $this->setFieldValue('title', $title);
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
     * @param string $priority
     * @return $this
     */
    public function setPriority($priority)
    {
        return $this->setFieldValue('priority', $priority);
    }

    /**
     * @param int $locked
     * @return $this
     */
    public function setLocked($locked)
    {
        return $this->setFieldValue('is_locked', $locked);
    }

    /**
     * @param int $deleted
     * @return $this
     */
    public function setIsDeleted($deleted)
    {
        return $this->setFieldValue('is_deleted', $deleted);
    }

    /**
     * @param int $sectionId
     * @return $this
     */
    public function setSectionId($sectionId)
    {
        return $this->setFieldValue('section_id', $sectionId);
    }

    /**
     * @param string $created
     * @return $this
     */
    public function setCreated($created)
    {
        return $this->setFieldValue('created_at', $created);
    }

    /**
     * @param int $postCount
     * @return $this
     */
    public function setPostCount($postCount)
    {
        return $this->setFieldValue('post_count', $postCount);
    }

    /**
     * @param string $lastAuthor
     * @return $this
     */
    public function setLastAuthor($lastAuthor)
    {
        return $this->setFieldValue('last_author', $lastAuthor);
    }

    /**
     * @param string $lastPost
     * @return $this
     */
    public function setLastPost($lastPost)
    {
        return $this->setFieldValue('last_post', $lastPost);
    }

    /**
     * @param int $cardId
     * @return $this
     */
    public function setReferenceId($cardId)
    {
        return $this->setFieldValue('reference_id', $cardId);
    }
}
