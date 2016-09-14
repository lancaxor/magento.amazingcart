<?php

/**
 * Created by PhpStorm.
 * User: serf
 * Date: 05.09.16
 * Time: 14:04
 */

namespace Amazingcard\JsonApi\Model\Catalog\Category\ResourceModel;

use Amazingcard\JsonApi\Model\Base\BaseAbstractModel;
use Amazingcard\JsonApi\Model\Base\BaseAbstractResourceModel;

class Entity extends BaseAbstractResourceModel
{
    protected $_idFieldName = 'entity_id';
    protected $_tableName = 'catalog_category_entity';

    /**
     * Table 'catalog_category_entity_varchar'
     * @var String
     */
    protected $_varcharTable;

    /**
     * Define main table. Define other tables name
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_varcharTable = $this->getTable('catalog_category_entity_varchar');
    }

    /**
     * DAAAAAMN >.<
     * Add product count
     * STUPID MODELS ARCHITECTURE >.<
     * @param $categoryProductModel \Amazingcard\JsonApi\Model\Catalog\Category\Product
     * @return $this
     */
    public function addProductCountInfo(\Amazingcard\JsonApi\Model\Catalog\Category\Product $categoryProductModel) {
        //die(var_dump());
        $categoryProductTable = $categoryProductModel->getResource()->getMainTable();
        $currentTableName = $this->getMainTable();

        // we need group, so just join will not work
        $countSelect = $this->getConnection()->select()
            ->from($categoryProductTable,[
                'category_id'   => 'category_id',
                'product_cnt' => new \Zend_Db_Expr('count(*)')
            ])
            ->group('category_id');
        $countCode = $countSelect->assemble();
//die(var_dump($countCode));
        $this->addJoin(
            ['rel_category_product'    => new \Zend_Db_Expr("($countCode)")],
            "rel_category_product.category_id = {$currentTableName}.entity_id",
            ['product_count' => 'rel_category_product.product_cnt'],
            null,
            BaseAbstractResourceModel::JOIN_LEFT
        );
        return $this;
    }

    /**
     * Overriding parent::loadSelect for joining varcharTable (with category names)
     * @param $field
     * @param $value
     * @param $object BaseAbstractModel
     * @return array
     */
    public function _getLoadSelect($field, $value = null, $object = null) {

        $select = parent::_getLoadSelect($field, $value, $object);
        $select->join(
            $this->_varcharTable,
            $this->getMainTable() . ".entity_id = {$this->_varcharTable}.entity_id AND {$this->_varcharTable}.attribute_id=45"   // attribute_id = 45 => category name
        );
        return $select;
    }
}