<?php
/**
 * Created by PhpStorm.
 * User: serf
 * Date: 12.09.16
 * Time: 14:38
 */

namespace Amazingcard\JsonApi\Helper;


use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\LocalizedException;

class Api
{

    /**
     * @var User
     */
    protected $_userHelper;

    /**
     * Deprecated, but nothing i can use in /checkout/api
     * @var \Magento\Checkout\Model\Cart
     */
    protected $_cart;

    /**
     * @var \Magento\Catalog\Model\ProductRepository
     */
    protected $_productRepository;

    /**
     * @var \Magento\SalesRule\Model\ResourceModel\Coupon\Collection
     */
    protected $_coupon;

    /**
     * @var
     */
    protected $_order;

    /**
     * @var \Magento\CatalogInventory\Api\StockStateInterface
     */
    protected $_stockState;

    /**
     * @var
     */
    protected $_salesRuleFactory;

    /**
     * @var \Magento\SalesRule\Model\RuleFactory
     */
    protected $_couponCollection;

    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    protected $_quoteFactory;

    /**
     * @var \Magento\SalesRule\Model\CouponFactory
     */
    protected $_salesRuleCouponFactory;

    /**
     * @var \Magento\SalesRule\Model\CouponRepository
     */
    protected $_couponRepository;

    /**
     * @var \Magento\Quote\Model\Quote\Payment
     */
    protected $_payment;

    /**
     * @var \Magento\Sales\Api\OrderPaymentRepositoryInterface
     */
    protected $_orderPaymentRepository;

    /**
     * @var \Magento\Quote\Model\QuoteManagement
     */
    protected $_quoteManagement;

    public function __construct(
        User $userHelper,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\CatalogInventory\Api\StockStateInterface $stockState,
        \Magento\SalesRule\Model\ResourceModel\Coupon\Collection $couponCollection,
        \Magento\SalesRule\Model\RuleFactory   $salesRuleFactory,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\SalesRule\Model\CouponFactory $salesRuleCouponFactory,
        \Magento\SalesRule\Model\CouponRepository   $couponRepository,
        \Magento\Quote\Model\Quote\Payment $payment,//TODO: remove
        \Magento\Sales\Api\OrderPaymentRepositoryInterface $orderPaymentRepository,
        \Magento\Quote\Model\QuoteManagement $quoteManagement
    ) {
        $this->_userHelper = $userHelper;
        $this->_cart = $cart;
        $this->_productRepository = $productRepository;
        $this->_stockState = $stockState;
        $this->_couponCollection = $couponCollection;
        $this->_salesRuleFactory = $salesRuleFactory;
        $this->_quoteFactory = $quoteFactory;
        $this->_salesRuleCouponFactory = $salesRuleCouponFactory;
        $this->_couponRepository = $couponRepository;
        $this->_payment = $payment;
        $this->_orderPaymentRepository = $orderPaymentRepository;
        $this->_quoteManagement = $quoteManagement;
    }

    /**
     * Add products and coupons to cart
     * @param $login    string
     * @param $password string
     * @param $productIdJSON    string
     * @param $couponCodeJSON   string
     * @return array
     */
    public function cartApi($login, $password, $productIdJSON, $couponCodeJSON) {

        $loginData = $this->_userHelper->login($login, $password);
        if(isset($loginData['error'])) {

            return [
                'error'     => 1,
                'reason'    => 'Not Authorized'
            ];
        }

        $paymentGateways = [];
        $cartContent = [];

        //--- products
        $stripProductId = stripslashes($productIdJSON);
        $productIds = json_decode($stripProductId);

        /**
         * @see http://magento.stackexchange.com/questions/115929/magento2-how-to-add-a-product-into-cart-programatically-when-checkout-cart-pro
         */
        foreach ($productIds as $id => $qty) {
            $product = $this->_productRepository->getById($id);
            $params = [
                'product'   => $id,
                'qty'      => $qty
            ];

            // check if the product is in the stock
            $isInStock = $this->_stockState->verifyStock($id);

            if($isInStock) {

                try {
                    $this->_cart->addProduct($product, $params);
                } catch (LocalizedException $exception) {
                    $ignoredProducts[] = $id;
                }
            } else {
                $ignoredProducts[] = $id;
            }

        }

        //--- coupons
        $coupons = [
            'applied-coupon'    => [],
            'discount-ammount'  => [],
            'coupon-array-inserted'    => []
        ];

        if(isset($couponCodeJSON)) {
            $stripCouponCode = stripslashes($couponCodeJSON);
            $couponCodes = json_decode($stripCouponCode);
        }

        if(isset($couponCodes)) {

            if(!is_array($couponCodes)) {
                $couponCodes = [$couponCodes];
            }

            $salesModel = $this->_salesRuleFactory->create();
            foreach ($couponCodes as $_ => $code) {

                $this->_cart->getQuote()->setCouponCode($code);

                $ruleId = $this->_salesRuleCouponFactory->create()
                    ->setCode($code)
                    ->getRuleId();



                $this->_cart->getQuote()->setAppliedRuleIds($ruleId);

//                $coupons = $salesModel->getCoupons();
                // TODO: add catalog price rules

                // TODO: get really applied coupons
                // TODO: get these applied coupons summary discount
                $coupons['coupon-array-inserted'][] = $code;
            }

            $strRuleIds = $this->_cart->getQuote()->getAppliedRuleIds();
            die(var_dump('applied rules', $strRuleIds));
            $ruleIds = explode(',', $strRuleIds);
            $rules = $this->_salesRuleFactory->create()
                ->getCollection()
                ->addFieldToFilter('rule_id', array('in'    => $ruleIds));

            foreach ($rules as $rule) {
                // TODO: count discount
            }
        }

        $appliedRules = $this->_cart->getQuote()->getAppliedRuleIds();
        die(var_dump($appliedRules));

        $items = $this->_cart->getQuote()->getItemsCollection();

        // TODO: count total

        //--- gateways
        // TODO: get all gateways and check for availability


        $this->_cart->save();
        return [
            'coupon'
        ];
    }

    /**
     * @TODO: test it
     * @param $login
     * @param $password
     * @param $orderData array [
     *       productJson
     *       couponCodeJson
     *       paymentMethodId
     *       orderNotes
     * ]
     * @return array
     */
    public function placeOrder($login, $password, $orderData) {

        $loginData = $this->_userHelper->login($login, $password);
        if(isset($loginData['error'])) {

            return [
                'error'     => 1,
                'reason'    => 'Not Authorized'
            ];
        }

        /** @var CustomerInterface $customer */
        $customer = $loginData['customer'];

        if(!isset($orderData['productJson'])) {
            return [
                'error' => 1,
                'reason'    => 'Missing required parameter productIDJson!'
            ];
        }
        $stripProductId = stripslashes($orderData['productJson']);
        $productIds = json_decode($stripProductId);

        $quoteModel = $this->_quoteFactory->create();
//        $quoteModel->setCustomer($customer);      // deprecated? see comment to setCustomer
        $quoteModel->setCurrency();
        $quoteModel->assignCustomer($customer); // assign the quote to customer

        foreach($productIds as $id => $quantity) {
            $product = $this->_productRepository->getById($id);
            $quoteModel->addProduct($product, intval($quantity));
        }

        $quoteModel->getShippingAddress()
            ->setCollectShippingRates(true)
            ->collectShippingRates()
            ->setShippingMethod('freeshipping_freeshipping');   // idk what is this -_-

        if(isset($orderData['paymentMethodId'])) {

            $payment = $this->_orderPaymentRepository->get(0);
            $quoteModel->setPayment($payment);
        } else {
            return [
                'error' => 1,
                'reason'    => 'Missing required parameter paymentMethodId!'
            ];
        }

        $quoteModel->collectTotals();
        $submittedOrder = $this->_quoteManagement->submit($quoteModel);

        return [
            'status'    => 0,
            'reason'    => 'OK',
            'data'      => [
                'order' => $submittedOrder,
                'customer'  => $customer,
                'payment'   => $payment
            ]
        ];
    }



}