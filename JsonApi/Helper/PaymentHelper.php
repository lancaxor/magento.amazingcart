<?php
/**
 * Created by PhpStorm.
 * User: serf
 * Date: 20.09.16
 * Time: 18:30
 */

namespace Amazingcard\JsonApi\Helper;

use Amazingcard\JsonApi\Helper\Payment\AuthorizeNetHelper;
use Amazingcard\JsonApi\Helper\Payment\PaypalHelper;
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
     * @var AuthorizeNetHelper
     */
    protected $authorizeNetHelper;

    /**
     * @var PaypalHelper
     */
    protected $paypalHelper;

    public function __construct(
//        MethodInterface $paymentMethod,
        PaymentFactory $paymentFactory,
        \Magento\Sales\Model\ResourceModel\Order\Payment\CollectionFactory $paymentCollectionFactory,
        \Magento\Payment\Model\Config $paymentConfig,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        AuthorizeNetHelper $authorizeNetHelper,
        PaypalHelper $paypalHelper
    )
    {
//        $this->paymentMethod = $paymentMethod;
        $this->paymentFactory = $paymentFactory;
        $this->paymentCollectionFactory = $paymentCollectionFactory;
        $this->paymentConfig = $paymentConfig;
        $this->scopeConfig = $scopeConfig;
        $this->authorizeNetHelper = $authorizeNetHelper;
        $this->paypalHelper = $paypalHelper;
    }



    public function getPayPalCheckoutRedirectUrl($quote, $methodId) {
        return $this->paypalHelper->getCheckoutRedirectUrl($quote, $methodId);
    }

    public function getAuthorizeNetCheckoutRedirectUrl($quote, $methodId) {
        return $this->authorizeNetHelper->getCheckoutRedirectUrl($quote, $methodId);
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

        $data = [];

        /** @var \Magento\Quote\Model\Quote\Payment $payment */
        foreach ($collection as $payment) {

            $dataItem = [
                'id'            => $payment->getMethod(),
                'title'         => $this->getPaymentTitle($payment->getMethod()),
                'description'   => $payment->getAdditionalInformation(),        // magento has no payment description...
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

        /** @var \Magento\Sales\Model\Order\Payment $payment **/
        foreach($collection as &$payment) {
//            $payment->title = $this->getPaymentTitle($payment->getMethod());
            $payment->setData('title', $this->getPaymentTitle($payment->getMethod()));
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
                'title'     => $this->getPaymentTitle($paymentCode),
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

    public function getPaymentTitle($paymentCode) {
        return $this->scopeConfig->getValue(
            'payment/' . $paymentCode . '/title',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getPaymentMetaByKey($paymentKey) {
        return [
            'key'       => $paymentKey,
            'safari'    => 0,
            'hideit'    => 0
        ];
    }
}