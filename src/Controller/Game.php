<?php
/**
 * Game - game related controller
 */

namespace Controller;

use ArcomageException as Exception;
use Db\Model\Player as PlayerModel;
use Util\Date;
use Util\Input;

class Game extends ControllerAbstract
{
    /**
     * Next game button
     * @throws Exception
     */
    protected function nextGame()
    {
        $request = $this->request();

        $player = $this->getCurrentPlayer();

        $this->result()->setCurrent('Games');

        $this->assertParamsNonEmpty(['current_game']);

        // find next game id
        $gameId = $this->service()->gameUtil()->findNextGame($player->getUsername(), $request['current_game']);

        $this->result()
            ->changeRequest('current_game', $gameId)
            ->setCurrent('Games_details');
    }

    /**
     * Save current's player game note
     * @throws Exception
     */
    protected function saveGameNote()
    {
        $request = $this->request();

        $player = $this->getCurrentPlayer();

        $this->result()->setCurrent('Games');

        $this->assertParamsNonEmpty(['current_game']);
        $gameId = $request['current_game'];

        $game = $this->dbEntity()->game()->getGameAsserted($gameId);

        // check if this user is allowed to perform game actions
        if ($player->getUsername() != $game->getPlayer1() && $player->getUsername() != $game->getPlayer2()) {
            throw new Exception('Access denied', Exception::WARNING);
        }

        $this->result()->setCurrent('Games_note');

        $this->assertParamsExist(['content']);

        $this->service()->gameUtil()->saveNote($player->getUsername(), $game, $request['content']);

        $this->result()->setInfo('Game note saved');
    }

    /**
     * Save current's player game note and return to game screen
     * @throws Exception
     */
    protected function saveGameNoteReturn()
    {
        $request = $this->request();

        $player = $this->getCurrentPlayer();

        $this->result()->setCurrent('Games');

        $this->assertParamsNonEmpty(['current_game']);
        $gameId = $request['current_game'];

        $game = $this->dbEntity()->game()->getGameAsserted($gameId);

        // check if this user is allowed to view this game
        if ($player->getUsername() != $game->getPlayer1() && $player->getUsername() != $game->getPlayer2()) {
            throw new Exception('Access denied', Exception::WARNING);
        }

        // disable re-visiting
        if (($player->getUsername() == $game->getPlayer1() && $game->getState() == 'P1 over')
            || ($player->getUsername() == $game->getPlayer2() && $game->getState() == 'P2 over')) {
            throw new Exception('Game already over', Exception::WARNING);
        }

        $this->result()->setCurrent('Games_note');

        $this->assertParamsExist(['content']);

        $this->service()->gameUtil()->saveNote($player->getUsername(), $game, $request['content']);

        $this->result()
            ->setInfo('Game note saved')
            ->setCurrent('Games_details');
    }

    /**
     * Clear current's player game note
     * @throws Exception
     */
    protected function clearGameNote()
    {
        $request = $this->request();

        $player = $this->getCurrentPlayer();

        $this->result()->setCurrent('Games');

        $this->assertParamsNonEmpty(['current_game']);
        $gameId = $request['current_game'];

        $game = $this->dbEntity()->game()->getGameAsserted($gameId);

        // check if this user is allowed to perform game actions
        if ($player->getUsername() != $game->getPlayer1() && $player->getUsername() != $game->getPlayer2()) {
            throw new Exception('Access denied', Exception::WARNING);
        }

        $this->result()->setCurrent('Games_note');

        $this->service()->gameUtil()->saveNote($player->getUsername(), $game, '');

        $this->result()->setInfo('Game note cleared');
    }

    /**
     * Clear current's player game note and return to game screen
     * @throws Exception
     */
    protected function clearGameNoteReturn()
    {
        $request = $this->request();

        $player = $this->getCurrentPlayer();

        $this->result()->setCurrent('Games');

        $this->assertParamsNonEmpty(['current_game']);
        $gameId = $request['current_game'];

        $game = $this->dbEntity()->game()->getGameAsserted($gameId);

        // check if this user is allowed to perform game actions
        if ($player->getUsername() != $game->getPlayer1() && $player->getUsername() != $game->getPlayer2()) {
            throw new Exception('Access denied', Exception::WARNING);
        }

        // disable re-visiting
        if (($player->getUsername() == $game->getPlayer1() && $game->getState() == 'P1 over')
            || ($player->getUsername() == $game->getPlayer2() && $game->getState() == 'P2 over')) {
            throw new Exception('Game already over', Exception::WARNING);
        }

        $this->result()->setCurrent('Games_note');

        $this->service()->gameUtil()->saveNote($player->getUsername(), $game, '');

        $this->result()
            ->setCurrent('Games_details')
            ->setInfo('Game note cleared');
    }

    /**
     * Send chat message
     * @throws Exception
     */
    protected function sendMessage()
    {
        $request = $this->request();

        $player = $this->getCurrentPlayer();

        $this->result()->setCurrent('Games');

        $this->assertParamsNonEmpty(['current_game']);
        $gameId = $request['current_game'];

        $game = $this->dbEntity()->game()->getGameAsserted($gameId);

        // check if this user is allowed to send messages in this game
        if ($player->getUsername() != $game->getPlayer1() && $player->getUsername() != $game->getPlayer2()) {
            throw new Exception('Access denied', Exception::WARNING);
        }

        $this->result()->setCurrent('Games_details');

        $this->assertParamsExist(['chat_message']);

        // check access rights
        if (!$this->checkAccess('chat')) {
            throw new Exception('Access denied', Exception::WARNING);
        }

        $this->service()->gameUtil()->sendChatMessage($player->getUsername(), $game, $request['chat_message']);
    }

    /**
     * Play card within the game
     * @throws Exception
     */
    protected function playCard()
    {
        $request = $this->request();

        $player = $this->getCurrentPlayer();

        $this->result()->setCurrent('Games');

        $this->assertParamsNonEmpty(['current_game']);
        $gameId = $request['current_game'];

        $game = $this->dbEntity()->game()->getGameAsserted($gameId);

        // check if this user is allowed to perform game actions
        if ($player->getUsername() != $game->getPlayer1() && $player->getUsername() != $game->getPlayer2()) {
            throw new Exception('Access denied', Exception::WARNING);
        }

        $this->result()->setCurrent('Games_details');

        // case 1: local play card button was used
        if (is_numeric($request['play_card']) && $request['play_card'] > 0) {
            $cardPos = $request['play_card'];
        }
        // case 2: global play/discard card button was used
        elseif (isset($request['selected_card']) && is_numeric($request['selected_card']) && $request['selected_card'] > 0) {
            $cardPos = $request['selected_card'];
        }
        // case 3: invalid user input
        else {
            throw new Exception('Invalid card selection input', Exception::WARNING);
        }

        // determine card mode
        $mode = (isset($request['card_mode'][$cardPos]) && is_numeric($request['card_mode'][$cardPos]))
            ? $request['card_mode'][$cardPos] : 0;

        // current round holds data about round continuity and prevents accidental turn executions
        $currentRound = (isset($request['current_round']) && is_numeric($request['current_round']))
            ? $request['current_round'] : 0;

        // execute game turn
        $levelUp = $this->service()->gameTurn()->executeGameTurn(
            $player->getUsername(), $game, 'play', $cardPos, $mode, $currentRound
        );

        // pass level-up flag if necessary
        if ($levelUp > 0) {
            $this->result()->setLevelUp($levelUp);
        }

        $this->result()->setInfo('You have played a card');
    }

    /**
     * Discard card within the game
     * @throws Exception
     */
    protected function discardCard()
    {
        $request = $this->request();

        $player = $this->getCurrentPlayer();

        $this->result()->setCurrent('Games');

        $this->assertParamsNonEmpty(['current_game']);
        $gameId = $request['current_game'];

        $game = $this->dbEntity()->game()->getGameAsserted($gameId);

        // check if this user is allowed to perform game actions
        if ($player->getUsername() != $game->getPlayer1() && $player->getUsername() != $game->getPlayer2()) {
            throw new Exception('Access denied', Exception::WARNING);
        }

        $this->result()->setCurrent('Games_details');

        // validate card selection
        $this->assertParamsNonEmpty(['selected_card']);
        if (!is_numeric($request['selected_card']) || $request['selected_card'] <= 0) {
            throw new Exception('Invalid card selection input', Exception::WARNING);
        }

        // current round holds data about round continuity and prevents accidental turn executions
        $currentRound = (isset($request['current_round']) && is_numeric($request['current_round']))
            ? $request['current_round'] : 0;

        // execute game turn
        $levelUp = $this->service()->gameTurn()->executeGameTurn(
            $player->getUsername(), $game, 'discard', $request['selected_card'], 0, $currentRound
        );

        // pass level-up flag if necessary
        if ($levelUp > 0) {
            $this->result()->setLevelUp($levelUp);
        }

        $this->result()->setInfo('You have discarded a card');
    }

    /**
     * Preview card within the game
     * @throws Exception
     */
    protected function previewCard()
    {
        $request = $this->request();

        $player = $this->getCurrentPlayer();

        $this->result()->setCurrent('Games');

        $this->assertParamsNonEmpty(['current_game']);
        $gameId = $request['current_game'];

        $game = $this->dbEntity()->game()->getGameAsserted($gameId);

        // check if this user is allowed to perform game actions
        if ($player->getUsername() != $game->getPlayer1() && $player->getUsername() != $game->getPlayer2()) {
            throw new Exception('Access denied', Exception::WARNING);
        }

        $this->result()->setCurrent('Games_details');

        $cardPos = $request['preview_card'];

        // determine card mode
        $mode = (isset($request['card_mode'][$cardPos]) && is_numeric($request['card_mode'][$cardPos]))
            ? $request['card_mode'][$cardPos] : 0;

        // validate game mode
        if ($game->checkGameMode('HiddenCards')) {
            throw new Exception('Action not allowed in this game mode', Exception::WARNING);
        }

        // check if game is locked in a surrender request
        if ($game->getSurrender() != '') {
            throw new Exception('Game is locked in a surrender request', Exception::WARNING);
        }

        // execute game turn
        $this->service()->gameUseCard()->useCard(
            $game, $player->getUsername(), 'play', $cardPos, $mode
        );

        // we do not save game data intentionally

        $this->result()->setCurrent('Games_preview');
    }

    /**
     * Execute AI move
     * @throws Exception
     */
    protected function aiMove()
    {
        $request = $this->request();

        $player = $this->getCurrentPlayer();

        $this->result()->setCurrent('Games');

        $this->assertParamsNonEmpty(['current_game']);
        $gameId = $request['current_game'];

        $game = $this->dbEntity()->game()->getGameAsserted($gameId);

        // check if this user is allowed to perform game actions
        if ($player->getUsername() != $game->getPlayer1() && $player->getUsername() != $game->getPlayer2()) {
            throw new Exception('Access denied', Exception::WARNING);
        }

        $this->result()->setCurrent('Games_details');

        // execute AI game turn
        $this->service()->gameTurn()->executeAiGameTurn($player->getUsername(), $game);

        $this->result()->setInfo('AI move executed');
    }

    /**
     * Make AI move instead of opponent
     * @throws Exception
     */
    protected function finishMove()
    {
        $request = $this->request();

        $player = $this->getCurrentPlayer();

        // an option to play turn instead of opponent when opponent refuses to play
        // applies only to games where opponent didn't take action for more then timeout if timeout was set for specified game

        $this->result()->setCurrent('Games');

        $this->assertParamsNonEmpty(['current_game']);
        $gameId = $request['current_game'];

        $game = $this->dbEntity()->game()->getGameAsserted($gameId);

        // check if this user can interact with this game
        if ($player->getUsername() != $game->getPlayer1() && $player->getUsername() != $game->getPlayer2()) {
            throw new Exception('Access denied', Exception::WARNING);
        }

        $this->result()->setCurrent('Games_details');

        $levelUp = $this->service()->gameTurn()->makeSubstituteAiMove($player->getUsername(), $game);

        // pass level-up flag if necessary
        if ($levelUp > 0) {
            $this->result()->setLevelUp($levelUp);
        }

        $this->result()->setInfo("Opponent's move executed");
    }

    /**
     * Surrender -> send surrender request to opponent
     * @throws Exception
     */
    protected function initiateSurrender()
    {
        $request = $this->request();

        $player = $this->getCurrentPlayer();

        $this->result()->setCurrent('Games');

        $this->assertParamsNonEmpty(['current_game']);
        $gameId = $request['current_game'];

        $game = $this->dbEntity()->game()->getGameAsserted($gameId);

        // check if this user is allowed to surrender in this game
        if ($player->getUsername() != $game->getPlayer1() && $player->getUsername() != $game->getPlayer2()) {
            throw new Exception('Access denied', Exception::WARNING);
        }

        $this->result()->setCurrent('Games_details');

        // only allow to request for surrender if the game is still on
        if ($game->getState() != 'in progress' || $game->getSurrender() != '') {
            throw new Exception('Action not allowed!', Exception::WARNING);
        }

        // set surrender initiator
        $game->setSurrender($player->getUsername());
        $this->result()->setInfo('Surrender request sent');

        // accept surrender request in case of AI game
        if ($game->checkGameMode('AIMode')) {
            // finish game via surrender
            $game
                ->setState('finished')
                ->setWinner(($game->getPlayer1() == $game->getSurrender()) ? $game->getPlayer2() : $game->getPlayer1())
                ->setOutcomeType('Surrender');

            $this->service()->gameUtil()->saveGameWithReplay($game);

            // update deck statistics
            $this->service()->deck()->updateDeckStatistics(
                $game->getPlayer1(), $game->getPlayer2(), $game->getDeckId1(), $game->getDeckId2(), $game->getWinner()
            );
            $this->result()->setInfo('Surrender request accepted');
        }

        if (!$game->save()) {
            throw new Exception('Failed to save game data');
        }
    }

    /**
     * Surrender -> cancel surrender request to opponent
     * @throws Exception
     */
    protected function cancelSurrender()
    {
        $request = $this->request();

        $player = $this->getCurrentPlayer();

        $this->result()->setCurrent('Games');

        $this->assertParamsNonEmpty(['current_game']);
        $gameId = $request['current_game'];

        $game = $this->dbEntity()->game()->getGameAsserted($gameId);

        // check if this user is allowed to cancel surrender in this game
        if ($player->getUsername() != $game->getSurrender()) {
            throw new Exception('Player is not allowed to cancel surrender', Exception::WARNING);
        }

        $this->result()->setCurrent('Games_details');

        // only allow to cancel surrender request if the game is still on
        if ($game->getState() != 'in progress' || $game->getSurrender() == '') {
            throw new Exception('Action not allowed!', Exception::WARNING);
        }

        // remove surrender initiator
        $game->setSurrender('');
        if (!$game->save()) {
            throw new Exception('Failed to save game data');
        }

        $this->result()->setInfo('Surrender request cancelled');
    }

    /**
     * Surrender -> reject surrender request from opponent
     * @throws Exception
     */
    protected function rejectSurrender()
    {
        $request = $this->request();

        $player = $this->getCurrentPlayer();

        $this->result()->setCurrent('Games');

        $this->assertParamsNonEmpty(['current_game']);
        $gameId = $request['current_game'];

        $game = $this->dbEntity()->game()->getGameAsserted($gameId);

        // check if this user is allowed to reject surrender in this game
        if (($player->getUsername() != $game->getPlayer1() && $player->getUsername() != $game->getPlayer2())
            || $player->getUsername() == $game->getSurrender()) {
            throw new Exception('Player is not allowed to reject surrender', Exception::WARNING);
        }

        $this->result()->setCurrent('Games_details');

        // only allow to cancel surrender request if the game is still on
        if ($game->getState() != 'in progress' || $game->getSurrender() == '') {
            throw new Exception('Action not allowed!', Exception::WARNING);
        }

        // remove surrender initiator
        $game->setSurrender('');
        if (!$game->save()) {
            throw new Exception('Failed to save game data');
        }

        $this->result()->setInfo('Surrender request rejected');
    }

    /**
     * Surrender -> accept surrender from opponent
     * @throws Exception
     */
    protected function acceptSurrender()
    {
        $request = $this->request();

        $player = $this->getCurrentPlayer();

        $this->result()->setCurrent('Games');

        $this->assertParamsNonEmpty(['current_game']);
        $gameId = $request['current_game'];

        $game = $this->dbEntity()->game()->getGameAsserted($gameId);

        // check if this user is allowed to accept surrender in this game
        if (($player->getUsername() != $game->getPlayer1() && $player->getUsername() != $game->getPlayer2())
            || $player->getUsername() == $game->getSurrender()) {
            throw new Exception('Player is not allowed to accept surrender', Exception::WARNING);
        }

        $this->result()->setCurrent('Games_details');

        // only allow surrender if the game is still on
        if ($game->getState() != 'in progress' || $game->getSurrender() == '') {
            throw new Exception('Action not allowed!', Exception::WARNING);
        }

        // finish game via surrender
        $game
            ->setState('finished')
            ->setWinner(($game->getPlayer1() == $game->getSurrender()) ? $game->getPlayer2() : $game->getPlayer1())
            ->setOutcomeType('Surrender');

        $this->service()->gameUtil()->saveGameWithReplay($game);

        // update deck statistics
        $this->service()->deck()->updateDeckStatistics(
            $game->getPlayer1(), $game->getPlayer2(), $game->getDeckId1(), $game->getDeckId2(), $game->getWinner()
        );

        // process game finish in case of non friendly play game
        if (!$game->checkGameMode('FriendlyPlay')) {
            $levelUp = $this->service()->gameTurn()->gameFinishProcessing($player->getUsername(), $game);

            // pass level-up flag if necessary
            if ($levelUp > 0) {
                $this->result()->setLevelUp($levelUp);
            }
        }

        $this->result()->setInfo('Surrender request accepted');
    }

    /**
     * Abort game
     * @throws Exception
     */
    protected function abortGame()
    {
        $request = $this->request();

        $player = $this->getCurrentPlayer();

        // an option to end the game without hurting your score
        // applies only to games against 'dead' players (abandoned games)

        $this->result()->setCurrent('Games');

        $this->assertParamsNonEmpty(['current_game']);
        $gameId = $request['current_game'];

        $game = $this->dbEntity()->game()->getGameAsserted($gameId);

        // check if this user is allowed to abort this game
        if ($player->getUsername() != $game->getPlayer1() && $player->getUsername() != $game->getPlayer2()) {
            throw new Exception('Access denied', Exception::WARNING);
        }

        $opponentName = ($game->getPlayer1() == $player->getUsername()) ? $game->getPlayer2() : $game->getPlayer1();
        $opponent = $this->dbEntity()->player()->getPlayerAsserted($opponentName);

        $this->result()->setCurrent('Games_details');

        // only allow aborting abandoned games
        if (!$opponent->isDead()) {
            throw new Exception('Action not allowed!', Exception::WARNING);
        }

        // only allow surrender if the game is still on
        if ($game->getState() != 'in progress') {
            throw new Exception('Action not allowed!', Exception::WARNING);
        }

        // finish game via abort
        $game
            ->setState('finished')
            ->setWinner('')
            ->setOutcomeType('Abort');

        $this->service()->gameUtil()->saveGameWithReplay($game);
    }

    /**
     * Finish game
     * @throws Exception
     */
    protected function finishGame()
    {
        $request = $this->request();

        $player = $this->getCurrentPlayer();

        // an option to end the game when opponent refuses to play
        // applies only to games against non-'dead' players, when opponent didn't take action for more then 3 weeks

        $this->result()->setCurrent('Games');

        $this->assertParamsNonEmpty(['current_game']);
        $gameId = $request['current_game'];

        $game = $this->dbEntity()->game()->getGameAsserted($gameId);

        // check if this user is allowed to abort this game
        if ($player->getUsername() != $game->getPlayer1() && $player->getUsername() != $game->getPlayer2()) {
            throw new Exception('Access denied', Exception::WARNING);
        }

        $opponentName = ($game->getPlayer1() == $player->getUsername()) ? $game->getPlayer2() : $game->getPlayer1();
        $opponent = $this->dbEntity()->player()->getPlayerAsserted($opponentName);

        $this->result()->setCurrent('Games_details');

        // only allow finishing active games
        if ($opponent->isDead()) {
            throw new Exception('Action not allowed!', Exception::WARNING);
        }

        // and only if the abort criteria are met
        if (time() - Date::strToTime($game->getLastAction()) < 3 * Date::WEEK || $game->getCurrent() == $player->getUsername()) {
            throw new Exception('Action not allowed!', Exception::WARNING);
        }

        // only allow finishing of non-AI games
        if ($game->getPlayer2() == PlayerModel::SYSTEM_NAME) {
            throw new Exception('Action not allowed!', Exception::WARNING);
        }

        // only allow surrender if the game is still on
        if ($game->getState() != 'in progress') {
            throw new Exception('Action not allowed!', Exception::WARNING);
        }

        // finish game via finish
        $game
            ->setState('finished')
            ->setWinner($player->getUsername())
            ->setOutcomeType('Abandon');

        $this->service()->gameUtil()->saveGameWithReplay($game);

        // update deck statistics
        $this->service()->deck()->updateDeckStatistics(
            $game->getPlayer1(), $game->getPlayer2(), $game->getDeckId1(), $game->getDeckId2(), $game->getWinner()
        );

        // process game finish in case of non friendly play game
        if (!$game->checkGameMode('FriendlyPlay')) {
            $levelUp = $this->service()->gameTurn()->gameFinishProcessing($player->getUsername(), $game);

            // pass level-up flag if necessary
            if ($levelUp > 0) {
                $this->result()->setLevelUp($levelUp);
            }
        }
    }

    /**
     * Leave the game
     * @throws Exception
     */
    protected function leaveGame()
    {
        $request = $this->request();

        $player = $this->getCurrentPlayer();

        $this->result()->setCurrent('Games');

        $this->assertParamsNonEmpty(['current_game']);
        $gameId = $request['current_game'];

        $game = $this->dbEntity()->game()->getGameAsserted($gameId);

        // disable re-visiting (or the player would set this twice >_>)
        if (($player->getUsername() == $game->getPlayer1() && $game->getState() == 'P1 over')
            || ($player->getUsername() == $game->getPlayer2() && $game->getState() == 'P2 over')) {
            throw new Exception('Game is already over', Exception::WARNING);
        }

        $this->result()->setCurrent('Games_details');

        $this->service()->gameManagement()->leaveGame($player->getUsername(), $game);

        $this->result()->setCurrent('Games');
    }

    /**
     * Host game
     * @throws Exception
     */
    protected function hostGame()
    {
        $request = $this->request();

        $player = $this->getCurrentPlayer();

        $this->result()
            ->changeRequest('subsection', 'hosted_games')
            ->setCurrent('Games');

        // check access rights
        if (!$this->checkAccess('send_challenges')) {
            throw new Exception('Access denied', Exception::WARNING);
        }

        $this->assertParamsNonEmpty(['selected_deck']);

        // set game modes
        $gameModes = array();
        if (isset($request['hidden_mode'])) {
            $gameModes[] = 'HiddenCards';
        }
        if (isset($request['friendly_mode'])) {
            $gameModes[] = 'FriendlyPlay';
        }
        if (isset($request['long_mode'])) {
            $gameModes[] = 'LongMode';
        }

        $turnTimeout = Input::defaultValue($request, 'turn_timeout', 0);
        $deckId = $request['selected_deck'];

        $this->service()->gameManagement()->hostGame($player->getUsername(), $deckId, $gameModes, $turnTimeout);

        $this->result()->setInfo('Game created. Waiting for opponent to join');
    }

    /**
     * Leave the game
     * @throws Exception
     */
    protected function unhostGame()
    {
        $request = $this->request();

        $this->result()
            ->changeRequest('subsection', 'hosted_games')
            ->setCurrent('Games');

        $gameId = $request['unhost_game'];

        $game = $this->dbEntity()->game()->getGameAsserted($gameId);

        // check if the game is a a challenge (and not a game in progress)
        if ($game->getState() != 'waiting') {
            throw new Exception('Game already in progress!', Exception::WARNING);
        }

        // delete game entry
        $game->markDeleted();
        if (!$game->save()) {
            throw new Exception('Failed to delete game');
        }

        $this->result()->setInfo('You have canceled a game');
    }

    /**
     * Join game
     * @throws Exception
     */
    protected function joinGame()
    {
        $request = $this->request();

        $player = $this->getCurrentPlayer();

        $this->result()
            ->changeRequest('subsection', 'free_games')
            ->setCurrent('Games');

        // check access rights
        if (!$this->checkAccess('accept_challenges')) {
            throw new Exception('Access denied', Exception::WARNING);
        }

        $this->assertParamsNonEmpty(['selected_deck']);

        $gameId = $request['join_game'];
        $game = $this->dbEntity()->game()->getGameAsserted($gameId);

        $deckId = $request['selected_deck'];

        $this->service()->gameManagement()->joinGame($player->getUsername(), $deckId, $game);

        $this->result()->setInfo('You have joined ' . $game->getPlayer1() . "'s game");
    }

    /**
     * Create AI game
     * @throws Exception
     */
    protected function aiGame()
    {
        $request = $this->request();

        $player = $this->getCurrentPlayer();

        $this->result()
            ->changeRequest('subsection', 'ai_games')
            ->setCurrent('Games');

        // check access rights
        if (!$this->checkAccess('send_challenges')) {
            throw new Exception('Access denied', Exception::WARNING);
        }

        $this->assertParamsNonEmpty(['selected_deck', 'selected_ai_deck']);

        // set game modes
        $gameModes = array();
        if (isset($request['hidden_mode'])) {
            $gameModes[] = 'HiddenCards';
        }
        if (isset($request['long_mode'])) {
            $gameModes[] = 'LongMode';
        }

        $this->service()->gameManagement()->startAiGame(
            $player->getUsername(), $request['selected_deck'], $request['selected_ai_deck'], $gameModes
        );

        $this->result()
            ->setInfo('Game vs AI created')
            ->setCurrent('Games');
    }

    /**
     * Create AI challenge game
     * @throws Exception
     */
    protected function aiChallenge()
    {
        $request = $this->request();

        $player = $this->getCurrentPlayer();

        $this->result()
            ->changeRequest('subsection', 'ai_games')
            ->setCurrent('Games');

        // check access rights
        if (!$this->checkAccess('send_challenges')) {
            throw new Exception('Access denied', Exception::WARNING);
        }

        $this->assertParamsNonEmpty(['selected_deck', 'selected_challenge']);

        $this->service()->gameManagement()->startAiChallenge(
            $player->getUsername(), $request['selected_deck'], $request['selected_challenge']
        );

        $this->result()
            ->setInfo('AI challenge created')
            ->setCurrent('Games');
    }

    /**
     * Create quick AI game
     * @throws Exception
     */
    protected function quickGame()
    {
        $request = $this->request();

        $player = $this->getCurrentPlayer();

        $this->result()->setCurrent('Games');

        // check access rights
        if (!$this->checkAccess('send_challenges')) {
            throw new Exception('Access denied', Exception::WARNING);
        }

        $this->assertParamsNonEmpty(['selected_deck']);

        $deckId = $request['selected_deck'];

        $game = $this->service()->gameManagement()->startAiGame($player->getUsername(), $deckId, 'starter_deck', []);

        $this->result()
            ->changeRequest('current_game', $game->getGameId())
            ->setInfo('Game vs AI created')
            ->setCurrent('Games_details');
    }

    /**
     * Use filter in hosted games view
     */
    protected function filterHostedGames()
    {
        $this->result()
            ->changeRequest('subsection', 'free_games')
            ->setCurrent('Games');
    }

    /**
     * cheats: put card into a game on specific position
     * @throws Exception
     */
    protected function putCard()
    {
        $request = $this->request();

        $player = $this->getCurrentPlayer();

        $this->result()->setCurrent('Games');

        $this->assertParamsNonEmpty(['current_game']);
        $gameId = $request['current_game'];

        $game = $this->dbEntity()->game()->getGameAsserted($gameId);

        // check if this user is allowed to perform game actions
        if ($player->getUsername() != $game->getPlayer1() && $player->getUsername() != $game->getPlayer2()) {
            throw new Exception('Access denied', Exception::WARNING);
        }

        $this->result()->setCurrent('Games_details');

        $this->assertParamsNonEmpty(['target_player', 'card_id', 'card_pos']);

        $this->service()->gameCheat()->cheatPutCard(
            $player->getUsername(), $game, $request['target_player'], $request['card_id'], $request['card_pos']
        );

        $this->result()->setInfo('Card added to game');
    }

    /**
     * cheats: change a game attribute
     * @throws Exception
     */
    protected function changeAttribute()
    {
        $request = $this->request();

        $player = $this->getCurrentPlayer();

        $this->assertParamsNonEmpty(['current_game']);
        $gameId = $request['current_game'];

        $game = $this->dbEntity()->game()->getGameAsserted($gameId);

        // check if this user is allowed to perform game actions
        if ($player->getUsername() != $game->getPlayer1() && $player->getUsername() != $game->getPlayer2()) {
            throw new Exception('Access denied', Exception::WARNING);
        }

        $this->result()->setCurrent('Games_details');

        $this->assertParamsNonEmpty(['target_player', 'target_change']);
        $this->assertParamsExist(['target_value']);

        $this->service()->gameCheat()->cheatChangeAttribute(
            $player->getUsername(), $game, $request['target_player'], $request['target_change'], $request['target_value']
        );

        $this->result()->setInfo('Game attribute changed');
    }

    /**
     * Execute AI move by specifying its exact move
     * @throws Exception
     */
    protected function customAiMove()
    {
        $request = $this->request();

        $player = $this->getCurrentPlayer();

        $this->result()->setCurrent('Games');

        $this->assertParamsNonEmpty(['current_game']);
        $gameId = $request['current_game'];

        $game = $this->dbEntity()->game()->getGameAsserted($gameId);

        // check if this user is allowed to perform game actions
        if ($player->getUsername() != $game->getPlayer1() && $player->getUsername() != $game->getPlayer2()) {
            throw new Exception('Access denied', Exception::WARNING);
        }

        $this->result()->setCurrent('Games_details');

        $this->assertParamsNonEmpty(['card_pos', 'ai_action']);
        $this->assertParamsExist(['ai_card_mode']);

        // fetch player's level
        $score = $this->dbEntity()->score()->getScoreAsserted($player->getUsername());

        $playerLevel = $score->getLevel();

        // validate player's level
        if ($playerLevel < PlayerModel::TUTORIAL_END) {
            throw new Exception('Action not allowed while in tutorial', Exception::WARNING);
        }

        // execute AI game turn
        $this->service()->gameTurn()->executeAiGameTurn($player->getUsername(), $game, [
            'cardpos' => $request['card_pos'],
            'mode' => $request['ai_card_mode'],
            'action' => $request['ai_action'],
        ]);

        $this->result()->setInfo('Custom AI move executed');
    }

    /**
     * cheats: change game mode
     * @throws Exception
     */
    protected function changeGameMode()
    {
        $request = $this->request();

        $player = $this->getCurrentPlayer();

        $this->assertParamsNonEmpty(['current_game']);
        $gameId = $request['current_game'];

        $game = $this->dbEntity()->game()->getGameAsserted($gameId);

        // check if this user is allowed to perform game actions
        if ($player->getUsername() != $game->getPlayer1() && $player->getUsername() != $game->getPlayer2()) {
            throw new Exception('Access denied', Exception::WARNING);
        }

        $this->result()->setCurrent('Games_details');

        $this->service()->gameCheat()->cheatChangeGameMode($player->getUsername(), $game);

        $this->result()->setInfo('Game mode changed');
    }
}
