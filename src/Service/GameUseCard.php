<?php
/**
 * Game / Use card
 */

namespace Service;

use ArcomageException as Exception;
use Db\Model\Deck as DeckModel;
use Db\Model\Game as GameModel;
use Db\Model\Player as PlayerModel;
use Def\Entity\XmlKeyword;
use Util\Date;
use Util\Random;

class GameUseCard extends ServiceAbstract
{
    /**
     * Played card position
     * @var int
     */
    private $playedCardPos = 0;

    /**
     * Played card mode
     * @var int
     */
    private $playedCardMode = 0;

    /**
     * Played card id
     * @var int
     */
    private $playedCardId = 0;

    /**
     * Name of the player who executed card action
     * @var string
     */
    private $playerName = '';

    /**
     * @var string
     */
    private $nextPlayer = '';

    /**
     * Initial state of my data attributes
     * @var array
     */
    private $myDataInitial = array();

    /**
     * Initial state of his data attributes
     * @var array
     */
    private $hisDataInitial = array();

    /**
     * Initial state of my discarded cards
     * @var array
     */
    private $myDiscardedCardsInitial = array();

    /**
     * Initial state of my new cards
     * @var array
     */
    private $myNewCardsInitial = array();

    /**
     * Initial state of his new cards
     * @var array
     */
    private $hisNewCardsInitial = array();

    /**
     * @var array
     */
    private $myChanges = array();

    /**
     * @var array
     */
    private $hisChanges = array();

    /**
     * @var int
     */
    private $nextCard = -1;

    /**
     * @var bool
     */
    private $isNextCardRevealed = false;

    /**
     * @var \GameProduction
     */
    private $production;

    /**
     * Current game
     * @var GameModel
     */
    private $game;

    /**
     * card statistics data
     * @var array
     */
    private $cardStats = [];
    /**
     * gained awards data
     * @var array
     */
    private $awards = [];

    /**
     * Add extra turn for current player
     */
    private function addExtraTurn()
    {
        $this->nextPlayer = $this->playerName;
    }

    /**
     * @param string $name
     * @return int
     */
    private function config($name)
    {
        return $this->game->config($name);
    }

    /**
     * @return int
     */
    private function round()
    {
        return $this->game->getRound();
    }

    /**
     * @return \CGamePlayerData
     */
    private function myData()
    {
        return $this->game->playerData($this->playerName);
    }

    /**
     * @return \CGamePlayerData
     */
    private function hisData()
    {
        return $this->game->playerData($this->game->determineOpponent());
    }

    /**
     * @return array
     */
    private function myDataInit()
    {
        return $this->myDataInitial;
    }

    /**
     * @return array
     */
    private function hisDataInit()
    {
        return $this->hisDataInitial;
    }

    /**
     * @return \CDeckData
     */
    private function myDeck()
    {
        return $this->myData()->Deck;
    }

    /**
     * @return \CDeckData
     */
    private function hisDeck()
    {
        return $this->hisData()->Deck;
    }

    /**
     * @return int
     */
    private function cardPos()
    {
        return $this->playedCardPos;
    }

    /**
     * @return int
     */
    private function mode()
    {
        return $this->playedCardMode;
    }

    /**
     * @return \Def\Model\Card
     */
    private function card()
    {
        return $this->getCard($this->playedCardId);
    }

    /**
     * @return int
     */
    private function myLastCardIndex()
    {
        return count($this->myData()->LastCard);
    }

    /**
     * @return int
     */
    private function hisLastCardIndex()
    {
        return count($this->hisData()->LastCard);
    }

    /**
     * @return string
     */
    private function myLastAction()
    {
        return $this->myData()->LastAction[$this->myLastCardIndex()];
    }

    /**
     * @return \Def\Model\Card
     */
    private function myLastCard()
    {
        return $this->getCard($this->myData()->LastCard[$this->myLastCardIndex()]);
    }

    /**
     * @return string
     */
    private function hisLastAction()
    {
        return $this->hisData()->LastAction[$this->hisLastCardIndex()];
    }

    /**
     * @return \Def\Model\Card
     */
    private function hisLastCard()
    {
        return $this->getCard($this->hisData()->LastCard[$this->hisLastCardIndex()]);
    }

    /**
     * @return bool
     */
    private function hiddenCards()
    {
        return $this->game->checkGameMode('HiddenCards');
    }

    /**
     * @param int $pos
     * @return bool
     */
    private function isMyNew($pos)
    {
        return isset($this->myNewCardsInitial[$pos]);
    }

    /**
     * @param int $pos
     * @return bool
     */
    private function isHisNew($pos)
    {
        return isset($this->hisNewCardsInitial[$pos]);
    }

    /**
     * @return array
     */
    private function myDiscardedCards()
    {
        return $this->myDiscardedCardsInitial;
    }

    /**
     * @param string $name
     * @return int
     */
    private function myChange($name)
    {
        return $this->myChanges[$name];
    }

    /**
     * @param string $name
     * @return int
     */
    private function hisChange($name)
    {
        return $this->hisChanges[$name];
    }

    /**
     * @param int $cardId [$cardId]
     * @param bool [$revealed]
     * @return int
     */
    private function nextCard($cardId = 0, $revealed = false)
    {
        if ($cardId > 0) {
            $this->nextCard = $cardId;

            // mark next card as revealed if necessary
            if ($revealed) {
                $this->isNextCardRevealed = true;
            }
        }

        return $this->nextCard;
    }

    /**
     * Disable standard draw
     */
    private function noNextCard()
    {
        $this->nextCard = 0;
    }

    /**
     * @return \GameProduction
     */
    private function production()
    {
        return $this->production;
    }

    /**
     * @return int
     */
    private function handSize()
    {
        return GameModel::HAND_SIZE;
    }

    /**
     * Steal stock in favour of specified player
     * @param string $type data type ('my', 'his')
     * @param int $amount amount stolen
     */
    private function stealStock($type, $amount)
    {
        // transfer stock
        foreach (['Bricks', 'Gems', 'Recruits'] as $resource) {
            $this->stealResource($type, $resource, $amount);
        }
    }

    /**
     * Steal resource in favour of specified player
     * @param string $type data type ('my', 'his')
     * @param string $resource resource ('my', 'his')
     * @param int $amount amount stolen
     * @throws Exception
     */
    private function stealResource($type, $resource, $amount)
    {
        $game = $this->game;

        // validate resource
        if (!in_array($resource, ['Bricks', 'Gems', 'Recruits'])) {
            throw new Exception('Invalid resource type ' . $resource);
        }

        // determine players
        $opponent = $game->determineOpponent();
        $sourcePlayer = ($type == 'my') ? $opponent : $game->getCurrent();
        $targetPlayer = ($type == 'my') ? $game->getCurrent() : $opponent;

        // extract data
        $sourceData = $game->playerData($sourcePlayer);
        $targetData = $game->playerData($targetPlayer);

        // transfer resource
        $stolen = min($amount, $sourceData->$resource);
        $sourceData->$resource -= $stolen;
        $targetData->$resource += $stolen;
    }

    /**
     * Steal random resources in favour of specified player
     * @param string $type data type ('my', 'his')
     * @param int $amount amount stolen
     */
    private function stealRandomResources($type, $amount)
    {
        $game = $this->game;

        // determine players
        $opponent = $game->determineOpponent();
        $sourcePlayer = ($type == 'my') ? $opponent : $game->getCurrent();
        $targetPlayer = ($type == 'my') ? $game->getCurrent() : $opponent;

        // extract data
        $sourceData = $game->playerData($sourcePlayer);
        $targetData = $game->playerData($targetPlayer);

        // transfer resources
        for ($i = 1; $i <= $amount; $i++) {
            $bricks = max(0, $sourceData->Bricks);
            $gems = max(0, $sourceData->Gems);
            $recruits = max(0, $sourceData->Recruits);
            $total = $bricks + $gems + $recruits;
            $rand = ($total > 0) ? mt_rand(1, $total) : 0;

            // case 1: Bricks
            if ($rand <= $bricks and $bricks > 0) {
                $sourceData->Bricks--;
                $targetData->Bricks++;
            }
            // case 2: Gems
            elseif ($rand <= ($bricks + $gems) and $gems > 0) {
                $sourceData->Gems--;
                $targetData->Gems++;
            }
            // case 3: Recruits
            elseif ($rand <= ($bricks + $gems + $recruits) and $recruits > 0) {
                $sourceData->Recruits--;
                $targetData->Recruits++;
            }
        }
    }

    /**
     * Set card to specified position in hand
     * @param string $type data type ('my', 'his')
     * @param int $cardPos card position in hand
     * @param int $cardId card id
     * @param array [$options] card options (reveal, mark as new, discard)
     */
    private function setCard($type, $cardPos, $cardId, array $options = [])
    {
        $game = $this->game;

        // determine target player
        $opponent = $game->determineOpponent();
        $targetPlayer = ($type == 'my') ? $game->getCurrent() : $opponent;

        // extract target data
        $data = $game->playerData($targetPlayer);

        // determine card that will be discarded
        $discardedCardId = $data->Hand[$cardPos];

        // check if revealing a card is necessary
        if (!$game->checkGameMode('HiddenCards') && isset($options['reveal']) && $options['reveal']) {
            // remove reveal option (hidden card mode is disabled)
            unset($options['reveal']);
        }

        // process cursed keyword effect unless hard discard option is set
        if ($discardedCardId > 0 && empty($options['discard'])) {
            $discardedCard = $this->getCard($discardedCardId);
            if ($discardedCard->hasKeyword('Cursed')) {
                // override new card by discarded card
                $cardId = $discardedCardId;

                // add revealed status if the discarded card was already revealed
                if ($game->checkGameMode('HiddenCards') && !empty($data->Revealed[$cardPos])) {
                    $options['reveal'] = true;
                }
            }
        }

        // set new card to specified position
        $data->setCard($cardPos, $cardId, $options);

        // process discarded card (ignore empty card or currently played card position)
        if ($discardedCardId > 0 && ($type != 'my' || $this->playedCardPos != $cardPos)) {
            // extract data that hold discarded cards structure (always belongs to current player)
            $discardData = $game->playerData($game->getCurrent());

            // determine discarded cards index
            $discardIndex = ($type == 'my') ? 0 : 1;
            $currentIndex = count($discardData->DisCards[$discardIndex]);

            $currentIndex++;
            $discardData->DisCards[$discardIndex][$currentIndex] = $discardedCardId;

            // update card statistics (card discarded by card effect)
            $this->logCardStat($discardedCardId, 'discard');
        }
    }

    /**
     * Replace card at specified position with other card
     * @param string $type data type ('my', 'his')
     * @param int $cardPos card position in hand
     * @param int $cardId card id
     */
    private function replaceCard($type, $cardPos, $cardId)
    {
        $game = $this->game;

        $options = ['new' => false];

        // detect persistent card effect
        if ($type == 'my' && $this->playedCardPos != $cardPos && $game->checkGameMode('HiddenCards')) {
            // reveal card position
            $options['reveal'] = true;
        }

        $this->setCard($type, $cardPos, $cardId, $options);
    }

    /**
     * Reveal card at specified position
     * @param string $type data type ('my', 'his')
     * @param int $cardPos card position in hand
     */
    private function revealCard($type, $cardPos)
    {
        $game = $this->game;

        // hidden game mode is inactive - nothing needs to be done
        if (!$game->checkGameMode('HiddenCards')) {
            return;
        }

        // determine target player
        $opponent = $game->determineOpponent();
        $targetPlayer = ($type == 'my') ? $game->getCurrent() : $opponent;

        // extract target data
        $data = $game->playerData($targetPlayer);

        // reveal card
        $data->Revealed[$cardPos] = 1;
    }

    /**
     * Set hand data to specified values
     * @param string $type data type ('my', 'his')
     * @param array $hand new hand data (doesn't need to be indexed correctly)
     */
    private function setHand($type, array $hand)
    {
        // incorrect data
        if (count($hand) != 8) {
            return;
        }

        // reindex input data
        $i = 1;
        foreach ($hand as $cardId) {
            $this->setCard($type, $i, $cardId);
            $i++;
        }
    }

    /**
     * Set hand data to specified values with shuffle
     * @param string $type data type ('my', 'his')
     * @param array $hand new hand data (doesn't need to be indexed correctly)
     */
    private function setHandShuffled($type, array $hand)
    {
        shuffle($hand);
        $this->setHand($type, $hand);
    }

    /**
     * Proxy function
     * @param int $cardId card id
     * @return \Def\Model\Card
     */
    private function getCard($cardId)
    {
        $defEntityCard = $this->defEntity()->card();
        $card = $defEntityCard->getCard($cardId);

        return $card;
    }

    /**
     * Proxy function
     * @param array $filters an array of chosen filters and their parameters
     * @return array ids for cards that match the filters
     */
    private function getList(array $filters)
    {
        // forbidden cards are excluded by default
        if (!isset($filters['forbidden'])) {
            $filters['forbidden'] = false;
        }

        $defEntityCard = $this->defEntity()->card();
        $list = $defEntityCard->getList($filters);

        return $list;
    }

    /**
     * Count specified keyword in hand
     * @param array $hand
     * @param string $keyword
     * @return int count
     */
    private function keywordCount(array $hand, $keyword)
    {
        $count = 0;

        foreach ($hand as $cardId) {
            $card = $this->getCard($cardId);

            if ($card->hasKeyword($keyword)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Extract keyword value from keyword string
     * @param string $keywords keywords string
     * @param string $keyword target keyword
     * @return int keyword value
     */
    private function keywordValue($keywords, $keyword)
    {
        $result = preg_match('/' . $keyword . ' \((\d+)\)/', $keywords, $matches);
        if ($result == 0) {
            return 0;
        }

        return (int)$matches[1];
    }

    /**
     * Count distinct keywords in hand
     * @param array $hand hand
     * @return int count
     */
    private function countDistinctKeywords(array $hand)
    {
        $keywordsList = array();

        foreach ($hand as $cardId) {
            $card = $this->getCard($cardId);

            // ignore cards with no keywords
            $keyword = $card->getData('keywords');
            if ($keyword != '') {
                $keywordsList[] = $keyword;
            }
        }

        // no keywords in hand
        if (count($keywordsList) == 0) {
            return 0;
        }

        // join keyword chunks strings into one
        $keywordsList = implode(",", $keywordsList);
        $keywords = array();

        // split individual keywords
        $words = explode(",", $keywordsList);
        foreach ($words as $word) {
            // remove keyword value if present
            $word = preg_split("/ \(/", $word, 0);
            $word = $word[0];
            // remove duplicates
            $keywords[$word] = $word;
        }

        return count($keywords);
    }

    /**
     * Returns one card at type-random from the specified source with the specified draw function
     * @param mixed $source card source (list or deck)
     * @param array $hand hand
     * @param int $cardPos card position of the drawn card
     * @param string $drawFunction draw function
     * @throws Exception
     * @return int next card
     */
    private function drawCard($source, array $hand, $cardPos, $drawFunction)
    {
        // try until anti-flood condition is met
        while (1) {
            if (!is_numeric($cardPos) || $cardPos < 1 || $cardPos > 8) {
                throw new Exception('Invalid card position ' . $cardPos);
            }

            $currentCard = $hand[$cardPos];

            // attempt to draw new card
            $nextCard = $this->$drawFunction($source, $currentCard);

            // count the number of occurrences of the same card on other slots
            $match = 0;
            for ($i = 1; $i <= $this->handSize(); $i++) {
                // do not count the card already played
                if ($hand[$i] == $nextCard && $cardPos != $i) {
                    $match++;
                }
            }

            // chance to retain the card decreases exponentially as the number of matches increases
            if (mt_rand(1, pow(2, $match)) == 1) {
                $this->logCardStat($nextCard, 'draw');

                return $nextCard;
            }
        }

        // should never happen
        return 0;
    }

    /**
     * Returns new hand from the specified source with the specified draw function
     * @param mixed $source card source (list or deck)
     * @param string $drawFunction draw function
     * @return array hand
     */
    private function drawHand($source, $drawFunction)
    {
        $hand = [1 => 0, 0, 0, 0, 0, 0, 0, 0];

        // card position is in this case irrelevant - send current position (it contains empty slot anyway)
        for ($i = 1; $i <= $this->handSize(); $i++) {
            $hand[$i] = $this->drawCard($source, $hand, $i, $drawFunction);
        }

        return $hand;
    }

    /**
     * Returns initial hand which always consist of 6 common and 2 uncommon cards
     * @param \CDeckData $deck
     * @return array hand
     */
    private function drawHandInitial(\CDeckData $deck)
    {
        // initialize empty hand
        $hand = [1 => 0, 0, 0, 0, 0, 0, 0, 0];

        // draw 6 common cards
        for ($i = 1; $i <= 6; $i++) {
            $hand[$i] = $this->drawCard($deck->Common, $hand, $i, 'drawCardList');
        }

        // draw 2 uncommon cards
        for ($i = 7; $i <= $this->handSize(); $i++) {
            $hand[$i] = $this->drawCard($deck->Uncommon, $hand, $i, 'drawCardList');
        }

        // shuffle card positions
        $keys = array_keys($hand);
        shuffle($hand);
        $hand = array_combine($keys, $hand);

        return $hand;
    }

    /**
     * Returns one card at type-random from the specified deck
     * @param \CDeckData $deck deck
     * @return int card id
     */
    private function drawCardRandom(\CDeckData $deck)
    {
        return $deck->drawCardRandom();
    }

    /**
     * Returns one card at type-random from the specified deck, different from the specified card
     * @param \CDeckData $deck deck
     * @param int $cardId card id
     * @return int card id
     */
    private function drawCardDifferent(\CDeckData $deck, $cardId)
    {
        return $deck->drawCardDifferent($cardId);
    }

    /**
     * Returns one card at type-random from the specified deck - no rare
     * @param \CDeckData $deck deck
     * @return int card id
     */
    private function drawCardNoRare(\CDeckData $deck)
    {
        return $deck->drawCardNoRare();
    }

    /**
     * Returns one card at random from the specified list of card ids
     * @param array $list list
     * @return int card id
     */
    private function drawCardList(array $list)
    {
        // "empty slot" card
        if (count($list) == 0) {
            return 0;
        }

        return $list[Random::arrayMtRand($list)];
    }

    /**
     * Returns a new hand consisting of type-random cards chosen from the specified deck
     * @param \CDeckData $deck
     * @return array
     */
    private function drawHandRandom(\CDeckData $deck)
    {
        return $this->drawHand($deck, 'drawCardRandom');
    }

    /**
     * Returns a new hand consisting of type-random cards chosen from the specified deck (excluding rare cards)
     * @param \CDeckData $deck
     * @return array hand
     */
    private function drawHandNoRare(\CDeckData $deck)
    {
        return $this->drawHand($deck, 'drawCardNoRare');
    }

    /**
     * Returns a new hand consisting of random cards from the specified list of card ids
     * @param array $list
     * @return array hand
     */
    private function drawHandList(array $list)
    {
        return $this->drawHand($list, 'drawCardList');
    }

    /**
     * Proxy function to Utils::arrayMtRand()
     * @param array $input array
     * @param int [$numReq] number of picked entries
     * @return mixed one or multiple picked entries (returns corresponding keys)
     */
    private function arrayRand(array $input, $numReq = 1)
    {
        return Random::arrayMtRand($input, $numReq);
    }

    /**
     * Allows us to execute embedded code with a completely separate scope
     * thus preventing any variables conflicts
     * @param string $code card or keyword code
     * @return bool
     */
    private function executeCode($code)
    {
        // nothing to execute (empty code is legal)
        if ($code == '') {
            return true;
        }

        // single "global" variable which is used within the card and keyword code
        $t = $this;

        // execute embedded code (card or keyword)
        if (eval($code) === false) {
            return false;
        }

        return true;
    }

    /**
     * Log card statistic
     * @param int $cardId card id
     * @param string $type statistic type
     */
    private function logCardStat($cardId, $type)
    {
        // case 1: card stat already exists
        if (isset($this->cardStats[$cardId][$type])) {
            $this->cardStats[$cardId][$type]++;
        }
        // case 2: card stat doesn't exist yet
        else {
            $this->cardStats[$cardId][$type] = 1;
        }
    }

    /**
     * Gain award
     * @param string $award award name
     * @param int [$amount] amount gained
     */
    private function gainAward($award, $amount = 1)
    {
        // case 1: card stat already exists
        if (isset($this->awards[$award])) {
            $this->awards[$award]+= $amount;
        }
        // case 2: card stat doesn't exist yet
        else {
            $this->awards[$award] = $amount;
        }
    }

    /**
     * @return array
     */
    public function getCardStats()
    {
        return $this->cardStats;
    }

    /**
     *
     */
    public function flushCardStats()
    {
        $this->cardStats = array();
    }

    /**
     * @return array
     */
    public function getAwards()
    {
        return $this->awards;
    }

    /**
     *
     */
    public function flushAwards()
    {
        $this->awards = array();
    }

    /**
     * Start game - used to provide player 2 data
     * @param GameModel $game
     * @param string $player player 2 name
     * @param DeckModel $deck player 2 deck
     * @param array [$challenge] challenge data
     */
    public function startGame(GameModel $game, $player, DeckModel $deck, $challenge = [])
    {
        // initialize player 2 data
        $game->setPlayer2($player);
        $game->setDeckId2($deck->getDeckId());

        $gameData = $game->getData();
        $gameData[2] = new \CGamePlayerData;
        $gameData[2]->Deck = $deck->getData();
        $game->setData($gameData);

        // update game status
        $game->setState('in progress');
        $game->setLastAction(Date::timeToStr());
        $game->setCurrent((mt_rand(0, 1) == 1) ? $game->getPlayer1() : $game->getPlayer2());

        // initialize game attributes
        $p1 = $game->getData()[1];
        $p2 = $game->getData()[2];

        // last card data
        $p1->LastCard[1] = $p2->LastCard[1] = 0;
        $p1->LastMode[1] = $p2->LastMode[1] = 0;
        $p1->LastAction[1] = $p2->LastAction[1] = 'play';

        // new card and revealed flags
        $p1->NewCards = $p2->NewCards = $p1->Revealed = $p2->Revealed = null;

        // discarded cards (0 - cards discarded from my hand, 1 - discarded from opponents hand)
        $p1->DisCards[0] = $p1->DisCards[1] = $p2->DisCards[0] = $p2->DisCards[1] = null;

        // castle and production
        $p1->Changes = $p2->Changes = [
            'Quarry' => 0, 'Magic' => 0, 'Dungeons' => 0,
            'Bricks' => 0, 'Gems' => 0, 'Recruits' => 0,
            'Tower' => 0, 'Wall' => 0
        ];

        $p1->Tower = $p2->Tower = $game->config('init_tower');
        $p1->Wall = $p2->Wall = $game->config('init_wall');
        $p1->Quarry = $p2->Quarry = 3;
        $p1->Magic = $p2->Magic = 3;
        $p1->Dungeons = $p2->Dungeons = 3;
        $p1->Bricks = $p2->Bricks = 15;
        $p1->Gems = $p2->Gems = 5;
        $p1->Recruits = $p2->Recruits = 10;

        // add starting bonus to second player
        // case 1: player 1 has first turn - player 2 gets starting bonus
        if ($game->getCurrent() == $game->getPlayer1()) {
            $p2->Bricks+= 1;
            $p2->Gems+= 1;
            $p2->Recruits+= 1;
        }
        // case 2: player 2 has first turn - player 1 gets starting bonus
        else {
            $p1->Bricks+= 1;
            $p1->Gems+= 1;
            $p1->Recruits+= 1;
        }

        // initialize tokens
        $p1->TokenNames = $p1->Deck->Tokens;
        $p2->TokenNames = $p2->Deck->Tokens;
        $p1->TokenValues = $p1->TokenChanges = array_fill_keys(array_keys($p1->TokenNames), 0);
        $p2->TokenValues = $p2->TokenChanges = array_fill_keys(array_keys($p2->TokenNames), 0);

        // initialize starting hands
        $p1->Hand = $this->drawHandInitial($p1->Deck);
        $p2->Hand = $this->drawHandInitial($p2->Deck);

        // process AI challenge (done only if the game is an AI challenge)
        if (count($challenge) > 0) {
            // AI challenge name
            $game->setAiName($challenge['name']);

            // override game attributes
            $p1Init = $challenge['init']['his'];
            $p2Init = $challenge['init']['mine'];

            // player 1 attributes
            foreach ($p1Init as $attrName => $attrValue) {
                $p1->$attrName = $attrValue;
            }

            // player 2 attributes
            foreach ($p2Init as $attrName => $attrValue) {
                $p2->$attrName = $attrValue;
            }
        }
    }

    /**
     * Use card in game
     * some variables can't be renamed or appear to be unused, but they are referenced in the XML code
     * @param GameModel $game
     * @param string $playerName player name
     * @param string $action card action ('play', 'discard', 'preview' or 'test')
     * @param int $cardPos card position (1 - 8)
     * @param int $mode card mode (0 - 8)
     * @return array result data
     */
    public function useCard(GameModel $game, $playerName, $action, $cardPos, $mode)
    {
        $defEntityCard = $this->defEntity()->card();
        $defEntityKeyword = $this->defEntity()->keyword();

        // store card action related data
        $this->playedCardPos = $cardPos;
        $this->playedCardMode = $mode;
        $this->playerName = $playerName;
        $this->game = $game;

        $result = array();

        // validate action
        if (!in_array($action, ['play', 'discard', 'preview', 'test'])) {
            $result['error'] = 'Invalid action!';
            return $result;
        }

        // only allow discarding if the game is still on
        if ($game->getState() != 'in progress') {
            $result['error'] = 'Action not allowed!';
            return $result;
        }

        // only allow action when it's the players' turn
        if ($game->getCurrent() != $playerName) {
            $result['error'] = 'Action only allowed on your turn!';
            return $result;
        }

        // anti-hack
        if ($cardPos < 1 || $cardPos > 8) {
            $result['error'] = 'Wrong card position!';
            return $result;
        }

        // determine game mode (normal or long)
        $gameMode = ($game->checkGameMode('LongMode')) ? 'long' : 'normal';

        // game configuration
        $maxTower = $game->config('max_tower');
        $resourceVictory = $game->config('res_victory');
        $timeoutVictory = $game->config('time_victory');

        // prepare basic information
        $opponent = $game->determineOpponent();
        $gameData = $game->getData();
        $myData = $this->myData();
        $hisData = $this->hisData();
        $myDeck = $this->myDeck();

        // find out what card is at that position
        $cardId = $myData->Hand[$cardPos];
        $this->playedCardId = $cardId;

        // load played card
        $card = $defEntityCard->getCard($cardId);

        // verify if there are enough resources
        if (in_array($action, ['play', 'preview', 'test']) && ($myData->Bricks < $card->getData('Bricks')
                || $myData->Gems < $card->getData('Gems') || $myData->Recruits < $card->getData('Recruits'))) {
            $result['error'] = 'Insufficient resources!';
            return $result;
        }

        // verify mode (depends on card)
        if (in_array($action, ['play', 'preview', 'test']) && ($mode < 0 || $mode > $card->getData('Modes')
                || ($mode == 0 && $card->getData('Modes') > 0))) {
            $result['error'] = 'Bad mode!';
            return $result;
        }

        // AI challenge check (rare cards are not allowed to be played by player in this game mode)
        if (in_array($action, ['play', 'preview', 'test']) && $game->getAiName() != '' && $card->getRarity() == 'Rare'
            && $playerName != PlayerModel::SYSTEM_NAME) {
            $result['error'] = "Rare cards can't be played in this game mode!";
            return $result;
        }

        // process card history
        $myLastCardIndex = $this->myLastCardIndex();

        // my last action related data
        $myLastAction = $this->myLastAction();
        $myLastCard = $this->myLastCard();

        // we need to store this information, because some cards will need it to make their effect,
        // however after effect this information is not stored
        $this->myNewCardsInitial = $myData->NewCards;
        $this->hisNewCardsInitial = $hisData->NewCards;
        $this->myDiscardedCardsInitial = $myData->DisCards;

        // create a copy of interesting game attributes
        $attributes = ['Quarry', 'Magic', 'Dungeons', 'Bricks', 'Gems', 'Recruits', 'Tower', 'Wall'];
        $this->myDataInitial = array();
        $this->hisDataInitial = array();

        foreach ($attributes as $attribute) {
            $this->myDataInitial[$attribute] = $myData->$attribute;
            $this->hisDataInitial[$attribute] = $hisData->$attribute;
        }

        // prepare changes made during previous round
        $myChanges = [
            'Quarry' => 0, 'Magic' => 0, 'Dungeons' => 0,
            'Bricks' => 0, 'Gems' => 0, 'Recruits' => 0,
            'Tower' => 0, 'Wall' => 0
        ];
        $hisChanges = $myChanges;

        // case 1: changes are no longer available - fetch data from replay
        if ($myLastCard->isPlayAgainCard() && $myLastAction == 'play') {
            // case 1: game testing - replay does not exist
            if ($action == 'test') {
                $replay = null;
            }
            // case 2: other cases - replay may exist
            else {
                // replay data is optional
                $replay = $this->dbEntity()->replay()->getReplay($game->getGameId());
            }

            // fetch data of the first turn of the current round
            $lastRoundData = false;
            if (!empty($replay)) {
                $replayLastRound = $replay->lastRound();
                if (!empty($replayLastRound)) {
                    $lastRoundData = $replayLastRound->GameData;
                }
            }

            // case 1: failed to load replay data - log warning and proceed with default changes data
            if (!$lastRoundData || !isset($lastRoundData[1]) || !isset($lastRoundData[2])) {
                // replay does not exist in case of test game action
                if ($action != 'test') {
                    $this->getDic()->logger()->logDebug(
                        "Failed to load replay data game ID = " . $game->getGameId()
                        . " p1 = " . $game->getPlayer1() . " p2 = " . $game->getPlayer2()
                    );
                }
            }
            // case 2: success
            else {
                $myChanges = $lastRoundData[(($game->getPlayer1() == $playerName) ? 1 : 2)]->Changes;
                $hisChanges = $lastRoundData[(($game->getPlayer1() == $playerName) ? 2 : 1)]->Changes;
            }
        }
        // case 2: changes are still available
        else {
            $myChanges = $myData->Changes;
            $hisChanges = $hisData->Changes;
        }

        $this->myChanges = $myChanges;
        $this->hisChanges = $hisChanges;

        // clear new cards flag, changes indicator and discarded cards here, if required
        if (!($myLastCard->isPlayAgainCard() && $myLastAction == 'play')) {
            $myData->NewCards = null;
            $myData->Changes = $hisData->Changes = [
                'Quarry' => 0, 'Magic' => 0, 'Dungeons' => 0,
                'Bricks' => 0, 'Gems' => 0, 'Recruits' => 0,
                'Tower' => 0, 'Wall' => 0
            ];
            $myData->DisCards[0] = $myData->DisCards[1] = null;
            $myData->TokenChanges = $hisData->TokenChanges = array_fill_keys(array_keys($myData->TokenNames), 0);
        }

        // by default, opponent goes next (but this may change via card)
        $this->nextPlayer = $opponent;

        // next card drawn will be decided randomly unless this changes
        $this->nextCard = -1;
        $this->isNextCardRevealed = false;

        // default production factor
        $this->production = new \GameProduction();

        $myHand = array();
        $hisHand = array();
        $myTokens = $hisTokens = array();

        // branch here according to action type
        if (in_array($action, ['play', 'preview', 'test'])) {
            // update player score (award 'Rares' - number of rare cards played)
            if ($action == 'play' && $card->getRarity() == 'Rare' && !$game->checkGameMode('FriendlyPlay')) {
                $this->gainAward('rares');
            }

            // subtract card cost
            $myData->Bricks-= $card->getData('Bricks');
            $myData->Gems-= $card->getData('Gems');
            $myData->Recruits-= $card->getData('Recruits');

            // update copy of game attributes (card cost was subtracted)
            foreach ($this->myDataInitial as $attribute => $value) {
                $this->myDataInitial[$attribute] = $myData->$attribute;
                $this->hisDataInitial[$attribute] = $hisData->$attribute;
            }

            // create a copy of token counters
            $myTokens = $myData->TokenValues;
            $hisTokens = $hisData->TokenValues;

            // create a copy of both players' hands (for difference computations only)
            $myHand = $myData->Hand;
            $hisHand = $hisData->Hand;

            // process token gains
            if ($card->getData('Keywords') != '') {
                // list all token keywords
                $keywords = XmlKeyword::tokenKeywords();

                foreach ($keywords as $keywordName) {
                    if ($card->hasKeyword($keywordName)) {
                        $keyword = $defEntityKeyword->getKeyword($keywordName);

                        // count number of cards with matching keyword (we don't count the played card)
                        $amount = $this->keywordCount($myData->Hand, $keywordName) - 1;

                        // increase token counter by basic gain + bonus gain
                        $myData->addToken($keywordName, $keyword->getBasicGain() + $amount * $keyword->getBonusGain());
                    }
                }
            }

            // execute card action
            if (!$this->executeCode($card->getData('Code'))) {
                $result['error'] = $debug = 'Debug: ' . $cardId . ': ' . $card->getData('Code');
                $this->getDic()->logger()->logDebug($debug);
                return $result;
            }

            // apply limits to game attributes
            $myData->applyGameLimits($gameMode);
            $hisData->applyGameLimits($gameMode);

            // process keyword effects
            if ($card->getData('Keywords') != '') {
                // we use this to cover the case when keyword token counter is filled up within a single turn
                $triggeredTokens = array();

                // list all keywords in order they are to be executed
                $keywords = XmlKeyword::keywordsOrder();

                foreach ($keywords as $keywordName) {
                    if ($card->hasKeyword($keywordName)) {
                        $keyword = $defEntityKeyword->getKeyword($keywordName);

                        // case 1: token keyword
                        if ($keyword->isTokenKeyword()) {
                            // check if player has matching token counter set and counter reached 100 or in case of test run
                            if ($myData->getToken($keywordName) >= 100) {
                                // reset token counter
                                $myData->setToken($keywordName, 0);

                                // store triggered token index
                                $triggeredTokens[$myData->findToken($keywordName)] = 1;

                                // execute keyword effect
                                if (!$this->executeCode($keyword->getCode())) {
                                    $result['error'] = $debug = 'Debug: ' . $keywordName . ': ' . $keyword->getCode();
                                    $this->getDic()->logger()->logDebug($debug);
                                    return $result;
                                }
                            }
                        }
                        // case 2: standard keyword
                        else {
                            // execute keyword effect
                            if (!$this->executeCode($keyword->getCode())) {
                                $result['error'] = $debug = 'Debug: ' . $keywordName . ': ' . $keyword->getCode();
                                $this->getDic()->logger()->logDebug($debug);
                                return $result;
                            }
                        }
                    }
                }
            }

            // apply limits to game attributes
            $myData->applyGameLimits($gameMode);
            $hisData->applyGameLimits($gameMode);

            // compute changes on token counters
            foreach ($myTokens as $index => $tokenVal) {
                $myData->TokenChanges[$index]+= $myData->TokenValues[$index] - $myTokens[$index];
                $hisData->TokenChanges[$index]+= $hisData->TokenValues[$index] - $hisTokens[$index];

                // single turn token counter fill correction
                if (isset($triggeredTokens[$index]) && $myData->TokenChanges[$index] == 0) {
                    $myData->TokenChanges[$index] = -100;
                }
            }
        }

        // add production at the end of turn
        $myData->Bricks+= $this->production->bricks() * $myData->Quarry;
        $myData->Gems+= $this->production->gems() * $myData->Magic;
        $myData->Recruits+= $this->production->recruits() * $myData->Dungeons;

        // compute changes on game attributes
        $myDiffs = $hisDiffs = array();
        $attributes = [
            'Quarry', 'Magic', 'Dungeons', 'Bricks', 'Gems', 'Recruits', 'Tower', 'Wall'
        ];
        foreach ($attributes as $attribute) {
            $myDiffs[$attribute] = $myDiff = $myData->$attribute - $this->myDataInitial[$attribute];
            $myData->Changes[$attribute]+= $myDiff;

            $hisDiffs[$attribute] = $hisDiff = $hisData->$attribute - $this->hisDataInitial[$attribute];
            $hisData->Changes[$attribute]+= $hisDiff;
        }

        // in case of play action update player awards
        if ($action == 'play' && !$game->checkGameMode('FriendlyPlay')) {
            // update player score (awards 'Quarry', 'Magic', 'Dungeons', 'Tower', 'Wall')
            foreach (['Quarry', 'Magic', 'Dungeons', 'Tower', 'Wall'] as $attribute) {
                if ($myDiffs[$attribute] > 0) {
                    $this->gainAward(strtolower($attribute), $myDiffs[$attribute]);
                }
            }

            // update player score (award 'TowerDamage' and 'WallDamage')
            foreach (['Tower', 'Wall'] as $attribute) {
                if ($hisDiffs[$attribute] < 0) {
                    $this->gainAward(strtolower($attribute) . '_damage', ($hisDiffs[$attribute] * (-1)));
                }
            }
        }

        // format preview data in case of preview action
        if (in_array($action, ['preview', 'test'])) {
            // draw card by card effect
            if ($this->nextCard > 0) {
                $myData->Hand[$cardPos] = $this->nextCard;

                // reveal next card if necessary
                if ($this->isNextCardRevealed) {
                    $myData->Revealed[$cardPos] = 1;
                }
            }

            $previewData = array();

            // card data
            $previewData['card']['name'] = $card->getData('Name');
            $previewData['card']['mode'] = $mode;
            $previewData['card']['position'] = $cardPos;

            // player data
            $previewData['player']['name'] = $playerName;

            // calculate changes in hand
            $handChanges = array();
            for ($i = 1; $i <= $this->handSize(); $i++) {
                if ($myHand[$i] != $myData->Hand[$i]) {
                    $handChanges[$i] = $myData->Hand[$i];
                }
            }

            $previewData['player']['hand_changes'] = $handChanges;

            // game attributes
            $myAttr = array();
            foreach ($attributes as $attribute) {
                $myAttr[$attribute] = $myData->$attribute;
            }

            $previewData['player']['attributes'] = $myAttr;
            $previewData['player']['changes'] = $myData->Changes;

            // tokens
            $my_tokens = $my_tokens_changes = array();
            foreach ($myTokens as $index => $tokenVal) {
                if ($myData->TokenNames[$index] != 'none') {
                    $tokenName = $myData->TokenNames[$index];
                    $my_tokens[$tokenName] = $myData->TokenValues[$index];
                    $my_tokens_changes[$tokenName] = $myData->TokenChanges[$index];
                }
            }

            $previewData['player']['tokens'] = $my_tokens;
            $previewData['player']['tokens_changes'] = $my_tokens_changes;

            // opponent data
            $previewData['opponent']['name'] = $opponent;

            // calculate changes in hand
            $handChanges = array();
            for ($i = 1; $i <= $this->handSize(); $i++) {
                if ($hisHand[$i] != $hisData->Hand[$i]) {
                    $handChanges[$i] = $hisData->Hand[$i];
                }
            }

            $previewData['opponent']['hand_changes'] = $handChanges;

            // game attributes
            $hisAttr = array();
            foreach ($attributes as $attribute) {
                $hisAttr[$attribute] = $hisData->$attribute;
            }
            $previewData['opponent']['attributes'] = $hisAttr;
            $previewData['opponent']['changes'] = $hisData->Changes;

            // tokens
            $his_tokens = $his_tokens_changes = array();
            foreach ($hisTokens as $index => $tokenVal) {
                if ($hisData->TokenNames[$index] != 'none') {
                    $tokenName = $hisData->TokenNames[$index];
                    $his_tokens[$tokenName] = $hisData->TokenValues[$index];
                    $his_tokens_changes[$tokenName] = $hisData->TokenChanges[$index];
                }
            }

            $previewData['opponent']['tokens'] = $his_tokens;
            $previewData['opponent']['tokens_changes'] = $his_tokens_changes;

            $result['p_data'] = $previewData;
            return $result;
        }

        // determine if force discard option is active or not
        $forceDiscard = in_array($action, ['play', 'preview', 'test']);

        // draw card at the end of turn
        // case 1: value was decided by a card effect
        if ($this->nextCard > 0) {
            $this->setCard('my', $cardPos, $this->nextCard, [
                'reveal' => $this->isNextCardRevealed,
                'discard' => $forceDiscard
            ]);
        }
        // case 2: normal drawing
        elseif ($this->nextCard == -1) {
            // determine drawing function
            // case 1: omit rare cards
            if ($action == 'play' && $card->isPlayAgainCard()) {
                $drawType = 'drawCardNoRare';
            }
            // case 2: standard draw
            elseif ($action == 'play') {
                $drawType = 'drawCardRandom';
            }
            // case 3: discard draw
            else {
                $drawType = 'drawCardDifferent';
            }

            $this->setCard(
                'my', $cardPos, $this->drawCard($myDeck, $myData->Hand, $cardPos, $drawType), ['discard' => $forceDiscard]
            );
        }
        // case 3: drawing was disabled entirely by a card effect
        else {
        }

        // store info about this current action, updating history as needed
        if ($myLastCard->isPlayAgainCard() && $myLastAction == 'play') {
            // preserve history when the previously played card was a "play again" card
            $myLastCardIndex++;
        }
        else {
            // otherwise erase the old history and start a new one
            $myData->LastCard = null;
            $myData->LastMode = null;
            $myData->LastAction = null;
            $myLastCardIndex = 1;
        }

        // record the current action in history
        $myData->LastCard[$myLastCardIndex] = $cardId;
        $myData->LastMode[$myLastCardIndex] = $mode;
        $myData->LastAction[$myLastCardIndex] = $action;

        // update card flags in case of discard action
        if ($action == 'discard') {
            $myData->NewCards[$cardPos] = 1;
            if (isset($myData->Revealed[$cardPos])) {
                unset($myData->Revealed[$cardPos]);
            }
        }

        // check victory conditions (in this predetermined order)
        // tower destruction victory - player
        if ($myData->Tower > 0 && $hisData->Tower <= 0) {
            $game->setWinner($playerName)
                ->setOutcomeType('Destruction')
                ->setState('finished');
        }
        // tower destruction victory - opponent
        elseif ($myData->Tower <= 0 && $hisData->Tower > 0) {
            $game->setWinner($opponent)
                ->setOutcomeType('Destruction')
                ->setState('finished');
        }
        // tower destruction victory - draw
        elseif ($myData->Tower <= 0 && $hisData->Tower <= 0) {
            $game->setWinner('')
                ->setOutcomeType('Draw')
                ->setState('finished');
        }
        // tower building victory - player
        elseif ($myData->Tower >= $maxTower && $hisData->Tower < $maxTower) {
            $game->setWinner($playerName)
                ->setOutcomeType('Construction')
                ->setState('finished');
        }
        // tower building victory - opponent
        elseif ($myData->Tower < $maxTower && $hisData->Tower >= $maxTower) {
            $game->setWinner($opponent)
                ->setOutcomeType('Construction')
                ->setState('finished');
        }
        // tower building victory - draw
        elseif ($myData->Tower >= $maxTower && $hisData->Tower >= $maxTower) {
            $game->setWinner('')
                ->setOutcomeType('Draw')
                ->setState('finished');
        }
        // resource accumulation victory - player
        elseif (($myData->Bricks + $myData->Gems + $myData->Recruits) >= $resourceVictory
            && !(($hisData->Bricks + $hisData->Gems + $hisData->Recruits) >= $resourceVictory)) {
            $game->setWinner($playerName)
                ->setOutcomeType('Resource')
                ->setState('finished');
        }
        // resource accumulation victory - opponent
        elseif (($hisData->Bricks + $hisData->Gems + $hisData->Recruits) >= $resourceVictory
            && !(($myData->Bricks + $myData->Gems + $myData->Recruits) >= $resourceVictory)) {
            $game->setWinner($opponent)
                ->setOutcomeType('Resource')
                ->setState('finished');
        }
        // resource accumulation victory - draw
        elseif (($myData->Bricks + $myData->Gems + $myData->Recruits) >= $resourceVictory
            && ($hisData->Bricks + $hisData->Gems + $hisData->Recruits) >= $resourceVictory) {
            $game->setWinner('')
                ->setOutcomeType('Draw')
                ->setState('finished');
        }
        // timeout victory
        elseif ($game->getRound() >= $timeoutVictory) {
            $game->setOutcomeType('Timeout')
                ->setState('finished');

            // compare towers
            if ($myData->Tower > $hisData->Tower) {
                $game->setWinner($playerName);
            }
            elseif ($myData->Tower < $hisData->Tower) {
                $game->setWinner($opponent);
            }
            // compare walls
            elseif ($myData->Wall > $hisData->Wall) {
                $game->setWinner($playerName);
            }
            elseif ($myData->Wall < $hisData->Wall) {
                $game->setWinner($opponent);
            }
            // compare facilities
            elseif ($myData->Quarry + $myData->Magic + $myData->Dungeons
                > $hisData->Quarry + $hisData->Magic + $hisData->Dungeons) {
                $game->setWinner($playerName);
            }
            elseif ($myData->Quarry + $myData->Magic + $myData->Dungeons
                < $hisData->Quarry + $hisData->Magic + $hisData->Dungeons) {
                $game->setWinner($opponent);
            }
            // compare resources
            elseif ($myData->Bricks + $myData->Gems + $myData->Recruits
                > $hisData->Bricks + $hisData->Gems + $hisData->Recruits) {
                $game->setWinner($playerName);
            }
            elseif ($myData->Bricks + $myData->Gems + $myData->Recruits
                < $hisData->Bricks + $hisData->Gems + $hisData->Recruits) {
                $game->setWinner($opponent);
            }
            // else draw
            else {
                $game->setWinner('')
                    ->setOutcomeType('Draw');
            }
        }

        // update misc data
        $game->setCurrent($this->nextPlayer);
        $game->setLastAction(Date::timeToStr());
        if ($this->nextPlayer != $playerName) {
            $game->setRound($game->getRound() + 1);
        }

        // update game data
        $game->setData($gameData);

        // update card statistics (card was played or discarded by standard discard action)
        $this->logCardStat($cardId, $action);

        return $result;
    }
}
