<?php
/**
 * Score - the ranking of a player
 */

namespace Db\Model;

class Score extends ModelAbstract
{
    /**
     * Determine next level exp requirement
     * @param int $level
     * @return int
     */
    private static function nextLevelRequirement($level)
    {
        $nextLevel = 500 + 50 * $level + 200 * floor($level / 5) + 100 * pow(floor($level / 10), 2);

        return $nextLevel;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->getFieldValue('Username');
    }

    /**
     * @return int
     */
    public function getLevel()
    {
        return $this->getFieldValue('Level');
    }

    /**
     * @return int
     */
    public function getExp()
    {
        return $this->getFieldValue('Exp');
    }

    /**
     * @return int
     */
    public function getGold()
    {
        return $this->getFieldValue('Gold');
    }

    /**
     * @return int
     */
    public function getGameSlots()
    {
        return $this->getFieldValue('GameSlots');
    }

    /**
     * Get score data
     * @param string $field field name
     * @return mixed
     */
    public function getData($field)
    {
        return $this->getFieldValue($field);
    }

    /**
     * @param int $level
     * @return $this
     */
    public function setLevel($level)
    {
        return $this->setFieldValue('Level', $level);
    }

    /**
     * @param int $exp
     * @return $this
     */
    public function setExp($exp)
    {
        return $this->setFieldValue('Exp', $exp);
    }

    /**
     * @param int $gold
     * @return $this
     */
    public function setGold($gold)
    {
        return $this->setFieldValue('Gold', $gold);
    }

    /**
     * @param int $gameSlots
     * @return $this
     */
    public function setGameSlots($gameSlots)
    {
        return $this->setFieldValue('GameSlots', $gameSlots);
    }

    /**
     * Set score data
     * @param string $field field name
     * @param mixed $value field value
     * @return $this
     */
    public function setData($field, $value)
    {
        return $this->setFieldValue($field, $value);
    }

    /**
     * Returns next level requirement in experience points
     * @return int next level requirement
     */
    public function nextLevel()
    {
        return self::nextLevelRequirement($this->getLevel());
    }

    /**
     * Calculate experience bar progress
     * @return float exp bar
     */
    public function expBar()
    {
        return $this->getExp() / $this->nextLevel();
    }

    /**
     * Add exp and process level-up if necessary
     * @param int $exp exp point to be added
     * @return bool if level-up occurred, false otherwise
     */
    public function addExp($exp)
    {
        $levelUp = false;
        $nextLevel = self::nextLevelRequirement($this->getLevel());
        $currentExp = $this->getExp() + $exp;

        // level up (gains 100 gold at level-up)
        if ($currentExp >= $nextLevel) {
            $currentExp -= $nextLevel;
            $this->setLevel($this->getLevel() + 1);
            $this->setGold($this->getGold() + 100);
            $levelUp = true;
        }

        $this->setExp($currentExp);

        return $levelUp;
    }

    /**
     * Reset exp and related data
     */
    public function resetExp()
    {
        $this->setExp(0);
        $this->setLevel(0);
        $this->setGold(0);
        $this->setGameSlots(0);
    }
}
