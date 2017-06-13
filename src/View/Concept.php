<?php
/**
 * Concept - concepts related view module
 */

namespace View;

use ArcomageException as Exception;
use Def\Model\Card;

class Concept extends TemplateDataAbstract
{
    /**
     * @throws Exception
     * @return Result
     */
    protected function concepts()
    {
        $data = array();
        $input = $this->input();

        $config = $this->getDic()->config();
        $player = $this->getCurrentPlayer();
        $dbEntityConcept = $this->dbEntity()->concept();

        // determine session state
        $data['is_logged_in'] = ($this->isSession()) ? 'yes' : 'no';

        // filter initialization
        $data['card_name'] = $name = (isset($input['card_name'])) ? trim($input['card_name']) : '';
        $data['date_val'] = $date = (isset($input['date_filter_concepts'])) ? $input['date_filter_concepts'] : 'none';
        $data['author_val'] = $author = (isset($input['author_filter'])) ? $input['author_filter'] : 'none';
        $data['state_val'] = $state = (isset($input['state_filter'])) ? $input['state_filter'] : 'none';

        // default ordering and condition
        $data['current_order'] = $order = (isset($input['concepts_current_order'])) ? $input['concepts_current_order'] : 'DESC';
        $data['current_condition'] = $condition = (isset($input['concepts_current_condition'])) ? $input['concepts_current_condition'] : 'modified_at';

        // validate current page
        $currentPage = ((isset($input['concepts_current_page'])) ? $input['concepts_current_page'] : 0);
        if (!is_numeric($currentPage) || $currentPage < 0) {
            throw new Exception('Invalid concepts page', Exception::WARNING);
        }
        $data['current_page'] = $currentPage;

        $listParams = [
            'name' => $name,
            'author' => $author,
            'date' => $date,
            'state' => $state,
            'condition' => $condition,
            'order' => $order,
            'page' => $currentPage,
        ];

        // list concepts
        $result = $dbEntityConcept->getList($listParams);
        if ($result->isError()) {
            throw new Exception('Failed to list concepts');
        }
        $concepts = $result->data();

        // add upload dir prefix to concept picture
        foreach ($concepts as $key => $concept) {
            $concepts[$key]['picture'] = $config['upload_dir']['concept'] . $concept['picture'];
        }

        // count pages for concepts list
        $result = $dbEntityConcept->countPages($listParams);
        if ($result->isErrorOrNoEffect()) {
            throw new Exception('Failed to count pages for concepts list');
        }
        $pages = ceil($result[0]['count'] / Card::CARDS_PER_PAGE);

        $data['list'] = $concepts;
        $data['page_count'] = $pages;

        // load card display settings
        $setting = $this->getCurrentSettings();

        // list concepts authors
        $result = $dbEntityConcept->listAuthors($date);
        if ($result->isError()) {
            throw new Exception('Failed to list concept authors');
        }
        $authors = array();
        foreach ($result->data() as $authorData) {
            $authors[] = $authorData['author'];
        }

        $data['notification'] = $player->getNotification();
        $data['authors'] = $authors;
        $data['my_cards'] = (in_array($player->getUsername(), $authors) ? 'yes' : 'no');
        $data['timezone'] = $setting->getSetting('timezone');
        $data['player_name'] = $player->getUsername();
        $data['create_card'] = ($this->checkAccess('create_card')) ? 'yes' : 'no';
        $data['edit_own_card'] = ($this->checkAccess('edit_own_card')) ? 'yes' : 'no';
        $data['edit_all_card'] = ($this->checkAccess('edit_all_card')) ? 'yes' : 'no';
        $data['delete_own_card'] = ($this->checkAccess('delete_own_card')) ? 'yes' : 'no';
        $data['delete_all_card'] = ($this->checkAccess('delete_all_card')) ? 'yes' : 'no';
        $data['card_old_look'] = $setting->getSetting('old_card_look');

        return new Result(['concepts' => $data]);
    }

    /**
     * @throws Exception
     * @return Result
     */
    protected function conceptsNew()
    {
        $data = array();
        $input = $this->input();

        // in case there is some user input provided, use it to fill form data
        $conceptData = array();
        $inputs = ['name', 'rarity', 'bricks', 'gems', 'recruits', 'effect', 'keywords', 'note'];
        foreach ($inputs as $field) {
            if (isset($input[$field])) {
                $conceptData[$field] = $input[$field];
            }
        }

        $data['data'] = $conceptData;

        // determine if user input data should be used or not
        $data['stored'] = (count($conceptData) > 0) ? 'yes' : 'no';

        return new Result(['concepts_new' => $data], 'New concept');
    }

    /**
     * @throws Exception
     * @return Result
     */
    protected function conceptsEdit()
    {
        $data = array();
        $input = $this->input();

        $config = $this->getDic()->config();
        $player = $this->getCurrentPlayer();

        // validate concept id
        $this->assertInputNonEmpty(['current_concept']);
        if (!is_numeric($input['current_concept']) || $input['current_concept'] <= 0) {
            throw new Exception('Invalid concept id', Exception::WARNING);
        }

        $concept = $this->dbEntity()->concept()->getConceptAsserted($input['current_concept']);
        $setting = $this->getCurrentSettings();

        // add upload dir prefix to concept picture
        $conceptData = $concept->getData();
        $conceptData['picture'] = $config['upload_dir']['concept'] . $conceptData['picture'];

        $data['data'] = $conceptData;
        $data['edit_all_card'] = ($this->checkAccess('edit_all_card')) ? 'yes' : 'no';
        $data['delete_own_card'] = ($this->checkAccess('delete_own_card')) ? 'yes' : 'no';
        $data['delete_all_card'] = ($this->checkAccess('delete_all_card')) ? 'yes' : 'no';
        $data['player_name'] = $player->getUsername();
        $data['delete'] = (isset($input['delete_concept'])) ? 'yes' : 'no';
        $data['card_old_look'] = $setting->getSetting('old_card_look');

        return new Result(['concepts_edit' => $data], $concept->getName());
    }

    /**
     * @throws Exception
     * @return Result
     */
    protected function conceptsDetails()
    {
        $data = array();
        $input = $this->input();

        $config = $this->getDic()->config();
        $dbEntityThread = $this->dbEntity()->forumThread();

        // validate concept id
        $this->assertInputNonEmpty(['current_concept']);
        if (!is_numeric($input['current_concept']) || $input['current_concept'] <= 0) {
            throw new Exception('Invalid concept id', Exception::WARNING);
        }

        $concept = $this->dbEntity()->concept()->getConceptAsserted($input['current_concept']);
        $setting = $this->getCurrentSettings();

        // add upload dir prefix to concept picture
        $conceptData = $concept->getData();
        $conceptData['picture'] = $config['upload_dir']['concept'] . $conceptData['picture'];

        // find related forum thread
        $result = $dbEntityThread->conceptThread($concept->getCardId());
        if ($result->isError()) {
            throw new Exception('Failed to find forum thread by concept id');
        }
        $threadId = ($result->isSuccess()) ? $result[0]['thread_id'] : 0;

        $data['data'] = $conceptData;
        $data['discussion'] = ($threadId) ? $threadId : 0;
        $data['create_thread'] = ($this->checkAccess('create_thread')) ? 'yes' : 'no';
        $data['edit_all_card'] = ($this->checkAccess('edit_all_card')) ? 'yes' : 'no';
        $data['delete_own_card'] = ($this->checkAccess('delete_own_card')) ? 'yes' : 'no';
        $data['delete_all_card'] = ($this->checkAccess('delete_all_card')) ? 'yes' : 'no';
        $data['card_old_look'] = $setting->getSetting('old_card_look');

        return new Result(['concepts_details' => $data], $concept->getName());
    }
}
