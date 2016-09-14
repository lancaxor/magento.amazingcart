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

    const TYPE_ACTION = 1,      // some-action => SomeAction
        TYPE_PUBLIC_METHOD = 2, // some-public-method => somePublicMethod
        TYPE_PROTECTED_METHOD = 3;  // some-protected-method => _someProtectedMethod

    private $_separators = ['_', '-'];
    protected $_decodedUrl = '';
    protected $_rawUrl = '';

    public function setRawUrl($rawUrl) {
        $this->_rawUrl = $rawUrl;
        return $this;
    }

    public function getDecodedUrl() {
        return $this->_decodedUrl;
    }

    /**
     * @param int $type
     * @return $this
     */
    public function decodeUrl($type = self::TYPE_ACTION)
    {

        $this->_secureType();
        $typeArray = str_split($this->_rawUrl);
        $isAfterSeparator = false;
        $firstChar = true;

        if ($type == self::TYPE_PROTECTED_METHOD) {
            $this->_decodedUrl = '_';
        }

        foreach ($typeArray as $char) {

            if($firstChar) {

                if($type == self::TYPE_ACTION) {
                    $this->_decodedUrl = mb_strtoupper($char);
                } else {
                    $this->_decodedUrl .= mb_strtolower($char);
                }
                $firstChar = false;

            } elseif($isAfterSeparator) {
                $this->_decodedUrl .= mb_strtoupper($char);
                $isAfterSeparator = false;
            } else {

                if (in_array($char, $this->_separators)) {
                    $isAfterSeparator = true;
                    continue;
                }
                $this->_decodedUrl .= mb_strtolower($char);
            }
        }
        return $this;
    }

    protected function _secureType() {
        $this->_rawUrl = str_replace(' ', '', $this->_rawUrl);
        $this->_rawUrl = stripslashes($this->_rawUrl);
        return $this;
    }
}