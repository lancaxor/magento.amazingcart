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
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Model\Quote\AddressFactory;

class Api
{

    /**
     * @var User
     */
    protected $userHelper;

    /**
     * Deprecated, but nothing i can use in /checkout/api
     * @var \Magento\Checkout\Model\Cart
     */
    protected $cart;

    /**
     * @var \Magento\Catalog\Model\ProductRepository
     */
    protected $productRepository;

    /**
     * @var \Magento\SalesRule\Model\ResourceModel\Coupon\Collection
     */
    protected $coupon;

    /**
     * @var
     */
    protected $order;

    /**
     * @var \Magento\CatalogInventory\Api\StockStateInterface
     */
    protected $stockState;

    /**
     * @var
     */
    protected $salesRuleFactory;

    /**
     * @var \Magento\SalesRule\Model\RuleFactory
     */
    protected $couponCollection;

    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    protected $quoteFactory;

    /**
     * @var \Magento\SalesRule\Model\CouponFactory
     */
    protected $salesRuleCouponFactory;

    /**
     * @var \Magento\SalesRule\Model\CouponRepository
     */
    protected $couponRepository;

    /**
     * @var \Magento\Quote\Model\Quote\Payment
     */
    protected $payment;

    /**
     * @var \Magento\Sales\Api\OrderPaymentRepositoryInterface
     */
    protected $orderPaymentRepository;

    /**
     * @var \Magento\Quote\Api\Data\PaymentInterface
     */
    protected $quotePayment;

    /**
     * @var \Magento\Quote\Model\QuoteManagement
     */
    protected $quoteManagement;

    /**
     * @var AddressFactory
     */
    protected $quoteAddressFactory;


    /**
     * @var \Magento\Quote\Model\Quote\AddressFactory
     */
//    protected $quoteAddressObject;

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
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Magento\Quote\Api\Data\PaymentInterface $quotePayment,
        AddressFactory $quoteAddressFactory
    ) {
        $this->userHelper = $userHelper;
        $this->cart = $cart;
        $this->productRepository = $productRepository;
        $this->stockState = $stockState;
        $this->couponCollection = $couponCollection;
        $this->salesRuleFactory = $salesRuleFactory;
        $this->quoteFactory = $quoteFactory;
        $this->salesRuleCouponFactory = $salesRuleCouponFactory;
        $this->couponRepository = $couponRepository;
        $this->payment = $payment;
        $this->orderPaymentRepository = $orderPaymentRepository;
        $this->quoteManagement = $quoteManagement;
        $this->quotePayment = $quotePayment;
        $this->quoteAddressFactory = $quoteAddressFactory;
//        $this->quoteAddressObject = $addressObject;
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

        $loginData = $this->userHelper->login($login, $password);
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
            $product = $this->productRepository->getById($id);
            $params = [
                'product'   => $id,
                'qty'      => $qty
            ];

            // check if the product is in the stock
            $isInStock = $this->stockState->verifyStock($id);

            if($isInStock) {

                try {
                    $this->cart->addProduct($product, $params);
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

            $salesModel = $this->salesRuleFactory->create();
            foreach ($couponCodes as $_ => $code) {

                $this->cart->getQuote()->setCouponCode($code);

                $ruleId = $this->salesRuleCouponFactory->create()
                    ->setCode($code)
                    ->getRuleId();



                $this->cart->getQuote()->setAppliedRuleIds($ruleId);

//                $coupons = $salesModel->getCoupons();
                // TODO: add catalog price rules

                // TODO: get really applied coupons
                // TODO: get these applied coupons summary discount
                $coupons['coupon-array-inserted'][] = $code;
            }
        }

        $strRuleIds = $this->cart->getQuote()->getAppliedRuleIds();
        die(var_dump('applied rules', $strRuleIds));
        $ruleIds = explode(',', $strRuleIds);
        $rules = $this->salesRuleFactory->create()
            ->getCollection()
            ->addFieldToFilter('rule_id', array('in'    => $ruleIds));

        foreach ($rules as $rule) {
            // TODO: count discount
        }

        $items = $this->cart->getQuote()->getItemsCollection();

        // TODO: count total

        //--- gateways
        // TODO: get all gateways and check for availability


        $this->cart->save();
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

        $loginData = $this->userHelper->login($login, $password);
        if(isset($loginData['error'])) {

            return [
                'error'     => 1,
                'reason'    => 'Not Authorized'
            ];
        }

        /** @var CustomerInterface $customer */
        $customer = $loginData['data']['customer'];

        if(!isset($orderData['productJson'])) {
            return [
                'error' => 1,
                'reason'    => 'Missing required parameter productIDJson!'
            ];
        }
        $stripProductId = stripslashes($orderData['productJson']);
        $productIds = json_decode($stripProductId);

        $quoteModel = $this->quoteFactory->create();
//        $quoteModel->setCustomer($customer);      // deprecated? see comment to setCustomer
        $quoteModel->setCurrency();
        $quoteModel->assignCustomer($customer); // assign the quote to customer


        foreach($productIds as $id => $quantity) {
            $product = $this->productRepository->getById($id);
            try {
                $quoteModel->addProduct($product, intval($quantity));
            } catch(LocalizedException $exception) {

            }
        }

        $shippingAddressId = $customer->getDefaultShipping();

        $quoteAddressModel = $this->quoteAddressFactory->create();
        $quoteAddressModel->getResource()
            ->load($quoteAddressModel, $shippingAddressId);

        die(var_dump($quoteModel->getShippingAddress()->getId()));
//        die(var_dump($quoteAddressModel->getData()));

        $quoteModel->setShippingAddress($quoteAddressModel->getById($shippingAddressId));

        $shippingMethod = $quoteModel->getShippingAddress()
            ->getShippingMethod();

        die(var_dump($shippingMethod));

        $quoteModel->getShippingAddress()
            ->setCollectShippingRates(true)
            ->collectShippingRates()
            ->setShippingMethod('freeshipping_freeshipping');   // idk what is this -_-

//
//        if(isset($orderData['paymentMethodId'])) {
//
//            $payment = $this->_orderPaymentRepository->get($orderData['paymentMethodId']);
//            $quoteModel->setPayment($payment);
//        } else {
//            return [
//                'error' => 1,
//                'reason'    => 'Missing required parameter paymentMethodID!'
//            ];
//        }

        $quoteModel->collectTotals();
        $submittedOrder = $this->quoteManagement->submit($quoteModel);

        return [
            'status'    => 0,
            'reason'    => 'OK',
            'data'      => [
                'order' => $submittedOrder,
                'customer'  => $customer,
//                'payment'   => $payment
            ]
        ];
    }



}