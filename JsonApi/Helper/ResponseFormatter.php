<?php
/**
 * Created by PhpStorm.
 * User: serf
 * Date: 07.09.16
 * Time: 16:07
 */

namespace Amazingcard\JsonApi\Helper;
use Magento\CatalogUrlRewrite\Model\ResourceModel\Category\ProductCollection;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * Class ResponseFormatter
 * Cast output to specified format.
 * The best way is just to create function to format each entity (product, order, e.t.c.)
 * and use it, but some functions require different fields for the same entity)
 * @package Amazingcard\JsonApi\Helper
 */
class ResponseFormatter
{

    /**
     * @var PaymentHelper
     */
    private $paymentHelper;

    public function __construct(
        PaymentHelper $paymentHelper
    )
    {
        $this->paymentHelper = $paymentHelper;
    }

    public function formatError($message, $code) {
        return [
            'status' => isset($code) ? $code : -1,
            'reason' => isset($message) ? $message : 'Unknown error'
        ];
    }
    /**
     * @param $productInfo
     * @param $categories array
     * @return array
     */
    public function formatProductById($productInfo, $categories = []) {

        if(empty($productInfo)) {
            return [];
        }
        if($productInfo instanceof \Magento\Catalog\Model\Product) {
            $formattedData = $this->formatSingleProduct($productInfo);
        } else {
            $formattedData = $this->formatSingleProductData($productInfo);
        }
        $formattedData['categories'] = !empty($categories) ?  $this->formatProductCategories($categories, 0, false) : [];
        return $formattedData;
    }

    /**
     * Get product categories (always your, captain)
     * @param $categoryList
     * @param $parent int Root category ID
     * @param $treeView boolean true for output category with subcategories
     * @return array
     */
    public function formatProductCategories($categoryList, $parent = 0, $treeView = true) {
        if ($treeView) {
            return $this->buildProductCategoriesTree($categoryList, $parent);
        }

        $result = [];
        foreach ($categoryList as $category) {

            $result[] = $this->formatSingleProductCategory($category);
        }
        return $result;
    }

    /**
     * @param $categoryInfo array(name, id)
     * @param $products
     * @param $pager \Amazingcard\JsonApi\Helper\Pager
     * @return array
     */
    public function formatCategoryByProductId($categoryInfo, $products, $pager) {

        return [
            'categoryID'    => $categoryInfo['id'],
            'categoryName'  => $categoryInfo['name'],
            'categorySlug'  => null,    // TODO: url-like name of category
            'current_page'  => $pager->getCurrentPage(),
            'post_per_page' => $pager->getPageSize(),
            'total_posts'   => $pager->getTotalItems(),
            'total_page'    => $pager->getTotalPages(),
            'products'      => $products,
        ];
    }

    /**
     * Formatting login user data
     *
     * @param $loginInfo array [
     * (int) error, (int) status,
     *  (array | string) data
     * ]
     * @return array
     */
    public function formatLoginUser($loginInfo) {

        $failedLogin = isset($loginInfo['error']);

        if(!$failedLogin) {

            /**
             * @var array $customer
             */
            $customer = $loginInfo['data'];

            return [
                'status'    => $loginInfo['status'],
                'reason'    => $loginInfo['reason'],
                'user'      => $this->formatCustomerData($customer)
            ];
        }

        // unsuccessful login
        return [
            'status'    => $loginInfo['error'],
            'reason'    => $loginInfo['reason']
        ];
    }

    /**
     * Converting CustomerInterface to array using specified format
     * @param $customerInfo array(
     *      \Magento\Customer\Api\Data\CustomerInterface customer,
     *      \Magento\Customer\Api\Data\AddressInterface billing,
     *      \Magento\Customer\Api\Data\AddressInterface shipping
     * )
     * @return array
     */
    public function formatCustomerData($customerInfo) {

        $customer = isset($customerInfo['customer'])? $customerInfo['customer'] : [];

        if(!($customer instanceof \Magento\Customer\Api\Data\CustomerInterface)) {
            return [];
        }

        $strCreatedDate = $customer->getCreatedAt();
        $createdDate = new \DateTime($strCreatedDate);
        $createdDateTimestamp = $createdDate->getTimestamp();

        $correctShipping = (
            isset($customerInfo['shipping']) &&
            $customerInfo['shipping'] instanceof \Magento\Customer\Api\Data\AddressInterface
        );

        if($correctShipping) {
            $shipping = $customerInfo['shipping'];
            $shippingRegion = $shipping->getRegion();
        }

        $formattedShipping = [
            'shipping_first_name' => $correctShipping && $shipping->getFirstname() ? $shipping->getFirstname() : '',
            'shipping_last_name' => $correctShipping && $shipping->getLastname() ? $shipping->getLastname() : '',
            'shipping_company' => $correctShipping && $shipping->getCompany() ? $shipping->getCompany() : '',
            'shipping_address_1' => ($correctShipping && $shipping->getStreet()) ? implode(', ', $shipping->getStreet()) : '',
            'shipping_address_2' => '',
            'shipping_city' => $correctShipping && $shipping->getCity() ? $shipping->getCity() : '',
            'shipping_postcode' => $correctShipping && $shipping->getPostcode() ? $shipping->getPostcode() : '',
            'shipping_state' => isset($shippingRegion) ? $shippingRegion->getRegion() : '',
            'shipping_state_code' => isset($shippingRegion) && $shippingRegion->getRegionCode() ? $shippingRegion->getRegionCode() : '',
            'shipping_has_state' => isset($shippingRegion),
            'shipping_country' => '',
            'shipping_country_code' => $correctShipping && $shipping->getCountryId() ? $shipping->getCountryId() : '',
            'shipping_phone'     =>  $correctShipping && $shipping->getTelephone() ? $shipping->getTelephone() : '',
            'shipping_email'     =>  ''      // reserved
        ];
//        $shipping = isset($customerInfo['shipping']) ? $customerInfo['shipping'] : [];
//        if($shipping instanceof \Magento\Customer\Api\Data\AddressInterface) {
//            $shippingRegion = $shipping->getRegion();
//
//        }
        unset($shipping);

        $correctBilling = (
            isset($customerInfo['shipping']) &&
            $customerInfo['shipping'] instanceof \Magento\Customer\Api\Data\AddressInterface
        );

        if($correctBilling) {
            $billing = $customerInfo['shipping'];
            $billingRegion = $billing->getRegion();
        }
        $formattedBilling = [
            'billing_first_name'    => $correctBilling && $billing->getFirstname() ? $billing->getFirstname() : '',
            'billing_last_name'     => $correctBilling && $billing->getLastname() ? $billing->getLastname() : '',
            'billing_company'       => $correctBilling && $billing->getCompany() ? $billing->getCompany() : '',
            'billing_address_1'     => ($correctBilling && $billing->getStreet()) ? implode(', ', $billing->getStreet()) : '',
            'billing_address_2'     => '',       // reserved
            'billing_city'          => $correctBilling && $billing->getCity() ? $billing->getCity() : '',
            'billing_postcode'      => $correctBilling && $billing->getPostcode() ? $billing->getPostcode() : '',
            'billing_state'         => isset($billingRegion) && $billingRegion->getRegion() ? $billingRegion->getRegion() : '',
            'billing_state_code'    => isset($billingRegion) && $billingRegion->getRegionCode()? $billingRegion->getRegionCode() : '',
            'billing_has_state'     => isset($billingRegion),
            'billing_country'       => '',      // reserved
            'billing_country_code'  =>$correctBilling && $billing->getCountryId() ? $billing->getCountryId() : '',
            'billing_phone'     =>  $correctBilling && $billing->getTelephone() ? $billing->getTelephone() : '',
            'billing_email'     =>  ''      // reserved
        ];
        unset($billing);

        return [
            'ID'    => $customer->getId(),
            'user_login'    => $customer->getEmail(),
            'avatar'        => null,    // in magento user has no avatar (excluding ones provided by external modules/plugins)
            'first_name'    => $customer->getFirstname(),
            'last_name'     => $customer->getLastname(),
            'email'         => $customer->getEmail(),
            'user_nicename' => $customer->getFirstname(),       // idk what is this, but it is like nickname
            'user_nickname' => $customer->getFirstname(),
            'user_status'   => 0,
            'order_count'   => 0,       // TODO: check it
            'credit_card_management_aut_net'    => [],
            'user_registered' => [
                'db_format'     => $createdDate->format('Y-m-d H:i:s'),
                'unixtime'      => $createdDateTimestamp,
                'servertime'    => $createdDateTimestamp,
                'ago'           => $this->getAgoString($strCreatedDate),
            ],
            'billing_address' => $formattedBilling,
            'shipping_address' => $formattedShipping
        ];
    }

    /**
     * @param $editUserData array(
     *  (int) status,
     *  (string) reason,
     *  (\Magento\Customer\Api\Data\CustomerInterface) new_user_data
     *  [(int) error]
     * )
     * @return array
     */
    public function formatEditUserData($editUserData) {
        if(isset($editUserData['error'])) {
            return [
                'status'    => $editUserData['error'],
                'reason'    => $editUserData['reason']
            ];
        }

        return [
            'status'    => $editUserData['status'],
            'reason'    => $editUserData['reason'],
            'new_user_data' => $this->formatCustomerData($editUserData['data'])
        ];
    }

    /**
     * @param $keyword
     * @param $pager    Pager
     * @param $productsInfo
     * @return array
     */
    public function formatSearchProduct($keyword, $pager, $productsInfo) {

        return [
            'keyword'       => $keyword,
            'current_page'  => $pager->getCurrentPage(),
            'total_page'    => $pager->getTotalPages(),
            'post_per_page' => $pager->getPageSize(),
            'total_post'    => $pager->getTotalItems(),
            'product'       => $productsInfo['data']
        ];
    }

    public function formatReviewsByProduct($reviewsData, $productId, $parentId = 0) {

        $result = [
            'postID'        => $productId,
            'comments'      => [],
            'main_comment'  => []
        ];

        if(!$parentId && !empty($reviewsData)) {
            $result['main_comment'] = current($reviewsData); // the first comment
        }

        foreach($reviewsData as $review) {

            $item = [
                'comment_id' => $review['review_id'],       // review_id
                'status' => $review['status_id'],
                'followed' => 'unfollowed',
                'author' => [
                    'avatar' => null,  // user have no avatar in magento
                    'author_id' => $review['customer_id'],
                    'author_name' => $review['nickname']
                ],
                'date' => $review['created_at'],     // mg_review.created_at
                'rating' => 0,
                'comment_author_IP' => '',
                'unixtime' => $review['timestamp'],
                'servertime' => $review['timestamp'],
                'ago' => $this->getAgoString($review['created_at']),
                'parent' => '0',
                'agent' => '',
                'content' => $review['detail'],
                'childs' => []   // review cannot has comments
            ];

            $result['comments'][] = $item;

            if($parentId && $review['review_id'] == $parentId) {
                $result['main_comment'] = $item;
            }
        }

        return $result;
    }

    /**
     * @param $countriesData array ['country_id', 'iso2_code', 'iso3_code', 'country_name']
     * @param $regionCollection \Magento\Directory\Model\ResourceModel\Region\Collection
     * @return array
     */
    public function formatCountries($countriesData, $regionCollection) {

        $countries = [];

        foreach($countriesData as $country) {
            $countries[$country['country_id']] = [  // make associative array for regions
                'code'      => $country['iso2_code'],
                'country'   => $country['country_name'],
                'states'    => []
            ];
        }

        foreach ($regionCollection as $item) {

            $region = [
                'state_code'    => $item->getData('code'),
                'state'         => $item->getData('name')
            ];

            $regionCountryId = $item->getData('country_id');

            if(isset($countries[$regionCountryId])) {
                $countries[$regionCountryId]['states'][] = $region;
            }
        }
        return array_values($countries);    // from assoc array to simple list
    }

    public function formatLogoutUser() {
        return [
            'status'    => '1',
            'reason'    => 'Successful'
        ];
    }

    public function formatSettings($settingsInfo) {
        $orderStatusArray = [];
        if(!empty($settingsInfo->orderStatusList)) {
            foreach ($settingsInfo->orderStatusList as $_ => $status) {
                $orderStatusArray[] = [
                    'status_slug' => $status['status'],
                    'status_label' => $status['label']
                ];
            }
        }
        return [
            'currency' => $settingsInfo->currency,
            'currency_symbol' => $settingsInfo->currencySign,
            'appearance_option' => [
                'category_browse_option'        => '',  // idk what is this
                'category_browse_show_thumb'    => ''   // idk what is this too
            ],
            'page'  => [
                'thankyou' => isset($settingsInfo->thanksUrl) ? $settingsInfo->thanksUrl : false,
                'cart' => isset($settingsInfo->cartUrl) ? $settingsInfo->cartUrl : false,
                'lost_password' => isset($settingsInfo->lostPasswordUrl) ? $settingsInfo->lostPasswordUrl : false,
            ],
            'status_list'   => $orderStatusArray,
            'instragram_api' => [
                'client_id' => false
            ]
        ];
    }

    /**
     * @param $updateBillingData array [status, reason, data [customer, billing, shipping]
     * @return array
     */
    public function formatUserBilling($updateBillingData) {
        //return $this->formatEditUserData($updateBillingData);  // in future we may need to edit the output

        if(isset($updateBillingData['error'])) {
            return [
                'status'    => $updateBillingData['error'],
                'reason'    => $updateBillingData['reason']
            ];
        }

        return [
            'status'        => $updateBillingData['status'],
            'reason'        => $updateBillingData['reason'],
            'new_user_data' => [
                'status'    => 0,
                'reason'    => 'Successful Log',
                'user'      => $this->formatCustomerData($updateBillingData['data'])
            ]
        ];
    }

    public function formatUserShipping($updateShippingData) {
        return $this->formatEditUserData($updateShippingData);  // in future we may need to edit the output (like previous, yeah)
    }

    /**
     * @param $cartApiInfo array
     * @return array
     */
    public function formatCartApi($cartApiInfo) {

        if(isset($cartApiInfo['error'])) {
            return [
                'error' => $cartApiInfo['error'],
                'response'  => $cartApiInfo['reason']
            ];
        }

        /** @var \Magento\Checkout\Model\Cart $cart */
        $cart = $cartApiInfo['cart'];
        $coupon = $cartApiInfo['coupon'];
        $payments = $cartApiInfo['paymentMethod'];

        $formattedCartItems = $this->formatCartItems($cart);
        $formattedPayments = $this->formatPaymentMethodArray($payments);
        return [
            'cart'  => [
                $formattedCartItems
            ],
            'coupon'    => $coupon,
            'has_tax' => boolval($cart->getQuote()->getShippingAddress()->getTaxAmount()),
            'currency' => 'bax',//$cart->getQuote()->getCurrency(),
            'display-price-during-cart-checkout' => true,
            'cart-subtotal' => $cart->getQuote()->getShippingAddress()->getSubtotalInclTax(),
            'cart-subtotal-ex-tax' => $cart->getQuote()->getSubtotal(),
            'cart-tax-total' => $cart->getQuote()->getShippingAddress()->getTaxAmount(),
            'shipping-cost' => $cart->getQuote()->getShippingAddress()->getAllTotalAmounts(),
            'shipping-method' => $cart->getQuote()->getShippingAddress()->getShippingMethod(),
            'discount' => ($cart->getQuote()->getSubtotal() - $cart->getQuote()->getSubtotalWithDiscount()),
            'grand-total' => $cart->getQuote()->getGrandTotal(),
            'payment-method' => $formattedPayments,
            'shipping_available'    => !empty($cart->getQuote()->getAllShippingAddresses()) // idk other way to check it, just by checking number of addresses
        ];
    }

    public function formatSingleReview($reviewData) {
        if(isset($reviewData['error'])) {
            return [
                'status'    => $reviewData['error'],
                'reason'    => $reviewData['reason']
            ];
        }

        $review     = $reviewData['data']['review'];
        $customer   = $reviewData['data']['customer'];
        return [
            'comment_ID'            => $review['review_id'],
            'comment_post_ID'       => $review['entity_pk_value'],
            'comment_author'        => $customer['first_name'],
            'comment_author_email'  => $customer['email'],
            'comment_author_url'    => null,
            'comment_author_IP'     => null,
            'comment_date'          => $review['created_at'],
            'comment_date_gmt'      => $review['created_at'],
            'comment_content'       => $review['detail'],
            'comment_carma'         => 0,
            'comment_approved'      => ($review['status_id'] == 1 ? 1 : 0),
            'comment_agent'         => null,
            'comment_type'          => '',
            'comment_parent'        => null,
            'user_id'               => $customer['id']
        ];
    }

    /**
     * @param $placedOrderInfo array
     * @return array
     */
    public function formatPlaceOrderApi($placedOrderInfo) {

        if(isset($placedOrderInfo['error'])) {
            return $placedOrderInfo;
        }

        /** @var \Magento\Sales\Model\Order $order */
        $order = $placedOrderInfo['data']['order'];

        /** @var \Magento\Quote\Model\Quote\Payment $payment */
        $payment = $placedOrderInfo['data']['payment'];


        return [
            'orderID' => $order->getEntityId(),
            'order_key' => '',
            'display-price-during-cart-checkout' => true,
            'orderDate' => $order->getCreatedAt(),
            'paymentDate' => $order->getCreatedAt(),        // You cannot create order without payment!!1!
            'status' => $order->getStatus(),
            'currency' => $order->getOrderCurrencyCode(),
            'billing_email' => '', // no BILLING email in magento, only customer, just remember it!
            'billing_phone' => $order->getBillingAddress()->getTelephone(),
            'billing_address' => $this->formatQuoteAddress($order->getBillingAddress()),
            'shipping_address' => $order->getShippingAddress()->toString(),
            'items' => $this->formatOrderItems($order->getAllItems()),
            'used_coupon' => boolval($order->getCouponCode()),  // '' or [] will be converted to false
            'subtotalWithTax' => $order->getSubtotalInclTax(),
            'subtotalExTax' => $order->getSubtotal(),
            'shipping_method' => $order->getShippingMethod(),
            'shipping_cost' => $order->getShippingAmount(),
            'shipping_tax' => $order->getShippingTaxAmount(),
            'tax_total' => $order->getTaxAmount(),
            'discount_total' => $order->getDiscountAmount(),
            'order_total' => $order->getTotalPaid(),
            'order_note' => $order->getCustomerNote(),
            'payment_method_id' => $payment->getMethod(),
            'payment_method_title' => $order->title     // yh, the same hack from PaymentHelper
        ];
    }

    /**
     * @param $productInfo
     * @param $pager    Pager
     * @return array
     */
    public function formatRecentProducts($productInfo, $pager) {

        $data = $this->formatPagedProducts($pager, $productInfo['data'], $productInfo['categories']);
        return $data;
    }

    /**
     * @param $productsInfo
     * @param $pager Pager
     * @return array
     */
    public function formatRandomProducts($productsInfo, $pager) {
        $data = $this->formatPagedProducts($pager, $productsInfo['data'], $productsInfo['categories']);
        return $data;
    }

    /**
     * @param $featuredProductInfo
     * @return array
     */
    public function formatFeaturedProduct($featuredProductInfo) {

        $productsList = $featuredProductInfo['data'];
        $productsCategories = $featuredProductInfo['categories'];
        $productCount = count($productsList);
        $products = [];

        foreach($productsList as $_ => $product) {
            $categories = isset($productsCategories[$product->getId()]) ? $productsCategories[$product->getId()] : [];
            $products[] = $this->formatProductById($product, $categories);
        }
        return [
            'total_post' => $productCount,
            'products'   => $products
        ];
    }

    /**
     * Just redirect call to formatCustomerData
     * may be useful in future, if we need to change output data
     * @param $userRegistrationInfo
     * @return array
     */
    public function formatUserRegistration($userRegistrationInfo) {

        if(!($userRegistrationInfo instanceof \Magento\Customer\Api\Data\CustomerInterface) &&  isset($userRegistrationInfo['error'])) {
            return [
                'status'    => $userRegistrationInfo['error'],
                'reason'    => $userRegistrationInfo['reason']
            ];
        }
        $formattedCustomer = $this->formatCustomerData([
            'customer'  => $userRegistrationInfo,
            'billing'   => [],
            'shipping'  => []
        ]);
        return [
            'status'    => 0,
            'reason'    => 'Successful Registered',
            'user'      => $formattedCustomer
        ];
    }

    public function formatSingleOrder($orderInfo) {

        $formattedOrder = [];
        if(isset($orderInfo['order'])) {
            $formattedOrder = $this->formatOrderEntity($orderInfo['order']);

            if(isset($orderInfo['orderItems'])) {
                $formattedOrder['items'] = $this->formatOrderItems($orderInfo['orderItems']);
            }
        }

        return $formattedOrder;
    }

    public function formatMyOrders($myOrdersInfo, $filter = 'All') {

        if(isset($myOrdersInfo['error'])) {
            return [
                'status'    => $myOrdersInfo['error'],
                'reason'    => $myOrdersInfo['reason']
            ];
        }

        $formattedOrders = [];
        $orderCount = 0;
        if(isset($myOrdersInfo['orders'])) {

            $orderCount = count($myOrdersInfo['orders']);

            /** @var OrderInterface $order */
            foreach ($myOrdersInfo['orders'] as $order) {

                $formattedOrder = $this->formatOrderEntity($order);
                $orderId = $order->getEntityId();

                if(isset($myOrdersInfo['orderItems'], $myOrdersInfo['orderItems'][$orderId])) {
                    $formattedOrder['items'] = $this->formatOrderItems($myOrdersInfo['orderItems'][$orderId]);
                }
                $formattedOrders[] = $formattedOrder;
            }
        }
        return  [
            'total_post' => $orderCount,
            'filter' => $filter,
            'my_order' => $formattedOrders
        ];
    }

    /**
     * @param $paymentGatewayInfo mixed
     * @return array
     */
    public function formatGetSinglePaymentGatewayMeta($paymentGatewayInfo) {
        return $paymentGatewayInfo;
    }

    // TODO: formatMobilePaymentRedirectApi
    // TODO: formatMobilePaymentRedirectAuthorizeDotNetApi


    //region private/protected service functions

    /**
     * @param $data &array(id, parent_id, name) -- pass reference to prevent memory leak
     * @param $parent
     * @return array
     */
    protected function buildProductCategoriesTree(&$data, $parent = 0) {
        $result = [];

        if(empty($data)) {
            return [];
        }

        foreach ($data as $row) {

            if($row['parent_id'] == $parent) {
                $item = [
                    'term_id'   => $row['entity_id'],
                    'thumb'     => null,
                    'name'      => $row['value'],
                    'slug'      => isset($row['category_slug']) ? $row['category_slug'] : '',
                    'category_parent'   => $row['parent_id'],
                    'post_count'    => isset($row['product_count']) ? $row['product_count'] : 0,
                    'children'  => $this->buildProductCategoriesTree($data, $row['entity_id'])
                ];
                $result[] = $item;
            }
        }
        return $result;
    }

    /**
     * Return time elapsed since a specified timestamp
     * @param $datetime string datetime, format: YYYY-MM-DD HH-ii-SS
     * @return string
     */
    protected function getAgoString($datetime) {

        $timeFrom = strtotime($datetime);
        $timeDiff = time() - $timeFrom;

        $tokens = array (
            31536000 => 'year',
            2592000 => 'month',
            604800 => 'week',
            86400 => 'day',
            3600 => 'hour',
            60 => 'minute',
            1 => 'second'
        );

        foreach ($tokens as $unit => $text) {
            if ($timeDiff < $unit) {
                continue;
            }
            $numberOfUnits = floor($timeDiff / $unit);
            return $numberOfUnits . ' ' . $text . (($numberOfUnits>1) ? 's' : '') . ' ago';
        }
        return '';
    }

    /**
     * Format pager and products
     * @param $pager    Pager
     * @param $productsInfo array of \Magento\Catalog\Model\Product
     * @param $categories array
     * @return array
     */
    protected function formatPagedProducts($pager, $productsInfo, $categories = []) {
        $products = [];
        foreach($productsInfo as $product) {
            $products[] = $this->formatProductById($product, $categories[$product->getId()]);
        }

        return [
            'current_page'  => $pager->getCurrentPage(),
            'total_page'    => $pager->getTotalPages(),
            'post_per_page' => $pager->getPageSize(),
            'total_post'    => intval($pager->getTotalItems()),
            'products'      => $products
        ];
    }

    /**
     * @param $addressInfo AddressInterface
     * @param $toString boolean
     * @param $stringSeparator string
     * @return string|array
     */
    public function formatQuoteAddress($addressInfo, $toString = false, $stringSeparator = ', ') {
        $data = [];
        if($addressInfo->getRegion()) {
            $data['region'] = $addressInfo->getRegion();
        }

        if($addressInfo->getCity()) {
            $data['city'] = $addressInfo->getCity();
        }


        if($addressInfo->getStreet()) {
            $data['street'] = implode(' ', $addressInfo->getStreet());
        }

        if($addressInfo->getPostcode()) {
            $data['postcode'] = $addressInfo->getPostcode();
        }

        if($toString) {
            return implode($stringSeparator, $data);
        }

        return $data;
    }

    /**
     * @param $orderData \Magento\Sales\Api\Data\OrderInterface
     * @return array
     */
    public function formatOrderEntity($orderData) {
        return [
            'orderID'   => intval($orderData->getEntityId()),
            'order_key' => '',
            'display-price-during-cart-checkout'    => 'excl',
            'orderDate' => $orderData->getCreatedAt(),
            'paymentDate'   => '',
            'status'    => $orderData->getStatus(),
            'currency'  => '&#36',
            'billing_email' => $orderData->getCustomerEmail(),
            'billing_phone' => '',      // no phone for billing?? ;(
            'billing_address'   => $this->formatQuoteAddress($orderData->getBillingAddress(), true, "\n"),
            'shipping_address'  => $this->formatQuoteAddress($orderData->getBillingAddress(), true, "\n"),
            'items' => [],
            'used_coupon'   => ($orderData->getCouponCode() ? $orderData->getCouponCode() : []),
            'subtotalWithTax'   => floatval($orderData->getSubtotalInclTax()),        // whatever is this...
            'subtotalExTax' => floatval($orderData->getSubtotal()),                   // and this....
            'shipping_method'   => null, //TODO: format shipping method
            'shipping_cost' => floatval($orderData->getBaseShippingAmount()),
            'shipping_tax'  => floatval($orderData->getShippingTaxAmount()),
            'tax_total' => ($orderData->getSubtotalInclTax() - $orderData->getSubtotal()),
            'discount_total'    => floatval($orderData->getShippingDiscountAmount()),
            'order_total'   => floatval($orderData->getGrandTotal()),
            'order_note'    => ($orderData->getCustomerNote() ? $orderData->getCustomerNote() : ''),
            'payment_method_id' => $orderData->getPayment()->getMethod(),
            'payment_method_title'  => $this->paymentHelper->getPaymentTitle($orderData->getPayment()->getMethod()),
            'payment_desc'  => $this->paymentHelper->getPaymentTitle($orderData->getPayment()->getMethod()),
            'order_notes'   => [$orderData->getCustomerNote() ? $orderData->getCustomerNote() : '']
        ];
    }

    /**
     * @param $orderItems \Magento\Sales\Model\Order\Item[]
     * @return array
     */
    protected function formatOrderItems($orderItems) {

        $resultData = [];
        foreach($orderItems as $orderItem) {
            $resultData[] = [
                'product_id'    => $orderItem->getProductId(),
                'product_info' => [
                    'featuredImages'=> '',
                    'productName'   => $orderItem->getName()
                ],
                'variation_id' => '0',
                'variation_info'    => [
                    'featuredImages' => '',
                    'productName'   => $orderItem->getName()
                ],
                'quantity'  => $orderItem->getQtyOrdered(),
                'product_price' => $orderItem->getPriceInclTax(),
                'product_price_ex_tax'  => $orderItem->getPrice(),
                'total_price'   => ($orderItem->getPriceInclTax() * $orderItem->getQtyOrdered()),
                'total_price_ex_tax'    => ($orderItem->getPrice() * $orderItem->getQtyOrdered()),
            ];
        }
        return $resultData;
    }

    /**
     * @param $cart \Magento\Checkout\Model\Cart
     * @return array
     */
    protected function formatCartItems($cart) {

        /** @var \Magento\Eav\Model\Entity\Collection\AbstractCollection $cartItems */
        $cartItems = $cart->getQuote()->getAllItems();
        $result = [];

        /**
         * @var integer $key
         * @var \Magento\Quote\Model\Quote\Item $item
         */
        foreach ($cartItems as $key => $item) {
            $product = $item->getProduct();
            $result[] = [
                'id' => $product->getId(),
                'product-price' => $product->getPrice(),
                'product-tax'   => $item->getTaxAmount(),
                'total-price'   => $item->getRowTotal(),
                'total-tax'     => $item->getRowTotalInclTax() - $item->getRowTotal(),//$item->getTaxAmount() * $product->getQty(),
                'quantity'      => $product->getQty()
            ];
        }
        return $result;
    }

    /**
     * @param $payment \Magento\Quote\Model\Quote\Payment
     * @return array
     */
    protected function formatSinglePaymentMethod($payment) {

        return [
            'id' => $payment->getMethod(),
//            'title' => $payment->title,     // @see PaymentHelper
//            'description' => $payment->title,
            'title' => $payment->getData('title'),     // @see PaymentHelper
            'description' => $payment->getData('title'),
            'meta_key'  => [
                'hideit' => '', // idk what is it, but it seems like woocommerce special keys
                'safari' => ''
            ]
        ];
    }

    protected function formatPaymentMethodArray($payments) {
        $result = [];
        foreach($payments as $_ => $payment) {
            $result[] = $this->formatSinglePaymentMethod($payment);
        }
        return $result;
    }

    /**
     * @param $category \Magento\Catalog\Model\Category
     * @return array
     */
    protected function formatSingleProductCategory($category) {
        return [
            'term_id' => $category->getId(),
            'name' => $category->getName(),
            'slug' => $category->getData('slug'),
            'term_group' => 0,
            'term_taxonomy_id' => $category->getId(),
            'taxonomy' => 'product_cat',
            'description' => $category->getData('name'),
            'parent' => $category->getParentIds(),
            'count' => $category->getProductCount(),
            'filter' => 'raw',
        ];
    }

    /**
     * FUCK ME when I did it before I learn collection!!1! >.<
     * @param $productData array
     * @return array
     */
    protected function formatSingleProductData($productData) {
        return [
            'created_at'    => $productData['created_at'],
            'product_ID'    => intval($productData['entity_id']),      // product_entity.product_id
            'is_downloadable'   => isset($productData['is_downloadable']) ? $productData['is_downloadable'] : false,
            'is_purchasable'    => isset($productData['is_salable']) ? $productData['is_salable'] : true,    // it MUST BE false by default -_-
            'is_featured'       => isset($productData['is_featured']) && $productData['is_featured'] ? true : false,
            'visibility'        => isset($productData['is_visible']) ? $productData['is_visible'] : false,
            'general'           => [
                'title'     => $productData['sku'],    // product_entity.title
                'link'      => isset($productData['product_url']) ? $productData['product_url'] : '',
                'content'   => [
                    'full_html'     => '',
                    'excepts'       => ''
                ],
                'SKU'           => isset($productData['sku']) ? $productData['sku'] : '',
                'product_type'  => isset($productData['type_id']) && $productData['type_id'] != 'simple' ? 'external' : 'simple',
                'if_external'   => [    // idk what is it
                    'product_url'   => isset($productData['product_url']) ? $productData['product_url'] : '',
                    'button_name'   => $productData['sku']
                ],
                'pricing'   => [
                    'is_on_sale'    => isset($productData['is_on_sale']) ? $productData['is_on_sale'] : false,
                    'currency'      => 'bax',
                    'regular_price' => isset($productData['final_price']) ? $productData['final_price'] : '',
                    'sale_price'    => isset($productData['sale_price']) ? $productData['sale_price'] : '',
                    'sale_start'    => [
                        'unixtime'      => '',
                        'day'           => false,
                        'month'         => false,
                        'year'          => false,
                        'day_name'      => false,
                        'fulldate'      => false
                    ],
                    'sale_end'    => [
                        'unixtime'      => '',
                        'day'           => false,
                        'month'         => false,
                        'year'          => false,
                        'day_name'      => false,
                        'fulldate'      => false
                    ],
                ],
                'tax_status'    => '',
                'tax_class'     => isset($productData['tax_class_id']) ? $productData['tax_class_id'] : null
            ],
            'inventory'     => [
                'manage_stock'  => false,
                'quantity'      => isset($productData['quantity']) ? $productData['quantity'] : 0,
                'stock_status'  => isset($productData['stock_status']) ? $productData['stock_status'] : false,
                'allow_backorder'   => false,
                'allow_backorder_require_notification'  => false,
                'sold_individually' => false
            ],
            'shipping'  => [
                'weight'    => [
                    'has_weight'    => false,
                    'unit'          => 'kg',
                    'value'         => ''   // attribute 82
                ],
                'dimension'     => [
                    'has_dimension' => false,
                    'unit'          => 'cm',
                    'value_l'       => '',
                    'value_w'       => '',
                    'value_h'       => '',
                ],
                'shipping_class'    => [
                    'class_name'    => '',
                    'class_id'      => 0
                ]
            ],
            'linked_products'   => [
                'upsells'       => [],
                'cross_sale'    => [],
                'grouped'       => 0
            ],
            'attributes'    => [
                'has_attributes'    => false,
                'attributes'        => []
            ],
            'advanced'  => [
                'purchase_note'     => '',
                'menu_order'        => 0,
                'comment_status'    => 'open'
            ],
            'ratings'   => [
                'average_rating'    => '',
                'rating_count'      => 0
            ],
            'if_variants'   => [
                'min_price'     => [
                    'currency'  => 'bax',
                    'price'     => isset($productData['min_price']) ? $productData['min_price'] : null,
                ],
                'max_price'     => [
                    'currency'  => 'bax',
                    'price'     => isset($productData['max_price']) ? $productData['max_price'] : null,
                ],
                'variables' => []
            ],
            'if_group'  => [
                'min_price'     => [
                    'currency'  => '',
                    'price'     => ''
                ],
                'group' => []
            ],
            'product_gallery'   => [
                'featured_images'   => isset($productData['image_url']) ? $productData['image_url'] : '',
                'other_images'      => []
            ],
        ];
    }

    /**
     * @param $product \Magento\Catalog\Model\Product
     * @return array
     */
    protected function formatSingleProduct($product) {

        $data = $product->getData();
        $data['quantity'] = isset($data['quantity_and_stock_status']['qty']) ? $data['quantity_and_stock_status']['qty'] : 0;
        $data['stock_status'] = isset($data['quantity_and_stock_status']['is_in_stock']) ? $data['quantity_and_stock_status']['is_in_stock'] : false;
        if ($product->getPrice() > $product->getFinalPrice()) {
            $data['is_on_sale'] = true;
            $data['sale_price'] = $product->getFinalPrice();
            $data['final_price'] = $product->getPrice();
        } else {
            $data['final_price'] = $product->getFinalPrice();
        }
        $data = $this->formatSingleProductData($data);
        return $data;
    }

    //endregion
}