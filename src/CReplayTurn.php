<?php
/**
 * Class CReplayTurn
 * has to be in global namespace, because it's being serialized and stored in DB (backwards compatibility reasons)
 */

class CReplayTurn
{
    /**
     * current player name
     * @var string
     */
    public $Current;

    /**
     * current round
     * @var int
     */
    public $Round;

    /**
     * game turn data
     * @var array
     */
    public $GameData;
}
