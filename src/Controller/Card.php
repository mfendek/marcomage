<?php
/**
 * Card - cards related controller
 */

namespace Controller;

use ArcomageException as Exception;
use Db\Model\ForumThread;

class Card extends ControllerAbstract
{
    /**
     * Apply filters
     */
    protected function cardsApplyFilters()
    {
        $this->result()
            ->changeRequest('cards_current_page', 0)
            ->setCurrent('Cards');
    }

    /**
     * Select page (previous and next button)
     */
    protected function cardsSelectPage()
    {
        $request = $this->request();
        $this->result()
            ->changeRequest('cards_current_page', $request['cards_select_page'])
            ->setCurrent('Cards');
    }

    /**
     * Find matching thread for specified card or create a new matching thread
     * @throws Exception
     */
    protected function findCardThread()
    {
        $request = $this->request();

        $player = $this->getCurrentPlayer();
        $defEntityCard = $this->defEntity()->card();
        $dbEntityForumThread = $this->dbEntity()->forumThread();

        $this->result()->setCurrent('Cards');
        $cardId = $request['find_card_thread'];

        // check access rights
        if (!$this->checkAccess('create_thread')) {
            throw new Exception('Access denied', Exception::WARNING);
        }

        // validate card id
        if (!is_numeric($cardId)) {
            throw new Exception('Invalid card id ' . $cardId, Exception::WARNING);
        }

        // find related forum thread
        $result = $dbEntityForumThread->cardThread($cardId);
        if ($result->isError()) {
            throw new Exception('Failed to find thread by card id ' . $cardId);
        }
        $threadId = ($result->isSuccess()) ? $result[0]['ThreadID'] : 0;

        // thread not found - create new thread
        if (!$threadId) {
            $card = $defEntityCard->getCard($cardId);

            // use card name as thread title
            $title = $card->getData('name');
            $thread = $dbEntityForumThread->createThread(
                $title, $player->getUsername(), 'normal', ForumThread::CARDS_SECTION_ID, $cardId
            );
            if (!$thread->save()) {
                throw new Exception('Failed to create new thread');
            }

            $threadId = $thread->getThreadId();
        }

        $this->result()
            ->changeRequest('CurrentThread', $threadId)
            ->setCurrent('Forum_thread');
    }

    /**
     * Buy foil version of a card
     * @throws Exception
     */
    protected function buyFoilCard()
    {
        $request = $this->request();

        $player = $this->getCurrentPlayer();
        $db = $this->getDb();
        $defEntityCard = $this->defEntity()->card();

        $boughtCard = $request['buy_foil_card'];
        $this->result()
            ->changeRequest('card', $boughtCard)
            ->setCurrent('Cards_details');

        // validate card
        $defEntityCard->getCard($boughtCard);

        // load foil cards list for current player
        $setting = $this->getCurrentSettings();

        $foilCards = $setting->getSetting('FoilCards');
        $foilCards = ($foilCards == '') ? [] : explode(',', $foilCards);

        // check if card can be purchased
        if (in_array($boughtCard, $foilCards)) {
            throw new Exception('Foil version of current card was already purchased', Exception::WARNING);
        }

        // subtract foil card cost form gold reserves
        $score = $this->dbEntity()->score()->getScoreAsserted($player->getUsername());

        if ($score->getGold() < \Def\Model\Card::FOIL_COST) {
            throw new Exception('Not enough gold', Exception::WARNING);
        }

        $db->beginTransaction();

        // remove payed gold
        $score->setGold($score->getGold() - \Def\Model\Card::FOIL_COST);
        if (!$score->save()) {
            $db->rollBack();
            throw new Exception('Failed to save score');
        }

        // store bought card
        array_push($foilCards, $boughtCard);
        $setting->changeSetting('FoilCards', implode(',', $foilCards));

        if (!$setting->save()) {
            $db->rollBack();
            throw new Exception('Failed to save setting');
        }

        $db->commit();

        $this->result()->setInfo('Foil version purchased');
    }
}
