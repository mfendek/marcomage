<?php
/**
 * Player - players related controller
 */

namespace Controller;

use ArcomageException as Exception;
use Util\Encode;

class Player extends ControllerAbstract
{
    /**
     * Change user access rights
     * @throws Exception
     */
    protected function changeAccess()
    {
        $request = $this->request();

        $this->result()->setCurrent('Players');

        $opponentName = Encode::postDecode($request['change_access']);

        $opponent = $this->dbEntity()->player()->getPlayerAsserted($opponentName);

        $this->result()
            ->changeRequest('Profile', $opponentName)
            ->setCurrent('Players_details');

        // check access rights
        if (!$this->checkAccess('change_rights')) {
            throw new Exception('Access denied', Exception::WARNING);
        }

        $this->assertParamsNonEmpty(['new_access']);
        $newAccess = $request['new_access'];

        // validate access type
        if (!in_array($newAccess, ['user', 'moderator', 'supervisor', 'admin', 'squashed', 'limited', 'banned'])) {
            throw new Exception('Invalid access type', Exception::WARNING);
        }

        // change access rights
        $opponent->setUserType($newAccess);
        if (!$opponent->save()) {
            throw new Exception('Failed to change access rights');
        }

        $this->result()->setInfo('Access rights changed');
    }

    /**
     * Rename player
     * @throws Exception
     */
    protected function renamePlayer()
    {
        $request = $this->request();

        $this->result()->setCurrent('Players');

        $playerName = Encode::postDecode($request['rename_player']);

        // check if player exists
        $this->dbEntity()->player()->getPlayerAsserted($playerName);

        $this->result()
            ->changeRequest('Profile', $playerName)
            ->setCurrent('Players_details');

        // check access rights
        if (!$this->checkAccess('change_rights')) {
            throw new Exception('Access denied', Exception::WARNING);
        }

        $this->assertParamsNonEmpty(['new_username']);
        $newName = trim($request['new_username']);

        // rename player data
        $this->service()->player()->renamePlayer($playerName, $newName);

        $this->result()
            ->changeRequest('Profile', $newName)
            ->setInfo('Player successfully renamed');
    }

    /**
     * Delete player
     * @throws Exception
     */
    protected function deletePlayer()
    {
        $request = $this->request();

        $this->result()->setCurrent('Players');

        $playerName = Encode::postDecode($request['delete_player']);

        // validate player
        $this->dbEntity()->player()->getPlayerAsserted($playerName);

        $this->result()
            ->changeRequest('Profile', $playerName)
            ->setCurrent('Players_details');

        // check access rights
        if (!$this->checkAccess('change_rights')) {
            throw new Exception('Access denied', Exception::WARNING);
        }

        // delete player data
        $this->service()->player()->deletePlayer($playerName);

        $this->result()
            ->setInfo('Player successfully deleted')
            ->setCurrent('Players');
    }

    /**
     * Reset exp
     * @throws Exception
     */
    protected function resetExp()
    {
        $request = $this->request();

        $this->result()->setCurrent('Players');

        $playerName = Encode::postDecode($request['reset_exp']);

        // check if player exists
        $this->dbEntity()->player()->getPlayerAsserted($playerName);

        $this->result()
            ->changeRequest('Profile', $playerName)
            ->setCurrent('Players_details');

        // check access rights
        if (!$this->checkAccess('reset_exp')) {
            throw new Exception('Access denied', Exception::WARNING);
        }

        $this->service()->player()->resetExp($playerName);

        $this->result()->setInfo('Exp reset');
    }

    /**
     * Reset some player's avatar
     * @throws Exception
     */
    protected function resetAvatarRemote()
    {
        $request = $this->request();
        $config = $this->getDic()->config();

        $this->result()->setCurrent('Players');

        $opponentName = Encode::postDecode($request['reset_avatar_remote']);

        // check if player exists
        $this->dbEntity()->player()->getPlayerAsserted($opponentName);

        $this->result()
            ->changeRequest('Profile', $opponentName)
            ->setCurrent('Players_details');

        // check access rights
        if (!$this->checkAccess('change_all_avatar')) {
            throw new Exception('Access denied', Exception::WARNING);
        }

        $setting = $this->dbEntity()->setting()->getSettingAsserted($opponentName);

        // delete avatar picture
        $formerName = $setting->getSetting('avatar');
        $formerPath = $config['upload_dir']['avatar'] . $formerName;

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
     * Export some player's deck
     * @throws Exception
     */
    protected function exportDeckRemote()
    {
        $request = $this->request();

        $this->result()->setCurrent('Players');

        $opponentName = Encode::postDecode($request['export_deck_remote']);

        // check if player exists
        $this->dbEntity()->player()->getPlayerAsserted($opponentName);

        $this->result()
            ->changeRequest('Profile', $opponentName)
            ->setCurrent('Players_details');

        // check access rights
        if (!$this->checkAccess('export_deck')) {
            throw new Exception('Access denied', Exception::WARNING);
        }

        $this->assertParamsNonEmpty(['exported_deck']);

        $deckId = $request['exported_deck'];
        $deck = $this->dbEntity()->deck()->getDeckAsserted($deckId);

        $file = $deck->toCSV();

        $contentType = 'text/csv';
        $fileName = preg_replace("/[^a-zA-Z0-9_-]/i", "_", $deck->getDeckName()) . '.csv';
        $fileLength = mb_strlen($file);

        $this->result()->setRawOutput($file, [
            'Content-Type: ' . $contentType . '',
            'Content-Disposition: attachment; filename="' . $fileName . '"',
            'Content-Length: ' . $fileLength,
        ]);
    }

    /**
     * Add gold
     * @throws Exception
     */
    protected function addGold()
    {
        $request = $this->request();

        $this->result()->setCurrent('Players');

        $opponentName = Encode::postDecode($request['add_gold']);

        // check if player exists
        $this->dbEntity()->player()->getPlayerAsserted($opponentName);

        $this->result()
            ->changeRequest('Profile', $opponentName)
            ->setCurrent('Players_details');

        // check access rights
        if (!$this->checkAccess('reset_exp')) {
            throw new Exception('Access denied', Exception::WARNING);
        }

        // validate gold amount
        $this->assertParamsNonEmpty(['gold_amount']);
        if (trim($request['gold_amount']) == '' || !is_numeric($request['gold_amount'])) {
            throw new Exception('Invalid gold amount', Exception::WARNING);
        }

        $score = $this->dbEntity()->score()->getScoreAsserted($opponentName);

        // add specified gold amount
        $score->setGold($score->getGold() + $request['gold_amount']);
        if (!$score->save()) {
            throw new Exception('Failed to add gold');
        }

        $this->result()->setInfo('Gold successfully added');
    }

    /**
     * Reset password in case user forgot his current password
     * @throws Exception
     */
    protected function resetPassword()
    {
        $request = $this->request();

        $this->result()->setCurrent('Players');

        $opponentName = Encode::postDecode($request['reset_password']);

        // check if player exists
        $opponent = $this->dbEntity()->player()->getPlayerAsserted($opponentName);

        $this->result()
            ->changeRequest('Profile', $opponentName)
            ->setCurrent('Players_details');

        // check access rights
        if (!$this->checkAccess('change_rights')) {
            throw new Exception('Access denied', Exception::WARNING);
        }

        // reset password to be the same as player's name
        $opponent->setPassword(md5($opponent->getUsername()));
        if (!$opponent->save()) {
            throw new Exception('Failed to reset password');
        }

        $this->result()->setInfo('Password reset');
    }

    /**
     * Use player filter in players list
     */
    protected function playersApplyFilters()
    {
        $this->result()
            ->changeRequest('players_current_page', 0)
            ->setCurrent('Players');
    }

    /**
     * Select page (previous and next button)
     */
    protected function playersSelectPage()
    {
        $request = $this->request();

        $this->result()
            ->changeRequest('players_current_page', $request['players_select_page'])
            ->setCurrent('Players');
    }
}
