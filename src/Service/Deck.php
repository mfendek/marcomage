<?php
/**
 * Deck
 */

namespace Service;

use ArcomageException as Exception;
use Db\Model\Message as MessageModel;
use Db\Model\Player as PlayerModel;
use Db\Model\Deck as DeckModel;
use Def\Entity\XmlKeyword;
use Util\Date;

class Deck extends ServiceAbstract
{
    /**
     * Returns list of started decks indexed by deck name
     * @return array decks
     */
    public function starterDecks()
    {
        $dbEntityDeck = $this->dbEntity()->deck();
        $starterDecks = $starterData = array();

        // Offense deck
        $deckData = new \CDeckData();
        $deckData->Common = [
            1 => 54, 240, 71, 256, 250, 259, 261, 113, 247, 79, 57, 140, 7, 236, 387
        ];
        $deckData->Uncommon = [
            1 => 28, 189, 83, 10, 204, 211, 230, 36, 216, 201, 53, 96, 146, 164, 208
        ];
        $deckData->Rare = [
            1 => 32, 197, 75, 74, 151, 61, 69, 66, 232, 506, 291, 21, 126, 542, 181
        ];
        $starterData['Offense'] = $deckData;

        // Defense deck
        $deckData = new \CDeckData();
        $deckData->Common = [
            1 => 1, 289, 23, 149, 359, 18, 260, 119, 26, 275, 271, 176, 60, 122, 272
        ];
        $deckData->Uncommon = [
            1 => 146, 163, 162, 164, 175, 266, 5, 154, 49, 136, 109, 35, 174, 270, 89
        ];
        $deckData->Rare = [
            1 => 235, 21, 124, 663, 161, 192, 4, 167, 233, 156, 67, 70, 169, 141, 148
        ];
        $starterData['Defense'] = $deckData;

        // Sabotage deck
        $deckData = new \CDeckData();
        $deckData->Common = [
            1 => 454, 45, 1, 260, 79, 238, 140, 46, 274, 269, 160, 362, 26, 300, 91
        ];
        $deckData->Uncommon = [
            1 => 29, 267, 84, 19, 47, 191, 320, 123, 98, 3, 8, 58, 109, 96, 52
        ];
        $deckData->Rare = [
            1 => 115, 108, 127, 86, 110, 138, 181, 242, 121, 249, 4, 277, 293, 199, 128
        ];
        $starterData['Sabotage'] = $deckData;

        foreach ($starterData as $deckName => $deckData) {
            $deck = $dbEntityDeck->createCustomDeck([
                'username' => PlayerModel::SYSTEM_NAME,
                'deck_name' => $deckName,
            ]);
            $deck->setData($deckData);

            $starterDecks[$deckName] = $deck;
        }

        return $starterDecks;
    }

    /**
     * Returns list of challenge decks indexed by deck name
     * @return array decks
     */
    public function challengeDecks()
    {
        $dbEntityDeck = $this->dbEntity()->deck();
        $challengeDecks = $challengeData = array();

        // Grofgul's deck
        $deckData = new \CDeckData();
        $deckData->Common = [
            1 => 71, 619, 71, 586, 584, 60, 258, 619, 608, 237, 188, 60, 624, 556, 119
        ];
        $deckData->Uncommon = [
            1 => 617, 405, 375, 123, 154, 388, 618, 146, 432, 388, 118, 617, 174, 617, 388
        ];
        $deckData->Rare = [
            1 => 407, 377, 407, 377, 546, 546, 377, 389, 389, 199, 389, 407, 199, 199, 546
        ];
        $deckData->Tokens[1] = 'Alliance';
        $challengeData['Grofgul'] = $deckData;

        // Marquis' deck
        $deckData = new \CDeckData();
        $deckData->Common = [
            1 => 608, 445, 481, 484, 538, 402, 570, 114, 112, 578, 578, 578, 445, 484, 538
        ];
        $deckData->Uncommon = [
            1 => 543, 576, 120, 133, 230, 186, 321, 296, 28, 446, 543, 576, 543, 576, 120
        ];
        $deckData->Rare = [
            1 => 612, 612, 612, 612, 612, 612, 612, 612, 612, 612, 612, 612, 612, 612, 612
        ];
        $deckData->Tokens[1] = 'Undead';
        $challengeData['Marquis'] = $deckData;

        // Demetrios' deck
        $deckData = new \CDeckData();
        $deckData->Common = [
            1 => 387, 301, 79, 91, 570, 559, 521, 524, 149, 575, 289, 501, 606, 559, 454
        ];
        $deckData->Uncommon = [
            1 => 14, 3, 84, 37, 29, 497, 162, 175, 560, 35, 523, 505, 525, 525, 523
        ];
        $deckData->Rare = [
            1 => 161, 229, 285, 222, 530, 285, 530, 161, 229, 285, 222, 530, 222, 530, 161
        ];
        $challengeData['Demetrios'] = $deckData;

        // Sophie's deck
        $deckData = new \CDeckData();
        $deckData->Common = [
            1 => 417, 18, 278, 275, 395, 490, 537, 582, 586, 587, 582, 587, 537, 537, 278
        ];
        $deckData->Uncommon = [
            1 => 231, 155, 5, 200, 492, 87, 137, 386, 72, 72, 155, 492, 5, 231, 137
        ];
        $deckData->Rare = [
            1 => 295, 499, 143, 517, 64, 401, 67, 294, 148, 21, 209, 231, 143, 517, 499
        ];
        $challengeData['Sophie'] = $deckData;

        // Myr's deck
        $deckData = new \CDeckData();
        $deckData->Common = [
            1 => 589, 589, 589, 82, 82, 82, 357, 357, 153, 153, 153, 597, 597, 608, 586
        ];
        $deckData->Uncommon = [
            1 => 172, 172, 109, 109, 53, 53, 53, 425, 425, 564, 338, 338, 11, 174, 155
        ];
        $deckData->Rare = [
            1 => 467, 467, 467, 121, 121, 121, 9, 9, 499, 596, 577, 577, 435, 435, 173
        ];
        $deckData->Tokens[1] = 'Mage';
        $challengeData['Myr'] = $deckData;

        // Gilgamesh's deck
        $deckData = new \CDeckData();
        $deckData->Common = [
            1 => 664, 642, 69, 32, 75, 66, 644, 74, 401, 456, 192, 643, 21, 663, 339
        ];
        $deckData->Uncommon = [
            1 => 435, 277, 70, 616, 506, 450, 614, 181, 427, 622, 61, 110, 181, 232, 464
        ];
        $deckData->Rare = [
            1 => 654, 125, 235, 426, 615, 138, 637, 239, 4, 611, 234, 233, 428, 9, 141
        ];
        $challengeData['Gilgamesh'] = $deckData;

        // Duroth's deck
        $deckData = new \CDeckData();
        $deckData->Common = [
            1 => 250, 250, 250, 250, 440, 440, 645, 645, 645, 645, 628, 628, 628, 628, 628
        ];
        $deckData->Uncommon = [
            1 => 195, 195, 195, 701, 701, 701, 679, 679, 679, 622, 622, 622, 670, 670, 131
        ];
        $deckData->Rare = [
            1 => 615, 637, 127, 86, 167, 61, 239, 126, 665, 128, 506, 428, 577, 6, 92
        ];
        $deckData->Tokens[1] = 'Alliance';
        $challengeData['Duroth'] = $deckData;

        foreach ($challengeData as $deckName => $deckData) {
            $deck = $dbEntityDeck->createCustomDeck([
                'username' => PlayerModel::SYSTEM_NAME,
                'deck_name' => $deckName,
            ]);
            $deck->setData($deckData);

            $challengeDecks[$deckName] = $deck;
        }

        return $challengeDecks;
    }

    /**
     * Find and set token keywords most present in the deck
     * @param DeckModel $deck
     */
    public function setAutoTokens(DeckModel $deck)
    {
        $defEntityCard = $this->defEntity()->card();

        $data = $deck->getData();
        $tokens = count($data->Tokens);

        // initialize token keyword counter array
        $tokenKeywords = XmlKeyword::tokenKeywords();
        $tokenValues = array_fill(0, count($tokenKeywords), 0);
        $distinctKeywords = array_combine($tokenKeywords, $tokenValues);

        // count token keywords
        foreach (['Common', 'Uncommon', 'Rare'] as $rarity) {
            foreach ($data->$rarity as $cardId) {
                if ($cardId > 0) {
                    $currentCard = $defEntityCard->getCard($cardId);

                    $keywords = $currentCard->getData('Keywords');
                    $words = explode(",", $keywords);

                    foreach ($words as $word) {
                        // remove parameter if present
                        $word = preg_split("/ \(/", $word, 0);
                        $word = $word[0];

                        if (in_array($word, $tokenKeywords)) {
                            $distinctKeywords[$word]++;
                        }
                    }
                }
            }
        }

        // get most present token keywords
        arsort($distinctKeywords);

        // remove keywords with zero presence
        $distinctKeywords = array_diff($distinctKeywords, [0]);
        $newTokens = array_keys(array_slice($distinctKeywords, 0, $tokens));

        // add empty tokens when there are not enough token keywords
        if (count($newTokens) < $tokens) {
            $newTokens = array_pad($newTokens, $tokens, 'none');
        }

        // adjust array keys
        $newTokens = array_combine(array_keys(array_fill(1, count($newTokens), 0)), $newTokens);
        $deck->setTokens($newTokens);
    }

    /**
     * Calculate average cost per turn
     * @param DeckModel $deck
     * @return array
     */
    public function avgCostPerTurn(DeckModel $deck)
    {
        $defEntityCard = $this->defEntity()->card();

        $data = $deck->getData();

        // define a data structure for our needs
        $subArray = ['Common' => 0, 'Uncommon' => 0, 'Rare' => 0];

        $sum = [
            'bricks' => $subArray,
            'gems' => $subArray,
            'recruits' => $subArray,
            'count' => $subArray
        ];
        $avg = [
            'bricks' => $subArray,
            'gems' => $subArray,
            'recruits' => $subArray
        ];
        $cost = ['bricks' => 0, 'gems' => 0, 'recruits' => 0];

        // load card data into the structure
        foreach ($subArray as $rarity => $value) {
            foreach ($data->$rarity as $index => $cardId) {
                if ($cardId != 0) {
                    $card = $defEntityCard->getCard($cardId);

                    $sum['bricks'][$rarity]+= $card->getData('Bricks');
                    $sum['gems'][$rarity]+= $card->getData('Gems');
                    $sum['recruits'][$rarity]+= $card->getData('Recruits');
                    $sum['count'][$rarity]+= 1;
                }
            }
        }

        // calculate average cost per resource type
        foreach ($avg as $type => $value) {
            // common cards
            $common = ($sum['count']['Common'] > 0) ? ($sum[$type]['Common'] * 0.65) / $sum['count']['Common'] : 0;

            // uncommon cards
            $uncommon = ($sum['count']['Uncommon'] > 0) ? ($sum[$type]['Uncommon'] * 0.29) / $sum['count']['Uncommon'] : 0;

            // rare cards
            $rare = ($sum['count']['Rare'] > 0) ? ($sum[$type]['Rare'] * 0.06) / $sum['count']['Rare'] : 0;

            $cost[$type] = round($common + $uncommon + $rare, 2);
        }

        return $cost;
    }

    /**
     * Update deck statistics
     * @param string $player1 player 1 name
     * @param string $player2 player 2 name
     * @param int $deckId1 deck id 1
     * @param int $deckId2 deck id 2
     * @param string $winner game winner
     * @throws Exception
     */
    public function updateDeckStatistics($player1, $player2, $deckId1, $deckId2, $winner)
    {
        // update player 1 deck statistics
        if ($deckId1 > 0) {
            $deck1 = $this->dbEntity()->deck()->getDeck($deckId1);

            if (!empty($deck1) && $deck1->getUsername() == $player1) {
                // case 1: player 1 won
                if ($winner == $player1) {
                    $deck1->setWins($deck1->getWins() + 1);
                }
                // case 2: player 2 won
                elseif ($winner == $player2) {
                    $deck1->setLosses($deck1->getLosses() + 1);
                }
                // case 3: draw
                else {
                    $deck1->setDraws($deck1->getDraws() + 1);
                }

                // save deck data
                if (!$deck1->save()) {
                    throw new Exception('Failed to save deck 1 statistics');
                }
            }
        }

        // update player 2 deck statistics
        if ($deckId2 > 0 && $player2 != PlayerModel::SYSTEM_NAME) {
            $deck2 = $this->dbEntity()->deck()->getDeck($deckId2);

            if (!empty($deck2) && $deck2->getUsername() == $player2) {
                // case 1: player 2 won
                if ($winner == $player2) {
                    $deck2->setWins($deck2->getWins() + 1);
                }
                // case 2: player 1 won
                elseif ($winner == $player1) {
                    $deck2->setLosses($deck2->getLosses() + 1);
                }
                // case 3: draw
                else {
                    $deck2->setDraws($deck2->getDraws() + 1);
                }
                // save deck data
                if (!$deck2->save()) {
                    throw new Exception('Failed to save deck 2 statistics');
                }
            }
        }
    }

    /**
     * Add card to deck
     * @param DeckModel $deck
     * @param int $cardId
     * @throws Exception
     * @return int
     */
    public function addCard(DeckModel $deck, $cardId)
    {
        $defEntityCard = $this->defEntity()->card();

        // validate card id
        if (!is_numeric($cardId) || $cardId <= 0) {
            throw new Exception('Invalid card id', Exception::WARNING);
        }

        // retrieve the card's data
        $card = $defEntityCard->getCard($cardId);
        $rarity = $card->getRarity();

        // check if the card isn't already in the deck
        $pos = array_search($cardId, $deck->getData()->$rarity);
        if ($pos !== false) {
            throw new Exception('Card is already present in the deck', Exception::WARNING);
        }

        // check if the deck's corresponding section isn't already full
        if ($deck->getData()->countRarity($rarity) == 15) {
            throw new Exception('Deck section is already full', Exception::WARNING);
        }

        // check for forbidden card
        if ($card->hasKeyword('Forbidden')) {
            throw new Exception('Unable to add card with Forbidden keyword to deck', Exception::WARNING);
        }

        // add card to deck data
        $slot = $deck->addCard($rarity, $cardId);
        if (!$slot) {
            throw new Exception('Failed to add card to deck data');
        }

        // set tokens automatically when deck is finished and player forgot to set them
        if (count(array_diff($deck->getData()->Tokens, ['none'])) == 0 && $deck->isReady()) {
            $this->setAutoTokens($deck);
        }

        $deck->setModifiedAt(Date::timeToStr());
        if (!$deck->save()) {
            throw new Exception('Unable to add the chosen card to this deck');
        }

        return $slot;
    }

    /**
     * Remove card from deck
     * @param DeckModel $deck
     * @param int $cardId
     * @throws Exception
     * @return int
     */
    public function removeCard(DeckModel $deck, $cardId)
    {
        $defEntityCard = $this->defEntity()->card();

        // validate card id
        if (!is_numeric($cardId) || $cardId <= 0) {
            throw new Exception('Invalid card id', Exception::WARNING);
        }

        // retrieve the card's data
        $card = $defEntityCard->getCard($cardId);
        $rarity = $card->getRarity();

        // remove card from deck
        $slot = $deck->returnCard($rarity, $cardId);
        if (!$slot) {
            throw new Exception('Failed to remove card from deck data');
        }

        $deck->setModifiedAt(Date::timeToStr());
        if (!$deck->save()) {
            throw new Exception('Unable to remove the chosen card from this deck');
        }

        return $slot;
    }

    /**
     * Save deck note
     * @param DeckModel $deck
     * @param string $note
     * @throws Exception
     */
    public function saveNote(DeckModel $deck, $note)
    {
        // validate note length
        if (mb_strlen($note) > MessageModel::MESSAGE_LENGTH) {
            throw new Exception('Deck note is too long', Exception::WARNING);
        }

        // update deck note
        $deck
            ->setNote($note)
            ->setModifiedAt(Date::timeToStr());

        if (!$deck->save()) {
            throw new Exception('Failed to save deck note');
        }
    }

    /**
     * @param mixed $deckId
     * @return bool
     */
    public function isChallengeDeck($deckId)
    {
        $challengeNames = array_keys($this->challengeDecks());

        return in_array($deckId, $challengeNames);
    }

    /**
     * @param $deckId
     * @param $playerName
     * @param array $gameModes
     * @return DeckModel
     * @throws Exception
     */
    public function loadReadyDeck($deckId, $playerName, array $gameModes)
    {
        // case 1: AI challenge deck was selected
        if ($this->service()->deck()->isChallengeDeck($deckId)) {
            // validate player's level
            $score = $this->dbEntity()->score()->getScoreAsserted($playerName);
            $level = $score->getLevel();
            if ($level < PlayerModel::TUTORIAL_END) {
                throw new Exception('Usage of AI decks is only permitted after end of tutorial', Exception::WARNING);
            }

            // validate game mode
            if (!in_array('FriendlyPlay', $gameModes)) {
                throw new Exception('Usage of AI decks is only permitted in friendly play game mode', Exception::WARNING);
            }

            $challengeDecks = $this->service()->deck()->challengeDecks();
            $deck = $challengeDecks[$deckId];
        }
        // case 2: standard deck was selected
        else {
            // load deck
            $deck = $this->dbEntity()->deck()->getDeckAsserted($deckId);

            // validate deck ownership
            if ($deck->getUsername() != $playerName) {
                throw new Exception('Can only use own deck', Exception::WARNING);
            }

            // validate data
            $this->validateCards($playerName, [
                'Common' => $deck->getData()->Common,
                'Uncommon' => $deck->getData()->Uncommon,
                'Rare' => $deck->getData()->Rare,
            ]);
        }

        // check if the deck is ready (all 45 cards)
        if (!$deck->isReady()) {
            throw new Exception('Deck is not yet ready for gameplay!', Exception::WARNING);
        }

        return $deck;
    }

    /**
     * @param string $playerName
     * @param array $cards
     * @throws Exception
     */
    public function validateCards($playerName, array $cards)
    {
        $defEntityCard = $this->defEntity()->card();

        // fetch player's level
        $score = $this->dbEntity()->score()->getScoreAsserted($playerName);
        $level = $score->getLevel();

        // check deck cards
        foreach ($cards as $rarity => $cardIds) {
            if (count($cardIds) != 15) {
                throw new Exception($rarity . ' cards data is corrupted', Exception::WARNING);
            }

            // remove empty slots
            $cards = array_diff($cardIds, [0]);

            // check for duplicates
            if (count($cards) != count(array_unique($cards))) {
                throw new Exception($rarity . ' cards data contains duplicates', Exception::WARNING);
            }

            // check ids
            $allCards = $defEntityCard->getList([
                'rarity' => $rarity, 'level' => $level, 'level_op' => '<=', 'forbidden' => false
            ]);

            if (count(array_diff($cards, $allCards)) > 0) {
                throw new Exception($rarity . ' cards data contains forbidden cards', Exception::WARNING);
            }
        }
    }
}
