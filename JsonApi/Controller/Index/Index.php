<?php
/**
 * Created by PhpStorm.
 * User: serf
 * Date: 31.08.16
 * Time: 18:16
 */

namespace Amazingcard\JsonApi\Controller\Index;

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

    /**
     * @var JsonFactory
    */
    protected $_resultJsonFactory;

    /**
     * @var \Amazingcard\JsonApi\Model\Catalog\Category\Factory\VarcharFactory
    */
    protected $_catalogCategoryVarcharFactory;

    /**
     * @var  \Amazingcard\JsonApi\Model\Catalog\Category\Factory\ProductFactory
    */
    protected $_catalogCategoryProductFactory;

    /**
     * @var  \Magento\Catalog\Model\CategoryFactory
    */
    protected $_coreCategoryFactory;

    /**
     * @var  \Amazingcard\JsonApi\Model\Catalog\Product\Factory\EntityFactory
    */
    protected $_productEntityFactory;

    /**
     * @var  \Amazingcard\JsonApi\Model\Catalog\Category\Factory\EntityFactory
    */
    protected $_catalogCategoryFactory;

    /**
     * @var \Amazingcard\JsonApi\Model\OverrideCore\Factory\ReviewFactory
     */
    protected $_coreReviewFactory;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $_coreCustomerFactory;

    /**
     * @var \Magento\Directory\Model\CountryFactory
     */
    protected $_coreCountryFactory;

    /**
     * Casting output to specified format
     * @var \Amazingcard\JsonApi\Helper\ResponseFormatter
     */
    protected $_responseFormatter;

    /**
     * @var \Amazingcard\JsonApi\Helper\User
     */
    protected $_userHelper;

    /**
     * @var \Amazingcard\JsonApi\Helper\Review
     */
    protected $_reviewHelper;

    /**
     * @var \Amazingcard\JsonApi\Helper\Api
     */
    protected $_apiHelper;

    /**
     * @var UrlWorker
     */
    protected $_urlWorker;

    protected $_coreProductCollection;
    /**
     * Index constructor.
     *
     * @param Context                                                            $context
     * @param JsonFactory                                                        $resultJsonFactory
     * @param \Amazingcard\JsonApi\Model\Catalog\Category\Factory\TextFactory    $textFactory
     * @param \Amazingcard\JsonApi\Model\Catalog\Category\Factory\ProductFactory $productFactory
     *
     * @SuppressWarnings("UnnecessaryFullyQualifiedName")
     */
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
        \Amazingcard\JsonApi\Helper\User    $userHelper,
        \Amazingcard\JsonApi\Helper\Review  $reviewHelper,
        \Amazingcard\JsonApi\Helper\Api $apiHelper,
        UrlWorker $urlWorker
    ) {
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->_catalogCategoryVarcharFactory = $varcharFactory;
        $this->_catalogCategoryProductFactory = $productFactory;
        $this->_productEntityFactory = $productEntityFactory;
        $this->_coreCategoryFactory = $coreCategoryFactory;
        $this->_catalogCategoryFactory = $categoryEntityFactory;
        $this->_coreReviewFactory = $coreReviewFactory;
        $this->_coreCustomerFactory = $coreCustomerFactory;
        $this->_coreCountryFactory = $coreCountryFactory;
        $this->_responseFormatter = $responseFormatter;
        $this->_userHelper = $userHelper;
        $this->_reviewHelper = $reviewHelper;
        $this->_apiHelper = $apiHelper;
        $this->_urlWorker = $urlWorker;
        $this->_coreProductCollection = $coreProductCollection;
        parent::__construct($context);
    }

    public function execute() 
    {

        /**
         * @var \Magento\Framework\App\RequestInterface $request
        */
        $request = $this->getRequest();
        $type = $request->getParam('type', null);

        /**
         * a bit of the reflection is here!
         * all function MUST be called like decoded by UrlWorker type in GET param.
         * If need -- we can do smth like 'strict mode' -- check for allowed actions
         * before processing them...
         * @see UrlWorker
         */
        $functionName = $this->_urlWorker
            ->setRawUrl($type)
            ->decodeUrl(UrlWorker::TYPE_PROTECTED_METHOD)
            ->getDecodedUrl();

        if(method_exists($this, $functionName)) {
            $data = call_user_func(array($this, $functionName), $request);
        } else {
            $data = $this->_responseFormatter->formatError('unknown command', 1);
        }

        if(!isset($data)) {
            $data = [];
        }

        $resultJson = $this->_resultJsonFactory->create();
        return $resultJson->setData($data);
    }

    /**
     * Get categories of products. 'parent' is not required
     * @param $request \Magento\Framework\App\RequestInterface
     * @return array
     */
    protected function _productCategories($request) {
        $parent = $request->getParam('parent', 0);

        // full list of categories
        $object = $this->_catalogCategoryFactory->getObject();
        $categories = $object->getCategories();
        return $this->_responseFormatter->formatProductCategories($categories, $parent);
    }

    /**
     * @param $request \Magento\Framework\App\RequestInterface
     * @return mixed
     */
    protected function _productByCategoryId($request)
    {
        $categoryId = $request->getParam('category_id', null);

        if(!isset($categoryId)) {
            return $this->_responseFormatter->formatError('CategoryID is required!', 6);
        }

        $pager = $this->_extractPager($request, 0);
        $categoryInfo = $this->_catalogCategoryVarcharFactory->getObject()->getNames($categoryId);

        if(empty($categoryInfo)) {
            return $this->_responseFormatter->formatError('Specified category does not exist!', 4);
        }

        $rawProductData = $this->_catalogCategoryProductFactory->getObject()->getProductsByCategory($categoryId, $pager['limit'], $pager['offset']);
        $products = $rawProductData['data'];
        $pager['count'] = $rawProductData['count'];

        return $this->_responseFormatter->formatCategoryByProductId($categoryInfo, $products, $pager);
    }

    /**
     * Extracting offset\limit values from request
     * @param $request \Magento\Framework\App\RequestInterface
     * @param $defaultPageSize integer
     * @param $pageParamName    string
     * @param $pageSizeParamName string
     * @return array
     */
    protected function _extractPager($request, $defaultPageSize = 0, $pageParamName = 'page', $pageSizeParamName = 'page_size') {
        $pageValue = $request->getParam($pageParamName, 0);
        $pageSizeValue = $request->getParam($pageSizeParamName, $defaultPageSize);

        return [
            'limit'     => $pageSizeValue,
            'offset'    => ($pageSizeValue * $pageValue),
            'page'      => $pageValue
        ];
    }

    /**
     * @param $request \Magento\Framework\App\RequestInterface
     * @return array
     */
    protected function _singleProduct($request)
    {
        $productId = $request->getParam('id', 0);
        $product = $this->_productEntityFactory->getObject()->getProductById($productId);
        return $this->_responseFormatter->formatProductById($product);
    }

    /**
     * @param $request \Magento\Framework\App\RequestInterface
     * @return array
     */
    protected function _searchProduct($request) 
    {
        $keyword = $request->getParam('keyword', null);
        $defaultPagerLimit = 50;

        if (!$keyword) {
            return $this->_responseFormatter->formatError('Keyword was not specified!', 3);
        }

        $pagerData = $this->_extractPager($request, $defaultPagerLimit);
        $productsInfo = $this->_productEntityFactory->getObject()->getProductsByNameTemplate($keyword, $pagerData['limit'], $pagerData['offset']);
        return $this->_responseFormatter->formatSearchProduct($keyword, $pagerData, $productsInfo);
    }

    /**
     * @param $request \Magento\Framework\App\RequestInterface
     * @return array
     */
    protected function _commentByPostId($request)
    {

        $productId = $request->getParam('id');

        if (!isset($productId)) {
            return $this->_responseFormatter->formatError('Id value is required!', 6);
        }

        /**
         * @var \Amazingcard\JsonApi\Model\OverrideCore\Review
         */
        $model = $this->_coreReviewFactory->create();
        $data = $model->getList($productId);
        return $this->_responseFormatter->formatReviewsByProduct($data, $productId);
    }

    /**
     * Just static data from http://wpsite/?amazingcart=json-api&type=countries
     * @return array
     */
    protected function _countries() {

        $countryModel = $this->_coreCountryFactory->create();
        $countryResourceCollection = $countryModel->getResourceCollection();
        $countries = $countryResourceCollection->load()->getData();

        foreach($countries as $key => &$country) {

            $countryModel = $this->_coreCountryFactory->create();
            $countryModel->setId($country['country_id']);
            $country['country_name'] = $countryModel->getName();
            unset($countryModel);   // cleanup to prevent memory leak
        }
        unset($country);

        $countryModel = $this->_coreCountryFactory->create();   // clear countryID
        $regions = $countryModel->getRegions();

        return $this->_responseFormatter->formatCountries($countries, $regions);
    }

    /**
     * Login user. 'username' and 'password' parameters are required.
     * @param $request \Magento\Framework\App\RequestInterface
     * @return array
     */
    protected function _userLogin($request) {
        $loginRequestName = 'username';
        $passwordRequestName = 'password';
        $login = $request->getParam($loginRequestName);
        $password = $request->getParam($passwordRequestName);

        if(!isset($login) || !isset($password)) {
            return $this->_responseFormatter->formatError('No input', -1);
        }

        $loginResponse = $this->_userHelper->login($login, $password);
        //$model = $this->_coreCustomerFactory->create();

        return $this->_responseFormatter->formatLoginUser($loginResponse);
    }

    /**
     * Logout user. Fields 'username' and 'password' are required.
     * @param $request \Magento\Framework\App\RequestInterface
     * @return array
     */
    protected function _userLogout($request) {

        $this->_userHelper->logout();
        return $this->_responseFormatter->formatLogoutUser();
    }

    /**
     * Updating user's first_name, last_name, email
     * display_name is useless, so it won't be changed
     * @param $request \Magento\Framework\App\RequestInterface
     * @return array
     */
    protected function _userProfileUpdate($request) {
        $firstName = $request->getParam('first_name');
        $lastName = $request->getParam('last_name');
        $email = $request->getParam('email');

        $updateProfileData = $this->_userHelper->updateProfile($firstName, $lastName, $email);
        return $this->_responseFormatter->formatEditUserData($updateProfileData);
    }

    /**
     * @param $request \Magento\Framework\App\RequestInterface
     * @return array
     */
    protected function _userBillingUpdate($request) {

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
        $updateBillingInfo = $this->_userHelper->updateBilling($login, $password, $billingData);
        return $this->_responseFormatter->formatUserBilling($updateBillingInfo);
    }

    /**
     * @param $request \Magento\Framework\App\RequestInterface
     * @return array
     */
    protected function _userShippingUpdate($request) {
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

        $updateShippingInfo = $this->_userHelper->updateShipping($login, $password, $shippingData);
        return $this->_responseFormatter->formatUserShipping($updateShippingInfo);
    }

    /**
     * @param $request \Magento\Framework\App\RequestInterface
     * @return array
     */
    protected function _userPostComment($request) {
        $userName = $request->getParam('username');
        $password = $request->getParam('password');
        $reviewData = [
            'comment'   => $request->getParam('comment'),
            'productId' => $request->getParam('postID'),
            'rating'    => $request->getParam('starRating')
        ];
        $reviewInfo = $this->_reviewHelper->addReview($userName, $password, $reviewData);
        return $reviewInfo;
    }

    /**
     * @param $request \Magento\Framework\App\RequestInterface
     * @return array
     */
    protected function _cartApi($request) {
        $userName = $request->getParam('username');
        $password = $request->getParam('password');
        $productIdJson = $request->getParam('productIDJson');
        $couponIdJson = $request->getParam('couponCodeJson');
        $cartInfo = $this->_apiHelper->cartApi($userName, $password, $productIdJson, $couponIdJson);
        return $this->_responseFormatter->formatCartApi($cartInfo);
    }

    /**
     * @param $request \Magento\Framework\App\RequestInterface
     * @return array
     */
    protected function _placeAnOrderApi($request) {
        $username = $request->getParam('username');
        $password = $request->getParam('password');
        $orderData = [
            'productJson' => $request->getParam('productIDJson'),
            'couponCodeJson' => $request->getParam('couponCodeJson'),
            'paymentMethodId' => $request->getParam('paymentMethodID'),
            'orderNotes' => $request->getParam('orderNotes')
        ];

        $placedOrderInfo = $this->_apiHelper->placeOrder($username, $password, $orderData);
        return $this->_responseFormatter->formatPlaceOrderApi($placedOrderInfo);
    }

    /**
     * @param $request \Magento\Framework\App\RequestInterface
     * @return array
     */
    protected function _mobilePaymentRedirectApi($request) {
        return [];
    }

    /**
     * @param $request \Magento\Framework\App\RequestInterface
     * @return array
     */
    protected function _mobilePaymentRedirectAuthorizeDotNetApi($request) {
        return [];
    }

    /**
     * @param $request \Magento\Framework\App\RequestInterface
     * @return array
     */
    protected function _getSettings($request) {
        return $this->_responseFormatter->formatSettings();
    }

    /**
     * @param $request \Magento\Framework\App\RequestInterface
     * @return array
     */
    protected function _getOrder($request) {
        return [];
    }

    /**
     * @param $request \Magento\Framework\App\RequestInterface
     * @return array
     */
    protected function _getFeaturedProduct($request) {

        $data = $this->_coreProductCollection->create()
            ->addAttributeToFilter('is_featured', 1)
            ->load()
            ->getData();

        die(var_dump($data));
        return [];
    }

    /**
     * @param $request \Magento\Framework\App\RequestInterface
     * @return array
     */
    protected function _getRecentItems($request) {
        $currentPage = $request->getParam('current_page', 1);
        $postPerPage = $request->getParam('post_per_page', 10);
        $order = $request->getParam('order', 'desc');

//        $this->_product
        return $this->_responseFormatter->formatRecentProducts([]);
    }

    /**
     * @param $request \Magento\Framework\App\RequestInterface
     * @return array
     */
    protected function _getRandomItems($request) {
        $currentPage = $request->getParam('current_page', 1);
        $postPerPage = $request->getParam('post_per_page', 10);
        $order = $request->getParam('order', 'desc');

        $pager = $this->_extractPager($request, 10, 'current_page', 'post_per_page');

        $model = $this->_productEntityFactory->getObject();
        /** @var BaseAbstractResourceModel $resource */
        $resource = $model->getResource();
        $productsInfo = $resource->setLimitOffset($pager['limit'], $pager['offset'])
            ->setWithCount(true)
            ->setOrder(['entity_id' => $order])
            ->load($model);

        die(var_dump($productsInfo));

    }

    /**
     * @param $request \Magento\Framework\App\RequestInterface
     * @return array
     */
    protected function _getMyOrder($request) {
        return [];
    }

    /**
     * @param $request \Magento\Framework\App\RequestInterface
     * @return array
     */
    protected function _userRegistrationPlaceorderapi($request) {
        return [];
    }


    /**
     * @param $request \Magento\Framework\App\RequestInterface
     * @return array
     */
    protected function _changePassword($request) {
        $username = $request->getParam('username');
        $currentPassword = $request->getParam('currentpassword');
        $newPassword = $request->getParam('newpassword');
        return $this->_userHelper->changePassword($username, $currentPassword, $newPassword);
    }

    /**
     * @param $request \Magento\Framework\App\RequestInterface
     * @return array
     */
    protected function _getCommentById($request) {
        $commentId = $request->getParam('commentID');
        $reviewInfo = $this->_reviewHelper->getReviewById($commentId);
        return $this->_responseFormatter->formatSingleReview($reviewInfo);
    }

    /**
     * @param $request \Magento\Framework\App\RequestInterface
     * @return array
     */
    protected function _getSinglePaymentGatewayMeta($request) {
        return [];
    }
}