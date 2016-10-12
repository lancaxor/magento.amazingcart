<?php
/**
 * Created by PhpStorm.
 * User: serf
 * Date: 31.08.16
 * Time: 18:16
 *
 * Controller class for Amazingcard API
 */

namespace Amazingcard\JsonApi\Controller\Index;

use Amazingcard\JsonApi\Helper\Logger;
use Amazingcard\JsonApi\Helper\Pager;
use Amazingcard\JsonApi\Helper\PaymentHelper;
use Amazingcard\JsonApi\Helper\Setting;
use Amazingcard\JsonApi\Helper\Settings;
use Amazingcard\JsonApi\Helper\UrlWorker;
use Amazingcard\JsonApi\Model\Base\BaseAbstractResourceModel;
use Amazingcard\JsonApi\Model\Catalog\Category\Factory\VarcharFactory;
use Amazingcard\JsonApi\Model\OverrideCore\Factory\ReviewFactory;
use Magento\CatalogImportExport\Model\Import\Proxy\Product\ResourceModel;
use Magento\Directory\Model\CountryFactory;
use \Magento\Framework\App\Action\Action;
use \Magento\Framework\App\Action\Context;
use \Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\ResponseInterface;

class Index extends Action
{

    /**#@+
     * service variables
     */
    private $activeLogger = true;
    private $logRequest = true;
    private $logResponse = true;
    private $logResult = true;
    /**#@-*/

    /**
     * @var JsonFactory
    */
    protected $resultJsonFactory;

    /**
     * @var \Amazingcard\JsonApi\Model\Catalog\Category\Factory\VarcharFactory
    */
    protected $catalogCategoryVarcharFactory;

    /**
     * @var  \Amazingcard\JsonApi\Model\Catalog\Category\Factory\ProductFactory
    */
    protected $catalogCategoryProductFactory;

    /**
     * @var  \Magento\Catalog\Model\CategoryFactory
    */
    protected $coreCategoryFactory;

    /**
     * @var  \Amazingcard\JsonApi\Model\Catalog\Product\Factory\EntityFactory
    */
    protected $productEntityFactory;

    /**
     * @var  \Amazingcard\JsonApi\Model\Catalog\Category\Factory\EntityFactory
    */
    protected $catalogCategoryFactory;

    /**
     * @var \Amazingcard\JsonApi\Model\OverrideCore\Factory\ReviewFactory
     */
    protected $coreReviewFactory;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $coreCustomerFactory;

    /**
     * @var \Magento\Directory\Model\CountryFactory
     */
    protected $coreCountryFactory;

    /**
     * Casting output to specified format
     * @var \Amazingcard\JsonApi\Helper\ResponseFormatter
     */
    protected $responseFormatter;

    /**
     * @var \Amazingcard\JsonApi\Helper\User
     */
    protected $userHelper;

    /**
     * @var \Amazingcard\JsonApi\Helper\Review
     */
    protected $reviewHelper;

    /**
     * @var \Amazingcard\JsonApi\Helper\Quote
     */
    protected $quoteHelper;

    /**
     * @var UrlWorker
     */
    protected $urlWorker;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $coreProductCollection;

    /**
     * @var \Amazingcard\JsonApi\Helper\Product
     */
    protected $productHelper;

    /**
     * @var \Amazingcard\JsonApi\Helper\Pager
     */
    protected $pagerHelper;

    /**
     * @var \Amazingcard\JsonApi\Helper\Order
     */
    protected $orderHelper;

    /**
     * @var PaymentHelper
     */
    protected $paymentHelper;

    /**
     * @var Settings
     */
    protected $settingsHelper;

    /**
     * @var Logger
     */
    protected $loggerHelper;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        \Amazingcard\JsonApi\Model\Catalog\Category\Factory\VarcharFactory $varcharFactory,
        \Amazingcard\JsonApi\Model\Catalog\Category\Factory\ProductFactory $productFactory,
        \Amazingcard\JsonApi\Model\Catalog\Product\Factory\EntityFactory $productEntityFactory,
        \Amazingcard\JsonApi\Model\Catalog\Category\Factory\EntityFactory $categoryEntityFactory,
        \Amazingcard\JsonApi\Model\OverrideCore\Factory\ReviewFactory $coreReviewFactory,
        \Magento\Catalog\Model\CategoryFactory $coreCategoryFactory,
        \Magento\Customer\Model\CustomerFactory $coreCustomerFactory,
        \Magento\Directory\Model\CountryFactory $coreCountryFactory,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $coreProductCollection,
        \Amazingcard\JsonApi\Helper\ResponseFormatter $responseFormatter,
        \Amazingcard\JsonApi\Helper\User $userHelper,
        \Amazingcard\JsonApi\Helper\Review $reviewHelper,
        \Amazingcard\JsonApi\Helper\Quote $apiHelper,
        \Amazingcard\JsonApi\Helper\Product $productHelper,
        \Amazingcard\JsonApi\Helper\Order $orderHelper,
        Pager $pagerHelper,
        PaymentHelper $paymentHelper,
        UrlWorker $urlWorker,
        Settings $settingsHelper,
        Logger $loggerHelper
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->catalogCategoryVarcharFactory = $varcharFactory;
        $this->catalogCategoryProductFactory = $productFactory;
        $this->productEntityFactory = $productEntityFactory;
        $this->coreCategoryFactory = $coreCategoryFactory;
        $this->catalogCategoryFactory = $categoryEntityFactory;
        $this->coreReviewFactory = $coreReviewFactory;
        $this->coreCustomerFactory = $coreCustomerFactory;
        $this->coreCountryFactory = $coreCountryFactory;
        $this->responseFormatter = $responseFormatter;
        $this->userHelper = $userHelper;
        $this->reviewHelper = $reviewHelper;
        $this->productHelper = $productHelper;
        $this->quoteHelper = $apiHelper;
        $this->urlWorker = $urlWorker;
        $this->coreProductCollection = $coreProductCollection;
        $this->pagerHelper = $pagerHelper;
        $this->orderHelper = $orderHelper;
        $this->paymentHelper = $paymentHelper;
        $this->settingsHelper = $settingsHelper;
        $this->loggerHelper = $loggerHelper;

        parent::__construct($context);

        $this->initialize();
    }

    /**
     * Do actions to init objects
     */
    public function initialize() {
        $this->pagerHelper->setStrict(true);
        $this->loggerHelper
            ->setOrder(Logger::LOG_ORDER_REVERSE)
            ->enable($this->activeLogger);
    }

    /**
     * Main action method
     * @return mixed
     */
    public function execute() 
    {

        /** @var \Magento\Framework\App\RequestInterface $request */
        $request = $this->getRequest();
        $type = $request->getParam('type', null);
        $loggingEnabled = $request->getParam('logging');
        if(isset($loggingEnabled)) {
            $loggingEnabled = boolval($loggingEnabled);
            $this->activeLogger = $loggingEnabled;
            $this->loggerHelper->enable($loggingEnabled);
        }

        if($this->logRequest) {
            $this->loggerHelper->addMessage($request->getActionName());
            $this->loggerHelper->addMessage($request->getParams(), Logger::LOG_TYPE_DATA);
        }

        /**
         * a bit of the reflection is here!
         * all function MUST be called like decoded by UrlWorker type in GET param.
         * If need -- we can do smth like 'strict mode' -- check for allowed actions
         * before processing them...
         * @see UrlWorker
         */
        $functionName = $this->urlWorker
            ->setRawUrl($type)
            ->decodeUrl(UrlWorker::TYPE_PUBLIC_METHOD)
            ->getDecodedUrl();

        if(method_exists($this, $functionName)) {
            $data = call_user_func(array($this, $functionName), $request);
        } else {
            $data = $this->responseFormatter->formatError('unknown command', 1);
        }

        if(!isset($data)) {
            $data = [];
        }

        if($this->logResult) {
            $this->loggerHelper->addMessage($data, Logger::LOG_TYPE_DATA);
        }

        $resultJson = $this->resultJsonFactory->create();
        return $resultJson->setData($data);
    }

    /**
     * Get categories of products. 'parent' is not required
     * @param $request \Magento\Framework\App\RequestInterface
     * @return array
     */
    protected function productCategories($request) {
        $parent = $request->getParam('parent', 0);

        // full list of categories
        $object = $this->catalogCategoryFactory->getObject();
        $categories = $object->getCategories();
        $data = $this->responseFormatter->formatProductCategories($categories, $parent);
        return $data;
    }

    /**
     * Get specified category and related products
     *
     * @param $request \Magento\Framework\App\RequestInterface
     * @return mixed
     */
    protected function productByCategoryId($request)
    {
        $categoryId = $request->getParam('category_id', null);

        if(!isset($categoryId)) {
            return $this->responseFormatter->formatError('CategoryID is required!', 6);
        }

        $this->pagerHelper->setPage($request->getParam('page', 0))
            ->setPageSize($request->getParam('products-per-page', 10));

        $categoryInfo = $this->catalogCategoryVarcharFactory->getObject()->getNamesAndSlugs($categoryId);

        if(empty($categoryInfo)) {
            return $this->responseFormatter->formatError('Specified category does not exist!', 4);
        }

        $rawProductData = $this->catalogCategoryProductFactory->getObject()
            ->getProductsByCategory($categoryId, $this->pagerHelper->getLimit(), $this->pagerHelper->getOffset());
        $this->pagerHelper->setTotalCount($rawProductData['count']);
        $products = $rawProductData['data'];

        return $this->responseFormatter->formatCategoryByProductId($categoryInfo, $products, $this->pagerHelper);
    }

    /**
     * Get single product by specified ID.
     *
     * @param $request \Magento\Framework\App\RequestInterface
     * @return array
     */
    protected function singleProduct($request)
    {
        $productId = $request->getParam('id', 0);
//        $product = $this->productEntityFactory->getObject()->getProductById($productId);

        if(!$productId) {
            return [
                'error'     => -1,
                'reason'    => 'id is required!'
            ];
        }
        $productInfo = $this->productHelper->getSingleProduct($productId);
        $data = [
            $this->responseFormatter->formatProductById($productInfo['product'], $productInfo['categories'])
        ];
//        die(var_dump($data));
        return $data;
    }

    /**
     * Search product by keyword. Pager parameters 'page' and 'products-per-page' are not required.
     *
     * @param $request \Magento\Framework\App\RequestInterface
     * @return array
     */
    protected function searchProduct($request)
    {
        $keyword = $request->getParam('keyword', null);

        if (!$keyword) {
            return $this->responseFormatter->formatError('Keyword was not specified!', 3);
        }

        $this->pagerHelper->setPage($request->getParam('page', 0))
            ->setPageSize($request->getParam('products-per-page', 10));

        $productsInfo = $this->productEntityFactory->getObject()
            ->getProductsByNameTemplate($keyword, $this->pagerHelper->getLimit(), $this->pagerHelper->getOffset());

        $this->pagerHelper->setTotalCount(isset($productsInfo['count']) ? $productsInfo['count'] : 0);
        return $this->responseFormatter->formatSearchProduct($keyword, $this->pagerHelper, $productsInfo);
    }

    /**
     * Magento has no comments (at least without using 3-rd party modules), so we'll use reviews.
     *
     * @param $request \Magento\Framework\App\RequestInterface
     * @return array
     */
    protected function commentByPostId($request)
    {

        $productId = $request->getParam('id');

        if (!isset($productId)) {
            return $this->responseFormatter->formatError('Id value is required!', 6);
        }

        /** @var $model \Amazingcard\JsonApi\Model\OverrideCore\Review */
        $model = $this->coreReviewFactory->create();
        $data = $model->getList($productId);
        return $this->responseFormatter->formatReviewsByProduct($data, $productId);
    }

    /**
     * Get countries and its states
     *
     * @return array
     */
    protected function countries() {

        $countryModel = $this->coreCountryFactory->create();
        $countryResourceCollection = $countryModel->getResourceCollection();
        $countries = $countryResourceCollection->load()->getData();

        foreach($countries as $key => &$country) {

            $countryModel = $this->coreCountryFactory->create();
            $countryModel->setId($country['country_id']);
            $country['country_name'] = $countryModel->getName();
            unset($countryModel);   // cleanup to prevent memory leak
        }
        unset($country);

        $countryModel = $this->coreCountryFactory->create();   // clear countryID
        $regions = $countryModel->getRegions();

        return $this->responseFormatter->formatCountries($countries, $regions);
    }

    /**
     * Login user. 'username' and 'password' parameters are required.
     *
     * @param $request \Magento\Framework\App\RequestInterface
     * @return array
     */
    protected function userLogin($request) {
        $loginRequestName = 'username';
        $passwordRequestName = 'password';
        $login = $request->getParam($loginRequestName);
        $password = $request->getParam($passwordRequestName);

        if(!isset($login) || !isset($password)) {
            return $this->responseFormatter->formatError('No input', -1);
        }

        $loginResponse = $this->userHelper->login($login, $password);
        //$model = $this->coreCustomerFactory->create();

        return $this->responseFormatter->formatLoginUser($loginResponse);
    }

    /**
     * Logout user. Fields 'username' and 'password' are required.
     *
     * @param $request \Magento\Framework\App\RequestInterface
     * @return array
     */
    protected function userLogout($request) {

        $this->userHelper->logout();
        return $this->responseFormatter->formatLogoutUser();
    }

    /**
     * Updating user's first_name, last_name, email
     *  display_name is useless, so it won't be changed
     *
     * @param $request \Magento\Framework\App\RequestInterface
     * @return array
     */
    protected function userProfileUpdate($request) {
        $firstName = $request->getParam('first_name');
        $lastName = $request->getParam('last_name');
        $email = $request->getParam('email');

        $updateProfileData = $this->userHelper->updateProfile($firstName, $lastName, $email);
        return $this->responseFormatter->formatEditUserData($updateProfileData);
    }

    /**
     * Update user billing address.
     *
     * @param $request \Magento\Framework\App\RequestInterface
     * @return array
     */
    protected function userBillingUpdate($request) {

        $login = $request->getParam('username');
        $password = $request->getParam('password');

        $billingData = [
            'billing_first_name'    => $request->getParam('billing_first_name'),
            'billing_last_name'     => $request->getParam('billing_last_name'),
            'billing_company'       => $request->getParam('billing_company'),
            'billing_address_1'     => $request->getParam('billing_address_1'),
            'billing_address_2'     => $request->getParam('billing_address_2'),
            'billing_city'          => $request->getParam('billing_city'),
            'billing_postcode'      => $request->getParam('billing_postcode'),
            'billing_state'         => $request->getParam('billing_state'),
            'billing_has_state'     => $request->getParam('billing_has_state'),
            'billing_country'       => $request->getParam('billing_country'),
            'billing_phone'         => $request->getParam('billing_phone'),
            'billing_email'         => $request->getParam('billing_email'),
        ];
        $updateBillingInfo = $this->userHelper->updateBilling($login, $password, $billingData);
        return $this->responseFormatter->formatUserBilling($updateBillingInfo);
    }

    /**
     * Update user shipping address. Email wont be used.
     *
     * @param $request \Magento\Framework\App\RequestInterface
     * @return array
     */
    protected function userShippingUpdate($request) {
        $login = $request->getParam('username');
        $password = $request->getParam('password');

        $shippingData = [
            'shipping_first_name'    => $request->getParam('shipping_first_name'),
            'shipping_last_name'     => $request->getParam('shipping_last_name'),
            'shipping_company'       => $request->getParam('shipping_company'),
            'shipping_address_1'     => $request->getParam('shipping_address_1'),
            'shipping_address_2'     => $request->getParam('shipping_address_2'),
            'shipping_city'          => $request->getParam('shipping_city'),
            'shipping_postcode'      => $request->getParam('shipping_postcode'),
            'shipping_state'         => $request->getParam('shipping_state'),
            'shipping_has_state'     => $request->getParam('shipping_has_state'),
            'shipping_country'       => $request->getParam('shipping_country'),
            'shipping_phone'         => $request->getParam('shipping_phone'),
            'shipping_email'         => $request->getParam('shipping_email'),
        ];

        $updateShippingInfo = $this->userHelper->updateShipping($login, $password, $shippingData);
        return $this->responseFormatter->formatUserShipping($updateShippingInfo);
    }

    /**
     * Magento has no comments, so it will post user instead.
     *
     * @param $request \Magento\Framework\App\RequestInterface
     * @return array
     */
    protected function userPostComment($request) {
        $userName = $request->getParam('username');
        $password = $request->getParam('password');
        $reviewData = [
            'comment'   => $request->getParam('comment'),
            'productId' => $request->getParam('postID'),
            'rating'    => $request->getParam('starRating')
        ];
        $reviewInfo = $this->reviewHelper->addReview($userName, $password, $reviewData);
        return $reviewInfo;
    }

    /**
     * Add products and coupons to cart.
     *
     * @param $request \Magento\Framework\App\RequestInterface
     * @return array
     */
    protected function cartApi($request) {
        $userName = $request->getParam('username');
        $password = $request->getParam('password');
        $productIdJson = $request->getParam('productIDJson');
        $couponIdJson = $request->getParam('couponCodeJson');
        $cartInfo = $this->quoteHelper->cartApi($userName, $password, $productIdJson, $couponIdJson);
        return $this->responseFormatter->formatCartApi($cartInfo);
    }

    /**
     * Add products and coupons to cart and submit related order.
     *
     * @param $request \Magento\Framework\App\RequestInterface
     * @return array
     */
    protected function placeAnOrderApi($request) {
        $username = $request->getParam('username');
        $password = $request->getParam('password');
        $orderData = [
            'productJson' => $request->getParam('productIDJson'),
            'couponCodeJson' => $request->getParam('couponCodeJson'),
            'paymentMethodId' => $request->getParam('paymentMethodID'),
            'orderNotes' => $request->getParam('orderNotes')
        ];

        $placedOrderInfo = $this->orderHelper->placeOrder($username, $password, $orderData);

        return $this->responseFormatter->formatPlaceOrderApi($placedOrderInfo);
    }

    /**
     *
     *
     * @param $request \Magento\Framework\App\RequestInterface
     * @return array
     */
    protected function mobilePaymentRedirectApi($request) {
        $orderKey = $request->getParam('orderKey'); // useless in magento, i guess...
        $orderId = $request->getParam('orderID');
        $paymentMethodId = $request->getParam('paymentMethodID');   // payment code

        $errorMessages = [];
        if (!isset($orderId)) {
            $errorMessages[] = 'Missing required field orderID!';
        }
        if (!isset($paymentMethodId)) {
            $errorMessages[] = 'Missing required field paymentMethodID!';
        }

        if ($errorMessages) {
            return $this->responseFormatter->formatError(implode($errorMessages, '\n'), -1);
        }

        $quote = $this->orderHelper->getQuoteByOrderId($orderId);
        $url = $this->paymentHelper->getPayPalCheckoutRedirectUrl($quote, $paymentMethodId);
        die(var_dump($url));
        return [];
    }

    /**
     * @TODO: the same like prev function description
     *
     * @param $request \Magento\Framework\App\RequestInterface
     * @return array
     */
    protected function mobilePaymentRedirectAuthorizeDotNetApi($request) {
        $orderKey = $request->getParam('orderKey'); // idk what is this -_-
        $orderId = $request->getParam('orderID');
        $paymentMethodId = $request->getParam('paymentMethodID');   // payment code

        $errorMessage = '';
        if (!isset($orderId)) {
            $errorMessage .= 'Missing required field orderID!\n';
        }
        if (!isset($paymentMethodId)) {
            $errorMessage .= 'Missing required field paymentMethodID';
        }

        if ($errorMessage) {
            return $this->responseFormatter->formatError($errorMessage, -1);
        }

        $quote = $this->orderHelper->getQuoteByOrderId($orderId);
        $url = $this->paymentHelper->getAuthorizeNetCheckoutRedirectUrl($quote, $paymentMethodId);
        die(var_dump($url));
        return [];
    }

    /**
     * TODO: Get user settings (or app ones? Look....
     * @param $request \Magento\Framework\App\RequestInterface
     * @return array
     */
    protected function getSettings($request) {

        $settings = $this->settingsHelper->getSettings();
        return $this->responseFormatter->formatSettings($settings);
    }

    /**
     * Get order by specified orderID.
     *
     * @param $request \Magento\Framework\App\RequestInterface
     * @return array
     */
    protected function getOrder($request) {

        $userName = $request->getParam('username');
        $password = $request->getParam('password');
        $orderId = $request->getParam('orderID');

        $orderInfo = $this->orderHelper->getOrderById($userName, $password, $orderId);
        $formattedOrder = $this->responseFormatter->formatSingleOrder($orderInfo);
        return $formattedOrder;
    }

    /**
     * Return list of featured product.
     * TODO: use pager?
     * @param $request \Magento\Framework\App\RequestInterface
     * @return array
     */
    protected function getFeaturedProduct($request) {

        $data = $this->productHelper->getFeaturedProducts();
        return $this->responseFormatter->formatFeaturedProduct($data);
    }

    /**
     * Get recent products. 'current_page' and 'post_per_page' parameters should be used for paging.
     *
     * @param $request \Magento\Framework\App\RequestInterface
     * @return array
     */
    protected function getRecentItems($request) {
        $currentPage = $request->getParam('current_page', 0);
        $postPerPage = $request->getParam('post_per_page', 10);
        $order = $request->getParam('order', 'desc');

        $this->pagerHelper->setPageSize($postPerPage)
            ->setPage($currentPage);

        $order = mb_strtolower($order);

        // a bit of security
        if($order != 'asc' && $order != 'desc') {
            $order = 'desc';
        }
        $recentItems = $this->productHelper->getRecentProducts($this->pagerHelper, $order);
        $this->pagerHelper->setTotalCount($recentItems['count']);
        return $this->responseFormatter->formatRecentProducts($recentItems, $this->pagerHelper);
    }

    /**
     * Get random products. 'current_page' and 'post_per_page' parameters should be used for paging.
     *
     * @param $request \Magento\Framework\App\RequestInterface
     * @return array
     */
    protected function getRandomItems($request) {
        $currentPage = $request->getParam('current_page', 0);
        $postPerPage = $request->getParam('post_per_page', 10);
        $order = $request->getParam('order', 'desc');       // order for random? seriously? 0_o

        $this->pagerHelper->setPage($currentPage)
            ->setPageSize($postPerPage);

        $productInfo = $this->productHelper->getRandomProducts($this->pagerHelper, $order);
        $this->pagerHelper->setTotalCount($productInfo['count']);
        $data = $this->responseFormatter->formatRandomProducts($productInfo, $this->pagerHelper);
        return $data;
    }

    /**
     * @param $request \Magento\Framework\App\RequestInterface
     * @return array
     */
    protected function getMyOrder($request) {
        $username = $request->getParam('username');
        $password = $request->getParam('password');
        $filter = $request->getParam('filter');

        $userOrdersInfo = $this->orderHelper->getOrdersByUser($username, $password, $filter);
        $formattedOrders = $this->responseFormatter->formatMyOrders($userOrdersInfo);
        return $formattedOrders;
    }

    /**
     * @param $request \Magento\Framework\App\RequestInterface
     * @return array
     */
    protected function userRegistration($request) {
        $username = $request->getParam('username'); // in magento email is using instead of username, so this param is useless
        $email = $request->getParam('email');
        $firstName = $request->getParam('first_name');
        $lastName = $request->getParam('last_name');
        $password = $request->getParam('password');
        $deviceId = $request->getParam('deviceID'); // whatever is it...
        $registeredUserInfo = $this->userHelper->registerUser($email, $password, $firstName, $lastName);
        return $this->responseFormatter->formatUserRegistration($registeredUserInfo);
    }


    /**
     * Change user password.
     *
     * @param $request \Magento\Framework\App\RequestInterface
     * @return array
     */
    protected function changePassword($request) {
        $username = $request->getParam('username');
        $currentPassword = $request->getParam('currentpassword');
        $newPassword = $request->getParam('newpassword');
        return $this->userHelper->changePassword($username, $currentPassword, $newPassword);
    }

    /**
     * Get review (instead of comment because of magento architecture) by its ID.
     *
     * @param $request \Magento\Framework\App\RequestInterface
     * @return array
     */
    protected function getCommentById($request) {
        $commentId = $request->getParam('commentID');
        $reviewInfo = $this->reviewHelper->getReviewById($commentId);
        return $this->responseFormatter->formatSingleReview($reviewInfo);
    }

    /**
     * idk what the XPEH is this doing here, but we need to do it! >.<
     *
     * @param $request \Magento\Framework\App\RequestInterface
     * @return array
     */
    protected function getSinglePaymentGatewayMeta($request) {
        $key = $request->getParam('key');
        $paymentInfo = $this->paymentHelper->getPaymentMetaByKey($key);
        return $this->responseFormatter->formatGetSinglePaymentGatewayMeta($paymentInfo);
    }

    #region service actions

    /**
     * Get all items in specified cart
     *
     * @param $request \Magento\Framework\App\RequestInterface
     * @return array
     */
    protected function confirmPayment($request) {
        $paymentId = $request->getParam('paymentId');
        return [];
    }

    /**
     * Action for PayPal access_token redirect
     *
     * @param $request \Magento\Framework\App\RequestInterface
     * @return array
     */
    protected function getTokenCallback($request) {
        $accessToken = $request->getParam('access_token');
        return [];
    }

    /**
     * Get current user. Return false if user is not logged in, and user data, if he is.
     * @return array|bool
     */
    protected function isLoggedIn() {
        return $this->userHelper->getCurrentUser();
    }

    /**
     * Get all items in specified cart
     *
     * @param $request \Magento\Framework\App\RequestInterface
     * @return array
     */
    protected function getItemsInCart($request) {
        $cartId = $request->getParam('cartId');

        if(!isset($cartId)) {
            return [
                'error'     => -1,
                'reason'    => 'CartId is required!!1!'
            ];
        }
        return [];
//        return $this->quoteHelper->
    }

    protected function getPaymentList() {
        return $this->paymentHelper->getPaymentArray();
    }

    protected function getPaymentGateways() {
        return $this->paymentHelper->getPaymentGateways();
    }

    protected function ping() {
        $request = $this->getRequest();
        $param = $request->getParam('param', 0);
        return [
            'param'     => $param,
            'status'    => 1
        ];
    }
    #endregion
}