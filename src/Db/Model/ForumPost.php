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
        return $this->getFieldValue('post_id');
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
    public function getContent()
    {
        return $this->getFieldValue('content');
    }

    /**
     * @return int
     */
    public function getThreadId()
    {
        return $this->getFieldValue('thread_id');
    }

    /**
     * @return int
     */
    public function getIsDeleted()
    {
        return $this->getFieldValue('is_deleted');
    }

    /**
     * @return string
     */
    public function getCreated()
    {
        return $this->getFieldValue('created_at');
    }

    /**
     * @return array
     */
    public function getData()
    {
        $data = array();
        $data['post_id'] = $this->getPostId();
        $data['author'] = $this->getAuthor();
        $data['content'] = $this->getContent();
        $data['thread_id'] = $this->getThreadId();
        $data['is_deleted'] = $this->getIsDeleted();
        $data['created_at'] = $this->getCreated();

        return $data;
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
     * @param string $content
     * @return $this
     */
    public function setContent($content)
    {
        return $this->setFieldValue('content', $content);
    }

    /**
     * @param int $threadId
     * @return $this
     */
    public function setThreadId($threadId)
    {
        return $this->setFieldValue('thread_id', $threadId);
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
     * @param string $created
     * @return $this
     */
    public function setCreated($created)
    {
        return $this->setFieldValue('created_at', $created);
    }
}
