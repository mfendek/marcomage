<?php
/**
 * Deck - the representation of decks database
 */

namespace Db\Entity;

use Db\Model\Deck;
use Util\Date;

class PdoDeck extends PdoAbstract
{
    /**
     * DB schema
     * @return array
     */
    protected function schema()
    {
        return [
            'entity_name' => 'decks',
            'model_name' => 'deck',
            'primary_fields' => [
                'DeckID',
            ],
            'fields' => [
                'DeckID' => [
                    'type' => EntityAbstract::TYPE_INT32,
                    'default' => 0,
                    'options' => [
                        EntityAbstract::OPT_UNSIGNED,
                        EntityAbstract::OPT_AUTO_ID,
                    ],
                ],
                'Username' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                ],
                'Deckname' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                ],
                'Ready' => [
                    'type' => EntityAbstract::TYPE_INT32,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'Data' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                    'options' => [
                        EntityAbstract::OPT_NO_DIFF,
                        EntityAbstract::OPT_SERIALIZE_PHP,
                    ],
                ],
                'Note' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                ],
                'Wins' => [
                    'type' => EntityAbstract::TYPE_INT32,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'Losses' => [
                    'type' => EntityAbstract::TYPE_INT32,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'Draws' => [
                    'type' => EntityAbstract::TYPE_INT32,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'Shared' => [
                    'type' => EntityAbstract::TYPE_INT32,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'Modified' => [
                    'type' => EntityAbstract::TYPE_DATETIME,
                    'default' => Date::DATETIME_ZERO,
                    'options' => [
                        EntityAbstract::OPT_INSERT_DATETIME,
                        EntityAbstract::OPT_UPDATE_DATETIME,
                    ],
                ],
            ],
        ];
    }

    /**
     * Create custom deck
     * @param array $data
     * @return Deck
     */
    public function createCustomDeck(array $data)
    {
        $deck = parent::createModel($data);
        $deck->cleanup();

        return $deck;
    }

    /**
     * Create new deck
     * @param string $username player name
     * @param string $deckName deck name
     * @return Deck
     */
    public function createDeck($username, $deckName)
    {
        /* @var Deck $deck */
        $deck = parent::createModel([
            'Username' => $username,
            'Deckname' => $deckName,
        ]);

        return $deck->setData(new \CDeckData());
    }

    /**
     * @param int $deckId
     * @param bool [$asserted]
     * @return Deck
     */
    public function getDeck($deckId, $asserted = false)
    {
        return parent::getModel(['DeckID' => $deckId], $asserted);
    }

    /**
     * @param int $deckId
     * @return Deck
     */
    public function getDeckAsserted($deckId)
    {
        return $this->getDeck($deckId, true);
    }

    /**
     * List decks for specified player
     * @param string $username player name
     * @return \Db\Util\Result
     */
    public function listDecks($username)
    {
        $db = $this->db();

        return $db->query(
            'SELECT `DeckID`, `Deckname`, `Modified`, (CASE WHEN `Ready` = TRUE THEN "yes" ELSE "no" END) as `Ready`'
            . ', `Wins`, `Losses`, `Draws`, `Shared` FROM `decks` WHERE `Username` = ?'
            , [$username]
        );
    }

    /**
     * List shared decks
     * @param string $author author filter
     * @param string $condition order condition
     * @param string $order order option
     * @param int $page current page
     * @return \Db\Util\Result
     */
    public function listSharedDecks($author, $condition, $order, $page)
    {
        $db = $this->db();

        $authorQuery = ($author != 'none') ? ' AND `Username` = ?' : '';

        $params = array();
        if ($author != 'none') {
            $params[] = $author;
        }

        $validConditions = ['Username', 'Deckname', 'Modified'];
        $condition = (in_array($condition, $validConditions)) ? $condition : 'Modified';
        $order = ($order == 'ASC') ? 'ASC' : 'DESC';
        $page = (is_numeric($page)) ? $page : 0;

        return $db->query(
            'SELECT `DeckID`, `Username`, `Deckname`, `Modified`, `Wins`, `Losses`, `Draws` FROM `decks`'
            . ' WHERE `Shared` = TRUE AND `Ready` = TRUE' . $authorQuery . ' ORDER BY `' . $condition . '` ' . $order
            . ' LIMIT ' . (Deck::DECKS_PER_PAGE * $page) . ', ' . Deck::DECKS_PER_PAGE . ''
            , $params
        );
    }

    /**
     * Count pages for shared decks list
     * @param string $author author filter
     * @return \Db\Util\Result
     */
    public function countPages($author)
    {
        $db = $this->db();

        $authorQuery = ($author != "none") ? ' AND `Username` = ?' : '';

        $params = array();
        if ($author != 'none') {
            $params[] = $author;
        }

        return $db->query(
            'SELECT COUNT(`DeckID`) as `Count` FROM `decks` WHERE `Shared` = TRUE AND `Ready` = TRUE' . $authorQuery . ''
            , $params
        );
    }

    /**
     * List shared decks authors
     * @return \Db\Util\Result
     */
    public function listAuthors()
    {
        $db = $this->db();

        return $db->query(
            'SELECT DISTINCT `Username` FROM `decks` WHERE `Shared` = TRUE AND `Ready` = TRUE ORDER BY `Username` ASC'
        );
    }

    /**
     * List ready decks for specified player
     * @param string $username player name
     * @return \Db\Util\Result
     */
    public function listReadyDecks($username)
    {
        $db = $this->db();

        return $db->query('SELECT `DeckID`, `Deckname` FROM `decks` WHERE `Username` = ? AND `Ready` = TRUE', [
            $username
        ]);
    }

    /**
     * Rename all player name instances in decks
     * @param string $player player name
     * @param string $newName new name
     * @return \Db\Util\Result
     */
    public function renameDecks($player, $newName)
    {
        $db = $this->db();

        return $db->query('UPDATE `decks` SET `Username` = ? WHERE `Username` = ?', [$newName, $player]);
    }

    /**
     * Delete all decks for specified player
     * @param string $player player name
     * @return \Db\Util\Result
     */
    public function deleteDecks($player)
    {
        $db = $this->db();

        return $db->query('DELETE FROM `decks` WHERE `Username` = ?', [$player]);
    }
}
