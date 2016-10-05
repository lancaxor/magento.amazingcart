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

    protected $coreCategoryFactory;
    protected $categoryProductFactory;
    protected $categoryCollectionFactory;

    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Amazingcard\JsonApi\Model\Catalog\Product\Factory\EntityFactory $entityFactory,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory,
        \Magento\Catalog\Model\ProductFactory $categoryProductFactory
    )
    {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->entityFactory = $entityFactory;
        $this->coreCategoryFactory = $categoryFactory;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->categoryProductFactory = $categoryProductFactory;
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
        $model = $this->getOrderedProductModel($pager, $order);
        $productsInfo = $model->getData();
        $productIds = array_column($productsInfo, 'entity_id');
        $categories = $this->getCategoriesByProductIds($productIds);
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

    /**
     * @param $productIds array
     * @return  \Magento\Catalog\Model\ResourceModel\Category\Collection
     */
    public function getCategoriesByProductIds($productIds) {

        $categoryProduct = $this->categoryProductFactory->create();
        $categoryProduct->getCollection()
            ->removeAllFieldsFromSelect()
            ->addFieldToSelect('product_id')
            ->addFieldToSelect('category_id')
            ->addFilterToSelect('product_id', $productIds);

        $categoryModel = $this->coreCategoryFactory->create();
        $categories1 = $categoryModel->getCollection()
            ->addFieldToFilter('product_id', $productIds)
            ->addFieldToSelect('*');

        $categoryCollection = $this->categoryCollectionFactory->create()
            ->addFieldToFilter('product_id', $productIds)
            ->addFieldToSelect('*')
            ->load();

        $categories = $categoryCollection->getItems();
        var_dump('stage1: ', $categories);

        /** @var \Magento\Catalog\Model\Category $category */
        foreach($categories1 as $category) {
            var_dump($category->getName());
        }
        die('damnit');
        return $categories;
    }
}