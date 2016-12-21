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
        return $this->getFieldValue('card_id');
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->getFieldValue('name');
    }

    /**
     * @return string
     */
    public function getRarity()
    {
        return $this->getFieldValue('rarity');
    }

    /**
     * @return int
     */
    public function getBricks()
    {
        return $this->getFieldValue('bricks');
    }

    /**
     * @return int
     */
    public function getGems()
    {
        return $this->getFieldValue('gems');
    }

    /**
     * @return int
     */
    public function getRecruits()
    {
        return $this->getFieldValue('recruits');
    }

    /**
     * @return string
     */
    public function getEffect()
    {
        return $this->getFieldValue('effect');
    }

    /**
     * @return string
     */
    public function getKeywords()
    {
        return $this->getFieldValue('keywords');
    }

    /**
     * @return string
     */
    public function getPicture()
    {
        return $this->getFieldValue('picture');
    }

    /**
     * @return string
     */
    public function getNote()
    {
        return $this->getFieldValue('note');
    }

    /**
     * @return string
     */
    public function getState()
    {
        return $this->getFieldValue('state');
    }

    /**
     * @return string
     */
    public function getAuthor()
    {
        return $this->getFieldValue('author');
    }

    /**
     * @return string
     */
    public function getModifiedAt()
    {
        return $this->getFieldValue('modified_at');
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
            'modified_at' => $this->getModifiedAt(),
        ];
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        return $this->setFieldValue('name', $name);
    }

    /**
     * @param string $rarity
     * @return $this
     */
    public function setRarity($rarity)
    {
        return $this->setFieldValue('rarity', $rarity);
    }

    /**
     * @param int $bricks
     * @return $this
     */
    public function setBricks($bricks)
    {
        return $this->setFieldValue('bricks', $bricks);
    }

    /**
     * @param int $gems
     * @return $this
     */
    public function setGems($gems)
    {
        return $this->setFieldValue('gems', $gems);
    }

    /**
     * @param int $recruits
     * @return $this
     */
    public function setRecruits($recruits)
    {
        return $this->setFieldValue('recruits', $recruits);
    }

    /**
     * @param string $effect
     * @return $this
     */
    public function setEffect($effect)
    {
        return $this->setFieldValue('effect', $effect);
    }

    /**
     * @param string $keywords
     * @return $this
     */
    public function setKeywords($keywords)
    {
        return $this->setFieldValue('keywords', $keywords);
    }

    /**
     * @param string $picture
     * @return $this
     */
    public function setPicture($picture)
    {
        return $this->setFieldValue('picture', $picture);
    }

    /**
     * @param string $note
     * @return $this
     */
    public function setNote($note)
    {
        return $this->setFieldValue('note', $note);
    }

    /**
     * @param string $state
     * @return $this
     */
    public function setState($state)
    {
        return $this->setFieldValue('state', $state);
    }

    /**
     * @param string $author
     * @return $this
     */
    public function setAuthor($author)
    {
        return $this->setFieldValue('author', $author);
    }

    /**
     * @param string $lastChange
     * @return $this
     */
    public function setModifiedAt($lastChange)
    {
        return $this->setFieldValue('modified_at', $lastChange);
    }
}
