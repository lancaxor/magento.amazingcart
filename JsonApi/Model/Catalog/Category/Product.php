<?php
/**
 * Created by PhpStorm.
 * User: serf
 * Date: 01.09.16
 * Time: 16:55
 */

namespace Amazingcard\JsonApi\Model\Catalog\Category;

use Amazingcard\JsonApi\Model\Base\BaseAbstractResourceModel;
use Amazingcard\JsonApi\Model\Catalog\Category\Factory\TextFactory;
use Amazingcard\JsonApi\Model\Catalog\Product\Factory\EntityFactory;
use Magento\Framework\Model\AbstractModel;
use \Magento\Framework\Model\Context;
use \Magento\Framework\Registry;
use \Amazingcard\JsonApi\Model\Catalog\Category\Factory\ProductFactory;
use \Magento\Framework\Model\ResourceModel\AbstractResource;
use \Magento\Framework\Data\Collection\AbstractDb;

class Product extends AbstractModel
{

    protected $_productFactory;
    protected $_textFactory;
    protected $_productEntityFactory;

    public function __construct(
        Context $context,
        Registry $registry,
        ProductFactory $productFactory,
        TextFactory $textFactory,
        EntityFactory $productEntityFactory,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->_productEntityFactory = $productEntityFactory;
        $this->_productFactory = $productFactory;
        $this->_textFactory = $textFactory;
    }

    protected function _construct()
    {
        $this->_init('Amazingcard\JsonApi\Model\Catalog\Category\ResourceModel\Product');
    }

    /**
     * Get data in format: {
     *  categoryId, categoryName, categorySlug, current_page,
     * total_page, post_per_page, total_post, products[]
     * }
     *
     * @param  $limit
     * @param  $offset
     * @param  $categoryId integer
     * @return mixed
     */
    public function getList($limit, $offset, $categoryId = 0) 
    {

        /**
         * @var \Amazingcard\JsonApi\Model\Catalog\Category\ResourceModel\Product $resource
        */
        $resource = $this->_getResource();
        $resource->setColumns(
            [
            'categoryId'    => 'category_id',
            'products'      => 'group_concat(product_id separator \',\')'
            ]
        )
            ->addCategoryNameInfo($this->_textFactory->getObject())
            ->setLimitOffset($limit, $offset)
            ->getList($this);
        return $this->getData();
    }

    public function getProductsByCategory($categoryId, $limit = null, $offset = null) 
    {

        /**
 * @var \Amazingcard\JsonApi\Model\Catalog\Category\ResourceModel\Product $resource 
*/
        $resource = $this->_getResource();

        if($limit || $offset) {
            $resource->setLimitOffset($limit, $offset);
        }

        $resource->setColumns(
            [
            'product_id'    => 'product_id',
            'category_id'   => 'category_id'
            ]
        )
            ->setWithCount(true)
            ->addProductEntityInfo($this->_productEntityFactory->getObject())
            ->setFetchType(BaseAbstractResourceModel::FETCH_ALL)
            ->getList($this, 'category_id', $categoryId);
        return $this->getData();
    }
}