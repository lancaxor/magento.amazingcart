<?php

/**
 * Created by PhpStorm.
 * User: serf
 * Date: 05.09.16
 * Time: 14:01
 */
namespace Amazingcard\JsonApi\Model\Catalog\Category;

use Amazingcard\JsonApi\Model\Base\BaseAbstractModel;
use Amazingcard\JsonApi\Model\Base\BaseAbstractResourceModel;
use \Magento\Framework\Model\Context;
use \Magento\Framework\Registry;
use \Magento\Framework\Model\ResourceModel\AbstractResource;
use \Magento\Framework\Data\Collection\AbstractDb;

class Entity extends BaseAbstractModel
{

    /**
     * Table 'catalog_category_product'
     * @var \Amazingcard\JsonApi\Model\Catalog\Category\Factory\ProductFactory
     */
    protected $_categoryProductFactory;

    public function __construct(
        Context $context,
        Registry $registry,
        \Amazingcard\JsonApi\Model\Catalog\Category\Factory\ProductFactory $categoryProductFactory,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {

        $this->_categoryProductFactory = $categoryProductFactory;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    protected function _construct()
    {
        $this->_init('Amazingcard\JsonApi\Model\Catalog\Category\ResourceModel\Entity');
    }


    public function getCategories()
    {

        /**
         * @var \Amazingcard\JsonApi\Model\Catalog\Category\ResourceModel\Entity $resource
        */
        $resource = $this->_getResource();
//        die(var_dump($resource));
        $categoryProductModel = $this->_categoryProductFactory->getObject();
        $resource->setFetchType(BaseAbstractResourceModel::FETCH_ALL)
            ->addProductCountInfo($categoryProductModel)
            ->load($this);
        return $this->getData();
    }
}