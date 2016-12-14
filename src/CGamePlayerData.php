<?php
use Util\Random;

/**
 * CGamePlayerData - representation of a game player data
 * has to be in global namespace, because it's being serialized and stored in DB (backwards compatibility reasons)
 */

class CGamePlayerData
{
    /**
     * @var CDeckData
     */
    public $Deck;

    /**
     * $i => $cardId
     * @var array
     */
    public $Hand;

    /**
     * list of cards played last turn (in the order they were played)
     * @var array
     */
    public $LastCard;

    /**
     * list of modes corresponding to cards played last turn (each is 0 or 1-8)
     * @var array
     */
    public $LastMode;

    /**
     * list of actions corresponding to cards played last turn ('play'/'discard')
     * @var array
     */
    public $LastAction;

    /**
     * associative array, where keys are card positions which have changed (values are arbitrary at the moment)
     * @var array
     */
    public $NewCards;

    /**
     * associative array, where keys are card positions which are revealed (values are arbitrary at the moment)
     * @var array
     */
    public $Revealed;

    /**
     * associative array, where keys are game attributes (resources, facilities, tower and wall)
     * values are amount of difference
     * @var array
     */
    public $Changes;

    /**
     * array of two lists, one for each player
     * list contains all cards that where discarded during player's turn(s)
     * can be empty
     * @var array
     */
    public $DisCards;

    /**
     * list of token names
     * @var array
     */
    public $TokenNames;

    /**
     * list of token values
     * @var array
     */
    public $TokenValues;

    /**
     * list of token changes
     * @var array
     */
    public $TokenChanges;

    /**
     * @var int
     */
    public $Tower;

    /**
     * @var int
     */
    public $Wall;

    /**
     * @var int
     */
    public $Quarry;

    /**
     * @var int
     */
    public $Magic;

    /**
     * @var int
     */
    public $Dungeons;

    /**
     * @var int
     */
    public $Bricks;

    /**
     * @var int
     */
    public $Gems;

    /**
     * @var int
     */
    public $Recruits;

    /**
     * Determine resource of specified type
     * @param string $type resource type ('lowest'|'highest')
     * @return string
     */
    private function detectResource($type)
    {
        $current = ($type == 'highest')
            ? max($this->Bricks, $this->Gems, $this->Recruits)
            : min($this->Bricks, $this->Gems, $this->Recruits);

        $resources = [
            'Bricks' => $this->Bricks,
            'Gems' => $this->Gems,
            'Recruits' => $this->Recruits
        ];

        $temp = array();
        foreach ($resources as $resource => $resourceValue) {
            if ($resourceValue == $current) {
                $temp[$resource] = $resourceValue;
            }
        }

        return Random::arrayMtRand($temp);
    }

    /**
     * Adds specified amount of resource
     * @param string $type resource type ('lowest'|'highest')
     * @param int $amount amount of resource to be added (can be negative)
     * @return $this
     */
    private function addOneResource($type, $amount)
    {
        if ($amount == 0) {
            return $this;
        }

        $chosen = $this->detectResource($type);
        $this->$chosen+= $amount;

        return $this;
    }

    /**
     * Determine facility of specified type
     * @param string $type facility type ('lowest'|'highest')
     * @return string
     */
    private function detectFacility($type)
    {
        $current = ($type == 'highest')
            ? max($this->Quarry, $this->Magic, $this->Dungeons)
            : min($this->Quarry, $this->Magic, $this->Dungeons);

        $facilities = [
            'Quarry' => $this->Quarry,
            'Magic' => $this->Magic,
            'Dungeons' => $this->Dungeons
        ];

        $temp = array();
        foreach ($facilities as $facility => $facilityValue) {
            if ($facilityValue == $current) {
                $temp[$facility] = $facilityValue;
            }
        }

        return Random::arrayMtRand($temp);
    }

    /**
     * Adds specified amount of facility
     * @param string $type facility type ('lowest'|'highest')
     * @param int $amount amount of facility to be added (can be negative)
     * @return $this
     */
    private function addOneFacility($type, $amount)
    {
        if ($amount == 0) {
            return $this;
        }

        $chosen = $this->detectFacility($type);
        $this->$chosen+= $amount;

        return $this;
    }

    /**
     * @param int $amount (can be negative)
     * @return $this
     */
    public function addTower($amount)
    {
        $this->Tower+= $amount;

        return $this;
    }

    /**
     * @param int $amount
     * @return $this
     */
    public function setTower($amount)
    {
        $this->Tower = $amount;

        return $this;
    }

    /**
     * @param int $amount (can be negative)
     * @return $this
     */
    public function addWall($amount)
    {
        $this->Wall+= $amount;

        return $this;
    }

    /**
     * @param int $amount
     * @return $this
     */
    public function setWall($amount)
    {
        $this->Wall = $amount;

        return $this;
    }

    /**
     * @param int $amount (can be negative)
     * @return $this
     */
    public function addQuarry($amount)
    {
        $this->Quarry+= $amount;

        return $this;
    }

    /**
     * @param int $amount
     * @return $this
     */
    public function setQuarry($amount)
    {
        $this->Quarry = $amount;

        return $this;
    }

    /**
     * @param int $amount (can be negative)
     * @return $this
     */
    public function addMagic($amount)
    {
        $this->Magic+= $amount;

        return $this;
    }

    /**
     * @param int $amount
     * @return $this
     */
    public function setMagic($amount)
    {
        $this->Magic = $amount;

        return $this;
    }

    /**
     * @param int $amount (can be negative)
     * @return $this
     */
    public function addDungeons($amount)
    {
        $this->Dungeons+= $amount;

        return $this;
    }

    /**
     * @param int $amount
     * @return $this
     */
    public function setDungeons($amount)
    {
        $this->Dungeons = $amount;

        return $this;
    }

    /**
     * @param int $amount (can be negative)
     * @return $this
     */
    public function addBricks($amount)
    {
        $this->Bricks+= $amount;

        return $this;
    }

    /**
     * @param int $amount
     * @return $this
     */
    public function setBricks($amount)
    {
        $this->Bricks = $amount;

        return $this;
    }

    /**
     * @param int $amount (can be negative)
     * @return $this
     */
    public function addGems($amount)
    {
        $this->Gems+= $amount;

        return $this;
    }

    /**
     * @param int $amount
     * @return $this
     */
    public function setGems($amount)
    {
        $this->Gems = $amount;

        return $this;
    }

    /**
     * @param int $amount (can be negative)
     * @return $this
     */
    public function addRecruits($amount)
    {
        $this->Recruits+= $amount;

        return $this;
    }

    /**
     * @param int $amount
     * @return $this
     */
    public function setRecruits($amount)
    {
        $this->Recruits = $amount;

        return $this;
    }

    /**
     * Performs an attack - first reducing wall, then tower (may lower both values below 0)
     * @param int $power attack power
     * @return $this
     */
    public function attack($power)
    {
        $damage = $power;

        // first, try to stop the attack with the wall
        if ($this->Wall > 0) {
            $damage-= $this->Wall;
            $this->Wall-= $power;
            if ($this->Wall < 0) {
                $this->Wall = 0;
            }
        }

        // rest of the damage hits the tower
        if ($damage > 0) {
            $this->Tower-= $damage;
        }

        return $this;
    }

    /**
     * Adds specified amount of resources to all resources types
     * @param int $amount amount of resources to be added (can be negative)
     * @return $this
     */
    public function addStock($amount)
    {
        if ($amount == 0) {
            return $this;
        }

        $this->Bricks+= $amount;
        $this->Gems+= $amount;
        $this->Recruits+= $amount;

        return $this;
    }

    /**
     * Sets all resources to specified value
     * @param int $amount resource amount
     * @return $this
     */
    public function setStock($amount)
    {
        // negative values are not valid
        $amount = max(0, $amount);

        $this->Bricks = $amount;
        $this->Gems = $amount;
        $this->Recruits = $amount;

        return $this;
    }

    /**
     * Adds specified amount of random resources
     * @param int $amount amount of resources to be added (can be negative)
     * @return $this
     */
    public function addRandomResources($amount)
    {
        if ($amount == 0) {
            return $this;
        }

        // case 1: resource gain - resource with lowest amount has a higher chance to be chosen
        if ($amount > 0) {
            // extract raw amount
            $amount = abs($amount);

            for ($i = 1; $i <= $amount; $i++) {
                $bricks = max(0, $this->Bricks);
                $gems = max(0, $this->Gems);
                $recruits = max(0, $this->Recruits);

                // resolve resource that have zero amount
                $zero = array();
                if ($bricks == 0) {
                    $zero[]= 'Bricks';
                }

                if ($gems == 0) {
                    $zero[]= 'Gems';
                }

                if ($recruits == 0) {
                    $zero[]= 'Recruits';
                }

                // case 1: there is at least one resource that has zero amount
                if (count($zero) > 0) {
                    $chosen = $zero[Random::arrayMtRand($zero)];
                    $this->$chosen++;
                }
                // case 2: there are no resources with zero amount
                else {
                    // calculate total
                    $total = $bricks + $gems + $recruits;

                    // calculate inverse probability
                    $bricks = ceil(1000 * $total / $bricks);
                    $gems = ceil(1000 * $total / $gems);
                    $recruits = ceil(1000 * $total / $recruits);

                    // calculate new total
                    $total = $bricks + $gems + $recruits;

                    $rand = mt_rand(1, $total);

                    // case 1: Bricks
                    if ($rand <= $bricks) {
                        $this->Bricks++;
                    }
                    // case 2: Gems
                    elseif ($rand <= ($bricks + $gems)) {
                        $this->Gems++;
                    }
                    // case 3: Recruits
                    elseif ($rand <= ($bricks + $gems + $recruits)) {
                        $this->Recruits++;
                    }
                }
            }
        }
        // case 2: resource reduction - resource with highest amount has a higher chance to be chosen
        else {
            // extract raw amount
            $amount = abs($amount);

            for ($i = 1; $i <= $amount; $i++) {
                $bricks = max(0, $this->Bricks);
                $gems = max(0, $this->Gems);
                $recruits = max(0, $this->Recruits);
                $total = $bricks + $gems + $recruits;
                $rand = ($total > 0) ? mt_rand(1, $total) : 0;

                // case 1: Bricks
                if ($rand <= $bricks and $bricks > 0) {
                    $this->Bricks--;
                }
                // case 2: Gems
                elseif ($rand <= ($bricks + $gems) and $gems > 0) {
                    $this->Gems--;
                }
                // case 3: Recruits
                elseif ($rand <= ($bricks + $gems + $recruits) and $recruits > 0) {
                    $this->Recruits--;
                }
            }
        }

        return $this;
    }

    /**
     * Determine highest resource
     * @return string
     */
    public function detectHighestResource()
    {
        return $this->detectResource('highest');
    }

    /**
     * Determine lowest resource
     * @return string
     */
    public function detectLowestResource()
    {
        return $this->detectResource('lowest');
    }

    /**
     * Adds specified amount of highest resource
     * @param int $amount amount of resource to be added (can be negative)
     * @return $this
     */
    public function addHighestResource($amount)
    {
        return $this->addOneResource('highest', $amount);
    }

    /**
     * Adds specified amount of highest resource
     * @param int $amount amount of resource to be added (can be negative)
     * @return $this
     */
    public function addLowestResource($amount)
    {
        return $this->addOneResource('lowest', $amount);
    }

    /**
     * Adds specified amount of facilities to all facility types
     * @param int $amount amount of facilities to be added (can be negative)
     * @return $this
     */
    public function addFacilities($amount)
    {
        if ($amount == 0) {
            return $this;
        }

        $this->Quarry+= $amount;
        $this->Magic+= $amount;
        $this->Dungeons+= $amount;

        return $this;
    }

    /**
     * Determine highest facility
     * @return string
     */
    public function detectHighestFacility()
    {
        return $this->detectFacility('highest');
    }

    /**
     * Determine lowest facility
     * @return string
     */
    public function detectLowestFacility()
    {
        return $this->detectFacility('lowest');
    }

    /**
     * Adds specified amount of highest facility
     * @param int $amount amount of facility to be added (can be negative)
     * @return $this
     */
    public function addHighestFacility($amount)
    {
        return $this->addOneFacility('highest', $amount);
    }

    /**
     * Adds specified amount of highest facility
     * @param int $amount amount of facility to be added (can be negative)
     * @return $this
     */
    public function addLowestFacility($amount)
    {
        return $this->addOneFacility('lowest', $amount);
    }

    /**
     * Sets card to specified position in hand
     * @param int $cardPos card position in hand
     * @param int $cardId card id
     * @param array [$options] card options (reveal, mark as new)
     * @return $this
     */
    public function setCard($cardPos, $cardId, array $options = [])
    {
        // incorrect card position
        if (!in_array($cardPos, [1, 2, 3, 4, 5, 6, 7, 8])) {
            return $this;
        }

        $this->Hand[$cardPos] = $cardId;

        // process mark as new option (enabled by default)
        $markAsNew = (!isset($options['new']) || $options['new']);
        if ($markAsNew) {
            $this->NewCards[$cardPos] = 1;
        }

        // process reveal option (disabled by default)
        $reveal = (isset($options['reveal']) && $options['reveal']);

        // case 1: reveal current position
        if ($reveal) {
            $this->Revealed[$cardPos] = 1;
        }
        // case 2: hide current position if card was revealed
        elseif (isset($this->Revealed[$cardPos])) {
            unset($this->Revealed[$cardPos]);
        }

        return $this;
    }

    /**
     * Shuffle card positions, but keep new card flags unchanged, while resetting revealed flags
     * @return $this
     */
    public function shuffleHand()
    {
        // create index translation
        $trans = array_keys($this->Hand);
        shuffle($trans);
        $trans = array_combine(array_keys($this->Hand), $trans);

        // create new hand
        $newHand = array();
        $newFlags = array();
        for ($i = 1; $i <= 8; $i++) {
            $pos = $trans[$i];

            // transform card position
            $newHand[$i] = $this->Hand[$pos];

            // transform new card flag
            if (isset($this->NewCards[$pos])) {
                $newFlags[$i] = 1;
            }

            // hide current position if card was revealed
            if (isset($this->Revealed[$i])) {
                unset($this->Revealed[$i]);
            }
        }

        // store new data
        $this->Hand = $newHand;
        if (count($newFlags) > 0) {
            $this->NewCards = $newFlags;
        }

        return $this;
    }

    /**
     * Switch positions of specified cards in hand
     * @param int $pos1 card position 1
     * @param int $pos2 card position 2
     * @return $this
     */
    public function switchCards($pos1, $pos2)
    {
        // incorrect card position
        if (!in_array($pos1, [1, 2, 3, 4, 5, 6, 7, 8]) || !in_array($pos2, [1, 2, 3, 4, 5, 6, 7, 8])) {
            return $this;
        }

        // invalid card position
        if ($pos1 == $pos2) {
            return $this;
        }

        $cardId1 = $this->Hand[$pos1];
        $cardId2 = $this->Hand[$pos2];
        $new1 = isset($this->NewCards[$pos1]);
        $new2 = isset($this->NewCards[$pos2]);

        // switch cards
        $this->Hand[$pos1] = $cardId2;
        $this->Hand[$pos2] = $cardId1;

        // switch new card flags
        if ($new1) {
            $this->NewCards[$pos2] = 1;
        }
        elseif (isset($this->NewCards[$pos2])) {
            unset($this->NewCards[$pos2]);
        }

        if ($new2) {
            $this->NewCards[$pos1] = 1;
        }
        elseif (isset($this->NewCards[$pos1])) {
            unset($this->NewCards[$pos1]);
        }

        // hide revealed positions
        if (isset($this->Revealed[$pos1])) {
            unset($this->Revealed[$pos1]);
        }

        if (isset($this->Revealed[$pos2])) {
            unset($this->Revealed[$pos2]);
        }

        return $this;
    }

    /**
     * Find specified token index
     * @param string $name token name
     * @return int
     */
    public function findToken($name)
    {
        $tokenIndex = array_search($name, $this->TokenNames);
        if ($tokenIndex) {
            return $tokenIndex;
        }

        return 0;
    }

    /**
     * Get specified token amount
     * @param string $name token name
     * @return bool|int
     */
    public function getToken($name)
    {
        $tokenIndex = $this->findToken($name);
        if ($tokenIndex) {
            return $this->TokenValues[$tokenIndex];
        }

        return false;
    }

    /**
     * Set token to specified value
     * @param string $name token name
     * @param int $amount new token amount
     * @return $this
     */
    public function setToken($name, $amount)
    {
        // amount has to be non-negative
        if ($amount < 0) {
            return $this;
        }

        $tokenIndex = $this->findToken($name);
        if ($tokenIndex) {
            $this->TokenValues[$tokenIndex] = $amount;
        }

        return $this;
    }

    /**
     * Add specified value to token
     * @param string $name token name
     * @param int $amount token amount (can be negative)
     * @return $this
     */
    public function addToken($name, $amount)
    {
        $tokenIndex = $this->findToken($name);
        if ($tokenIndex) {
            $this->TokenValues[$tokenIndex]+= $amount;
        }

        return $this;
    }

    /**
     * Apply game limits to game attributes
     * @param string $gameMode game mode
     * @return $this
     */
    public function applyGameLimits($gameMode)
    {
        $gameConfig = \Db\Model\Game::gameConfig();

        $this->Quarry = max($this->Quarry, 1);
        $this->Magic = max($this->Magic, 1);
        $this->Dungeons = max($this->Dungeons, 1);
        $this->Bricks = max($this->Bricks, 0);
        $this->Gems = max($this->Gems, 0);
        $this->Recruits = max($this->Recruits, 0);
        $this->Tower = min(max($this->Tower, 0), $gameConfig[$gameMode]['max_tower']);
        $this->Wall = min(max($this->Wall, 0), $gameConfig[$gameMode]['max_wall']);

        foreach ($this->TokenValues as $index => $token_val) {
            $this->TokenValues[$index] = max(min($this->TokenValues[$index], 100), 0);
        }

        return $this;
    }
}
