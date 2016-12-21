<?php
/**
 * ForumThread - Forum threads database
 */

namespace Db\Entity;

use Db\Model\ForumPost;
use Db\Model\ForumThread;
use Util\Date;

class PdoForumThread extends PdoAbstract
{
    /**
     * DB schema
     * @return array
     */
    protected function schema()
    {
        return [
            'entity_name' => 'forum_thread',
            'primary_fields' => [
                'thread_id',
            ],
            'fields' => [
                'thread_id' => [
                    'type' => EntityAbstract::TYPE_INT32,
                    'default' => 0,
                    'options' => [
                        EntityAbstract::OPT_UNSIGNED,
                        EntityAbstract::OPT_AUTO_ID,
                    ],
                ],
                'title' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                ],
                'author' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                ],
                'priority' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => 'normal',
                ],
                'is_locked' => [
                    'type' => EntityAbstract::TYPE_INT32,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'is_deleted' => [
                    'type' => EntityAbstract::TYPE_INT32,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'section_id' => [
                    'type' => EntityAbstract::TYPE_INT32,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'created_at' => [
                    'type' => EntityAbstract::TYPE_DATETIME,
                    'default' => Date::DATETIME_ZERO,
                    'options' => [EntityAbstract::OPT_INSERT_DATETIME],
                ],
                'post_count' => [
                    'type' => EntityAbstract::TYPE_INT32,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'last_author' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                ],
                'last_post' => [
                    'type' => EntityAbstract::TYPE_DATETIME,
                    'default' => Date::DATETIME_ZERO,
                ],
                'reference_id' => [
                    'type' => EntityAbstract::TYPE_INT32,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
            ],
        ];
    }

    /**
     * Create new thread
     * @param string $title title
     * @param string $author author
     * @param string $priority priority
     * @param int $sectionId section id
     * @param int [$referenceId] card id reference
     * @return ForumThread
     */
    public function createThread($title, $author, $priority, $sectionId, $referenceId = 0)
    {
        return parent::createModel([
            'title' => $title,
            'author' => $author,
            'priority' => $priority,
            'section_id' => $sectionId,
            'reference_id' => $referenceId,
        ]);
    }

    /**
     * @param int $threadId
     * @param bool [$asserted]
     * @return ForumThread
     */
    public function getThread($threadId, $asserted = false)
    {
        return parent::getModel(['thread_id' => $threadId], $asserted);
    }

    /**
     * @param int $threadId
     * @return ForumThread
     */
    public function getThreadAsserted($threadId)
    {
        return $this->getThread($threadId, true);
    }

    /**
     * List forum sections
     * @param int $sectionId section id
     * @return \Db\Util\Result
     */
    public function listSectionThreads($sectionId)
    {
        $db = $this->db();

        // get threads list for current section
        return $db->query(
            'SELECT `thread_id`, `title`, `author`, `priority`, (CASE WHEN `is_locked` = TRUE THEN "yes" ELSE "no" END) as `is_locked`'
            . ', `created_at`, `post_count`, `last_author`, `last_post`, CEIL(`post_count` / ' . ForumPost::POSTS_PER_PAGE . ') as `last_page`'
            . ', `section_id` FROM `forum_thread` WHERE `section_id` = ? AND `is_deleted` = FALSE'
            . ' ORDER BY `last_post` DESC, `created_at` DESC LIMIT ' . ForumThread::NUM_THREADS . ''
            , [$sectionId]
        );
    }

    /**
     * Check if specified thread title already exists
     * @param string $title title
     * @return \Db\Util\Result
     */
    public function checkThreadTitle($title)
    {
        $db = $this->db();

        return $db->query('SELECT `thread_id` FROM `forum_thread` WHERE `title` = ? AND `is_deleted` = FALSE', [
            $title
        ]);
    }

    /**
     * Find matching thread for specified card
     * @param int $cardId card id
     * @return \Db\Util\Result
     */
    public function cardThread($cardId)
    {
        $db = $this->db();

        return $db->query('SELECT `thread_id` FROM `forum_thread` WHERE `reference_id` = ? AND `section_id` = ? AND `is_deleted` = FALSE', [
            $cardId, ForumThread::CARDS_SECTION_ID
        ]);
    }

    /**
     * Find matching thread for specified deck
     * @param int $deckId card id
     * @return \Db\Util\Result
     */
    public function deckThread($deckId)
    {
        $db = $this->db();

        return $db->query('SELECT `thread_id` FROM `forum_thread` WHERE `reference_id` = ? AND `section_id` = ? AND `is_deleted` = FALSE', [
            $deckId, ForumThread::DECKS_SECTION_ID
        ]);
    }

    /**
     * Find matching thread for specified concept
     * @param int $deckId card id
     * @return \Db\Util\Result
     */
    public function conceptThread($deckId)
    {
        $db = $this->db();

        return $db->query('SELECT `thread_id` FROM `forum_thread` WHERE `reference_id` = ? AND `section_id` = ? AND `is_deleted` = FALSE', [
            $deckId, ForumThread::CONCEPTS_SECTION_ID
        ]);
    }

    /**
     * Find matching thread for specified replay
     * @param int $deckId card id
     * @return \Db\Util\Result
     */
    public function replayThread($deckId)
    {
        $db = $this->db();

        return $db->query('SELECT `thread_id` FROM `forum_thread` WHERE `reference_id` = ? AND `section_id` = ? AND `is_deleted` = FALSE', [
            $deckId, ForumThread::REPLAYS_SECTION_ID
        ]);
    }

    /**
     * List threads
     * @param int $sectionId section id
     * @param int $page page
     * @return \Db\Util\Result
     */
    public function listThreads($sectionId, $page)
    {
        $db = $this->db();

        $page = (is_numeric($page)) ? $page : 0;

        return $db->query(
            'SELECT `thread_id`, `title`, `author`, `priority`, (CASE WHEN `is_locked` = TRUE THEN "yes" ELSE "no" END) as `is_locked`'
            . ', `created_at`, `post_count`, `last_author`, `last_post`, CEIL(`post_count` / ' . ForumPost::POSTS_PER_PAGE . ') as `last_page`'
            . ', 0 as `flag` FROM `forum_thread` WHERE `section_id` = ? AND `is_deleted` = FALSE AND `priority` = "sticky" UNION'
            . ' SELECT `thread_id`, `title`, `author`, `priority`, (CASE WHEN `is_locked` = TRUE THEN "yes" ELSE "no" END) as `is_locked`'
            . ', `created_at`, `post_count`, `last_author`, `last_post`, CEIL(`post_count` / ' . ForumPost::POSTS_PER_PAGE . ') as `last_page`'
            . ', 1 as `flag` FROM `forum_thread` WHERE `section_id` = ? AND `is_deleted` = FALSE AND `priority` != "sticky"'
            . ' ORDER BY `flag` ASC, `last_post` DESC, `created_at` DESC'
            . ' LIMIT ' . (ForumThread::THREADS_PER_PAGE * $page) . ' , ' . ForumThread::THREADS_PER_PAGE . ''
            , [$sectionId, $sectionId]
        );
    }

    /**
     * List all threads except for specified one
     * @param int $threadId thread id
     * @return \Db\Util\Result
     */
    public function listTargetThreads($threadId)
    {
        $db = $this->db();

        return $db->query(
            'SELECT `thread_id`, `title` FROM `forum_thread` WHERE `thread_id` != ? AND `is_deleted` = FALSE ORDER BY `title` ASC',
            [$threadId]
        );
    }

    /**
     * Count pages in specified section
     * @param int $section section id
     * @return \Db\Util\Result
     */
    public function countPages($section)
    {
        $db = $this->db();

        return $db->query('SELECT COUNT(`thread_id`) as `count` FROM `forum_thread` WHERE `section_id` = ? AND `is_deleted` = FALSE', [
            $section
        ]);
    }

    /**
     * Load data of most recent post of specified thread
     * @param int $threadId thread id
     * @return \Db\Util\Result
     */
    public function getLastPost($threadId)
    {
        $db = $this->db();

        return $db->query(
            'SELECT `author`, `created_at` FROM `forum_post` WHERE `thread_id` = ? AND `is_deleted` = FALSE'
            . ' AND `created_at` = (SELECT MAX(`created_at`) FROM `forum_post` WHERE `thread_id` = ? AND `is_deleted` = FALSE)'
            , [$threadId, $threadId]
        );
    }

    /**
     * Rename all player name instances in forum threads authors
     * @param string $player player name
     * @param string $newName new name
     * @return \Db\Util\Result
     */
    public function renameAuthors($player, $newName)
    {
        $db = $this->db();

        return $db->query('UPDATE `forum_thread` SET `author` = ? WHERE `author` = ?', [$newName, $player]);
    }

    /**
     * Rename all player name instances in forum threads last authors
     * @param string $player player name
     * @param string $newName new name
     * @return \Db\Util\Result
     */
    public function renameLastAuthors($player, $newName)
    {
        $db = $this->db();

        return $db->query('UPDATE `forum_thread` SET `last_author` = ? WHERE `last_author` = ?', [
            $newName, $player
        ]);
    }
}
