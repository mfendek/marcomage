<?php
/**
 * Message - messages related view module
 */

namespace View;

use ArcomageException as Exception;
use Db\Model\Player as PlayerModel;
use Util\Date;
use Util\Encode;
use Util\Input;
use Util\Random;

class Message extends TemplateDataAbstract
{
    /**
     * @throws Exception
     * @return Result
     */
    protected function messages()
    {
        $data = array();
        $input = $this->input();

        $player = $this->getCurrentPlayer();
        $dbEntityDeck = $this->dbEntity()->deck();
        $dbEntityMessage = $this->dbEntity()->message();
        $defEntityChallenge = $this->defEntity()->challenge();

        // determine current challenges and messages subsection
        $currentSubsection = Input::defaultValue($input, 'challenges_subsection', 'incoming');
        $currentLocation = Input::defaultValue($input, 'messages_subsection', 'inbox');

        // validate challenges subsection
        if (!in_array($currentSubsection, ['incoming', 'outgoing'])) {
            throw new Exception('Invalid challenges subsection', Exception::WARNING);
        }

        // validate messages subsection
        if (!in_array($currentLocation, ['inbox', 'sent_mail', 'all_mail'])) {
            throw new Exception('Invalid messages subsection', Exception::WARNING);
        }

        // check access to admin mail
        if ($currentLocation == 'all_mail' && !$this->checkAccess('see_all_messages')) {
            throw new Exception('Access denied', Exception::WARNING);
        }

        $setting = $this->getCurrentSettings();

        // player's settings
        $data['player_name'] = $player->getUsername();
        $data['notification'] = $player->getNotification();
        $data['timezone'] = $setting->getSetting('timezone');
        $data['random_deck_option'] = $setting->getSetting('use_random_deck');
        $data['system_name'] = PlayerModel::SYSTEM_NAME;

        // decks related data
        $result = $dbEntityDeck->listReadyDecks($player->getUsername());
        if ($result->isError()) {
            throw new Exception('Failed to list ready decks');
        }
        $decks = $result->data();

        $data['decks'] = $decks;
        $data['random_deck'] = (count($decks) > 0) ? $decks[Random::arrayMtRand($decks)]['deck_id'] : '';
        $data['deck_count'] = count($decks);
        $data['free_slots'] = $this->service()->gameUtil()->countFreeSlots($player->getUsername(), true);

        $score = $this->dbEntity()->score()->getScoreAsserted($player->getUsername());

        $aiChallenges = array();
        if ($score->getLevel() >= PlayerModel::TUTORIAL_END) {
            $result = $defEntityChallenge->listChallenges();
            if ($result->isError()) {
                throw new Exception('Failed to list AI challenges');
            }

            $aiChallenges = $result->data();
        }

        $data['ai_challenges'] = $aiChallenges;

        // list messages based on current subsection
        if ($currentSubsection == 'incoming') {
            $result = $dbEntityMessage->listChallengesTo($player->getUsername());
        }
        else {
            $result = $dbEntityMessage->listChallengesFrom($player->getUsername());
        }
        if ($result->isError()) {
            throw new Exception('Failed to list challenges');
        }
        $challenges = $result->data();

        $data['challenges'] = $challenges;
        $data['challenges_count'] = count($challenges);
        $data['current_subsection'] = $currentSubsection;

        // validate current page
        $currentPage = Input::defaultValue($input, 'messages_current_page', 0);
        if (!is_numeric($currentPage) || $currentPage < 0) {
            throw new Exception('Invalid messages page', Exception::WARNING);
        }

        // initialize message filters
        $data['date_val'] = $date = Input::defaultValue($input, 'date_filter', 'none');
        $data['name_val'] = $name = Encode::postDecode(Input::defaultValue($input, 'name_filter'));
        $data['current_order'] = $currentOrder = Input::defaultValue($input, 'messages_current_order', 'DESC');
        $data['current_condition'] = $currentCondition = Input::defaultValue($input, 'messages_current_condition', 'created_at');
        $data['current_page'] = $currentPage;

        // case 1: all mail section
        if ($currentLocation == 'all_mail') {
            $result = $dbEntityMessage->listAllMessages($date, $name, $currentCondition, $currentOrder, $currentPage);
            if ($result->isError()) {
                throw new Exception('Failed to list all messages');
            }
            $messages = $result->data();

            $result = $dbEntityMessage->countPagesAll($date, $name);
            if ($result->isErrorOrNoEffect()) {
                throw new Exception('Failed to count pages for all messages list');
            }
            $pageCount = ceil($result[0]['count'] / \Db\Model\Message::MESSAGES_PER_PAGE);
        }
        // case 2: sent mail section
        elseif ($currentLocation == 'sent_mail') {
            $result = $dbEntityMessage->listMessagesFrom(
                $player->getUsername(), $date, $name, $currentCondition, $currentOrder, $currentPage
            );
            if ($result->isError()) {
                throw new Exception('Failed to list messages from player');
            }
            $messages = $result->data();

            $result = $dbEntityMessage->countPagesFrom($player->getUsername(), $date, $name);
            if ($result->isErrorOrNoEffect()) {
                throw new Exception('Failed to count pages for messages from list');
            }
            $pageCount = ceil($result[0]['count'] / \Db\Model\Message::MESSAGES_PER_PAGE);
        }
        // case 3: inbox section
        else {
            $result = $dbEntityMessage->listMessagesTo(
                $player->getUsername(), $date, $name, $currentCondition, $currentOrder, $currentPage
            );
            if ($result->isError()) {
                throw new Exception('Failed to list messages to player');
            }
            $messages = $result->data();

            $result = $dbEntityMessage->countPagesTo($player->getUsername(), $date, $name);
            if ($result->isErrorOrNoEffect()) {
                throw new Exception('Failed to count pages for messages to list');
            }
            $pageCount = ceil($result[0]['count'] / \Db\Model\Message::MESSAGES_PER_PAGE);
        }

        // messages elated data
        $data['messages'] = $messages;
        $data['page_count'] = $pageCount;
        $data['messages_count'] = count($messages);
        $data['current_location'] = $currentLocation;
        $data['current_page'] = $currentPage;

        $data['send_messages'] = ($this->checkAccess('messages')) ? 'yes' : 'no';
        $data['accept_challenges'] = ($this->checkAccess('accept_challenges')) ? 'yes' : 'no';
        $data['see_all_messages'] = ($this->checkAccess('see_all_messages')) ? 'yes' : 'no';

        return new Result(['messages' => $data]);
    }

    /**
     * @throws Exception
     * @return Result
     */
    protected function messagesDetails()
    {
        $data = array();
        $input = $this->input();

        $player = $this->getCurrentPlayer();

        // validate message id
        $this->assertInputNonEmpty(['CurrentMessage']);
        if (!is_numeric($input['CurrentMessage']) || $input['CurrentMessage'] < 0) {
            throw new Exception('Missing message id', Exception::WARNING);
        }

        $messageId = $input['CurrentMessage'];
        $message = $this->dbEntity()->message()->getMessageAsserted($messageId);

        // validate message access
        if (!$this->checkAccess('see_all_messages')) {
            // validate message from player's point of view
            if ($message->getAuthor() != $player->getUsername() && $message->getRecipient() != $player->getUsername()) {
                throw new Exception('Can only view own messages', Exception::WARNING);
            }

            // validate message from player's point of view
            if (($message->getAuthor() == $player->getUsername() && $message->getIsDeletedAuthor() == 1)
                || ($message->getRecipient() == $player->getUsername() && $message->getIsDeletedRecipient() == 1)) {
                throw new Exception('Message was already deleted', Exception::WARNING);
            }
        }

        $setting = $this->getCurrentSettings();

        $data['player_name'] = $player->getUsername();
        $data['system_name'] = PlayerModel::SYSTEM_NAME;
        $data['timezone'] = $setting->getSetting('timezone');

        $data['author'] = $message->getAuthor();
        $data['recipient'] = $message->getRecipient();
        $data['subject'] = $message->getSubject();
        $data['content'] = $message->getContent();
        $data['message_id'] = $messageId;
        $data['delete'] = (isset($input['message_delete'])) ? 'yes' : 'no';
        $data['messages'] = ($this->checkAccess('messages')) ? 'yes' : 'no';

        $data['current_location'] = Input::defaultValue($input, 'messages_subsection', 'inbox');
        $data['created'] = $message->getCreated();

        // assign stamp picture based in timestamp
        $data['stamp'] = 1 + Date::strToTime($message->getCreated()) % 4;

        return new Result(['message_details' => $data]);
    }

    /**
     * @return Result
     */
    protected function messagesNew()
    {
        $data = array();
        $input = $this->input();

        $data['author'] = $input['author'];
        $data['recipient'] = $input['recipient'];
        $data['content'] = Input::defaultValue($input, 'content');
        $data['subject'] = Input::defaultValue($input, 'subject');

        return new Result(['message_new' => $data]);
    }
}
