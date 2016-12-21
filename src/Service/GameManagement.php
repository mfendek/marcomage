<?php
/**
 * GameManagement - game setup and creation
 */

namespace Service;

use ArcomageException as Exception;
use Db\Model\Game as GameModel;
use Db\Model\Player as PlayerModel;
use Util\Random;

class GameManagement extends ServiceAbstract
{
    /**
     * Leave game
     * @param string $playerName
     * @param GameModel $game
     * @throws Exception
     */
    public function leaveGame($playerName, GameModel $game)
    {
        $db = $this->getDb();
        $dEntityChat = $this->dbEntity()->chat();

        // only allow if the game is over (stay if not)
        if ($game->getState() == 'in progress') {
            throw new Exception('Game is still in progress', Exception::WARNING);
        }

        // case 1: we are the first one to acknowledge and opponent isn't a computer player
        if ($game->getState() == 'finished' && !$game->checkGameMode('AIMode')) {
            $game->setState(($game->getPlayer1() == $playerName) ? 'P1 over' : 'P2 over');

            $db->beginTransaction();

            if (!$game->save()) {
                $db->rollBack();
                throw new Exception('Failed to save game data');
            }

            // inform other player about leaving the game
            $result = $dEntityChat->saveChatMessage($game->getGameId(), 'has left the game', $playerName);
            if ($result->isErrorOrNoEffect()) {
                $db->rollBack();
                throw new Exception('Failed to send chat message');
            }

            $db->commit();
        }
        // case 2: the other player has already acknowledged (auto-acknowledge in case of a computer player) 'P1 over' or 'P2 over'
        else {
            $db->beginTransaction();

            // delete game related chat messages
            $result = $dEntityChat->deleteChat($game->getGameId());
            if ($result->isError()) {
                $db->rollBack();
                throw new Exception('Failed to delete chat messages');
            }

            // delete game
            $game->markDeleted();
            if (!$game->save()) {
                $db->rollBack();
                throw new Exception('Failed to delete game');
            }

            $db->commit();
        }
    }

    /**
     * Host a game
     * @param string $playerName
     * @param mixed $deckId
     * @param array $gameModes
     * @param int $timeout
     * @throws Exception
     */
    public function hostGame($playerName, $deckId, array $gameModes, $timeout)
    {
        $dbEntityGame = $this->dbEntity()->game();

        $deck = $this->service()->deck()->loadReadyDeck($deckId, $playerName, $gameModes);

        // check if player has enough empty game slots
        if ($this->service()->gameUtil()->countFreeSlots($playerName) == 0) {
            throw new Exception('Too many games / challenges! Please resolve some', Exception::WARNING);
        }

        $timeoutValues = GameModel::listTimeoutValues();
        $timeoutKeys = array_keys($timeoutValues);
        $turnTimeout = (in_array($timeout, $timeoutKeys)) ? $timeout : 0;

        // create a new game
        $game = $dbEntityGame->createGame($playerName, '', $deck, $gameModes, $turnTimeout);
        if (!$game->save()) {
            throw new Exception('Failed to create new game!');
        }
    }

    /**
     * Join a game
     * @param string $playerName
     * @param mixed $deckId
     * @param GameModel $game
     * @throws Exception
     */
    public function joinGame($playerName, $deckId, GameModel $game)
    {
        $dbEntityGame = $this->dbEntity()->game();
        $serviceGameUseCard = $this->service()->gameUseCard();

        // check if the game is a challenge and not an active game
        if ($game->getState() != 'waiting') {
            throw new Exception('Game already in progress!', Exception::WARNING);
        }

        // check if player has enough empty game slots
        if ($this->service()->gameUtil()->countFreeSlots($playerName) == 0) {
            throw new Exception('Too many games / challenges! Please resolve some', Exception::WARNING);
        }

        // check if the game can be joined (can't join game against a computer player)
        if ($game->checkGameMode('AIMode')) {
            throw new Exception("Can't join AI game", Exception::WARNING);
        }

        $deck = $this->service()->deck()->loadReadyDeck($deckId, $playerName, $game->getGameModes());

        // check if such opponent exists
        $opponentName = $game->getPlayer1();
        $this->dbEntity()->player()->getPlayerAsserted($opponentName);

        // load opponent's settings
        $setting = $this->dbEntity()->setting()->getSettingAsserted($opponentName);

        // check if simultaneous games are allowed (depends on host settings)
        $gameLimit = $setting->getSetting('unique_game_opponent');
        $result = $dbEntityGame->checkGame($playerName, $opponentName);
        if ($result->isError()) {
            throw new Exception('Failed to check game between two players');
        }
        $checkGame = $result->isSuccess();

        if ($gameLimit == 'yes' && $checkGame) {
            throw new Exception('Unable to join game because opponent has disabled simultaneous games', Exception::WARNING);
        }

        // start game
        $serviceGameUseCard->startGame($game, $playerName, $deck);

        // process card statistics
        $cardStats = $serviceGameUseCard->getCardStats();
        if (count($cardStats) > 0) {
            $this->service()->statistic()->updateCardStats($cardStats);
        }

        $this->service()->gameUtil()->saveGameCreateReplay($game);
    }

    /**
     * Start AI game
     * @param string $playerName
     * @param mixed $deckId
     * @param mixed $aiDeckId
     * @param array $gameModes
     * @throws Exception
     * @return GameModel
     */
    public function startAiGame($playerName, $deckId, $aiDeckId, array $gameModes)
    {
        $dbEntityGame = $this->dbEntity()->game();

        $deck = $this->service()->deck()->loadReadyDeck($deckId, $playerName, $gameModes);

        $aiDeckId = (is_numeric($aiDeckId)) ? $aiDeckId : 'starter_deck';

        // case 1: pick random starter deck
        if ($aiDeckId == 'starter_deck') {
            $starterDecks = $this->service()->deck()->starterDecks();
            $aiDeck = $starterDecks[Random::arrayMtRand($starterDecks)];
        }
        // case 2: use deck provided by player
        else {
            // load deck
            $aiDeck = $this->dbEntity()->deck()->getDeckAsserted($aiDeckId);

            // validate deck ownership
            if ($aiDeck->getUsername() != $playerName) {
                throw new Exception('Can only use own deck', Exception::WARNING);
            }
        }

        // check if the deck is ready (all 45 cards)
        if (!$aiDeck->isReady()) {
            throw new Exception('Selected AI deck is not yet ready for gameplay!', Exception::WARNING);
        }

        // check if player has enough empty game slots
        if ($this->service()->gameUtil()->countFreeSlots($playerName) == 0) {
            throw new Exception('Too many games / challenges! Please resolve some', Exception::WARNING);
        }

        // always active in AI game
        $gameModes[] = 'FriendlyPlay';
        $gameModes[] = 'AIMode';

        // create a new game
        $game = $dbEntityGame->createGame($playerName, '', $deck, $gameModes);

        // join the computer player
        $this->service()->gameUseCard()->startGame($game, PlayerModel::SYSTEM_NAME, $aiDeck);

        $this->service()->gameUtil()->saveGameCreateReplay($game);

        return $game;
    }

    /**
     * Start AI challenge game
     * @param string $playerName
     * @param int $deckId
     * @param string $challengeName
     * @throws Exception
     */
    public function startAiChallenge($playerName, $deckId, $challengeName)
    {
        $defEntityChallenge = $this->defEntity()->challenge();
        $dbEntityDeck = $this->dbEntity()->deck();
        $dbEntityGame = $this->dbEntity()->game();

        $deck = $dbEntityDeck->getDeckAsserted($deckId);

        // validate deck ownership
        if ($deck->getUsername() != $playerName) {
            throw new Exception('Can only use own deck', Exception::WARNING);
        }

        // check if the deck is ready
        if (!$deck->isReady()) {
            throw new Exception('Select deck is not yet ready for game-play!', Exception::WARNING);
        }

        // check if player has enough empty game slots
        if ($this->service()->gameUtil()->countFreeSlots($playerName) == 0) {
            throw new Exception('Too many games / challenges! Please resolve some', Exception::WARNING);
        }

        // check AI challenge
        $challenge = $defEntityChallenge->getChallenge($challengeName);
        if (!$challenge) {
            throw new Exception('Invalid AI challenge ' . $challengeName, Exception::WARNING);
        }

        $challengeData = [
            'name' => $challenge->getName(),
            'init' => $challenge->getInit(),
        ];

        // prepare AI deck
        $challengeDecks = $this->service()->deck()->challengeDecks();
        $aiDeck = $challengeDecks[$challengeName];

        // set game modes (predefined for AI challenge)
        $gameModes = ['FriendlyPlay', 'LongMode', 'AIMode'];

        // create a new game
        $game = $dbEntityGame->createGame($playerName, '', $deck, $gameModes);

        // join the computer player
        $this->service()->gameUseCard()->startGame($game, PlayerModel::SYSTEM_NAME, $aiDeck, $challengeData);

        $this->service()->gameUtil()->saveGameCreateReplay($game);
    }
}
