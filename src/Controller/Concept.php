<?php
/**
 * Concept - concepts related controller
 */

namespace Controller;

use ArcomageException as Exception;
use Db\Model\ForumThread;
use Util\Date;
use Util\Input;

class Concept extends ControllerAbstract
{
    /**
     * Select ascending order in card concepts list
     */
    protected function conceptsOrderAsc()
    {
        $request = $this->request();

        $this->result()
            ->changeRequest('concepts_current_condition', $request['concepts_order_asc'])
            ->changeRequest('concepts_current_order', 'ASC')
            ->setCurrent('Concepts');
    }

    /**
     * Select descending order in card concepts list
     */
    protected function conceptsOrderDesc()
    {
        $request = $this->request();

        $this->result()
            ->changeRequest('concepts_current_condition', $request['concepts_order_desc'])
            ->changeRequest('concepts_current_order', 'DESC')
            ->setCurrent('Concepts');
    }

    /**
     * Use filter
     */
    protected function conceptsApplyFilters()
    {
        $this->result()
            ->changeRequest('concepts_current_page', 0)
            ->setCurrent('Concepts');
    }

    /**
     * Use "my cards" quick button
     */
    protected function showMyConcepts()
    {
        $player = $this->getCurrentPlayer();

        $this->result()
            ->changeRequest('date_filter_concepts', 'none')
            ->changeRequest('author_filter', $player->getUsername())
            ->changeRequest('state_filter', 'none')
            ->changeRequest('concepts_current_page', 0)
            ->setCurrent('Concepts');
    }

    /**
     * Select page (previous and next button)
     */
    protected function conceptsSelectPage()
    {
        $request = $this->request();

        $this->result()
            ->changeRequest('concepts_current_page', $request['concepts_select_page'])
            ->setCurrent('Concepts');
    }

    /**
     * Go to new card form
     * @throws Exception
     */
    protected function newConcept()
    {
        $this->result()->setCurrent('Concepts');

        // check access rights
        if (!$this->checkAccess('create_card')) {
            throw new Exception('Access denied', Exception::WARNING);
        }

        $this->result()->setCurrent('Concepts_new');
    }

    /**
     * Create new card concept
     * @throws Exception
     */
    protected function createConcept()
    {
        $request = $this->request();

        $player = $this->getCurrentPlayer();
        $dbEntityConcept = $this->dbEntity()->concept();

        $this->result()->setCurrent('Concepts_new');

        // check access rights
        if (!$this->checkAccess('create_card')) {
            $this->result()->setCurrent('Concepts');
            throw new Exception('Access denied', Exception::WARNING);
        }

        // validate user inputs
        $data = array();
        $inputs = ['name', 'rarity', 'bricks', 'gems', 'recruits', 'effect', 'keywords', 'note'];
        $this->assertParamsExist($inputs);

        foreach ($inputs as $input) {
            $data[$input] = $request[$input];
        }

        // add default cost values
        if (trim($data['bricks']) == '') {
            $data['bricks'] = 0;
        }
        if (trim($data['gems']) == '') {
            $data['gems'] = 0;
        }
        if (trim($data['recruits']) == '') {
            $data['recruits'] = 0;
        }

        $data['author'] = $player->getUsername();

        $this->service()->concept()->checkInputs($data);

        // create new card concept
        $concept = $dbEntityConcept->createConcept($data);
        if (!$concept->save()) {
            throw new Exception('Failed to create new card');
        }

        $this->result()
            ->changeRequest('current_concept', $concept->getCardId())
            ->setInfo('New card created')
            ->setCurrent('Concepts_edit');
    }

    /**
     * Go to card edit form
     * @throws Exception
     */
    protected function editConcept()
    {
        $request = $this->request();

        $player = $this->getCurrentPlayer();

        $this->result()->setCurrent('Concepts');
        $conceptId = $request['edit_concept'];

        $concept = $this->dbEntity()->concept()->getConceptAsserted($conceptId);

        // check access rights
        if (!($this->checkAccess('edit_all_card') || ($this->checkAccess('edit_own_card')
                && $player->getUsername() == $concept->getAuthor()))) {
            throw new Exception('Access denied', Exception::WARNING);
        }

        $this->result()
            ->changeRequest('current_concept', $conceptId)
            ->setCurrent('Concepts_edit');
    }

    /**
     * Save edited changes
     * @throws Exception
     */
    protected function saveConcept()
    {
        $request = $this->request();
        $player = $this->getCurrentPlayer();
        $dbEntityThread = $this->dbEntity()->forumThread();

        $this->result()->setCurrent('Concepts');

        $this->assertParamsNonEmpty(['current_concept']);
        $conceptId = $request['current_concept'];
        $concept = $this->dbEntity()->concept()->getConceptAsserted($conceptId);

        // check access rights
        if (!($this->checkAccess('edit_all_card') || ($this->checkAccess('edit_own_card')
                && $player->getUsername() == $concept->getAuthor()))) {
            throw new Exception('Access denied', Exception::WARNING);
        }

        $this->result()->setCurrent('Concepts_edit');

        // validate user input
        $data = array();
        $inputs = ['name', 'rarity', 'bricks', 'gems', 'recruits', 'effect', 'keywords', 'note'];
        $this->assertParamsExist($inputs);

        foreach ($inputs as $input) {
            $data[$input] = $request[$input];
        }

        // store concept name in case it will be changed
        $oldName = $concept->getName();
        $newName = $data['name'];

        // find related forum thread
        $result = $dbEntityThread->conceptThread($concept->getCardId());
        if ($result->isError()) {
            throw new Exception('Failed to find forum thread by concept id');
        }
        $threadId = ($result->isSuccess()) ? $result[0]['thread_id'] : 0;

        // add default cost values
        if (trim($data['bricks']) == '') {
            $data['bricks'] = 0;
        }
        if (trim($data['gems']) == '') {
            $data['gems'] = 0;
        }
        if (trim($data['recruits']) == '') {
            $data['recruits'] = 0;
        }

        $this->service()->concept()->checkInputs($data);

        // update concept data
        $concept
            ->setName($data['name'])
            ->setRarity($data['rarity'])
            ->setBricks($data['bricks'])
            ->setGems($data['gems'])
            ->setRecruits($data['recruits'])
            ->setEffect($data['effect'])
            ->setKeywords($data['keywords'])
            ->setNote($data['note'])
            ->setModifiedAt(Date::timeToStr());

        if (!$concept->save()) {
            throw new Exception('Failed to save changes');
        }

        // update corresponding thread name if necessary
        if (trim($oldName) != trim($newName) && $threadId > 0) {
            $thread = $this->dbEntity()->forumThread()->getThread($threadId);
            if (!empty($thread)) {
                $thread
                    ->setTitle($newName)
                    ->setPriority('normal');

                if (!$thread->save()) {
                    throw new Exception('Failed to rename thread');
                }
            }
        }

        $this->result()->setInfo('Changes saved');
    }

    /**
     * Save edited changes (special access)
     * @throws Exception
     */
    protected function saveConceptSpecial()
    {
        $request = $this->request();
        $dbEntityConcept = $this->dbEntity()->concept();
        $dbEntityThread = $this->dbEntity()->forumThread();

        $this->result()->setCurrent('Concepts');

        $this->assertParamsNonEmpty(['current_concept']);
        $conceptId = $request['current_concept'];

        // check access rights
        if (!$this->checkAccess('edit_all_card')) {
            throw new Exception('Access denied', Exception::WARNING);
        }

        $concept = $dbEntityConcept->getConceptAsserted($conceptId);

        $this->result()->setCurrent('Concepts_edit');

        // validate user input
        $data = array();
        $inputs = ['name', 'rarity', 'bricks', 'gems', 'recruits', 'effect', 'keywords', 'note', 'state'];
        $this->assertParamsExist($inputs);

        foreach ($inputs as $input) {
            $data[$input] = $request[$input];
        }

        // store concept name in case it will be changed
        $oldName = $concept->getName();
        $newName = $data['name'];

        // find related forum thread
        $result = $dbEntityThread->conceptThread($concept->getCardId());
        if ($result->isError()) {
            throw new Exception('Failed to find forum thread by concept id');
        }
        $threadId = ($result->isSuccess()) ? $result[0]['thread_id'] : 0;

        // add default cost values
        if (trim($data['bricks']) == '') {
            $data['bricks'] = 0;
        }
        if (trim($data['gems']) == '') {
            $data['gems'] = 0;
        }
        if (trim($data['recruits']) == '') {
            $data['recruits'] = 0;
        }

        $this->service()->concept()->checkInputs($data);

        // update concept data
        $concept
            ->setName($data['name'])
            ->setRarity($data['rarity'])
            ->setBricks($data['bricks'])
            ->setGems($data['gems'])
            ->setRecruits($data['recruits'])
            ->setEffect($data['effect'])
            ->setKeywords($data['keywords'])
            ->setNote($data['note'])
            ->setState($data['state']);

        if (!$concept->save()) {
            throw new Exception('Failed to save changes');
        }

        // update corresponding thread name if necessary
        if (trim($oldName) != trim($newName) && $threadId > 0) {
            $thread = $this->dbEntity()->forumThread()->getThread($threadId);
            if (!empty($thread)) {
                $thread
                    ->setTitle($newName)
                    ->setPriority('normal');

                if (!$thread->save()) {
                    throw new Exception('Failed to rename thread');
                }
            }
        }

        $this->result()->setInfo('Changes saved');
    }

    /**
     * Upload card picture
     * @throws Exception
     */
    protected function uploadConceptImage()
    {
        $request = $this->request();
        $player = $this->getCurrentPlayer();
        $files = $this->getDic()->files();
        $config = $this->getDic()->config();

        $this->result()->setCurrent('Concepts');

        $this->assertParamsNonEmpty(['current_concept']);
        $conceptId = $request['current_concept'];

        $concept = $this->dbEntity()->concept()->getConceptAsserted($conceptId);

        // check access rights
        if (!($this->checkAccess('edit_all_card') || ($this->checkAccess('edit_own_card')
                && $player->getUsername() == $concept->getAuthor()))) {
            throw new Exception('Access denied', Exception::WARNING);
        }

        $this->result()->setCurrent('Concepts_edit');

        // determine concept picture paths
        $formerName = $concept->getPicture();
        $uploadDir = $config['upload_dir']['concept'];
        $formerPath = $uploadDir . $formerName;

        $type = $files['concept_image_file']['type'];
        $pos = strrpos($type, "/") + 1;

        $codeType = substr($type, $pos, mb_strlen($type) - $pos);
        $filteredName = preg_replace("/[^a-zA-Z0-9_-]/i", "_", $player->getUsername());

        $codeName = time() . $filteredName . '.' . $codeType;
        $targetPath = $uploadDir . $codeName;

        // validate upload name
        if ($files['concept_image_file']['tmp_name'] == '') {
            throw new Exception('Invalid input file', Exception::WARNING);
        }

        // validate file size
        if ($files['concept_image_file']['size'] > \Db\Model\Concept::UPLOAD_SIZE) {
            throw new Exception('File is too big', Exception::WARNING);
        }

        // validate file type
        if (!in_array($files['concept_image_file']['type'], Input::imageUploadTypes())) {
            throw new Exception('Unsupported input file', Exception::WARNING);
        }

        // upload file
        if (move_uploaded_file($files['concept_image_file']['tmp_name'], $targetPath) == false) {
            throw new Exception('Upload failed, error code ' . $files['concept_image_file']['error'], Exception::WARNING);
        }

        // remove old concept picture if it exists
        if (file_exists($formerPath) && $formerName != 'blank.jpg') {
            unlink($formerPath);
        }

        // set new picture
        $concept
            ->setPicture($codeName)
            ->setModifiedAt(Date::timeToStr());

        if (!$concept->save()) {
            throw new Exception('Failed to save changes');
        }

        $this->result()->setInfo('Picture uploaded');
    }

    /**
     * Clear card picture
     * @throws Exception
     */
    protected function clearConceptImage()
    {
        $request = $this->request();
        $config = $this->getDic()->config();
        $player = $this->getCurrentPlayer();

        $this->result()->setCurrent('Concepts');

        $this->assertParamsNonEmpty(['current_concept']);
        $conceptId = $request['current_concept'];

        $concept = $this->dbEntity()->concept()->getConceptAsserted($conceptId);

        // check access rights
        if (!($this->checkAccess('edit_all_card') || ($this->checkAccess('edit_own_card')
                && $player->getUsername() == $concept->getAuthor()))) {
            throw new Exception('Access denied', Exception::WARNING);
        }

        $this->result()->setCurrent('Concepts_edit');

        // determine concept picture path
        $formerName = $concept->getPicture();
        $formerPath = $config['upload_dir']['concept'] . $formerName;

        // remove concept picture
        if (file_exists($formerPath) && $formerName != 'blank.jpg') {
            unlink($formerPath);
        }

        // reset concept picture to default
        $concept
            ->setPicture('blank.jpg')
            ->setModifiedAt(Date::timeToStr());

        if (!$concept->save()) {
            throw new Exception('Failed to save changes');
        }

        $this->result()->setInfo('Card picture cleared');
    }

    /**
     * Delete card concept
     * @throws Exception
     */
    protected function deleteConcept()
    {
        $request = $this->request();
        $player = $this->getCurrentPlayer();

        $this->result()->setCurrent('Concepts');
        $conceptId = $request['delete_concept'];

        $concept = $this->dbEntity()->concept()->getConceptAsserted($conceptId);

        // check access rights
        if (!($this->checkAccess('edit_all_card') || ($this->checkAccess('edit_own_card')
                && $player->getUsername() == $concept->getAuthor()))) {
            throw new Exception('Access denied', Exception::WARNING);
        }

        $this->result()
            ->changeRequest('current_concept', $conceptId)
            ->setCurrent('Concepts_edit');
    }

    /**
     * Delete card concept confirmation
     * @throws Exception
     */
    protected function deleteConceptConfirm()
    {
        $request = $this->request();
        $player = $this->getCurrentPlayer();
        $dbEntityThread = $this->dbEntity()->forumThread();

        $this->result()->setCurrent('Concepts');

        $this->assertParamsNonEmpty(['current_concept']);
        $conceptId = $request['current_concept'];

        $concept = $this->dbEntity()->concept()->getConceptAsserted($conceptId);

        // find related forum thread
        $result = $dbEntityThread->conceptThread($concept->getCardId());
        if ($result->isError()) {
            throw new Exception('Failed to find forum thread by concept id');
        }
        $threadId = ($result->isSuccess()) ? $result[0]['thread_id'] : 0;

        // extract concept data relevant for related forum thread
        $conceptName = $concept->getName();

        // check access rights
        if (!($this->checkAccess('edit_all_card') || ($this->checkAccess('edit_own_card')
                && $player->getUsername() == $concept->getAuthor()))) {
            throw new Exception('Access denied', Exception::WARNING);
        }

        // delete concept
        $concept->markDeleted();
        if (!$concept->save()) {
            $this->result()->setCurrent('Concepts_edit');
            throw new Exception('Failed to delete card');
        }

        // update related forum thread if necessary
        if ($threadId > 0) {
            $thread = $this->dbEntity()->forumThread()->getThread($threadId);
            if (!empty($thread)) {
                $thread
                    ->setTitle($conceptName . ' [Deleted]')
                    ->setPriority('normal');

                if (!$thread->save()) {
                    throw new Exception('Failed to rename thread');
                }
            }
        }

        $this->result()->setInfo('Card deleted');
    }

    /**
     * Create new thread for specified card concept
     * @throws Exception
     */
    protected function findConceptThread()
    {
        $request = $this->request();
        $player = $this->getCurrentPlayer();
        $dbEntityThread = $this->dbEntity()->forumThread();

        $this->result()->setCurrent('Concepts');

        $this->assertParamsNonEmpty(['current_concept']);

        $conceptId = $request['current_concept'];

        // check access rights
        if (!$this->checkAccess('create_thread')) {
            $this->result()->setCurrent('Concepts_details');
            throw new Exception('Access denied', Exception::WARNING);
        }

        $concept = $this->dbEntity()->concept()->getConceptAsserted($conceptId);
        $this->result()->setCurrent('Concepts_details');

        // find related forum thread
        $result = $dbEntityThread->conceptThread($concept->getCardId());
        if ($result->isError()) {
            throw new Exception('Failed to find forum thread by concept id');
        }
        $threadId = ($result->isSuccess()) ? $result[0]['thread_id'] : 0;

        // check if related forum thread already exists
        if ($threadId > 0) {
            $this->result()
                ->changeRequest('current_thread', $threadId)
                ->setCurrent('Forum_thread');

            throw new Exception('Thread already exists', Exception::WARNING);
        }

        // create new concept thread
        $newThread = $dbEntityThread->createThread(
            $concept->getName(), $player->getUsername(), 'normal', ForumThread::CONCEPTS_SECTION_ID, $concept->getCardId()
        );
        if (!$newThread->save()) {
            throw new Exception('Failed to create new thread');
        }

        $this->result()
            ->changeRequest('current_thread', $newThread->getThreadId())
            ->setInfo('Thread created')
            ->setCurrent('Forum_thread');
    }
}
