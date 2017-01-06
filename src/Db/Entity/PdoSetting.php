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
            'entity_name' => 'setting',
            'primary_fields' => [
                'username',
            ],
            'fields' => [
                'username' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                ],
                'first_name' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                ],
                'surname' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                ],
                'birth_date' => [
                    'type' => EntityAbstract::TYPE_DATE,
                    'default' => Date::DATE_ZERO,
                ],
                'gender' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => 'none',
                ],
                'email' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                ],
                'im_number' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                ],
                'country' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => 'Unknown',
                ],
                'hobby' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                ],
                'avatar' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => 'noavatar.jpg',
                ],
                'status' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => 'newbie',
                ],
                'friendly_flag' => [
                    'type' => EntityAbstract::TYPE_INT,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'blind_flag' => [
                    'type' => EntityAbstract::TYPE_INT,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'long_flag' => [
                    'type' => EntityAbstract::TYPE_INT,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'timezone' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '0',
                ],
                'keyword_insignia' => [
                    'type' => EntityAbstract::TYPE_INT,
                    'default' => 1,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'play_card_button' => [
                    'type' => EntityAbstract::TYPE_INT,
                    'default' => 1,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'chat_reverse_order' => [
                    'type' => EntityAbstract::TYPE_INT,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'integrated_chat' => [
                    'type' => EntityAbstract::TYPE_INT,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'in_game_avatar' => [
                    'type' => EntityAbstract::TYPE_INT,
                    'default' => 1,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'old_card_look' => [
                    'type' => EntityAbstract::TYPE_INT,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'card_mini_flag' => [
                    'type' => EntityAbstract::TYPE_INT,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'battle_report' => [
                    'type' => EntityAbstract::TYPE_INT,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'forum_notification' => [
                    'type' => EntityAbstract::TYPE_INT,
                    'default' => 1,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'concept_notification' => [
                    'type' => EntityAbstract::TYPE_INT,
                    'default' => 1,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'skin' => [
                    'type' => EntityAbstract::TYPE_INT,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'game_bg_image' => [
                    'type' => EntityAbstract::TYPE_INT,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'default_player_filter' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => 'none',
                ],
                'auto_refresh_timer' => [
                    'type' => EntityAbstract::TYPE_INT,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'auto_ai_timer' => [
                    'type' => EntityAbstract::TYPE_INT,
                    'default' => 5,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'use_random_deck' => [
                    'type' => EntityAbstract::TYPE_INT,
                    'default' => 1,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'unique_game_opponent' => [
                    'type' => EntityAbstract::TYPE_INT,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'game_turn_timeout' => [
                    'type' => EntityAbstract::TYPE_INT,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'foil_cards' => [
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
        return parent::createModel(['username' => $username]);
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

        return parent::getModel(['username' => $username], $asserted);
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
            'skin' => 0,
            'timezone' => '0',
            'auto_refresh_timer' => 0,
            'old_card_look' => 0,
            'keyword_insignia' => 1,
            'country' => 'Arcomage',
            'avatar' => 'noavatar.jpg',
            'default_player_filter' => 'none',
            'foil_cards' => '',
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

        return $db->query('UPDATE `setting` SET `username` = ? WHERE `username` = ?', [$newName, $player]);
    }
}
