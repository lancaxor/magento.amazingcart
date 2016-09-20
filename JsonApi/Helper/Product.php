<?php
/**
 * Created by PhpStorm.
 * User: serf
 * Date: 15.09.16
 * Time: 12:19
 */

namespace Amazingcard\JsonApi\Helper;


use Amazingcard\JsonApi\Model\Base\BaseAbstractModel;
use Amazingcard\JsonApi\Model\Base\BaseAbstractResourceModel;

class Product
{
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $productCollectionFactory;
    protected $entityFactory;

    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Amazingcard\JsonApi\Model\Catalog\Product\Factory\EntityFactory $entityFactory
    )
    {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->entityFactory = $entityFactory;
    }

    /**
     * Getting featured products (with attribute is_featured = 1)
     * @return array
     */
    public function getFeaturedProducts() {
        return $this->productCollectionFactory->create()
            ->addAttributeToFilter('is_featured', 1)
            ->addAttributeToSelect('*')
            ->load(true)
            ->getData();
    }

    /**
     * @TODO Maybe we need to use some seed in cookies for correct paging
     * @param $pager    Pager
     * @param string $order -- i don't know how to use ORDER for RANDOM sort....
     * @return array
     */
    public function getRandomProducts($pager, $order = 'DESC') {

        /** @var BaseAbstractModel $model */
        $model = $this->getOrderedProductModel($pager, 'rand');
        $productsInfo = $model->getData();
        return $productsInfo;
    }

    /**
     * @param $pager    Pager
     * @param string $order
     * @return array
     */
    public function getRecentProducts($pager, $order = 'DESC') {

        /** @var BaseAbstractModel $model */
        $model = $this->getOrderedProductModel($pager, 'created_at ' . $order);
        $productsInfo = $model->getData();
        return $productsInfo;
    }

    /**
     * @param $pager    Pager
     * @param $order    string
     * @return BaseAbstractModel
     */
    protected function getOrderedProductModel($pager, $order) {

        $model = $this->entityFactory->getObject();

        /** @var BaseAbstractResourceModel $resource */
        $resource = $model->getResource();
        $resource->setLimitOffset($pager->getLimit(), $pager->getOffset())
            ->setWithCount(true);
        if($order == 'rand') {
            $resource->setRandOrder();
        } else {
            $resource->setOrder('created_at ' . $order);
        }
        $resource->load($model);
        return $model;
    }
}