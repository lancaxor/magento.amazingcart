<?php
/**
 * Created by PhpStorm.
 * User: serf
 * Date: 05.09.16
 * Time: 14:18
 */

namespace Amazingcard\JsonApi\Model\Catalog\Category\Factory;

use Amazingcard\JsonApi\Model\Base\BaseAbstractFactory;

class EntityFactory extends BaseAbstractFactory
{

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        parent::__construct($objectManager, '\\Amazingcard\\JsonApi\\Model\\Catalog\\Category\\Entity');
    }

    /**
     * Create new Product Entity model
     *
     * @param  array $arguments
     * @return \Amazingcard\JsonApi\Model\Catalog\Category\Entity
     */
    public function getObject(array $arguments = [])
    {
        return $this->get($this->instanceName, $arguments);
    }
}