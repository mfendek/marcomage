<?php
/**
 * Player - players related view module
 */

namespace View;

use ArcomageException as Exception;
use Db\Model\Deck as DeckModel;
use Db\Model\Game as GameModel;
use Db\Model\Player as PlayerModel;
use Def\Entity\XmlAward;
use Util\Date;
use Util\Random;

class Player extends TemplateDataAbstract
{
    /**
     * Check if player has final achievement with specified tier
     * @param string $playerName player name
     * @param int $tier
     * @throws Exception
     * @return bool
     */
    private function checkFinalAchievement($playerName, $tier)
    {
        $defEntityAward = $this->defEntity()->award();

        $player = $this->dbEntity()->player()->getPlayerAsserted($playerName);
        $score = $this->dbEntity()->score()->getScoreAsserted($player->getUsername());

        // list all supported awards
        $result = $defEntityAward->awardsNames();
        if ($result->isError()) {
            throw new Exception('Failed to list award names');
        }
        $awardsList = $result->data();

        // check every supported achievement
        foreach ($awardsList as $award) {
            $result = $defEntityAward->getAchievement($award, $tier);
            if ($result->isErrorOrNoEffect()) {
                throw new Exception('Failed to load achievement');
            }
            $achievement = $result->data();

            // check achievement completion
            if ($score->getData($award) < $achievement['condition']) {
                return false;
            }
        }

        return true;
    }

    /**
     * @throws Exception
     * @return Result
     */
    protected function players()
    {
        $data = array();
        $input = $this->input();

        $config = $this->getDic()->config();
        $player = $this->getCurrentPlayer();
        $dbEntityDeck = $this->dbEntity()->deck();
        $dbEntityPlayer = $this->dbEntity()->player();

        $data['is_logged_in'] = ($this->isSession()) ? 'yes' : 'no';

        // validate sorting order
        $condition = (isset($input['players_sort'])) ? $input['players_sort'] : 'Level';
        if (!in_array($condition, [
            'Level', 'Username', 'Country', 'Quarry', 'Magic', 'Dungeons', 'Rares', 'Challenges', 'Tower', 'Wall',
            'TowerDamage', 'WallDamage', 'Assassin', 'Builder', 'Carpenter', 'Collector', 'Desolator', 'Dragon',
            'Gentle_touch', 'Saboteur', 'Snob', 'Survivor', 'Titan'
        ])) {
            throw new Exception('Invalid sorting condition', Exception::WARNING);
        }

        $data['players_sort'] = $condition;

        // choose correct sorting order
        $ascOrder = ['Country', 'Username'];
        $order = (in_array($condition, $ascOrder)) ? 'ASC' : 'DESC';

        $setting = $this->getCurrentSettings();

        // validate activity filter
        $activityFilter = (isset($input['activity_filter']))
            ? $input['activity_filter'] : $setting->getSetting('DefaultFilter');
        if (!in_array($activityFilter, ['none', 'active', 'offline', 'all'])) {
            throw new Exception('Invalid activity filter', Exception::WARNING);
        }

        // validate status filter
        $statusFilter = (isset($input['status_filter'])) ? $input['status_filter'] : 'none';
        if (!in_array($statusFilter, ['none', 'ready', 'quick', 'dnd', 'newbie'])) {
            throw new Exception('Invalid status filter', Exception::WARNING);
        }

        // filter initialization
        $data['activity_filter'] = $activityFilter;
        $data['status_filter'] = $statusFilter;
        $data['pname_filter'] = $playerNameFilter = (isset($input['pname_filter'])) ? trim($input['pname_filter']) : '';

        $data['player_name'] = $player->getUsername();

        // check for active decks
        $decks = array();
        if ($this->isSession()) {
            $result = $dbEntityDeck->listReadyDecks($player->getUsername());
            if ($result->isError()) {
                throw new Exception('Failed to list ready decks');
            }
            $decks = $result->data();
        }

        $data['active_decks'] = count($decks);

        // retrieve layout setting
        $data['avatar_path'] = $config['upload_dir']['avatar'];

        $freeSlots = GameModel::MAX_GAMES;
        if ($this->isSession()) {
            $freeSlots = $this->service()->gameUtil()->countFreeSlots($player->getUsername());
        }

        $data['free_slots'] = $freeSlots;

        // messages access rights
        $data['messages'] = ($this->checkAccess('messages')) ? 'yes' : 'no';
        $data['send_challenges'] = ($this->checkAccess('send_challenges')) ? 'yes' : 'no';

        // validate current page
        $currentPage = (isset($input['players_current_page'])) ? $input['players_current_page'] : 0;
        if (!is_numeric($currentPage) || $currentPage < 0) {
            throw new Exception('Invalid players page', Exception::WARNING);
        }

        $data['current_page'] = $currentPage;

        $result = $dbEntityPlayer->countPages($activityFilter, $statusFilter, $playerNameFilter);
        if ($result->isErrorOrNoEffect()) {
            throw new Exception('Failed to count pages for players list');
        }

        $data['page_count'] = ceil($result[0]['Count'] / PlayerModel::PLAYERS_PER_PAGE);

        // get the list of all existing players; (Username, Wins, Losses, Draws, Last Query, Free slots, Avatar, Country)
        $result = $dbEntityPlayer->listPlayers(
            $activityFilter, $statusFilter, $playerNameFilter, $condition, $order, $currentPage
        );
        if ($result->isError()) {
            throw new Exception('Failed to list players');
        }
        $list = $result->data();

        // for each player, display their name, score, and if conditions are met, also display the challenge button
        foreach ($list as $i => $playerData) {
            $data['list'][] = [
                'name' => $playerData['Username'],
                'rank' => $playerData['UserType'],
                'level' => $playerData['Level'],
                'wins' => $playerData['Wins'],
                'losses' => $playerData['Losses'],
                'draws' => $playerData['Draws'],
                'avatar' => $playerData['Avatar'],
                'status' => $playerData['Status'],
                'friendly_flag' => ($playerData['FriendlyFlag'] == 1) ? 'yes' : 'no',
                'blind_flag' => ($playerData['BlindFlag'] == 1) ? 'yes' : 'no',
                'long_flag' => ($playerData['LongFlag'] == 1) ? 'yes' : 'no',
                'country' => $playerData['Country'],
                'last_query' => $playerData['Last Query'],
                'inactivity' => time() - Date::strToTime($playerData['Last Query']),
            ];
        }

        return new Result(['players' => $data]);
    }

    /**
     * @throws Exception
     * @return Result
     */
    protected function playersDetails()
    {
        $data = array();
        $input = $this->input();

        $config = $this->getDic()->config();
        $player = $this->getCurrentPlayer();
        $dbEntityDeck = $this->dbEntity()->deck();
        $dbEntityForumPost = $this->dbEntity()->forumPost();
        $defEntityChallenge = $this->defEntity()->challenge();

        // validate player profile
        $this->assertInputNonEmpty(['Profile']);
        if (trim($input['Profile']) == '') {
            throw new Exception('Invalid player profile', Exception::WARNING);
        }
        $opponentName = $input['Profile'];

        $setting = $this->getCurrentSettings();

        $opponent = $this->dbEntity()->player()->getPlayerAsserted($opponentName);

        // prevent access to guest profile
        if ($opponent->isGuest()) {
            throw new Exception('Unable to access guest profile', Exception::WARNING);
        }

        $opponentSetting = $this->dbEntity()->setting()->getSettingAsserted($opponentName);

        $opponentScore = $this->dbEntity()->score()->getScoreAsserted($opponentName);

        $result = $dbEntityDeck->listDecks($opponentName);
        if ($result->isError()) {
            throw new Exception('Failed to list decks');
        }
        $opponentDecks = $result->data();

        $freeSlots = $opponentFreeSlots = GameModel::MAX_GAMES;
        if ($this->isSession()) {
            $freeSlots = $this->service()->gameUtil()->countFreeSlots($player->getUsername());
            $opponentFreeSlots = $this->service()->gameUtil()->countFreeSlots($opponentName);
        }

        $data['is_logged_in'] = ($this->isSession()) ? 'yes' : 'no';
        $data['player_name'] = $subsectionName = $opponent->getUsername();
        $data['player_type'] = $opponent->getUserType();
        $data['last_query'] = $opponent->getLastQuery();
        $data['registered'] = $opponent->getRegistered();
        $data['first_name'] = $opponentSetting->getSetting('Firstname');
        $data['surname'] = $opponentSetting->getSetting('Surname');
        $data['gender'] = $opponentSetting->getSetting('Gender');
        $data['country'] = $opponentSetting->getSetting('Country');
        $data['status'] = $opponentSetting->getSetting('Status');
        $data['friendly_flag'] = $opponentSetting->getSetting('FriendlyFlag');
        $data['blind_flag'] = $opponentSetting->getSetting('BlindFlag');
        $data['long_flag'] = $opponentSetting->getSetting('LongFlag');
        $data['avatar'] = $opponentSetting->getSetting('Avatar');
        $data['email'] = $opponentSetting->getSetting('Email');
        $data['im_number'] = $opponentSetting->getSetting('Imnumber');
        $data['hobby'] = $opponentSetting->getSetting('Hobby');
        $data['level'] = $opponentScore->getData('Level');
        $data['free_slots'] = $opponentFreeSlots;
        $data['exp'] = $opponentScore->getData('Exp');
        $data['next_level'] = $opponentScore->nextLevel();
        $data['exp_bar'] = $opponentScore->expBar();
        $data['wins'] = $opponentScore->getData('Wins');
        $data['losses'] = $opponentScore->getData('Losses');
        $data['draws'] = $opponentScore->getData('Draws');
        $data['gold'] = $opponentScore->getData('Gold');
        $data['game_slots'] = $opponentScore->getData('GameSlots');
        $data['deck_slots'] = max(0, count($opponentDecks) - DeckModel::DECK_SLOTS);
        $data['avatar_path'] = $config['upload_dir']['avatar'];

        // count all forum posts created by opponent
        $result = $dbEntityForumPost->countPosts($opponentName);
        if ($result->isError()) {
            throw new Exception('Failed to count forum posts');
        }
        $posts = $result[0]['Count'];
        $data['post_count'] = $posts;

        // case 1: birthday is set
        if ($opponentSetting->getSetting('Birthdate') != Date::DATE_ZERO) {
            $data['age'] = $opponentSetting->age();
            $data['sign'] = $opponentSetting->sign();
            $data['birth_date'] = date('d-m-Y', Date::strToTime($opponentSetting->getSetting('Birthdate')));
        }
        // case 2: birthday is not set
        else {
            $data['age'] = 'Unknown';
            $data['sign'] = 'Unknown';
            $data['birth_date'] = 'Unknown';
        }

        $result = $dbEntityDeck->listReadyDecks($player->getUsername());
        if ($result->isError()) {
            throw new Exception('Failed to list ready decks');
        }
        $decks = $result->data();

        $data['current_player_name'] = $player->getUsername();
        $data['hidden_cards'] = $setting->getSetting('BlindFlag');
        $data['friendly_play'] = $setting->getSetting('FriendlyFlag');
        $data['long_mode'] = $setting->getSetting('LongFlag');
        $data['random_deck_option'] = $setting->getSetting('RandomDeck');
        $data['timezone'] = $setting->getSetting('Timezone');
        $data['timeout'] = $setting->getSetting('Timeout');
        $data['send_challenges'] = ($this->checkAccess('send_challenges')) ? 'yes' : 'no';
        $data['messages'] = ($this->checkAccess('messages')) ? 'yes' : 'no';
        $data['change_rights'] = ($this->checkAccess('change_rights')
            && $opponent->getUserType() != 'admin') ? 'yes' : 'no';
        $data['system_notification'] = ($this->checkAccess('system_notification')) ? 'yes' : 'no';
        $data['change_all_avatar'] = ($this->checkAccess('change_all_avatar')) ? 'yes' : 'no';
        $data['reset_exp'] = ($this->checkAccess('reset_exp')) ? 'yes' : 'no';
        $data['export_deck'] = ($this->checkAccess('export_deck')) ? 'yes' : 'no';
        $data['free_slots'] = $freeSlots;
        $data['decks'] = $decks;
        $data['random_deck'] = (count($decks) > 0) ? $decks[Random::arrayMtRand($decks)]['DeckID'] : '';

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

        $data['challenging'] = (isset($input['prepare_challenge'])) ? 'yes' : 'no';

        // versus statistics
        $statistics = array();
        if ($this->isSession()) {
            // case 1: player is viewing his own profile - display game statistics
            if ($player->getUsername() == $opponent->getUsername()) {
                $statistics = $this->service()->statistic()->gameStats($player->getUsername());
            }
            // case 2: player is viewing some other player's profile - display versus statistics
            else {
                $statistics = $this->service()->statistic()->versusStats($player->getUsername(), $opponentName);
            }
        }

        $data['statistics'] = $statistics;
        $data['export_decks'] = ($this->checkAccess('export_deck')) ? $opponentDecks : [];

        return new Result(['profile' => $data], $subsectionName);
    }

    /**
     * @throws Exception
     * @return Result
     */
    protected function playersAchievements()
    {
        $data = array();
        $input = $this->input();

        $defEntityAward = $this->defEntity()->award();

        // validate player profile
        $this->assertInputNonEmpty(['Profile']);
        if (trim($input['Profile']) == '') {
            throw new Exception('Invalid player profile', Exception::WARNING);
        }
        $playerName = $input['Profile'];

        $player = $this->dbEntity()->player()->getPlayerAsserted($playerName);
        $score = $this->dbEntity()->score()->getScoreAsserted($playerName);

        // get all achievements data (group by tier)
        $result = $defEntityAward->awardsNames();
        if ($result->isError()) {
            throw new Exception('Failed to list award names');
        }
        $awardNames = $result->data();

        // prepare achievements data
        $achievementsData = array();
        foreach ($awardNames as $award) {
            $result = $defEntityAward->getAchievements($award);
            if ($result->isError()) {
                throw new Exception('Failed to list achievements for award '.$award);
            }
            $achievements = $result->data();

            foreach ($achievements as $achievement) {
                $achievement['count'] = $score->getData($award);
                $achievementsData[$achievement['tier']][] = $achievement;
            }
        }

        // add final achievement data
        $final = XmlAward::finalAchievements();
        foreach ($final as $tier => $achievement) {
            // in this case condition holds the information if player has this achievement (yes/no)
            $achievement['condition'] = ($this->checkFinalAchievement($player->getUsername(), $tier)) ? 'yes' : 'no';
            $achievement['count'] = '';
            $achievement['tier'] = $tier;
            $achievementsData[$tier][] = $achievement;
        }

        $data['player_name'] = $player->getUsername();
        $data['data'] = $achievementsData;

        return new Result(['achievements' => $data], $player->getUsername());
    }
}
