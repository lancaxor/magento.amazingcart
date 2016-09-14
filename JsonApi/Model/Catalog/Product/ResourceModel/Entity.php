<?php

/**
 * Created by PhpStorm.
 * User: serf
 * Date: 05.09.16
 * Time: 14:04
 */

namespace Amazingcard\JsonApi\Model\Catalog\Product\ResourceModel;

use Amazingcard\JsonApi\Model\Base\BaseAbstractResourceModel;

class Entity extends BaseAbstractResourceModel
{
    protected $_idFieldName = 'entity_id';
    protected $_tableName = 'catalog_product_entity';
}