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

    public function __construct()
    {

    }

    public function formatError($message, $code) {
        return [
            'status' => isset($code) ? $code : -1,
            'reason' => isset($message) ? $message : 'Unknown error'
        ];
    }
    /**
     * @param $productInfo
     * @return array
     */
    public function formatProductById($productInfo) {
        if(empty($productInfo)) {
            return [];
        }
        return [
            'product_ID'    => $productInfo['entity_id'],      // product_entity.product_id
            'is_downloadable'   => isset($productInfo['is_downloadable']) ? $productInfo['is_downloadable'] : false,
            'is_purchasable'    => true,
            'is_featured'       => isset($productInfo['is_featured']) ? $productInfo['is_featured'] : false,
            'visibility'        => isset($productInfo['visibility']) ? $productInfo['visibility'] : false,
            'general'           => [
                'title'     => $productInfo['sku'],    // product_entity.title
                'link'      => '',
                'content'   => [
                    'full_html'     => '',
                    'excepts'       => ''
                ],
                'SKU'           => isset($productInfo['sku']) ? $productInfo['sku'] : '',
                'product_type'  => isset($productInfo['type_id']) ? $productInfo['type_id'] : null,    // product_entity.type_id
                'if_external'   => [    // idk what is it
                    'product_url'   => '',
                    'button_name'   => ''
                ],
                'pricing'   => [
                    'is_on_sale'    => isset($productInfo['is_salable']) ? $productInfo['is_salable'] : false,
                    'currency'      => '',
                    'regular_price' => isset($productInfo['final_price']) ? $productInfo['final_price'] : '',
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
                'tax_class'     => isset($productInfo['tax_class_id']) ? $productInfo['tax_class_id'] : null
            ],
            'invertory'     => [
                'manage_stock'  => false,
                'quantity'      => '',
                'stock_status'  => '',
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
                    'currency'  => '',
                    'price'     => isset($productInfo['min_price']) ? $productInfo['min_price'] : null,
                ],
                'max_price'     => [
                    'currency'  => '',
                    'price'     => isset($productInfo['max_price']) ? $productInfo['max_price'] : null,
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
                'featured_images'   => '0',
                'other_images'      => []
            ],
            'categories'    => isset($productInfo['category']) ? $this->formatProductCategories($productInfo['category']) : []
        ];
    }

    /**
     * Get product categories (always your, captain)
     * @param $categoryList
     * @param $parent int Root category ID
     * @return array
     */
    public function formatProductCategories($categoryList, $parent = 0) {
        return $this->buildProductCategoriesTree($categoryList, $parent);
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

        $formattedShipping = [];
        $shipping = isset($customerInfo['shipping']) ? $customerInfo['shipping'] : [];
        if($shipping instanceof \Magento\Customer\Api\Data\AddressInterface) {
            $shippingRegion = $shipping->getRegion();
            $formattedShipping = [
                'shipping_first_name' => $shipping->getFirstname(),
                'shipping_last_name' => $shipping->getLastname(),
                'shipping_company' => $shipping->getCompany(),
                'shipping_address_1' => implode(', ', $shipping->getStreet()),
                'shipping_address_2' => '',
                'shipping_city' => $shipping->getCity(),
                'shipping_postcode' => $shipping->getPostcode(),
                'shipping_state' => isset($shippingRegion) ? $shippingRegion->getRegion() : null,
                'shipping_state_code' => isset($shippingRegion) ? $shippingRegion->getRegionCode() : null,
                'shipping_has_state' => isset($shippingRegion),
                'shipping_country' => '',
                'shipping_country_code' => $shipping->getCountryId(),
                'shipping_phone'     =>  $shipping->getTelephone(),
                'shipping_email'     =>  ''      // reserved
            ];
        }
        unset($shipping);

        $formattedBilling = [];
        $billing = isset($customerInfo['billing']) ? $customerInfo['billing'] : [];
        if($billing instanceof \Magento\Customer\Api\Data\AddressInterface) {
            $billingRegion = $billing->getRegion();
            $formattedBilling = [
                'billing_first_name'    => $billing->getFirstname(),
                'billing_last_name'     => $billing->getLastname(),
                'billing_company'       => $billing->getCompany(),
                'billing_address_1'     => implode(', ', $billing->getStreet()),
                'billing_address_2'     => '',       // reserved
                'billing_city'          => $billing->getCity(),
                'billing_postcode'      => $billing->getPostcode(),
                'billing_state'         => isset($billingRegion) ? $billingRegion->getRegion() : null,
                'billing_state_code'    => isset($billingRegion) ? $billingRegion->getRegionCode() : null,
                'billing_has_state'     => isset($billingRegion),
                'billing_country'       => '',      // reserved
                'billing_country_code'  =>$billing->getCountryId(),
                'billing_phone'     =>  $billing->getTelephone(),
                'billing_email'     =>  ''      // reserved
            ];
        }
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
                'db_format'     => $createdDate,
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

    public function formatReviewsByProduct($reviewsData, $productId) {

        $result = [
            'postID'    => $productId,
            'comments'  => []
        ];
        foreach($reviewsData as $review) {

            $item = [
                'comment_id' => $review['review_id'],       // review_id
                'status' => $review['status_id'],
                'author' => [
                    'avatar' => null,  // user have no avatar in magento
                    'author_id' => $review['customer_id'],
                    'author_name' => $review['nickname']
                ],
                'date' => $review['created_at'],     // mg_review.created_at
                'rating' => 0,
                'comment_author_IP' => null,
                'unixtime' => $review['timestamp'],
                'servertime' => $review['timestamp'],
                'ago' => $this->getAgoString($review['created_at']),
                'parent' => 0,
                'agent' => null,
                'content' => $review['detail'],
                'childs' => []   // review cannot has comments
            ];

            $result['comments'][] = $item;
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

    public function formatSettings() {
        return [
            'currency',
            'currency_symbol',
            'appearance_option' => [
                'category_browse_option'        => '',
                'category_browse_show_thumb'    => ''
            ],
            'page'  => [
                'thankyou',
                'cart',
                'lost_password'
            ],
            'status_list'   => [
                [
                    'status_slug'   => 'All',
                    'status_label'  => 'All'
                ]
            ],
            'instragram_api'    => [
                'client_id' => ''
            ]
        ];
    }

    public function formatUserBilling($updateBillingData) {
        return $this->formatEditUserData($updateBillingData);  // in future we may need to edit the output
    }

    public function formatUserShipping($updateShippingData) {
        return $this->formatEditUserData($updateShippingData);  // in future we may need to edit the output (like previous, yeah)
    }

    /**
     * @param $cartApiInfo array
     * @return array
     */
    public function formatCartApi($cartApiInfo) {
        if(!isset($cartApiInfo['cart'])) {
            return [
                'error' => -1,
                'response'  => 'No cart was created'
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
            'currency' => $cart->getQuote()->getCurrency(),
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

        if(isset($placeOrderInfo['error'])) {
            return $placedOrderInfo;
        }

        $billingAddress = $placeOrderInfo['billing_address'];
        $coupon = $placeOrderInfo['coupon'];

        $order = $placedOrderInfo['order'];

        return [
            'orderID',
            'order_key',
            'display-price-during-cart-checkout',
            'orderDate',
            'paymentDate',
            'status',
            'currency',
            'billing_email',
            'billing_phone',
            'billing_address',
            'shipping_address',
            'items',
            'used_coupon',
            'subtotalWithTax',
            'subtotalExTax',
            'shipping_method',
            'shipping_cost',
            'shipping_tax',
            'tax_total',
            'discount_total',
            'order_total',
            'order_note',
            'payment_method_id',
            'payment_method_title',
        ];
    }

    /**
     * @param $productInfo
     * @param $pager    Pager
     * @return array
     */
    public function formatRecentProducts($productInfo, $pager) {

        $data = $this->formatPagedProducts($pager, $productInfo['data']);
        return $data;
    }

    /**
     * @param $productsInfo
     * @param $pager Pager
     * @return array
     */
    public function formatRandomProducts($productsInfo, $pager) {
        $data = $this->formatPagedProducts($pager, $productsInfo['data']);
        return $data;
    }

    /**
     * @param $featuredProductInfo
     * @return array
     */
    public function formatFeaturedProduct($featuredProductInfo) {

        $productCount = count($featuredProductInfo);
        $products = [];

        foreach($featuredProductInfo as $_ => $product) {
            $products[] = $this->formatProductById($product);
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
        return $this->formatCustomerData([
            'customer'  => $userRegistrationInfo,
            'billing'   => [],
            'shipping'  => []
        ]);
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

    public function formatMyOrders($myOrdersInfo) {

        $formattedOrders = [];
        if(isset($myOrdersInfo['orders'])) {

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
        return $formattedOrders;
    }


    // TODO: formatMobilePaymentRedirectApi
    // TODO: formatMobilePaymentRedirectAuthorizeDotNetApi
    // TODO: formatGetSettings
    // TODO: formatGetSinglePaymentGatewayMeta


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
     * @param $productsInfo array
     * @return array
     */
    protected function formatPagedProducts($pager, $productsInfo) {
        $products = [];
        foreach($productsInfo as $product) {
            $products[] = $this->formatProductById($product);
        }

        return [
            'current_page'  => $pager->getCurrentPage(),
            'total_page'    => $pager->getTotalPages(),
            'post_per_page' => $pager->getPageSize(),
            'total_post'    => $pager->getTotalItems(),
            'products'      => $products
        ];
    }

    /**
     * @param $addressInfo AddressInterface
     * @return string|array
     */
    public function formatQuoteAddress($addressInfo) {
        $data = [];
        if($addressInfo->getRegion()) {
            $data['region'] = $addressInfo->getRegion();
        }

        if($addressInfo->getCity()) {
            $data['city'] = $addressInfo->getCity();
        }


        if($addressInfo->getStreet()) {
            $data['street'] = $addressInfo->getStreet();
        }

        if($addressInfo->getPostcode()) {
            $data['postcode'] = $addressInfo->getPostcode();
        }

        return $data;
    }

    /**
     * @param $orderData \Magento\Sales\Api\Data\OrderInterface
     * @return array
     */
    public function formatOrderEntity($orderData) {
        return [
            'orderID'   => $orderData->getEntityId(),
            'order_key' => '',
            'display-price-during-cart-checkout'    => '',
            'orderDate' => $orderData->getCreatedAt(),
            'paymentDate'   => '',
            'status'    => '',
            'currency'  => '',
            'billing_email' => $orderData->getCustomerEmail(),
            'billing_phone' => '',      // no phone for billing?? ;(
            'billing_address'   => $this->formatQuoteAddress($orderData->getBillingAddress()),
            'shipping_address'  => '', //$this->_formatQuoteAddress($orderData->getCustomer()),
            'items' => [],
            'used_coupon'   => ($orderData->getCouponCode() ? 1 : 0),
            'subtotalWithTax'   => $orderData->getSubtotalInclTax(),        // whatever is this...
            'subtotalExTax' => $orderData->getSubtotal(),                   // and this....
            'shipping_method'   => '', //TODO: format shipping method
            'shipping_cost' => $orderData->getBaseShippingAmount(),
            'shipping_tax'  => $orderData->getShippingTaxAmount(),
            'tax_total' => '',
            'discount_total'    => $orderData->getShippingDiscountAmount(),
            'order_total'   => '',
            'order_note'    => $orderData->getCustomerNote(),
            'payment_method_id' => '',
            'payment_method_title'  => '',
            'payment_desc'  => '',  // TODO: payment status description
            'order_notes'   => ''
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
                    'productName'   => ''
                ],
                'variation_id' => '',
                'variation_info'    => [
                    'featuredImages',
                    'productName'   => $orderItem->getProduct()->getName()
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
            'title' => $payment->title,     // @see PaymentHelper
            'description' => $payment->title,
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

    //endregion
}