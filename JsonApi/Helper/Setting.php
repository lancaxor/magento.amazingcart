<?php
/**
 * Created by PhpStorm.
 * User: serf
 * Date: 22.09.16
 * Time: 19:01
 */

namespace Amazingcard\JsonApi\Helper;


use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Store\Model\StoreManagerInterface;

class Setting
{

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    protected $priceCurrency;

    public function __construct(
        StoreManagerInterface $storeManager,
        PriceCurrencyInterface $priceCurrency
    )
    {
        $this->storeManager = $storeManager;
        $this->priceCurrency = $priceCurrency;
    }

    public function getSettings() {
        $currency = $this->storeManager->getStore()->getCurrency();
//        $this->priceCurrency->getCurrency()->co
    }
}