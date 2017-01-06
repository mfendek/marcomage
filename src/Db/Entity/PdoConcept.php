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
            'entity_name' => 'concept',
            'primary_fields' => [
                'card_id',
            ],
            'fields' => [
                'card_id' => [
                    'type' => EntityAbstract::TYPE_INT,
                    'default' => 0,
                    'options' => [
                        EntityAbstract::OPT_UNSIGNED,
                        EntityAbstract::OPT_AUTO_ID,
                    ],
                ],
                'name' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                ],
                'rarity' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                ],
                'bricks' => [
                    'type' => EntityAbstract::TYPE_INT,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'gems' => [
                    'type' => EntityAbstract::TYPE_INT,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'recruits' => [
                    'type' => EntityAbstract::TYPE_INT,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'effect' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                ],
                'keywords' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                ],
                'picture' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => 'blank.jpg',
                ],
                'note' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                ],
                'state' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => 'waiting',
                ],
                'author' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                ],
                'modified_at' => [
                    'type' => EntityAbstract::TYPE_DATETIME,
                    'default' => Date::DATETIME_ZERO,
                    'options' => [EntityAbstract::OPT_INSERT_DATETIME],
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
            'name' => $data['name'],
            'rarity' => $data['rarity'],
            'bricks' => $data['bricks'],
            'gems' => $data['gems'],
            'recruits' => $data['recruits'],
            'effect' => $data['effect'],
            'keywords' => $data['keywords'],
            'note' => $data['note'],
            'author' => $data['author'],
        ]);
    }

    /**
     * @param int $cardId
     * @param bool [$asserted]
     * @return \Db\Model\Concept
     */
    public function getConcept($cardId, $asserted = false)
    {
        return parent::getModel(['card_id' => $cardId], $asserted);
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

        $nameQuery = ($name != '') ? ' AND `name` LIKE ?' : '';
        $authorQuery = ($author != 'none') ? ' AND `author` = ?' : '';
        $dateQuery = ($date != 'none') ? ' AND `modified_at` >= NOW() - INTERVAL ? DAY' : '';
        $stateQuery = ($state != 'none') ? ' AND `state` = ?' : '';

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

        $condition = (in_array($condition, ['name', 'modified_at'])) ? $condition : 'modified_at';
        $order = ($order == 'ASC') ? 'ASC' : 'DESC';
        $page = (is_numeric($page)) ? $page : 0;

        return $db->query(
            'SELECT `card_id` as `id`, `name`, `rarity`, `bricks`, `gems`'
            . ', `recruits`, `effect`, `keywords`, `picture`'
            . ', `note`, `state`, `author`, `modified_at` FROM `concept`'
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

        $nameQuery = ($name != '') ? ' AND `name` LIKE ?' : '';
        $authorQuery = ($author != 'none') ? ' AND `author` = ?' : '';
        $dateQuery = ($date != 'none') ? ' AND `modified_at` >= NOW() - INTERVAL ? DAY' : '';
        $stateQuery = ($state != 'none') ? ' AND `state` = ?' : '';

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
            'SELECT COUNT(`card_id`) as `count` FROM `concept` WHERE 1'
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

        $dateQuery = ($date != 'none') ? ' AND `modified_at` >= NOW() - INTERVAL ? DAY' : '';

        $params = array();
        if ($date != 'none') {
            $params[] = $date;
        }

        return $db->query(
            'SELECT DISTINCT `author` FROM `concept` WHERE 1' . $dateQuery . ' ORDER BY `author` ASC', $params
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

        return $db->query('SELECT 1 FROM `concept` WHERE `modified_at` > ? LIMIT 1', [$time]);
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

        return $db->query('UPDATE `concept` SET `author` = ? WHERE `author` = ?', [$newName, $player]);
    }

    /**
     * Calculate top 10 concept authors for suggested concepts
     * @return \Db\Util\Result
     */
    public function suggestedConcepts()
    {
        $db = $this->db();

        return $db->query(
            'SELECT `author`, COUNT(`author`) as `count` FROM `concept` WHERE `state` = "waiting"'
            . ' OR `state` = "interesting" GROUP BY `author` ORDER BY `count` DESC, `author` ASC LIMIT 10'
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
            'SELECT `author`, COUNT(`author`) as `count` FROM `concept`'
            . ' WHERE `state` = "implemented" GROUP BY `author` ORDER BY `count` DESC, `author` ASC LIMIT 10'
        );
    }
}
