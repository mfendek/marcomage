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
    const PLAYERS_PER_PAGE = 50;

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
     * use cookies flag
     * @var bool
     */
    public $cookies = false;

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->getFieldValue('Username');
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->getFieldValue('Password');
    }

    /**
     * @return int
     */
    public function getSessionId()
    {
        return $this->getFieldValue('SessionID');
    }

    /**
     * @return string
     */
    public function getUserType()
    {
        return $this->getFieldValue('UserType');
    }

    /**
     * @return string
     */
    public function getRegistered()
    {
        return $this->getFieldValue('Registered');
    }

    /**
     * @return string
     */
    public function getLastIP()
    {
        return $this->getFieldValue('Last IP');
    }

    /**
     * @return string
     */
    public function getLastQuery()
    {
        return $this->getFieldValue('Last Query');
    }

    /**
     * @return string
     */
    public function getNotification()
    {
        return $this->getFieldValue('Notification');
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
        return $this->setFieldValue('Password', $password);
    }

    /**
     * @param int $sessionId
     * @return $this
     */
    public function setSessionId($sessionId)
    {
        return $this->setFieldValue('SessionID', $sessionId);
    }

    /**
     * @param string $userType
     * @return $this
     */
    public function setUserType($userType)
    {
        return $this->setFieldValue('UserType', $userType);
    }

    /**
     * @param string $registered
     * @return $this
     */
    public function setRegistered($registered)
    {
        return $this->setFieldValue('Registered', $registered);
    }

    /**
     * @param string $lastIp
     * @return $this
     */
    public function setLastIp($lastIp)
    {
        return $this->setFieldValue('Last IP', $lastIp);
    }

    /**
     * @param string $lastQuery
     * @return $this
     */
    public function setLastQuery($lastQuery)
    {
        return $this->setFieldValue('Last Query', $lastQuery);
    }

    /**
     * @param string $notification
     * @return $this
     */
    public function setNotification($notification)
    {
        return $this->setFieldValue('Notification', $notification);
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
        return (time() - Date::strToTime($this->getLastQuery()) < 10 * Date::MINUTE);
    }

    /**
     * @return bool
     */
    public function isDead()
    {
        return (time() - Date::strToTime($this->getLastQuery()) > 3 * Date::WEEK);
    }

    /**
     * @return bool
     */
    public function isGuest()
    {
        return ($this->getUserType() == 'guest');
    }
}
