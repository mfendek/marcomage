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
        $condition = (isset($input['players_sort'])) ? $input['players_sort'] : 'level';
        if (!in_array($condition, [
            'level', 'username', 'country', 'quarry', 'magic', 'dungeons', 'rares', 'ai_challenges', 'tower', 'wall',
            'tower_damage', 'wall_damage', 'assassin', 'builder', 'carpenter', 'collector', 'desolator', 'dragon',
            'gentle_touch', 'saboteur', 'snob', 'survivor', 'titan'
        ])) {
            throw new Exception('Invalid sorting condition', Exception::WARNING);
        }

        $data['players_sort'] = $condition;

        // choose correct sorting order
        $ascOrder = ['country', 'username'];
        $order = (in_array($condition, $ascOrder)) ? 'ASC' : 'DESC';

        $setting = $this->getCurrentSettings();

        // validate activity filter
        $activityFilter = (isset($input['activity_filter']))
            ? $input['activity_filter'] : $setting->getSetting('default_player_filter');
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

        $listParams = [
            'name' => $playerNameFilter,
            'status' => $statusFilter,
            'activity' => $activityFilter,
            'condition' => $condition,
            'order' => $order,
            'page' => $currentPage,
        ];

        $result = $dbEntityPlayer->countPages($listParams);
        if ($result->isErrorOrNoEffect()) {
            throw new Exception('Failed to count pages for players list');
        }

        $data['page_count'] = ceil($result[0]['count'] / PlayerModel::PLAYERS_PER_PAGE);

        // get the list of all existing players; (username, wins, losses, draws, last activity, free slots, avatar, country)
        $result = $dbEntityPlayer->listPlayers($listParams);
        if ($result->isError()) {
            throw new Exception('Failed to list players');
        }
        $list = $result->data();

        // for each player, display their name, score, and if conditions are met, also display the challenge button
        foreach ($list as $i => $playerData) {
            $data['list'][] = [
                'name' => $playerData['username'],
                'rank' => $playerData['user_type'],
                'level' => $playerData['level'],
                'wins' => $playerData['wins'],
                'losses' => $playerData['losses'],
                'draws' => $playerData['draws'],
                'avatar' => $playerData['avatar'],
                'status' => $playerData['status'],
                'friendly_flag' => ($playerData['friendly_flag'] == 1) ? 'yes' : 'no',
                'blind_flag' => ($playerData['blind_flag'] == 1) ? 'yes' : 'no',
                'long_flag' => ($playerData['long_flag'] == 1) ? 'yes' : 'no',
                'country' => $playerData['country'],
                'last_query' => $playerData['last_activity_at'],
                'inactivity' => time() - Date::strToTime($playerData['last_activity_at']),
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
        $data['last_query'] = $opponent->getLastActivity();
        $data['registered'] = $opponent->getRegistered();
        $data['first_name'] = $opponentSetting->getSetting('first_name');
        $data['surname'] = $opponentSetting->getSetting('surname');
        $data['gender'] = $opponentSetting->getSetting('gender');
        $data['country'] = $opponentSetting->getSetting('country');
        $data['status'] = $opponentSetting->getSetting('status');
        $data['friendly_flag'] = $opponentSetting->getSetting('friendly_flag');
        $data['blind_flag'] = $opponentSetting->getSetting('blind_flag');
        $data['long_flag'] = $opponentSetting->getSetting('long_flag');
        $data['avatar'] = $opponentSetting->getSetting('avatar');
        $data['email'] = $opponentSetting->getSetting('email');
        $data['im_number'] = $opponentSetting->getSetting('im_number');
        $data['hobby'] = $opponentSetting->getSetting('hobby');
        $data['level'] = $opponentScore->getLevel();
        $data['free_slots'] = $opponentFreeSlots;
        $data['exp'] = $opponentScore->getExp();
        $data['next_level'] = $opponentScore->nextLevel();
        $data['exp_bar'] = $opponentScore->expBar();
        $data['wins'] = $opponentScore->getData('wins');
        $data['losses'] = $opponentScore->getData('losses');
        $data['draws'] = $opponentScore->getData('draws');
        $data['gold'] = $opponentScore->getGold();
        $data['game_slots'] = $opponentScore->getGameSlots();
        $data['deck_slots'] = max(0, count($opponentDecks) - DeckModel::DECK_SLOTS);
        $data['avatar_path'] = $config['upload_dir']['avatar'];

        // count all forum posts created by opponent
        $result = $dbEntityForumPost->countPosts($opponentName);
        if ($result->isError()) {
            throw new Exception('Failed to count forum posts');
        }
        $posts = $result[0]['count'];
        $data['post_count'] = $posts;

        // case 1: birthday is set
        if ($opponentSetting->getSetting('birth_date') != Date::DATE_ZERO) {
            $data['age'] = $opponentSetting->age();
            $data['sign'] = $opponentSetting->sign();
            $data['birth_date'] = date('d-m-Y', Date::strToTime($opponentSetting->getSetting('birth_date')));
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
        $data['hidden_cards'] = $setting->getSetting('blind_flag');
        $data['friendly_play'] = $setting->getSetting('friendly_flag');
        $data['long_mode'] = $setting->getSetting('long_flag');
        $data['random_deck_option'] = $setting->getSetting('use_random_deck');
        $data['timezone'] = $setting->getSetting('timezone');
        $data['timeout'] = $setting->getSetting('game_turn_timeout');
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
        $data['random_deck'] = (count($decks) > 0) ? $decks[Random::arrayMtRand($decks)]['deck_id'] : '';

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
