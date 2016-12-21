<?php
/**
 * ForumSection - MArcomage discussion forum / section related functionality
 */

namespace Db\Entity;

class PdoForumSection extends PdoAbstract
{
    /**
     * DB schema
     * @return array
     */
    protected function schema()
    {
        return [
            'entity_name' => 'forum_section',
            'primary_fields' => [
                'section_id',
            ],
            'fields' => [
                'section_id' => [
                    'type' => EntityAbstract::TYPE_INT32,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'section_name' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                ],
                'description' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                ],
                'section_order' => [
                    'type' => EntityAbstract::TYPE_INT32,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
            ],
        ];
    }

    /**
     * List forum sections
     * @return \Db\Util\Result
     */
    public function listSections()
    {
        $db = $this->db();

        // get section list with thread count, ordered by custom order (alphabetical order is not suited for our needs)
        return $db->query(
            'SELECT `forum_section`.`section_id`, `section_name`, `description`, IFNULL(`count`, 0) as `count` FROM `forum_section`'
            . ' LEFT OUTER JOIN (SELECT `section_id`, COUNT(`thread_id`) as `count` FROM `forum_thread`'
            . ' WHERE `is_deleted` = FALSE GROUP BY `section_id`) as `threads` USING (`section_id`) ORDER BY `section_order` ASC'
        );
    }

    /**
     * List all forum sections except the specified section indexed by section id
     * @param int [$current_section] section id
     * @return \Db\Util\Result
     */
    public function listTargetSections($currentSection = 0)
    {
        $db = $this->db();

        return $db->query('SELECT `section_id`, `section_name` FROM `forum_section` WHERE `section_id` != ? ORDER BY `section_order`', [
            $currentSection
        ]);
    }

    /**
     * Get forum section data
     * @param int $sectionId
     * @return \Db\Util\Result
     */
    public function getSection($sectionId)
    {
        $db = $this->db();

        return $db->query('SELECT `section_id`, `section_name`, `description` FROM `forum_section` WHERE `section_id` = ?', [
            $sectionId
        ]);
    }

    /**
     * Search forum for specified phrase
     * @param string $phrase search phrase
     * @param string [$target] target filter
     * @param string [$section] section filter
     * @return \Db\Util\Result
     */
    public function search($phrase, $target = 'all', $section = 'any')
    {
        $db = $this->db();

        $params = array();
        $postSectionQuery = $threadSectionQuery = $postQuery = $threadQuery = '';

        if ($target == 'posts' || $target == 'all') {
            if ($section != 'any') {
                $postSectionQuery = ' AND `section_id` = ?';
            }

            // search post text content
            $postQuery = 'SELECT `thread_id`, `title`, `author`, `priority`, (CASE WHEN `is_locked` = TRUE THEN "yes" ELSE "no" END) as `is_locked`'
                . ', `created_at`, `post_count`, `last_author`, `last_post` FROM (SELECT DISTINCT `thread_id` FROM `forum_post`'
                . ' WHERE `is_deleted` = FALSE AND `content` LIKE ?) as `posts` INNER JOIN'
                . ' (SELECT `thread_id`, `title`, `author`, `priority`, `is_locked`, `created_at`, `post_count`, `last_author`, `last_post`'
                . ' FROM `forum_thread` WHERE `is_deleted` = FALSE' . $postSectionQuery . ') as `threads` USING(`thread_id`)';

            $params[] = '%' . $phrase . '%';

            if ($section != 'any') {
                $params[] = $section;
            }
        }

        if ($target == 'threads' || $target == 'all') {
            if ($section != 'any') {
                $threadSectionQuery = ' AND `section_id` = ?';
            }

            // search thread title
            $threadQuery = 'SELECT `thread_id`, `title`, `author`, `priority`, (CASE WHEN `is_locked` = TRUE THEN "yes" ELSE "no" END) as `is_locked`'
                . ', `created_at`, `post_count`, `last_author`, `last_post` FROM `forum_thread`'
                . ' WHERE `is_deleted` = FALSE AND `title` LIKE ?' . $threadSectionQuery . '';
            $params[] = '%' . $phrase . '%';

            if ($section != 'any') {
                $params[] = $section;
            }
        }

        // merge results
        $query = $postQuery . (($target == 'all') ? ' UNION DISTINCT ' : '') . $threadQuery . ' ORDER BY `last_post` DESC';

        return $db->query($query, $params);
    }
}
