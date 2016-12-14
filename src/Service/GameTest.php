<?php
/**
 * GameTest - game testing functionality
 */

namespace Service;

use ArcomageException as Exception;
use Db\Model\Player as PlayerModel;
use Def\Entity\XmlKeyword;
use Util\Random;

class GameTest extends ServiceAbstract
{
    /**
     * Run all card tests
     * @param bool $testSummary if active returns summary of all test results instead stopping on first error
     * @param bool $hiddenCards enable/disable hidden cards mode
     * @throws Exception
     * @return string
     */
    public function runCardTests($testSummary, $hiddenCards)
    {
        $defEntityCard = $this->defEntity()->card();
        $defEntityCardTest = $this->defEntity()->cardTest();
        $dbEntityGame = $this->dbEntity()->game();
        $serviceGameUseCard = $this->service()->gameUseCard();

        // player name is arbitrary for testing purposes
        $dummyPlayerName = 'tester';

        // prepare decks
        $starterDecks = $this->service()->deck()->starterDecks();
        $deck = $starterDecks[Random::arrayMtRand($starterDecks)];
        $aiDeck = $starterDecks[Random::arrayMtRand($starterDecks)];

        // prepare game
        $gameModes = ($hiddenCards) ? ['HiddenCards'] : [];
        $game = $dbEntityGame->createGame($dummyPlayerName, '', $deck, $gameModes);
        $serviceGameUseCard->startGame($game, PlayerModel::SYSTEM_NAME, $aiDeck);
        $game->setCurrent($dummyPlayerName);

        // backup initial state
        $game->checkpoint();

        // list cards
        $ids = $defEntityCard->getList();
        $cardList = $defEntityCard->getData($ids);

        // constants
        $cardSeparator = '========================';
        $testSeparator = '------------------------';
        $defaultPosition = 4;

        // initialize test log
        $log = $errors = array();

        // process cards
        foreach ($cardList as $card) {
            $cardTitle = '(' . $card['id'] . ') ' . $card['name'];

            // mark start of card test
            $log[] = $cardTitle;
            $log[] = $cardSeparator;

            // process card modes
            // case 1: has modes
            if ($card['modes'] > 0) {
                $modes = array_keys(array_fill(1, $card['modes'], 0));
            }
            // case 2: no modes (defaults to mode 0)
            else {
                $modes = array(0);
            }

            // analyze all possible card modes
            foreach ($modes as $mode) {
                // add card to hand
                $cardPos = $defaultPosition;
                $gameData = $game->getData();
                $myData = $gameData[1];
                $myHand = $myData->Hand;
                $myHand[$cardPos] = $card['id'];
                $myData->Hand = $myHand;

                // add card cost
                $myData->Bricks+= $card['bricks'];
                $myData->Gems+= $card['gems'];
                $myData->Recruits+= $card['recruits'];

                $previewData = $serviceGameUseCard->useCard($game, $dummyPlayerName, 'test', $cardPos, $mode);
                $cardMessage = ($card['modes'] > 0) ? ' mode ' . $mode : '';

                // flush card statistics and gained awards
                $serviceGameUseCard->flushCardStats();
                $serviceGameUseCard->flushAwards();

                // case 1: error
                if (isset($previewData['error'])) {
                    if (!$testSummary) {
                        throw new Exception($cardTitle . $cardMessage . ' ' . $previewData['error'], Exception::WARNING);
                    }

                    $errors[] = 'ERROR: ' . $cardTitle . $cardMessage . ' ' . $previewData['error'];
                }
                // case 2: success
                else {
                    $log[] = 'OK: ' . $cardMessage;
                    $log[] = GameUtil::formatPreview($previewData['p_data'], true);
                    $log[] = $testSeparator;
                }

                $game->rollback();
            }

            // process explicit card tests (this is optional)
            $result = $defEntityCardTest->getCard($card['id']);
            if ($result->isError()) {
                throw new Exception('card ' . $card['id'] . ' failed to load card test data', Exception::WARNING);
            }
            $cardTests = $result->data();

            foreach ($cardTests as $cardTest) {
                // card mode
                $mode = $cardTest['mode'];

                // determine card position
                $cardPos = (in_array($cardTest['cardpos'], [1, 2, 3, 4, 5, 6, 7, 8])) ? $cardTest['cardpos'] : $defaultPosition;

                $gameData = $game->getData();

                // round
                if (isset($cardTest['round']) && $cardTest['round'] > 0) {
                    $game->setRound($cardTest['round']);
                }

                $myData = $gameData[1];

                // add additional cards to hand
                foreach ([1 => 'mydata', 2 => 'hisdata'] as $dataIndex => $dataType) {
                    $currentData = $gameData[$dataIndex];

                    // game attributes
                    foreach (['tower', 'wall', 'quarry', 'magic', 'dungeons', 'bricks', 'gems', 'recruits'] as $attribute) {
                        if (isset($cardTest[$dataType][$attribute])) {
                            $attributeProp = ucfirst($attribute);
                            $currentData->$attributeProp = $cardTest[$dataType][$attribute];
                        }
                    }

                    // last card
                    if (isset($cardTest[$dataType]['last_card']) && isset($cardTest[$dataType]['last_action'])) {
                        $currentData->LastCard[1] = $cardTest[$dataType]['last_card'];
                        // irrelevant for this purpose, just for consistency
                        $currentData->LastMode[1] = 0;
                        $currentData->LastAction[1] = $cardTest[$dataType]['last_action'];
                    }

                    // hand data
                    if (isset($cardTest[$dataType]['hand'])) {
                        foreach ($cardTest[$dataType]['hand'] as $handData) {
                            $pos = $handData['position'];
                            $cardId = $handData['value'];

                            if (in_array($pos, [1, 2, 3, 4, 5, 6, 7, 8])) {
                                $currentData->Hand[$pos] = $cardId;

                                // new card flag
                                if (isset($handData['is_new']) && $handData['is_new'] == 1) {
                                    $currentData->NewCards[$pos] = 1;
                                }
                            }
                        }
                    }

                    // add current card to player's hand
                    if ($dataType == 'mydata') {
                        $currentData->Hand[$cardPos] = $card['id'];
                    }

                    // changes
                    if (isset($cardTest[$dataType]['changes'])) {
                        foreach (['tower', 'wall', 'quarry', 'magic', 'dungeons', 'bricks', 'gems', 'recruits'] as $attribute) {
                            if (isset($cardTest[$dataType]['changes'][$attribute])) {
                                $attributeProp = ucfirst($attribute);
                                $currentData->Changes[$attributeProp] = $cardTest[$dataType]['changes'][$attribute];
                            }
                        }
                    }

                    // tokens
                    if (isset($cardTest[$dataType]['tokens'])) {
                        $tokens = $cardTest[$dataType]['tokens'];

                        // validate number of tokens
                        if (count($tokens) > 3) {
                            throw new Exception('card ' . $card['id'] . ' too many tokens', Exception::WARNING);
                        }

                        // process tokens
                        $i = 1;
                        foreach ($tokens as $tokenData) {
                            // validate token name
                            if (!in_array($tokenData['name'], XmlKeyword::tokenKeywords())) {
                                throw new Exception(
                                    'card ' . $card['id'] . ' invalid token keyword ' . $tokenData['name'],
                                    Exception::WARNING
                                );
                            }

                            // validate token value
                            if (!is_numeric($tokenData['value']) || $tokenData['value'] < 0 || $tokenData['value'] > 100) {
                                throw new Exception(
                                    'card ' . $card['id'] . ' invalid token value ' . $tokenData['value'],
                                    Exception::WARNING
                                );
                            }

                            // set token counter
                            $currentData->TokenNames[$i] = $tokenData['name'];
                            $currentData->TokenValues[$i] = $tokenData['value'];
                            $i++;
                        }
                    }
                }

                // add card cost
                $myData->Bricks+= $card['bricks'];
                $myData->Gems+= $card['gems'];
                $myData->Recruits+= $card['recruits'];

                $previewData = $serviceGameUseCard->useCard($game, $dummyPlayerName, 'test', $cardPos, $mode);
                $cardMessage = ($card['modes'] > 0) ? ' mode ' . $mode : '';

                // flush card statistics and gained awards
                $serviceGameUseCard->flushCardStats();
                $serviceGameUseCard->flushAwards();

                // case 1: error
                if (isset($previewData['error'])) {
                    if (!$testSummary) {
                        throw new Exception($cardTitle . $cardMessage . ' ' . $previewData['error'], Exception::WARNING);
                    }

                    $errors[] = 'ERROR: ' . $cardTitle . $cardMessage . ' ' . $previewData['error'];
                }
                // case 2: success
                else {
                    $log[] = 'OK: ' . $cardMessage;
                    $log[] = GameUtil::formatPreview($previewData['p_data'], true);
                    $log[] = $testSeparator;
                }

                $game->rollback();
            }
        }

        return implode("\n", array_merge($errors, $log));
    }

    /**
     * Run all keyword tests
     * @param bool $testSummary if active returns summary of all test results instead stopping on first error
     * @param bool $hiddenCards enable/disable hidden cards mode
     * @throws Exception
     * @return string
     */
    public function runKeywordTests($testSummary, $hiddenCards)
    {
        $defEntityCard = $this->defEntity()->card();
        $defEntityKeywordTest = $this->defEntity()->keywordTest();
        $dbEntityGame = $this->dbEntity()->game();
        $serviceGameUseCard = $this->service()->gameUseCard();

        // player name is arbitrary for testing purposes
        $dummyPlayerName = 'tester';

        // prepare decks
        $starterDecks = $this->service()->deck()->starterDecks();
        $deck = $starterDecks[Random::arrayMtRand($starterDecks)];
        $aiDeck = $starterDecks[Random::arrayMtRand($starterDecks)];

        // prepare game
        $gameModes = ($hiddenCards) ? ['HiddenCards'] : [];
        $game = $dbEntityGame->createGame($dummyPlayerName, '', $deck, $gameModes);
        $serviceGameUseCard->startGame($game, PlayerModel::SYSTEM_NAME, $aiDeck);
        $game->setCurrent($dummyPlayerName);

        // backup initial state
        $game->checkpoint();

        // constants
        $cardSeparator = '========================';
        $testSeparator = '------------------------';
        $defaultPosition = 4;

        // initialize test log
        $log = $errors = array();

        // process keywords
        $keywords = XmlKeyword::keywordsOrder();
        foreach ($keywords as $keyword) {
            // process keyword tests
            $result = $defEntityKeywordTest->getKeyword($keyword);
            if ($result->isError()) {
                throw new Exception('keyword ' . $keyword . ' failed to load keyword test data', Exception::WARNING);
            }
            $keywordTests = $result->data();

            // mark start of card test
            $log[] = $keyword;
            $log[] = $cardSeparator;

            foreach ($keywordTests as $keywordTest) {
                // card mode
                $mode = $keywordTest['mode'];

                // determine card position
                $cardPos = (in_array($keywordTest['cardpos'], [1, 2, 3, 4, 5, 6, 7, 8]))
                    ? $keywordTest['cardpos'] : $defaultPosition;

                $gameData = $game->getData();

                // round
                if (isset($keywordTest['round']) && $keywordTest['round'] > 0) {
                    $game->setRound($keywordTest['round']);
                }

                $myData = $gameData[1];

                // add additional cards to hand
                foreach ([1 => 'mydata', 2 => 'hisdata'] as $dataIndex => $dataType) {
                    $currentData = $gameData[$dataIndex];

                    // game attributes
                    foreach (['tower', 'wall', 'quarry', 'magic', 'dungeons', 'bricks', 'gems', 'recruits'] as $attribute) {
                        if (isset($keywordTest[$dataType][$attribute])) {
                            $attributeProp = ucfirst($attribute);
                            $currentData->$attributeProp = $keywordTest[$dataType][$attribute];
                        }
                    }

                    // last card
                    if (isset($keywordTest[$dataType]['last_card']) && isset($keywordTest[$dataType]['last_action'])) {
                        $currentData->LastCard[1] = $keywordTest[$dataType]['last_card'];
                        // irrelevant for this purpose, just for consistency
                        $currentData->LastMode[1] = 0;
                        $currentData->LastAction[1] = $keywordTest[$dataType]['last_action'];
                    }

                    // hand data
                    if (isset($keywordTest[$dataType]['hand'])) {
                        foreach ($keywordTest[$dataType]['hand'] as $handData) {
                            $pos = $handData['position'];
                            $cardId = $handData['value'];

                            if (in_array($pos, [1, 2, 3, 4, 5, 6, 7, 8])) {
                                $currentData->Hand[$pos] = $cardId;

                                // new card flag
                                if (isset($handData['is_new']) && $handData['is_new'] == 1) {
                                    $currentData->NewCards[$pos] = 1;
                                }
                            }
                        }
                    }

                    // changes
                    if (isset($keywordTest[$dataType]['changes'])) {
                        foreach (['tower', 'wall', 'quarry', 'magic', 'dungeons', 'bricks', 'gems', 'recruits'] as $attribute) {
                            if (isset($keywordTest[$dataType]['changes'][$attribute])) {
                                $attributeProp = ucfirst($attribute);
                                $currentData->Changes[$attributeProp] = $keywordTest[$dataType]['changes'][$attribute];
                            }
                        }
                    }

                    // tokens
                    if (isset($keywordTest[$dataType]['tokens'])) {
                        $tokens = $keywordTest[$dataType]['tokens'];

                        // validate number of tokens
                        if (count($tokens) > 3) {
                            throw new Exception('keyword ' . $keyword . ' too many tokens', Exception::WARNING);
                        }

                        // process tokens
                        $i = 1;
                        foreach ($tokens as $tokenData) {
                            // validate token name
                            if (!in_array($tokenData['name'], XmlKeyword::tokenKeywords())) {
                                throw new Exception(
                                    'keyword ' . $keyword . ' invalid token keyword ' . $tokenData['name'],
                                    Exception::WARNING
                                );
                            }

                            // validate token value
                            if (!is_numeric($tokenData['value']) || $tokenData['value'] < 0 || $tokenData['value'] > 100) {
                                throw new Exception(
                                    'keyword ' . $keyword . ' invalid token value ' . $tokenData['value'],
                                    Exception::WARNING
                                );
                            }

                            // set token counter
                            $currentData->TokenNames[$i] = $tokenData['name'];
                            $currentData->TokenValues[$i] = $tokenData['value'];
                            $i++;
                        }
                    }
                }

                // determine which card are we going to play
                $playedCard = $myData->Hand[$cardPos];
                $card = $defEntityCard->getCard($playedCard);
                $card = $card->getData();

                // add card cost
                $myData->Bricks+= $card['bricks'];
                $myData->Gems+= $card['gems'];
                $myData->Recruits+= $card['recruits'];

                $previewData = $serviceGameUseCard->useCard($game, $dummyPlayerName, 'test', $cardPos, $mode);
                $cardMessage = ($card['modes'] > 0) ? ' mode ' . $mode : '';

                // flush card statistics and gained awards
                $serviceGameUseCard->flushCardStats();
                $serviceGameUseCard->flushAwards();

                // case 1: error
                if (isset($previewData['error'])) {
                    if (!$testSummary) {
                        throw new Exception(
                            $keyword . ' card ' . $playedCard . ' ' . $cardMessage . ' ' . $previewData['error'],
                            Exception::WARNING
                        );
                    }

                    $errors[] = 'ERROR: ' . $keyword . ' card ' . $playedCard . ' ' . $cardMessage . ' ' . $previewData['error'];
                }
                // case 2: success
                else {
                    $log[] = 'OK: ' . $cardMessage;
                    $log[] = GameUtil::formatPreview($previewData['p_data'], true);
                    $log[] = $testSeparator;
                }

                $game->rollback();
            }
        }

        return implode("\n", array_merge($errors, $log));
    }
}
