<?php

/**
 * Created by PhpStorm.
 * User: serf
 * Date: 21.09.16
 * Time: 17:43
 */

namespace Amazingcard\JsonApi\Helper\Payment;

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
     * @param $quote \Magento\Quote\Model\Quote
     * @param $method string
     * @return string
     */
    public function getCheckoutRedirectUrl($quote, $method)
    {
        return $this->getRedirectUrl($quote, $method);
    }

    /**
     * ....or this one, so use both and fix in future...
     * @param $order \Magento\Sales\Model\Order
     * @param $method string
     * @return string
     */
    public function getOrderRedirectUrl($order, $method)
    {
        return $this->getRedirectUrl($order, $method);
    }

    /**
     * idk how to handle orderId and method......
     * @param $orderId \Magento\Sales\Model\Order
     * @param $method
     * @return string
     */
    public function getRedirectUrl($order, $method) {

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