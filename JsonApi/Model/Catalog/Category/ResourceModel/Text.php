<?php

/**
 * Created by PhpStorm.
 * User: serf
 * Date: 01.09.16
 * Time: 11:43
 */
namespace Amazingcard\JsonApi\Model\Catalog\Category\ResourceModel;

use Amazingcard\JsonApi\Model\Base\BaseAbstractResourceModel;

class Text extends BaseAbstractResourceModel
{
    
    protected $_idFieldName = 'value_id';
    protected $_tableName = 'catalog_category_entity_text';

    public function getList(\Amazingcard\JsonApi\Model\Catalog\Category\Text $textModel, $field = 'entity_id', $value = null) 
    {
        $this->load($textModel, $value, $field);
        return $this;
        //return $textModel->getData();
        //return $resource;
    }
}