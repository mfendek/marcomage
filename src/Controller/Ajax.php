<?php
/**
 * Ajax
 */

namespace Controller;

use ArcomageException as Exception;
use Service\GameUtil;

class Ajax extends ControllerAbstract
{
    /**
     * Transfer card from card pool to deck
     * @throws Exception
     */
    protected function takeCard()
    {
        $dic = $this->getDic();
        $request = $this->request();

        $player = $this->getCurrentPlayer();

        $this->assertParamsNonEmpty(['deck_id', 'card_id']);

        $deckId = $request['deck_id'];
        $cardId = $request['card_id'];

        // load deck
        $deck = $this->dbEntity()->deck()->getDeckAsserted($deckId);

        // validate deck ownership
        if ($deck->getUsername() != $player->getUsername()) {
            throw new Exception('Can only manipulate own deck', Exception::WARNING);
        }

        $slot = $this->service()->deck()->addCard($deck, $cardId);

        // recalculate the average cost per turn label
        $avgData = $this->service()->deck()->avgCostPerTurn($deck);

        $html = $dic->view()->renderTemplateWithoutLayout('Cards_lookup', ['card' => $cardId]);

        $this->result()->setData([
            'slot' => $slot,
            'tokens' => $deck->getData()->Tokens,
            'avg' => array_values($avgData),
            'taken_card' => $html,
        ]);
    }

    /**
     * Transfer card from deck to card pool
     * @throws Exception
     */
    protected function removeCard()
    {
        $dic = $this->getDic();
        $request = $this->request();

        $player = $this->getCurrentPlayer();

        $this->assertParamsNonEmpty(['deck_id', 'card_id']);

        $deckId = $request['deck_id'];
        $cardId = $request['card_id'];

        // load deck
        $deck = $this->dbEntity()->deck()->getDeckAsserted($deckId);

        // validate deck ownership
        if ($deck->getUsername() != $player->getUsername()) {
            throw new Exception('Can only manipulate own deck', Exception::WARNING);
        }

        $slot = $this->service()->deck()->removeCard($deck, $cardId);

        // recalculate the average cost per turn label
        $avgData = $this->service()->deck()->avgCostPerTurn($deck);

        // render empty card slot
        $html = $dic->view()->renderTemplateWithoutLayout('Cards_lookup', ['card' => 0]);

        $this->result()->setData([
            'slot' => $slot,
            'avg' => array_values($avgData),
            'slot_html' => $html,
        ]);
    }

    /**
     * Generate card preview
     * @throws Exception
     */
    protected function previewCard()
    {
        $request = $this->request();

        $player = $this->getCurrentPlayer();

        $this->assertParamsNonEmpty(['cardpos', 'game_id']);

        $cardPos = $request['cardpos'];
        $mode = (isset($request['mode']) && $request['mode'] != '') ? $request['mode'] : 0;
        $gameId = $request['game_id'];

        // download game
        $game = $this->dbEntity()->game()->getGameAsserted($gameId);

        // validate access
        if ($player->getUsername() != $game->getPlayer1() && $player->getUsername() != $game->getPlayer2()) {
            throw new Exception('Action not allowed', Exception::WARNING);
        }

        // verify inputs
        if (!is_numeric($cardPos)) {
            throw new Exception('Invalid card position', Exception::WARNING);
        }
        if (!is_numeric($mode)) {
            throw new Exception('Invalid mode', Exception::WARNING);
        }

        // validate game mode
        if ($game->checkGameMode('HiddenCards')) {
            throw new Exception('Action not allowed in this game mode', Exception::WARNING);
        }

        // check if game is locked in a surrender request
        if ($game->getSurrender() != '') {
            throw new Exception('Game is locked in a surrender request', Exception::WARNING);
        }

        $previewData = $this->service()->gameUseCard()->useCard(
            $game, $player->getUsername(), 'preview', $cardPos, $mode
        );
        if (isset($previewData['error'])) {
            throw new Exception($previewData['error'], Exception::WARNING);
        }

        $this->result()->setData([
            'info' => GameUtil::formatPreview($previewData['p_data']),
        ]);
    }

    /**
     * Save game note
     * @throws Exception
     */
    protected function saveGameNote()
    {
        $request = $this->request();

        $player = $this->getCurrentPlayer();

        $this->assertParamsExist(['note']);
        $this->assertParamsNonEmpty(['game_id']);

        $note = $request['note'];
        $gameId = $request['game_id'];

        // download game
        $game = $this->dbEntity()->game()->getGameAsserted($gameId);

        // validate access
        if ($player->getUsername() != $game->getPlayer1() && $player->getUsername() != $game->getPlayer2()) {
            throw new Exception('Action not allowed', Exception::WARNING);
        }

        $this->service()->gameUtil()->saveNote($player->getUsername(), $game, $note);

        $this->result()->setData([
            'info' => 'Game note saved',
        ]);
    }

    /**
     * Clear game note
     * @throws Exception
     */
    protected function clearGameNote()
    {
        $request = $this->request();

        $player = $this->getCurrentPlayer();

        $this->assertParamsNonEmpty(['game_id']);

        $gameId = $request['game_id'];

        // download game
        $game = $this->dbEntity()->game()->getGameAsserted($gameId);

        // validate access
        if ($player->getUsername() != $game->getPlayer1() && $player->getUsername() != $game->getPlayer2()) {
            throw new Exception('Action not allowed', Exception::WARNING);
        }

        $this->service()->gameUtil()->saveNote($player->getUsername(), $game, '');

        $this->result()->setData([
            'info' => 'Game note cleared',
        ]);
    }

    /**
     * Save deck note
     * @throws Exception
     */
    protected function saveDeckNote()
    {
        $request = $this->request();

        $player = $this->getCurrentPlayer();

        $this->assertParamsExist(['note']);
        $this->assertParamsNonEmpty(['deck_id']);

        $note = $request['note'];
        $deckId = $request['deck_id'];

        // load deck
        $deck = $this->dbEntity()->deck()->getDeckAsserted($deckId);

        // validate deck ownership
        if ($deck->getUsername() != $player->getUsername()) {
            throw new Exception('Can only manipulate own deck', Exception::WARNING);
        }

        $this->service()->deck()->saveNote($deck, $note);

        $this->result()->setData([
            'info' => 'Deck note saved',
        ]);
    }

    /**
     * Clear deck note
     * @throws Exception
     */
    protected function clearDeckNote()
    {
        $request = $this->request();

        $player = $this->getCurrentPlayer();

        $this->assertParamsNonEmpty(['deck_id']);

        $deckId = $request['deck_id'];

        // load deck
        $deck = $this->dbEntity()->deck()->getDeckAsserted($deckId);

        // validate deck ownership
        if ($deck->getUsername() != $player->getUsername()) {
            throw new Exception('Can only manipulate own deck', Exception::WARNING);
        }

        $this->service()->deck()->saveNote($deck, '');

        $this->result()->setData([
            'info' => 'Deck note cleared',
        ]);
    }

    /**
     * Send in-game chat message
     * @throws Exception
     */
    protected function sendChatMessage()
    {
        $request = $this->request();

        $player = $this->getCurrentPlayer();

        // check access rights
        if (!$this->checkAccess('chat')) {
            throw new Exception('Access denied', Exception::WARNING);
        }

        $this->assertParamsExist(['message']);
        $this->assertParamsNonEmpty(['game_id']);

        $message = $request['message'];
        $gameId = $request['game_id'];

        // download game
        $game = $this->dbEntity()->game()->getGameAsserted($gameId);

        // validate access
        if ($player->getUsername() != $game->getPlayer1() && $player->getUsername() != $game->getPlayer2()) {
            throw new Exception('Action not allowed', Exception::WARNING);
        }

        // check access rights
        if (!$this->checkAccess('chat')) {
            throw new Exception('Access denied', Exception::WARNING);
        }

        $this->service()->gameUtil()->sendChatMessage($player->getUsername(), $game, $message);

        $this->result()->setData([
            'info' => 'Chat message sent',
        ]);
    }

    /**
     * Reset chat notification
     * @throws Exception
     */
    protected function resetChatNotification()
    {
        $request = $this->request();

        $player = $this->getCurrentPlayer();

        $this->assertParamsNonEmpty(['game_id']);

        $gameId = $request['game_id'];

        // download game
        $game = $this->dbEntity()->game()->getGameAsserted($gameId);

        // validate access
        if ($player->getUsername() != $game->getPlayer1() && $player->getUsername() != $game->getPlayer2()) {
            throw new Exception('Action not allowed', Exception::WARNING);
        }

        // check if chat is allowed (can't chat with a computer player)
        if ($game->checkGameMode('AIMode')) {
            throw new Exception('Chat not allowed!', Exception::WARNING);
        }

        $game->resetChatNotification($player->getUsername());
        if (!$game->save()) {
            throw new Exception('Failed reset chat notification');
        }

        $this->result()->setData([
            'info' => 'Chat notification reset',
        ]);
    }

    /**
     * Lookup card details
     * @throws Exception
     */
    protected function cardLookup()
    {
        $dic = $this->getDic();
        $request = $this->request();

        $this->assertParamsExist(['card_id']);

        $cardId = $request['card_id'];

        $html = $dic->view()->renderTemplateWithoutLayout('Cards_lookup', ['card' => $cardId]);

        $this->result()->setData([
            'data' => $html,
        ]);
    }

    /**
     * Check if player has active games (AI games do not count)
     * @throws Exception
     */
    protected function activeGames()
    {
        $player = $this->getCurrentPlayer();
        $dbEntityGame = $this->dbEntity()->game();

        // active games notification
        $result = $dbEntityGame->countCurrentGames($player->getUsername());
        if ($result->isErrorOrNoEffect()) {
            throw new Exception('Failed to count current games');
        }
        $currentGames = $result[0]['count'];

        $this->result()->setData([
            'active_games' => ($currentGames > 0),
        ]);
    }
}
