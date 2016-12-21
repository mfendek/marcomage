<?php
/**
 * Statistic - card statistics
 */

namespace Db\Entity;

class PdoStatistic extends PdoAbstract
{
    /**
     * DB schema
     * @return array
     */
    protected function schema()
    {
        return [
            'entity_name' => 'statistic',
            'primary_fields' => [
                'card_id',
            ],
            'fields' => [
                'card_id' => [
                    'type' => EntityAbstract::TYPE_INT32,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'played' => [
                    'type' => EntityAbstract::TYPE_INT32,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'discarded' => [
                    'type' => EntityAbstract::TYPE_INT32,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'played_total' => [
                    'type' => EntityAbstract::TYPE_INT32,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'discarded_total' => [
                    'type' => EntityAbstract::TYPE_INT32,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'drawn' => [
                    'type' => EntityAbstract::TYPE_INT32,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'drawn_total' => [
                    'type' => EntityAbstract::TYPE_INT32,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
            ],
        ];
    }

    /**
     * List statistic data for card list based on specified parameters
     * @param string $condition filter condition
     * @return \Db\Util\Result
     */
    public function listCardStats($condition)
    {
        $db = $this->db();

        $condition = (in_array($condition, [
            'played', 'played_total', 'discarded', 'discarded_total', 'drawn', 'drawn_total'
        ])) ? $condition : 'played';

        return $db->query(
            'SELECT `card_id`, `' . $condition . '` as `value` FROM `statistic` WHERE `card_id` > 0 ORDER BY `'
            . $condition . '` DESC, `card_id` ASC'
        );
    }

    /**
     * Return statistics for specified card
     * @param int $cardId
     * @return \Db\Util\Result
     */
    public function cardStatistics($cardId)
    {
        $db = $this->db();

        return $db->query(
            'SELECT `played`, `discarded`, `drawn`, `played_total`, `discarded_total`, `drawn_total` FROM `statistic` WHERE `card_id` = ?'
            , [$cardId]
        );
    }

    /**
     * Find card statistic data
     * @param array $ids
     * @return \Db\Util\Result
     */
    public function findCards(array $ids)
    {
        $db = $this->db();

        $queryString = array();
        foreach ($ids as $cardId) {
            $queryString[] = '?';
        }

        return $db->query(
            'SELECT `card_id` FROM `statistic` WHERE `card_id` IN (' . implode(",", $queryString) . ')'
            , $ids
        );
    }

    /**
     * Update card statistics
     * @param int $cardId
     * @param array $actions
     * @return \Db\Util\Result
     */
    public function updateCard($cardId, array $actions)
    {
        $db = $this->db();

        // action to column names translation
        $trans = [
            'play' => 'played',
            'discard' => 'discarded',
            'draw' => 'drawn',
        ];

        $queryString = $params = array();
        foreach ($actions as $action => $amount) {
            // current counter
            $queryString[] = '`' . $trans[$action] . '` = `' . $trans[$action] . '` + ?';
            $params[] = $amount;

            // total counter
            $queryString[] = '`' . $trans[$action] . '_total` = `' . $trans[$action] . '_total` + ?';
            $params[] = $amount;
        }

        $params[] = $cardId;

        return $db->query('UPDATE `statistic` SET ' . implode(", ", $queryString) . ' WHERE `card_id` = ?', $params);
    }

    /**
     * Create card statistics
     * @param int $cardId
     * @param array $actions
     * @return \Db\Util\Result
     */
    public function createCard($cardId, array $actions)
    {
        $db = $this->db();

        // action to column names translation
        $trans = [
            'play' => 'played',
            'discard' => 'discarded',
            'draw' => 'drawn',
        ];

        $columnString = $queryString = $params = array();
        $params[] = $cardId;

        foreach ($actions as $action => $amount) {
            // current counter
            $columnString[] = '`' . $trans[$action] . '`';
            $queryString[] = '?';
            $params[] = $amount;

            // total counter
            $columnString[] = '`' . $trans[$action] . '_total`';
            $queryString[] = '?';
            $params[] = $amount;
        }

        return $db->query(
            'INSERT INTO `statistic` (`card_id`,' . implode(",", $columnString) . ')'
            . ' VALUES (?,' . implode(",", $queryString) . ')'
            , $params
        );
    }
}
