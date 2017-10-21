<?php
/**
 * Player - in-game identity of a player
 */

namespace Db\Model;

use Util\Date;

class Player extends ModelAbstract
{
    /**
     * User name for system notification and AI games
     */
    const SYSTEM_NAME = 'MArcomage';

    /**
     * Number of players that are displayed per one page
     */
    const PLAYERS_PER_PAGE = 30;

    /**
     * Maximum length of hobby description
     */
    const HOBBY_LENGTH = 300;

    /**
     * Tutorial end level
     */
    const TUTORIAL_END = 10;

    /**
     * Avatar maximum upload size
     */
    const UPLOAD_SIZE = 10 * 1000;

    /**
     * @return array
     */
    private static function listAccessRights()
    {
        return [
            'guest' => [
                'create_post' => false,
                'create_thread' => false,
                'del_all_post' => false,
                'del_all_thread' => false,
                'lock_thread' => false,
                'edit_own_post' => false,
                'edit_all_post' => false,
                'edit_all_thread' => false,
                'edit_own_thread' => false,
                'move_post' => false,
                'move_thread' => false,
                'change_priority' => false,
                'messages' => false,
                'chat' => false,
                'create_card' => false,
                'edit_own_card' => false,
                'edit_all_card' => false,
                'delete_own_card' => false,
                'delete_all_card' => false,
                'send_challenges' => false,
                'accept_challenges' => false,
                'change_own_avatar' => false,
                'change_all_avatar' => false,
                'login' => false,
                'see_all_messages' => false,
                'system_notification' => false,
                'reset_exp' => false,
                'export_deck' => false,
                'change_rights' => false
            ],
            'banned' => [],
            'limited' => [
                'messages' => true,
                'accept_challenges' => true,
                'login' => true
            ],
            'squashed' => [
                'create_post' => true,
                'messages' => true,
                'chat' => true,
                'accept_challenges' => true,
                'login' => true
            ],
            'user' => [
                'create_post' => true,
                'create_thread' => true,
                'edit_own_post' => true,
                'edit_own_thread' => true,
                'messages' => true,
                'chat' => true,
                'create_card' => true,
                'edit_own_card' => true,
                'delete_own_card' => true,
                'send_challenges' => true,
                'accept_challenges' => true,
                'change_own_avatar' => true,
                'login' => true
            ],
            'supervisor' => [
                'create_post' => true,
                'create_thread' => true,
                'edit_own_post' => true,
                'edit_own_thread' => true,
                'messages' => true,
                'chat' => true,
                'create_card' => true,
                'edit_own_card' => true,
                'edit_all_card' => true,
                'delete_own_card' => true,
                'delete_all_card' => true,
                'send_challenges' => true,
                'accept_challenges' => true,
                'change_own_avatar' => true,
                'login' => true
            ],
            'moderator' => [
                'create_post' => true,
                'create_thread' => true,
                'del_all_post' => true,
                'del_all_thread' => true,
                'lock_thread' => true,
                'edit_own_post' => true,
                'edit_all_post' => true,
                'edit_all_thread' => true,
                'edit_own_thread' => true,
                'move_post' => true,
                'move_thread' => true,
                'change_priority' => true,
                'messages' => true,
                'chat' => true,
                'create_card' => true,
                'edit_own_card' => true,
                'delete_own_card' => true,
                'send_challenges' => true,
                'accept_challenges' => true,
                'change_own_avatar' => true,
                'login' => true
            ],
            'admin' => [
                'create_post' => true,
                'create_thread' => true,
                'del_all_post' => true,
                'del_all_thread' => true,
                'lock_thread' => true,
                'edit_own_post' => true,
                'edit_all_post' => true,
                'edit_all_thread' => true,
                'edit_own_thread' => true,
                'move_post' => true,
                'move_thread' => true,
                'change_priority' => true,
                'messages' => true,
                'chat' => true,
                'create_card' => true,
                'edit_own_card' => true,
                'edit_all_card' => true,
                'delete_own_card' => true,
                'delete_all_card' => true,
                'send_challenges' => true,
                'accept_challenges' => true,
                'change_own_avatar' => true,
                'change_all_avatar' => true,
                'login' => true,
                'see_all_messages' => true,
                'system_notification' => true,
                'reset_exp' => true,
                'export_deck' => true,
                'change_rights' => true
            ]
        ];
    }

    /**
     * Check access for specified user type and access right
     * @param string $userType user type
     * @param string $accessRight access right
     * @return bool
     */
    public static function checkAccess($userType, $accessRight)
    {
        $accessList = self::listAccessRights();

        if (!in_array($userType, array_keys($accessList))) {
            return false;
        }

        $accessRights = array_merge($accessList['guest'], $accessList[$userType]);

        if (!isset($accessRights[$accessRight])) {
            return false;
        }

        return $accessRights[$accessRight];
    }

    /**
     * use cookies flag
     * @var bool
     */
    public $cookies = false;

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->getFieldValue('username');
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->getFieldValue('password');
    }

    /**
     * @return int
     */
    public function getSessionId()
    {
        return $this->getFieldValue('session_id');
    }

    /**
     * @return string
     */
    public function getUserType()
    {
        return $this->getFieldValue('user_type');
    }

    /**
     * @return string
     */
    public function getRegistered()
    {
        return $this->getFieldValue('registered_at');
    }

    /**
     * @return string
     */
    public function getLastIP()
    {
        return $this->getFieldValue('last_ip');
    }

    /**
     * @return string
     */
    public function getLastActivity()
    {
        return $this->getFieldValue('last_activity_at');
    }

    /**
     * @return string
     */
    public function getNotification()
    {
        return $this->getFieldValue('notification_at');
    }

    /**
     * @return bool
     */
    public function hasCookies()
    {
        return $this->cookies;
    }

    /**
     * @param string $password
     * @return $this
     */
    public function setPassword($password)
    {
        return $this->setFieldValue('password', $password);
    }

    /**
     * @param int $sessionId
     * @return $this
     */
    public function setSessionId($sessionId)
    {
        return $this->setFieldValue('session_id', $sessionId);
    }

    /**
     * @param string $userType
     * @return $this
     */
    public function setUserType($userType)
    {
        return $this->setFieldValue('user_type', $userType);
    }

    /**
     * @param string $registered
     * @return $this
     */
    public function setRegistered($registered)
    {
        return $this->setFieldValue('registered_at', $registered);
    }

    /**
     * @param string $lastIp
     * @return $this
     */
    public function setLastIp($lastIp)
    {
        return $this->setFieldValue('last_ip', $lastIp);
    }

    /**
     * @param string $lastActivity
     * @return $this
     */
    public function setLastActivity($lastActivity)
    {
        return $this->setFieldValue('last_activity_at', $lastActivity);
    }

    /**
     * @param string $notification
     * @return $this
     */
    public function setNotification($notification)
    {
        return $this->setFieldValue('notification_at', $notification);
    }

    /**
     * Terminate session
     * @return $this
     */
    public function logout()
    {
        return $this->setSessionId(0);
    }

    /**
     * @return bool
     */
    public function isOnline()
    {
        return (time() - Date::strToTime($this->getLastActivity()) < 10 * Date::MINUTE);
    }

    /**
     * @return bool
     */
    public function isDead()
    {
        return (time() - Date::strToTime($this->getLastActivity()) > 3 * Date::WEEK);
    }

    /**
     * @return bool
     */
    public function isGuest()
    {
        return ($this->getUserType() == 'guest');
    }
}
