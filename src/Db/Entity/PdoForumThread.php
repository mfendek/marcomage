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
            'entity_name' => 'forum_threads',
            'model_name' => 'forum_thread',
            'primary_fields' => [
                'ThreadID',
            ],
            'fields' => [
                'ThreadID' => [
                    'type' => EntityAbstract::TYPE_INT32,
                    'default' => 0,
                    'options' => [
                        EntityAbstract::OPT_UNSIGNED,
                        EntityAbstract::OPT_AUTO_ID,
                    ],
                ],
                'Title' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                ],
                'Author' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                ],
                'Priority' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => 'normal',
                ],
                'Locked' => [
                    'type' => EntityAbstract::TYPE_INT32,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'Deleted' => [
                    'type' => EntityAbstract::TYPE_INT32,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'SectionID' => [
                    'type' => EntityAbstract::TYPE_INT32,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'Created' => [
                    'type' => EntityAbstract::TYPE_DATETIME,
                    'default' => Date::DATETIME_ZERO,
                    'options' => [EntityAbstract::OPT_INSERT_DATETIME],
                ],
                'PostCount' => [
                    'type' => EntityAbstract::TYPE_INT32,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'LastAuthor' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                ],
                'LastPost' => [
                    'type' => EntityAbstract::TYPE_DATETIME,
                    'default' => Date::DATETIME_ZERO,
                ],
                'CardID' => [
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
     * @param int [$cardId] card id reference
     * @return ForumThread
     */
    public function createThread($title, $author, $priority, $sectionId, $cardId = 0)
    {
        return parent::createModel([
            'Title' => $title,
            'Author' => $author,
            'Priority' => $priority,
            'SectionID' => $sectionId,
            'CardID' => $cardId,
        ]);
    }

    /**
     * @param int $threadId
     * @param bool [$asserted]
     * @return ForumThread
     */
    public function getThread($threadId, $asserted = false)
    {
        return parent::getModel(['ThreadID' => $threadId], $asserted);
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
            'SELECT `ThreadID`, `Title`, `Author`, `Priority`, (CASE WHEN `Locked` = TRUE THEN "yes" ELSE "no" END) as `Locked`'
            . ', `Created`, `PostCount`, `LastAuthor`, `LastPost`, CEIL(`PostCount` / ' . ForumPost::POSTS_PER_PAGE . ') as `LastPage`'
            . ', `SectionID` FROM `forum_threads` WHERE `SectionID` = ? AND `Deleted` = FALSE'
            . ' ORDER BY `LastPost` DESC, `Created` DESC LIMIT ' . ForumThread::NUM_THREADS . ''
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

        return $db->query('SELECT `ThreadID` FROM `forum_threads` WHERE `Title` = ? AND `Deleted` = FALSE', [
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

        return $db->query('SELECT `ThreadID` FROM `forum_threads` WHERE `CardID` = ? AND `SectionID` = ? AND `Deleted` = FALSE', [
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

        return $db->query('SELECT `ThreadID` FROM `forum_threads` WHERE `CardID` = ? AND `SectionID` = ? AND `Deleted` = FALSE', [
            $deckId, ForumThread::DECKS_SECTION_ID
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
            'SELECT `ThreadID`, `Title`, `Author`, `Priority`, (CASE WHEN `Locked` = TRUE THEN "yes" ELSE "no" END) as `Locked`'
            . ', `Created`, `PostCount`, `LastAuthor`, `LastPost`, CEIL(`PostCount` / ' . ForumPost::POSTS_PER_PAGE . ') as `LastPage`'
            . ', 0 as `flag` FROM `forum_threads` WHERE `SectionID` = ? AND `Deleted` = FALSE AND `Priority` = "sticky" UNION'
            . ' SELECT `ThreadID`, `Title`, `Author`, `Priority`, (CASE WHEN `Locked` = TRUE THEN "yes" ELSE "no" END) as `Locked`'
            . ', `Created`, `PostCount`, `LastAuthor`, `LastPost`, CEIL(`PostCount` / ' . ForumPost::POSTS_PER_PAGE . ') as `LastPage`'
            . ', 1 as `flag` FROM `forum_threads` WHERE `SectionID` = ? AND `Deleted` = FALSE AND `Priority` != "sticky"'
            . ' ORDER BY `Flag` ASC, `LastPost` DESC, `Created` DESC'
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
            'SELECT `ThreadID`, `Title` FROM `forum_threads` WHERE `ThreadID` != ? AND `Deleted` = FALSE ORDER BY `Title` ASC',
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

        return $db->query('SELECT COUNT(`ThreadID`) as `Count` FROM `forum_threads` WHERE `SectionID` = ? AND `Deleted` = FALSE', [
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
            'SELECT `Author`, `Created` FROM `forum_posts` WHERE `ThreadID` = ? AND `Deleted` = FALSE'
            . ' AND `Created` = (SELECT MAX(`Created`) FROM `forum_posts` WHERE `ThreadID` = ? AND `Deleted` = FALSE)'
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

        return $db->query('UPDATE `forum_threads` SET `Author` = ? WHERE `Author` = ?', [$newName, $player]);
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

        return $db->query('UPDATE `forum_threads` SET `LastAuthor` = ? WHERE `LastAuthor` = ?', [
            $newName, $player
        ]);
    }
}
