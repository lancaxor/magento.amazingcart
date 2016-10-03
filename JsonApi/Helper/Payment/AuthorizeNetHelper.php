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

    /**
     * @var \Magento\Authorizenet\Model\TransactionService
     */
    protected $transactionService;

    public function __construct(
        \Magento\Authorizenet\Model\Authorizenet $authorizenet,
        \Magento\Authorizenet\Model\TransactionService $transactionService
    ) {
        $this->authorizeNet = $authorizenet;
        $this->transactionService = $transactionService;
    }

    public function getRedirectUrl($methodId) {
        $url = $this->authorizeNet->getConfigData('cgi_url_td') ? '' : \Magento\Authorizenet\Model\TransactionService::CGI_URL_TD;
        return $url;
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