<?php
/**
 * GameAward - experience, awards, achievements
 */

namespace Service;

use ArcomageException as Exception;
use Db\Model\Game as GameModel;
use Def\Entity\XmlAward;
use Util\Rename;

class GameAward extends ServiceAbstract
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
     * Calculate gained experience and format game finish message
     * @param GameModel $game
     * @param string $player player name
     * @param int $player1Level player 1 level
     * @param int $player2Level player 2 level
     * @return array
     */
    public function calculateExp(GameModel $game, $player, $player1Level, $player2Level)
    {
        $defEntityCard = $this->defEntity()->card();

        // determine game mode (normal or long)
        $gameMode = ($game->checkGameMode('LongMode')) ? 'long' : 'normal';

        // game configuration
        $maxTower = $game->config('max_tower');
        $maxWall = $game->config('max_wall');
        $resVic = $game->config('res_victory');

        $opponent = ($game->getPlayer1() == $player) ? $game->getPlayer2() : $game->getPlayer1();
        $myData = $game->playerData($player);
        $hisData = $game->playerData($opponent);
        $round = $game->getRound();
        $winner = $game->getWinner();
        $endType = $game->getOutcomeType();
        $myLevel = ($game->getPlayer1() == $player) ? $player1Level : $player2Level;
        $hisLevel = ($game->getPlayer2() == $player) ? $player2Level : $player1Level;

        $win = ($player == $winner);
        // base exp
        $exp = 100;
        // base gold
        $gold = 0;
        $message = 'Base = ' . $exp . ' EXP' . "\n";

        // first phase: Game rating

        // resource victory
        if ($endType == 'Resource' && $win) {
            $mod = 1.15;
        }
        // construction victory
        elseif ($endType == 'Construction' && $win) {
            $mod = 1.10;
        }
        // destruction victory
        elseif ($endType == 'Destruction' && $win) {
            $mod = 1.05;
        }
        // abandon victory
        elseif ($endType == 'Abandon' && $win) {
            $mod = 1;
        }
        // surrender victory
        elseif ($endType == 'Surrender' && $win) {
            $mod = 0.95;
        }
        // timeout victory
        elseif ($endType == 'Timeout' && $win) {
            $mod = 0.6;
        }
        // draw
        elseif ($endType == 'Draw') {
            $mod = 0.5;
        }
        // timeout loss
        elseif ($endType == 'Timeout' && !$win) {
            $mod = 0.4;
        }
        // destruction loss
        elseif ($endType == 'Destruction' && !$win) {
            $mod = 0.15;
        }
        // construction loss
        elseif ($endType == 'Construction' && !$win) {
            $mod = 0.1;
        }
        // resource loss
        elseif ($endType == 'Resource' && !$win) {
            $mod = 0.05;
        }
        // surrender loss
        elseif ($endType == 'Surrender' && !$win) {
            $mod = 0;
        }
        // abandon loss
        elseif ($endType == 'Abandon' && !$win) {
            $mod = 0;
        }
        // should never happen
        else {
            $mod = 0;
        }

        // update exp and message
        $exp = round($exp * $mod);
        $message.= 'Game rating' . "\n" . 'Modifier: ' . $mod . ', Total: ' . $exp . ' EXP' . "\n";

        // second phase: Opponent rating

        // my level is higher
        if ($myLevel > $hisLevel) {
            $mod = 1 - 0.05 * min(10, $myLevel - $hisLevel);
        }
        // my level is lower
        elseif ($myLevel < $hisLevel) {
            $mod = 1 + 0.1 * min(10, $hisLevel - $myLevel);
        }
        // my level is equal
        else {
            $mod = 1;
        }

        // update exp and message
        $exp = round($exp * $mod);
        $message.= 'Opponent rating' . "\n" . 'Modifier: ' . $mod . ', Total: ' . $exp . ' EXP' . "\n";

        // third phase: Victory rating

        // player is winner
        if ($win) {
            // tactical (1), minor (2) and major (3) victory bonuses
            $bonus = [1 => 1, 2 => 1.25, 3 => 1.75];
            $victories = array();

            // Resource accumulation victory
            $enemyStock = $hisData->Bricks + $hisData->Gems + $hisData->Recruits;
            if ($enemyStock < round($resVic / 3)) {
                $victories[] = 3;
            }
            elseif ($enemyStock >= round($resVic / 3) && $enemyStock <= round($resVic * 2 / 3)) {
                $victories[] = 2;
            }
            else {
                $victories[] = 1;
            }

            // Tower building victory
            if ($hisData->Tower < round($maxTower / 3)) {
                $victories[] = 3;
            }
            elseif ($hisData->Tower >= round($maxTower / 3) && $hisData->Tower <= round($maxTower * 2 / 3)) {
                $victories[] = 2;
            }
            else {
                $victories[] = 1;
            }

            // Tower destruction victory
            if ($myData->Tower > round($maxTower * 2 / 3)) {
                $victories[] = 3;
            }
            elseif ($myData->Tower >= round($maxTower / 3) && $myData->Tower <= round($maxTower * 2 / 3)) {
                $victories[] = 2;
            }
            else {
                $victories[] = 1;
            }

            // calculate avg (rounded down)
            $victory = (int)floor(array_sum($victories) / count($victories));
            $mod = $bonus[$victory];

            // update exp and message
            $exp = round($exp * $mod);
            $message.= 'Victory rating' . "\n" . 'Modifier: ' . $mod . ', Total: ' . $exp . ' EXP' . "\n";
        }
        // player is defeated
        else {
            // tactical (1), minor (2) and major (3) victory bonuses
            $bonus = [1 => 1, 2 => 1.25, 3 => 1.75];
            $victories = array();

            // Resource accumulation victory
            $stock = $myData->Bricks + $myData->Gems + $myData->Recruits;

            // major
            if ($stock > round($resVic * 2 / 3)) {
                $victories[] = 3;
            }
            // minor
            elseif ($stock >= round($resVic / 3) && $stock <= round($resVic * 2 / 3)) {
                $victories[] = 2;
            }
            // tactical
            else {
                $victories[] = 1;
            }

            // Tower building victory
            // major
            if ($myData->Tower > round($maxTower * 2 / 3)) {
                $victories[] = 3;
            }
            // minor
            elseif ($myData->Tower >= round($maxTower / 3) && $myData->Tower <= round($maxTower * 2 / 3)) {
                $victories[] = 2;
            }
            // tactical
            else {
                $victories[] = 1;
            }

            // Tower destruction victory
            // major
            if ($hisData->Tower < round($maxTower / 3)) {
                $victories[] = 3;
            }
            // minor
            elseif ($hisData->Tower >= round($maxTower / 3) && $hisData->Tower <= round($maxTower * 2 / 3)) {
                $victories[] = 2;
            }
            // tactical
            else {
                $victories[] = 1;
            }

            // calculate avg (rounded up)
            $victory = (int)ceil(array_sum($victories) / count($victories));
            $mod = $bonus[$victory];

            // update exp and message
            $exp = round($exp * $mod);
            $message.= 'Victory rating' . "\n" . 'Modifier: ' . $mod . ', Total: ' . $exp . ' EXP' . "\n";
        }

        // fourth phase: Awards
        $received = array();

        // applied only in case of victory
        if ($win) {
            $myLastCardIndex = count($myData->LastCard);
            $myLastCard = $defEntityCard->getCard($myData->LastCard[$myLastCardIndex]);

            $myLastAction = $myData->LastAction[$myLastCardIndex];
            $standardVictory = in_array($endType, ['Resource', 'Construction', 'Destruction']);

            // awards list 'award_name' => 'gold_gain'
            $awards = [
                'saboteur' => 1,
                'gentle_touch' => 2,
                'desolator' => 3,
                'dragon' => 3,
                'carpenter' => 4,
                'titan' => 4,
                'assassin' => 5,
                'snob' => 6,
                'collector' => 7,
                'builder' => 8,
                'survivor' => 9
            ];
            // sort alphabetically
            ksort($awards);

            $assassinLimit = ($gameMode == 'long') ? 20 : 10;

            // Assassin
            if ($round <= $assassinLimit && $standardVictory) {
                $received[] = 'assassin';
            }

            // Desolator
            if ($hisData->Quarry == 1 && $hisData->Magic == 1 && $hisData->Dungeons == 1) {
                $received[] = 'desolator';
            }

            // Dragon
            if ($myLastCard->hasKeyword('Dragon') && $myLastAction == 'play' && $standardVictory) {
                $received[] = 'dragon';
            }

            // Carpenter
            if ($myData->Quarry >= 6 && $myData->Magic >= 6 && $myData->Dungeons >= 6) {
                $received[] = 'carpenter';
            }

            // Builder
            if ($myData->Wall == $maxWall) {
                $received[] = 'builder';
            }

            // Gentle touch
            if ($myLastCard->getRarity() == 'Common' && $myLastAction == 'play' && $standardVictory) {
                $received[] = 'gentle_touch';
            }

            // Snob
            if ($myLastAction == 'discard' && $standardVictory) {
                $received[] = 'snob';
            }

            // Collector
            $tmp = 0;
            for ($i = 1; $i <= 8; $i++) {
                $currentCard = $defEntityCard->getCard($myData->Hand[$i]);

                // count rare cards
                if ($currentCard->getRarity() == 'Rare') {
                    $tmp++;
                }
            }
            if ($tmp >= 4) {
                $received[] = 'collector';
            }

            // Titan
            if ($myLastCard->id() == 315 && $myLastAction == 'play' && $endType == 'Destruction') {
                $received[] = 'titan';
            }

            // Saboteur
            if ($hisData->Tower == 0 && $hisData->Wall > 0 && $standardVictory) {
                $received[] = 'saboteur';
            }

            // Survivor
            if ($myData->Tower == 1 && $myData->Wall == 0) {
                $received[] = 'survivor';
            }

            // update message, calculate gold
            // case 1: awards achieved
            if (count($received) > 0) {
                $awardsGained = array();
                foreach ($received as $award) {
                    $gold+= $awards[$award];
                    $awardsGained[] = Rename::underscoreToTextName($award) . ' (' . $awards[$award] . ' gold)';
                }
                $message.= 'Awards' . "\n" . implode("\n", $awardsGained) . "\n";
            }
            // case 2: no awards achieved
            else {
                $message.= 'Awards' . "\n" . 'None achieved' . "\n";
            }
        }

        // finalize report
        $message.= "\n" . 'You gained ' . $exp . ' EXP' . (($gold > 0) ? ' and ' . $gold . ' gold' : '');
        $result = array();
        $result['exp'] = $exp;
        $result['gold'] = $gold;
        $result['message'] = $message;
        $result['awards'] = $received;

        return $result;
    }

    /**
     * Update score on specified game award by specified amount
     * @param string $playerName player name
     * @param string $award award name
     * @param int [$amount]
     * @throws Exception
     */
    public function updateAward($playerName, $award, $amount = 1)
    {
        $dbEntityMessage = $this->dbEntity()->message();
        $defEntityAward = $this->defEntity()->award();

        $player = $this->dbEntity()->player()->getPlayerAsserted($playerName);

        // sanitize award name
        $award = str_replace(' ', '_', $award);

        // list all supported awards
        $result = $defEntityAward->awardsNames();
        if ($result->isError()) {
            throw new Exception('Failed to list award names');
        }
        $awardsList = $result->data();

        // check if award is supported
        if (!in_array($award, $awardsList)) {
            throw new Exception('Unsupported award '. $award);
        }

        $score = $this->dbEntity()->score()->getScoreAsserted($player->getUsername());

        // check if player gained achievement of specified award
        $result = $defEntityAward->getAchievements($award);
        if ($result->isError()) {
            throw new Exception('Failed to list achievements for award');
        }
        $achievements = $result->data();

        // find maximum value of the achievement counter
        $maxAmount = 0;
        foreach ($achievements as $achievementData) {
            $maxAmount = max($maxAmount, $achievementData['condition']);
        }

        // update related score property
        $before = $score->getData($award);
        $after = $before + $amount;

        // apply maximum to achievement counter
        $after = min($after, $maxAmount);
        $score->setData($award, $after);

        // find active achievement
        $achievement = false;
        foreach ($achievements as $achievementData) {
            if ($before < $achievementData['condition'] && $after >= $achievementData['condition']) {
                $achievement = $achievementData;
                break;
            }
        }

        // proceed only if active achievement exists
        if ($achievement) {
            // reward player with gold
            $score->setGold($score->getGold() + $achievement['reward']);

            // inform player about achievement gain
            $message = $dbEntityMessage->achievementNotification(
                $player->getUsername(), $achievement['name'], $achievement['reward']
            );
            if (!$message->save()) {
                throw new Exception('Failed to send achievement notification');
            }

            // check final achievement of the same tier as recently gained achievement
            if ($this->checkFinalAchievement($player->getUsername(), $achievement['tier'])) {
                // get final achievement data
                $final = XmlAward::finalAchievements($achievement['tier']);

                // reward player with gold
                $score->setGold($score->getGold() + $final['reward']);

                // inform player about achievement gain
                $message = $dbEntityMessage->achievementNotification(
                    $player->getUsername(), $final['name'], $final['reward']
                );
                if (!$message->save()) {
                    throw new Exception('Failed to send final achievement notification');
                }
            }
        }

        if (!$score->save()) {
            throw new Exception('Failed to save score');
        }
    }
}
