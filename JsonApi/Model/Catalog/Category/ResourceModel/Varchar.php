<?php
/**
 * Created by PhpStorm.
 * User: serf
 * Date: 02.09.16
 * Time: 19:24
 */

namespace Amazingcard\JsonApi\Model\Catalog\Category\ResourceModel;

use Amazingcard\JsonApi\Model\Base\BaseAbstractResourceModel;

class Varchar extends BaseAbstractResourceModel
{

    protected $_idFieldName = 'value_id';
    protected $tableName = 'catalog_category_entity_varchar';

    //    public function getList(\Amazingcard\JsonApi\Model\Catalog\Category\Varchar $textModel, $field = 'entity_id', $value = null) {
    //        return $this->load($textModel, $value, $field);
    //    }

    public function getList(\Amazingcard\JsonApi\Model\Catalog\Category\Varchar $varcharModel, $field = 'entity_id', $categoryId = null) 
    {
        return $this->load($varcharModel, $categoryId, $field);
    }
}