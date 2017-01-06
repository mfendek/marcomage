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
            'entity_name' => 'deck',
            'primary_fields' => [
                'deck_id',
            ],
            'fields' => [
                'deck_id' => [
                    'type' => EntityAbstract::TYPE_INT,
                    'default' => 0,
                    'options' => [
                        EntityAbstract::OPT_UNSIGNED,
                        EntityAbstract::OPT_AUTO_ID,
                    ],
                ],
                'username' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                ],
                'deck_name' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                ],
                'is_ready' => [
                    'type' => EntityAbstract::TYPE_INT,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'data' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                    'options' => [
                        EntityAbstract::OPT_NO_DIFF,
                        EntityAbstract::OPT_SERIALIZE_PHP,
                    ],
                ],
                'note' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                ],
                'wins' => [
                    'type' => EntityAbstract::TYPE_INT,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'losses' => [
                    'type' => EntityAbstract::TYPE_INT,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'draws' => [
                    'type' => EntityAbstract::TYPE_INT,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'is_shared' => [
                    'type' => EntityAbstract::TYPE_INT,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'modified_at' => [
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
            'username' => $username,
            'deck_name' => $deckName,
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
        return parent::getModel(['deck_id' => $deckId], $asserted);
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
            'SELECT `deck_id`, `deck_name`, `modified_at`, (CASE WHEN `is_ready` = TRUE THEN "yes" ELSE "no" END) as `is_ready`'
            . ', `wins`, `losses`, `draws`, `is_shared` FROM `deck` WHERE `username` = ?'
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

        $authorQuery = ($author != 'none') ? ' AND `username` = ?' : '';

        $params = array();
        if ($author != 'none') {
            $params[] = $author;
        }

        $validConditions = ['username', 'deck_name', 'modified_at'];
        $condition = (in_array($condition, $validConditions)) ? $condition : 'modified_at';
        $order = ($order == 'ASC') ? 'ASC' : 'DESC';
        $page = (is_numeric($page)) ? $page : 0;

        return $db->query(
            'SELECT `deck_id`, `username`, `deck_name`, `modified_at`, `wins`, `losses`, `draws` FROM `deck`'
            . ' WHERE `is_shared` = TRUE AND `is_ready` = TRUE' . $authorQuery . ' ORDER BY `' . $condition . '` ' . $order
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

        $authorQuery = ($author != "none") ? ' AND `username` = ?' : '';

        $params = array();
        if ($author != 'none') {
            $params[] = $author;
        }

        return $db->query(
            'SELECT COUNT(`deck_id`) as `count` FROM `deck` WHERE `is_shared` = TRUE AND `is_ready` = TRUE' . $authorQuery . ''
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
            'SELECT DISTINCT `username` FROM `deck` WHERE `is_shared` = TRUE AND `is_ready` = TRUE ORDER BY `username` ASC'
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

        return $db->query('SELECT `deck_id`, `deck_name` FROM `deck` WHERE `username` = ? AND `is_ready` = TRUE', [
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

        return $db->query('UPDATE `deck` SET `username` = ? WHERE `username` = ?', [$newName, $player]);
    }

    /**
     * Delete all decks for specified player
     * @param string $player player name
     * @return \Db\Util\Result
     */
    public function deleteDecks($player)
    {
        $db = $this->db();

        return $db->query('DELETE FROM `deck` WHERE `username` = ?', [$player]);
    }
}
