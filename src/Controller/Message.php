<?php
/**
 * Message - messages related controller
 */

namespace Controller;

use ArcomageException as Exception;
use Db\Model\Player as PlayerModel;
use Util\Encode;

class Message extends ControllerAbstract
{
    /**
     * View message
     * @throws Exception
     */
    protected function messageDetails()
    {
        $request = $this->request();

        $player = $this->getCurrentPlayer();

        $this->result()->setCurrent('Messages');
        $messageId = $request['message_details'];

        $message = $this->dbEntity()->message()->getMessageAsserted($messageId);

        // validate message from player's point of view
        if ($message->getAuthor() != $player->getUsername() && $message->getRecipient() != $player->getUsername()) {
            throw new Exception('Can only view own messages', Exception::WARNING);
        }

        // validate message from player's point of view
        if (($message->getAuthor() == $player->getUsername() && $message->getIsDeletedAuthor() == 1)
            || ($message->getRecipient() == $player->getUsername() && $message->getIsDeletedRecipient() == 1)) {
            throw new Exception('Message was already deleted', Exception::WARNING);
        }

        // update unread flag
        if ($message->getRecipient() == $player->getUsername()) {
            $message->setUnread(0);

            if (!$message->save()) {
                throw new Exception('Failed to update unread flag');
            }
        }

        $this->result()
            ->changeRequest('CurrentMessage', $messageId)
            ->setCurrent('Messages_details');
    }

    /**
     * Retrieve message (even deleted one)
     * @throws Exception
     */
    protected function messageRetrieve()
    {
        $request = $this->request();

        $this->result()->setCurrent('Messages');
        $messageId = $request['message_retrieve'];

        // check access rights
        if (!$this->checkAccess('see_all_messages')) {
            throw new Exception('Access denied', Exception::WARNING);
        }

        // check if message exists
        $this->dbEntity()->message()->getMessageAsserted($messageId);

        $this->result()
            ->changeRequest('CurrentMessage', $messageId)
            ->setCurrent('Messages_details');
    }

    /**
     * Delete message
     * @throws Exception
     */
    protected function messageDelete()
    {
        $request = $this->request();

        $player = $this->getCurrentPlayer();

        $this->result()->setCurrent('Messages');
        $messageId = $request['message_delete'];

        $message = $this->dbEntity()->message()->getMessageAsserted($messageId);

        // validate message from player's point of view
        if ($message->getAuthor() != $player->getUsername() && $message->getRecipient() != $player->getUsername()) {
            throw new Exception('Can only view own messages', Exception::WARNING);
        }

        // validate message from player's point of view
        if (($message->getAuthor() == $player->getUsername() && $message->getIsDeletedAuthor() == 1)
            || ($message->getRecipient() == $player->getUsername() && $message->getIsDeletedRecipient() == 1)) {
            throw new Exception('Message was already deleted', Exception::WARNING);
        }

        $this->result()
            ->changeRequest('CurrentMessage', $messageId)
            ->setCurrent('Messages_details');
    }

    /**
     * Delete message confirmation
     * @throws Exception
     */
    protected function messageDeleteConfirm()
    {
        $request = $this->request();

        $player = $this->getCurrentPlayer();

        $this->result()->setCurrent('Messages');
        $messageId = $request['message_delete_confirm'];

        $message = $this->dbEntity()->message()->getMessageAsserted($messageId);

        // validate message from player's point of view
        if ($message->getAuthor() != $player->getUsername() && $message->getRecipient() != $player->getUsername()) {
            throw new Exception('Can only view own messages', Exception::WARNING);
        }

        // validate message from player's point of view
        if (($message->getAuthor() == $player->getUsername() && $message->getIsDeletedAuthor() == 1)
            || ($message->getRecipient() == $player->getUsername() && $message->getIsDeletedRecipient() == 1)) {
            throw new Exception('Message was already deleted', Exception::WARNING);
        }

        // case 1: system message - delete completely
        if ($message->getAuthor() == PlayerModel::SYSTEM_NAME) {
            $message->markDeleted();
        }
        // case 2: standard message - hide
        else {
            // case 1: author
            if ($message->getAuthor() == $player->getUsername()) {
                $message->setIsDeletedAuthor(1);
            }
            // case 2: recipient
            else {
                $message->setIsDeletedRecipient(1);
            }
        }

        if (!$message->save()) {
            $this->result()->setCurrent('Messages_details');
            throw new Exception('Failed to delete message!');
        }

        $this->result()->setInfo('Message deleted');
    }

    /**
     * Cancel new message creation
     */
    protected function messageCancel()
    {
        $this->result()->setCurrent('Messages');
    }

    /**
     * Send new message
     * @throws Exception
     */
    protected function messageSend()
    {
        $request = $this->request();

        $dbEntityMessage = $this->dbEntity()->message();

        $this->result()->setCurrent('Messages_new');

        $this->assertParamsNonEmpty(['recipient', 'author']);
        $recipient = $request['recipient'];
        $author = $request['author'];

        // check access rights
        if (!$this->checkAccess('messages')) {
            $this->result()->setCurrent('Messages');
            throw new Exception('Access denied', Exception::WARNING);
        }

        $this->assertParamsExist(['subject', 'content']);

        // validate user input
        if (trim($request['subject']) == '' && trim($request['content']) == '') {
            throw new Exception('No message input specified', Exception::WARNING);
        }

        // check message length
        if (mb_strlen($request['content']) > \Db\Model\Message::MESSAGE_LENGTH) {
            throw new Exception('Message too long', Exception::WARNING);
        }

        // validate recipient
        $recipient = $this->dbEntity()->player()->getPlayerAsserted($recipient);

        $message = $dbEntityMessage->sendMessage(
            $author, $recipient->getUsername(), $request['subject'], $request['content']
        );
        if (!$message->save()) {
            $this->result()->setCurrent('Messages');
            throw new Exception('Failed to send message');
        }

        $this->result()
            ->changeRequest('messages_subsection', 'sent_mail')
            ->setInfo('Message sent')
            ->setCurrent('Messages');
    }

    /**
     * Go to new message screen
     * @throws Exception
     */
    protected function messageCreate()
    {
        $request = $this->request();

        $player = $this->getCurrentPlayer();

        $this->result()->setCurrent('Messages');

        // check access rights
        if (!$this->checkAccess('messages')) {
            throw new Exception('Access denied', Exception::WARNING);
        }

        $this->result()
            ->changeRequest('recipient', Encode::postDecode($request['message_create']))
            ->changeRequest('author', $player->getUsername())
            ->setCurrent('Messages_new');
    }

    /**
     * Go to new message screen to write system notification
     * @throws Exception
     */
    protected function systemNotification()
    {
        $request = $this->request();

        // check access rights
        if (!$this->checkAccess('system_notification')) {
            $this->result()->setCurrent('Players');
            throw new Exception('Access denied', Exception::WARNING);
        }

        $this->result()
            ->changeRequest('recipient', Encode::postDecode($request['system_notification']))
            ->changeRequest('author', PlayerModel::SYSTEM_NAME)
            ->setCurrent('Messages_new');
    }

    /**
     * Select ascending order in message list
     */
    protected function messagesOrderAsc()
    {
        $request = $this->request();

        $this->result()
            ->changeRequest('messages_current_condition', $request['messages_order_asc'])
            ->changeRequest('messages_current_order', 'ASC')
            ->setCurrent('Messages');
    }

    /**
     * Select descending order in message list
     */
    protected function messagesOrderDesc()
    {
        $request = $this->request();

        $this->result()
            ->changeRequest('messages_current_condition', $request['messages_order_desc'])
            ->changeRequest('messages_current_order', 'DESC')
            ->setCurrent('Messages');
    }

    /**
     * Use filter
     */
    protected function messagesApplyFilters()
    {
        $this->result()
            ->changeRequest('messages_current_page', 0)
            ->setCurrent('Messages');
    }

    /**
     * Select page (previous and next button)
     */
    protected function messagesSelectPage()
    {
        $request = $this->request();

        $this->result()
            ->changeRequest('messages_current_page', $request['messages_select_page'])
            ->setCurrent('Messages');
    }

    /**
     * Delete selected messages
     * @throws Exception
     */
    protected function deleteMassMessages()
    {
        $request = $this->request();

        $player = $this->getCurrentPlayer();
        $dbEntityMessage = $this->dbEntity()->message();

        $this->result()->setCurrent('Messages');

        $deletedMessages = array();
        for ($i = 1; $i <= \Db\Model\Message::MESSAGES_PER_PAGE; $i++) {
            if (isset($request['mass_delete_' . $i])) {
                $deletedMessages[] = $request['mass_delete_' . $i];
            }
        }

        // case 1: some messages were selected
        if (count($deletedMessages) > 0) {
            // find system messages
            $result = $dbEntityMessage->findSystemMessages($deletedMessages, $player->getUsername());
            if ($result->isError()) {
                throw new Exception('Failed to find system messages');
            }

            // extract message ids
            $systemIds = array();
            foreach ($result->data() as $data) {
                $systemIds[] = $data['message_id'];
            }

            // there are some system messages
            if (count($systemIds) > 0) {
                // remove system messages from list
                $deletedMessages = array_diff($deletedMessages, $systemIds);

                // delete system messages
                $result = $dbEntityMessage->deleteMessagesList($systemIds);
                if ($result->isError()) {
                    throw new Exception('Failed to delete system messages');
                }
            }

            // hide normal messages if necessary
            if (count($deletedMessages) > 0) {
                $result = $dbEntityMessage->hideMessages($deletedMessages, $player->getUsername());
                if ($result->isErrorOrNoEffect()) {
                    throw new Exception('Failed to delete messages');
                }
            }

            $this->result()->setInfo('Messages deleted');
        }
        // case 2: no messages were selected
        else {
            $this->result()->setWarning('No messages selected');
        }
    }
}
