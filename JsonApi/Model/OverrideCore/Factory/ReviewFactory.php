<?php

/**
 * Created by PhpStorm.
 * User: serf
 * Date: 06.09.16
 * Time: 15:52
 */

namespace Amazingcard\JsonApi\Model\OverrideCore\Factory;

class ReviewFactory extends \Magento\Review\Model\ReviewFactory
{
    /**
     * Factory constructor
     *
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param string $instanceName
     */
    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager, $instanceName = '\\Amazingcard\\JsonApi\\Model\\OverrideCore\\Review')
    {
        parent::__construct($objectManager, $instanceName);
    }
}