<?php
/**
 * Challenge - challenge related controller
 */

namespace Controller;

use ArcomageException as Exception;
use Db\Model\Game as GameModel;
use Db\Model\Message as MessageModel;
use Util\Encode;

class Challenge extends ControllerAbstract
{
    /**
     * Accept challenge
     * @throws Exception
     */
    protected function acceptChallenge()
    {
        $request = $this->request();

        $player = $this->getCurrentPlayer();
        $db = $this->getDb();
        $dbEntityGame = $this->dbEntity()->game();
        $dbEntityMessage = $this->dbEntity()->message();
        $dbEntityReplay = $this->dbEntity()->replay();
        $serviceUseCard = $this->service()->gameUseCard();

        $this->result()->setCurrent('Messages');

        $this->assertParamsNonEmpty(['accept_deck']);

        // check access rights
        if (!$this->checkAccess('accept_challenges')) {
            throw new Exception('Access denied', Exception::WARNING);
        }

        $gameId = $request['accept_challenge'];
        $game = $dbEntityGame->getGameAsserted($gameId);

        // check if the game is a challenge and not an active game
        if ($game->getState() != 'waiting') {
            throw new Exception('Game already in progress', Exception::WARNING);
        }

        // check if player has enough empty game slots
        if ($this->service()->gameUtil()->countFreeSlots($player->getUsername(), true) == 0) {
            throw new Exception('Not enough free game slots', Exception::WARNING);
        }

        $opponentName = $game->getPlayer1();
        $deckId = $request['accept_deck'];

        $deck = $this->service()->deck()->loadReadyDeck($deckId, $player->getUsername(), $game->getGameModes());

        $this->dbEntity()->player()->getPlayerAsserted($opponentName);

        // check if player can enter the game
        if ($game->getPlayer2() != $player->getUsername()) {
            throw new Exception('Only invited player may enter the game', Exception::WARNING);
        }

        // accept the challenge
        $serviceUseCard->startGame($game, $player->getUsername(), $deck);

        // process card statistics
        $cardStats = $serviceUseCard->getCardStats();
        if (count($cardStats) > 0) {
            $this->service()->statistic()->updateCardStats($cardStats);
        }

        $db->beginTransaction();

        // update game data
        if (!$game->save()) {
            $db->rollBack();
            throw new Exception('Game start failed');
        }

        // create matching replay
        $replay = $dbEntityReplay->createReplay($game);
        if (!$replay->save()) {
            $db->rollBack();
            throw new Exception('Failed to create replay');
        }

        // delete attached challenge
        $result = $dbEntityMessage->cancelChallenge($game->getGameId());
        if ($result->isErrorOrNoEffect()) {
            $db->rollBack();
            throw new Exception('Failed to cancel challenge');
        }

        $db->commit();

        $this->result()->setInfo('You have accepted a challenge from ' . $opponentName);
    }

    /**
     * Reject challenge
     * @throws Exception
     */
    protected function rejectChallenge()
    {
        $request = $this->request();

        $db = $this->getDb();
        $dbEntityMessage = $this->dbEntity()->message();

        $this->result()->setCurrent('Messages');

        $gameId = $request['reject_challenge'];
        $game = $this->dbEntity()->game()->getGameAsserted($gameId);

        // check if the game is a challenge (and not a game in progress)
        if ($game->getState() != 'waiting') {
            throw new Exception('Game already in progress!', Exception::WARNING);
        }

        // check if such opponent exists
        $opponentName = $game->getPlayer1();
        $this->dbEntity()->player()->getPlayerAsserted($opponentName);

        $db->beginTransaction();

        // delete attached challenge
        $result = $dbEntityMessage->cancelChallenge($game->getGameId());
        if ($result->isErrorOrNoEffect()) {
            $db->rollBack();
            throw new Exception('Failed to cancel challenge');
        }

        // delete game
        $game->markDeleted();
        if (!$game->save()) {
            $db->rollBack();
            throw new Exception('Failed to reject challenge');
        }

        $db->commit();

        $this->result()->setInfo('You have rejected a challenge');
    }

    /**
     * Challenge specific player
     * @throws Exception
     */
    protected function prepareChallenge()
    {
        $request = $this->request();

        // check access rights
        if (!$this->checkAccess('send_challenges')) {
            $this->result()->setCurrent('Players');
            throw new Exception('Access denied', Exception::WARNING);
        }

        // this is only used to assist the function below
        $this->result()
            ->changeRequest('Profile', Encode::postDecode($request['prepare_challenge']))
            ->setCurrent('Players_details');
    }

    /**
     * Send challenge
     * @throws Exception
     */
    protected function sendChallenge()
    {
        $request = $this->request();

        $player = $this->getCurrentPlayer();
        $db = $this->getDb();
        $dbEntityMessage = $this->dbEntity()->message();
        $dbEntityGame = $this->dbEntity()->game();

        $opponentName = Encode::postDecode($request['send_challenge']);
        $this->result()
            ->changeRequest('Profile', $opponentName)
            ->setCurrent('Players_details');

        // check access rights
        if (!$this->checkAccess('send_challenges')) {
            throw new Exception('Access denied', Exception::WARNING);
        }

        $this->assertParamsExist(['content']);
        $this->assertParamsNonEmpty(['challenge_deck']);

        // check challenge text length
        if (mb_strlen($request['content']) > MessageModel::CHALLENGE_LENGTH) {
            throw new Exception('Message too long', Exception::WARNING);
        }

        $deckId = $request['challenge_deck'];

        // set game modes
        $hiddenCards = (isset($request['hidden_cards']) ? 'yes' : 'no');
        $friendlyPlay = (isset($request['friendly_play']) ? 'yes' : 'no');
        $longMode = (isset($request['long_mode']) ? 'yes' : 'no');

        $gameModes = array();
        if ($hiddenCards == 'yes') {
            $gameModes[] = 'HiddenCards';
        }
        if ($friendlyPlay == 'yes') {
            $gameModes[] = 'FriendlyPlay';
        }
        if ($longMode == 'yes') {
            $gameModes[] = 'LongMode';
        }

        $deck = $this->service()->deck()->loadReadyDeck($deckId, $player->getUsername(), $gameModes);

        // check if such opponent exists
        $this->dbEntity()->player()->getPlayerAsserted($opponentName);

        // check if you are within the MAX_GAMES limit
        if ($this->service()->gameUtil()->countFreeSlots($player->getUsername()) == 0) {
            throw new Exception('Too many games / challenges! Please resolve some', Exception::WARNING);
        }

        $timeoutValues = GameModel::listTimeoutValues();
        $timeoutKeys = array_keys($timeoutValues);
        $turnTimeout = (isset($request['turn_timeout']) && in_array($request['turn_timeout'], $timeoutKeys)) ? $request['turn_timeout'] : 0;

        $challengeText = "Hide opponent's cards: " . $hiddenCards . "\n";
        $challengeText.= 'Friendly play: ' . $friendlyPlay . "\n";
        $challengeText.= 'Long mode: ' . $longMode . "\n";
        $challengeText.= 'Timeout: ' . $timeoutValues[$turnTimeout] . "\n";
        $challengeText.= $request['content'];

        // create a new challenge
        $db->beginTransaction();

        $game = $dbEntityGame->createGame($player->getUsername(), $opponentName, $deck, $gameModes, $turnTimeout);
        if (!$game->save()) {
            $db->rollBack();
            throw new Exception('Failed to create new game!');
        }

        $challenge = $dbEntityMessage->sendChallenge(
            $player->getUsername(), $opponentName, $challengeText, $game->getGameId()
        );
        if (!$challenge->save()) {
            $db->rollBack();
            throw new Exception('Failed to create new challenge!');
        }

        $db->commit();

        $this->result()->setInfo('You have challenged ' . $opponentName . '. Waiting for reply');
    }

    /**
     * Cancel challenge
     * @throws Exception
     */
    protected function withdrawChallenge()
    {
        $request = $this->request();

        $db = $this->getDb();
        $dbEntityMessage = $this->dbEntity()->message();

        $this->result()->setCurrent('Messages');
        $gameId = $request['withdraw_challenge'];

        $game = $this->dbEntity()->game()->getGameAsserted($gameId);

        // check if the game is a a challenge (and not a game in progress)
        if ($game->getState() != 'waiting') {
            throw new Exception('Game already in progress', Exception::WARNING);
        }

        $opponentName = $game->getPlayer2();
        $this->result()->changeRequest('Profile', $opponentName);

        // check if such opponent exists
        $this->dbEntity()->player()->getPlayerAsserted($opponentName);

        $db->beginTransaction();

        // delete attached challenge
        $result = $dbEntityMessage->cancelChallenge($game->getGameId());
        if ($result->isErrorOrNoEffect()) {
            $db->rollBack();
            throw new Exception('Failed to cancel challenge');
        }

        // delete game
        $game->markDeleted();
        if (!$game->save()) {
            $db->rollBack();
            throw new Exception('Failed to withdraw challenge');
        }

        $db->commit();

        // stay in "Outgoing" subsection
        $this->result()
            ->changeRequest('outgoing', 'outgoing')
            ->setInfo('You have withdrawn a challenge');
    }
}
