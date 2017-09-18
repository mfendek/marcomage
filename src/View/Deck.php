<?php
/**
 * Deck - decks related view module
 */

namespace View;

use ArcomageException as Exception;
use Db\Model\Player as PlayerModel;
use Def\Entity\XmlKeyword;

class Deck extends TemplateDataAbstract
{
    /**
     * @throws Exception
     * @return Result
     */
    protected function decksEdit()
    {
        $data = array();
        $input = $this->input();

        $player = $this->getCurrentPlayer();
        $defEntityCard = $this->defEntity()->card();

        // list all card creation dates
        $createdDates = $defEntityCard->listCreationDates();

        // list all card modification dates
        $modifiedDates = $defEntityCard->listModifyDates();

        // fetch player's level
        $score = $this->dbEntity()->score()->getScoreAsserted($player->getUsername());
        $playerLevel = $score->getLevel();

        // determine initial date created filter
        // players in tutorial are given no filters selected to avoid confusion (they are limited by card level anyway)
        // all other players are given created filter set to most recent batch of new cards to prevent unnecessary card image loading
        $initialValue = ($playerLevel >= PlayerModel::TUTORIAL_END && count($createdDates) > 0)
            ? $createdDates[0] : 'none';

        // initialize filters
        $deckId = $data['current_deck'] = isset($input['current_deck']) ? $input['current_deck'] : '';
        $nameFilter = $data['name_filter'] = isset($input['name_filter']) ? $input['name_filter'] : '';
        $rarityFilter = $data['rarity_filter'] = isset($input['rarity_filter']) ? $input['rarity_filter'] : 'none';
        $costFilter = $data['cost_filter'] = isset($input['cost_filter']) ? $input['cost_filter'] : 'none';
        $keywordFilter = $data['keyword_filter'] = isset($input['keyword_filter']) ? $input['keyword_filter'] : 'none';
        $advancedFilter = $data['advanced_filter'] = isset($input['advanced_filter']) ? $input['advanced_filter'] : 'none';
        $supportFilter = $data['support_filter'] = isset($input['support_filter']) ? $input['support_filter'] : 'none';
        $createdFilter = $data['created_filter'] = isset($input['created_filter']) ? $input['created_filter'] : $initialValue;
        $modifiedFilter = $data['modified_filter'] = isset($input['modified_filter']) ? $input['modified_filter'] : 'none';
        $levelFilter = $data['level_filter'] = isset($input['level_filter']) ? $input['level_filter'] : 'none';
        $data['card_sort'] = isset($input['card_sort']) ? $input['card_sort'] : 'name';

        $data['player_level'] = $playerLevel;
        $data['tutorial_end'] = PlayerModel::TUTORIAL_END;
        $data['levels'] = $defEntityCard->levels($playerLevel);
        $data['keywords'] = $defEntityCard->keywords();
        $data['created_dates'] = $createdDates;
        $data['modified_dates'] = $modifiedDates;

        // download the necessary data
        $deck = $this->dbEntity()->deck()->getDeckAsserted($deckId);

        // validate deck ownership
        if ($deck->getUsername() != $player->getUsername()) {
            throw new Exception('Can only edit own deck', Exception::WARNING);
        }

        $data['reset'] = (isset($input['reset_deck_prepare'])) ? 'yes' : 'no';
        $data['reset_stats'] = (isset($input['reset_stats_prepare'])) ? 'yes' : 'no';
        $data['toggle_edit'] = (isset($input['toggle_edit_show']) && $input['toggle_edit_show']) ? 'yes' : 'no';
        $data['toggle_stats'] = (isset($input['toggle_stats_show']) && $input['toggle_stats_show']) ? 'yes' : 'no';

        // load card display settings
        $setting = $this->getCurrentSettings();

        $data['card_old_look'] = $setting->getSetting('old_card_look');
        $data['card_insignias'] = $setting->getSetting('keyword_insignia');
        $data['card_foils'] = $setting->getSetting('foil_cards');
        // calculate average cost per turn
        $data['avg_cost'] = $this->service()->deck()->avgCostPerTurn($deck);
        $data['card_pool'] = (isset($input['card_pool']) && $input['card_pool'] == 'no') ? 'no' : 'yes';

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
        } else {
            $filter['level'] = $playerLevel;
        }

        // cards not present in the card pool
        $excluded = array_merge($deck->getData()->Common, $deck->getData()->Uncommon, $deck->getData()->Rare);

        // load card data
        $cardList = $defEntityCard->getData($defEntityCard->getList($filter));

        foreach ($cardList as $i => $cardData) {
            // excluded cards are all cards that are already present in the deck
            $cardList[$i]['excluded'] = (in_array($cardData['id'], $excluded)) ? 'yes' : 'no';

            // locked cards are cards with Forbidden keyword
            $cardList[$i]['locked'] = (strpos($cardData['keywords'], 'Forbidden') !== false) ? 'yes' : 'no';
        }

        $data['card_list'] = $cardList;

        foreach (['Common', 'Uncommon', 'Rare'] as $rarity) {
            $cardList = $defEntityCard->getData($deck->getData()->$rarity);

            $data['deck_cards'][$rarity] = $cardList;
        }

        $data['deck_name'] = $deck->getDeckName();
        $data['wins'] = $deck->getWins();
        $data['losses'] = $deck->getLosses();
        $data['draws'] = $deck->getDraws();
        $data['tokens'] = $deck->getData()->Tokens;
        $data['token_keywords'] = XmlKeyword::tokenKeywords();
        $data['note'] = $deck->getNote();
        $data['shared'] = ($deck->getIsShared() == 1) ? 'yes' : 'no';

        return new Result(['deck_edit' => $data], $deck->getDeckName());
    }

    /**
     * @throws Exception
     * @return Result
     */
    protected function decksNote()
    {
        $data = array();
        $input = $this->input();

        $player = $this->getCurrentPlayer();

        // validate deck id
        $this->assertInputNonEmpty(['current_deck']);
        if (!is_numeric($input['current_deck']) || $input['current_deck'] <= 0) {
            throw new Exception('Invalid deck id', Exception::WARNING);
        }
        $deckId = $input['current_deck'];

        $deck = $this->dbEntity()->deck()->getDeckAsserted($deckId);

        // validate deck ownership
        if ($deck->getUsername() != $player->getUsername()) {
            throw new Exception('Can only edit own deck', Exception::WARNING);
        }

        $data['current_deck'] = $deckId;

        // determine if user input should be used or not
        $data['text'] = (isset($input['content'])) ? $input['content'] : $deck->getNote();

        return new Result(['deck_note' => $data]);
    }

    /**
     * @throws Exception
     * @return Result
     */
    protected function decks()
    {
        $data = array();

        $player = $this->getCurrentPlayer();
        $dbEntityDeck = $this->dbEntity()->deck();

        // fetch player's level
        $score = $this->dbEntity()->score()->getScoreAsserted($player->getUsername());

        // list player's decks
        $result = $dbEntityDeck->listDecks($player->getUsername());
        if ($result->isError()) {
            throw new Exception("Failed to list player's decks");
        }
        $decks = $result->data();

        $setting = $this->getCurrentSettings();

        $data['player_level'] = $score->getLevel();
        $data['tutorial_end'] = PlayerModel::TUTORIAL_END;
        $data['list'] = $decks;
        $data['timezone'] = $setting->getSetting('timezone');

        return new Result(['decks' => $data]);
    }

    /**
     * @throws Exception
     * @return Result
     */
    protected function decksShared()
    {
        $data = array();
        $input = $this->input();

        $player = $this->getCurrentPlayer();
        $dbEntityDeck = $this->dbEntity()->deck();

        // initialize filter
        $data['author_val'] = $author = (isset($input['author_filter'])) ? $input['author_filter'] : 'none';

        // default ordering and condition
        $data['current_order'] = $order = (isset($input['decks_current_order'])) ? $input['decks_current_order'] : 'DESC';
        $data['current_condition'] = $condition = (isset($input['decks_current_condition'])) ? $input['decks_current_condition'] : 'modified_at';

        // validate current page
        $currentPage = ((isset($input['decks_current_page'])) ? $input['decks_current_page'] : 0);
        if (!is_numeric($currentPage) || $currentPage < 0) {
            throw new Exception('Invalid decks page', Exception::WARNING);
        }
        $data['current_page'] = $currentPage;

        // list shared decks
        $result = $dbEntityDeck->listSharedDecks($author, $condition, $order, $currentPage);
        if ($result->isError()) {
            throw new Exception('Failed to list shared decks');
        }
        $sharedDecks = $result->data();

        // count pages for shared decks list
        $result = $dbEntityDeck->countPages($author);
        if ($result->isErrorOrNoEffect()) {
            throw new Exception('Failed to count pages for shared decks');
        }
        $pages = ceil($result[0]['count'] / \Db\Model\Deck::DECKS_PER_PAGE);

        // list deck authors
        $result = $dbEntityDeck->listAuthors();
        if ($result->isError()) {
            throw new Exception('Failed to list authors for shared decks');
        }

        $authors = array();
        foreach ($result->data() as $authorData) {
            $authors[] = $authorData['username'];
        }

        // list player's decks
        $result = $dbEntityDeck->listDecks($player->getUsername());
        if ($result->isError()) {
            throw new Exception("Failed to list player's decks");
        }
        $decks = $result->data();

        $setting = $this->getCurrentSettings();

        $data['shared_list'] = $sharedDecks;
        $data['page_count'] = $pages;
        $data['authors'] = $authors;
        $data['decks'] = $decks;
        $data['timezone'] = $setting->getSetting('timezone');

        return new Result(['decks_shared' => $data]);
    }

    /**
     * @throws Exception
     * @return Result
     */
    protected function decksDetails()
    {
        $data = array();
        $input = $this->input();

        $defEntityCard = $this->defEntity()->card();
        $dbEntityThread = $this->dbEntity()->forumThread();

        // validate deck id
        $this->assertInputNonEmpty(['current_deck']);
        if (!is_numeric($input['current_deck']) || $input['current_deck'] <= 0) {
            throw new Exception('Invalid deck id', Exception::WARNING);
        }
        $deckId = $input['current_deck'];

        // load shared deck
        $deck = $this->dbEntity()->deck()->getDeckAsserted($deckId);

        // validate deck
        if ($deck->getIsShared() == 0) {
            throw new Exception('Selected deck is not shared', Exception::WARNING);
        }
        if (!$deck->isReady()) {
            throw new Exception('Selected deck is incomplete', Exception::WARNING);
        }

        // process tokens
        $tokens = array();
        foreach ($deck->getData()->Tokens as $tokenName) {
            if ($tokenName != 'none') {
                $tokens[] = $tokenName;
            }
        }

        // load needed settings
        $setting = $this->getCurrentSettings();

        $data['deck_id'] = $deck->getDeckId();
        $data['deck_name'] = $deck->getDeckName();
        $data['card_old_look'] = $setting->getSetting('old_card_look');
        $data['card_insignias'] = $setting->getSetting('keyword_insignia');
        $data['card_foils'] = $setting->getSetting('foil_cards');
        $data['tokens'] = (count($tokens) > 0) ? implode(", ", $tokens) : '';

        // calculate average cost per turn
        $data['avg_cost'] = $this->service()->deck()->avgCostPerTurn($deck);
        $data['note'] = $deck->getNote();

        foreach (['Common', 'Uncommon', 'Rare'] as $rarity) {
            $data['deck_cards'][$rarity] = $defEntityCard->getData($deck->getData()->$rarity);
        }

        // find related forum thread
        $result = $dbEntityThread->deckThread($deckId);
        if ($result->isError()) {
            throw new Exception('Failed to find forum thread by deck id');
        }
        $threadId = ($result->isSuccess()) ? $result[0]['thread_id'] : 0;
        $data['discussion'] = ($threadId) ? $threadId : 0;
        $data['create_thread'] = ($this->checkAccess('create_thread')) ? 'yes' : 'no';

        return new Result(['decks_details' => $data], $deck->getDeckName());
    }
}
