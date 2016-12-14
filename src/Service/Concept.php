<?php
/**
 * Concept
 */

namespace Service;

use ArcomageException as Exception;
use Db\Model\Message as MessageModel;

class Concept extends ServiceAbstract
{
    /**
     * Check if user inputs are correct
     * @param array $data input data
     * @throws Exception
     */
    public function checkInputs(array $data)
    {
        // check mandatory inputs (Name is mandatory and also either Keywords or Effect must be specified as well)
        if (trim($data['name']) == '' || (trim($data['keywords']) == '' && trim($data['effect']) == '')) {
            throw new Exception('Fill in the mandatory inputs', Exception::WARNING);
        }

        // check card class
        if (!in_array($data['rarity'], ['Common', 'Uncommon', 'Rare'])) {
            throw new Exception('Invalid card class', Exception::WARNING);
        }

        // check card cost - numeric inputs
        if (!is_numeric($data['bricks']) || !is_numeric($data['gems']) || !is_numeric($data['recruits'])) {
            throw new Exception('Invalid numeric input', Exception::WARNING);
        }

        // check card cost -  negative values are not allowed
        if ($data['bricks'] < 0 || $data['gems'] < 0 || $data['recruits'] < 0) {
            throw new Exception('Card cost cannot be negative', Exception::WARNING);
        }

        // check card cost - value validity (cannot have 3 different values)
        if ($data['bricks'] > 0 && $data['gems'] > 0 && $data['recruits'] > 0
            && !($data['bricks'] == $data['gems'] && $data['gems'] == $data['recruits'])) {
            throw new Exception('Invalid cost input', Exception::WARNING);
        }

        // check state
        if (isset($data['state']) && !in_array($data['state'], ['waiting', 'rejected', 'interesting', 'implemented'])) {
            throw new Exception('Invalid concept state', Exception::WARNING);
        }

        // check input length
        if (mb_strlen($data['effect']) > \Db\Model\Concept::EFFECT_LENGTH) {
            throw new Exception('Card effect text is too long', Exception::WARNING);
        }
        if (mb_strlen($data['note']) > MessageModel::MESSAGE_LENGTH) {
            throw new Exception('Note text is too long', Exception::WARNING);
        }
    }
}
