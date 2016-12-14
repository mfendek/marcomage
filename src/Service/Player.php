<?php
/**
 * Player
 */

namespace Service;

use ArcomageException as Exception;
use Db\Model\Deck as DeckModel;
use Db\Model\Game as GameModel;
use Util\Date;
use Util\Ip;

class Player extends ServiceAbstract
{
    /**
     * server-side session expiry time (in seconds)
     */
    const SESSION_TIMEOUT = 7 * 24 * 60 * 60;

    /**
     * client-side session cookie expiry time (in seconds)
     */
    const COOKIE_TIMEOUT = 7 * 24 * 60 * 60;

    /**
     * Validate session
     * @param \Db\Model\Player $player
     * @param int $sessionId
     * @throws Exception
     */
    public function validateSession($player, $sessionId)
    {
        // validate session id
        if (!is_numeric($sessionId) || $sessionId <= 0) {
            throw new Exception('Invalid session id', Exception::WARNING);
        }

        // check if session id matches
        if ($player->getSessionId() != $sessionId) {
            throw new Exception('Session id does not match', Exception::WARNING);
        }

        if (time() - Date::strToTime($player->getLastQuery()) > self::SESSION_TIMEOUT) {
            throw new Exception('Session has expired', Exception::WARNING);
        }
    }

    /**
     * Begin new session
     * @param \Db\Model\Player $player
     * @param string $cookies cookies option
     * @param int $sessionId [$session_id]
     * @throws Exception
     * @return array
     */
    public function beginSession($player, $cookies, $sessionId = 0)
    {
        // key => [value, timeout]
        $newCookies = array();

        // test if a new session is needed
        if ($sessionId == 0) {
            // generate and store a new uninitialized session for the user
            $sessionId = mt_rand(1, pow(2, 31) - 1);

            $player->setSessionId($sessionId);
            $player->setNotification($player->getLastQuery());
        }

        $now = time();

        // store current `Last IP` and `Last Query`, refresh cookies
        $player->setLastIp(Ip::getIp());
        $player->setLastQuery(Date::timeToStr());

        // try even if not sure
        if ($cookies == 'yes' || $cookies == 'maybe') {
            $timeout = $now + self::COOKIE_TIMEOUT;
            $newCookies['username'] = [$player->getUsername(), $timeout];
            $newCookies['session_id'] = [$player->getSessionId(), $timeout];
        }

        // (yes -> 1, maybe -> 0, no -> 0)
        $player->cookies = ($cookies == 'yes');

        // save player data
        if (!$player->save()) {
            throw new Exception('Failed to save initial player data');
        }

        return $newCookies;
    }

    /**
     * @param string $playerName
     * @param string $password
     * @throws Exception
     */
    public function register($playerName, $password)
    {
        $dbEntityPlayer = $this->dbEntity()->player();
        $dbEntityDeck = $this->dbEntity()->deck();
        $dbEntityMessage = $this->dbEntity()->message();
        $dbEntitySetting = $this->dbEntity()->setting();
        $dbEntityScore = $this->dbEntity()->score();

        $db = $this->getDb();

        $db->beginTransaction();

        // create new player record
        $player = $dbEntityPlayer->createPlayer(trim($playerName));
        $player
            ->setPassword(md5($password))
            ->setLastIp(Ip::getIp())
            ->setLastQuery(Date::timeToStr());

        if (!$player->save()) {
            $db->rollBack();
            throw new Exception('Failed to create new player record');
        }

        // create new score record
        $score = $dbEntityScore->createScore($player->getUsername());
        if (!$score->save()) {
            $db->rollBack();
            throw new Exception('Failed to create new score record');
        }

        // create starter decks
        $starterDecks = $this->service()->deck()->starterDecks();

        /* @var DeckModel $starterDeck */
        foreach ($starterDecks as $deckName => $starterDeck) {
            $deck = $dbEntityDeck->createDeck($player->getUsername(), $deckName);
            $deck->setData($starterDeck->getData());

            if (!$deck->save()) {
                $db->rollBack();
                throw new Exception('Failed to save new starter deck');
            }
        }

        // fill remaining decks slots with empty decks
        $remainingDecksSlots = DeckModel::DECK_SLOTS - count($starterDecks);
        for ($i = 1; $i <= $remainingDecksSlots; $i++) {
            $deck = $dbEntityDeck->createDeck($player->getUsername(), 'deck ' . $i);
            if (!$deck->save()) {
                $db->rollBack();
                throw new Exception('Failed to create new empty deck');
            }
        }

        // create settings record
        $setting = $dbEntitySetting->createSetting($player->getUsername());
        if (!$setting->save()) {
            $db->rollBack();
            throw new Exception('Failed to create new settings record');
        }

        // send welcome message
        $message = $dbEntityMessage->welcomeMessage($player->getUsername());
        if (!$message->save()) {
            $db->rollBack();
            throw new Exception('Failed to send welcome message');
        }

        // registration was a success
        $db->commit();
    }

    /**
     * Rename player in all available data
     * @param string $playerName
     * @param string $newName
     * @throws Exception
     */
    public function renamePlayer($playerName, $newName)
    {
        $db = $this->getDb();
        $dbEntityChat = $this->dbEntity()->chat();
        $dbEntityConcept = $this->dbEntity()->concept();
        $dbEntityDeck = $this->dbEntity()->deck();
        $dbEntityGame = $this->dbEntity()->game();
        $dbEntityMessage = $this->dbEntity()->message();
        $dbEntityForumPost = $this->dbEntity()->forumPost();
        $dbEntityPlayer = $this->dbEntity()->player();
        $dbEntityReplay = $this->dbEntity()->replay();
        $dbEntityScore = $this->dbEntity()->score();
        $dbEntitySetting = $this->dbEntity()->setting();
        $dbEntityForumThread = $this->dbEntity()->forumThread();

        // validate new name
        if ($newName == '' || $newName == $playerName
            || strtolower($newName) == strtolower(\Db\Model\Player::SYSTEM_NAME)) {
            throw new Exception('Invalid new name', Exception::WARNING);
        }

        // validate new name length
        if (mb_strlen($newName) > 20) {
            throw new Exception('New name is too long', Exception::WARNING);
        }

        $player = $dbEntityPlayer->getPlayerAsserted($playerName);

        // check if such player already exists
        $otherPlayer = $dbEntityPlayer->getPlayer($newName);
        if (!empty($otherPlayer)) {
            throw new Exception('Such player already exists', Exception::WARNING);
        }

        $db->beginTransaction();

        // chats
        $result = $dbEntityChat->renameChats($playerName, $newName);
        if ($result->isError()) {
            $db->rollBack();
            throw new Exception('Failed to rename player in chats table');
        }

        // concepts
        $result = $dbEntityConcept->renameConcepts($playerName, $newName);
        if ($result->isError()) {
            $db->rollBack();
            throw new Exception('Failed to rename player in concepts table');
        }

        // decks
        $result = $dbEntityDeck->renameDecks($playerName, $newName);
        if ($result->isErrorOrNoEffect()) {
            $db->rollBack();
            throw new Exception('Failed to rename player in decks table');
        }

        // forum posts
        $result = $dbEntityForumPost->renamePosts($playerName, $newName);
        if ($result->isError()) {
            $db->rollBack();
            throw new Exception('Failed to rename player in forum posts table');
        }

        // forum threads - thread author
        $result = $dbEntityForumThread->renameAuthors($playerName, $newName);
        if ($result->isError()) {
            $db->rollBack();
            throw new Exception('Failed to rename player in forum threads (author) table');
        }

        // forum threads - thread author cached
        $result = $dbEntityForumThread->renameLastAuthors($playerName, $newName);
        if ($result->isError()) {
            $db->rollBack();
            throw new Exception('Failed to rename player in forum threads (last author) table');
        }

        // games - player 1
        $result = $dbEntityGame->renamePlayer1($playerName, $newName);
        if ($result->isError()) {
            $db->rollBack();
            throw new Exception('Failed to rename player in games (player 1) table');
        }

        // games - player 2
        $result = $dbEntityGame->renamePlayer2($playerName, $newName);
        if ($result->isError()) {
            $db->rollBack();
            throw new Exception('Failed to rename player in games (player 2) table');
        }

        // games - current
        $result = $dbEntityGame->renameCurrent($playerName, $newName);
        if ($result->isError()) {
            $db->rollBack();
            throw new Exception('Failed to rename player in games (current) table');
        }

        // games - winner
        $result = $dbEntityGame->renameWinner($playerName, $newName);
        if ($result->isError()) {
            $db->rollBack();
            throw new Exception('Failed to rename player in games (winner) table');
        }

        // games - surrender
        $result = $dbEntityGame->renameSurrender($playerName, $newName);
        if ($result->isError()) {
            $db->rollBack();
            throw new Exception('Failed to rename player in games (surrender) table');
        }

        // player
        $result = $dbEntityPlayer->renamePlayer($playerName, $newName);
        if ($result->isErrorOrNoEffect()) {
            $db->rollBack();
            throw new Exception('Failed to rename player in logins table');
        }

        // messages - author
        $result = $dbEntityMessage->renameAuthor($playerName, $newName);
        if ($result->isError()) {
            $db->rollBack();
            throw new Exception('Failed to rename player in messages (author) table');
        }

        // messages - recipient
        $result = $dbEntityMessage->renameRecipient($playerName, $newName);
        if ($result->isError()) {
            $db->rollBack();
            throw new Exception('Failed to rename player in messages (recipient) table');
        }

        // replays - player 1
        $result = $dbEntityReplay->renamePlayer1($playerName, $newName);
        if ($result->isError()) {
            $db->rollBack();
            throw new Exception('Failed to rename player in replays (player 1) table');
        }

        // replays - player 2
        $result = $dbEntityReplay->renamePlayer2($playerName, $newName);
        if ($result->isError()) {
            $db->rollBack();
            throw new Exception('Failed to rename player in replays (player 2) table');
        }

        // replays - winner
        $result = $dbEntityReplay->renameWinner($playerName, $newName);
        if ($result->isError()) {
            $db->rollBack();
            throw new Exception('Failed to rename player in replays (winner) table');
        }

        // scores
        $result = $dbEntityScore->renameScores($playerName, $newName);
        if ($result->isErrorOrNoEffect()) {
            $db->rollBack();
            throw new Exception('Failed to rename player in scores table');
        }

        // settings
        $result = $dbEntitySetting->renameSettings($playerName, $newName);
        if ($result->isErrorOrNoEffect()) {
            $db->rollBack();
            throw new Exception('Failed to rename player in settings table');
        }

        $db->commit();

        // remove old player model from cache
        $dbEntityPlayer->destroyModel($player);
    }

    /**
     * @param string $playerName
     * @throws Exception
     */
    public function deletePlayer($playerName)
    {
        $db = $this->getDb();
        $dbEntityChat = $this->dbEntity()->chat();
        $dbEntityDeck = $this->dbEntity()->deck();
        $dbEntityGame = $this->dbEntity()->game();
        $dbEntityMessage = $this->dbEntity()->message();
        $dbEntityReplay = $this->dbEntity()->replay();

        $player = $this->dbEntity()->player()->getPlayerAsserted($playerName);

        // load necessary player data
        $score = $this->dbEntity()->score()->getScoreAsserted($playerName);
        $setting = $this->dbEntity()->setting()->getSettingAsserted($playerName);

        // list game ids
        $result = $dbEntityGame->listGameIds($playerName);
        if ($result->isError()) {
            throw new Exception('Failed to list game ids for player');
        }

        $games = array();
        foreach ($result->data() as $data) {
            $games[] = $data['GameID'];
        }

        $db->beginTransaction();

        $player->markDeleted();
        if (!$player->save()) {
            $db->rollBack();
            throw new Exception('Failed to delete player');
        }

        // score
        $score->markDeleted();
        if (!$score->save()) {
            $db->rollBack();
            throw new Exception('Failed to delete score');
        }

        // decks
        $result = $dbEntityDeck->deleteDecks($playerName);
        if ($result->isErrorOrNoEffect()) {
            $db->rollBack();
            throw new Exception('Failed to delete decks');
        }

        // settings
        $setting->markDeleted();
        if (!$setting->save()) {
            $db->rollBack();
            throw new Exception('Failed to delete settings');
        }

        // messages
        $result = $dbEntityMessage->deleteMessages($playerName);
        if ($result->isError()) {
            $db->rollBack();
            throw new Exception('Failed to delete messages');
        }

        // game data
        if (count($games) > 0) {
            $result = $dbEntityGame->deleteGames($games);
            if ($result->isErrorOrNoEffect()) {
                $db->rollBack();
                throw new Exception('Failed to delete games');
            }

            // chats data
            $result = $dbEntityChat->deleteChats($games);
            if ($result->isError()) {
                $db->rollBack();
                throw new Exception('Failed to delete chats');
            }

            // replays data
            $result = $dbEntityReplay->deleteReplays($games);
            if ($result->isError()) {
                $db->rollBack();
                throw new Exception('Failed to delete replays');
            }
        }

        $db->commit();
    }

    /**
     * Reset experience for specified player along with deck slots
     * @param string $playerName
     * @throws Exception
     */
    public function resetExp($playerName)
    {
        $db = $this->getDb();
        $dbEntityDeck = $this->dbEntity()->deck();

        $score = $this->dbEntity()->score()->getScoreAsserted($playerName);

        // list all decks
        $result = $dbEntityDeck->listDecks($playerName);
        if ($result->isError()) {
            throw new Exception('Failed to list decks');
        }
        $decks = $result->data();

        // mark excess decks (gained by bonus deck slots)
        $excessDecks = array();
        foreach ($decks as $i => $deckData) {
            if ($i >= DeckModel::DECK_SLOTS) {
                $deck = $dbEntityDeck->getDeckAsserted($deckData['DeckID']);
                $excessDecks[] = $deck;
            }
        }

        $db->beginTransaction();

        // reset score data
        $score->resetExp();
        if (!$score->save()) {
            $db->rollBack();
            throw new Exception('Failed to reset exp');
        }

        // delete bonus deck slots
        foreach ($excessDecks as $deck) {
            $deck->markDeleted();
            if (!$deck->save()) {
                $db->rollBack();
                throw new Exception('Failed to delete deck');
            }
        }

        $db->commit();
    }

    /**
     * Buy item for specified player
     * @param string $playerName
     * @param string $item
     * @throws Exception
     */
    public function buyItem($playerName, $item)
    {
        $dbEntityDeck = $this->dbEntity()->deck();

        // validate selection
        if (!in_array($item, ['game_slot', 'deck_slot'])) {
            throw new Exception('Invalid item selection', Exception::WARNING);
        }

        $score = $this->dbEntity()->score()->getScoreAsserted($playerName);

        // case 1: game slot
        if ($item == 'game_slot') {
            // check if player has enough gold
            if ($score->getGold() < GameModel::GAME_SLOT_COST) {
                throw new Exception('Not enough gold', Exception::WARNING);
            }

            // buy game slot
            $score->setGold($score->getGold() - GameModel::GAME_SLOT_COST);
            $score->setData('GameSlots', $score->getData('GameSlots') + 1);
        }
        // case 2: deck slot
        elseif ($item == 'deck_slot') {
            // check if player has enough gold
            if ($score->getGold() < DeckModel::DECK_SLOT_COST) {
                throw new Exception('Not enough gold', Exception::WARNING);
            }

            $deck = $dbEntityDeck->createDeck($playerName, time());
            if (!$deck->save()) {
                throw new Exception('Failed to create new deck slot');
            }

            // buy deck slot
            $score->setGold($score->getGold() - DeckModel::DECK_SLOT_COST);
        }

        if (!$score->save()) {
            throw new Exception('Failed to buy shop item');
        }
    }
}
