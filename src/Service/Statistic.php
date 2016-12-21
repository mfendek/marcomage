<?php
/**
 * Statistic
 */

namespace Service;

use ArcomageException as Exception;
use Util\Input;

class Statistic extends ServiceAbstract
{
    /**
     * Calculate game victory types statistics
     * @throws Exception
     * @return array
     */
    public function victoryTypes()
    {
        $dbEntityReplay = $this->dbEntity()->replay();

        // fill statistics with default values
        $statistics = [
            'Construction', 'Destruction', 'Resource', 'Timeout', 'Draw', 'Surrender', 'Abort', 'Abandon'
        ];
        $statistics = array_combine($statistics, array_fill(0, count($statistics), 0));

        // get number of different victory types
        $result = $dbEntityReplay->listVictoryTypes();
        if ($result->isError()) {
            throw new Exception('failed to list victory types');
        }

        $totalGames = 0;
        foreach ($result->data() as $data) {
            $totalGames+= $data['count'];
            $statistics[$data['outcome_type']] = $data['count'];
        }

        // calculate percentage, restructure data
        $rounded = array();
        foreach ($statistics as $statistic => $value) {
            $currentStatistic['type'] = $statistic;
            $currentStatistic['count'] = ($totalGames > 0) ? round(($value / $totalGames) * 100, 2) : 0;
            $rounded[] = $currentStatistic;
        }

        return $rounded;
    }

    /**
     * Calculate game modes statistics
     * @throws Exception
     * @return array
     */
    public function gameModes()
    {
        $dbEntityReplay = $this->dbEntity()->replay();

        // get number of games with various game modes
        $result = $dbEntityReplay->listGameModes();
        if ($result->isError()) {
            throw new Exception('failed to list game modes');
        }

        $totalGames = 0;
        $gameModes = array();
        foreach ($result->data() as $resData) {
            $totalGames+= $resData['count'];

            $modes = explode(",", $resData['game_modes']);
            foreach ($modes as $mode) {
                if (isset($gameModes[$mode])) {
                    $gameModes[$mode]+= $resData['count'];
                }
                else {
                    $gameModes[$mode] = $resData['count'];
                }
            }
        }

        $statistics['hidden'] = Input::defaultValue($gameModes, 'HiddenCards', 0);
        $statistics['friendly'] = Input::defaultValue($gameModes, 'FriendlyPlay', 0);;
        $statistics['long'] = Input::defaultValue($gameModes, 'LongMode', 0);;

        // get number of AI mode games (exclude AI challenges)
        $result = $dbEntityReplay->countAiGames();
        if ($result->isErrorOrNoEffect()) {
            throw new Exception('failed to count AI mode games');
        }

        $data = $result[0];
        $statistics['ai'] = $data['ai'];

        // get number of AI victories (exclude AI challenges)
        $result = $dbEntityReplay->countAiVictories();
        if ($result->isErrorOrNoEffect()) {
            throw new Exception('failed to count AI victories');
        }

        $data = $result[0];
        $aiWins = $data['ai_wins'];

        $aiWinRatio = ($statistics['ai'] > 0) ? round(($aiWins / $statistics['ai']) * 100, 2) : 0;

        // get number of AI challenge games
        $result = $dbEntityReplay->countAiChallenges();
        if ($result->isErrorOrNoEffect()) {
            throw new Exception('failed to count AI challenges');
        }

        $data = $result[0];
        $statistics['challenge'] = $data['challenge'];

        // get number of AI challenge victories
        $result = $dbEntityReplay->countAiChallengeVictories();
        if ($result->isErrorOrNoEffect()) {
            throw new Exception('failed to count AI challenge victories');
        }

        $data = $result[0];
        $challengeWins = $data['challenge_wins'];

        $challengeWinRatio = ($statistics['challenge'] > 0) ? round(($challengeWins / $statistics['challenge']) * 100, 2) : 0;

        // calculate percentage
        foreach ($statistics as $statistic => $value) {
            $statistics[$statistic] = ($totalGames > 0) ? round(($value / $totalGames) * 100, 2) : 0;
        }

        // calculate AI win ratio
        $statistics['ai_wins'] = $aiWinRatio;
        $statistics['challenge_wins'] = $challengeWinRatio;

        return $statistics;
    }

    /**
     * Calculate versus statistics for the two specified players from player1 perspecive
     * @param string $player1 player 1 name
     * @param string $player2 player 2 name
     * @throws Exception
     * @return array
     */
    public function versusStats($player1, $player2)
    {
        $dbEntityReplay = $this->dbEntity()->replay();
        $statistics = array();

        foreach (['wins' => $player1, 'losses' => $player2, 'other' => ''] as $type => $winner) {
            // get number of games with various game modes
            $result = $dbEntityReplay->listGameModesVersus($player1, $player2, $winner);
            if ($result->isError()) {
                throw new Exception('failed to list game mode versus ' . $type . ' stats');
            }

            $total = 0;
            if (count($result->data()) > 0) {
                foreach ($result->data() as $data) {
                    $statistics[$type][] = $data;
                    $total+= $data['count'];
                }

                // calculate percentage
                foreach ($statistics[$type] as $i => $data) {
                    $statistics[$type][$i]['ratio'] = ($total > 0) ? round(($data['count'] / $total) * 100, 1) : 0;
                }
            }
            $statistics[$type . '_total'] = $total;
        }

        // average game duration (normal mode)
        $result = $dbEntityReplay->versusGameDurationNormal($player1, $player2);
        if ($result->isErrorOrNoEffect()) {
            throw new Exception('failed to count average game duration (normal) versus player');
        }

        $data = $result[0];
        $statistics['turns'] = $data['turns'];
        $statistics['rounds'] = $data['rounds'];

        // average game duration (long mode)
        $result = $dbEntityReplay->versusGameDurationLong($player1, $player2);
        if ($result->isErrorOrNoEffect()) {
            throw new Exception('failed to count average game duration (long) versus player');
        }

        $data = $result[0];
        $statistics['turns_long'] = $data['turns'];
        $statistics['rounds_long'] = $data['rounds'];

        return $statistics;
    }

    /**
     * Calculate overall game statistics for the specified player
     * @param string $player player name
     * @throws Exception
     * @return array
     */
    public function gameStats($player)
    {
        $dbEntityReplay = $this->dbEntity()->replay();
        $statistics = array();

        // wins statistics
        $result = $dbEntityReplay->countPlayerWins($player);
        if ($result->isError()) {
            throw new Exception('failed to count wins for player');
        }

        $winsTotal = 0;
        if (count($result->data()) > 0) {
            foreach ($result->data() as $data) {
                $statistics['wins'][] = $data;
                $winsTotal+= $data['count'];
            }

            // calculate percentage
            foreach ($statistics['wins'] as $i => $data) {
                $statistics['wins'][$i]['ratio'] = ($winsTotal > 0) ? round(($data['count'] / $winsTotal) * 100, 1) : 0;
            }
        }
        $statistics['wins_total'] = $winsTotal;

        // loss statistics
        $result = $dbEntityReplay->countPlayerLosses($player);
        if ($result->isError()) {
            throw new Exception('failed to count losses for player');
        }

        $lossesTotal = 0;

        if (count($result->data()) > 0) {
            foreach ($result->data() as $data) {
                $statistics['losses'][] = $data;
                $lossesTotal+= $data['count'];
            }

            // calculate percentage
            foreach ($statistics['losses'] as $i => $data) {
                $statistics['losses'][$i]['ratio'] = ($lossesTotal > 0) ? round(($data['count'] / $lossesTotal) * 100, 1) : 0;
            }
        }
        $statistics['losses_total'] = $lossesTotal;

        // other statistics (draws, aborts...)
        $result = $dbEntityReplay->countPlayerDraws($player);
        if ($result->isError()) {
            throw new Exception('failed to count draws for player');
        }

        $otherTotal = 0;
        if (count($result->data()) > 0) {
            foreach ($result->data() as $data) {
                $statistics['other'][] = $data;
                $otherTotal+= $data['count'];
            }

            // calculate percentage
            foreach ($statistics['other'] as $i => $data) {
                $statistics['other'][$i]['ratio'] = ($otherTotal > 0) ? round(($data['count'] / $otherTotal) * 100, 2) : 0;
            }
        }
        $statistics['other_total'] = $otherTotal;

        // average game duration (normal mode)
        $result = $dbEntityReplay->totalGameDurationNormal($player);
        if ($result->isErrorOrNoEffect()) {
            throw new Exception('failed to count average game duration (normal) in total');
        }

        $data = $result[0];
        $statistics['turns'] = $data['turns'];
        $statistics['rounds'] = $data['rounds'];

        // average game duration (long mode)
        $result = $dbEntityReplay->totalGameDurationLong($player);
        if ($result->isErrorOrNoEffect()) {
            throw new Exception('failed to count average game duration (long) in total');
        }

        $data = $result[0];
        $statistics['turns_long'] = $data['turns'];
        $statistics['rounds_long'] = $data['rounds'];

        return $statistics;
    }

    /**
     * Update card statistics (used when card is played, drawn or discarded)
     * @param array $stats 'card_id' => 'action' => 'action_count'
     * @throws Exception
     */
    public function updateCardStats(array $stats)
    {
        $dbEntityStatistic = $this->dbEntity()->statistic();

        // extract card ids
        $ids = array_keys($stats);

        // check if the cards are already present in the database
        $result = $dbEntityStatistic->findCards($ids);
        if ($result->isError()) {
            throw new Exception('failed to find card statistics data');
        }

        // create a list of existing card ids
        $updateIds = array();
        foreach ($result->data() as $data) {
            $updateIds[] = $data['card_id'];
        }

        foreach ($stats as $cardId => $actions) {
            // case 1: card record already exists - update row data
            if (in_array($cardId, $updateIds)) {
                $result = $dbEntityStatistic->updateCard($cardId, $actions);
                if ($result->isErrorOrNoEffect()) {
                    throw new Exception('failed to update card statistic');
                }
            }
            // case 2: card record does not exist yet - insert new row
            else {
                $result = $dbEntityStatistic->createCard($cardId, $actions);
                if ($result->isErrorOrNoEffect()) {
                    throw new Exception('failed to create card statistic');
                }
            }
        }
    }
}
