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
 *
 * @package Amazingcard\JsonApi\Model\Base
 */
abstract class BaseAbstractFactory
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager = null;

    /**
     * Instance name to create
     *
     * @var string
     */
    protected $_instanceName = null;

    /**
     * @var BaseAbstractModel
    */
    protected $_resource;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager, $instanceName = '\\Amazingcard\JsonApi\\Model\\Base\\BaseAbstractResourceModel')
    {
        $this->_objectManager = $objectManager;
        $this->_instanceName = $instanceName;
    }

    /**
     * @param $type string
     * @param array       $args
     * @return mixed
     */
    public function create($type, array $args = []) 
    {
        return $this->_objectManager->create($type, $args);
    }

    public function get($type, $args = []) 
    {

        if (!isset($this->_resource)) {
            $this->_resource = $this->create($type, $args);
        }
        return $this->_resource;
    }
}