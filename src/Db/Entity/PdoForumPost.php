<?php
/**
 * ForumPost - Forum posts database
 */

namespace Db\Entity;

use Db\Model\ForumPost;
use Util\Date;

class PdoForumPost extends PdoAbstract
{
    /**
     * DB schema
     * @return array
     */
    protected function schema()
    {
        return [
            'entity_name' => 'forum_post',
            'primary_fields' => [
                'post_id',
            ],
            'fields' => [
                'post_id' => [
                    'type' => EntityAbstract::TYPE_INT32,
                    'default' => 0,
                    'options' => [
                        EntityAbstract::OPT_UNSIGNED,
                        EntityAbstract::OPT_AUTO_ID,
                    ],
                ],
                'author' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                ],
                'content' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                ],
                'thread_id' => [
                    'type' => EntityAbstract::TYPE_INT32,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'is_deleted' => [
                    'type' => EntityAbstract::TYPE_INT32,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'created_at' => [
                    'type' => EntityAbstract::TYPE_DATETIME,
                    'default' => Date::DATETIME_ZERO,
                    'options' => [EntityAbstract::OPT_INSERT_DATETIME],
                ],
            ],
        ];
    }

    /**
     * Create new forum post
     * @param int $threadId
     * @param string $author author name
     * @param string $content post content
     * @return ForumPost
     */
    public function createPost($threadId, $author, $content)
    {
        return parent::createModel([
            'author' => $author,
            'content' => $content,
            'thread_id' => $threadId,
        ]);
    }

    /**
     * @param int $postId
     * @param bool [$asserted]
     * @return ForumPost
     */
    public function getPost($postId, $asserted = false)
    {
        return parent::getModel(['post_id' => $postId], $asserted);
    }

    /**
     * @param int $postId
     * @return ForumPost
     */
    public function getPostAsserted($postId)
    {
        return $this->getPost($postId, true);
    }

//    /**
//     * Delete multiple posts
//     * @param array $deletedPosts posts to be deleted
//     * @return \Db\Util\Result
//     */
//    public function massDeletePost(array $deletedPosts)
//    {
//        $db = $this->db();
//
//        $first = true;
//        $postQuery = "";
//
//        foreach ($deletedPosts as $postId) {
//            if ($first) {
//                $postQuery .= '`post_id` = "' . $db->Escape($postId) . '"';
//                $first = false;
//            }
//            else {
//                $postQuery .= ' OR `post_id` = "' . $db->Escape($postId) . '"';
//            }
//        }
//
//        return $db->query('UPDATE `forum_post` SET `Deleted` = TRUE WHERE ' . $postQuery . '');
//    }

//    /**
//     * Move multiple posts
//     * @param array $movedPosts posts to be moved
//     * @param int $newThread target thread id
//     * @return \Db\Util\Result
//     */
//    public function massMovePost(array $movedPosts, $newThread)
//    {
//        $db = $this->db();
//
//        $first = true;
//        $postQuery = "";
//
//        foreach ($movedPosts as $postId) {
//            if ($first) {
//                $postQuery .= '`post_id` = "' . $db->Escape($postId) . '"';
//                $first = false;
//            }
//            else {
//                $postQuery .= ' OR `post_id` = "' . $db->Escape($postId) . '"';
//            }
//        }
//
//        return $db->query('UPDATE `forum_post` SET `thread_id` = "' . $db->Escape($newThread) . '" WHERE ' . $postQuery . '');
//    }

    /**
     * List posts for specified thread
     * @param int $threadId
     * @param int $page current page
     * @return \Db\Util\Result
     */
    public function listPosts($threadId, $page)
    {
        $db = $this->db();

        $page = (is_numeric($page)) ? $page : 0;

        return $db->query(
            'SELECT `post_id`, `author`, `content`, `created_at`, IFNULL(`avatar`,"noavatar.jpg") as `avatar` FROM `forum_post`'
            . ' LEFT OUTER JOIN `setting` ON `forum_post`.`author` = `setting`.`username`'
            . ' WHERE `thread_id` = ? AND `is_deleted` = FALSE ORDER BY `created_at` ASC LIMIT '
            . (ForumPost::POSTS_PER_PAGE * $page) . ' , ' . ForumPost::POSTS_PER_PAGE . ''
            , [$threadId]
        );
    }

    /**
     * Count pages for posts list
     * @param int $threadId
     * @return \Db\Util\Result
     */
    public function countPages($threadId)
    {
        $db = $this->db();

        return $db->query('SELECT COUNT(`post_id`) as `count` FROM `forum_post` WHERE `thread_id` = ? AND `is_deleted` = FALSE', [
            $threadId
        ]);
    }

    /**
     * Count posts created by specified player
     * @param string $author
     * @return \Db\Util\Result
     */
    public function countPosts($author)
    {
        $db = $this->db();

        return $db->query('SELECT COUNT(`post_id`) as `count` FROM `forum_post` WHERE `author` = ? AND `is_deleted` = FALSE', [
            $author
        ]);
    }

    /**
     * Check if there are some new posts on forum
     * TODO this should be cached in player's table
     * @param string $time player's last activity
     * @return \Db\Util\Result
     */
    public function newPosts($time)
    {
        $db = $this->db();

        return $db->query('SELECT 1 FROM `forum_post` WHERE `created_at` > ? AND `is_deleted` = FALSE LIMIT 1', [
            $time
        ]);
    }

    /**
     * Load latest posts creation date
     * TODO this should be cached in player's table
     * @param string $author author name
     * @return \Db\Util\Result
     */
    public function getLatestPost($author)
    {
        $db = $this->db();

        return $db->query(
            'SELECT `created_at` FROM `forum_post` WHERE `author` = ? AND `is_deleted` = FALSE ORDER BY `created_at` DESC LIMIT 1', [
            $author
        ]);
    }

    /**
     * Rename all player name instances in forum posts
     * @param string $player player name
     * @param string $newName new name
     * @return \Db\Util\Result
     */
    public function renamePosts($player, $newName)
    {
        $db = $this->db();

        return $db->query('UPDATE `forum_post` SET `author` = ? WHERE `author` = ?', [
            $newName, $player
        ]);
    }

    /**
     * Delete posts contained in the specified thread
     * @param int $threadId
     * @return \Db\Util\Result
     */
    public function deleteThreadPosts($threadId)
    {
        $db = $this->db();

        return $db->query('UPDATE `forum_post` SET `is_deleted` = TRUE WHERE `thread_id` = ?', [$threadId]);
    }
}
