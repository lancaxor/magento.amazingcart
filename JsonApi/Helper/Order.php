<?php
/**
 * Created by PhpStorm.
 * User: serf
 * Date: 15.09.16
 * Time: 15:05
 */

namespace Amazingcard\JsonApi\Helper;


use Magento\Catalog\Model\ProductRepository;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote\AddressFactory;
use Magento\Quote\Model\Quote\Payment;
use Magento\Quote\Model\Quote\PaymentFactory;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\QuoteManagement;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Status;
use Magento\Sales\Model\Order\StatusFactory;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Api\OrderManagementInterface;
use \Magento\Sales\Api\OrderPaymentRepositoryInterface;

class Order
{
    /**
     * @var User
     */
    protected $userHelper;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $orderFactory;

    /**
     * @var \Magento\Sales\Api\OrderManagementInterface
     */
    protected $orderManagement;

    /**
     * @var \Magento\Quote\Model\QuoteManagement
     */
    protected $quoteManagement;

    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    protected $quoteFactory;

    /**
     * @var AddressFactory
     */
    protected $quoteAddressFactory;

    /**
     * @var \Magento\Catalog\Model\ProductRepository
     */
    protected $productRepository;

    /**
     * @var AddressRepositoryInterface
     */
    protected $customerAddress;

    /**
     * @var \Magento\Sales\Api\OrderPaymentRepositoryInterface
     */
    protected $orderPaymentRepository;

    /**
     * @var PaymentFactory
     */
    protected $quotePaymentFactory;

    /**
     * @var \Amazingcard\JsonApi\Helper\PaymentHelper
     */
//    protected $paymentHelper;

    /**
     * @var Quote
     */
    protected $quoteHelper;

    /**
     * @var \Magento\Sales\Model\Order\StatusFactory
     */
    protected $oderStatusFactory;

    public function __construct(
        User $userHelper,
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        OrderFactory $orderFactory,
        ProductRepository $productRepository,
        OrderManagementInterface $orderManagement,
        QuoteManagement $quoteManagement,
        AddressFactory $addressFactory,
        QuoteFactory $quoteFactory,
        StatusFactory $statusFactory,
        AddressRepositoryInterface $customerAddressRepository,
        OrderPaymentRepositoryInterface $orderPaymentRepository,
        PaymentFactory $quotePaymentFactory,
//        PaymentHelper $paymentHelper,
        Quote $quoteHelper
    ) {
        $this->userHelper = $userHelper;
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->orderFactory = $orderFactory;
        $this->orderManagement = $orderManagement;
        $this->quoteManagement = $quoteManagement;
        $this->productRepository = $productRepository;
        $this->quoteAddressFactory = $addressFactory;
        $this->quoteFactory = $quoteFactory;
        $this->orderStatusFactory = $statusFactory;
        $this->customerAddress = $customerAddressRepository;
        $this->orderPaymentRepository = $orderPaymentRepository;
        $this->quotePaymentFactory = $quotePaymentFactory;
//        $this->paymentHelper = $paymentHelper;
        $this->quoteHelper = $quoteHelper;
    }

    /**
     * Get specified customer's orders by filter
     *
     * @param $userName
     * @param $password
     * @param $filter
     * @return array
     */
    public function getOrdersByUser($userName, $password, $filter) {

        $loginInfo = $this->userHelper->login($userName, $password);

        if(isset($loginInfo['error'])) {
            return [
                'error' => 1,
                'reason' => 'Not Authorized'
            ];
        }

        $currentStatus = '';
        /**
         * @see http://magento.stackexchange.com/questions/95608/orders-collection-magento-2
         */
        switch ($filter) {
            case 'On Hold':
                $currentStatus = 'holded';
                break;
            case 'Processing':
                $currentStatus = 'processing';
                break;
            case 'Pending Payment':
                $currentStatus = 'pending_payment';
                break;
            case 'Completed':
                $currentStatus = 'complete';
                break;
            case 'Refunded':    // Canceled?
                $currentStatus = 'canceled';
                break;
                case 'Failed':
                $currentStatus = 'canceled';    // idk what is this
                break;
            case null:
            case '':
            default:
                break;
        }

        /** @var CustomerInterface $customer */
        $customer = $loginInfo['data']['customer'];

        $orderModel = $this->orderFactory->create();
        $orderCollection = $orderModel->getCollection();
        $orderCollection->addFieldToFilter('customer_id', $customer->getId());
        if($currentStatus) {
            $orderCollection->addFieldToFilter('status', $currentStatus);
        }
        $orderList = $orderCollection->getItems();
        $orderItems = [];

        /** @var \Magento\Sales\Model\Order $order */
        foreach($orderList as $order) {
            $orderItems[$order->getEntityId()] = $order->getItems();
        }

        return [
            'orders' => $orderList,
            'orderItems'    => $orderItems
        ];
    }

    /**
     * @param $userName
     * @param $password
     * @param $orderId
     * @return array
     */
    public function getOrderById($userName, $password, $orderId) {

        if($orderId == null) {
            return [
                'error' => 2,
                'reason'    => 'Missing required param orderID!'
            ];
        }
        $loginInfo = $this->userHelper->login($userName, $password);

        if(isset($loginInfo['error'])) {
            return [
                'error' => 1,
                'reason'    => 'Not Authorized'
            ];
        }

        $order = $this->orderRepository->get($orderId);
        $orderModel = $this->orderFactory->create();
        $orderModel->getResource()
            ->load($orderModel, $orderId);

        $orderItems = $orderModel
            ->getAllItems();

        return [
            'order' => $order,
            'orderItems'    => $orderItems
        ];
    }

    /**
     * Create order using cart
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

        $cartInfo = $this->quoteHelper->cartApi($login, $password, $orderData['productJson'], $orderData['couponCodeJson']);
        if(isset($cartInfo['error'])) {
            return $cartInfo;
        }

        $loginData = $cartInfo['customerInfo'];
        $customer = $loginData['data']['customer'];

        /** @var \Magento\Checkout\Model\Cart $cart */
        $cart = $cartInfo['cart'];
        $quote = $cart->getQuote();

        if(isset($orderData['paymentMethodId'])) {

            /** @var \Magento\Quote\Model\Quote\Payment $paymentModel */
            $paymentModel = $this->quotePaymentFactory->create();
            $paymentModel->getResource()
                ->load($paymentModel, $orderData['paymentMethodId']);
            $paymentModel->setQuote($quote);
            $quote->setPayment($paymentModel);
            $quote->getPayment()->setMethod($paymentModel->getMethod());   // damn......

        } else {
            return [
                'error' => 2,
                'reason'    => 'Missing required parameter paymentMethodID!'
            ];
        }

        $quote->collectTotals();

        if($orderData['orderNotes']) {
            $quote->setCustomerNote($orderData['orderNotes']);
        }

        try {
            $this->quoteManagement->placeOrder($cart->getQuote()->getId(),  $paymentModel);
            $submittedOrder = $this->quoteManagement->submit($quote);

        } catch(LocalizedException $exception) {
            return [
                'error'     => 1,
                'reason'    => $exception->getMessage()
            ];
        }

        return [
            'status'    => 0,
            'reason'    => 'OK',
            'data'      => [
                'order' => $submittedOrder,
                'customer'  => $customer,
                'payment'   => $paymentModel
            ]
        ];
    }

    public function getStatusList() {

        /** @var Status $model */
        $model = $this->orderStatusFactory->create();
        $data = $model->getCollection()->getData();
        return $data;
    }

    /**
     * @param $orderId
     * @return \Magento\Quote\Model\Quote
     */
    public function getQuoteByOrderId($orderId) {

        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->orderFactory->create();
        $order->getResource()
            ->load($order, $orderId);
        $quoteId = $order->getQuoteId();
        $quote = $this->quoteHelper->getQuoteById($quoteId);
        return $quote;
    }
}