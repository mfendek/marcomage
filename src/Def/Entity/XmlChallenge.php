<?php
/**
 * Challenge - AI challenges configuration database
 */

namespace Def\Entity;

use ArcomageException as Exception;
use Def\Model\Challenge;
use Def\Util\Result;

class XmlChallenge extends EntityAbstract
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
        $this->db = new \SimpleXMLElement('xml/challenges.xml', 0, true);
        $this->db->registerXPathNamespace('am', 'http://arcomage.net');
    }

    /**
     * Get list of available AI challenges
     * @return \Def\Util\Result
     */
    public function listChallenges()
    {
        $db = $this->getDb();
        $result = $db->xpath('/am:challenges/am:challenge');

        if ($result === false) {
            return new Result(Result::ERROR);
        }

        if (count($result) == 0) {
            return new Result(Result::NO_EFFECT);
        }

        $challenges = array();
        foreach ($result as $challenge) {
            $currentChallenge['name'] = (string)$challenge->attributes()->name;
            $currentChallenge['fullname'] = (string)$challenge->fullname;
            $currentChallenge['description'] = (string)$challenge->description;
            $challenges[] = $currentChallenge;
        }

        return new Result(Result::SUCCESS, $challenges);
    }

    /**
     * Get list of AI challenges names
     * @return \Def\Util\Result
     */
    public function listChallengeNames()
    {
        $db = $this->getDb();
        $result = $db->xpath('/am:challenges/am:challenge');

        if ($result === false) {
            return new Result(Result::ERROR);
        }

        if (count($result) == 0) {
            return new Result(Result::NO_EFFECT);
        }

        $challenges = array();
        foreach ($result as $challenge) {
            $challenges[] = (string)$challenge->attributes()->name;
        }

        return new Result(Result::SUCCESS, $challenges);
    }

    /**
     * @param $challengeName
     * @throws Exception
     * @return Challenge
     */
    public function getChallenge($challengeName)
    {
        $db = $this->getDb();
        $result = $db->xpath('/am:challenges/am:challenge[@name="' . $challengeName . '"]');

        if ($result === false || count($result) == 0) {
            throw new Exception('failed to load challenge data ' . $challengeName);
        }

        $data = $result[0];

        $init = $config = array();
        foreach (['mine', 'his'] as $player) {
            foreach (['Quarry', 'Magic', 'Dungeons', 'Bricks', 'Gems', 'Recruits', 'Tower', 'Wall'] as $attr) {
                $attribute = strtolower($attr);
                $init[$player][$attr] = (int)$data->initialization->$player->$attribute;
                $config[$player][$attr] = (int)$data->config->$player->$attribute;
            }
        }

        return new Challenge($challengeName, $init, $config);
    }
}
