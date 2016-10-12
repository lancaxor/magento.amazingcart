<?php
/**
 * Created by PhpStorm.
 * User: serf
 * Date: 11.10.16
 * Time: 14:10
 */

namespace Amazingcard\JsonApi\Helper;

class Logger {

    /**#@+
     * Log types
     */
    const LOG_TYPE_INFO = 1;
    const LOG_TYPE_DATA = 2;
    const LOG_TYPE_DEFAULT = 3;
    const LOG_TYPE_ERROR = 4;
    const LOG_TYPE_WARN = 5;
    /**#@-*/

    /**#@+
     * Log order types
     * @var self::LOG_ORDER_NORMAL normal order (the newest data will be appended after old data)
     * @var self::LOG_ORDER_REVERSE reversed order (the newest data will be appended before old data)
     */
    const LOG_ORDER_NORMAL = 1;
    const LOG_ORDER_REVERSE = 2;
    /**#@-*/

    /**
     * Text before message
     */
    const BEFORE_TYPE = [
        self::LOG_TYPE_INFO => '[INFO] ',
        self::LOG_TYPE_DATA => "\n[DATA]====================\n",
        self::LOG_TYPE_DEFAULT => '[LOG] ',
        self::LOG_TYPE_ERROR => '[ERROR!] ',
        self::LOG_TYPE_WARN => '[WARNING!] ',
    ];

    /**
     * Text after message
     */
    const AFTER_TYPE = [
        self::LOG_TYPE_INFO => '',
        self::LOG_TYPE_DATA => "\n[/DATA]======================\n",
        self::LOG_TYPE_DEFAULT => '',
        self::LOG_TYPE_ERROR => '',
        self::LOG_TYPE_WARN => '',
    ];

    private $enabled = false;
    private $orderType = self::LOG_ORDER_NORMAL;
    private $logFile = 'var/log/runtime.log';
    private $data = [];

    public function __construct($filePath = 'var/log/runtime.log', $enabled = false) {
        $this->enabled = $enabled;
    }

    /**
     * Specify, where new data will be inserted.
     *
     * @param $order int -- order type. Values:
     * self::LOG_ORDER_NORMAL -- append data to the end of the log;
     * self::LOG_ORDER_REVERSE -- append data to the beginning of the log
     * @return $this
     */
    public function setOrder($order) {
        $this->orderType = $order;
        return $this;
    }

    public function enable($enabled = true) {
        $this->enabled = $enabled;
        return $this;
    }

    public function disable() {
        $this->enabled = false;
        return $this;
    }

    public function addMessage($data, $type = self::LOG_TYPE_DEFAULT, $uploadLog = true, $clearAfterUpload = true) {
        if(!$this->enabled) {
            return $this;
        }

        $timestamp = date('Y-m-d, H:i:s.u');
        $message = $timestamp . ': '
            . (array_key_exists($type, self::BEFORE_TYPE) ? self::BEFORE_TYPE[$type] : '')
            . print_r($data, true)
            . (array_key_exists($type, self::AFTER_TYPE) ? self::AFTER_TYPE[$type] : '')
            . "\n";

        if($this->orderType == self::LOG_ORDER_NORMAL) {
            array_push($this->data, $message);
        } else {
            array_unshift($this->data, $message);
        }

        if($uploadLog) {
            $this->uploadLog($clearAfterUpload, true);
        }
        return $this;
    }

    public function reverseLog() {
        $this->data = array_reverse($this->data);
        return $this;
    }

    public function getLog($reverse = false) {
        if ($reverse) {
            return array_reverse($this->data);
        }
        return $this->data;
    }

    public function getStringLog() {
        $data = '';
        if(!empty($this->data) && is_array($this->data)) {
            $data = implode('\n', $this->data);
        }
        return $data;
    }
    public function clearLog() {
        $this->data = [];
        return $this;
    }

    public function uploadLog($clearData = true, $append = true) {
        if(!$this->enabled) {
            return $this;
        }
        if(!file_exists($this->logFile)) {
            file_put_contents($this->logFile, '');  // for creating file if not exists
        }
        if($this->orderType == self::LOG_ORDER_REVERSE) {
            file_put_contents($this->logFile, $this->getStringLog() . file_get_contents($this->logFile));
        } else {
            file_put_contents($this->logFile, $this->data, $append ? FILE_APPEND : null);
        }

        if($clearData) {
            $this->data = [];
        }
        return $this;
    }
}