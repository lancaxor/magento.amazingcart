<?php

/**
 * Created by PhpStorm.
 * User: serf
 * Date: 21.09.16
 * Time: 17:43
 */
class AuthorizeNetHelper
{
    /**
     * @var \Magento\Authorizenet\Model\Authorizenet
     */
    protected $authorizeNet;

    public function __construct(
        \Magento\Authorizenet\Model\Authorizenet $authorizenet
    ) {
        $this->authorizeNet = $authorizenet;
    }

    public function getRedirectUrl($methodId) {
    }

    public function getName() {
        return $this->authorizeNet->getTitle();
    }

    public function getData() {
        return [
            'title' => $this->authorizeNet->getTitle(),
            'code' => $this->authorizeNet->getCode(),
        ];
    }

}