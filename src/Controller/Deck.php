<?php
/**
 * Deck - decks related controller
 */

namespace Controller;

use ArcomageException as Exception;
use Db\Model\ForumThread;
use Db\Model\Player as PlayerModel;
use Def\Entity\XmlKeyword;
use Util\Date;

class Deck extends ControllerAbstract
{
    /**
     * Modify this deck -> take
     * @throws Exception
     */
    protected function addCard()
    {
        $request = $this->request();

        $player = $this->getCurrentPlayer();

        $this->result()->setCurrent('Decks');

        $this->assertParamsNonEmpty(['current_deck']);
        $deckId = $request['current_deck'];

        // load deck
        $deck = $this->dbEntity()->deck()->getDeckAsserted($deckId);

        // validate deck ownership
        if ($deck->getUsername() != $player->getUsername()) {
            throw new Exception('Can only manipulate own deck', Exception::WARNING);
        }

        $this->result()->setCurrent('Decks_edit');

        $cardId = $request['add_card'];

        $this->service()->deck()->addCard($deck, $cardId);
    }

    /**
     * Modify this deck -> return
     * @throws Exception
     */
    protected function returnCard()
    {
        $request = $this->request();

        $player = $this->getCurrentPlayer();

        $this->result()->setCurrent('Decks');

        $this->assertParamsNonEmpty(['current_deck']);
        $deckId = $request['current_deck'];

        // load deck
        $deck = $this->dbEntity()->deck()->getDeckAsserted($deckId);

        // validate deck ownership
        if ($deck->getUsername() != $player->getUsername()) {
            throw new Exception('Can only manipulate own deck', Exception::WARNING);
        }

        $this->result()->setCurrent('Decks_edit');

        $cardId = $request['return_card'];

        $this->service()->deck()->removeCard($deck, $cardId);
    }

    /**
     * Set tokens
     * @throws Exception
     */
    protected function setTokens()
    {
        $request = $this->request();
        $player = $this->getCurrentPlayer();

        $this->result()->setCurrent('Decks');

        $this->assertParamsNonEmpty(['current_deck']);
        $deckId = $request['current_deck'];

        $deck = $this->dbEntity()->deck()->getDeckAsserted($deckId);

        // validate deck ownership
        if ($deck->getUsername() != $player->getUsername()) {
            throw new Exception('Can only manipulate own deck', Exception::WARNING);
        }

        $this->result()->setCurrent('Decks_edit');

        // read tokens from inputs
        $tokens = array();
        foreach ($deck->getData()->Tokens as $tokenIndex => $token) {
            $this->assertParamsExist(['Token' . $tokenIndex]);

            $tokens[$tokenIndex] = $request['Token' . $tokenIndex];
        }

        $length = count($tokens);

        // remove empty tokens
        $tokens = array_diff($tokens, ['none']);

        // remove duplicates
        $tokens = array_unique($tokens);
        $tokens = array_pad($tokens, $length, 'none');

        // sort tokens, add consistent keys
        $i = 1;
        $sorted_tokens = array();
        foreach ($tokens as $token) {
            $sorted_tokens[$i] = $token;
            $i++;
        }

        // save token data
        $deck
            ->setTokens($sorted_tokens)
            ->setModifiedAt(Date::timeToStr());
        if (!$deck->save()) {
            throw new Exception('Unable to set tokens in this deck');
        }

        $this->result()->setInfo('Tokens set');
    }

    /**
     * Assign tokens automatically
     * @throws Exception
     */
    protected function autoTokens()
    {
        $request = $this->request();
        $player = $this->getCurrentPlayer();

        $this->result()->setCurrent('Decks');

        $this->assertParamsNonEmpty(['current_deck']);
        $deckId = $request['current_deck'];

        $deck = $this->dbEntity()->deck()->getDeckAsserted($deckId);

        // validate deck ownership
        if ($deck->getUsername() != $player->getUsername()) {
            throw new Exception('Can only manipulate own deck', Exception::WARNING);
        }

        $this->result()->setCurrent('Decks_edit');

        // set tokens automatically
        $this->service()->deck()->setAutoTokens($deck);

        $deck->setModifiedAt(Date::timeToStr());
        if (!$deck->save()) {
            throw new Exception('Unable to set tokens automatically in this deck');
        }

        $this->result()->setInfo('Tokens set');
    }

    /**
     * Save note
     * @throws Exception
     */
    protected function saveDeckNote()
    {
        $request = $this->request();
        $player = $this->getCurrentPlayer();

        $this->result()->setCurrent('Decks');

        $this->assertParamsNonEmpty(['current_deck']);
        $deckId = $request['current_deck'];

        $deck = $this->dbEntity()->deck()->getDeckAsserted($deckId);

        // validate deck ownership
        if ($deck->getUsername() != $player->getUsername()) {
            throw new Exception('Can only manipulate own deck', Exception::WARNING);
        }

        $this->result()->setCurrent('Decks_note');

        $this->assertParamsExist(['content']);

        $this->service()->deck()->saveNote($deck, $request['content']);

        $this->result()->setInfo('Deck note saved');
    }

    /**
     * Save note and return to deck screen
     * @throws Exception
     */
    protected function saveDeckNoteReturn()
    {
        $request = $this->request();
        $player = $this->getCurrentPlayer();

        $this->result()->setCurrent('Decks');

        $this->assertParamsNonEmpty(['current_deck']);
        $deckId = $request['current_deck'];

        $deck = $this->dbEntity()->deck()->getDeckAsserted($deckId);

        // validate deck ownership
        if ($deck->getUsername() != $player->getUsername()) {
            throw new Exception('Can only manipulate own deck', Exception::WARNING);
        }

        $this->result()->setCurrent('Decks_note');

        $this->assertParamsExist(['content']);

        $this->service()->deck()->saveNote($deck, $request['content']);

        $this->result()
            ->setCurrent('Decks_edit')
            ->setInfo('Deck note saved');
    }

    /**
     * Clear current's player deck note
     * @throws Exception
     */
    protected function clearDeckNote()
    {
        $request = $this->request();
        $player = $this->getCurrentPlayer();

        $this->result()->setCurrent('Decks');

        $this->assertParamsNonEmpty(['current_deck']);
        $deckId = $request['current_deck'];

        $deck = $this->dbEntity()->deck()->getDeckAsserted($deckId);

        // validate deck ownership
        if ($deck->getUsername() != $player->getUsername()) {
            throw new Exception('Can only manipulate own deck', Exception::WARNING);
        }

        $this->result()->setCurrent('Decks_note');

        $this->service()->deck()->saveNote($deck, '');

        // clear user text input
        $this->result()
            ->changeRequest('content', '')
            ->setInfo('Deck note saved');
    }

    /**
     * Clear current's player deck note and return to deck screen
     * @throws Exception
     */
    protected function clearDeckNoteReturn()
    {
        $request = $this->request();
        $player = $this->getCurrentPlayer();

        $this->result()->setCurrent('Decks');

        $this->assertParamsNonEmpty(['current_deck']);
        $deckId = $request['current_deck'];

        $deck = $this->dbEntity()->deck()->getDeckAsserted($deckId);

        // validate deck ownership
        if ($deck->getUsername() != $player->getUsername()) {
            throw new Exception('Can only manipulate own deck', Exception::WARNING);
        }

        $this->result()->setCurrent('Decks_note');

        $this->service()->deck()->saveNote($deck, '');

        $this->result()
            ->setCurrent('Decks_edit')
            ->setInfo('Deck note saved');
    }

    /**
     * Modify this deck -> apply filters
     */
    protected function deckApplyFilters()
    {
        // show card pool after applying filters
        $this->result()
            ->changeRequest('card_pool', 'yes')
            ->setCurrent('Decks_edit');
    }

    /**
     * Modify this deck -> prepare deck reset
     */
    protected function resetDeckPrepare()
    {
        // only symbolic functionality... rest is handled below
        $this->result()
            ->changeRequest('toggle_edit_show', true)
            ->setCurrent('Decks_edit');
    }

    /**
     * Modify this deck -> confirm reset
     * @throws Exception
     */
    protected function resetDeckConfirm()
    {
        $request = $this->request();
        $player = $this->getCurrentPlayer();

        $this->result()->setCurrent('Decks');

        $this->assertParamsNonEmpty(['current_deck']);
        $deckId = $request['current_deck'];

        $deck = $this->dbEntity()->deck()->getDeckAsserted($deckId);

        // validate deck ownership
        if ($deck->getUsername() != $player->getUsername()) {
            throw new Exception('Can only manipulate own deck', Exception::WARNING);
        }

        $this->result()->setCurrent('Decks_edit');

        // reset deck, saving it on success
        $deck
            ->resetData()
            ->resetStatistics()
            ->setModifiedAt(Date::timeToStr());
        if (!$deck->save()) {
            throw new Exception('Failed to reset deck');
        }

        $this->result()->setInfo('Deck successfully reset');
    }

    /**
     * Reset statistics
     */
    protected function resetStatsPrepare()
    {
        // only symbolic functionality... rest is handled below
        $this->result()
            ->changeRequest('toggle_stats_show', true)
            ->setCurrent('Decks_edit');
    }

    /**
     * Reset statistics -> confirm reset
     * @throws Exception
     */
    protected function resetStatsConfirm()
    {
        $request = $this->request();
        $player = $this->getCurrentPlayer();

        $this->result()->setCurrent('Decks');

        $this->assertParamsNonEmpty(['current_deck']);
        $deckId = $request['current_deck'];

        $deck = $this->dbEntity()->deck()->getDeckAsserted($deckId);

        // validate deck ownership
        if ($deck->getUsername() != $player->getUsername()) {
            throw new Exception('Can only manipulate own deck', Exception::WARNING);
        }

        $this->result()->setCurrent('Decks_edit');

        // reset deck statistics
        $deck
            ->resetStatistics()
            ->setModifiedAt(Date::timeToStr());
        if (!$deck->save()) {
            throw new Exception('Failed to reset statistics');
        }

        $this->result()->setInfo('Deck statistics successfully reset');
    }

    /**
     * Modify this deck -> Rename
     * @throws Exception
     */
    protected function renameDeck()
    {
        $request = $this->request();
        $player = $this->getCurrentPlayer();
        $dbEntityDeck = $this->dbEntity()->deck();

        $this->result()->setCurrent('Decks');

        $this->assertParamsNonEmpty(['current_deck']);
        $deckId = $request['current_deck'];

        $deck = $this->dbEntity()->deck()->getDeckAsserted($deckId);

        // validate deck ownership
        if ($deck->getUsername() != $player->getUsername()) {
            throw new Exception('Can only manipulate own deck', Exception::WARNING);
        }

        $this->result()->setCurrent('Decks_edit');

        $this->assertParamsExist(['new_deck_name']);
        $newName = $request['new_deck_name'];

        // validate new deck name
        if (trim($newName) == '') {
            throw new Exception('Cannot change deck name, invalid input', Exception::WARNING);
        }

        // list player's deck
        $result = $dbEntityDeck->listDecks($player->getUsername());
        if ($result->isError()) {
            throw new Exception('Failed to list decks');
        }
        $list = $result->data();

        // extract deck names
        $deckNames = array();
        foreach ($list as $deckData) {
            // omit current deck
            if ($deck->getDeckId() != $deckData['deck_id']) {
                $deckNames[] = $deckData['deck_name'];
            }
        }

        // check if the new deck name isn't already used
        $pos = array_search($newName, $deckNames);
        if ($pos !== false) {
            throw new Exception('Cannot change deck name, it is already used by another deck', Exception::WARNING);
        }

        // update deck name
        $deck
            ->setDeckName($newName)
            ->setModifiedAt(Date::timeToStr());
        if (!$deck->save()) {
            throw new Exception('Failed to rename deck');
        }

        $this->result()->setInfo('Deck saved');
    }

    /**
     * Modify this deck -> export
     * @throws Exception
     */
    protected function exportDeck()
    {
        $request = $this->request();
        $player = $this->getCurrentPlayer();

        $this->result()->setCurrent('Decks');

        $this->assertParamsNonEmpty(['current_deck']);
        $deckId = $request['current_deck'];

        $deck = $this->dbEntity()->deck()->getDeckAsserted($deckId);

        // validate deck ownership
        if ($deck->getUsername() != $player->getUsername()) {
            throw new Exception('Can only manipulate own deck', Exception::WARNING);
        }

        // export deck data
        $file = $deck->toCSV();
        $contentType = 'text/csv';
        $fileName = preg_replace("/[^a-zA-Z0-9_-]/i", "_", $deck->getDeckName()) . '.csv';
        $fileLength = mb_strlen($file);

        // create raw output
        $this->result()->setRawOutput($file, [
            'Content-Type: ' . $contentType . '',
            'Content-Disposition: attachment; filename="' . $fileName . '"',
            'Content-Length: ' . $fileLength,
        ]);
    }

    /**
     * Modify this deck -> import
     * @throws Exception
     */
    protected function importDeck()
    {
        $request = $this->request();
        $player = $this->getCurrentPlayer();
        $dbEntityDeck = $this->dbEntity()->deck();
        $files = $this->getDic()->files();

        $this->result()->setCurrent('Decks');

        $this->assertParamsNonEmpty(['current_deck']);
        $deckId = $request['current_deck'];

        $deck = $this->dbEntity()->deck()->getDeckAsserted($deckId);

        // validate deck ownership
        if ($deck->getUsername() != $player->getUsername()) {
            throw new Exception('Can only manipulate own deck', Exception::WARNING);
        }

        $this->result()->setCurrent('Decks_edit');

        //$supportedTypes = ['text/csv', 'text/comma-separated-values'];
        $supportedTypes = ['csv'];

        if ($files['deck_data_file']['tmp_name'] == '') {
            throw new Exception('Invalid input file', Exception::WARNING);
        }

        // MIME file type checking cannot be used, there are browser specific issues (Firefox, Chrome), instead use file extension check
//        if (!in_array($files['deck_data_file']['type'], $supportedTypes)) {
//          throw new Exception('Unsupported input file', Exception::WARNING);
//        }

        // validate file extension
        $fileName = explode(".", $files['deck_data_file']['name']);
        $extension = end($fileName);
        if (!in_array($extension, $supportedTypes)) {
            throw new Exception('Unsupported input file', Exception::WARNING);
        }

        // validate file size
        if ($files['deck_data_file']['size'] > \Db\Model\Deck::UPLOAD_SIZE) {
            throw new Exception('File is too big', Exception::WARNING);
        }

        // load file
        $file = file_get_contents($files['deck_data_file']['tmp_name']);

        // load data
        $lines = explode("\n", $file);

        $newName = trim($lines[0]);
        $deckCards = [
            'Common' => explode(',', $lines[1]),
            'Uncommon' => explode(',', $lines[2]),
            'Rare' => explode(',', $lines[3]),
        ];
        $tokens = explode(',', $lines[4]);

        // validate deck name
        if (trim($newName) == '') {
            throw new Exception('Cannot change deck name, invalid name', Exception::WARNING);
        }
        if (mb_strlen($newName) > 20) {
            throw new Exception('Deck name is too long', Exception::WARNING);
        }

        // list player's decks
        $result = $dbEntityDeck->listDecks($player->getUsername());
        if ($result->isError()) {
            throw new Exception('Failed to list decks');
        }
        $list = $result->data();

        // extract deck names
        $deckNames = array();
        foreach ($list as $deckData) {
            // omit current deck
            if ($deck->getDeckId() != $deckData['deck_id']) {
                $deckNames[] = $deckData['deck_name'];
            }
        }

        // check if the new deck name isn't already used
        $pos = array_search($newName, $deckNames);
        if ($pos !== false) {
            throw new Exception('Cannot change deck name, it is already used by another deck', Exception::WARNING);
        }

        $this->service()->deck()->validateCards($player->getUsername(), $deckCards);

        // check tokens
        if (count($tokens) != 3) {
            throw new Exception('Token data is corrupted', Exception::WARNING);
        }

        // remove empty tokens
        $nonEmpty = array_diff($tokens, ['none']);

        // check for duplicates
        if (count($nonEmpty) != count(array_unique($nonEmpty))) {
            throw new Exception('Token data contains duplicates', Exception::WARNING);
        }

        // check token names
        $allTokens = array_merge(XmlKeyword::tokenKeywords(), ['none']);
        if (count(array_diff($tokens, $allTokens)) > 0) {
            throw new Exception('Token data contains non token keywords', Exception::WARNING);
        }

        // import verified data

        // rename deck
        $deck->setDeckName($newName);

        // adjust key numbering
        $cardKeys = array_keys(array_fill(1, 15, 0));

        $deckData = $deck->getData();
        $deckData->Common = array_combine($cardKeys, $deckCards['Common']);
        $deckData->Uncommon = array_combine($cardKeys, $deckCards['Uncommon']);
        $deckData->Rare = array_combine($cardKeys, $deckCards['Rare']);
        $deck
            ->setData($deckData)
            ->setTokens(array_combine([1, 2, 3], $tokens))
            ->setModifiedAt(Date::timeToStr());

        if (!$deck->save()) {
            throw new Exception('Failed to import deck');
        }

        $this->result()->setInfo('Deck successfully imported');
    }

    /**
     * Use filter
     */
    protected function decksSharedFilter()
    {
        $this->result()
            ->changeRequest('decks_current_page', 0)
            ->setCurrent('Decks_shared');
    }

    /**
     * Select ascending order in shared decks list
     */
    protected function decksOrderAsc()
    {
        $request = $this->request();

        $this->result()
            ->changeRequest('decks_current_condition', $request['decks_order_asc'])
            ->changeRequest('decks_current_order', 'ASC')
            ->setCurrent('Decks_shared');
    }

    /**
     * Select descending order in shared decks list
     */
    protected function decksOrderDesc()
    {
        $request = $this->request();

        $this->result()
            ->changeRequest('decks_current_condition', $request['decks_order_desc'])
            ->changeRequest('decks_current_order', 'DESC')
            ->setCurrent('Decks_shared');
    }

    /**
     * Select page (previous and next button)
     */
    protected function decksSelectPage()
    {
        $request = $this->request();

        $this->result()
            ->changeRequest('decks_current_page', $request['decks_select_page'])
            ->setCurrent('Decks_shared');
    }

    /**
     * Import shared deck
     * @throws Exception
     */
    protected function importSharedDeck()
    {
        $request = $this->request();

        $player = $this->getCurrentPlayer();

        $this->result()->setCurrent('Decks_shared');
        $sourceDeckId = $request['import_shared_deck'];

        $this->assertParamsNonEmpty(['selected_deck']);
        $targetDeckId = $request['selected_deck'];

        // check if deck import makes sense
        if ($sourceDeckId == $targetDeckId) {
            throw new Exception('Unable to import self', Exception::WARNING);
        }

        // validate player's level
        $score = $this->dbEntity()->score()->getScoreAsserted($player->getUsername());
        $level = $score->getLevel();
        if ($level < PlayerModel::TUTORIAL_END) {
            $this->result()->setCurrent('Decks');
            throw new Exception('Access denied (level requirement)', Exception::WARNING);
        }

        // load source deck
        $sourceDeck = $this->dbEntity()->deck()->getDeckAsserted($sourceDeckId);

        // check if source deck is shared
        if ($sourceDeck->getIsShared() == 0) {
            throw new Exception('Selected deck is not shared', Exception::WARNING);
        }

        // check if source deck is ready (completed)
        if (!$sourceDeck->isReady()) {
            throw new Exception('Selected deck is incomplete', Exception::WARNING);
        }

        $deck = $this->dbEntity()->deck()->getDeckAsserted($targetDeckId);

        // validate deck ownership
        if ($deck->getUsername() != $player->getUsername()) {
            $this->result()->setCurrent('Decks');
            throw new Exception('Can only manipulate own deck');
        }

        // import shared deck
        $deck
            ->setDeckName($sourceDeck->getDeckName())
            ->setData($sourceDeck->getData())
            ->setModifiedAt(Date::timeToStr());

        if (!$deck->save()) {
            $this->result()->setCurrent('Decks_edit');
            throw new Exception('Failed to import shared deck');
        }

        $this->result()
            ->changeRequest('current_deck', $targetDeckId)
            ->setInfo('Deck successfully imported from shared deck')
            ->setCurrent('Decks_edit');
    }

    /**
     * Modify this deck -> share
     * @throws Exception
     */
    protected function shareDeck()
    {
        $request = $this->request();
        $player = $this->getCurrentPlayer();

        $this->result()->setCurrent('Decks');

        $this->assertParamsNonEmpty(['current_deck']);
        $deckId = $request['current_deck'];

        $deck = $this->dbEntity()->deck()->getDeckAsserted($deckId);

        // validate deck ownership
        if ($deck->getUsername() != $player->getUsername()) {
            throw new Exception('Can only manipulate own deck', Exception::WARNING);
        }

        $this->result()->setCurrent('Decks_edit');

        // check if deck ins't already shared
        if ($deck->getIsShared() == 1) {
            throw new Exception('Deck is already shared', Exception::WARNING);
        }

        $deck->setIsShared(1);
        if (!$deck->save()) {
            throw new Exception('Failed to share deck');
        }

        $this->result()->setInfo('Deck successfully shared');
    }

    /**
     * Modify this deck -> Unshare
     * @throws Exception
     */
    protected function unshareDeck()
    {
        $request = $this->request();
        $player = $this->getCurrentPlayer();

        $this->result()->setCurrent('Decks');

        $this->assertParamsNonEmpty(['current_deck']);
        $deckId = $request['current_deck'];

        $deck = $this->dbEntity()->deck()->getDeckAsserted($deckId);

        // validate deck ownership
        if ($deck->getUsername() != $player->getUsername()) {
            throw new Exception('Can only manipulate own deck', Exception::WARNING);
        }

        $this->result()->setCurrent('Decks_edit');

        // check if deck isn't already unshared
        if ($deck->getIsShared() == 0) {
            throw new Exception('Deck is already unshared', Exception::WARNING);
        }

        $deck->setIsShared(0);
        if (!$deck->save()) {
            throw new Exception('Failed to unshare deck');
        }

        $this->result()->setInfo('Deck successfully unshared');
    }

    /**
     * Show/hide card pool (used only when JavaScript is disabled)
     */
    protected function cardPoolSwitch()
    {
        $request = $this->request();

        $this->result()
            ->changeRequest('card_pool', (isset($request['card_pool']) && $request['card_pool'] == 'yes') ? 'no' : 'yes')
            ->setCurrent('Decks_edit');
    }

    /**
     * Find matching thread for specified deck or create a new matching thread
     * @throws Exception
     */
    protected function findDeckThread()
    {
        $request = $this->request();

        $player = $this->getCurrentPlayer();
        $dbEntityDeck = $this->dbEntity()->deck();
        $dbEntityForumThread = $this->dbEntity()->forumThread();

        $this->result()->setCurrent('Decks');
        $deckId = $request['find_deck_thread'];

        // check access rights
        if (!$this->checkAccess('create_thread')) {
            throw new Exception('Access denied', Exception::WARNING);
        }

        // validate card id
        if (!is_numeric($deckId)) {
            throw new Exception('Invalid deck id ' . $deckId, Exception::WARNING);
        }

        // find related forum thread
        $result = $dbEntityForumThread->deckThread($deckId);
        if ($result->isError()) {
            throw new Exception('Failed to find thread by deck id ' . $deckId);
        }
        $threadId = ($result->isSuccess()) ? $result[0]['thread_id'] : 0;

        // thread not found - create new thread
        if (!$threadId) {
            $deck = $dbEntityDeck->getDeckAsserted($deckId);

            // use deck name as thread title
            $title = $deck->getDeckName();
            $thread = $dbEntityForumThread->createThread(
                $title, $player->getUsername(), 'normal', ForumThread::DECKS_SECTION_ID, $deckId
            );
            if (!$thread->save()) {
                throw new Exception('Failed to create new thread');
            }

            $threadId = $thread->getThreadId();
        }

        $this->result()
            ->changeRequest('current_thread', $threadId)
            ->setCurrent('Forum_thread');
    }
}
