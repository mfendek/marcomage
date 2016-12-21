<?php
/**
 * GameTurn - game turn execution
 */

namespace Service;

use ArcomageException as Exception;
use Db\Model\Game as GameModel;
use Db\Model\Player as PlayerModel;
use Util\Date;

class GameTurn extends ServiceAbstract
{
    /**
     * Execute game turn (returns level-up flag)
     * @param string $playerName
     * @param GameModel $game
     * @param string $action card action ('play' or 'discard')
     * @param int $cardPos card position (1 - 8)
     * @param int [$mode] card mode (0 - 8)
     * @param int [$currentRound] current round validation data
     * @throws Exception
     * @return int
     */
    public function executeGameTurn($playerName, $game, $action, $cardPos, $mode = 0, $currentRound = 0)
    {
        $dbEntityMessage = $this->dbEntity()->message();
        $serviceGameUseCard = $this->service()->gameUseCard();

        // default level-up flag
        $levelUpFlag = 0;

        // check if game is locked in a surrender request
        if ($game->getSurrender() != '') {
            throw new Exception('Game is locked in a surrender request', Exception::WARNING);
        }

        // validate current round (prevents unintentional game actions via form re-submit)
        if ($currentRound > 0 && $currentRound != $game->getRound()) {
            throw new Exception('Unintentional re-submit detected, ignoring game action', Exception::WARNING);
        }

        // the rest of the checks are done internally
        $result = $serviceGameUseCard->useCard($game, $playerName, $action, $cardPos, $mode);
        if (isset($result['error'])) {
            throw new Exception($result['error'], Exception::WARNING);
        }

        // process card statistics
        if ($game->getPlayer2() != PlayerModel::SYSTEM_NAME) {
            $cardStats = $serviceGameUseCard->getCardStats();
            if (count($cardStats) > 0) {
                $this->service()->statistic()->updateCardStats($cardStats);
            }
        }

        // process gained awards
        $awards = $serviceGameUseCard->getAwards();
        foreach ($awards as $awardName => $amount) {
            $this->service()->gameAward()->updateAward($playerName, $awardName, $amount);
        }

        $this->service()->gameUtil()->saveGameWithReplay($game);

        // game has finished this move
        if ($game->getState() == 'finished') {
            // update deck statistics
            $this->service()->deck()->updateDeckStatistics(
                $game->getPlayer1(), $game->getPlayer2(), $game->getDeckId1(), $game->getDeckId2(), $game->getWinner()
            );

            // update AI challenge score in case of AI challenge game
            if ($game->getAiName() != '' && $game->getWinner() == $playerName) {
                $this->service()->gameAward()->updateAward($playerName, 'ai_challenges');
            }

            // case 1: standard AI mode
            if ($game->checkGameMode('AIMode') && $game->getAiName() == '') {
                // fetch player's level
                $score = $this->dbEntity()->score()->getScoreAsserted($playerName);

                $playerLevel = $score->getLevel();

                // add experience in case player is still in tutorial
                if ($playerLevel < PlayerModel::TUTORIAL_END) {
                    // opponent is AI - use player level
                    $exp = $this->service()->gameAward()->calculateExp($game, $playerName, $playerLevel, $playerLevel);

                    // add exp and process level up if necessary
                    $levelUp = $score->addExp($exp['exp']);
                    $score->setGold($score->getGold() + $exp['gold']);

                    // process gained awards
                    foreach ($exp['awards'] as $award) {
                        $this->service()->gameAward()->updateAward($playerName, $award);
                    }

                    if (!$score->save()) {
                        throw new Exception('Failed to save score');
                    }

                    // display level up dialog
                    if ($levelUp) {
                        $levelUpFlag = $score->getLevel();
                    }

                    $setting = $this->dbEntity()->setting()->getSettingAsserted($playerName);

                    // send level up message
                    if ($levelUp && $setting->getSetting('battle_report') == 'yes') {
                        $message = $dbEntityMessage->levelUp($playerName, $score->getLevel());
                        if (!$message->save()) {
                            throw new Exception('Failed to send level up message');
                        }
                    }
                }
            }
            // case 2: standard game
            elseif (!$game->checkGameMode('FriendlyPlay')) {
                $levelUpFlag = $this->gameFinishProcessing($playerName, $game);
            }
        }

        return $levelUpFlag;
    }

    /**
     * Game finish processing (returns level-up flag)
     * @param string $playerName
     * @param GameModel $game
     * @throws Exception
     * @return int
     */
    public function gameFinishProcessing($playerName, GameModel $game)
    {
        $dbEntityMessage = $this->dbEntity()->message();
        $serviceGameAward =  $this->service()->gameAward();

        // default level-up flag
        $levelUpFlag = 0;

        $player1 = $game->getPlayer1();
        $player2 = $game->getPlayer2();

        // load score for both players
        $score1 = $this->dbEntity()->score()->getScoreAsserted($player1);
        $score2 = $this->dbEntity()->score()->getScoreAsserted($player2);

        // calculate exp data for both players
        $exp1 = $serviceGameAward->calculateExp($game, $player1, $score1->getLevel(), $score2->getLevel());
        $exp2 = $serviceGameAward->calculateExp($game, $player2, $score1->getLevel(), $score2->getLevel());

        // update score
        // player 1 won
        if ($game->getWinner() == $player1) {
            $score1->setData('wins', $score1->getData('wins') + 1);
            $score2->setData('losses', $score2->getData('losses') + 1);
        }
        // player 2 won
        elseif ($game->getWinner() == $player2) {
            $score2->setData('wins', $score2->getData('wins') + 1);
            $score1->setData('losses', $score1->getData('losses') + 1);
        }
        // draw
        else {
            $score1->setData('draws', $score1->getData('draws') + 1);
            $score2->setData('draws', $score1->getData('draws') + 1);
        }

        $levelUp1 = $score1->addExp($exp1['exp']);
        $levelUp2 = $score2->addExp($exp2['exp']);
        $score1->setGold($score1->getGold() + $exp1['gold']);
        $score2->setGold($score2->getGold() + $exp2['gold']);

        // process gained awards
        foreach ($exp1['awards'] as $award) {
            $this->service()->gameAward()->updateAward($player1, $award);
        }
        foreach ($exp2['awards'] as $award) {
            $this->service()->gameAward()->updateAward($player2, $award);
        }

        if (!$score1->save()) {
            throw new Exception('Failed to save score');
        }
        if (!$score2->save()) {
            throw new Exception('Failed to save score');
        }

        // display level-up dialog
        if ($levelUp1 && $player1 == $playerName) {
            $levelUpFlag = $score1->getLevel();
        }
        if ($levelUp2 && $player2 == $playerName) {
            $levelUpFlag = $score2->getLevel();
        }

        // load settings for both players
        $setting1 = $this->dbEntity()->setting()->getSettingAsserted($player1);
        $setting2 = $this->dbEntity()->setting()->getSettingAsserted($player2);

        // send level up messages
        $player1Report = $setting1->getSetting('battle_report');
        if ($levelUp1 && $player1Report == 'yes') {
            $message = $dbEntityMessage->levelUp($player1, $score1->getLevel());
            if (!$message->save()) {
                throw new Exception('Failed to send level up message');
            }
        }
        $player2Report = $setting2->getSetting('battle_report');
        if ($levelUp2 && $player2Report == 'yes') {
            $message = $dbEntityMessage->levelUp($player2, $score2->getLevel());
            if (!$message->save()) {
                throw new Exception('Failed to send level up message');
            }
        }

        // send battle report message
        $outcome = GameUtil::outcomeMessage($game->getOutcomeType());
        $winner = $game->getWinner();
        $hidden = $game->checkGameMode('HiddenCards');

        // player 1 battle report
        if ($player1Report == 'yes') {
            $message = $dbEntityMessage->sendBattleReport(
                $player1, $player2, $outcome, $hidden, $exp1['message'], $winner
            );
            if (!$message->save()) {
                throw new Exception('Failed to send battle report message');
            }
        }

        // player 2 battle report
        if ($player2Report == 'yes') {
            $message = $dbEntityMessage->sendBattleReport(
                $player2, $player1, $outcome, $hidden, $exp2['message'], $winner
            );
            if (!$message->save()) {
                throw new Exception('Failed to send battle report message');
            }
        }

        return $levelUpFlag;
    }

    /**
     * Execute AI game turn
     * @param $playerName
     * @param GameModel $game
     * @param array [$decision]
     * @throws Exception
     */
    public function executeAiGameTurn($playerName, GameModel $game, array $decision = [])
    {
        $defEntityChallenge = $this->defEntity()->challenge();

        // check if AI move is allowed
        if (!$game->checkGameMode('AIMode')) {
            throw new Exception('AI move not allowed!', Exception::WARNING);
        }

        // only allow AI move if the game is still on
        if ($game->getState() != 'in progress') {
            throw new Exception('Action not allowed!', Exception::WARNING);
        }

        // only allow action when it's the AI's turn
        if ($game->getCurrent() != PlayerModel::SYSTEM_NAME) {
            throw new Exception('Action only allowed on your turn!', Exception::WARNING);
        }

        $gameAi = $this->service()->gameAi();
        // add custom config in case of challenge AI
        if ($game->getAiName() != '') {
            $challenge = $defEntityChallenge->getChallenge($game->getAiName());
            $gameAi->setCustomConfig($challenge->getConfig());
        }

        // case 1: decision needs to be determine
        if (empty($decision)) {
            // determine AI move
            $decision = $gameAi->determineMove(PlayerModel::SYSTEM_NAME, $game);
        }
        // case 2: decision is provided
        else {
            // only allow action in case of standard AI
            if ($game->getAiName() != '') {
                throw new Exception('Action only allowed in game vs standard AI', Exception::WARNING);
            }
        }

        $cardPos = $decision['cardpos'];
        $mode = $decision['mode'];
        $action = $decision['action'];

        // fetch player's level
        $score = $this->dbEntity()->score()->getScoreAsserted($playerName);

        $playerLevel = $score->getLevel();

        // sabotage standard AI to relax the difficulty
        if ($game->getAiName() == '' && $action == 'play' && $playerLevel < PlayerModel::TUTORIAL_END) {
            $chance = max(1 / 2 - $playerLevel / 20, 0);
            $chance = round($chance * 100);
            $gamble = mt_rand(1, 100);

            if ($gamble <= $chance) {
                $action = 'discard';
                $mode = 0;
            }
        }

        $result = $this->service()->gameUseCard()->useCard($game, PlayerModel::SYSTEM_NAME, $action, $cardPos, $mode);
        if (isset($result['error'])) {
            throw new Exception($result['error'], Exception::WARNING);
        }

        $this->service()->gameUtil()->saveGameWithReplay($game);

        // game has finished this move
        if ($game->getState() == 'finished') {
            // update deck statistics
            $this->service()->deck()->updateDeckStatistics(
                $game->getPlayer1(), $game->getPlayer2(), $game->getDeckId1(), $game->getDeckId2(), $game->getWinner()
            );

            // update AI challenge score in case of AI challenge game
            if ($game->getAiName() != '' && $game->getWinner() == $playerName) {
                $this->service()->gameAward()->updateAward($playerName, 'ai_challenges');
            }
        }
    }

    /**
     * Make AI move instead of opponent (returns level-up flag)
     * @param string $playerName
     * @param GameModel $game
     * @throws Exception
     * @return int
     */
    public function makeSubstituteAiMove($playerName, GameModel $game)
    {
        $serviceGameUseCard = $this->service()->gameUseCard();

        // default level-up flag
        $levelUpFlag = 0;

        // check if game is locked in a surrender request
        if ($game->getSurrender() != '') {
            throw new Exception('Game is locked in a surrender request', Exception::WARNING);
        }

        // only allow finishing of non-AI games
        if ($game->getPlayer2() == PlayerModel::SYSTEM_NAME) {
            throw new Exception('Action not allowed!', Exception::WARNING);
        }

        // only allow finish move if the game is still on
        if ($game->getState() != 'in progress') {
            throw new Exception('Game has to be in progress!', Exception::WARNING);
        }

        // and only if the finish move criteria are met
        if ($game->getTimeout() == 0 || time() - Date::strToTime($game->getLastAction()) < $game->getTimeout()
            || $game->getCurrent() == $playerName) {
            throw new Exception('Action not allowed!', Exception::WARNING);
        }

        $opponentName = ($game->getPlayer1() == $playerName) ? $game->getPlayer2() : $game->getPlayer1();

        // determine AI move
        $decision = $this->service()->gameAi()->determineMove($opponentName, $game);
        $cardPos = $decision['cardpos'];
        $mode = $decision['mode'];
        $action = $decision['action'];

        $result = $serviceGameUseCard->useCard($game, $opponentName, $action, $cardPos, $mode);
        if (isset($result['error'])) {
            throw new Exception($result['error'], Exception::WARNING);
        }

        // process card statistics
        $cardStats = $serviceGameUseCard->getCardStats();
        if (count($cardStats) > 0) {
            $this->service()->statistic()->updateCardStats($cardStats);
        }

        // process gained awards
        $awards = $serviceGameUseCard->getAwards();
        foreach ($awards as $award_name => $amount) {
            $this->service()->gameAward()->updateAward($opponentName, $award_name, $amount);
        }

        $this->service()->gameUtil()->saveGameWithReplay($game);

        // game has finished this move
        if ($game->getState() == 'finished') {
            // update deck statistics
            $this->service()->deck()->updateDeckStatistics(
                $game->getPlayer1(), $game->getPlayer2(), $game->getDeckId1(), $game->getDeckId2(), $game->getWinner()
            );

            // process game finish in case of non friendly play game
            if (!$game->checkGameMode('FriendlyPlay')) {
                $levelUpFlag = $this->gameFinishProcessing($playerName, $game);
            }
        }

        return $levelUpFlag;
    }
}
