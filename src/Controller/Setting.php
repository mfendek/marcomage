<?php
/**
 * Setting - settings related controller
 */

namespace Controller;

use ArcomageException as Exception;
use Db\Model\Player as PlayerModel;
use Util\Date;
use Util\Input;
use Util\Rename;

class Setting extends ControllerAbstract
{
    /**
     * Upload user settings
     * @throws Exception
     */
    protected function saveSettings()
    {
        $request = $this->request();

        $this->result()->setCurrent('Settings');

        $this->assertParamsExist(['hobby', 'status', 'default_player_filter', 'gender', 'birth_date']);

        // validate hobby
        if (mb_strlen($request['hobby']) > PlayerModel::HOBBY_LENGTH) {
            // trim hobby text to appropriate length
            $request['hobby'] = mb_substr($request['hobby'], 0, PlayerModel::HOBBY_LENGTH);

            $this->result()->setWarning('Hobby text is too long');
        }

        // validate player status
        if (!in_array($request['status'], ['newbie', 'ready', 'quick', 'dnd', 'none'])) {
            $this->result()
                ->changeRequest('status', 'none')
                ->setWarning('Invalid player status');
        }

        // validate default player filter setting
        if (!in_array($request['default_player_filter'], ['none', 'active', 'offline', 'all'])) {
            $this->result()
                ->changeRequest('default_player_filter', 'none')
                ->setWarning('Invalid player filter');
        }

        // validate gender setting
        if (!in_array($request['gender'], ['none', 'male', 'female'])) {
            $this->result()
                ->changeRequest('gender', 'none')
                ->setWarning('Invalid gender setting');
        }

        $setting = $this->getCurrentSettings();

        $booleanSettings = \Db\Model\Setting::listBooleanSettings();
        $otherSettings = \Db\Model\Setting::listOtherSettings();

        // process yes/no settings
        foreach ($booleanSettings as $settingName) {
            $setting->changeSetting($settingName, ((isset($request[$settingName])) ? 1 : 0));
        }

        // process other settings
        foreach ($otherSettings as $settingName) {
            // omit birth date, avatar and foil cards
            if (!in_array($settingName, ['birth_date', 'avatar', 'foil_cards'])) {
                $this->assertParamsExist([$settingName]);

                $setting->changeSetting($settingName, $request[$settingName]);
            }
        }

        // birth date is not mandatory
        if ($request['birth_date'] != '') {
            $birthDate = explode('-', $request['birth_date']);

            // date is expected to be in format dd-mm-yyyy
            if (count($birthDate) != 3) {
                $this->result()->setWarning('Invalid birth date');
            }
            else {
                list($year, $month, $day) = explode('-', $request['birth_date']);

                $result = Input::checkDateInput($year, $month, $day);
                if ($result != '') {
                    $this->result()->setWarning($result);
                }
                // disallow future dates
                elseif (time() <= Date::strToTime(implode('-', [$year, $month, $day]))) {
                    $this->result()->setWarning('Future birth date not allowed');
                }
                else {
                    $setting->changeSetting('birth_date', $request['birth_date']);
                }
            }
        }

        if (!$setting->save()) {
            throw new Exception('Failed to change settings');
        }

        $this->result()->setInfo('User settings saved');
    }

    /**
     * Upload avatar
     * @throws Exception
     */
    protected function uploadAvatarImage()
    {
        $files = $this->getDic()->files();
        $player = $this->getCurrentPlayer();
        $config = $this->getDic()->config();

        $this->result()->setCurrent('Settings');

        // check access rights
        if (!$this->checkAccess('change_own_avatar')) {
            throw new Exception('Access denied', Exception::WARNING);
        }

        $setting = $this->getCurrentSettings();

        // determine avatar picture paths
        $formerName = $setting->getSetting('avatar');
        $uploadDir = $config['upload_dir']['avatar'];
        $formerPath = $uploadDir . $formerName;

        $type = $files['avatar_image_file']['type'];
        $pos = strrpos($type, "/") + 1;

        $codeType = substr($type, $pos, mb_strlen($type) - $pos);
        $filteredName = preg_replace("/[^a-zA-Z0-9_-]/i", "_", $player->getUsername());

        $codeName = time() . $filteredName . '.' . $codeType;
        $targetPath = $uploadDir . $codeName;

        // validate upload name
        if ($files['avatar_image_file']['tmp_name'] == '') {
            throw new Exception('Invalid input file', Exception::WARNING);
        }

        // validate file size
        if ($files['avatar_image_file']['size'] > PlayerModel::UPLOAD_SIZE) {
            throw new Exception('File is too big', Exception::WARNING);
        }

        // validate file type
        if (!in_array($files['avatar_image_file']['type'], Input::imageUploadTypes())) {
            throw new Exception('Unsupported input file', Exception::WARNING);
        }

        // upload file
        if (move_uploaded_file($files['avatar_image_file']['tmp_name'], $targetPath) == false) {
            throw new Exception('Upload failed, error code ' . $files['avatar_image_file']['error'], Exception::WARNING);
        }

        // remove old avatar picture if it exists
        if (file_exists($formerPath) && $formerName != 'noavatar.jpg') {
            unlink($formerPath);
        }

        $setting->changeSetting('avatar', $codeName);
        if (!$setting->save()) {
            throw new Exception('Failed to change avatar');
        }

        $this->result()->setInfo('Avatar uploaded');
    }

    /**
     * Reset own avatar
     * @throws Exception
     */
    protected function resetAvatar()
    {
        $config = $this->getDic()->config();

        $this->result()->setCurrent('Settings');

        // check access rights
        if (!$this->checkAccess('change_own_avatar')) {
            throw new Exception('Access denied', Exception::WARNING);
        }

        $setting = $this->getCurrentSettings();

        $formerName = $setting->getSetting('avatar');
        $formerPath = $config['upload_dir']['avatar'] . $formerName;

        // delete avatar picture
        if (file_exists($formerPath) && $formerName != 'noavatar.jpg') {
            unlink($formerPath);
        }

        $setting->changeSetting('avatar', 'noavatar.jpg');
        if (!$setting->save()) {
            throw new Exception('Failed to reset avatar');
        }

        $this->result()->setInfo('Avatar cleared');
    }

    /**
     * Change password
     * @throws Exception
     */
    protected function changePassword()
    {
        $request = $this->request();

        $player = $this->getCurrentPlayer();

        $this->result()->setCurrent('Settings');

        $this->assertParamsNonEmpty(['new_password', 'confirm_password']);

        $newPassword = trim($request['new_password']);
        $newPasswordConf = trim($request['confirm_password']);

        // validate passwords
        if ($newPassword == '' || $newPasswordConf == '') {
            throw new Exception('Please enter all required inputs', Exception::WARNING);
        }

        // check if passwords match
        if ($newPassword != $newPasswordConf) {
            throw new Exception("The two passwords don't match", Exception::WARNING);
        }

        $player->setPassword(md5($newPassword));
        if (!$player->save()) {
            throw new Exception('Failed to change password');
        }

        $this->result()->setInfo('Password changed');
    }

    /**
     * Buy item at MArcomage shop (currently in settings section)
     * @throws Exception
     */
    protected function buyItem()
    {
        $request = $this->request();
        $player = $this->getCurrentPlayer();

        $this->result()->setCurrent('Settings');

        $this->assertParamsNonEmpty(['selected_item']);

        $this->service()->player()->buyItem($player->getUsername(), $request['selected_item']);

        $this->result()->setInfo('Shop item (' . Rename::underscoreToTextName($request['selected_item']) . ') has been successfully purchased');
    }

    /**
     * Skip tutorial
     * @throws Exception
     */
    protected function skipTutorial()
    {
        $player = $this->getCurrentPlayer();

        $this->result()->setCurrent('Settings');

        $score = $this->dbEntity()->score()->getScoreAsserted($player->getUsername());

        // allow skip tutorial only if player has appropriate level
        if ($score->getLevel() >= PlayerModel::TUTORIAL_END) {
            throw new Exception('Tutorial has already ended', Exception::WARNING);
        }

        // fast forward player to level 10
        while ($score->getLevel() < PlayerModel::TUTORIAL_END) {
            $score->addExp($score->nextLevel() - $score->getExp());
        }

        if (!$score->save()) {
            throw new Exception('Failed to save score');
        }

        $this->result()->setInfo('Tutorial has been skipped');
    }
}
