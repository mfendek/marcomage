<?php
/**
 * Replay - replays related view module
 */

namespace View;

use ArcomageException as Exception;
use Db\Model\Game as GameModel;
use Db\Model\Player as PlayerModel;
use Service\GameUtil;
use Util\Input;

class Replay extends TemplateDataAbstract
{
    /**
     * Returns replay filter values
     * @return array
     */
    private static function replayFilters()
    {
        return ['none', 'include', 'exclude'];
    }

    /**
     * @param \Db\Model\Replay $replay
     * @param int $playerView player view option (1 or 2)
     * @param int $turn turn number
     * @throws Exception
     * @return array
     */
    private function formatReplayData(\Db\Model\Replay $replay, $playerView, $turn)
    {
        $defEntityCard = $this->defEntity()->card();
        $serviceGameUtil = $this->service()->gameUtil();

        $data = array();

        // determine player view
        $player1 = ($playerView == 1) ? $replay->getPlayer1() : $replay->getPlayer2();
        $player2 = ($playerView == 1) ? $replay->getPlayer2() : $replay->getPlayer1();

        // prepare the necessary data
        $turnData = $replay->getTurn($turn);
        if (empty($turnData)) {
            throw new Exception('Invalid replay turn', Exception::WARNING);
        }

        $gameData = $turnData->GameData;
        $player1Data = $gameData[(($playerView == 1) ? 1 : 2)];
        $player2Data = $gameData[(($playerView == 1) ? 2 : 1)];

        // load needed settings
        $setting = $this->getCurrentSettings();

        $data['card_old_look'] = $setting->getSetting('old_card_look');
        $data['card_insignias'] = $setting->getSetting('keyword_insignia');
        $data['card_mini_flag'] = $setting->getSetting('card_mini_flag');
        $data['background_img'] = $setting->getSetting('game_bg_image');

        // attempt to load setting of both players
        $player1Setting = $this->dbEntity()->setting()->getSetting($player1);
        $player2Setting = $this->dbEntity()->setting()->getSetting($player2);

        $data['p1_card_foils'] = $player1Setting->getSetting('foil_cards');
        $data['p2_card_foils'] = $player2Setting->getSetting('foil_cards');
        $data['p1_country'] = $player1Setting->getSetting('country');
        $data['p2_country'] = $player2Setting->getSetting('country');

        $gameConfig = GameModel::gameConfig();

        // game attributes
        $data['turns'] = $replay->getTurns();
        $data['round'] = $turnData->Round;
        $data['outcome'] = GameUtil::outcomeMessage($replay->getEndType());
        $data['outcome_type'] = $replay->getEndType();
        $data['winner'] = $replay->getWinner();
        $data['player1'] = $player1;
        $data['player2'] = $player2;
        $data['current_player'] = $turnData->Current;
        $data['ai_name'] = $replay->getAi();
        $data['system_name'] = PlayerModel::SYSTEM_NAME;
        $data['hidden_cards'] = ($replay->checkGameMode('HiddenCards')) ? 'yes' : 'no';
        $data['friendly_play'] = ($replay->checkGameMode('FriendlyPlay')) ? 'yes' : 'no';
        $gameMode = ($replay->checkGameMode('LongMode')) ? 'long' : 'normal';
        $data['ai_mode'] = ($replay->checkGameMode('AIMode')) ? 'yes' : 'no';
        $data['max_tower'] = $gameConfig[$gameMode]['max_tower'];
        $data['max_wall'] = $gameConfig[$gameMode]['max_wall'];
        $data['res_victory'] = $gameConfig[$gameMode]['res_victory'];

        $data['p1_hand'] = $serviceGameUtil->formatHandData($player1Data);
        $data['p1_tower'] = $player1Data->Tower;
        $data['p1_wall'] = $player1Data->Wall;
        $data['p1_stock'] = [
            'bricks' => $player1Data->Bricks,
            'gems' => $player1Data->Gems,
            'recruits' => $player1Data->Recruits,
            'quarry' => $player1Data->Quarry,
            'magic' => $player1Data->Magic,
            'dungeons' => $player1Data->Dungeons,
        ];

        // player1 discarded cards - cards discarded from player1 hand
        if (count($player1Data->DisCards[0]) > 0) {
            $data['p1_discarded_cards_0'] = $defEntityCard->getData($player1Data->DisCards[0]);
        }
        // player1 discarded cards - cards discarded from player2 hand
        if (count($player1Data->DisCards[1]) > 0) {
            $data['p1_discarded_cards_1'] = $defEntityCard->getData($player1Data->DisCards[1]);
        }

        $data['p1_last_card'] = $serviceGameUtil->formatLastCard($player1Data);
        $data['p1_tokens'] = $serviceGameUtil->formatTokens($player1Data);
        $data['p2_hand'] = $serviceGameUtil->formatHandData($player2Data);
        $data['p2_tower'] = $player2Data->Tower;
        $data['p2_wall'] = $player2Data->Wall;
        $data['p2_stock'] = [
            'bricks' => $player2Data->Bricks,
            'gems' => $player2Data->Gems,
            'recruits' => $player2Data->Recruits,
            'quarry' => $player2Data->Quarry,
            'magic' => $player2Data->Magic,
            'dungeons' => $player2Data->Dungeons,
        ];

        // player2 discarded cards - cards discarded from player1 hand
        if (count($player2Data->DisCards[0]) > 0) {
            $data['p2_discarded_cards_0'] = $defEntityCard->getData($player2Data->DisCards[0]);
        }
        // player2 discarded cards - cards discarded from player2 hand
        if (count($player2Data->DisCards[1]) > 0) {
            $data['p2_discarded_cards_1'] = $defEntityCard->getData($player2Data->DisCards[1]);
        }

        $data['p2_last_card'] = $serviceGameUtil->formatLastCard($player2Data);
        $data['p2_tokens'] = array_reverse($serviceGameUtil->formatTokens($player2Data));
        $data['p1_changes'] = $serviceGameUtil->formatChanges($player1Data);
        $data['p2_changes'] = $serviceGameUtil->formatChanges($player2Data);

        return $data;
    }

    /**
     * @throws Exception
     * @return Result
     */
    protected function replays()
    {
        $data = array();
        $input = $this->input();

        $defEntityChallenge = $this->defEntity()->challenge();
        $dbEntityReplay = $this->dbEntity()->replay();

        // validate current page
        $currentPage = Input::defaultValue($input, 'replays_current_page', 0);
        if (!is_numeric($currentPage) || $currentPage < 0) {
            throw new Exception('Invalid replays page', Exception::WARNING);
        }
        $data['current_page'] = $currentPage;

        // validate hidden cards filter
        $hiddenFilter = Input::defaultValue($input, 'hidden_cards', 'none');
        if (!in_array($hiddenFilter, self::replayFilters())) {
            throw new Exception('Invalid hidden cards filter value', Exception::WARNING);
        }

        // validate friendly play filter
        $friendlyFilter = Input::defaultValue($input, 'friendly_play', 'none');
        if (!in_array($friendlyFilter, self::replayFilters())) {
            throw new Exception('Invalid friendly play filter value', Exception::WARNING);
        }

        // validate long mode filter
        $longFilter = Input::defaultValue($input, 'long_mode', 'none');
        if (!in_array($longFilter, self::replayFilters())) {
            throw new Exception('Invalid long mode filter value', Exception::WARNING);
        }

        // validate AI mode filter
        $aiFilter = Input::defaultValue($input, 'ai_mode', 'none');
        if (!in_array($aiFilter, self::replayFilters())) {
            throw new Exception('Invalid AI mode filter value', Exception::WARNING);
        }

        $result = $defEntityChallenge->listChallengeNames();
        if ($result->isError()) {
            throw new Exception('Failed to list AI challenge names');
        }
        $challengeNames = $result->data();

        // validate AI challenge mode filter
        $challengeFilter = Input::defaultValue($input, 'challenge_filter', 'none');
        if (!in_array($challengeFilter, array_merge(self::replayFilters(), $challengeNames))) {
            throw new Exception('Invalid AI challenge mode filter value', Exception::WARNING);
        }

        // validate victory type filter
        $victoryFilter = Input::defaultValue($input, 'victory_filter', 'none');
        if (!in_array($victoryFilter, [
            'none', 'Construction', 'Destruction', 'Resource', 'Timeout', 'Draw', 'Surrender', 'Abort', 'Abandon'
        ])) {
            throw new Exception('Invalid victory type filter value', Exception::WARNING);
        }

        $data['player_filter'] = $playerFilter = Input::defaultValue($input, 'player_filter');
        $data['hidden_cards'] = $hiddenFilter;
        $data['friendly_play'] = $friendlyFilter;
        $data['long_mode'] = $longFilter;
        $data['ai_mode'] = $aiFilter;
        $data['challenge_filter'] = $challengeFilter;
        $data['victory_filter'] = $victoryFilter;

        // default ordering and condition
        $order = Input::defaultValue($input, 'replays_current_order', 'DESC');
        $cond = Input::defaultValue($input, 'replays_current_condition', 'finished_at');

        $data['order'] = $order;
        $data['cond'] = $cond;

        $result = $dbEntityReplay->listReplays(
            $playerFilter, $hiddenFilter, $friendlyFilter, $longFilter, $aiFilter,
            $challengeFilter, $victoryFilter, $cond, $order, $currentPage
        );
        if ($result->isError()) {
            throw new Exception('Failed to list replays');
        }
        $replays = $result->data();

        $result = $dbEntityReplay->countPages(
            $playerFilter, $hiddenFilter, $friendlyFilter, $longFilter, $aiFilter, $challengeFilter, $victoryFilter
        );
        if ($result->isErrorOrNoEffect()) {
            throw new Exception('Failed to count paged for replays list');
        }
        $pages = ceil($result[0]['count'] / \Db\Model\Replay::REPLAYS_PER_PAGE);

        $setting = $this->getCurrentSettings();

        $data['list'] = $replays;
        $data['page_count'] = $pages;
        $data['timezone'] = $setting->getSetting('timezone');
        $data['ai_challenges'] = $challengeNames;

        return new Result(['replays' => $data]);
    }

    /**
     * @throws Exception
     * @return Result
     */
    protected function replaysDetails()
    {
        $data = array();
        $input = $this->input();

        $dbEntityThread = $this->dbEntity()->forumThread();

        // validate game id
        $this->assertInputNonEmpty(['CurrentReplay']);
        if (!is_numeric($input['CurrentReplay']) || $input['CurrentReplay'] < 0) {
            throw new Exception('Invalid game id', Exception::WARNING);
        }
        $data['current_replay'] = $gameId = $input['CurrentReplay'];

        // validate player view
        if (!isset($input['PlayerView']) || !in_array($input['PlayerView'], [1, 2])) {
            throw new Exception('Invalid player view selection', Exception::WARNING);
        }
        $data['player_view'] = $playerView = $input['PlayerView'];

        // validate game turn
        if (!isset($input['Turn']) || !is_numeric($input['Turn']) || $input['Turn'] <= 0) {
            throw new Exception('Invalid game turn', Exception::WARNING);
        }
        $data['current_turn'] = $turn = $input['Turn'];
        $data['create_thread'] = ($this->checkAccess('create_thread')) ? 'yes' : 'no';

        // load replay data
        $replay = $this->dbEntity()->replay()->getReplayAsserted($gameId);

        // validate replay state
        if ($replay->getEndType() == 'Pending') {
            throw new Exception('Replay is not yet available', Exception::WARNING);
        }

        // increment number of views each time player enters a replay
        if ($turn == 1 && $playerView == 1) {
            $replay->setViews($replay->getViews() + 1);
            if (!$replay->save()) {
                throw new Exception('Failed to save replay data');
            }
        }

        // find related forum thread
        $result = $dbEntityThread->replayThread($gameId);
        if ($result->isError()) {
            throw new Exception('Failed to find forum thread by replay id');
        }
        $threadId = ($result->isSuccess()) ? $result[0]['thread_id'] : 0;
        $data['discussion'] = ($threadId) ? $threadId : 0;

        // add replay view data
        $replayView = $this->formatReplayData($replay, $playerView, $turn);
        $data = array_merge($data, $replayView);

        return new Result(['replay' => $data]);
    }

    /**
     * @throws Exception
     * @return Result
     */
    protected function replaysHistory()
    {
        $data = array();
        $input = $this->input();

        $player = $this->getCurrentPlayer();

        // validate game id
        $this->assertInputNonEmpty(['CurrentReplay']);
        if (!is_numeric($input['CurrentReplay']) || $input['CurrentReplay'] < 0) {
            throw new Exception('Invalid game id', Exception::WARNING);
        }
        $data['current_replay'] = $gameId = $input['CurrentReplay'];;

        // load replay data
        $replay = $this->dbEntity()->replay()->getReplayAsserted($gameId);

        // validate replay state
        if ($replay->getEndType() != 'Pending') {
            throw new Exception('Game history is no longer available', Exception::WARNING);
        }

        $turns = $replay->getTurns();

        // validate game turn
        $turn = Input::defaultValue($input, 'Turn', $turns);
        if (!is_numeric($turn) || $turn <= 0) {
            throw new Exception('Invalid game turn', Exception::WARNING);
        }

        $data['current_turn'] = $turn;

        // check if this user is allowed to view this replay
        if ($player->getUsername() != $replay->getPlayer1() && $player->getUsername() != $replay->getPlayer2()) {
            throw new Exception('You are not allowed to access this replay', Exception::WARNING);
        }

        // determine player view
        $playerView = ($player->getUsername() == $replay->getPlayer1()) ? 1 : 2;

        // add replay view data
        $replayView = $this->formatReplayData($replay, $playerView, $turn);
        $data = array_merge($data, $replayView);

        return new Result(['replays_history' => $data]);
    }
}
