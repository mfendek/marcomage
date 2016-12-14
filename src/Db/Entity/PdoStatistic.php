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
            'entity_name' => 'chats',
            'primary_fields' => [
                'CardID',
            ],
            'fields' => [
                'CardID' => [
                    'type' => EntityAbstract::TYPE_INT32,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'Played' => [
                    'type' => EntityAbstract::TYPE_INT32,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'Discarded' => [
                    'type' => EntityAbstract::TYPE_INT32,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'PlayedTotal' => [
                    'type' => EntityAbstract::TYPE_INT32,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'DiscardedTotal' => [
                    'type' => EntityAbstract::TYPE_INT32,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'Drawn' => [
                    'type' => EntityAbstract::TYPE_INT32,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'DrawnTotal' => [
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
            'Played', 'PlayedTotal', 'Discarded', 'DiscardedTotal', 'Drawn', 'DrawnTotal'
        ])) ? $condition : 'Played';

        return $db->query(
            'SELECT `CardID`, `' . $condition . '` as `value` FROM `statistics` WHERE `CardID` > 0 ORDER BY `'
            . $condition . '` DESC, `CardID` ASC'
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
            'SELECT `Played`, `Discarded`, `Drawn`, `PlayedTotal`, `DiscardedTotal`, `DrawnTotal` FROM `statistics` WHERE `CardID` = ?'
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
            'SELECT `CardID` FROM `statistics` WHERE `CardID` IN (' . implode(",", $queryString) . ')'
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
            'play' => 'Played',
            'discard' => 'Discarded',
            'draw' => 'Drawn',
        ];

        $queryString = $params = array();
        foreach ($actions as $action => $amount) {
            // current counter
            $queryString[] = '`' . $trans[$action] . '` = `' . $trans[$action] . '` + ?';
            $params[] = $amount;

            // total counter
            $queryString[] = '`' . $trans[$action] . 'Total` = `' . $trans[$action] . 'Total` + ?';
            $params[] = $amount;
        }

        $params[] = $cardId;

        return $db->query('UPDATE `statistics` SET ' . implode(", ", $queryString) . ' WHERE `CardID` = ?', $params);
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
            'play' => 'Played',
            'discard' => 'Discarded',
            'draw' => 'Drawn',
        ];

        $columnString = $queryString = $params = array();
        $params[] = $cardId;

        foreach ($actions as $action => $amount) {
            // current counter
            $columnString[] = '`' . $trans[$action] . '`';
            $queryString[] = '?';
            $params[] = $amount;

            // total counter
            $columnString[] = '`' . $trans[$action] . 'Total`';
            $queryString[] = '?';
            $params[] = $amount;
        }

        return $db->query(
            'INSERT INTO `statistics` (`CardID`,' . implode(",", $columnString) . ')'
            . ' VALUES (?,' . implode(",", $queryString) . ')'
            , $params
        );
    }
}
