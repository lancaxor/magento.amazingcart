<?php
/**
 * Created by PhpStorm.
 * User: serf
 * Date: 07.09.16
 * Time: 16:07
 */

namespace Amazingcard\JsonApi\Helper;

class ResponseFormatter
{

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

        return [
            'product_ID'    => $productInfo['id'],      // product_entity.product_id
            'is_downloadable'   => false,
            'is_purchasable'    => true,
            'is_featured'       => false,
            'visibility'        => '',
            'general'           => [
                'title'     => $productInfo['name'],    // product_entity.title
                'link'      => '',
                'content'   => [
                    'full_html'     => '',
                    'excepts'       => ''
                ],
                'SKU'           => '',
                'product_type'  => $productInfo['type_id'] ? $productInfo['type_id'] : null,    // product_entity.type_id
                'if_external'   => [    // idk what is it
                    'product_url'   => '',
                    'button_name'   => ''
                ],
                'pricing'   => [
                    'is_on_sale'    => false,
                    'currency'      => '',
                    'regular_price' => '',      //  catalog_product_index_price.
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
                'tax_class'     => ''
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
                    'value'         => ''
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
                    'price'     => ''
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
            'categories'    => []
        ];
    }

    /**
     * Get product categories (always your, captain)
     * @param $categoryList
     * @param $parent int Root category ID
     * @return array
     */
    public function formatProductCategories($categoryList, $parent = 0) {
        return $this->_buildProductCategoriesTree($categoryList, $parent);
    }

    /**
     * @param $categoryInfo array(name, id)
     * @param $products
     * @param $pager array(count, page, limit)
     * @return array
     */
    public function formatCategoryByProductId($categoryInfo, $products, $pager) {
        $fullCount = isset($pager['count']) ? $pager['count'] : 0;
        $page = isset($pager['page']) ? $pager['page'] : 0;
        $pageSize = isset($pager['limit']) ? $pager['limit'] : 0;
        return [
            'categoryID'    => $categoryInfo['id'],
            'categoryName'  => $categoryInfo['name'],
            'categorySlug'  => null,    // only XPEH knows what is it
            'current_page'  => $page,
            'post_per_page' => $pageSize ? $pageSize : $fullCount,
            'total_posts'   => $fullCount,
            'total_page'    => $pageSize ? intval($fullCount / $pageSize) + 1: 1,
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
                'ago'           => $this->_getAgoString($strCreatedDate),
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

    public function formatSearchProduct($keyword, $pager, $productsInfo) {

        $page = isset($pager['page']) ? $pager['page'] : 0;
        $pageSize = isset($pager['limit']) ? $pager['limit'] : 0;
        $totalCount = $productsInfo['count'];
        $totalPages = ($pageSize ? ceil($totalCount / $pageSize) : $totalCount);

        return [
            'keyword'       => $keyword,
            'current_page'  => $page,       // human-friendly
            'total_page'    => $totalPages,
            'post_per_page' => $pageSize,
            'total_post'    => $productsInfo['count'],
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
                'ago' => $this->_getAgoString($review['created_at']),
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
        return [
            'cart'  => [
            ],
            'coupon'    => [
                'applied-coupon'    => [],
                'discount-ammount'  => [],
                'coupon-array-inserted'    => []
            ],
            'has_tax'   => '',
            'currency'  => '',
            'display-price-during-cart-checkout',
            'cart-subtotal' => '',
            'cart-subtotal-ex-tax'  => '',
            'cart-tax-total'    => '',
            'shipping-cost'     => '',
            'shipping-method'   => '',
            'discount'          => '',
            'grand-total'       => '',
            'payment-method'    => [
                [           // pay method sample
                    'id'    => 'cheque',
                    'title' => 'cheque payment',
                    'description'   => 'please send your cheque to store name, store street, store town, store state/country, store postcode.',
                    'meta_key'  => [
                        'hideit'    => '',
                        'safari'    => ''
                    ]
                ],
                [
                    'id'    => 'cheque',
                    'title' => 'cheque payment',
                    'description'   => 'please send your cheque to store name, store street, store town, store state/country, store postcode.',
                    'meta_key'  => [
                        'hideit'    => '',
                        'safari'    => ''
                    ]
                ],
            ],
            'shipping_available'    => null
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

    public function formatPlaceOrderApi($placedOrderInfo) {
        return [];
    }

    public function formatRecentProducts($productInfo) {

        if(isset($productInfo['error'])) {
            return [
                'status'    => $productInfo['error'],
                'reason'    => $productInfo['reason']
            ];
        }
        $products = $productInfo['product'];
        $pager = $productInfo['pager'];

        $data = [
            'current_page'  => $pager['current_page'],
            'total_page'    => $pager['total_page'],
            'post_per_page' => $pager['limit'],
            'total_post'    => $pager['total'],
            'products'      => $products
        ];
        return $data;
    }

    public function formatRandomProducts($productsInfo) {
        return [];
    }


    // TODO: formatMobilePaymentRedirectApi
    // TODO: formatMobilePaymentRedirectAuthorizeDotNetApi
    // TODO: formatGetSettings
    // TODO: formatGetOrder
    // TODO: formatGetFeaturedProduct
    // TODO: formatGetRandomItems
    // TODO: formatGetMyOrder
    // TODO: formatUserRegistration_placeorderapi
    // TODO: formatGetSinglePaymentGatewayMeta


    //region private/protected service functions

    /**
     * @param $data &array(id, parent_id, name) -- pass reference to prevent memory leak
     * @param $parent
     * @return array
     */
    protected function _buildProductCategoriesTree(&$data, $parent) {

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
                    'slug'      => null,
                    'category_parent'   => $row['parent_id'],
                    'post_count'    => isset($row['product_count']) ? $row['product_count'] : 0,
                    'children'  => $this->_buildProductCategoriesTree($data, $row['entity_id'])
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
    protected function _getAgoString($datetime) {

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

    //endregion
}