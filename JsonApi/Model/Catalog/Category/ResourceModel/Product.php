<?php

/**
 * Created by PhpStorm.
 * User: serf
 * Date: 01.09.16
 * Time: 17:05
 */

namespace Amazingcard\JsonApi\Model\Catalog\Category\ResourceModel;

use Amazingcard\JsonApi\Model\Base\BaseAbstractResourceModel;
use Amazingcard\JsonApi\Model\Catalog\Category\Text;

class Product extends BaseAbstractResourceModel
{
    protected $_idFieldName = 'entity_id';
    protected $tableName = 'catalog_category_product';

    /**
     * @param $textModel \Amazingcard\JsonApi\Model\Catalog\Category\Text
     * @return Product
     */
    public function addCategoryNameInfo(Text $textModel) 
    {
        $textTableName = $textModel->getResource()->getMainTable();
        $currentTableName = $this->getMainTable();
        $this->addJoin(
            ['category_text' => $textTableName],
            "category_text.value_id = {$currentTableName}.category_id",
            ['categoryName' => 'category_text.value']
        );
        return $this;
    }

    public function addProductEntityInfo(\Amazingcard\JsonApi\Model\Catalog\Product\Entity $productEntity)
    {
        $entityTableName = $productEntity->getResource()->getMainTable();
        $currentTableName = $this->getMainTable();
        $this->addJoin(
            ['product_entity'    => $entityTableName],
            "product_entity.entity_id = {$currentTableName}.product_id",
            ['product_name' => 'product_entity.sku']
        );
        return $this;
    }


    public function getList(\Amazingcard\JsonApi\Model\Catalog\Category\Product $productModel, $whereField = null, $whereValue = null) 
    {
        return $this->load($productModel, $whereValue, $whereField);
    }
}