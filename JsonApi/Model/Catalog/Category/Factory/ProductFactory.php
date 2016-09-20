<?php
/**
 * Created by PhpStorm.
 * User: serf
 * Date: 01.09.16
 * Time: 17:00
 */

namespace Amazingcard\JsonApi\Model\Catalog\Category\Factory;

use Amazingcard\JsonApi\Model\Base\BaseAbstractFactory;

class ProductFactory extends BaseAbstractFactory
{
    /**
     * Create new Product model
     *
     * @param  array $arguments
     * @return \Amazingcard\JsonApi\Model\Catalog\Category\Product
     */
    public function getObject(array $arguments = [])
    {
        return $this->get(\Amazingcard\JsonApi\Model\Catalog\Category\Product::class, $arguments);
    }
}