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
        $result = $db->query('SELECT MAX(`game_id`) as `max` FROM `game`');
        if ($result->isErrorOrNoEffect()) {
            throw new Exception('Failed to retrieve max id from games DB');
        }
        $maxGameId = $result[0]['max'] + 1;

        // get max id from replays
        $result = $db->query('SELECT MAX(`game_id`) as `max` FROM `replay`');
        if ($result->isErrorOrNoEffect()) {
            throw new Exception('Failed to retrieve max id from replays DB');
        }
        $nextAutoIncrement = max($maxGameId, $result[0]['max'] + 1);

        // set auto increment
        $result = $db->query('ALTER TABLE `game` AUTO_INCREMENT = ' . $nextAutoIncrement);
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
            'SELECT `username` FROM (SELECT `username` FROM `login` WHERE `last_activity_at` < NOW() - INTERVAL 12 WEEK) as `login` INNER JOIN (SELECT `username` FROM `score` WHERE `wins` + `losses` + `draws` = 0) as `score` USING (`username`) LIMIT 50'
        );
        if ($result->isError()) {
            throw new Exception('Failed to find dead players');
        }

        foreach ($result->data() as $data) {
            $username = $data['username'];

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

    /**
     * @throws Exception
     */
    protected function r2636()
    {
        $db = $this->getDb();

        $this->result()->setData(array_merge($this->result()->data(), [
            'Updating forum thread data...'
        ]));

        $result = $db->query('SELECT `card_id`, `thread_id` FROM `concept` WHERE `thread_id` > 0');
        if ($result->isError()) {
            throw new Exception('Failed to list concepts');
        }

        $conceptsCount = 0;
        foreach ($result->data() as $data) {
            $threadId = $data['thread_id'];
            $conceptId = $data['card_id'];

            $result = $db->query('UPDATE `forum_thread` SET `reference_id` = ? WHERE `thread_id` = ?', [
                $conceptId, $threadId
            ]);
            if ($result->isError()) {
                throw new Exception('Failed to update forum thread (concept)');
            }

            $conceptsCount++;
        }

        $result = $db->query('SELECT `game_id`, `thread_id` FROM `replay` WHERE `thread_id` > 0');
        if ($result->isError()) {
            throw new Exception('Failed to list replays');
        }

        $replaysCount = 0;
        foreach ($result->data() as $data) {
            $threadId = $data['thread_id'];
            $gameId = $data['game_id'];

            $result = $db->query('UPDATE `forum_thread` SET `reference_id` = ? WHERE `thread_id` = ?', [
                $gameId, $threadId
            ]);
            if ($result->isError()) {
                throw new Exception('Failed to update forum thread (replay)');
            }

            $replaysCount++;
        }

        $this->result()->setData(array_merge($this->result()->data(), [
            'Done. Updated concepts: ' . $conceptsCount . '. Updated replays: ' . $replaysCount
        ]));
    }
}
