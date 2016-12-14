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
            'entity_name' => 'forum_sections',
            'primary_fields' => [
                'SectionID',
            ],
            'fields' => [
                'SectionID' => [
                    'type' => EntityAbstract::TYPE_INT32,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'SectionName' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                ],
                'Description' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                ],
                'SectionOrder' => [
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
            'SELECT `forum_sections`.`SectionID`, `SectionName`, `Description`, IFNULL(`count`, 0) as `count` FROM `forum_sections`'
            . ' LEFT OUTER JOIN (SELECT `SectionID`, COUNT(`ThreadID`) as `count` FROM `forum_threads`'
            . ' WHERE `Deleted` = FALSE GROUP BY `SectionID`) as `threads` USING (`SectionID`) ORDER BY `SectionOrder` ASC'
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

        return $db->query('SELECT `SectionID`, `SectionName` FROM `forum_sections` WHERE `SectionID` != ? ORDER BY `SectionOrder`', [
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

        return $db->query('SELECT `SectionID`, `SectionName`, `Description` FROM `forum_sections` WHERE `SectionID` = ?', [
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
                $postSectionQuery = ' AND `SectionID` = ?';
            }

            // search post text content
            $postQuery = 'SELECT `ThreadID`, `Title`, `Author`, `Priority`, (CASE WHEN `Locked` = TRUE THEN "yes" ELSE "no" END) as `Locked`'
                . ', `Created`, `PostCount`, `LastAuthor`, `LastPost` FROM (SELECT DISTINCT `ThreadID` FROM `forum_posts`'
                . ' WHERE `Deleted` = FALSE AND `Content` LIKE ?) as `posts` INNER JOIN'
                . ' (SELECT `ThreadID`, `Title`, `Author`, `Priority`, `Locked`, `Created`, `PostCount`, `LastAuthor`, `LastPost`'
                . ' FROM `forum_threads` WHERE `Deleted` = FALSE' . $postSectionQuery . ') as `threads` USING(`ThreadID`)';

            $params[] = '%' . $phrase . '%';

            if ($section != 'any') {
                $params[] = $section;
            }
        }

        if ($target == 'threads' || $target == 'all') {
            if ($section != 'any') {
                $threadSectionQuery = ' AND `SectionID` = ?';
            }

            // search thread title
            $threadQuery = 'SELECT `ThreadID`, `Title`, `Author`, `Priority`, (CASE WHEN `Locked` = TRUE THEN "yes" ELSE "no" END) as `Locked`'
                . ', `Created`, `PostCount`, `LastAuthor`, `LastPost` FROM `forum_threads`'
                . ' WHERE `Deleted` = FALSE AND `Title` LIKE ?' . $threadSectionQuery . '';
            $params[] = '%' . $phrase . '%';

            if ($section != 'any') {
                $params[] = $section;
            }
        }

        // merge results
        $query = $postQuery . (($target == 'all') ? ' UNION DISTINCT ' : '') . $threadQuery . ' ORDER BY `LastPost` DESC';

        return $db->query($query, $params);
    }
}
