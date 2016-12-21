<?php
/**
 * Card - cards related view module
 */

namespace View;

use ArcomageException as Exception;
use Def\Entity\XmlKeyword;
use Util\Input;

class Card extends TemplateDataAbstract
{
    /**
     * @throws Exception
     * @return Result
     */
    protected function cards()
    {
        $data = array();
        $input = $this->input();

        $defEntityCard = $this->defEntity()->card();

        // load session state
        $data['is_logged_in'] = ($this->isSession()) ? 'yes' : 'no';

        // validate current page
        $currentPage = Input::defaultValue($input, 'cards_current_page', 0);
        if (!is_numeric($currentPage) || $currentPage < 0) {
            throw new Exception('Invalid cards page', Exception::WARNING);
        }
        $data['current_page'] = $currentPage;

        // initialize filters
        $nameFilter = $data['name_filter'] = Input::defaultValue($input, 'name_filter');
        $rarityFilter = $data['rarity_filter'] = Input::defaultValue($input, 'rarity_filter', 'none');
        $costFilter = $data['cost_filter'] = Input::defaultValue($input, 'cost_filter', 'none');
        $keywordFilter = $data['keyword_filter'] = Input::defaultValue($input, 'keyword_filter', 'none');
        $advancedFilter = $data['advanced_filter'] = Input::defaultValue($input, 'advanced_filter', 'none');
        $supportFilter = $data['support_filter'] = Input::defaultValue($input, 'support_filter', 'none');
        $createdFilter = $data['created_filter'] = Input::defaultValue($input, 'created_filter', 'none');
        $modifiedFilter = $data['modified_filter'] = Input::defaultValue($input, 'modified_filter', 'none');
        $levelFilter = $data['level_filter'] = Input::defaultValue($input, 'level_filter', 'none');
        $data['card_sort'] = Input::defaultValue($input, 'card_sort', 'name');;

        // list all card creation dates
        $createdDates = $defEntityCard->listCreationDates();

        // list all card modification dates
        $modifiedDates = $defEntityCard->listModifyDates();

        $data['levels'] = $defEntityCard->levels();
        $data['keywords'] = $defEntityCard->keywords();
        $data['created_dates'] = $createdDates;
        $data['modified_dates'] = $modifiedDates;

        // prepare filters
        $filter = array();
        if ($nameFilter != '') {
            $filter['name'] = $nameFilter;
        }
        if ($rarityFilter != 'none') {
            $filter['rarity'] = $rarityFilter;
        }
        if ($keywordFilter != 'none') {
            $filter['keyword'] = $keywordFilter;
        }
        if ($costFilter != 'none') {
            $filter['cost'] = $costFilter;
        }
        if ($advancedFilter != 'none') {
            $filter['advanced'] = $advancedFilter;
        }
        if ($supportFilter != 'none') {
            $filter['support'] = $supportFilter;
        }
        if ($createdFilter != 'none') {
            $filter['created'] = $createdFilter;
        }
        if ($modifiedFilter != 'none') {
            $filter['modified'] = $modifiedFilter;
        }
        if ($levelFilter != 'none') {
            $filter['level'] = $levelFilter;
            $filter['level_op'] = '=';
        }

        // list cards
        $ids = $defEntityCard->getList($filter);
        $cardList = $defEntityCard->getData($ids);

        // case 1: reindex card list by card cost
        if ($data['card_sort'] == 'cost') {
            $cardsSorted = $cardList;
            usort($cardsSorted, '\Def\Model\Card::compareCardData');
        }
        // case 2: reindex card list by card names
        else {
            $cardsSorted = array();
            foreach ($cardList as $card) {
                $cardsSorted[$card['name']] = $card;
            }

            // sort card list by name alphabetically
            ksort($cardsSorted, SORT_STRING);
        }

        // extract current page
        $cardsSorted = array_slice($cardsSorted, $currentPage * \Def\Model\Card::CARDS_PER_PAGE, \Def\Model\Card::CARDS_PER_PAGE);

        $data['card_list'] = array_values($cardsSorted);
        $data['page_count'] = $defEntityCard->countPages($filter);

        // load card display settings
        $setting = $this->getCurrentSettings();

        $data['card_old_look'] = $setting->getSetting('old_card_look');
        $data['card_insignias'] = $setting->getSetting('keyword_insignia');
        $data['card_foils'] = $setting->getSetting('foil_cards');

        return new Result(['cards' => $data]);
    }

    /**
     * @throws Exception
     * @return Result
     */
    protected function cardsDetails()
    {
        $data = array();
        $input = $this->input();

        $defEntityCard = $this->defEntity()->card();
        $dbEntityThread = $this->dbEntity()->forumThread();
        $dbEntityStatistic = $this->dbEntity()->statistic();

        $this->assertInputNonEmpty(['card']);

        // validate card id
        if (!is_numeric($input['card']) || $input['card'] <= 0) {
            throw new Exception('Invalid card id', Exception::WARNING);
        }
        $cardId = $input['card'];

        // load card data
        $card = $defEntityCard->getCard($cardId);

        // find related forum thread
        $result = $dbEntityThread->cardThread($cardId);
        if ($result->isError()) {
            throw new Exception('Failed to find forum thread by card id');
        }
        $threadId = ($result->isSuccess()) ? $result[0]['thread_id'] : 0;

        // load card statistics
        $result = $dbEntityStatistic->cardStatistics($cardId);
        if ($result->isError()) {
            throw new Exception('Failed to load card statistics');
        }
        if ($result->isNoEffect()) {
            $cardStats = [
                'played' => 0, 'discarded' => 0, 'drawn' => 0,
                'played_total' => 0, 'discarded_total' => 0, 'drawn_total' => 0,
            ];
        }
        else {
            $cardStats = $result[0];
        }

        $score = $this->dbEntity()->score()->getScoreAsserted($this->getCurrentPlayer()->getUsername());

        $data['data'] = $cardData = $card->getData();
        $data['discussion'] = ($threadId) ? $threadId : 0;
        $data['create_thread'] = ($this->checkAccess('create_thread')) ? 'yes' : 'no';
        $data['statistics'] = $cardStats;
        $data['foil_cost'] = \Def\Model\Card::FOIL_COST;
        $data['gold'] = $score->getGold();
        $data['is_logged_in'] = ($this->isSession()) ? 'yes' : 'no';

        // load card display settings
        $setting = $this->getCurrentSettings();

        $data['card_old_look'] = $setting->getSetting('old_card_look');
        $data['card_insignias'] = $setting->getSetting('keyword_insignia');
        $data['card_foils'] = $foil_cards = $setting->getSetting('foil_cards');

        // determine if current card has a foil version
        $foil_cards = ($foil_cards == '') ? [] : explode(",", $foil_cards);
        $data['foil_version'] = (in_array($cardId, $foil_cards)) ? 'yes' : 'no';

        return new Result(['cards_details' => $data], $cardData['name']);
    }

    /**
     * @throws Exception
     * @return Result
     */
    protected function cardsLookup()
    {
        $data = array();
        $input = $this->input();

        $defEntityCard = $this->defEntity()->card();

        $this->assertInputExist(['card']);

        // validate card id
        if (!is_numeric($input['card'])) {
            throw new Exception('Invalid card id', Exception::WARNING);
        }
        $cardId = $input['card'];

        // load card data
        $card = $defEntityCard->getCard($cardId);

        $data['data'] = $cardData = $card->getData();

        // load card display settings
        $setting = $this->getCurrentSettings();

        $data['card_old_look'] = $setting->getSetting('old_card_look');
        $data['card_insignias'] = $setting->getSetting('keyword_insignia');
        $data['card_foils'] = $setting->getSetting('foil_cards');

        return new Result(['cards_lookup' => $data], $cardData['name']);
    }

    /**
     * @throws Exception
     * @return Result
     */
    protected function cardsKeywords()
    {
        $data = array();
        $input = $this->input();

        $defEntityKeyword = $this->defEntity()->keyword();

        // determine sorting order
        $order = (isset($input['keywords_order']) && in_array($input['keywords_order'], ['name', 'execution']))
            ? $input['keywords_order'] : 'name';

        // list all keywords
        $result = $defEntityKeyword->listKeywords();
        if ($result->isError()) {
            throw new Exception('Failed to list keywords');
        }

        // determine execution sort order
        $keywordsOrder = array_flip(XmlKeyword::keywordsOrder());

        $keywords = array();
        foreach ($result->data() as $i => $keywordData) {
            $keywordName = $keywordData['name'];

            // only add keywords that are white listed
            if (isset($keywordsOrder[$keywordName])) {
                // determine sorting key
                $key = ($order == 'execution') ? $keywordsOrder[$keywordName] : $keywordName;

                // add keyword execution order (we start counting from 1 not 0)
                $keywordData['order'] = $keywordsOrder[$keywordName] + 1;

                $keywords[$key] = $keywordData;
            }
        }

        ksort($keywords);

        $data['keywords'] = array_values($keywords);
        $data['order'] = $order;

        return new Result(['cards_keywords' => $data], 'Keywords');
    }

    /**
     * @return Result
     */
    protected function cardsKeywordDetails()
    {
        $data = array();
        $input = $this->input();

        $data['name'] = $subsectionName = Input::defaultValue($input, 'keyword');

        return new Result(['keyword_details' => $data], $subsectionName);
    }
}
