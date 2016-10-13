<?php
/**
 * Created by PhpStorm.
 * User: serf
 * Date: 22.09.16
 * Time: 19:01
 */

namespace Amazingcard\JsonApi\Helper;


use Magento\Checkout\Helper\Cart;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Store\Model\StoreManagerInterface;

class Settings
{

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var \Magento\Checkout\Helper\Cart
     */
    protected $coreCartHelper;

    /**
     * @var Order
     */
    protected $orderHelper;

    public function __construct(
        StoreManagerInterface $storeManager,
        PriceCurrencyInterface $priceCurrency,
        Cart $cartHelper,
        Order $orderHelper
    )
    {
        $this->storeManager = $storeManager;
        $this->priceCurrency = $priceCurrency;
        $this->coreCartHelper = $cartHelper;
        $this->orderHelper = $orderHelper;
    }

    public function getSettings() {
        $settings = new \stdClass();    // use it as object, not array. Just because it looks cool B-)
        $settings->currency = $this->storeManager->getStore()->getCurrency();
        $settings->currencySign = 'bax';
        $settings->cartUrl = $this->coreCartHelper->getCartUrl();
        $settings->host = $this->storeManager->getStore()->getBaseUrl();
        $settings->lostPasswordUrl = $settings->host . '/customer/account/login/';
        $settings->orderStatusList = $this->orderHelper->getStatusList();
        return $settings;
    }
}