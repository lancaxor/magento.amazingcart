<?php
/**
 * Created by PhpStorm.
 * User: serf
 * Date: 11.10.16
 * Time: 11:28
 */

namespace Amazingcard\JsonApi\Helper;


class Requester
{
    private $url;
    private $headers;
    private $params;
    private $method;

    private $response;
    private $errorCode;
    private $errorMessage;
    private $timeout;

    public function __construct($url)
    {
        $this->url = $url;
        $this->headers = [];
        $this->params = [];
        $this->timeout = 30;
    }

    /**
     * @return array
     */
    public function getLastError() {
        return [
            'code'  => $this->errorCode,
            'message' => $this->errorMessage
        ];
    }

    /**
     * @param $timeout
     * @return $this
     */
    public function setTimeout($timeout) {
        $this->timeout = $timeout;
        return $this;
    }

    /**
     * @return int
     */
    public function getTimeout() {
        return $this->timeout;
    }

    /**
     * @param $url
     * @return $this
     */
    public function setUrl($url) {
        $this->url = $url;
        return $this;
    }

    /**
     * @param $header string|array
     * @param $value mixed
     * @return $this
     */
    public function addHeader($header, $value = null) {
        if (is_array($header)) {
            $this->headers = array_merge($this->headers, $header);
            return $this;
        }
        $this->headers[][$header] = $value;
        return $this;
    }

    /**
     * @return $this
     */
    public function resetHeaders() {
        $this->headers = [];
        return $this;
    }

    /**
     * @param      $param
     * @param null $value
     * @return $this
     */
    public function addParam($param, $value = null) {
        if(is_array($param)) {
            $this->params = array_merge($this->params, $param);
            return $this;
        }

        $this->params[][$param] =  $value;
        return $this;
    }

    /**
     * @return $this
     */
    public function resetParams() {
        $this->params = [];
        return $this;
    }

    /**
     * @param $method
     */
    public function setMethod($method) {
        $this->method = $method;
    }


    public function send($async = false) {
        if($async) {
            $this->sendAsyncRequest();
        } else {
            $this->sendRequest();
        }
        return $this;
    }

    public function getResponse() {
        return $this->response;
    }

    protected function sendRequest() {
        $options = [
            'http' => [
                'header' => $this->headers,
                'method' => $this->method,
                'content' => http_build_query($this->params)
            ]
        ];
        $context = stream_context_create($options);
        $this->response = file_get_contents($this->url, null, $context);
        return $this;
    }
    protected function sendAsyncRequest() {

        $params = [];
        foreach ($this->params as $key => $value) {
            $params[] = $key . '=' . urlencode($value);
        }
        $rawParams = implode('&', $params);
        $parts = parse_url($this->url);
        $fp = fsockopen(
            $parts['host'],
            isset($parts['port']) ? $parts['port'] : 80,
            $this->errorCode,
            $this->errorMessage,
            $this->timeout
        );

        $out = 'POST ' . $parts['path'] . 'HTTP/1.1\r\n';
        $out .= 'Host: ' . $parts['host'] . '\r\n';
        $out .= 'Content-Type: application/x-www-form-urlencoded\r\n';
        $out .= 'Content-Length: ' . strlen($rawParams) . '\r\n';
        $out .= 'Connection: Close\r\n\r\n';
        $out .= $rawParams;

        fwrite($fp, $out);
        fclose($fp);
        return $this;
    }
}