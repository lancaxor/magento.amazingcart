<?php
/**
 * Created by PhpStorm.
 * User: serf
 * Date: 20.09.16
 * Time: 18:30
 */

namespace Amazingcard\JsonApi\Helper;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Payment\Model\MethodInterface;
use Magento\Quote\Model\Quote\PaymentFactory;

class PaymentHelper
{

    /**
     * @var MethodInterface
     */
    protected $paymentMethod;

    /**
     * @var \Magento\Quote\Model\Quote\PaymentFactory
     */
    protected $paymentFactory;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Payment\CollectionFactory
     */
    protected $paymentCollectionFactory;

    /**
     * @var \Magento\Payment\Model\Config
     */
    protected $paymentConfig;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \AuthorizeNetHelper
     */
    protected $authorizeNetHelper;

    public function __construct(
//        MethodInterface $paymentMethod,
        PaymentFactory $paymentFactory,
        \Magento\Sales\Model\ResourceModel\Order\Payment\CollectionFactory $paymentCollectionFactory,
        \Magento\Payment\Model\Config $paymentConfig,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    )
    {
//        $this->paymentMethod = $paymentMethod;
        $this->paymentFactory = $paymentFactory;
        $this->paymentCollectionFactory = $paymentCollectionFactory;
        $this->paymentConfig = $paymentConfig;
        $this->scopeConfig = $scopeConfig;
    }

    public function getPaymentRedirectUrl($paymentId) {

        $payment = $this->getPaymentModelById($paymentId);

        die(var_dump($payment->getMethod()));

        $orderPlaceUrl = $payment->getOrderPlaceRedirectUrl();
        $checkoutUrl = $payment->getCheckoutRedirectUrl();
        return $checkoutUrl;
    }

    /**
     * @param $paymentId int
     * @return \Magento\Quote\Model\Quote\Payment
     */
    public function getPaymentModelById($paymentId) {
        $paymentModel = $this->paymentFactory->create();
        $paymentModel->getResource()
            ->load($paymentModel, $paymentId);
        return $paymentModel;
    }

    public function getPaymentArray() {
        $collection = $this->paymentCollectionFactory->create()
            ->addFieldToSelect('*');
//        $paymentItems = $collection->getItems();

        $data = [];

        foreach ($collection as $payment) {

            $dataItem = [
                'id'            => $payment->getMethod(),
                'title'         => $payment->getTitle()
//                'description'   => '',        // magento has no payment description...
//                'meta_key'      => ''         // ...and meta-key...
            ];

            $data[] = $dataItem;
        }
        return $data;
    }

    /**
     * Get payment methods collection
     * @return \Magento\Sales\Model\ResourceModel\Order\Payment\Collection
     */
    public function getPaymentCollection()
    {
        $collection = $this->paymentCollectionFactory->create()
            ->addFieldToSelect('*');

        foreach($collection as &$payment) {
            $payment->title = $this->scopeConfig->getValue('payment/' . $payment->getMethod() . '/title',   // dynamically add object's field? The worst solution. I`m mad 0_o
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
        }
        return $collection;
    }


    /**
     * @return array
     */
    public function getPaymentGateways()
    {
        $activeMethods = $this->paymentConfig->getActiveMethods();
        $methodInfo = $this->paymentConfig->getMethodsInfo();

        $resultData = [];

        // let paymentId = code of gateway
        foreach ($activeMethods as $paymentCode => $paymentModel) {
            $paymentItem = [
                'title'     => $this->scopeConfig->getValue(
                    'payment/' . $paymentCode . '/title',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                ),
                'code'      => $paymentCode,
                'model'     => $paymentModel
            ];

            array_push($resultData, $paymentItem);
        }

        return [
            'active'    => $activeMethods,
            'info'      => $methodInfo,
            'resultData'    => $resultData
        ];
    }

    public function getPaymentMetaByKey($paymentKey) {
//        $this->paymentConfig

    }
}