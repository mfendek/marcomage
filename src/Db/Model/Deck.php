<?php
/**
 * Deck - the representation of a player's deck
 */

namespace Db\Model;

class Deck extends ModelAbstract
{
    /**
     * Maximum number of decks slots (base number, can be extended by extra decks slots)
     */
    const DECK_SLOTS = 8;

    /**
     * Bonus deck slot cost in gold
     */
    const DECK_SLOT_COST = 300;

    /**
     * Number of decks that are displayed per one page
     */
    const DECKS_PER_PAGE = 30;

    /**
     * Deck import maximum upload size
     */
    const UPLOAD_SIZE = 1 * 1000;

    /**
     * @return int
     */
    public function getDeckId()
    {
        return $this->getFieldValue('DeckID');
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->getFieldValue('Username');
    }

    /**
     * @return string
     */
    public function getDeckName()
    {
        return $this->getFieldValue('Deckname');
    }

    /**
     * @return int
     */
    public function getReady()
    {
        return $this->getFieldValue('Ready');
    }

    /**
     * @return \CDeckData
     */
    public function getData()
    {
        return $this->getFieldValue('Data');
    }

    /**
     * @return string
     */
    public function getNote()
    {
        return $this->getFieldValue('Note');
    }

    /**
     * @return int
     */
    public function getWins()
    {
        return $this->getFieldValue('Wins');
    }

    /**
     * @return int
     */
    public function getLosses()
    {
        return $this->getFieldValue('Losses');
    }

    /**
     * @return int
     */
    public function getDraws()
    {
        return $this->getFieldValue('Draws');
    }

    /**
     * @return int
     */
    public function getShared()
    {
        return $this->getFieldValue('Shared');
    }

    /**
     * @return string
     */
    public function getModified()
    {
        return $this->getFieldValue('Modified');
    }

    /**
     * @param string $username
     * @return $this
     */
    public function setUsername($username)
    {
        return $this->setFieldValue('Username', $username);
    }

    /**
     * @param string $deckName
     * @return $this
     */
    public function setDeckName($deckName)
    {
        return $this->setFieldValue('Deckname', $deckName);
    }

    /**
     * @param int $ready
     * @return $this
     */
    public function setReady($ready)
    {
        return $this->setFieldValue('Ready', $ready);
    }

    /**
     * @param \CDeckData $data
     * @return $this
     */
    public function setData(\CDeckData $data)
    {
        $this->setFieldValue('Data', $data->sanitizeCardData());
        $this->updateIsReady();
        return $this;
    }

    /**
     * @param string $note
     * @return $this
     */
    public function setNote($note)
    {
        return $this->setFieldValue('Note', $note);
    }

    /**
     * @param int $wins
     * @return $this
     */
    public function setWins($wins)
    {
        return $this->setFieldValue('Wins', $wins);
    }

    /**
     * @param int $losses
     * @return $this
     */
    public function setLosses($losses)
    {
        return $this->setFieldValue('Losses', $losses);
    }

    /**
     * @param int $draws
     * @return $this
     */
    public function setDraws($draws)
    {
        return $this->setFieldValue('Draws', $draws);
    }

    /**
     * @param int $shared
     * @return $this
     */
    public function setShared($shared)
    {
        return $this->setFieldValue('Shared', $shared);
    }

    /**
     * @param string $modified
     * @return $this
     */
    public function setModified($modified)
    {
        return $this->setFieldValue('Modified', $modified);
    }

    /**
     * @param array $tokens
     * @return $this
     */
    public function setTokens(array $tokens)
    {
        $data = $this->getData();
        $data->Tokens = $tokens;
        $this->setData($data);
        return $this;
    }

    /**
     * @return bool
     */
    public function isReady()
    {
        return ($this->getReady() == 1);
    }

    /**
     * Update ready value
     * @return $this
     */
    public function updateIsReady()
    {
        $data = $this->getData();
        return $this->setReady(
            (int)($data->countRarity('Common') == 15
                && $data->countRarity('Uncommon') == 15
                && $data->countRarity('Rare') == 15)
        );
    }

    /**
     * Reset deck statistics
     * @return $this
     */
    public function resetStatistics()
    {
        $this->setWins(0);
        $this->setLosses(0);
        $this->setDraws(0);

        return $this;
    }

    /**
     * Removes all cards and resets tokens
     * Zeroes out all three class arrays and sets the token options to 'none'
     * @return $this
     */
    public function resetData()
    {
        $data = $this->getData();
        $defaultData = [
            1 => 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0
        ];
        $data->Common = $defaultData;
        $data->Uncommon = $defaultData;
        $data->Rare = $defaultData;
        $this->setData($data);
        $this->setTokens([1 => 'none', 'none', 'none']);

        return $this;
    }

    /**
     * Format deck data as CSV
     * @return string CSV data
     */
    public function toCSV()
    {
        $data = $this->getData();
        $csv = '';

        $csv.= $this->getDeckName() . "\n";
        $csv.= implode(",", $data->Common) . "\n";
        $csv.= implode(",", $data->Uncommon) . "\n";
        $csv.= implode(",", $data->Rare) . "\n";
        $csv.= implode(",", $data->Tokens) . "\n";

        return $csv;
    }

    /**
     * Add card to deck
     * @param string $rarity card rarity
     * @param int $cardId card id
     * @return int slot number if operation was successful, 0 otherwise
     */
    public function addCard($rarity, $cardId)
    {
        $data = $this->getData();

        // find an empty spot in the section
        $pos = array_search(0, $data->$rarity);
        if ($pos === false) {
            return 0;
        }

        // add card to respective card slot
        $data->editSlot($rarity, $pos, $cardId);
        $rarities = ['Common' => 0, 'Uncommon' => 1, 'Rare' => 2];

        // return slot number that was used to store newly added card (slot range 1 - 45)
        $pos = $pos + 15 * $rarities[$rarity];
        $this->setData($data);

        return $pos;
    }

    /**
     * Return card to card pool
     * @param string $rarity card rarity
     * @param int $cardId card id
     * @return int slot number if operation was successful, 0 otherwise
     */
    public function returnCard($rarity, $cardId)
    {
        $data = $this->getData();

        // check if the card is present in the deck
        $pos = array_search($cardId, $data->$rarity);
        if ($pos === false) {
            return false;
        }

        // remove the card from the deck
        $data->editSlot($rarity, $pos, 0);
        $rarities = ['Common' => 0, 'Uncommon' => 1, 'Rare' => 2];

        // return slot number that became vacant
        $pos = $pos + 15 * $rarities[$rarity];
        $this->setData($data);

        return $pos;
    }
}
