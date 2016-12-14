<?php
/**
 * Class GameProduction - representation of a game production factor
 * has to be in global namespace, because of backwards compatibility (release branch does not have namespaces)
 */

class GameProduction
{
    /**
     * @var int
     */
    protected $bricks;

    /**
     * @var int
     */
    protected $gems;

    /**
     * @var int
     */
    protected $recruits;

    /**
     * GameProduction constructor.
     */
    public function __construct()
    {
        // default production factor
        $this->bricks = 1;
        $this->gems = 1;
        $this->recruits = 1;
    }

    /**
     * @return int
     */
    public function bricks()
    {
        return $this->bricks;
    }

    /**
     * @return int
     */
    public function gems()
    {
        return $this->gems;
    }

    /**
     * @return int
     */
    public function recruits()
    {
        return $this->recruits;
    }

    /**
     * Multiply bricks production
     * @param int $factor
     * @return $this
     */
    public function multiplyBricks($factor)
    {
        return $this->multiply($factor, 'Bricks');
    }

    /**
     * Multiply gems production
     * @param int $factor
     * @return $this
     */
    public function multiplyGems($factor)
    {
        return $this->multiply($factor, 'Gems');
    }

    /**
     * Multiply recruits production
     * @param int $factor
     * @return $this
     */
    public function multiplyRecruits($factor)
    {
        return $this->multiply($factor, 'Recruits');
    }

    /**
     * Multiply production
     * @param int $factor
     * @param string [$type] production type (supports names by both resources and facilities)
     * @return $this
     */
    public function multiply($factor, $type = '')
    {
        // only non-negative factor is allowed
        if ($factor < 0) {
            return $this;
        }

        // case 1: bricks
        if (in_array($type, ['Bricks', 'Quarry'])) {
            $this->bricks*= $factor;
        }
        // case 2: gems
        elseif (in_array($type, ['Gems', 'Magic'])) {
            $this->gems*= $factor;
        }
        // case 3: recruits
        elseif (in_array($type, ['Recruits', 'Dungeons'])) {
            $this->recruits*= $factor;
        }
        // case 4: all
        else {
            $this->bricks*= $factor;
            $this->gems*= $factor;
            $this->recruits*= $factor;
        }

        return $this;
    }
}
