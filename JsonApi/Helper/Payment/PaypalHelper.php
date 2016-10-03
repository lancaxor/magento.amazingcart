<?php

/**
 * Created by PhpStorm.
 * User: serf
 * Date: 03.10.16
 * Time: 17:16
 */
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

    /**
     * @var \Amazingcard\JsonApi\Helper\Quote
     */
    protected $quoteHelper;

    public function __construct(
        \Magento\Paypal\Model\Express $paypalExpress,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Amazingcard\JsonApi\Helper\Quote $quoteHelper
    ) {
        $this->paypalExpress = $paypalExpress;
        $this->orderFactory = $orderFactory;
        $this->quoteHelper = $quoteHelper;
    }

    public function getCheckoutRedirectUrl($orderId, $method)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->orderFactory->create();
        $order->getResource()->load($order, $orderId);
        $quoteId = $order->getQuoteId();
        $quote = $this->quoteHelper->getQuoteById($quoteId);
        $url = $quote->getCheckoutRedirectUrl();
        die(var_dump($url));
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