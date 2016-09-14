<?php

/**
 * Created by PhpStorm.
 * User: serf
 * Date: 05.09.16
 * Time: 14:01
 */
namespace Amazingcard\JsonApi\Model\Catalog\Product;

use Amazingcard\JsonApi\Model\Base\BaseAbstractModel;
use Amazingcard\JsonApi\Model\Base\BaseAbstractResourceModel;
use \Magento\Framework\Model\Context;
use \Magento\Framework\Registry;
use \Magento\Framework\Model\ResourceModel\AbstractResource;
use \Magento\Framework\Data\Collection\AbstractDb;

class Entity extends BaseAbstractModel
{
    public function __construct(
        Context $context,
        Registry $registry,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
    
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    protected function _construct()
    {
        $this->_init('Amazingcard\JsonApi\Model\Catalog\Product\ResourceModel\Entity');

    }

    public function getProductById($productId) 
    {

        /**
         * @var \Amazingcard\JsonApi\Model\Catalog\Product\ResourceModel\Entity $resource
        */
        $resource = $this->_getResource();
        $resource->setColumns(
            [
                'id'        => 'entity_id',
                'name'      => 'sku',
                'type_id'   => 'type_id'
            ]
        )->setFetchType(BaseAbstractResourceModel::FETCH_ROW);
        $resource->load($this, $productId, 'entity_id');
        return $this->getData();
    }

    public function getProductsByCategoryId($categoryId, $limit, $offset) 
    {

        /**
         * @var \Amazingcard\JsonApi\Model\Catalog\Product\ResourceModel\Entity $resource
        */
        $resource = $this->_getResource();

        $resource->setLimitOffset($limit, $offset)
            ->load($this, $categoryId, 'category_id');
        return $this->getData();
    }

    public function getProductsByNameTemplate($nameTemplate, $limit = null, $offset = null)
    {
        /**
         * @var \Amazingcard\JsonApi\Model\Catalog\Product\ResourceModel\Entity $resource
        */
        $resource = $this->_getResource();
        $resource->setCompareMode(BaseAbstractResourceModel::COMPARE_LIKE)
            ->setLimitOffset($limit, $offset)
            ->setWithCount()
            ->setColumns(
                [
                'id'    => 'entity_id',
                'name'  => 'sku'
                ]
            );
        $resource->load($this, "%{$nameTemplate}%", 'sku');
        return $this->getData();
    }
}