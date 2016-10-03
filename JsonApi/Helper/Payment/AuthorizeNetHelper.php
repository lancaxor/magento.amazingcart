<?php

/**
 * Created by PhpStorm.
 * User: serf
 * Date: 21.09.16
 * Time: 17:43
 */
class AuthorizeNetHelper implements \Amazingcard\JsonApi\Api\PaymentMethodInterface
{
    /**
     * @var string
     */

    /**
     * @var \Magento\Authorizenet\Model\Directpost
     */
    protected $authorizeNet;

    /**
     * @var \Magento\Authorizenet\Model\TransactionService
     */
    protected $transactionService;

    public function __construct(
        \Magento\Authorizenet\Model\Directpost $authorizenet,
        \Magento\Authorizenet\Model\TransactionService $transactionService
    ) {
        $this->authorizeNet = $authorizenet;
        $this->transactionService = $transactionService;
    }

    /**
     * IDK what is correct in current situation, this method......
     * @param $orderId integer
     * @param $method string
     * @return string
     */
    public function getCheckoutRedirectUrl($orderId, $method)
    {
        return $this->getRedirectUrl($orderId, $method);
    }

    /**
     * ....or this one, so use both and fix in future...
     * @param $orderId integer
     * @param $method string
     * @return string
     */
    public function getOrderRedirectUrl($orderId, $method)
    {
        return $this->getRedirectUrl($orderId, $method);
    }

    /**
     * idk how to handle orderId and method......
     * @param $orderId
     * @param $method
     * @return string
     */
    public function getRedirectUrl($orderId, $method) {

        // wrong link -_-
        $url = $this->authorizeNet->getConfigData('cgi_url_td') ? '' : \Magento\Authorizenet\Model\TransactionService::CGI_URL_TD;
        return $url;
    }

    public function getName($method) {
        return $this->authorizeNet->getTitle();
    }

    public function getData($method) {
        return [
            'title' => $this->authorizeNet->getTitle(),
            'code' => $this->authorizeNet->getCode(),
        ];
    }

}