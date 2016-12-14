<?php
/**
 * ForumPost - the representation of a forum post
 */

namespace Db\Model;

class ForumPost extends ModelAbstract
{
    /**
     * Number of posts that are displayed per one page
     */
    const POSTS_PER_PAGE = 20;

    /**
     * Maximum post message length
     */
    const POST_LENGTH = 4000;

    /**
     * @return int
     */
    public function getPostId()
    {
        return $this->getFieldValue('PostID');
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
    public function getContent()
    {
        return $this->getFieldValue('Content');
    }

    /**
     * @return int
     */
    public function getThreadId()
    {
        return $this->getFieldValue('ThreadID');
    }

    /**
     * @return int
     */
    public function getDeleted()
    {
        return $this->getFieldValue('Deleted');
    }

    /**
     * @return string
     */
    public function getCreated()
    {
        return $this->getFieldValue('Created');
    }

    /**
     * @return array
     */
    public function getData()
    {
        $data = array();
        $data['PostID'] = $this->getPostId();
        $data['Author'] = $this->getAuthor();
        $data['Content'] = $this->getContent();
        $data['ThreadID'] = $this->getThreadId();
        $data['Deleted'] = $this->getDeleted();
        $data['Created'] = $this->getCreated();

        return $data;
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
     * @param string $content
     * @return $this
     */
    public function setContent($content)
    {
        return $this->setFieldValue('Content', $content);
    }

    /**
     * @param int $threadId
     * @return $this
     */
    public function setThreadId($threadId)
    {
        return $this->setFieldValue('ThreadID', $threadId);
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
     * @param string $created
     * @return $this
     */
    public function setCreated($created)
    {
        return $this->setFieldValue('Created', $created);
    }
}
