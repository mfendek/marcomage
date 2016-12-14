<?php
/**
 * Keyword tests - the representation of Keyword tests database
 */

namespace Def\Entity;

use ArcomageException as Exception;
use Def\Util\Result;

class XmlKeywordTest extends EntityAbstract
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
        $xml = new \SimpleXMLElement('xml/keyword_tests.xml', 0, true);
        $xml->registerXPathNamespace('am', 'http://arcomage.net');

        $keywords = $xml->xpath('/am:keywords/am:keyword');
        if ($keywords === false) {
            throw new Exception('failed to read keyword test DB');
        }

        // load all keyword tests
        $this->db = array();
        foreach ($keywords as $keyword) {
            $keywordName = (string)$keyword->name;

            // parse keyword test data
            $tests = array();
            if (isset($keyword->keyword_test)) {
                foreach ($keyword->keyword_test as $keywordTest) {
                    $testData = array();
                    $testData['mode'] = (isset($keywordTest->mode)) ? (int)$keywordTest->mode : 0;
                    $testData['cardpos'] = (isset($keywordTest->cardpos)) ? (int)$keywordTest->cardpos : 0;
                    $testData['round'] = (isset($keywordTest->round)) ? (int)$keywordTest->round : 0;

                    foreach (['mydata', 'hisdata'] as $dataType) {
                        // game attributes
                        foreach (['tower', 'wall', 'quarry', 'magic', 'dungeons', 'bricks', 'gems', 'recruits'] as $attribute) {
                            if (isset($keywordTest->$dataType->$attribute)) {
                                $testData[$dataType][$attribute] = (int)$keywordTest->$dataType->$attribute;
                            }
                        }

                        // last card
                        if (isset($keywordTest->$dataType->last_card) && isset($keywordTest->$dataType->last_action)) {
                            $testData[$dataType]['last_card'] = (int)$keywordTest->$dataType->last_card;
                            $testData[$dataType]['last_action'] = (string)$keywordTest->$dataType->last_action;
                        }

                        // hand data
                        if (isset($keywordTest->$dataType->hand->slot)) {
                            $hand = array();
                            foreach ($keywordTest->$dataType->hand->slot as $slot) {
                                $slotData = array();
                                $slotData['position'] = (int)$slot->position;
                                $slotData['value'] = (int)$slot->value;
                                $slotData['is_new'] = (int)$slot->is_new;

                                $hand[] = $slotData;
                            }

                            $testData[$dataType]['hand'] = $hand;
                        }

                        // changes
                        if (isset($keywordTest->$dataType->changes)) {
                            foreach (['tower', 'wall', 'quarry', 'magic', 'dungeons', 'bricks', 'gems', 'recruits'] as $attribute) {
                                if (isset($keywordTest->$dataType->changes->$attribute)) {
                                    $testData[$dataType]['changes'][$attribute] = (int)$keywordTest->$dataType->changes->$attribute;
                                }
                            }
                        }

                        // tokens
                        if (isset($keywordTest->$dataType->tokens->token)) {
                            $tokens = $usedTokenNames = array();
                            foreach ($keywordTest->$dataType->tokens->token as $token) {
                                $tokenData = array();
                                $tokenData['name'] = (string)$token->name;
                                $tokenData['value'] = (int)$token->value;

                                if (!in_array($tokenData['name'], $usedTokenNames)) {
                                    $tokens[] = $tokenData;

                                    // mark token as used to prevent duplicit token data
                                    $usedTokenNames[] = $tokenData['name'];
                                }
                            }

                            $testData[$dataType]['tokens'] = $tokens;
                        }
                    }

                    $tests[] = $testData;
                }
            }

            $this->db[$keywordName] = $tests;
        }
    }

    /**
     * Load specified keyword test
     * @param string $keywordName keyword name
     * @return Result
     */
    public function getKeyword($keywordName)
    {
        $data = $this->getDb();

        // check if keyword test data is present
        if (!isset($data[$keywordName])) {
            return new Result(Result::NO_EFFECT);
        }

        return new Result(Result::SUCCESS, $data[$keywordName]);
    }
}
