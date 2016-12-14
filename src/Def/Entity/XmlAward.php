<?php
/**
 * Award - game awards XML database (contains player achievements)
 */

namespace Def\Entity;

use Def\Util\Result;

class XmlAward extends EntityAbstract
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
        $this->db = new \SimpleXMLElement('xml/awards.xml', 0, true);
        $this->db->registerXPathNamespace('am', 'http://arcomage.net');
    }

    /**
     * List award names
     * @return \Def\Util\Result
     */
    public function awardsNames()
    {
        // TODO this function seems to be used multiple times during one request and it does not cache data
        $db = $this->getDb();
        $result = $db->xpath('/am:awards/am:award');

        if ($result === false) {
            return new Result(Result::ERROR);
        }

        if (count($result) == 0) {
            return new Result(Result::NO_EFFECT);
        }

        $awards = array();
        foreach ($result as $award) {
            $awards[] = (string)$award->attributes()->name;
        }

        return new Result(Result::SUCCESS, $awards);
    }

    /**
     * Load achievement related to specified award and tier
     * @param string $awardName award name
     * @param int $tier tier
     * @return \Def\Util\Result
     */
    public function getAchievement($awardName, $tier)
    {
        $db = $this->getDb();
        $result = $db->xpath('/am:awards/am:award[@name="' . $awardName . '"]/am:achievement[position() = ' . $tier . ']');

        if ($result === false) {
            return new Result(Result::ERROR);
        }

        if (count($result) == 0) {
            return new Result(Result::NO_EFFECT);
        }

        $data = $result[0];
        $achievement = array();

        foreach ($data->attributes() as $attrName => $attrValue) {
            $achievement[$attrName] = (string)$attrValue;
        }

        return new Result(Result::SUCCESS, $achievement);
    }

    /**
     * Load achievements related to specified award
     * @param string $awardName award name
     * @return \Def\Util\Result
     */
    public function getAchievements($awardName)
    {
        $db = $this->getDb();
        $result = $db->xpath('/am:awards/am:award[@name="' . $awardName . '"]');

        if ($result === false) {
            return new Result(Result::ERROR);
        }

        if (count($result) == 0) {
            return new Result(Result::NO_EFFECT);
        }

        $awardData = $result[0];
        $description = $awardData->attributes()->desc;
        $i = 1;
        $achievements = array();

        /* @var $achievement \SimpleXMLElement */
        foreach ($awardData->children() as $achievement) {
            foreach ($achievement->attributes() as $attrName => $attrValue) {
                $achievements[$i][$attrName] = (string)$attrValue;
            }

            // achievement description (replace # in the award description template by the achievement condition)
            $achievements[$i]['desc'] = str_replace('#', $achievements[$i]['condition'], $description);

            // achievement tier (depends on position in the XML file)
            $achievements[$i]['tier'] = $i;

            $i++;
        }

        return new Result(Result::SUCCESS, $achievements);
    }

    /**
     * Returns final achievement data (all tier or specific tier)
     * @param int [$tier]
     * @return mixed
     */
    public static function finalAchievements($tier = 0)
    {
        // final achievement is gained only if player already has all other achievements of the same tier
        $final = [
            // Veteran (final achievement tier 1)
            1 => [
                'name' => 'Veteran',
                'reward' => '1250',
                'desc' => 'gain every tier 1 achievement',
            ],
            // Champion (final achievement tier 2)
            2 => [
                'name' => 'Champion',
                'reward' => '2500',
                'desc' => 'gain every tier 2 achievement',
            ],
            // Grandmaster (final achievement tier 3)
            3 => [
                'name' => 'Grandmaster',
                'reward' => '3750',
                'desc' => 'gain every tier 3 achievement',
            ],
        ];

        return (!empty($final[$tier]) ? $final[$tier] : $final);
    }
}
