<?php
/**
 * Score - player scores database
 */

namespace Db\Entity;

use Db\Model\Player;

class PdoScore extends PdoAbstract
{
    /**
     * DB schema
     * @return array
     */
    protected function schema()
    {
        return [
            'entity_name' => 'score',
            'primary_fields' => [
                'username',
            ],
            'fields' => [
                'username' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                ],
                'level' => [
                    'type' => EntityAbstract::TYPE_INT,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'exp' => [
                    'type' => EntityAbstract::TYPE_INT,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'gold' => [
                    'type' => EntityAbstract::TYPE_INT,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
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
                'game_slots' => [
                    'type' => EntityAbstract::TYPE_INT,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'assassin' => [
                    'type' => EntityAbstract::TYPE_INT,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'builder' => [
                    'type' => EntityAbstract::TYPE_INT,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'carpenter' => [
                    'type' => EntityAbstract::TYPE_INT,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'collector' => [
                    'type' => EntityAbstract::TYPE_INT,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'desolator' => [
                    'type' => EntityAbstract::TYPE_INT,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'dragon' => [
                    'type' => EntityAbstract::TYPE_INT,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'gentle_touch' => [
                    'type' => EntityAbstract::TYPE_INT,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'saboteur' => [
                    'type' => EntityAbstract::TYPE_INT,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'snob' => [
                    'type' => EntityAbstract::TYPE_INT,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'survivor' => [
                    'type' => EntityAbstract::TYPE_INT,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'titan' => [
                    'type' => EntityAbstract::TYPE_INT,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'quarry' => [
                    'type' => EntityAbstract::TYPE_INT,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'magic' => [
                    'type' => EntityAbstract::TYPE_INT,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'dungeons' => [
                    'type' => EntityAbstract::TYPE_INT,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'rares' => [
                    'type' => EntityAbstract::TYPE_INT,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'tower' => [
                    'type' => EntityAbstract::TYPE_INT,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'wall' => [
                    'type' => EntityAbstract::TYPE_INT,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'tower_damage' => [
                    'type' => EntityAbstract::TYPE_INT,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'wall_damage' => [
                    'type' => EntityAbstract::TYPE_INT,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
                'ai_challenges' => [
                    'type' => EntityAbstract::TYPE_INT,
                    'default' => 0,
                    'options' => [EntityAbstract::OPT_UNSIGNED],
                ],
            ],
        ];
    }

    /**
     * Create new score for specified player
     * @param string $username username
     * @return \Db\Model\Score
     */
    public function createScore($username)
    {
        return parent::createModel(['username' => $username]);
    }

    /**
     * @param string $username
     * @param bool [$asserted]
     * @return \Db\Model\Score
     */
    public function getScore($username, $asserted = false)
    {
        // detect guest score
        if (in_array($username, ['', Player::SYSTEM_NAME])) {
            return $this->getGuestScore();
        }

        return parent::getModel(['username' => $username], $asserted);
    }

    /**
     * @param string $username
     * @return \Db\Model\Score
     */
    public function getScoreAsserted($username)
    {
        return $this->getScore($username, true);
    }

    /**
     * Load guest score
     * @return \Db\Model\Score
     */
    public function getGuestScore()
    {
        return $this->createModel([])
            ->resetCreated()
            ->cleanup();
    }

    /**
     * Rename all player name instances in scores
     * @param string $player player name
     * @param string $newName new name
     * @return \Db\Util\Result
     */
    public function renameScores($player, $newName)
    {
        $db = $this->db();

        return $db->query('UPDATE `score` SET `username` = ? WHERE `username` = ?', [$newName, $player]);
    }
}
