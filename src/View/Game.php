<?php
/**
 * Game - games related view module
 */

namespace View;

use ArcomageException as Exception;
use Db\Model\Player as PlayerModel;
use Db\Model\Game as GameModel;
use Service\GameUtil;
use Util\Date;
use Util\Input;
use Util\Random;

class Game extends TemplateDataAbstract
{
    /**
     * @param int $gameId
     * @param string $playerName
     * @throws Exception
     * @return array
     */
    private function formatGameData($gameId, $playerName)
    {
        $data = array();

        $config = $this->getDic()->config();
        $defEntityCard = $this->defEntity()->card();
        $defEntityKeyword = $this->defEntity()->keyword();
        $dbEntityPlayer = $this->dbEntity()->player();
        $dbEntityGame = $this->dbEntity()->game();
        $serviceGameAi = $this->service()->gameAi();
        $serviceGameUtil = $this->service()->gameUtil();

        $player = $dbEntityPlayer->getPlayerAsserted($playerName);
        $game = $dbEntityGame->getGameAsserted($gameId);

        $player1 = $game->getPlayer1();
        $player2 = $game->getPlayer2();

        // check if this user is allowed to view this game
        if ($player->getUsername() != $player1 && $player->getUsername() != $player2) {
            throw new Exception('You are not allowed to access this game', Exception::WARNING);
        }

        // check if the game is a game in progress (and not a challenge)
        if ($game->getState() == 'waiting') {
            throw new Exception('Opponent did not accept the challenge yet!', Exception::WARNING);
        }

        // disable re-visiting
        if (($player->getUsername() == $player1 && $game->getState() == 'P1 over')
            || ($player->getUsername() == $player2 && $game->getState() == 'P2 over')) {
            throw new Exception('Game is already over', Exception::WARNING);
        }

        // prepare the necessary data
        $opponentName = ($player1 != $player->getUsername()) ? $player1 : $player2;

        $opponent = $dbEntityPlayer->getPlayerAsserted($opponentName);
        $opponentSetting = $this->dbEntity()->setting()->getSettingAsserted($opponentName);

        $score = $this->dbEntity()->score()->getScoreAsserted($player->getUsername());

        // prepare play suggestion for players in tutorial
        if ($score->getLevel() < PlayerModel::TUTORIAL_END && $player->getUsername() == $game->getCurrent() && $game->getState() == 'in progress') {
            $decision = $serviceGameAi->determineMove($player->getUsername(), $game);
        }

        $gameData = $game->getData();
        $player1Data = $gameData[(($game->getPlayer1() == $player->getUsername()) ? 1 : 2)];
        $player2Data = $gameData[(($game->getPlayer1() == $player->getUsername()) ? 2 : 1)];

        $data['current_game'] = $gameId;

        // load needed settings
        $setting = $this->dbEntity()->setting()->getSettingAsserted($playerName);

        $data['card_old_look'] = $setting->getSetting('old_card_look');
        $data['card_insignias'] = $setting->getSetting('keyword_insignia');
        $data['p1_card_foils'] = $setting->getSetting('foil_cards');
        $data['p2_card_foils'] = $opponentSetting->getSetting('foil_cards');
        $data['card_mini_flag'] = $setting->getSetting('card_mini_flag');

        $data['p1_country'] = $setting->getSetting('country');
        $data['p2_country'] = $opponentSetting->getSetting('country');
        $data['background_img'] = $setting->getSetting('game_bg_image');
        $data['avatar_path'] = $config['upload_dir']['avatar'];
        $data['p1_avatar'] = $setting->getSetting('avatar');
        $data['p2_avatar'] = $opponentSetting->getSetting('avatar');

        $gameConfig = GameModel::gameConfig();

        // game attributes
        $data['game_state'] = $game->getState();
        $data['surrender'] = $game->getSurrender();
        $data['player_name'] = $player->getUsername();
        $data['opponent_name'] = $opponentName;
        $data['ai_name'] = $game->getAiName();
        $data['system_name'] = PlayerModel::SYSTEM_NAME;
        $data['current'] = $game->getCurrent();
        $data['hidden_cards'] = ($game->checkGameMode('HiddenCards')) ? 'yes' : 'no';
        $data['friendly_play'] = ($game->checkGameMode('FriendlyPlay')) ? 'yes' : 'no';
        $data['long_mode'] = ($game->checkGameMode('LongMode')) ? 'yes' : 'no';
        $gameMode = ($game->checkGameMode('LongMode')) ? 'long' : 'normal';
        $data['ai_mode'] = ($game->checkGameMode('AIMode')) ? 'yes' : 'no';
        $data['max_tower'] = $gameConfig[$gameMode]['max_tower'];
        $data['max_wall'] = $gameConfig[$gameMode]['max_wall'];
        $data['res_victory'] = $gameConfig[$gameMode]['res_victory'];

        // my hand
        $p1Hand = $player1Data->Hand;
        $handData = $defEntityCard->getData($p1Hand);

        // process card data
        $playable = false;
        $keywordList = array();
        foreach ($handData as $i => $card) {
            $entry = array();
            $entry['card_data'] = $card;

            // suggested card
            $entry['suggested'] = (!empty($decision) && $decision['cardpos'] == $i) ? 'yes' : 'no';
            $entry['suggested_mode'] = (!empty($decision) && $decision['cardpos'] == $i && $decision['action'] == 'play'
                && $decision['mode'] > 0) ? $decision['mode'] : 0;

            // block playability of rare card is case of AI challenge
            $blocked = ($game->getAiName() != '' && $card['rarity'] == 'Rare');

            // playability
            $entry['playable'] = ($player1Data->Bricks >= $card['bricks'] && $player1Data->Gems >= $card['gems']
                && $player1Data->Recruits >= $card['recruits'] && !$blocked) ? 'yes' : 'no';

            // determine if at least one card is playable
            if ($entry['playable'] == 'yes') {
                $playable = true;
            }

            // modes
            $entry['modes'] = $card['modes'];

            // card flags
            $entry['new_card'] = (isset($player1Data->NewCards[$i])) ? 'yes' : 'no';
            $entry['revealed'] = (isset($player1Data->Revealed[$i])) ? 'yes' : 'no';

            $data['p1_hand'][$i] = $entry;

            // count number of different keywords in hand
            if ($card['keywords'] != '') {
                $cardKeywords = explode(",", $card['keywords']);
                foreach ($cardKeywords as $keywordName) {
                    // remove keyword value
                    $keywordName = preg_replace('/ \((\d+)\)/', '', $keywordName);
                    $keywordList[$keywordName] = (isset($keywordList[$keywordName])) ? $keywordList[$keywordName] + 1 : 1;
                }
            }
        }

        // determine of at least one card is playable
        $data['cards_playable'] = ($playable) ? 'yes' : 'no';

        // determine keyword counts
        $keywordsCount = array();
        foreach ($keywordList as $keywordName => $keywordCount) {
            $currentKeyword = $defEntityKeyword->getKeyword($keywordName);

            if ($currentKeyword->isTokenKeyword()) {
                $keywordsCount[] = [
                    'name' => $keywordName,
                    'count' => $keywordCount,
                ];
            }
        }
        $data['keywords_count'] = $keywordsCount;

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

        // my discarded cards - cards discarded from my hand
        if (count($player1Data->DisCards[0]) > 0) {
            $data['p1_discarded_cards_0'] = $defEntityCard->getData($player1Data->DisCards[0]);
        }
        // my discarded cards - cards discarded from his hand
        if (count($player1Data->DisCards[1]) > 0) {
            $data['p1_discarded_cards_1'] = $defEntityCard->getData($player1Data->DisCards[1]);
        }

        $data['p1_last_card'] = $serviceGameUtil->formatLastCard($player1Data);
        $data['p1_tokens'] = $serviceGameUtil->formatTokens($player1Data);

        // his hand
        $p2Hand = $player2Data->Hand;
        $handData = $defEntityCard->getData($p2Hand);

        // process card data
        foreach ($handData as $i => $card) {
            $data['p2_hand'][$i] = [
                'card_data' => $card,
                'playable' => ($player2Data->Bricks >= $card['bricks'] && $player2Data->Gems >= $card['gems']
                    && $player2Data->Recruits >= $card['recruits']) ? 'yes' : 'no',
                'new_card' => (isset($player2Data->NewCards[$i])) ? 'yes' : 'no',
                'revealed' => (isset($player2Data->Revealed[$i])) ? 'yes' : 'no',
            ];
        }

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

        // his discarded cards - cards discarded from my hand
        if (count($player2Data->DisCards[0]) > 0) {
            $data['p2_discarded_cards_0'] = $defEntityCard->getData($player2Data->DisCards[0]);
        }
        // his discarded cards - cards discarded from his hand
        if (count($player2Data->DisCards[1]) > 0) {
            $data['p2_discarded_cards_1'] = $defEntityCard->getData($player2Data->DisCards[1]);
        }

        $data['p2_last_card'] = $serviceGameUtil->formatLastCard($player2Data);
        $data['p2_tokens'] = $serviceGameUtil->formatTokens($player2Data);

        // - <game state indicator>
        $data['opponent_is_online'] = ($opponent->isOnline()) ? 'yes' : 'no';

        $data['p1_changes'] = $serviceGameUtil->formatChanges($player1Data);
        $data['p2_changes'] = $serviceGameUtil->formatChanges($player2Data);

        return $data;
    }

    /**
     * @throws Exception
     * @return Result
     */
    protected function games()
    {
        $data = array();
        $input = $this->input();

        $config = $this->getDic()->config();
        $newUser = $this->getDic()->newUserFlag();
        $player = $this->getCurrentPlayer();
        $defEntityChallenge = $this->defEntity()->challenge();
        $dbEntityChallenge = $this->dbEntity()->deck();
        $dbEntityGame = $this->dbEntity()->game();

        $setting = $this->getCurrentSettings();
        $default = ($newUser) ? 'game_creation' : 'started_games';
        $gamesSubsection = Input::defaultValue($input, 'games_subsection', $default);
        $gamesSubsection = (in_array($gamesSubsection, ['started_games', 'game_creation']))
            ? $gamesSubsection : 'started_games';
        $data['games_subsection'] = $gamesSubsection;

        // process player's settings
        $data['player_name'] = $player->getUsername();
        $data['timezone'] = $setting->getSetting('timezone');
        $data['blind_flag'] = $setting->getSetting('blind_flag');
        $data['friendly_flag'] = $setting->getSetting('friendly_flag');
        $data['long_flag'] = $setting->getSetting('long_flag');
        $data['random_deck_option'] = $setting->getSetting('use_random_deck');
        $data['auto_refresh'] = $setting->getSetting('auto_refresh_timer');
        $data['timeout'] = $setting->getSetting('game_turn_timeout');
        $data['system_name'] = PlayerModel::SYSTEM_NAME;
        $data['avatar_path'] = $config['upload_dir']['avatar'];

        // started games subsection specific data
        if ($gamesSubsection == 'started_games') {
            // list games
            $result = $dbEntityGame->listGamesData($player->getUsername());
            if ($result->isError()) {
                throw new Exception('Failed to list games data');
            }
            $list = $result->data();

            // process games
            if (count($list) > 0) {
                foreach ($list as $i => $gameData) {
                    // determine opponent name
                    $opponentName = ($gameData['player1'] != $player->getUsername()) ? $gameData['player1'] : $gameData['player2'];

                    // determine opponent's activity (only in case of human player)
                    $lastSeen = Date::timeToStr();
                    if (strpos($gameData['game_modes'], 'AIMode') === false) {
                        $opponent = $this->dbEntity()->player()->getPlayerAsserted($opponentName);

                        $lastSeen = $opponent->getLastActivity();
                    }
                    $inactivity = time() - Date::strToTime($lastSeen);

                    $timeout = '';
                    if ($gameData['turn_timeout'] > 0 && $gameData['current'] == $player->getUsername()
                        && $opponentName != PlayerModel::SYSTEM_NAME) {
                        // case 1: time is up
                        if (time() - Date::strToTime($gameData['last_action_at']) >= $gameData['turn_timeout']) {
                            $timeout = 'time is up';
                        }
                        // case 2: there is still some time left
                        else {
                            $timeout = Input::formatTimeDiff($gameData['turn_timeout'] - time() + Date::strToTime($gameData['last_action_at']));
                        }
                    }

                    $data['list'][$i] = [
                        'opponent' => $opponentName,
                        'ready' => ($gameData['current'] == $player->getUsername()) ? 'yes' : 'no',
                        'game_id' => $gameData['game_id'],
                        'game_state' => $gameData['state'],
                        'round' => $gameData['round'],
                        'active' => ($inactivity < 10 * Date::MINUTE) ? 'yes' : 'no',
                        'is_dead' => ($inactivity > 3 * Date::WEEK) ? 'yes' : 'no',
                        'game_action' => $gameData['last_action_at'],
                        'finish_allowed' => (time() - Date::strToTime($gameData['last_action_at']) >= 3 * Date::WEEK
                            && $gameData['current'] != $player->getUsername()
                            && $opponentName != PlayerModel::SYSTEM_NAME) ? 'yes' : 'no',
                        'finish_move' => ($gameData['turn_timeout'] > 0
                            && time() - Date::strToTime($gameData['last_action_at']) >= $gameData['turn_timeout']
                            && $gameData['current'] != $player->getUsername()
                            && $opponentName != PlayerModel::SYSTEM_NAME) ? 'yes' : 'no',
                        'game_modes' => $gameData['game_modes'],
                        'timeout' => $timeout,
                        'ai' => $gameData['ai_name'],
                    ];
                }
            }
        }

        // game creation subsection specific data
        if ($gamesSubsection == 'game_creation') {
            $score = $this->dbEntity()->score()->getScoreAsserted($player->getUsername());

            // determine if AI challenges should be shown
            $data['show_challenges'] = ($score->getLevel() >= PlayerModel::TUTORIAL_END) ? 'yes' : 'no';
            $data['current_subsection'] = Input::defaultValue($input, 'subsection', 'free_games');
            $data['hidden_cards'] = $hiddenFilter = Input::defaultValue($input, 'hidden_cards', 'none');
            $data['friendly_play'] = $friendlyFilter = Input::defaultValue($input, 'friendly_play', 'none');
            $data['long_mode'] = $longFilter = Input::defaultValue($input, 'long_mode', 'none');

            // list hosted game
            $result = $dbEntityGame->listHostedGames($player->getUsername());
            if ($result->isError()) {
                throw new Exception('Failed to list hosted games');
            }
            $hostedGames = $result->data();

            // list free games
            $result = $dbEntityGame->listFreeGames($player->getUsername(), [
                'hidden' => $hiddenFilter,
                'friendly' => $friendlyFilter,
                'long' => $longFilter,
            ]);
            if ($result->isError()) {
                throw new Exception('Failed to list free games');
            }
            $freeGames = $result->data();

            // list player's ready decks
            $result = $dbEntityChallenge->listReadyDecks($player->getUsername());
            if ($result->isError()) {
                throw new Exception('Failed to list ready decks for player');
            }
            $decks = $result->data();

            $data['free_slots'] = $this->service()->gameUtil()->countFreeSlots($player->getUsername());
            $data['decks'] = $decks;
            $data['random_deck'] = (count($decks) > 0) ? $decks[Random::arrayMtRand($decks)]['deck_id'] : '';
            $data['random_ai_deck'] = (count($decks) > 0) ? $decks[Random::arrayMtRand($decks)]['deck_id'] : '';
            $result = $defEntityChallenge->listChallenges();
            if ($result->isError()) {
                throw new Exception('Failed to list AI challenges');
            }
            $data['ai_challenges'] = $result->data();

            // compute opponent's activity for free games
            if (count($freeGames) > 0) {
                foreach ($freeGames as $i => $gameData) {
                    $opponentName = $gameData['player1'];

                    $opponent = $this->dbEntity()->player()->getPlayerAsserted($opponentName);
                    $setting = $this->dbEntity()->setting()->getSettingAsserted($opponentName);

                    $inactivity = time() - Date::strToTime($opponent->getLastActivity());

                    $data['free_games'][$i] = [
                        'opponent' => $opponentName,
                        'game_id' => $gameData['game_id'],
                        'active' => ($inactivity < 10 * Date::MINUTE) ? 'yes' : 'no',
                        'status' => $setting->getSetting('status'),
                        'game_action' => $gameData['last_action_at'],
                        'game_modes' => $gameData['game_modes'],
                        'timeout' => $gameData['turn_timeout'],
                    ];
                }
            }

            // reformat hosted games
            if (count($hostedGames) > 0) {
                foreach ($hostedGames as $i => $gameData) {
                    $data['hosted_games'][$i] = [
                        'game_id' => $gameData['game_id'],
                        'game_action' => $gameData['last_action_at'],
                        'game_modes' => $gameData['game_modes'],
                        'timeout' => $gameData['turn_timeout'],
                    ];
                }
            }
        }

        return new Result(['games' => $data]);
    }

    /**
     * @throws Exception
     * @return Result
     */
    protected function gamesDetails()
    {
        $input = $this->input();
        $player = $this->getCurrentPlayer();

        $dbEntityGame = $this->dbEntity()->game();
        $defEntityCard = $this->defEntity()->card();
        $dbEntityChat = $this->dbEntity()->chat();
        $dbEntityPlayer = $this->dbEntity()->player();

        // validate game id
        $this->assertInputNonEmpty(['current_game']);
        if (!is_numeric($input['current_game']) || $input['current_game'] < 0) {
            throw new Exception('Missing game id', Exception::WARNING);
        }

        $gameId = $input['current_game'];

        // prepare "core" data
        $formattedData = $this->formatGameData($gameId, $player->getUsername());

        // prepare additional data
        $data = array();

        $data['chat'] = (PlayerModel::checkAccess($player->getUserType(), 'chat')) ? 'yes' : 'no';

        $score = $this->dbEntity()->score()->getScoreAsserted($player->getUsername());

        // cheat menu data
        $cardNames = array();
        if ($score->getLevel() >= PlayerModel::TUTORIAL_END) {
            // list cards
            $ids = $defEntityCard->getList();
            $cardList = $defEntityCard->getData($ids);

            // reindex card list by card names
            $cardsSorted = array();
            foreach ($cardList as $card) {
                $cardsSorted[$card['name']] = ['id' => $card['id'], 'name' => $card['name']];
            }

            // sort card list by name alphabetically
            ksort($cardsSorted, SORT_STRING);

            $cardNames = array_values($cardsSorted);
        }
        $data['player_level'] = $score->getLevel();
        $data['tutorial_end'] = PlayerModel::TUTORIAL_END;
        $data['card_names'] = $cardNames;
        $data['cheat_menu'] = (isset($input['cheat_menu']) && $input['cheat_menu'] == 'yes') ? 'yes' : 'no';

        // load needed settings
        $setting = $this->dbEntity()->setting()->getSettingAsserted($player->getUsername());

        $data['timezone'] = $setting->getSetting('timezone');
        $data['play_buttons'] = $setting->getSetting('play_card_button');

        $game = $dbEntityGame->getGameAsserted($gameId);

        $player1 = $game->getPlayer1();
        $player2 = $game->getPlayer2();

        // disable auto-refresh if it's player's turn
        $data['auto_refresh'] = ($player->getUsername() == $game->getCurrent()) ? 0 : $setting->getSetting('auto_refresh_timer');

        $opponentName = ($player1 != $player->getUsername()) ? $player1 : $player2;

        // disable auto ai move if it's player's turn or if this is PvP game
        $data['auto_ai'] = ($player->getUsername() == $game->getCurrent()
            || $opponentName != PlayerModel::SYSTEM_NAME) ? 0 : $setting->getSetting('auto_ai_timer');

        $data['round'] = $game->getRound();
        $data['outcome'] = GameUtil::outcomeMessage($game->getOutcomeType());
        $data['outcome_type'] = $game->getOutcomeType();
        $data['winner'] = $game->getWinner();
        $data['has_note'] = ($game->getNote($player->getUsername()) != '') ? 'yes' : 'no';
        $data['game_note'] = $game->getNote($player->getUsername());

        // cheat menu
        $data['target_player'] = Input::defaultValue($input, 'target_player');
        $data['card_id'] = Input::defaultValue($input, 'card_id');
        $data['card_pos'] = Input::defaultValue($input, 'card_pos');
        $data['target_change'] = Input::defaultValue($input, 'target_change');
        $data['target_value'] = Input::defaultValue($input, 'target_value', 0);

        // new chat notification
        $chatNotification = ($player->getUsername() == $player1)
            ? $game->getChatNotification1() : $game->getChatNotification2();
        $data['chat_notification'] = $chatNotification;

        $result = $dbEntityChat->newMessages($game->getGameId(), $player->getUsername(), $chatNotification);
        if ($result->isError()) {
            throw new Exception('Failed to load new messages notification');
        }
        $newMessages = $result->isSuccess();
        $data['new_chat_messages'] = ($newMessages) ? 'yes' : 'no';

        // - <'jump to next game' button>
        $result = $dbEntityGame->nextGameList($player->getUsername());
        if ($result->isError()) {
            throw new Exception('Failed to next game list');
        }

        $nextGames = array();
        foreach ($result->data() as $nextGameData) {
            $nextGames[$nextGameData['game_id']] = $nextGameData['Opponent'];
        }

        $data['next_game_button'] = (count($nextGames) > 0) ? 'yes' : 'no';

        $opponent = $dbEntityPlayer->getPlayerAsserted($opponentName);

        $data['opponent_is_dead'] = ($opponent->isDead()) ? 'yes' : 'no';
        $data['finish_game'] = (time() - Date::strToTime($game->getLastAction()) >= 3 * Date::WEEK
            && $game->getCurrent() != $player->getUsername() && $opponentName != PlayerModel::SYSTEM_NAME) ? 'yes' : 'no';
        $data['finish_move'] = ($game->getTimeout() > 0
            && time() - Date::strToTime($game->getLastAction()) >= $game->getTimeout()
            && $game->getCurrent() != $player->getUsername() && $opponentName != PlayerModel::SYSTEM_NAME) ? 'yes' : 'no';

        // timeout message
        $timeout = '';
        if ($game->getTimeout() > 0 && $game->getCurrent() == $player->getUsername() && $opponent != PlayerModel::SYSTEM_NAME) {
            // case 1: time is up
            if (time() - Date::strToTime($game->getLastAction()) >= $game->getTimeout()) {
                $timeout = 'time is up';
            }
            // case 2: there is still some time left
            else {
                $timeout = Input::formatTimeDiff(
                        $game->getTimeout() - time() + Date::strToTime($game->getLastAction())
                    ) . ' remaining';
            }
        }
        $data['timeout'] = $timeout;

        // chat board
        $data['integrated_chat'] = $setting->getSetting('integrated_chat');
        $data['reverse_chat'] = $reverseChat = $setting->getSetting('chat_reverse_order');
        $order = ($reverseChat == 'yes') ? 'ASC' : 'DESC';

        // list in-game chat messages
        $result = $dbEntityChat->listChatMessages($game->getGameId(), $order);
        if ($result->isError()) {
            throw new Exception('Failed to list chat messages');
        }
        $messageList = $result->data();
        $data['message_list'] = $messageList;

        return new Result(['game' => array_merge($data, $formattedData)]);
    }

    /**
     * @throws Exception
     * @return Result
     */
    protected function gamesPreview()
    {
        $input = $this->input();
        $player = $this->getCurrentPlayer();

        // validate game id
        $this->assertInputNonEmpty(['current_game']);
        if (!is_numeric($input['current_game']) || $input['current_game'] < 0) {
            throw new Exception('Missing game id', Exception::WARNING);
        }

        return new Result(['game' => $this->formatGameData($input['current_game'], $player->getUsername())]);
    }

    /**
     * @throws Exception
     * @return Result
     */
    protected function decksView()
    {
        $data = array();
        $input = $this->input();

        $player = $this->getCurrentPlayer();
        $defEntityCard = $this->defEntity()->card();

        // validate game id
        $this->assertInputNonEmpty(['current_game']);
        if (!is_numeric($input['current_game']) || $input['current_game'] < 0) {
            throw new Exception('Missing game id', Exception::WARNING);
        }

        $gameId = $input['current_game'];
        $game = $this->dbEntity()->game()->getGameAsserted($gameId);

        // check if this user is allowed to view this game
        if ($player->getUsername() != $game->getPlayer1() && $player->getUsername() != $game->getPlayer2()) {
            throw new Exception('You are not allowed to access this game', Exception::WARNING);
        }

        // load deck data from game
        $gameData = $game->getData();
        $myData = $gameData[(($game->getPlayer1() == $player->getUsername()) ? 1 : 2)];
        $deck = $myData->Deck;

        // load needed settings
        $setting = $this->getCurrentSettings();

        $data['card_old_look'] = $setting->getSetting('old_card_look');
        $data['card_insignias'] = $setting->getSetting('keyword_insignia');
        $data['card_foils'] = $setting->getSetting('foil_cards');
        $data['current_game'] = $gameId;

        // load card data
        foreach (['Common', 'Uncommon', 'Rare'] as $rarity) {
            $data['deck_cards'][$rarity] = $defEntityCard->getData($deck->$rarity);
        }

        return new Result(['deck_view' => $data]);
    }

    /**
     * @throws Exception
     * @return Result
     */
    protected function gamesNote()
    {
        $data = array();
        $input = $this->input();

        $player = $this->getCurrentPlayer();

        // validate game id
        $this->assertInputNonEmpty(['current_game']);
        if (!is_numeric($input['current_game']) || $input['current_game'] < 0) {
            throw new Exception('Missing game id', Exception::WARNING);
        }

        $gameId = $input['current_game'];
        $game = $this->dbEntity()->game()->getGameAsserted($gameId);

        // check if this user is allowed to view this game
        if ($player->getUsername() != $game->getPlayer1() && $player->getUsername() != $game->getPlayer2()) {
            throw new Exception('You are not allowed to access this game', Exception::WARNING);
        }

        $data['current_game'] = $gameId;
        $data['text'] = Input::defaultValue($input, 'content', $game->getNote($player->getUsername()));

        return new Result(['game_note' => $data]);
    }
}
