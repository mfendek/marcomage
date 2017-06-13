<?php
/**
 * Player - players database
 */

namespace Db\Entity;

use Db\Model\Player;
use Util\Date;
use Util\Input;

class PdoPlayer extends PdoAbstract
{
    /**
     * DB schema
     * @return array
     */
    protected function schema()
    {
        return [
            'entity_name' => 'login',
            'model_name' => 'player',
            'primary_fields' => [
                'username',
            ],
            'fields' => [
                'username' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                ],
                'password' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                ],
                'session_id' => [
                    'type' => EntityAbstract::TYPE_INT,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'user_type' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => 'user',
                ],
                'registered_at' => [
                    'type' => EntityAbstract::TYPE_DATETIME,
                    'default' => Date::DATETIME_ZERO,
                    'options' => [EntityAbstract::OPT_INSERT_DATETIME],
                ],
                'last_ip' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '0.0.0.0',
                ],
                'last_activity_at' => [
                    'type' => EntityAbstract::TYPE_DATETIME,
                    'default' => Date::DATETIME_ZERO,
                ],
                'notification_at' => [
                    'type' => EntityAbstract::TYPE_DATETIME,
                    'default' => Date::DATETIME_ZERO,
                ],
            ],
        ];
    }

    /**
     * Create new player
     * @param string $username
     * @return Player
     */
    public function createPlayer($username)
    {
        return parent::createModel(['username' => $username]);
    }

    /**
     * @param string $username
     * @param bool [$asserted]
     * @return Player
     */
    public function getPlayer($username, $asserted = false)
    {
        // detect guest player
        if (in_array($username, ['', Player::SYSTEM_NAME])) {
            return $this->getGuest();
        }

        return parent::getModel(['username' => $username], $asserted);
    }

    /**
     * @param string $username
     * @return Player
     */
    public function getPlayerAsserted($username)
    {
        return $this->getPlayer($username, true);
    }

    /**
     * List players
     * @param array $data
     * @return \Db\Util\Result
     */
    public function listPlayers(array $data)
    {
        $db = $this->db();

        $name = (isset($data['name'])) ? $data['name'] : '';
        $status = (isset($data['status'])) ? $data['status'] : 'none';
        $activity = (isset($data['activity'])) ? $data['activity'] : 'none';
        $condition = (isset($data['condition'])) ? $data['condition'] : '';
        $order = (isset($data['order'])) ? $data['order'] : 'ASC';
        $page = (isset($data['page'])) ? $data['page'] : 0;

        $interval = ($activity == 'active' ? '10 MINUTE'
            : ($activity == 'offline' ? '1 WEEK'
                : ($activity == 'none' ? '3 WEEK'
                    : ($activity == 'all' ? ''
                        : ''))));

        $nameQuery = ($name != '') ? ' AND `username` LIKE ?' : '';
        $statusQuery = ($status != 'none') ? ' AND `status` = ?' : '';
        $activityQuery = ($interval != '') ? ' AND `last_activity_at` >= NOW() - INTERVAL ' . $interval . '' : '';

        $params = array();
        if ($name != '') {
            $params[] = '%' . $name . '%';
        }
        if ($status != 'none') {
            $params[] = $status;
        }

        $validConditions = [
            'level', 'username', 'country', 'quarry', 'magic', 'dungeons', 'rares', 'ai_challenges', 'tower', 'wall',
            'tower_damage', 'wall_damage', 'assassin', 'builder', 'carpenter', 'collector', 'desolator', 'dragon',
            'gentle_touch', 'saboteur', 'snob', 'survivor', 'titan'
        ];
        $condition = (in_array($condition, $validConditions)) ? $condition : 'level';
        $order = ($order == 'ASC') ? 'ASC' : 'DESC';
        $page = Input::unsignedInt($page);

        return $db->query(
            'SELECT `username`, `user_type`, `level`, `wins`, `losses`, `draws`, `avatar`, `status`, `friendly_flag`'
            . ', `blind_flag`, `long_flag`, `setting`.`country`, `last_activity_at` FROM `login` JOIN `setting`'
            . ' USING (`username`) JOIN `score` USING (`username`) WHERE 1 ' . $nameQuery . $statusQuery . $activityQuery
            . ' ORDER BY `' . $condition . '` ' . $order . ', `username` ASC LIMIT '
            . (Player::PLAYERS_PER_PAGE * $page) . ', ' . Player::PLAYERS_PER_PAGE . ''
            , $params
        );
    }

    /**
     * Count pages for players list
     * @param array $data
     * @return \Db\Util\Result
     */
    public function countPages(array $data)
    {
        $db = $this->db();

        $name = (isset($data['name'])) ? $data['name'] : '';
        $status = (isset($data['status'])) ? $data['status'] : 'none';
        $activity = (isset($data['activity'])) ? $data['activity'] : 'none';

        $interval = ($activity == 'active' ? '10 MINUTE'
            : ($activity == 'offline' ? '1 WEEK'
                : ($activity == 'none' ? '3 WEEK'
                    : ($activity == 'all' ? ''
                        : ''))));

        $nameQuery = ($name != '') ? ' AND `username` LIKE ?' : '';
        $statusQuery = ($status != 'none') ? ' JOIN (SELECT `username` FROM `setting` WHERE `status` = ?) as `setting` USING (`username`)' : '';
        $activityQuery = ($interval != '') ? ' AND `last_activity_at` >= NOW() - INTERVAL ' . $interval . '' : '';

        $params = array();
        if ($status != 'none') {
            $params[] = $status;
        }
        if ($name != '') {
            $params[] = '%' . $name . '%';
        }

        return $db->query(
            'SELECT COUNT(`username`) as `count` FROM `login`' . $statusQuery . ' WHERE 1' . $activityQuery . $nameQuery . ''
            , $params
        );
    }

    /**
     * Get guest player
     * @return Player
     */
    public function getGuest()
    {
        return $this->createModel([
            'user_type' => 'guest',
            'last_activity_at' => Date::DATETIME_ZERO,
            'notification_at' => Date::timeToStr(time() + Date::DAY),
        ])
            ->resetCreated()
            ->cleanup();
    }

    /**
     * Validate IP for new player creation to prevent massive player creation from the same IP
     * @param string $ip ip address
     * @return \Db\Util\Result
     */
    public function validateIp($ip)
    {
        $db = $this->db();

        // flood prevention - limits the frequency of account creations per ip
        return $db->query('SELECT 1 FROM `login` WHERE `last_ip` = ? AND `registered_at` >= NOW() - INTERVAL 1 MINUTE LIMIT 1', [
            $ip
        ]);
    }

    /**
     * Rename all player name instances in players
     * @param string $player player name
     * @param string $new_name new name
     * @return \Db\Util\Result
     */
    public function renamePlayer($player, $new_name)
    {
        $db = $this->db();

        return $db->query('UPDATE `login` SET `username` = ? WHERE `username` = ?', [$new_name, $player]);
    }
}
