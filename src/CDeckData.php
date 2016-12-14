<?php
/**
 * CDeckData - the representation of a player's deck data
 * has to be in global namespace, because it's being serialized and stored in DB (backwards compatibility reasons)
 */

class CDeckData
{
    /**
     * common card slots
     * @var array
     */
    public $Common;

    /**
     * uncommon card slots
     * @var array
     */
    public $Uncommon;

    /**
     * rare card slots
     * @var array
     */
    public $Rare;

    /**
     * token data
     * @var array
     */
    public $Tokens;

    /**
     * CDeckData constructor.
     */
    public function __construct()
    {
        $emptySlots = [
            1 => 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0
        ];
        $this->Common = $emptySlots;
        $this->Uncommon = $emptySlots;
        $this->Rare = $emptySlots;
        $this->Tokens = [
            1 => 'none', 'none', 'none'
        ];
    }

    /**
     * Edit value at specified slot
     * @param string $rarity
     * @param int $pos position
     * @param int $value
     */
    public function editSlot($rarity, $pos, $value)
    {
        $data = $this->$rarity;
        $data[$pos] = (int)$value;
        $this->$rarity = $data;
    }

    /**
     * Returns one card at type-random
     * @return int
     */
    public function drawCardRandom()
    {
        $i = mt_rand(1, 100);

        // common
        if ($i <= 65) {
            return $this->Common[mt_rand(1, 15)];
        }
        // uncommon
        elseif ($i <= 65 + 29) {
            return $this->Uncommon[mt_rand(1, 15)];
        }
        // rare
//        elseif ($i <= 65 + 29 + 6) {
        else {
            return $this->Rare[mt_rand(1, 15)];
        }
    }

    /**
     * Returns one card at type-random different from the specified card
     * @param int $cardId
     * @return int
     */
    public function drawCardDifferent($cardId)
    {
        // draw cards until you pick a different card
        do {
            $card = $this->drawCardRandom();
        }
        while ($card == $cardId);

        return $card;
    }

    /**
     * Returns one card at type-random - no rare
     * @return int
     */
    public function drawCardNoRare()
    {
        $i = mt_rand(1, 94);

        // common
        if ($i <= 65) {
            return $this->Common[mt_rand(1, 15)];
        }
        // uncommon
        else {
            return $this->Uncommon[mt_rand(1, 15)];
        }
    }

    /**
     * Count number of cards for specified rarity
     * @param string $rarity card rarity
     * @return int
     */
    public function countRarity($rarity)
    {
        // validate card rarity
        if (!in_array($rarity, ['Common', 'Uncommon', 'Rare'])) {
            return 0;
        }

        $amount = 0;
        foreach ($this->$rarity as $val) {
            if ($val != 0) {
                $amount++;
            }
        }

        return $amount;
    }

    /**
     * @return $this
     */
    public function sanitizeCardData()
    {
        foreach (['Common', 'Uncommon', 'Rare'] as $rarity) {
            $sanitized = array();
            foreach ($this->$rarity as $key => $value) {
                // make sure values are integers
                $sanitized[$key] = (int)$value;
            }
            $this->$rarity = $sanitized;
        }

        return $this;
    }
}
