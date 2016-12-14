<?php
/**
 * Card - the representation of a cards database
 */

namespace Def\Entity;

use ArcomageException as Exception;
use Def\Model\Card;
use Util\Xml;

class XmlCard extends EntityAbstract
{
    /**
     * Card objects cache (indexed by card id)
     * @var array
     */
    private $cards = array();

    /**
     * card data (ID -> card data)
     * @return array
     */
    protected function getDb()
    {
        return parent::getDb();
    }

    /**
     *
     */
    protected function initDb()
    {
        $reader = new Xml();
        $cards = $reader->readFile('xml/cards.xml', 'http://arcomage.net');

        $this->db = array();
        $default = array();
        foreach ($cards as $id => $card) {
            /* @var \SimpleXMLElement $card */
            $data = array();

            // mandatory data
            $data['id'] = (int)$id;
            $data['name'] = (string)$card->name;
            $data['rarity'] = (string)$card->rarity;

            // optional data
            foreach (['bricks', 'gems', 'recruits'] as $resource) {
                if (isset($card->cost->$resource)) {
                    $data[$resource] = (int)$card->cost->$resource;
                }
            }

            if (isset($card->modes)) {
                $data['modes'] = (int)$card->modes;
            }

            $data['level'] = (int)$card->level;

            if (isset($card->keywords)) {
                $data['keywords'] = (string)$card->keywords;
            }

            $data['effect'] = (string)$card->effect;
            $data['code'] = (string)$card->code;
            $data['created'] = (string)$card->created;
            $data['modified'] = (string)$card->modified;

            // case 1: default card - store default data
            if ($data['id'] == 0) {
                $default = $data;
            }
            // case 2: standard card - merge data with default
            else {
                $data = array_merge($default, $data);
            }

            $this->db[$data['id']] = $data;
        }
    }

    /**
     * @param $cardId
     * @return Card
     */
    public function getCard($cardId)
    {
        // create card object only if necessary
        if (empty($this->cards[$cardId])) {
            $data = $this->getData([$cardId]);

            $cardData = array_pop($data);
            $this->cards[$cardId] = new Card($cardData);
        }

        return $this->cards[$cardId];
    }

    /**
     * Filters cards according to the provided filtering instructions
     * @param array [$filters] filters and their parameters
     * Available filters are:
     * 'name'     => { <search_substring> }, queries `Name`
     * 'rarity'    => { None | Common | Uncommon | Rare }, queries `Rarity`
     * 'keyword'  => { Any keyword | No keywords | <a specific keyword> }, queries `Keywords`
     * 'cost'     => { Red | Blue | Green | Zero | Mixed }, queries `Bricks`, `Gems` and `Recruits`
     * 'advanced' => { <a specific substring> }, queries `Effect`
     * 'support'  => { Any keyword | No keywords | <a specific keyword> }, queries `Effect`
     * 'level'    => { <specific_level> }, queries `level`
     * 'level_op' => { = | <= }, additional parameter for `level` defaults to '='
     * 'forbidden' => { boolean }, include forbidden cards, defaults to true
     * @return array
     */
    public function getList(array $filters = [])
    {
        // list all cards
        $cards = $this->getData();

        // remove default from cards
        unset($cards[0]);

        // filter cards
        $out = array();
        foreach ($cards as $cardId => $card) {
            // name filter
            if (isset($filters['name']) && strpos($card['name'], $filters['name']) === false) {
                continue;
            }

            // rarity filter
            if (isset($filters['rarity']) && $card['rarity'] != $filters['rarity']) {
                continue;
            }

            // keyword filter
            if (isset($filters['keyword'])) {
                // case 1: any keywords
                if ($filters['keyword'] == 'Any keyword') {
                    if ($card['keywords'] == '') {
                        continue;
                    }
                }
                // case 2: no keywords
                elseif ($filters['keyword'] == 'No keywords') {
                    if ($card['keywords'] != '') {
                        continue;
                    }
                }
                // case 3: one specific keyword
                else {
                    if (strpos($card['keywords'], $filters['keyword']) === false) {
                        continue;
                    }
                }
            }

            // resource cost filter
            if (isset($filters['cost'])) {
                // case 1: bricks cost only
                if ($filters['cost'] == 'Red') {
                    if ($card['bricks'] == 0 || $card['gems'] > 0 || $card['recruits'] > 0) {
                        continue;
                    }
                }
                // case 2: gems cost only
                elseif ($filters['cost'] == 'Blue') {
                    if ($card['bricks'] > 0 || $card['gems'] == 0 || $card['recruits'] > 0) {
                        continue;
                    }
                }
                // case 3: recruits cost only
                elseif ($filters['cost'] == 'Green') {
                    if ($card['bricks'] > 0 || $card['gems'] > 0 || $card['recruits'] == 0) {
                        continue;
                    }
                }
                // case 4: zero cost only
                elseif ($filters['cost'] == 'Zero') {
                    if ($card['bricks'] > 0 || $card['gems'] > 0 || $card['recruits'] > 0) {
                        continue;
                    }
                }
                // case 5: mixed cost only
                elseif ($filters['cost'] == 'Mixed') {
                    if ((($card['bricks'] > 0) + ($card['gems'] > 0) + ($card['recruits'] > 0)) < 2) {
                        continue;
                    }
                }
            }

            // advanced filter
            if (isset($filters['advanced']) && strpos($card['effect'], $filters['advanced']) === false
                && strpos($card['effect'], strtolower($filters['advanced'])) === false) {
                continue;
            }

            // support keyword filter
            if (isset($filters['support'])) {
                // case 1: any keywords
                if ($filters['support'] == 'Any keyword') {
                    if (strpos($card['effect'], '<b>') === false) {
                        continue;
                    }
                }
                // case 2: no keywords
                elseif ($filters['support'] == 'No keywords') {
                    if (strpos($card['effect'], '<b>') !== false) {
                        continue;
                    }
                }
                // case 3: one specific keyword
                else {
                    if (strpos($card['effect'], '<b>' . $filters['support']) === false) {
                        continue;
                    }
                }
            }

            // date created filter
            if (isset($filters['created']) && $card['created'] != $filters['created']) {
                continue;
            }

            // date modified filter
            if (isset($filters['modified']) && $card['modified'] != $filters['modified']) {
                continue;
            }

            // level filter
            if (isset($filters['level'])) {
                // determine operator
                $operator = (isset($filters['level_op']) && in_array($filters['level_op'], ['=', '<='])) ? $filters['level_op'] : '<=';

                // case 1: equal
                if ($operator == '=') {
                    if ($card['level'] != $filters['level']) {
                        continue;
                    }
                }
                // case 2: default operator (less or equal)
                else {
                    if ($card['level'] > $filters['level']) {
                        continue;
                    }
                }
            }

            // forbidden cards filter
            if (isset($filters['forbidden']) && !$filters['forbidden'] && strpos($card['keywords'], 'Forbidden') !== false) {
                continue;
            }

            $out[] = $cardId;
        }

        return $out;
    }

    /**
     * Calculate number of pages for current card list (specified by filters)
     * @param array $filters filters
     * @return int
     */
    public function countPages(array $filters)
    {
        $result = $this->getList($filters);

        return ceil(count($result) / Card::CARDS_PER_PAGE);
    }

    /**
     * Retrieves data for the specified card ids
     * can be used in combination with Cards::GetList()
     * the same card id may be specified multiple times
     * the result will use the same keys and key order as the input
     * @param array [$ids] an array of card ids to retrieve
     * @param bool [$assert]
     * @throws Exception
     * @return array
     */
    public function getData(array $ids = null, $assert = true)
    {
        // return nothing in case no IDs are specified
        if (!is_null($ids) && count($ids) == 0) {
            return array();
        }

        // return all cards in case IDs are set to special value
        if (is_null($ids)) {
            return $this->getDb();
        }

        // match card data with specified card ids
        $cards = $this->getDb();
        $out = $names = array();
        foreach ($ids as $index => $id) {
            // check if specified card has matching data
            if (!isset($cards[$id])) {
                if (!$assert) {
                    continue;
                }

                throw new Exception('card data not found ' . $id);
            }

            $out[$index] = $cards[$id];
        }

        return $out;
    }

    /**
     * Returns distinct levels that are less or equal to specified level which are present in the card database
     * @param int [$level]
     * @return array
     */
    public function levels($level = -1)
    {
        $filter = ($level >= 0) ? ['level' => $level] : array();

        $result = $this->getData($this->getList($filter));

        $levels = array();
        foreach ($result as $card) {
            $cardLevel = $card['level'];
            $levels[$cardLevel] = $cardLevel;
        }

        sort($levels);

        return $levels;
    }

    /**
     * Returns all distinct keywords
     * @return array
     */
    public function keywords()
    {
        $result = $this->getData($this->getList(['keyword' => 'Any keyword']));

        $keywords = array();
        foreach ($result as $card) {
            $entry = $card['keywords'];

            // split individual keywords
            $words = explode(",", $entry);

            foreach ($words as $word) {
                // remove keyword parameter if present
                $word = preg_split("/ \(/", $word, 0);
                $word = $word[0];

                // remove duplicates
                $keywords[$word] = $word;
            }
        }

        sort($keywords);

        return $keywords;
    }

    /**
     * Returns list of distinct creation dates present in the card database
     * @return array
     */
    public function listCreationDates()
    {
        $result = $this->getData($this->getList());

        $dates = array();
        foreach ($result as $card) {
            $dates[] = $card['created'];
        }

        // remove duplicates
        $dates = array_unique($dates);
        rsort($dates);

        return $dates;
    }

    /**
     * Returns list of distinct modification dates present in the card database
     * @return array
     */
    public function listModifyDates()
    {
        $result = $this->getData($this->getList());

        $dates = array();
        foreach ($result as $card) {
            $dates[] = $card['modified'];
        }

        // remove duplicates
        $dates = array_unique($dates);
        rsort($dates);

        return $dates;
    }
}
