<?php
/**
 * GameUtil - general game utilities
 */

namespace Service;

use ArcomageException as Exception;
use Db\Model\Game as GameModel;
use Db\Model\Message as MessageModel;
use Db\Model\Player as PlayerModel;
use Util\Date;
use Util\Input;

class GameUtil extends ServiceAbstract
{
    /**
     * Save game note
     * @param string $playerName
     * @param GameModel $game
     * @param string $note
     * @throws Exception
     */
    public function saveNote($playerName, GameModel $game, $note)
    {
        if (mb_strlen($note) > MessageModel::MESSAGE_LENGTH) {
            throw new Exception('Game note is too long', Exception::WARNING);
        }

        // update message
        $game->setNote($playerName, $note);
        if (!$game->save()) {
            throw new Exception('Failed to save game note');
        }
    }

    /**
     * Send chat message within a game
     * @param string $playerName
     * @param GameModel $game
     * @param string $message
     * @throws Exception
     */
    public function sendChatMessage($playerName, GameModel $game, $message)
    {
        $dbEntityChat = $this->dbEntity()->chat();

        // remove unnecessary whitespace
        $message = trim($message);

        // ignore empty message
        if ($message == '') {
//            throw new Exception("You can't send empty chat messages", Exception::WARNING);
            return;
        }

        // validate message length
        if (mb_strlen($message) > MessageModel::CHAT_LENGTH) {
            throw new Exception('Chat message is too long', Exception::WARNING);
        }

        // check if chat is allowed (can't chat with a computer player)
        if ($game->checkGameMode('AIMode')) {
            throw new Exception('Chat is not allowed in AI mode', Exception::WARNING);
        }

        $result = $dbEntityChat->saveChatMessage($game->getGameId(), $message, $playerName);
        if ($result->isErrorOrNoEffect()) {
            throw new Exception('Failed to send chat message');
        }
    }

    /**
     * Count free game slots for specified player
     * @param string $playerName
     * @param bool [$omitChallenges]
     * @throws Exception
     * @return int
     */
    public function countFreeSlots($playerName, $omitChallenges = false)
    {
        $dbEntityGame = $this->dbEntity()->game();

        $result = $dbEntityGame->countActiveGames($playerName, $omitChallenges);

        // determine number of free slots
        if ($result->isErrorOrNoEffect()) {
            throw new Exception('Failed to count empty game slots');
        }

        $gameCount = $result[0]['count'];

        $score = $this->dbEntity()->score()->getScoreAsserted($playerName);

        return max(0, GameModel::MAX_GAMES + $score->getGameSlots() - $gameCount);
    }

    /**
     * Generate outcome message based on game's end type
     * @param string $endType game end type
     * @return string message
     */
    public static function outcomeMessage($endType)
    {
        return Input::defaultValue([
            'Surrender' => 'Opponent has surrendered',
            'Abort' => 'Aborted',
            'Abandon' => 'Opponent has fled the battlefield',
            'Destruction' => 'Tower destruction victory',
            'Draw' => 'Draw',
            'Construction' => 'Tower building victory',
            'Resource' => 'Resource accumulation victory',
            'Timeout' => 'Timeout victory',
            'Pending' => 'Pending',
        ], $endType);
    }

    /**
     * Format game attributes and their changes into a text message
     * @param array $gameAttributes game attributes and their changes
     * @param bool [$shortFormat] short format
     * @return string information message
     */
    public static function formatPreview(array $gameAttributes, $shortFormat = false)
    {
        $cardName = $gameAttributes['card']['name'];
        $cardMode = $gameAttributes['card']['mode'];

        $myName = $gameAttributes['player']['name'];
        $myAttr = $gameAttributes['player']['attributes'];
        $myChanges = $gameAttributes['player']['changes'];
        $myTokens = $gameAttributes['player']['tokens'];
        $myTokenChanges = $gameAttributes['player']['tokens_changes'];

        $hisName = $gameAttributes['opponent']['name'];
        $hisAttr = $gameAttributes['opponent']['attributes'];
        $hisChanges = $gameAttributes['opponent']['changes'];
        $hisTokens = $gameAttributes['opponent']['tokens'];
        $hisTokensChanges = $gameAttributes['opponent']['tokens_changes'];

        // create result text message
        $message = array();

        // card name and card mode
        if (!$shortFormat) {
            $message[] = $cardName . (($cardMode > 0) ? ' (mode ' . $cardMode . ')' : '');
        }

        $myPart = $hisPart = array();
        // game attributes
        foreach ($myAttr as $attrName => $attrValue) {
            if ($myChanges[$attrName] != 0) {
                $myPart[] = $attrName . ': ' . $attrValue . ' ('
                    . (($myChanges[$attrName] > 0) ? '+' : '') . $myChanges[$attrName] . ')';
            }
        }

        // tokens
        foreach ($myTokens as $tokenName => $tokenValue) {
            if ($myTokenChanges[$tokenName] != 0) {
                $myPart[] = $tokenName . ': ' . $tokenValue . ' ('
                    . (($myTokenChanges[$tokenName] > 0) ? '+' : '') . $myTokenChanges[$tokenName] . ')';
            }
        }

        // player data
        if (count($myPart) > 0) {
            $message[] = "\n" . $myName . "\n";
        }
        elseif (!$shortFormat) {
            $message[] = "\n" . $myName . "\n";
            $myPart[] = 'no changes';
        }

        $message = array_merge($message, $myPart);

        // game attributes
        foreach ($hisAttr as $attrName => $attrValue) {
            if ($hisChanges[$attrName] != 0) {
                $hisPart[] = $attrName . ': ' . $attrValue . ' ('
                    . (($hisChanges[$attrName] > 0) ? '+' : '') . $hisChanges[$attrName] . ')';
            }
        }

        // tokens
        foreach ($hisTokens as $tokenName => $tokenValue) {
            if ($hisTokensChanges[$tokenName] != 0) {
                $hisPart[] = $tokenName . ': ' . $tokenValue . ' ('
                    . (($hisTokensChanges[$tokenName] > 0) ? '+' : '') . $hisTokensChanges[$tokenName] . ')';
            }
        }

        // opponent data
        if (count($hisPart) > 0) {
            $message[] = "\n" . $hisName . "\n";
        }
        elseif (!$shortFormat) {
            $message[] = "\n" . $hisName . "\n";
            $hisPart[] = 'no changes';
        }

        $message = array_merge($message, $hisPart);

        return implode("\n", $message);
    }

    /**
     * Find next game id
     * @param $playerName
     * @param $currentGameId
     * @throws Exception
     * @return int
     */
    public function findNextGame($playerName, $currentGameId)
    {
        $dbEntityGame = $this->dbEntity()->game();
        $dbEntityPlayer = $this->dbEntity()->player();

        // determine active games
        $result = $dbEntityGame->nextGameList($playerName);
        if ($result->isError()) {
            throw new Exception('Failed to load next games list');
        }

        $list = array();
        foreach ($result->data() as $data) {
            $list[$data['game_id']] = $data['Opponent'];
        }

        // check if there is an active game
        if (count($list) == 0) {
            throw new Exception('There are no game where your turn is', Exception::WARNING);
        }

        // split games into active and inactive based on opponent's activity
        $active = $inactive = array();
        foreach ($list as $gameId => $opponentName) {
            // case 1: AI player
            if ($opponentName == PlayerModel::SYSTEM_NAME) {
                $opponent = $dbEntityPlayer->getGuest();
            }
            // case 2: standard player
            else {
                $opponent = $dbEntityPlayer->getPlayerAsserted($opponentName);
            }

            // separate games into two groups based on opponent activity
            $inactivity = time() - Date::strToTime($opponent->getLastActivity());

            // case 1: active player
            if ($inactivity < 10 * Date::MINUTE) {
                $active[] = $gameId;
            }
            // case 2: inactive player
            else {
                $inactive[] = $gameId;
            }
        }

        // merge active and inactive games in this order
        $list = array_merge($active, $inactive);

        // find next game in the list base on current game
        $gameId = $list[0];
        foreach ($list as $i => $currentGame) {
            if ($currentGameId == $currentGame) {
                // warp around
                $gameId = $list[($i + 1) % count($list)];
                break;
            }
        }

        $game = $this->dbEntity()->game()->getGameAsserted($gameId);

        // check if this user is allowed to view this game
        if ($playerName != $game->getPlayer1() && $playerName != $game->getPlayer2()) {
            throw new Exception('Access denied', Exception::WARNING);
        }

        // check if the game is a game in progress (and not a challenge)
        if ($game->getState() == 'waiting') {
            throw new Exception('Opponent did not accept the challenge yet', Exception::WARNING);
        }

        // disable re-visiting
        if (($playerName == $game->getPlayer1() && $game->getState() == 'P1 over')
            || ($playerName == $game->getPlayer2() && $game->getState() == 'P2 over')) {
            throw new Exception('Game is already over', Exception::WARNING);
        }

        return $game->getGameId();
    }

    /**
     * Save game and replay data
     * @param GameModel $game
     * @throws Exception
     */
    public function saveGameWithReplay(GameModel $game)
    {
        $db = $this->getDb();

        // attempt to load replay (replay is optional)
        $replay = $this->dbEntity()->replay()->getReplay($game->getGameId());

        $db->beginTransaction();

        // save game data
        if (!$game->save()) {
            $db->rollBack();
            throw new Exception('Failed to save game data');
        }

        // update replay data (optional)
        if (!empty($replay)) {
            $replay->update($game);

            if (!$replay->save()) {
                $db->rollBack();
                throw new Exception('Failed to save replay data');
            }
        }

        $db->commit();
    }

    /**
     * Save game data and create matching replay
     * @param GameModel $game
     * @throws Exception
     */
    public function saveGameCreateReplay(GameModel $game)
    {
        $db = $this->getDb();
        $dbEntityReplay = $this->dbEntity()->replay();

        $db->beginTransaction();

        // update game data
        if (!$game->save()) {
            $db->rollBack();
            throw new Exception('Game start failed');
        }

        // create matching replay
        $replay = $dbEntityReplay->createReplay($game);
        if (!$replay->save()) {
            $db->rollBack();
            throw new Exception('Failed to create game replay');
        }

        $db->commit();
    }

    /**
     * @param \CGamePlayerData $data
     * @return array
     */
    public function formatHandData(\CGamePlayerData $data)
    {
        $defEntityCard = $this->defEntity()->card();

        $handData = $defEntityCard->getData($data->Hand);

        // process card data
        $result = array();
        foreach ($handData as $i => $card) {
            $result[$i] = [
                'card_data' => $card,
                'new_card' => (isset($data->NewCards[$i])) ? 'yes' : 'no',
                'revealed' => (isset($data->Revealed[$i])) ? 'yes' : 'no',
            ];
        }

        return $result;
    }

    /**
     * @param \CGamePlayerData $data
     * @return array
     */
    public function formatLastCard(\CGamePlayerData $data)
    {
        $defEntityCard = $this->defEntity()->card();

        $cardList = $defEntityCard->getData($data->LastCard);

        $result = array();
        foreach ($cardList as $i => $card) {
            $result[$i] = [
                'card_data' => $card,
                'card_action' => $data->LastAction[$i],
                'card_mode' => $data->LastMode[$i],
                'card_position' => $i,
            ];
        }

        return $result;
    }

    /**
     * @param \CGamePlayerData $data
     * @return array
     */
    public function formatTokens(\CGamePlayerData $data)
    {
        $result = array();
        foreach ($data->TokenNames as $i => $value) {
            $result[$i] = [
                'name' => $data->TokenNames[$i],
                'value' => $data->TokenValues[$i],
                'change' => $data->TokenChanges[$i],
            ];
        }

        return $result;
    }

    /**
     * @param \CGamePlayerData $data
     * @return array
     */
    public function formatChanges(\CGamePlayerData $data)
    {
        $result = [
            'Quarry' => '', 'Magic' => '', 'Dungeons' => '',
            'Bricks' => '', 'Gems' => '', 'Recruits' => '',
            'Tower' => '', 'Wall' => ''
        ];

        foreach ($result as $attribute => $change) {
            $result[strtolower($attribute)] = (($data->Changes[$attribute] > 0) ? '+' : '') . $data->Changes[$attribute];
        }

        return $result;
    }
}
