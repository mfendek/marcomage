<?php
/**
 * GameCheat - game cheats
 */

namespace Service;

use ArcomageException as Exception;
use Db\Model\Game as GameModel;
use Db\Model\Player as PlayerModel;

class GameCheat extends ServiceAbstract
{
    /**
     * Game cheat - place a card on specified position
     * @param string $playerName
     * @param GameModel $game
     * @param string $targetData
     * @param int $cardId
     * @param int $cardPos
     * @throws Exception
     */
    public function cheatPutCard($playerName, GameModel $game, $targetData, $cardId, $cardPos)
    {
        $defEntityCard = $this->defEntity()->card();

        // validate target player
        if (!in_array($targetData, ['mine', 'his'])) {
            throw new Exception('Invalid target player', Exception::WARNING);
        }

        // validate card id
        if (!is_numeric($cardId) || $cardId <= 0) {
            throw new Exception('Invalid card id', Exception::WARNING);
        }

        // validate card itself
        $defEntityCard->getCard($cardId);

        // validate card position
        if (!is_numeric($cardPos) || !in_array($cardPos, [1, 2, 3, 4, 5, 6, 7, 8])) {
            throw new Exception('Invalid card position', Exception::WARNING);
        }

        // check if AI move is allowed
        if (!$game->checkGameMode('AIMode')) {
            throw new Exception('AI move not allowed!', Exception::WARNING);
        }

        // only allow AI move if the game is still on
        if ($game->getState() != 'in progress') {
            throw new Exception('Action not allowed!', Exception::WARNING);
        }

        // only allow action in case of standard AI
        if ($game->getAiName() != '') {
            throw new Exception('Action only allowed in game vs standard AI', Exception::WARNING);
        }

        // fetch player's level
        $score = $this->dbEntity()->score()->getScoreAsserted($playerName);

        $playerLevel = $score->getLevel();

        // validate player's level
        if ($playerLevel < PlayerModel::TUTORIAL_END) {
            throw new Exception('Action not allowed while in tutorial', Exception::WARNING);
        }

        // update game data
        $gameData = $game->getData();
        $dataSelector = ($targetData == 'mine') ? 1 : 2;
        $playerData = $gameData[$dataSelector];
        $handData = $playerData->Hand;
        $handData[$cardPos] = $cardId;
        $playerData->Hand = $handData;

        $game->setData($gameData);
        if (!$game->save()) {
            throw new Exception('Failed to put card into a game');
        }
    }

    /**
     * Game cheat - change game attribute
     * @param string $playerName
     * @param GameModel $game
     * @param string $targetData
     * @param string $attribute
     * @param int $value
     * @throws Exception
     */
    public function cheatChangeAttribute($playerName, GameModel $game, $targetData, $attribute, $value)
    {
        // validate target player
        if (!in_array($targetData, ['mine', 'his'])) {
            throw new Exception('Invalid target player', Exception::WARNING);
        }

        // validate target change
        if (!in_array($attribute, [
                'Quarry', 'Magic', 'Dungeons', 'Bricks', 'Gems', 'Recruits', 'Tower', 'Wall', 'Facilities', 'Stock'
            ])) {
            throw new Exception('Invalid target change', Exception::WARNING);
        }

        // validate card position
        if (!is_numeric($value) || $value < 0 || $value > 1000) {
            throw new Exception('Invalid target value', Exception::WARNING);
        }

        $oldValue = $value;
        $value = (int)$value;

        // only allow integer values
        if ($value != $oldValue) {
            throw new Exception('Target value has to be integer', Exception::WARNING);
        }

        // check if AI move is allowed
        if (!$game->checkGameMode('AIMode')) {
            throw new Exception('AI move not allowed!', Exception::WARNING);
        }

        // only allow AI move if the game is still on
        if ($game->getState() != 'in progress') {
            throw new Exception('Action not allowed!', Exception::WARNING);
        }

        // only allow action in case of standard AI
        if ($game->getAiName() != '') {
            throw new Exception('Action only allowed in game vs standard AI', Exception::WARNING);
        }

        // fetch player's level
        $score = $this->dbEntity()->score()->getScoreAsserted($playerName);

        $playerLevel = $score->getLevel();

        // validate player's level
        if ($playerLevel < PlayerModel::TUTORIAL_END) {
            throw new Exception('Action not allowed while in tutorial', Exception::WARNING);
        }

        // update game data
        $gameData = $game->getData();
        $dataSelector = ($targetData == 'mine') ? 1 : 2;

        /* @var $playerData \CGamePlayerData */
        $playerData = $gameData[$dataSelector];

        // case 1: virtual attribute - facilities
        if ($attribute == 'Facilities') {
            $playerData->Quarry = $value;
            $playerData->Magic = $value;
            $playerData->Dungeons = $value;
        }
        // case 2: virtual attribute - stock
        elseif ($attribute == 'Stock') {
            $playerData->Bricks = $value;
            $playerData->Gems = $value;
            $playerData->Recruits = $value;
        }
        // case 3: real attributes
        else {
            $playerData->$attribute = $value;
        }
        $playerData->applyGameLimits(($game->checkGameMode('LongMode')) ? 'long' : 'normal');

        $game->setData($gameData);
        if (!$game->save()) {
            throw new Exception('Failed to change game attribute');
        }
    }

    /**
     * Game cheat - change game mode to hidden or non-hidden
     * @param string $playerName
     * @param GameModel $game
     * @throws Exception
     */
    public function cheatChangeGameMode($playerName, GameModel $game)
    {
        // check if AI move is allowed
        if (!$game->checkGameMode('AIMode')) {
            throw new Exception('AI move not allowed!', Exception::WARNING);
        }

        // only allow AI move if the game is still on
        if ($game->getState() != 'in progress') {
            throw new Exception('Action not allowed!', Exception::WARNING);
        }

        // only allow action in case of standard AI
        if ($game->getAiName() != '') {
            throw new Exception('Action only allowed in game vs standard AI', Exception::WARNING);
        }

        // fetch player's level
        $score = $this->dbEntity()->score()->getScoreAsserted($playerName);

        $playerLevel = $score->getLevel();

        // validate player's level
        if ($playerLevel < PlayerModel::TUTORIAL_END) {
            throw new Exception('Action not allowed while in tutorial', Exception::WARNING);
        }

        // case 1: game is in hidden cards mode
        if ($game->checkGameMode('HiddenCards')) {
            // remove hidden cards mode
            $game->setGameModes(array_diff($game->getGameModes(), ['HiddenCards']));
        }
        // case 2: game is in non-hidden cards mode
        else {
            // add hidden cards mode
            $game->setGameModes(array_merge($game->getGameModes(), ['HiddenCards']));
        }

        if (!$game->save()) {
            throw new Exception('Failed to change game mode');
        }
    }
}
