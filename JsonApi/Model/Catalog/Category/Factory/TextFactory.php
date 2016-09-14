<?php
/**
 * Created by PhpStorm.
 * User: serf
 * Date: 01.09.16
 * Time: 12:30
 */

namespace Amazingcard\JsonApi\Model\Catalog\Category\Factory;

use Amazingcard\JsonApi\Model\Base\BaseAbstractFactory;

class TextFactory extends BaseAbstractFactory
{
    /**
     * Create new Text model
     *
     * @param  array $arguments
     * @return \Amazingcard\JsonApi\Model\Catalog\Category\Text
     */
    public function getObject(array $arguments = [])
    {
        return $this->get('Amazingcard\JsonApi\Model\Catalog\Category\Text', $arguments);
    }
}