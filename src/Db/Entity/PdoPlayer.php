<?php
/**
 * Player - players database
 */

namespace Db\Entity;

use Db\Model\Player;
use Util\Date;

class PdoPlayer extends PdoAbstract
{
    /**
     * DB schema
     * @return array
     */
    protected function schema()
    {
        return [
            'entity_name' => 'logins',
            'model_name' => 'player',
            'primary_fields' => [
                'Username',
            ],
            'fields' => [
                'Username' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                ],
                'Password' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                ],
                'SessionID' => [
                    'type' => EntityAbstract::TYPE_INT32,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'UserType' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => 'user',
                ],
                'Registered' => [
                    'type' => EntityAbstract::TYPE_DATETIME,
                    'default' => Date::DATETIME_ZERO,
                    'options' => [EntityAbstract::OPT_INSERT_DATETIME],
                ],
                'Last IP' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '0.0.0.0',
                ],
                'Last Query' => [
                    'type' => EntityAbstract::TYPE_DATETIME,
                    'default' => Date::DATETIME_ZERO,
                ],
                'Notification' => [
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
        return parent::createModel(['Username' => $username]);
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

        return parent::getModel(['Username' => $username], $asserted);
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
     * @param string $activity activity filter
     * @param string $status status filter
     * @param string $name name filter
     * @param string $condition order condition
     * @param string $order order option
     * @param int $page current page
     * @return \Db\Util\Result
     */
    public function listPlayers($activity, $status, $name, $condition, $order, $page)
    {
        $db = $this->db();

        $interval = ($activity == 'active' ? '10 MINUTE'
            : ($activity == 'offline' ? '1 WEEK'
                : ($activity == 'none' ? '3 WEEK'
                    : ($activity == 'all' ? ''
                        : ''))));

        $nameQuery = ($name != '') ? ' AND `Username` LIKE ?' : '';
        $statusQuery = ($status != 'none') ? ' AND `Status` = ?' : '';
        $activityQuery = ($interval != '') ? ' AND `Last Query` >= NOW() - INTERVAL ' . $interval . '' : '';

        $params = array();
        if ($name != '') {
            $params[] = '%' . $name . '%';
        }
        if ($status != 'none') {
            $params[] = $status;
        }

        $validConditions = [
            'Level', 'Username', 'Country', 'Quarry', 'Magic', 'Dungeons', 'Rares', 'Challenges', 'Tower', 'Wall',
            'TowerDamage', 'WallDamage', 'Assassin', 'Builder', 'Carpenter', 'Collector', 'Desolator', 'Dragon',
            'Gentle_touch', 'Saboteur', 'Snob', 'Survivor', 'Titan'
        ];
        $condition = (in_array($condition, $validConditions)) ? $condition : 'Level';
        $order = ($order == 'ASC') ? 'ASC' : 'DESC';
        $page = (is_numeric($page)) ? $page : 0;

        return $db->query(
            'SELECT `Username`, `UserType`, `Level`, `Wins`, `Losses`, `Draws`, `Avatar`, `Status`, `FriendlyFlag`'
            . ', `BlindFlag`, `LongFlag`, `settings`.`Country`, `Last Query` FROM `logins` JOIN `settings`'
            . ' USING (`Username`) JOIN `scores` USING (`Username`) WHERE 1 ' . $nameQuery . $statusQuery . $activityQuery
            . ' ORDER BY `' . $condition . '` ' . $order . ', `Username` ASC LIMIT '
            . (Player::PLAYERS_PER_PAGE * $page) . ', ' . Player::PLAYERS_PER_PAGE . ''
            , $params
        );
    }

    /**
     * Count pages for players list
     * @param string $activity activity filter
     * @param string $status status filter
     * @param string $name name filter
     * @return \Db\Util\Result
     */
    public function countPages($activity, $status, $name)
    {
        $db = $this->db();

        $interval = ($activity == 'active' ? '10 MINUTE'
            : ($activity == 'offline' ? '1 WEEK'
                : ($activity == 'none' ? '3 WEEK'
                    : ($activity == 'all' ? ''
                        : ''))));

        $nameQuery = ($name != '') ? ' AND `Username` LIKE ?' : '';
        $statusQuery = ($status != 'none') ? ' JOIN (SELECT `Username` FROM `settings` WHERE `Status` = ?) as `settings` USING (`Username`)' : '';
        $activityQuery = ($interval != '') ? ' AND `Last Query` >= NOW() - INTERVAL ' . $interval . '' : '';

        $params = array();
        if ($status != 'none') {
            $params[] = $status;
        }
        if ($name != '') {
            $params[] = '%' . $name . '%';
        }

        return $db->query(
            'SELECT COUNT(`Username`) as `Count` FROM `logins`' . $statusQuery . ' WHERE 1' . $activityQuery . $nameQuery . ''
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
            'UserType' => 'guest',
            'Last Query' => Date::DATETIME_ZERO,
            'Notification' => Date::timeToStr(time() + Date::DAY),
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
        return $db->query('SELECT 1 FROM `logins` WHERE `Last IP` = ? AND `Registered` >= NOW() - INTERVAL 1 MINUTE LIMIT 1', [
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

        return $db->query('UPDATE `logins` SET `Username` = ? WHERE `Username` = ?', [$new_name, $player]);
    }
}
