<?php
/**
 * Concept - the representation of a card concept
 */

namespace Db\Model;

class Concept extends ModelAbstract
{
    /**
     * Maximum card effect length
     */
    const EFFECT_LENGTH = 500;

    /**
     * Card picture maximum upload size
     */
    const UPLOAD_SIZE = 50 * 1000;

    /**
     * @return int
     */
    public function getCardId()
    {
        return $this->getFieldValue('CardID');
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->getFieldValue('Name');
    }

    /**
     * @return string
     */
    public function getRarity()
    {
        return $this->getFieldValue('Rarity');
    }

    /**
     * @return int
     */
    public function getBricks()
    {
        return $this->getFieldValue('Bricks');
    }

    /**
     * @return int
     */
    public function getGems()
    {
        return $this->getFieldValue('Gems');
    }

    /**
     * @return int
     */
    public function getRecruits()
    {
        return $this->getFieldValue('Recruits');
    }

    /**
     * @return string
     */
    public function getEffect()
    {
        return $this->getFieldValue('Effect');
    }

    /**
     * @return string
     */
    public function getKeywords()
    {
        return $this->getFieldValue('Keywords');
    }

    /**
     * @return string
     */
    public function getPicture()
    {
        return $this->getFieldValue('Picture');
    }

    /**
     * @return string
     */
    public function getNote()
    {
        return $this->getFieldValue('Note');
    }

    /**
     * @return string
     */
    public function getState()
    {
        return $this->getFieldValue('State');
    }

    /**
     * @return string
     */
    public function getAuthor()
    {
        return $this->getFieldValue('Author');
    }

    /**
     * @return string
     */
    public function getLastChange()
    {
        return $this->getFieldValue('LastChange');
    }

    /**
     * @return int
     */
    public function getThreadId()
    {
        return $this->getFieldValue('ThreadID');
    }

    /**
     * @return array
     */
    public function getData()
    {
        return [
            'id' => $this->getCardId(),
            'name' => $this->getName(),
            'rarity' => $this->getRarity(),
            'bricks' => $this->getBricks(),
            'gems' => $this->getGems(),
            'recruits' => $this->getRecruits(),
            'keywords' => $this->getKeywords(),
            'effect' => $this->getEffect(),
            'picture' => $this->getPicture(),
            'note' => $this->getNote(),
            'state' => $this->getState(),
            'author' => $this->getAuthor(),
            'lastchange' => $this->getLastChange(),
            'threadid' => $this->getThreadId(),
        ];
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        return $this->setFieldValue('Name', $name);
    }

    /**
     * @param string $rarity
     * @return $this
     */
    public function setRarity($rarity)
    {
        return $this->setFieldValue('Rarity', $rarity);
    }

    /**
     * @param int $bricks
     * @return $this
     */
    public function setBricks($bricks)
    {
        return $this->setFieldValue('Bricks', $bricks);
    }

    /**
     * @param int $gems
     * @return $this
     */
    public function setGems($gems)
    {
        return $this->setFieldValue('Gems', $gems);
    }

    /**
     * @param int $recruits
     * @return $this
     */
    public function setRecruits($recruits)
    {
        return $this->setFieldValue('Recruits', $recruits);
    }

    /**
     * @param string $effect
     * @return $this
     */
    public function setEffect($effect)
    {
        return $this->setFieldValue('Effect', $effect);
    }

    /**
     * @param string $keywords
     * @return $this
     */
    public function setKeywords($keywords)
    {
        return $this->setFieldValue('Keywords', $keywords);
    }

    /**
     * @param string $picture
     * @return $this
     */
    public function setPicture($picture)
    {
        return $this->setFieldValue('Picture', $picture);
    }

    /**
     * @param string $note
     * @return $this
     */
    public function setNote($note)
    {
        return $this->setFieldValue('Note', $note);
    }

    /**
     * @param string $state
     * @return $this
     */
    public function setState($state)
    {
        return $this->setFieldValue('State', $state);
    }

    /**
     * @param string $author
     * @return $this
     */
    public function setAuthor($author)
    {
        return $this->setFieldValue('Author', $author);
    }

    /**
     * @param string $lastChange
     * @return $this
     */
    public function setLastChange($lastChange)
    {
        return $this->setFieldValue('LastChange', $lastChange);
    }

    /**
     * @param int $threadId
     * @return $this
     */
    public function setThreadId($threadId)
    {
        return $this->setFieldValue('ThreadID', $threadId);
    }
}
