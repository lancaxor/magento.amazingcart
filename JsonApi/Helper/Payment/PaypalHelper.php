<?php

/**
 * Created by PhpStorm.
 * User: serf
 * Date: 03.10.16
 * Time: 17:16
 */

namespace Amazingcard\JsonApi\Helper\Payment;

class PaypalHelper implements \Amazingcard\JsonApi\Api\PaymentMethodInterface
{

    /**
     * @var \Magento\Paypal\Model\Express
     */
    protected $paypalExpress;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $orderFactory;

    public function __construct(
        \Magento\Paypal\Model\Express $paypalExpress,
        \Magento\Sales\Model\OrderFactory $orderFactory
    ) {
        $this->paypalExpress = $paypalExpress;
        $this->orderFactory = $orderFactory;
    }

    /**
     * @param $quote \Magento\Quote\Model\Quote
     * @param $method
     * @return string
     */
    public function getCheckoutRedirectUrl($quote, $method)
    {
        $url = $quote->getCheckoutRedirectUrl();
        die(var_dump($quote->getId(), $url));
        return $this->paypalExpress->getCheckoutRedirectUrl();
    }

    public function getOrderRedirectUrl($orderId, $method)
    {
        return $this->paypalExpress->getOrderPlaceRedirectUrl();
    }

    public function getName($method)
    {
        return $this->paypalExpress->getTitle();
    }

    public function getData($method)
    {
        return [
            'title' => $this->paypalExpress->getTitle(),
            'code' => $this->paypalExpress->getCode()
        ];
    }

}