<?php

/**
 * Created by PhpStorm.
 * User: serf
 * Date: 31.08.16
 * Time: 19:28
 */

namespace Amazingcard\JsonApi\Helper;

/**
 * Class UrlWorker
 * Encoding GET parameter to function name by next rules:
 *      some-action => SomeAction
 *      some-public-method => somePublicMethod
 *      some-protected-method => _someProtectedMethod
 * @package Amazingcard\JsonApi\Helper
 */
class UrlWorker
{

    /**#@+
     * Type of method
     */
    const TYPE_ACTION = 1;      // some-action => SomeAction
    const TYPE_PUBLIC_METHOD = 2; // some-public-method => somePublicMethod
    const TYPE_PROTECTED_METHOD = 3;  // some-protected-method => _someProtectedMethod
    /**#@-*/

    /**
     * Array of separators
     * @var array
     */
    private $separators = ['_', '-'];

    /**
     * Result method name
     * @var string
     */
    protected $decodedUrl = '';

    /**
     * Source string
     * @var string
     */
    protected $rawUrl = '';

    /**
     * Store source string
     * @param $rawUrl
     * @return $this
     */
    public function setRawUrl($rawUrl) {
        $this->rawUrl = $rawUrl;
        return $this;
    }

    /**
     * Get decoded method name
     * @return string
     */
    public function getDecodedUrl() {
        return $this->decodedUrl;
    }

    /**
     * Decode method name
     * @param int $type
     * @return $this
     */
    public function decodeUrl($type = self::TYPE_ACTION)
    {

        $this->secureType();
        $typeArray = str_split($this->rawUrl);
        $isAfterSeparator = false;
        $firstChar = true;

        if ($type == self::TYPE_PROTECTED_METHOD) {
            $this->decodedUrl = '_';
        }

        foreach ($typeArray as $char) {

            if($firstChar) {

                if($type == self::TYPE_ACTION) {
                    $this->decodedUrl = mb_strtoupper($char);
                } else {
                    $this->decodedUrl .= mb_strtolower($char);
                }
                $firstChar = false;

            } elseif($isAfterSeparator) {
                $this->decodedUrl .= mb_strtoupper($char);
                $isAfterSeparator = false;
            } else {

                if (in_array($char, $this->separators)) {
                    $isAfterSeparator = true;
                    continue;
                }
                $this->decodedUrl .= mb_strtolower($char);
            }
        }
        return $this;
    }

    /**
     * Secure string to prevent sql\php injections
     * @return $this
     */
    protected function secureType() {
        $this->rawUrl = str_replace([' ', '\t'], '', $this->rawUrl);
        $this->rawUrl = stripslashes($this->rawUrl);
        $this->rawUrl = htmlentities($this->rawUrl);
        return $this;
    }
}