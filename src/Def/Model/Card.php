<?php
/**
 * Card - the representation of a single card
 */

namespace Def\Model;

use ArcomageException as Exception;

class Card extends ModelAbstract
{
    /**
     * Foil version card cost in gold
     */
    const FOIL_COST = 500;

    /**
     * Number of cards that are displayed per one page
     */
    const CARDS_PER_PAGE = 20;

    /**
     * @return array
     */
    private function fields()
    {
        return [
            'id', 'name', 'rarity', 'bricks', 'gems', 'recruits', 'modes', 'level', 'keywords', 'effect', 'code', 'created', 'modified'
        ];
    }

    /**
     * Card constructor.
     * @param array $data
     */
    public function __construct(array $data)
    {
        foreach ($this->fields() as $field) {
            if (isset($data[$field])) {
                $this->values[$field] = $data[$field];
            }
        }
    }

    /**
     * Check if card is a play again card (player can make another move after playing such card)
     * @return bool
     */
    public function isPlayAgainCard()
    {
        return ($this->hasKeyword('Quick') || $this->hasKeyword('Swift'));
    }

    ///
    /// check if card has a specific keyword or any keyword
    /// @return bool true card has specified keyword, false otherwise
    /**
     * Check if card has a specific keyword or any keyword
     * @param string $keyword
     * @return bool
     */
    public function hasKeyword($keyword)
    {
        // case 1: specific keyword
        if ($keyword != 'any') {
            return (strpos($this->getFieldValue('keywords'), $keyword) !== false);
        }
        // case 2: any keyword
        else {
            return ($this->getFieldValue('keywords') != '');
        }
    }

    /**
     * Returns number of specified resource calculated from card cost
     * @param string $type [$type]
     * @throws Exception
     * @return int
     */
    public function getResources($type = '')
    {
        $resources = ['bricks', 'gems', 'recruits'];

        // validate resource type
        $type = strtolower($type);
        if ($type != '' && !in_array($type, $resources)) {
            throw new Exception('invalid resource type ' . $type);
        }

        // case 1: count specific resource
        if ($type != '') {
            $amount = $this->getFieldValue($type);
        }
        // case 2: count all resources
        else {
            $amount = 0;
            foreach ($resources as $resource) {
                $amount += $this->getFieldValue($resource);
            }
        }

        return $amount;
    }

    /**
     * Returns specified field value from card data or all fields
     * @param string $field [$field]
     * @throws Exception
     * @return array|mixed
     */
    public function getData($field = '')
    {
        $fields = $this->fields();

        // validate field type
        $field = strtolower($field);
        if ($field != '' && !in_array($field, $fields)) {
            throw new Exception('invalid field name ' . $field);
        }

        // case 1: specific field
        if ($field != '') {
            return $this->getFieldValue($field);
        }
        // case 2: all fields
        else {
            $data = array();
            foreach ($fields as $fieldName) {
                $data[$fieldName] = $this->getFieldValue($fieldName);
            }

            return $data;
        }
    }

    /**
     * @return int
     */
    public function id()
    {
        return $this->getData('id');
    }

    /**
     * @return string
     */
    public function getRarity()
    {
        return $this->getData('rarity');
    }

    /**
     * Comparison function for sorting
     * @param array $card1
     * @param array $card2
     * @return int
     */
    public static function compareCardData(array $card1, array $card2)
    {
        $card1Total = $card1['bricks'] + $card1['gems'] + $card1['recruits'];
        $card2Total = $card2['bricks'] + $card2['gems'] + $card2['recruits'];

        // less than
        if ($card1Total < $card2Total) {
            return -1;
        }
        // greater than
        elseif ($card1Total > $card2Total) {
            return 1;
        }

        // equals - compare ids
        if ($card1['id'] < $card2['id']) {
            return -1;
        }
        elseif ($card1['id'] > $card2['id']) {
            return 1;
        }

        // should never happen
        return 0;
    }
}
