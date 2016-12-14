<?php
/**
 * Misc - misc related view module
 */

namespace View;

use ArcomageException as Exception;
use Db\Model\Deck as DeckModel;
use Db\Model\Game as GameModel;
use Db\Model\Player as PlayerModel;
use Util\Date;
use Util\Input;

class Misc extends TemplateDataAbstract
{
    /**
     * @return Result
     */
    protected function error()
    {
        // this error page is only used when controller routing fails to determine current section
        return new Result(['error' => ['message' => 'routing error: ' . $this->getDic()->error()]]);
    }

    /**
     * @throws Exception
     * @return Result
     */
    protected function novels()
    {
        $data = array();
        $input = $this->input();

        // novels navigation data
        $data['novel'] = $novel = Input::defaultValue($input, 'novel');
        $data['chapter'] = $chapter = Input::defaultValue($input, 'chapter');
        $data['part'] = $part = Input::defaultValue($input, 'part');
        $data['page'] = $page = Input::defaultValue($input, 'page');
        $subsectionName = $part . (($part != '') ? ' - ' : '') . $chapter . (($chapter != '') ? ' - ' : '') . $novel;

        return new Result(['novels' => $data], $subsectionName);
    }

    /**
     * @throws Exception
     * @return Result
     */
    protected function settings()
    {
        $data = array();

        $config = $this->getDic()->config();
        $player = $this->getCurrentPlayer();
        $dbEntityDeck = $this->dbEntity()->deck();

        $setting = $this->getCurrentSettings();

        $data['current_settings'] = $setting->getData();
        $data['player_type'] = $player->getUserType();
        $data['change_own_avatar'] = ($this->checkAccess('change_own_avatar')) ? 'yes' : 'no';
        $data['avatar_path'] = $config['upload_dir']['avatar'];

        $score = $this->dbEntity()->score()->getScoreAsserted($player->getUsername());

        // list player's decks
        $result = $dbEntityDeck->listDecks($player->getUsername());
        if ($result->isError()) {
            throw new Exception('Failed to list decks');
        }
        $decks = $result->data();

        $data['gold'] = $score->getData('Gold');
        $data['game_slots'] = $score->getData('GameSlots');
        $data['deck_slots'] = max(0, count($decks) - DeckModel::DECK_SLOTS);
        $data['game_slot_cost'] = GameModel::GAME_SLOT_COST;
        $data['deck_slot_cost'] = DeckModel::DECK_SLOT_COST;
        $data['player_level'] = $score->getLevel();
        $data['tutorial_end'] = PlayerModel::TUTORIAL_END;

        // date is handled separately
        $birthDate = $setting->getSetting('Birthdate');

        // case 1: birthday is provided
        if ($birthDate && $birthDate != Date::DATE_ZERO) {
            $data['current_settings']['age'] = $setting->age();
            $data['current_settings']['sign'] = $setting->sign();
            $data['current_settings']['birth_date'] = $birthDate;
        }
        // case 2: birthday is unknown
        else {
            $data['current_settings']['age'] = 'Unknown';
            $data['current_settings']['sign'] = 'Unknown';
            $data['current_settings']['birth_date'] = '';
        }

        return new Result(['settings' => $data]);
    }

    /**
     * @throws Exception
     * @return Result
     */
    protected function statistics()
    {
        $data = array();
        $input = $this->input();

        $defEntityCard = $this->defEntity()->card();
        $dbEntityStatistic = $this->dbEntity()->statistic();

        // default subsection
        $subsection = 'card_statistics';

        // case 1: card statistics subsection
        if (isset($input['card_statistics'])) {
            $subsection = 'card_statistics';
        }
        // case 2: other statistics subsection
        elseif (isset($input['other_statistics'])) {
            $subsection = 'other_statistics';
        }

        // validate selected statistic
        $currentStatistic = Input::defaultValue($input, 'selected_statistic', 'Played');
        if (!in_array($currentStatistic, ['Played', 'PlayedTotal', 'Discarded', 'DiscardedTotal', 'Drawn', 'DrawnTotal'])) {
            throw new Exception('Invalid selected statistic', Exception::WARNING);
        }

        // validate list size
        $currentSize = Input::defaultValue($input, 'selected_size', 10);
        if (!in_array($currentSize, [10, 15, 20, 30, 50, 'full'])) {
            throw new Exception('Invalid list size', Exception::WARNING);
        }

        $data['current_subsection'] = $subsection;
        $data['current_statistic'] = $currentStatistic;
        $data['current_size'] = $currentSize;
        $data['change_rights'] = ($this->checkAccess('change_rights')) ? 'yes' : 'no';

        // case 1: card statistics subsection
        if ($subsection == 'card_statistics') {
            // load statistic data for cards
            $result = $dbEntityStatistic->listCardStats($currentStatistic);
            if ($result->isError()) {
                throw new Exception('Failed to list card statistics');
            }
            $cardStats = $result->data();

            $cards = $values = array();
            foreach ($cardStats as $statData) {
                $cards[] = $statData['CardID'];

                // assign a statistic value to each card id
                $values[$statData['CardID']] = $statData['value'];
            }

            // case 1: card statistics are available
            if (count($cards) > 0) {
                // load card data
                $cardsData = $defEntityCard->getData($cards);
            }
            // case 2: there are no card statistics available
            else {
                $cardsData = array();
            }

            // separate statistics based on card rarity, create top and bottom lists
            $separated = ['Common' => [], 'Uncommon' => [], 'Rare' => []];
            $statistics = [
                'Common' => ['top' => [], 'bottom' => []],
                'Uncommon' => ['top' => [], 'bottom' => []],
                'Rare' => ['top' => [], 'bottom' => []]
            ];
            $total = ['Common' => 0, 'Uncommon' => 0, 'Rare' => 0];

            // separate card list by card rarity, calculate total sum for each rarity type
            foreach ($cardsData as $cardData) {
                $separated[$cardData['rarity']][] = $cardData;

                // add current's card statistics to current card rarity total
                $total[$cardData['rarity']]+= $values[$cardData['id']];
            }

            // make top and bottom lists for each rarity type
            foreach ($separated as $rarity => $list) {
                $statistics[$rarity]['top'] = ($currentSize == 'full') ? $list : array_slice($list, 0, $currentSize);
                $statistics[$rarity]['bottom'] = ($currentSize == 'full')
                    ? array() : array_slice(array_reverse($list), 0, $currentSize);
            }

            // calculate usage factor for each card (relative to card's rarity)
            foreach ($statistics as $rarity => $types) {
                foreach ($types as $type => $list) {
                    foreach ($list as $i => $currentCard) {
                        $statistics[$rarity][$type][$i]['factor'] = ($total[$rarity] > 0)
                            ? round($values[$currentCard['id']] / $total[$rarity], 5) * 1000 : 0;
                    }
                }
            }

            $data['card_statistics'] = $statistics;
        }
        // case 2: other statistics subsection
        elseif ($subsection == 'other_statistics') {
            $serviceStatistic = $this->service()->statistic();
            $dbEntityConcept = $this->dbEntity()->concept();

            // game victory types
            $victoryTypes = $serviceStatistic->victoryTypes();

            // game modes
            $gameModes = $serviceStatistic->gameModes();

            // suggested concepts
            $result = $dbEntityConcept->suggestedConcepts();
            if ($result->isError()) {
                throw new Exception('Failed to compute suggested concepts statistic');
            }
            $suggested = $result->data();

            // implemented concepts
            $result = $dbEntityConcept->implementedConcepts();
            if ($result->isError()) {
                throw new Exception('Failed to compute implemented concepts statistic');
            }
            $implemented = $result->data();

            $data['victory_types'] = $victoryTypes;
            $data['game_modes'] = $gameModes;
            $data['suggested'] = $suggested;
            $data['implemented'] = $implemented;
        }

        return new Result(['statistics' => $data]);
    }
}
