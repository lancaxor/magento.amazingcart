<?php
/**
 * Created by PhpStorm.
 * User: serf
 * Date: 10.10.16
 * Time: 10:29
 */

namespace Amazingcard\JsonApi\Helper;

class ImageHelper
{
    /**
     * @var \Magento\Catalog\Helper\ImageFactory
     */
    protected $imageHelper;

    protected $baseImageId = 'product_base_image';

    public function __construct() {

    }

    public function getProductImages($productIds) {
        if (is_array($productIds)) {
        }
    }
}