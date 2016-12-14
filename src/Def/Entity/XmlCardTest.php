<?php
/**
 * Card test - the representation of card tests database
 */

namespace Def\Entity;

use ArcomageException as Exception;
use Def\Util\Result;

class XmlCardTest extends EntityAbstract
{
    /**
     * @return array
     */
    protected function getDb()
    {
        return parent::getDb();
    }

    /**
     * @throws Exception
     */
    protected function initDb()
    {
        $xml = new \SimpleXMLElement('xml/card_tests.xml', 0, true);
        $xml->registerXPathNamespace('am', 'http://arcomage.net');

        $cards = $xml->xpath('/am:cards/am:card');
        if ($cards === false) {
            throw new Exception('failed to read cards DB');
        }

        // load all card tests
        $this->db = array();
        foreach ($cards as $card) {
            $cardId = (int)$card->attributes()->id;

            // parse card test data
            $tests = array();
            if (isset($card->card_test)) {
                foreach ($card->card_test as $cardTest) {
                    $testData = array();
                    $testData['mode'] = (isset($cardTest->mode)) ? (int)$cardTest->mode : 0;
                    $testData['cardpos'] = (isset($cardTest->cardpos)) ? (int)$cardTest->cardpos : 0;
                    $testData['round'] = (isset($cardTest->round)) ? (int)$cardTest->round : 0;

                    foreach (['mydata', 'hisdata'] as $dataType) {
                        // game attributes
                        foreach (['tower', 'wall', 'quarry', 'magic', 'dungeons', 'bricks', 'gems', 'recruits'] as $attribute) {
                            if (isset($cardTest->$dataType->$attribute)) {
                                $testData[$dataType][$attribute] = (int)$cardTest->$dataType->$attribute;
                            }
                        }

                        // last card
                        if (isset($cardTest->$dataType->last_card) && isset($cardTest->$dataType->last_action)) {
                            $testData[$dataType]['last_card'] = (int)$cardTest->$dataType->last_card;
                            $testData[$dataType]['last_action'] = (string)$cardTest->$dataType->last_action;
                        }

                        // hand data
                        if (isset($cardTest->$dataType->hand->slot)) {
                            $hand = array();
                            foreach ($cardTest->$dataType->hand->slot as $slot) {
                                $slotData = array();
                                $slotData['position'] = (int)$slot->position;
                                $slotData['value'] = (int)$slot->value;
                                $slotData['is_new'] = (int)$slot->is_new;

                                $hand[] = $slotData;
                            }

                            $testData[$dataType]['hand'] = $hand;
                        }

                        // changes
                        if (isset($cardTest->$dataType->changes)) {
                            foreach (['tower', 'wall', 'quarry', 'magic', 'dungeons', 'bricks', 'gems', 'recruits'] as $attribute) {
                                if (isset($cardTest->$dataType->changes->$attribute)) {
                                    $testData[$dataType]['changes'][$attribute] = (int)$cardTest->$dataType->changes->$attribute;
                                }
                            }
                        }

                        // tokens
                        if (isset($cardTest->$dataType->tokens->token)) {
                            $tokens = $usedTokenNames = array();
                            foreach ($cardTest->$dataType->tokens->token as $token) {
                                $tokenData = array();
                                $tokenData['name'] = (string)$token->name;
                                $tokenData['value'] = (int)$token->value;

                                if (!in_array($tokenData['name'], $usedTokenNames)) {
                                    $tokens[] = $tokenData;

                                    // mark token as used to prevent redundant token data
                                    $usedTokenNames[] = $tokenData['name'];
                                }
                            }

                            $testData[$dataType]['tokens'] = $tokens;
                        }
                    }

                    $tests[] = $testData;
                }
            }

            $this->db[$cardId] = $tests;
        }
    }

    /**
     * @param $cardId
     * @return \Def\Util\Result
     */
    public function getCard($cardId)
    {
        $data = $this->getDb();

        // check if card test data is present
        if (!isset($data[$cardId])) {
            return new Result(Result::NO_EFFECT);
        }

        return new Result(Result::SUCCESS, $data[$cardId]);
    }
}
