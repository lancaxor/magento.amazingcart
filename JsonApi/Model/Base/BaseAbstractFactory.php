<?php
/**
 * Created by PhpStorm.
 * User: serf
 * Date: 01.09.16
 * Time: 16:58
 */

namespace Amazingcard\JsonApi\Model\Base;

/**
 * Base class for category factories
 * Class BaseAbstractFactory
 */
abstract class BaseAbstractFactory
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager = null;

    /**
     * Instance name to create
     *
     * @var string
     */
    protected $instanceName = null;

    /**
     * @var BaseAbstractModel
    */
    protected $resource;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param $instanceName $string
     */
    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager, $instanceName = \Amazingcard\JsonApi\Model\Base\BaseAbstractResourceModel::class)
    {
        $this->objectManager = $objectManager;
        $this->instanceName = $instanceName;
    }

    /**
     * @param $type string
     * @param array       $args
     * @return mixed
     */
    public function create($type, array $args = []) 
    {
        return $this->objectManager->create($type, $args);
    }

    public function get($type, $args = []) 
    {

        if (!isset($this->resource)) {
            $this->resource = $this->create($type, $args);
        }
        return $this->resource;
    }
}