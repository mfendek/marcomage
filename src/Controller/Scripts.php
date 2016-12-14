<?php
/**
 * Scripts - maintenance scripts
 */

namespace Controller;

use ArcomageException as Exception;
use Util\Encode;

class Scripts extends ControllerAbstract
{
    /**
     * Initialize game id auto increment value
     * @throws Exception
     */
    protected function initGameAutoIncrement()
    {
        $db = $this->getDb();

        $this->result()->setData(array_merge($this->result()->data(), [
            'Setting game auto increment...'
        ]));

        // get max id from games
        $result = $db->query('SELECT MAX(`GameID`) as `max` FROM `games`');
        if ($result->isErrorOrNoEffect()) {
            throw new Exception('Failed to retrieve max id from games DB');
        }
        $maxGameId = $result[0]['max'] + 1;

        // get max id from replays
        $result = $db->query('SELECT MAX(`GameID`) as `max` FROM `replays`');
        if ($result->isErrorOrNoEffect()) {
            throw new Exception('Failed to retrieve max id from replays DB');
        }
        $nextAutoIncrement = max($maxGameId, $result[0]['max'] + 1);

        // set auto increment
        $result = $db->query('ALTER TABLE `games` AUTO_INCREMENT = ' . $nextAutoIncrement);
        if ($result->isError()) {
            throw new Exception('Failed to initialize game auto increment');
        }

        $this->result()->setData(array_merge($this->result()->data(), [
            'Game auto-increment set to ' . $nextAutoIncrement
        ]));
    }

    /**
     * Delete dead users (no activity in more than 12 weeks and zero score) and their related data
     * deletes 50 users at a time
     * provides debug information about delete procedure
     * @throws Exception
     */
    protected function playersCleanup()
    {
        $db = $this->getDb();

        $this->result()->setData(array_merge($this->result()->data(), [
            'Deleting player data...'
        ]));

        $result = $db->query(
            'SELECT `Username` FROM (SELECT `Username` FROM `logins` WHERE `Last Query` < NOW() - INTERVAL 12 WEEK) as `logins` INNER JOIN (SELECT `Username` FROM `scores` WHERE `Wins` + `Losses` + `Draws` = 0) as `scores` USING (`Username`) LIMIT 50'
        );
        if ($result->isError()) {
            throw new Exception('Failed to find dead players');
        }

        foreach ($result->data() as $data) {
            $username = $data['Username'];

            $this->result()->setData(array_merge($this->result()->data(), [
                'Deleting player ' . Encode::htmlEncode($username)
            ]));

            $message = 'Success';

            try {
                $this->service()->player()->deletePlayer($username);
            }
            catch (Exception $e) {
                $message = 'Failure ' . $e->getMessage();
            }

            $this->result()->setData(array_merge($this->result()->data(), [
                $message
            ]));
        }

        $this->result()->setData(array_merge($this->result()->data(), [
            'Done.'
        ]));
    }
}
