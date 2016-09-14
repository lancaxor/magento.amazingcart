<?php
/**
 * Created by PhpStorm.
 * User: serf
 * Date: 05.09.16
 * Time: 14:18
 */

namespace Amazingcard\JsonApi\Model\Catalog\Product\Factory;

use Amazingcard\JsonApi\Model\Base\BaseAbstractFactory;

class EntityFactory extends BaseAbstractFactory
{
    /**
     * Create new Product Entity model
     *
     * @param  array $arguments
     * @return \Amazingcard\JsonApi\Model\Catalog\Product\Entity
     */
    public function getObject(array $arguments = [])
    {
        return $this->get('\Amazingcard\JsonApi\Model\Catalog\Product\Entity', $arguments);
    }
}