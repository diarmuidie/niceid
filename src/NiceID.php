<?php
/**
 * User: diarmuid <hello@diarmuid.ie>
 * Date: 21/03/15
 */

namespace Diarmuidie\NiceID;

use Diarmuidie\NiceID\Utilities\BaseConvert;
use Diarmuidie\NiceID\Utilities\FisherYates;

/**
 * Class NiceID
 * @package Diarmuidie\NiceID
 */
class NiceID
{

    /**
     * @var string The default characters to use when encoding
     */
    protected $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    /**
     * @var int The default min length of the encoded string
     */
    protected $minLength = 5;

    /**
     * @var string The default secret to use for shuffling
     */
    protected $secret = 'Random secret string';

    /**
     * Optionally set the secret at initialisation
     *
     * @param null|string $secret
     */
    public function __construct($secret = null)
    {
        if ($secret) {
            $this->secret = $secret;
        }
    }

    /**
     * Set the secret
     *
     * @param string $secret
     */
    public function setSecret($secret)
    {
        $this->secret = $secret;
    }

    /**
     * Set the characters string to use for encoding
     *
     * @param string $characters
     */
    public function setCharacters($characters)
    {
        $this->characters = $characters;
    }

    /**
     * Get the characters string to use for encoding
     *
     * @return string
     */
    public function getCharacters()
    {
        return $this->characters;
    }

    /**
     * Set the min length of the encoded string
     *
     * @param int $minLength
     */
    public function setMinLength($minLength)
    {
        $this->minLength = $minLength;
    }

    /**
     * Get the min length of the encoded string
     *
     * @return int
     */
    public function getMinLength()
    {
        return $this->minLength;
    }

    /**
     * Encode an integer into a NiceID string
     *
     * @param int $id The ID to encode
     * @return string The encoded ID
     */
    public function encode($id)
    {

        $characters = $this->characters;

        // Split characters string into array (preg_split plays nice with UTF-8 chars)
        $charactersArray = preg_split('//u', $characters, -1, PREG_SPLIT_NO_EMPTY);

        // Pick a random salt character
        $salt = $charactersArray[mt_rand(0, count($charactersArray) - 1)];

        // Shuffle the array
        $shuffledCharactersArray = FisherYates::shuffle($charactersArray, $this->secret . $salt);
        $characters = implode($shuffledCharactersArray);

        // If a minLength is set bump up the input ID by this many orders of magnitude
        if ($this->minLength > 0) {
            $id += pow(strlen($this->characters), $this->minLength - 2);
        }

        // Encode the ID
        $niceId = BaseConvert::convert($id, '0123456789', $characters) . $salt;

        return $niceId;

    }

    /**
     * Decode a NiceId into an integer
     *
     * @param string $niceId
     * @return int
     */
    public function decode($niceId)
    {

        $characters = $this->characters;

        // Split characters string into array (preg_split plays nice with UTF-8 chars)
        $charactersArray = preg_split('//u', $characters, -1, PREG_SPLIT_NO_EMPTY);

        $salt = $this->getSaltChar($niceId);

        // Shuffle the array
        $shuffledCharactersArray = FisherYates::shuffle($charactersArray, $this->secret . $salt);
        $characters = implode($shuffledCharactersArray);

        $niceId = $this->getNiceID($niceId);

        // Decode the ID
        $id = (int)BaseConvert::convert($niceId, $characters, '0123456789');

        // If a minLength is set remove the value additions from the ID
        if ($this->minLength > 0) {
            $id -= pow(strlen($this->characters), $this->minLength - 2);
        }

        return $id;
    }

    /**
     * Extract the salt ID from the NiceID
     * @param string $niceID
     * @return string The salt
     */
    private function getSaltChar($niceID)
    {

        // Return the last char in the ID
        return (string)mb_substr($niceID, -1);

    }

    /**
     * Get the NiceID from a salted NiceID
     *
     * @param string $niceID The salted NiceID
     * @return string The un-salted NiceID
     */
    private function getNiceID($niceID)
    {

        // Return all but the last char in the ID
        return mb_substr($niceID, 0, -1);

    }
}