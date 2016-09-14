<?php
/**
 * Created by PhpStorm.
 * User: serf
 * Date: 02.09.16
 * Time: 19:21
 */

namespace Amazingcard\JsonApi\Model\Catalog\Category;

use Amazingcard\JsonApi\Model\Base\BaseAbstractModel;
use Amazingcard\JsonApi\Model\Base\BaseAbstractResourceModel;
use Amazingcard\JsonApi\Model\Catalog\Category\Factory\VarcharFactory;
use \Magento\Framework\Model\Context;
use \Magento\Framework\Registry;
use \Magento\Framework\Model\ResourceModel\AbstractResource;
use \Magento\Framework\Data\Collection\AbstractDb;


class Varchar extends BaseAbstractModel
{

    const ATTRIBUTE_NAME    = 45,
        ATTRIBUTE_TYPE      = 52,
        ATTRIBUTE_URL_NAME  = 117,
        ATTRIBUTE_PATH      = 118;

    protected $_varcharFactory;

    public function __construct(
        Context $context,
        Registry $registry,
        VarcharFactory $varcharFactory,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->_varcharFactory= $varcharFactory;
    }

    protected function _construct()
    {
        $this->_init('Amazingcard\JsonApi\Model\Catalog\Category\ResourceModel\Varchar');
    }

    /**
     * Get name of specified category (or list of names)
     *
     * @param  integer|null $categoryId
     * @return mixed
     */
    public function getNames($categoryId = null) 
    {

        /**
         * @var \Amazingcard\JsonApi\Model\Catalog\Category\ResourceModel\Varchar $resource
        */
        $resource = $this->_getResource();
        $resource->addWhere('attribute_id=?', self::ATTRIBUTE_NAME)
            ->setColumns(
                [
                'id'        => 'entity_id',
                'name'      => 'value'
                ]
            )
            ->setFetchType(isset($categoryId) ? BaseAbstractResourceModel::FETCH_ROW :BaseAbstractResourceModel::FETCH_ASSOC);  // assoc by entity_id
        $resource->getList($this, 'entity_id', $categoryId);
        return $this->getData();
    }
}