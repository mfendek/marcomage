<?php
/**
 * Concept - the representation of card concepts database
 */

namespace Db\Entity;

use Def\Model\Card;
use Util\Date;

class PdoConcept extends PdoAbstract
{
    /**
     * DB schema
     * @return array
     */
    protected function schema()
    {
        return [
            'entity_name' => 'concepts',
            'model_name' => 'concept',
            'primary_fields' => [
                'CardID',
            ],
            'fields' => [
                'CardID' => [
                    'type' => EntityAbstract::TYPE_INT32,
                    'default' => 0,
                    'options' => [
                        EntityAbstract::OPT_UNSIGNED,
                        EntityAbstract::OPT_AUTO_ID,
                    ],
                ],
                'Name' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                ],
                'Rarity' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                ],
                'Bricks' => [
                    'type' => EntityAbstract::TYPE_INT32,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'Gems' => [
                    'type' => EntityAbstract::TYPE_INT32,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'Recruits' => [
                    'type' => EntityAbstract::TYPE_INT32,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'Effect' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                ],
                'Keywords' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                ],
                'Picture' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => 'blank.jpg',
                ],
                'Note' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                ],
                'State' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => 'waiting',
                ],
                'Author' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                ],
                'LastChange' => [
                    'type' => EntityAbstract::TYPE_DATETIME,
                    'default' => Date::DATETIME_ZERO,
                    'options' => [EntityAbstract::OPT_INSERT_DATETIME],
                ],
                'ThreadID' => [
                    'type' => EntityAbstract::TYPE_INT32,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
            ],
        ];
    }

    /**
     * Create new concept
     * @param array $data concept data
     * @return \Db\Model\Concept
     */
    public function createConcept(array $data)
    {
        return parent::createModel([
            'Name' => $data['name'],
            'Rarity' => $data['rarity'],
            'Bricks' => $data['bricks'],
            'Gems' => $data['gems'],
            'Recruits' => $data['recruits'],
            'Effect' => $data['effect'],
            'Keywords' => $data['keywords'],
            'Note' => $data['note'],
            'Author' => $data['author'],
        ]);
    }

    /**
     * @param int $cardId
     * @param bool [$asserted]
     * @return \Db\Model\Concept
     */
    public function getConcept($cardId, $asserted = false)
    {
        return parent::getModel(['CardID' => $cardId], $asserted);
    }

    /**
     * @param int $cardId
     * @return \Db\Model\Concept
     */
    public function getConceptAsserted($cardId)
    {
        return $this->getConcept($cardId, true);
    }

    /**
     * List concepts
     * @param string $name concept name
     * @param string $author player name
     * @param string $date date filter
     * @param string $state state filter
     * @param string $condition order condition
     * @param string $order order option
     * @param int $page current page
     * @return \Db\Util\Result
     */
    public function getList($name, $author, $date, $state, $condition, $order, $page)
    {
        $db = $this->db();

        $nameQuery = ($name != '') ? ' AND `Name` LIKE ?' : '';
        $authorQuery = ($author != 'none') ? ' AND `Author` = ?' : '';
        $dateQuery = ($date != 'none') ? ' AND `LastChange` >= NOW() - INTERVAL ? DAY' : '';
        $stateQuery = ($state != 'none') ? ' AND `State` = ?' : '';

        $params = array();
        if ($name != '') {
            $params[] = '%' . $name . '%';
        }
        if ($author != 'none') {
            $params[] = $author;
        }
        if ($date != 'none') {
            $params[] = $date;
        }
        if ($state != 'none') {
            $params[] = $state;
        }

        $condition = (in_array($condition, ['Name', 'LastChange'])) ? $condition : 'LastChange';
        $order = ($order == 'ASC') ? 'ASC' : 'DESC';
        $page = (is_numeric($page)) ? $page : 0;

        return $db->query(
            'SELECT `CardID` as `id`, `Name` as `name`, `Rarity` as `rarity`, `Bricks` as `bricks`, `Gems` as `gems`'
            . ', `Recruits` as `recruits`, `Effect` as `effect`, `Keywords` as `keywords`, `Picture` as `picture`'
            . ', `Note` as `note`, `State` as `state`, `Author` as `author`, `LastChange` as `lastchange` FROM `concepts`'
            . ' WHERE 1' . $nameQuery . $authorQuery . $dateQuery . $stateQuery
            . ' ORDER BY `' . $condition . '` ' . $order . ' LIMIT '
            . (Card::CARDS_PER_PAGE * $page) . ' , ' . Card::CARDS_PER_PAGE . ''
            , $params
        );
    }

    /**
     * Count pages for concepts list
     * @param string $name concept name
     * @param string $author player name
     * @param string $date date filter
     * @param string $state state filter
     * @return \Db\Util\Result
     */
    public function countPages($name, $author, $date, $state)
    {
        $db = $this->db();

        $nameQuery = ($name != '') ? ' AND `Name` LIKE ?' : '';
        $authorQuery = ($author != 'none') ? ' AND `Author` = ?' : '';
        $dateQuery = ($date != 'none') ? ' AND `LastChange` >= NOW() - INTERVAL ? DAY' : '';
        $stateQuery = ($state != 'none') ? ' AND `State` = ?' : '';

        $params = array();
        if ($name != '') {
            $params[] = '%' . $name . '%';
        }
        if ($author != 'none') {
            $params[] = $author;
        }
        if ($date != 'none') {
            $params[] = $date;
        }
        if ($state != 'none') {
            $params[] = $state;
        }

        return $db->query(
            'SELECT COUNT(`CardID`) as `Count` FROM `concepts` WHERE 1'
            . $nameQuery . $authorQuery . $dateQuery . $stateQuery . ''
            , $params
        );
    }

    /**
     * List concept authors
     * @param string $date  date filter
     * @return \Db\Util\Result
     */
    public function listAuthors($date)
    {
        $db = $this->db();

        $dateQuery = ($date != 'none') ? ' AND `LastChange` >= NOW() - INTERVAL ? DAY' : '';

        $params = array();
        if ($date != 'none') {
            $params[] = $date;
        }

        return $db->query(
            'SELECT DISTINCT `Author` FROM `concepts` WHERE 1' . $dateQuery . ' ORDER BY `Author` ASC', $params
        );
    }

    /**
     * Check if there are some new concepts
     * @param string $time player's last activity
     * @return \Db\Util\Result
     */
    public function newConcepts($time)
    {
        $db = $this->db();

        return $db->query('SELECT 1 FROM `concepts` WHERE `LastChange` > ? LIMIT 1', [$time]);
    }

    /**
     * Find a concept that is assigned to specified forum thread
     * @param int $threadId
     * @return \Db\Util\Result
     */
    public function findConcept($threadId)
    {
        $db = $this->db();

        return $db->query('SELECT `CardID` FROM `concepts` WHERE `ThreadID` = ?', [$threadId]);
    }

    /**
     * Rename all instances of player name in concepts
     * @param string $player player name
     * @param string $newName new name
     * @return \Db\Util\Result
     */
    public function renameConcepts($player, $newName)
    {
        $db = $this->db();

        return $db->query('UPDATE `concepts` SET `Author` = ? WHERE `Author` = ?', [$newName, $player]);
    }

    /**
     * Calculate top 10 concept authors for suggested concepts
     * @return \Db\Util\Result
     */
    public function suggestedConcepts()
    {
        $db = $this->db();

        return $db->query(
            'SELECT `Author`, COUNT(`Author`) as `count` FROM `concepts` WHERE `State` = "waiting"'
            . ' OR `State` = "interesting" GROUP BY `Author` ORDER BY `count` DESC, `Author` ASC LIMIT 10'
        );
    }

    /**
     * Calculate top 10 concept authors for implemented concepts
     * @return \Db\Util\Result
     */
    public function implementedConcepts()
    {
        $db = $this->db();

        return $db->query(
            'SELECT `Author`, COUNT(`Author`) as `count` FROM `concepts`'
            . ' WHERE `State` = "implemented" GROUP BY `Author` ORDER BY `count` DESC, `Author` ASC LIMIT 10'
        );
    }
}
