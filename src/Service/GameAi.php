<?php
/**
 * GameAI - artificial intelligence
 */

namespace Service;

use ArcomageException as Exception;
use Db\Model\Game as GameModel;
use Util\Random;

class GameAI extends ServiceAbstract
{
    /**
     * @var array
     */
    private $customConfig = array();

    /**
     * Returns static config - AI behavior configuration (more points, more likely to choose such action)
     * @return array config
     */
    private function staticConfig()
    {
        // custom config available
        if (!empty($this->customConfig)) {
            return $this->customConfig;
        }

        // static configuration (base value)
        return [
            'mine' => [
                'Quarry' => 80,
                'Magic' => 120,
                'Dungeons' => 100,
                'Bricks' => 4,
                'Gems' => 6,
                'Recruits' => 5,
                'Tower' => 7.5,
                'Wall' => 5,
            ],
            'his' => [
                'Quarry' => 96,
                'Magic' => 144,
                'Dungeons' => 120,
                'Bricks' => 4.8,
                'Gems' => 7.2,
                'Recruits' => 6,
                'Tower' => 9,
                'Wall' => 6,
            ],
        ];
    }

    /**
     * Returns misc config - special actions (more points, more likely to choose such action)
     * @return array config
     */
    private function miscConfig()
    {
        return [
            // play again cards (Quick and Swift)
            'play_again' => 300,
            // summon rare cards
            'summon' => 150,
            // discard rare cards
            'discard' => 250,
            // discard poison cards (common cards not from deck)
            'cleanup' => 50,
            // add poison cards
            'poison' => 50,
        ];
    }

    /**
     * Dynamic config - AI behavior configuration based on current game data (more points, more likely to choose such action)
     * @param GameModel $game current game
     * @param string $player player name
     * @return array config
     */
    private function dynamicConfig(GameModel $game, $player)
    {
        $gameConfig = GameModel::gameConfig();

        // determine game mode (normal or long)
        $gameMode = ($game->checkGameMode('LongMode')) ? 'long' : 'normal';

        // game configuration
        $maxTower = $gameConfig[$gameMode]['max_tower'];
        $maxWall = $gameConfig[$gameMode]['max_wall'];

        // prepare basic information
        $gameData = $game->getData();
        $myData = $gameData[(($game->getPlayer1() == $player) ? 1 : 2)];
        $hisData = $gameData[(($game->getPlayer1() == $player) ? 2 : 1)];

        // dynamic configuration (adjustment factor based on current game situation)
        $conf = array();
        foreach (['Quarry', 'Magic', 'Dungeons'] as $facility) {
            $conf['mine'][$facility] = $this->facilityEval($myData->$facility);
            $conf['his'][$facility] = $this->facilityEval($hisData->$facility);
        }

        foreach (['Bricks', 'Gems', 'Recruits'] as $resource) {
            $conf['mine'][$resource] = $this->resourceEval($myData->$resource);
            $conf['his'][$resource] = $this->resourceEval($hisData->$resource);
        }

        $conf['mine']['Tower'] = $this->towerEval($myData->Tower, $maxTower);
        $conf['mine']['Wall'] = $this->wallEval($myData->Wall, $maxWall);
        $conf['his']['Tower'] = $this->towerEval($hisData->Tower, $maxTower);
        $conf['his']['Wall'] = $this->wallEval($hisData->Wall, $maxWall);

        return $conf;
    }

    /**
     * @param float $facility
     * @return float
     */
    private function facilityEval($facility)
    {
        return min(2.5, (6 / (pow($facility, 2))) + 0.6);
    }

    /**
     * @param float $resource
     * @return float
     */
    private function resourceEval($resource)
    {
        return ((20 / ($resource + 40)) + 0.8);
    }

    /**
     * @param float $tower
     * @param int $maxTower
     * @return float
     */
    private function towerEval($tower, $maxTower)
    {
        $ratio = ($tower / $maxTower) * 100;
        return ((pow(($ratio - 50), 2) / 3000) + 0.9);
    }

    /**
     * @param float $wall
     * @param int $maxWall
     * @return float
     */
    private function wallEval($wall, $maxWall)
    {
        $ratio = ($wall / $maxWall) * 100;
        return min(1.5, (5 / ($ratio + 5)) + 0.85);
    }

    /**
     * Config - merge all configs together
     * @param GameModel $game current game
     * @param string $player player name
     * @return array config
     */
    protected function config(GameModel $game, $player)
    {
        // compute adjusted points (base value * adjustment factor)
        $aiConfig = array();
        $static = $this->staticConfig();
        $dynamic = $this->dynamicConfig($game, $player);

        foreach (['mine', 'his'] as $side) {
            foreach ($static[$side] as $name => $value) {
                $aiConfig[$side][$name] = $value * $dynamic[$side][$name];
            }
        }

        return $aiConfig;
    }

    /**
     * @param array $config
     */
    public function setCustomConfig(array $config)
    {
        $this->customConfig = $config;
    }

    /**
     *
     */
    public function clearCustomConfig()
    {
        $this->customConfig = array();
    }

    /**
     * Determine AI move - determine card action ('play'/'discard'), card position (1-8) and card mode (0/1-8)
     * @param string $player player name
     * @param GameModel $game current game
     * @throws Exception
     * @return array
     */
    public function determineMove($player, GameModel $game)
    {
        $defEntityCard = $this->defEntity()->card();
        $serviceGameUseCard = $this->service()->gameUseCard();

        $gameConfig = GameModel::gameConfig();

        // determine game mode (normal or long)
        $gameMode = ($game->checkGameMode('LongMode')) ? 'long' : 'normal';

        // game configuration
        $maxTower = $gameConfig[$gameMode]['max_tower'];
        $resVic = $gameConfig[$gameMode]['res_victory'];

        // backup initial data state
        $game->checkpoint();

        // prepare basic information
        $gameData = $game->getData();
        $myData = $gameData[(($game->getPlayer1() == $player) ? 1 : 2)];
        $hisData = $gameData[(($game->getPlayer1() == $player) ? 2 : 1)];
        $myDeck = $myData->Deck;
        $hisDeck = $hisData->Deck;

        // format game attributes
        $myAttributes = $hisAttributes = array();
        foreach (['Quarry', 'Magic', 'Dungeons', 'Bricks', 'Gems', 'Recruits', 'Tower', 'Wall'] as $attribute) {
            $myAttributes[$attribute] = $myData->$attribute;
            $hisAttributes[$attribute] = $hisData->$attribute;
        }

        // default data
        $action = 'discard';
        $chosenCardPos = 1;
        $mode = 0;

        // load card data for both hands
        // 'card_position' => 'card modes'
        $playablePositions = array();
        $myHand = $myData->Hand;
        $myHandData = $defEntityCard->getData($myHand);
        if ($myHandData === false) {
            throw new Exception('Failed to load card data for hand ' . implode(",", $myHand));
        }
        $hisHandData = $defEntityCard->getData($hisData->Hand);
        if ($myHandData === false) {
            throw new Exception('Failed to load card data for hand ' . implode(",", $hisData->Hand));
        }

        // determine playable cards
        foreach ($myHandData as $i => $card) {
            if ($myData->Bricks >= $card['bricks'] && $myData->Gems >= $card['gems'] && $myData->Recruits >= $card['recruits']) {
                $playablePositions[] = $i;
            }
        }

        // choose a card for playing
        if (count($playablePositions) > 0) {
            // determine all possible actions is this turn
            $maxPoints = 0;
            $choices = array();
            $aiConfig = $this->config($game, $player);
            $miscConfig = $this->miscConfig();

            // take into account all playable positions
            foreach ($playablePositions as $pos) {
                $card = $myHandData[$pos];
                $playAgain = (strpos($card['keywords'], 'Quick') !== false || strpos($card['keywords'], 'Swift') !== false);

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
                foreach ($modes as $i) {
                    $points = 0;
                    $preview = $serviceGameUseCard->useCard($game, $player, 'preview', $pos, $i);
                    if (isset($preview['error'])) {
                        throw new Exception(
                            'Failed to calculate game preview for player '
                            . $player . ' pos ' . $pos . ' mode ' . $i . ' ' . $preview['error']
                        );
                    }

                    // flush card statistics and gained awards
                    $serviceGameUseCard->flushCardStats();
                    $serviceGameUseCard->flushAwards();

                    $playerData = $preview['p_data']['player'];
                    $opponentData = $preview['p_data']['opponent'];

                    $myAfter = $playerData['attributes'];
                    $hisAfter = $opponentData['attributes'];

                    // calculate points from my attributes
                    foreach ($myAttributes as $attrName => $attrValue) {
                        $points+= ($myAfter[$attrName] - $myAttributes[$attrName]) * $aiConfig['mine'][$attrName];
                    }

                    // calculate points from his attributes
                    foreach ($hisAttributes as $attrName => $attrValue) {
                        $points+= ($hisAttributes[$attrName] - $hisAfter[$attrName]) * $aiConfig['his'][$attrName];
                    }

                    // add extra points in case of play again card
                    if ($playAgain) {
                        $points+= $miscConfig['play_again'];
                    }

                    // analyze cards in hand
                    $myHandChanges = $playerData['hand_changes'];
                    $hisHandChanges = $opponentData['hand_changes'];

                    foreach ($myHandChanges as $cardPos => $currentCard) {
                        // previous card
                        $prevCard = $myHandData[$cardPos];
                        $prevRarity = $prevCard['rarity'];

                        // new card
                        $cardData = $defEntityCard->getCard($currentCard);
                        if (!$cardData) {
                            throw new Exception('Failed to load card data ' . $currentCard);
                        }
                        $cardClass = $cardData->getRarity();

                        // gain extra points if rare cards were added to player's hand (summoning related cards)
                        if ($cardClass == 'Rare') {
                            $points+= $miscConfig['summon'];
                        }

                        // lose points if rare cards were discarded from hand (ignore played card position)
                        if ($cardPos != $pos && $prevRarity == 'Rare') {
                            // determine how soon is possible to play this card
                            $costNeeded = $prevCard['bricks'] + $prevCard['gems'] + $prevCard['recruits'];
                            $costMissing = max(0, $prevCard['bricks'] - $myAttributes['Bricks'])
                                    + max(0, $prevCard['gems'] - $myAttributes['Gems'])
                                    + max(0, $prevCard['recruits'] - $myAttributes['Recruits']);

                            $playRatio = ($costNeeded > 0) ? ($costNeeded - $costMissing) / $costNeeded : 1;
                            $points-= $miscConfig['discard'] * $playRatio;
                        }

                        // gain extra points in case poison cards were discarded from hand
                        if ($prevRarity == 'Common' && !in_array($prevCard['id'], $myDeck->Common)
                            && in_array($currentCard, $myDeck->$cardClass)) {
                            $points+= $miscConfig['cleanup'];
                        }

                        // lose points in case poison cards were added to hand
                        if ($cardClass == 'Common' && !in_array($currentCard, $myDeck->Common)
                            && in_array($prevCard['id'], $myDeck->$prevRarity)) {
                            $points-= $miscConfig['poison'];
                        }
                    }

                    foreach ($hisHandChanges as $cardPos => $currentCard) {
                        // previous card
                        $prevCard = $hisHandData[$cardPos];
                        $prevRarity = $prevCard['rarity'];

                        // new card
                        $cardData = $defEntityCard->getCard($currentCard);
                        if (!$cardData) {
                            throw new Exception('Failed to load card data ' . $currentCard);
                        }
                        $cardClass = $cardData->getRarity();

                        // lose points if rare cards were added to opponent's hand
                        if ($cardClass == 'Rare') {
                            $points-= $miscConfig['summon'];
                        }

                        // gain extra points if rare cards were discarded from opponent's hand (discard related cards)
                        if ($prevRarity == 'Rare') {
                            // determine how soon is opponent going to be able to play this card
                            $costNeeded = $prevCard['bricks'] + $prevCard['gems'] + $prevCard['recruits'];
                            $costMissing = max(0, $prevCard['bricks'] - $hisAttributes['Bricks'])
                                    + max(0, $prevCard['gems'] - $hisAttributes['Gems'])
                                    + max(0, $prevCard['recruits'] - $hisAttributes['Recruits']);

                            $playRatio = ($costNeeded > 0) ? ($costNeeded - $costMissing) / $costNeeded : 1;
                            $points+= $miscConfig['discard'] * $playRatio;
                        }

                        // lose points in case poison cards were discarded from opponent's hand
                        if ($prevRarity == 'Common' && !in_array($prevCard['id'], $hisDeck->Common)
                            && in_array($currentCard, $hisDeck->$cardClass)) {
                            $points-= $miscConfig['cleanup'];
                        }

                        // gain extra points in case poison cards were added to opponent's hand
                        if ($cardClass == 'Common' && !in_array($currentCard, $hisDeck->Common)
                            && in_array($prevCard['id'], $hisDeck->$prevRarity)) {
                            $points+= $miscConfig['poison'];
                        }
                    }

                    // evaluate how efficiently was the stock spent
                    $stockBefore = $myAttributes['Bricks'] + $myAttributes['Gems'] + $myAttributes['Recruits'];
                    $cardCost = $card['bricks'] + $card['gems'] + $card['recruits'];
                    $stockRatio = ($stockBefore > 0) ? $cardCost / $stockBefore : 1;
                    $points+= $points * $stockRatio;

                    // check victory and loss conditions
                    $victory = ($hisAfter['Tower'] <= 0 || $myAfter['Tower'] >= $maxTower
                        || ($myAfter['Bricks'] + $myAfter['Gems'] + $myAfter['Recruits']) >= $resVic);
                    $loss = ($myAfter['Tower'] <= 0 || $hisAfter['Tower'] >= $maxTower
                        || ($hisAfter['Bricks'] + $hisAfter['Gems'] + $hisAfter['Recruits']) >= $resVic);

                    // evaluate victory and loss conditions (avoid draws)
                    if ($victory && !$loss) {
                        $points = 99999; // choose this choice in case of certain win
                    }
                    elseif (!$victory && $loss) {
                        $points = 0; // never choose this choice in case of certain loss
                    }

                    // format turn simulation data
                    $data = array();
                    $data['pos'] = $pos;
                    $data['mode'] = $i;
                    $data['points'] = $points;
                    $maxPoints = max($maxPoints, $points);

                    $choices[] = $data;

                    // restore initial data state
                    $game->rollback();
                }
            }

            // if no card qualified for playing, discard a card instead (prevent play action)
            if ($maxPoints > 0) {
                $bestChoices = array();
                foreach ($choices as $choice) {
                    if ($choice['points'] == $maxPoints) {
                        $currentChoice['pos'] = $choice['pos'];
                        $currentChoice['mode'] = $choice['mode'];
                        $bestChoices[] = $currentChoice;
                    }
                }
                $bestChoice = $bestChoices[Random::arrayMtRand($bestChoices)];

                $action = 'play';
                $chosenCardPos = $bestChoice['pos'];
                $mode = $bestChoice['mode'];
            }
        }

        // choose a card for discarding
        if ($action == 'discard') {
            $rares = $rest = $selected = array();
            // split cards into two groups based on rarity (rares and the rest)
            foreach ($myHandData as $i => $card) {
                if ($card['rarity'] == 'Rare') {
                    $rares[$i] = $card;
                }
                else {
                    $rest[$i] = $card;
                }
            }

            // don't discard rares unless there is no other choice
            $selected = (count($rest) == 0) ? $rares : $rest;

            // calculate resources missing for each card
            $max = 0;
            $missingRes = array();
            foreach ($selected as $i => $card) {
                $missing = max(0, $card['bricks'] - $myData->Bricks)
                        + max(0, $card['gems'] - $myData->Gems)
                        + max(0, $card['recruits'] - $myData->Recruits);
                $missingRes[$i] = $missing;
                $max = max($max, $missing);
            }

            // pick cards with most resources missing to play (sort by card rarity)
            $storage = ['Common' => [], 'Uncommon' => [], 'Rare' => []];
            foreach ($missingRes as $i => $missing) {
                if ($missing == $max) {
                    $cardRarity = $myHandData[$i]['rarity'];
                    $storage[$cardRarity][] = $i;
                }
            }

            // pick preferably cards with lower rarity, but choose random card within the rarity group
            shuffle($storage['Common']);
            shuffle($storage['Uncommon']);
            shuffle($storage['Rare']);
            $storageTemp = array_merge($storage['Common'], $storage['Uncommon'], $storage['Rare']);
            $chosenCardPos = array_shift($storageTemp);

            $mode = 0;
        }

        $result = array();
        $result['action'] = $action;
        $result['cardpos'] = $chosenCardPos;
        $result['mode'] = $mode;

        return $result;
    }
}
