<?php
/**
 * Keyword - the representation of keywords database
 */

namespace Def\Entity;

use ArcomageException as Exception;
use Def\Model\Keyword;
use Def\Util\Result;

class XmlKeyword extends EntityAbstract
{
    /**
     * @return \SimpleXMLElement
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
        $this->db = new \SimpleXMLElement('xml/keywords.xml', 0, true);
        $this->db->registerXPathNamespace('am', 'http://arcomage.net');
    }

    /**
     * @return \Def\Util\Result
     */
    public function listKeywords()
    {
        $db = $this->getDb();
        $result = $db->xpath('/am:keywords/am:keyword');

        if ($result === false) {
            return new Result(Result::ERROR);
        }

        if (count($result) == 0) {
            return new Result(Result::NO_EFFECT);
        }

        $keywords = array();
        foreach ($result as $keyword) {
            $keywords[] = [
                'name' => (string)$keyword->name,
                'basic_gain' => (int)$keyword->basic_gain,
                'bonus_gain' => (int)$keyword->bonus_gain,
                'description' => (string)$keyword->description,
                'lore' => (string)$keyword->lore,
                'code' => (string)$keyword->code,
            ];
        }

        return new Result(Result::SUCCESS, $keywords);
    }

    /**
     * @param string $name keyword name
     * @return Keyword
     * @throws Exception
     */
    public function getKeyword($name)
    {
        $db = $this->getDb();
        $result = $db->xpath('/am:keywords/am:keyword[am:name="' . $name . '"]');

        if ($result === false || count($result) == 0) {
            throw new Exception('failed to read keywords DB');
        }

        $data = $result[0];
        return new Keyword([
            'name' => (string)$data->name,
            'basic_gain' => (int)$data->basic_gain,
            'bonus_gain' => (int)$data->bonus_gain,
            'description' => (string)$data->description,
            'code' => (string)$data->code,
        ]);
    }

    /**
     * Returns list of token keywords
     * @return array
     */
    public static function tokenKeywords()
    {
        return [
            'Alliance',
            'Barbarian',
            'Brigand',
            'Beast',
            'Burning',
            'Holy',
            'Mage',
            'Soldier',
            'Titan',
            'Undead',
            'Unliving'
        ];
    }

    /**
     * Returns list of keywords in execution order
     * @return array
     */
    public static function keywordsOrder()
    {
        return [
            'Alliance',
            'Aqua',
            'Barbarian',
            'Beast',
            'Brigand',
            'Burning',
            'Demonic',
            'Destruction',
            'Dragon',
            'Holy',
            'Illusion',
            'Legend',
            'Mage',
            'Nature',
            'Restoration',
            'Runic',
            'Soldier',
            'Titan',
            'Undead',
            'Unliving',
            'Durable',
            'Quick',
            'Swift',
            'Far sight',
            'Banish',
            'Skirmisher',
            'Horde',
            'Rebirth',
            'Flare blitz',
            'Frenzy',
            'Aria',
            'Enduring',
            'Charge',
            'Siege',
            'Cursed',
            'Forbidden',
        ];
    }
}
