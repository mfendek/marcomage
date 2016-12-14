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
            'entity_name' => 'forum_posts',
            'model_name' => 'forum_post',
            'primary_fields' => [
                'PostID',
            ],
            'fields' => [
                'PostID' => [
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
                'Content' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                ],
                'ThreadID' => [
                    'type' => EntityAbstract::TYPE_INT32,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'Deleted' => [
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
     * Create new forum post
     * @param int $threadId
     * @param string $author author name
     * @param string $content post content
     * @return ForumPost
     */
    public function createPost($threadId, $author, $content)
    {
        return parent::createModel([
            'Author' => $author,
            'Content' => $content,
            'ThreadID' => $threadId,
        ]);
    }

    /**
     * @param int $postId
     * @param bool [$asserted]
     * @return ForumPost
     */
    public function getPost($postId, $asserted = false)
    {
        return parent::getModel(['PostID' => $postId], $asserted);
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
//                $postQuery .= '`PostID` = "' . $db->Escape($postId) . '"';
//                $first = false;
//            }
//            else {
//                $postQuery .= ' OR `PostID` = "' . $db->Escape($postId) . '"';
//            }
//        }
//
//        return $db->query('UPDATE `forum_posts` SET `Deleted` = TRUE WHERE ' . $postQuery . '');
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
//                $postQuery .= '`PostID` = "' . $db->Escape($postId) . '"';
//                $first = false;
//            }
//            else {
//                $postQuery .= ' OR `PostID` = "' . $db->Escape($postId) . '"';
//            }
//        }
//
//        return $db->query('UPDATE `forum_posts` SET `ThreadID` = "' . $db->Escape($newThread) . '" WHERE ' . $postQuery . '');
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
            'SELECT `PostID`, `Author`, `Content`, `Created`, IFNULL(`Avatar`,"noavatar.jpg") as `Avatar` FROM `forum_posts`'
            . ' LEFT OUTER JOIN `settings` ON `forum_posts`.`Author` = `settings`.`Username`'
            . ' WHERE `ThreadID` = ? AND `Deleted` = FALSE ORDER BY `Created` ASC LIMIT '
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

        return $db->query('SELECT COUNT(`PostID`) as `Count` FROM `forum_posts` WHERE `ThreadID` = ? AND `Deleted` = FALSE', [
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

        return $db->query('SELECT COUNT(`PostID`) as `Count` FROM `forum_posts` WHERE `Author` = ? AND `Deleted` = FALSE', [
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

        return $db->query('SELECT 1 FROM `forum_posts` WHERE `Created` > ? AND `Deleted` = FALSE LIMIT 1', [
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
            'SELECT `Created` FROM `forum_posts` WHERE `Author` = ? AND `Deleted` = FALSE ORDER BY `Created` DESC LIMIT 1', [
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

        return $db->query('UPDATE `forum_posts` SET `Author` = ? WHERE `Author` = ?', [
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

        return $db->query('UPDATE `forum_posts` SET `Deleted` = TRUE WHERE `ThreadID` = ?', [$threadId]);
    }
}
