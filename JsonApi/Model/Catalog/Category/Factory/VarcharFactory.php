<?php
/**
 * Created by PhpStorm.
 * User: serf
 * Date: 02.09.16
 * Time: 19:22
 */

namespace Amazingcard\JsonApi\Model\Catalog\Category\Factory;

use Amazingcard\JsonApi\Model\Base\BaseAbstractFactory;

class VarcharFactory extends BaseAbstractFactory
{
    /**
     * Create new Text model
     *
     * @param  array $arguments
     * @return \Amazingcard\JsonApi\Model\Catalog\Category\Varchar
     */
    public function getObject(array $arguments = [])
    {
        return $this->get('Amazingcard\JsonApi\Model\Catalog\Category\Varchar', $arguments);
    }
}