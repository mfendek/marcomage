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
        return $this->getFieldValue('ThreadID');
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->getFieldValue('Title');
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
    public function getPriority()
    {
        return $this->getFieldValue('Priority');
    }

    /**
     * @return int
     */
    public function getLocked()
    {
        return $this->getFieldValue('Locked');
    }

    /**
     * @return int
     */
    public function getDeleted()
    {
        return $this->getFieldValue('Deleted');
    }

    /**
     * @return int
     */
    public function getSectionId()
    {
        return $this->getFieldValue('SectionID');
    }

    /**
     * @return string
     */
    public function getCreated()
    {
        return $this->getFieldValue('Created');
    }

    /**
     * @return int
     */
    public function getPostCount()
    {
        return $this->getFieldValue('PostCount');
    }

    /**
     * @return string
     */
    public function getLastAuthor()
    {
        return $this->getFieldValue('LastAuthor');
    }

    /**
     * @return string
     */
    public function getLastPost()
    {
        return $this->getFieldValue('LastPost');
    }

    /**
     * @return int
     */
    public function getReferenceId()
    {
        // TODO this field needs to be renamed to reference_id which can hold either card id, concept id, replay id or deck id
        return $this->getFieldValue('CardID');
    }

    /**
     * @return array
     */
    public function getData()
    {
        $data = array();
        $data['ThreadID'] = $this->getThreadId();
        $data['Title'] = $this->getTitle();
        $data['Author'] = $this->getAuthor();
        $data['Priority'] = $this->getPriority();
        $data['Locked'] = ($this->getLocked() == 1) ? 'yes' : 'no';
        $data['SectionID'] = $this->getSectionId();
        $data['Created'] = $this->getCreated();
        $data['reference_card'] = ($this->getSectionId() == self::CARDS_SECTION_ID) ? $this->getReferenceId() : 0;
        $data['reference_deck'] = ($this->getSectionId() == self::DECKS_SECTION_ID) ? $this->getReferenceId() : 0;

        return $data;
    }

    /**
     * @param string $title
     * @return $this
     */
    public function setTitle($title)
    {
        return $this->setFieldValue('Title', $title);
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
     * @param string $priority
     * @return $this
     */
    public function setPriority($priority)
    {
        return $this->setFieldValue('Priority', $priority);
    }

    /**
     * @param int $locked
     * @return $this
     */
    public function setLocked($locked)
    {
        return $this->setFieldValue('Locked', $locked);
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
     * @param int $sectionId
     * @return $this
     */
    public function setSectionId($sectionId)
    {
        return $this->setFieldValue('SectionID', $sectionId);
    }

    /**
     * @param string $created
     * @return $this
     */
    public function setCreated($created)
    {
        return $this->setFieldValue('Created', $created);
    }

    /**
     * @param int $postCount
     * @return $this
     */
    public function setPostCount($postCount)
    {
        return $this->setFieldValue('PostCount', $postCount);
    }

    /**
     * @param string $lastAuthor
     * @return $this
     */
    public function setLastAuthor($lastAuthor)
    {
        return $this->setFieldValue('LastAuthor', $lastAuthor);
    }

    /**
     * @param string $lastPost
     * @return $this
     */
    public function setLastPost($lastPost)
    {
        return $this->setFieldValue('LastPost', $lastPost);
    }

    /**
     * @param int $cardId
     * @return $this
     */
    public function setReferenceId($cardId)
    {
        return $this->setFieldValue('CardID', $cardId);
    }
}
