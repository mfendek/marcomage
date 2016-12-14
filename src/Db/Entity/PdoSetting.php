<?php
/**
 * Setting - player settings database
 */

namespace Db\Entity;

use Db\Model\Player;
use Util\Date;

class PdoSetting extends PdoAbstract
{
    /**
     * DB schema
     * @return array
     */
    protected function schema()
    {
        return [
            'entity_name' => 'settings',
            'model_name' => 'setting',
            'primary_fields' => [
                'Username',
            ],
            'fields' => [
                'Username' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                ],
                'Firstname' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                ],
                'Surname' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                ],
                'Birthdate' => [
                    'type' => EntityAbstract::TYPE_DATE,
                    'default' => Date::DATE_ZERO,
                ],
                'Gender' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => 'none',
                ],
                'Email' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                ],
                'Imnumber' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                ],
                'Country' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => 'Unknown',
                ],
                'Hobby' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                ],
                'Avatar' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => 'noavatar.jpg',
                ],
                'Status' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => 'newbie',
                ],
                'FriendlyFlag' => [
                    'type' => EntityAbstract::TYPE_INT32,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'BlindFlag' => [
                    'type' => EntityAbstract::TYPE_INT32,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'LongFlag' => [
                    'type' => EntityAbstract::TYPE_INT32,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'Timezone' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '0',
                ],
                'Insignias' => [
                    'type' => EntityAbstract::TYPE_INT32,
                    'default' => 1,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'PlayButtons' => [
                    'type' => EntityAbstract::TYPE_INT32,
                    'default' => 1,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'Chatorder' => [
                    'type' => EntityAbstract::TYPE_INT32,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'IntegratedChat' => [
                    'type' => EntityAbstract::TYPE_INT32,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'Avatargame' => [
                    'type' => EntityAbstract::TYPE_INT32,
                    'default' => 1,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'OldCardLook' => [
                    'type' => EntityAbstract::TYPE_INT32,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'Miniflags' => [
                    'type' => EntityAbstract::TYPE_INT32,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'Reports' => [
                    'type' => EntityAbstract::TYPE_INT32,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'Forum_notification' => [
                    'type' => EntityAbstract::TYPE_INT32,
                    'default' => 1,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'Concepts_notification' => [
                    'type' => EntityAbstract::TYPE_INT32,
                    'default' => 1,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'Skin' => [
                    'type' => EntityAbstract::TYPE_INT32,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'Background' => [
                    'type' => EntityAbstract::TYPE_INT32,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'DefaultFilter' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => 'none',
                ],
                'Autorefresh' => [
                    'type' => EntityAbstract::TYPE_INT32,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'AutoAi' => [
                    'type' => EntityAbstract::TYPE_INT32,
                    'default' => 5,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'RandomDeck' => [
                    'type' => EntityAbstract::TYPE_INT32,
                    'default' => 1,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'GameLimit' => [
                    'type' => EntityAbstract::TYPE_INT32,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'Timeout' => [
                    'type' => EntityAbstract::TYPE_INT32,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'FoilCards' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                    // TODO do we want this?
//                    'options' => [
//                        EntityAbstract::OPT_NO_DIFF,
//                        EntityAbstract::OPT_SERIALIZE_LIST,
//                    ],
                ],
            ],
        ];
    }

    /**
     * Create new setting for specified player
     * @param string $username username
     * @return \Db\Model\Setting
     */
    public function createSetting($username)
    {
        return parent::createModel(['Username' => $username]);
    }

    /**
     * @param string $username username
     * @param bool [$asserted]
     * @return \Db\Model\Setting
     */
    public function getSetting($username, $asserted = false)
    {
        // detect guest setting
        if (in_array($username, ['', Player::SYSTEM_NAME])) {
            return $this->getGuestSettings();
        }

        return parent::getModel(['Username' => $username], $asserted);
    }

    /**
     * @param string $username
     * @return \Db\Model\Setting
     */
    public function getSettingAsserted($username)
    {
        return $this->getSetting($username, true);
    }

    /**
     * Load guest settings
     * @return \Db\Model\Setting
     */
    public function getGuestSettings()
    {
        return $this->createModel([
            'Skin' => 0,
            'Timezone' => '0',
            'Autorefresh' => 0,
            'OldCardLook' => 0,
            'Insignias' => 1,
            'Country' => 'Arcomage',
            'Avatar' => 'noavatar.jpg',
            'DefaultFilter' => 'none',
            'FoilCards' => '',
        ])
        ->resetCreated()
        ->cleanup();
    }

    /**
     * Rename all player name instances in settings
     * @param string $player player name
     * @param string $newName new name
     * @return \Db\Util\Result
     */
    public function renameSettings($player, $newName)
    {
        $db = $this->db();

        return $db->query('UPDATE `settings` SET `Username` = ? WHERE `Username` = ?', [$newName, $player]);
    }
}
