<?php

/**
 * Created by PhpStorm.
 * User: serf
 * Date: 01.09.16
 * Time: 11:16
 */
namespace Amazingcard\JsonApi\Model\Catalog\Category;

use Amazingcard\JsonApi\Model\Base\BaseAbstractModel;
use Amazingcard\JsonApi\Model\Base\BaseAbstractResourceModel;
use \Magento\Framework\Model\Context;
use \Magento\Framework\Registry;
use \Magento\Framework\Model\ResourceModel\AbstractResource;
use \Magento\Framework\Data\Collection\AbstractDb;

class Text extends BaseAbstractModel
{

    // /** @var  \Magento\Catalog\Model\CategoryFactory */
    //protected $_magentoCategoryFactory;

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
        $this->_init('Amazingcard\JsonApi\Model\Catalog\Category\ResourceModel\Text');
    }

    public function getList($categoryId = null, $getAll = true) 
    {

        /**
         * @var \Amazingcard\JsonApi\Model\Catalog\Category\ResourceModel\Text $resource
        */
        $resource = $this->_getResource();
        $resource->setColumns(
            [
            'id' => 'entity_id',
            'name' => 'value'
            ]
        );

        if (!$getAll) {
            $resource->setFetchType(BaseAbstractResourceModel::FETCH_ROW);
        }
        $resource->getList($this, 'entity_id', $categoryId);
        return $this->getData();
    }
}