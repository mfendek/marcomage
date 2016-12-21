<?php
/**
 * Setting - player settings
 */

namespace Db\Model;

use Util\Date;

class Setting extends ModelAbstract
{
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
    public function getBirthDate()
    {
        return $this->getFieldValue('birth_date');
    }

    /**
     * Return all settings data
     * @return array settings
     */
    public function getData()
    {
        $data = array();
        $settings = array_merge(self::listBooleanSettings(), self::listOtherSettings());

        foreach ($settings as $setting) {
            $data[$setting] = (in_array($setting, self::listBooleanSettings()))
                ? (($this->getFieldValue($setting) == 1) ? 'yes' : 'no') : $this->getFieldValue($setting);
        }

        return $data;
    }

    /**
     * Get specified setting value
     * @param string $setting setting name
     * @return mixed setting
     */
    public function getSetting($setting)
    {
        // reformat boolean setting value
        $value = (in_array($setting, self::listBooleanSettings()))
            ? (($this->getFieldValue($setting) == 1) ? 'yes' : 'no') : $this->getFieldValue($setting);

        return $value;
    }

    /**
     * Change specified setting value
     * @param string $setting setting name
     * @param mixed $value new value
     * @return $this
     */
    public function changeSetting($setting, $value)
    {
        return $this->setFieldValue($setting, $value);
    }

    /**
     * Calculates age from birth date (standard date string)
     * @return int age
     */
    public function age()
    {
        list($year, $month, $day) = explode("-", $this->getBirthDate());

        $age = date('Y') - $year;
        if (date('m') < $month || (date('m') == $month && date('d') < $day)) {
            $age--;
        }

        return (int)$age;
    }

    /**
     * Calculates sign from birth date (standard date string)
     * @return string sign
     */
    public function sign()
    {
        $birthDate = Date::strToTime($this->getBirthDate());
        $month = intval(date('m', $birthDate));
        $day = intval(date('j', $birthDate));

        if (($month == 3 && $day >= 21) || ($month == 4 && $day <= 19)) {
            $sign = 'Aries';
        }
        elseif (($month == 4 && $day >= 20) || ($month == 5 && $day <= 20)) {
            $sign = 'Taurus';
        }
        elseif (($month == 5 && $day >= 21) || ($month == 6 && $day <= 20)) {
            $sign = 'Gemini';
        }
        elseif (($month == 6 && $day >= 21) || ($month == 7 && $day <= 22)) {
            $sign = 'Cancer';
        }
        elseif (($month == 7 && $day >= 23) || ($month == 8 && $day <= 22)) {
            $sign = 'Leo';
        }
        elseif (($month == 8 && $day >= 23) || ($month == 9 && $day <= 22)) {
            $sign = 'Virgo';
        }
        elseif (($month == 9 && $day >= 23) || ($month == 10 && $day <= 22)) {
            $sign = 'Libra';
        }
        elseif (($month == 10 && $day >= 23) || ($month == 11 && $day <= 21)) {
            $sign = 'Scorpio';
        }
        elseif (($month == 11 && $day >= 22) || ($month == 12 && $day <= 21)) {
            $sign = 'Sagittarius';
        }
        elseif (($month == 12 && $day >= 22) || ($month == 1 && $day <= 19)) {
            $sign = 'Capricorn';
        }
        elseif (($month == 1 && $day >= 20) || ($month == 2 && $day <= 18)) {
            $sign = 'Aquarius';
        }
        elseif (($month == 2 && $day >= 19) || ($month == 3 && $day <= 20)) {
            $sign = 'Pisces';
        }
        else {
            $sign = 'Unknown';
        }

        return $sign;
    }

    /**
     * Returns list of all boolean type setting names
     * @return array boolean settings
     */
    public static function listBooleanSettings()
    {
        return [
            'friendly_flag',
            'blind_flag',
            'long_flag',
            'keyword_insignia',
            'play_card_button',
            'chat_reverse_order',
            'integrated_chat',
            'in_game_avatar',
            'old_card_look',
            'card_mini_flag',
            'battle_report',
            'forum_notification',
            'concept_notification',
            'use_random_deck',
            'unique_game_opponent',
        ];
    }

    /**
     * Returns list of all non-boolean type setting names
     * @return array other settings
     */
    public static function listOtherSettings()
    {
        return [
            'first_name',
            'surname',
            'birth_date',
            'gender',
            'email',
            'im_number',
            'country',
            'hobby',
            'avatar',
            'status',
            'timezone',
            'skin',
            'game_bg_image',
            'default_player_filter',
            'auto_refresh_timer',
            'auto_ai_timer',
            'game_turn_timeout',
            'foil_cards',
        ];
    }
}
