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

    const ATTR_CATEGORY_NAME = 45,
        ATTR_CATEGORY_SLUG = 117;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var \Amazingcard\JsonApi\Model\Catalog\Product\Factory\EntityFactory
     */
    protected $entityFactory;

    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    protected $categoryFactory;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $productFactory;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory
     */
    protected $categoryCollectionFactory;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Collection\
     */
    protected $categoryProductCollection;

    /**
     * @var \Magento\Catalog\Helper\ImageFactory
     */
    protected $imageFactoryHelper;

//    protected $baseImageId = 'category_page_list';
    protected $baseImageId = 'product_base_image';

    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Amazingcard\JsonApi\Model\Catalog\Product\Factory\EntityFactory $entityFactory,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory,
        \Magento\Catalog\Model\ProductFactory $categoryProductFactory,
        \Magento\Catalog\Model\ResourceModel\CategoryProduct $categoryProductModel,     // TODO: remove
        \Magento\Catalog\Helper\ImageFactory $imageFactory
    )
    {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->entityFactory = $entityFactory;
        $this->categoryFactory = $categoryFactory;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->productFactory = $categoryProductFactory;
        $this->imageFactoryHelper = $imageFactory;
    }

    /**
     * Getting featured products (with attribute is_featured = 1)
     *
     * @return array
     */
    public function getFeaturedProducts() {
        $data = $this->getOrderedProductModel(null, 'rand', ['is_featured' => 1]);
        $collection = $data['model'];
        $productsInfo = [
            'data'  => $collection,
            'count' => $data['count']
        ];

        $productsInfo['categories'] = $this->getProductsCategories($collection->getData());
        return $productsInfo;
    }

    /**
     * @TODO Maybe we need to use some seed in cookies for correct paging
     * @param $pager    Pager
     * @param string $order -- i don't know how to use ORDER for RANDOM sort....
     * @return array
     */
    public function getRandomProducts($pager, $order = 'DESC') {

        $data = $this->getOrderedProductModel($pager, 'rand');
        $collection = $data['model'];

        $productsInfo = [
            'data'  => $collection,
            'count' => $data['count']
        ];

        $productsInfo['categories'] = $this->getProductsCategories($collection->getData());
        return $productsInfo;
    }

    /**
     * @param $pager    Pager
     * @param string $order
     * @return array
     */
    public function getRecentProducts($pager, $order = 'DESC') {

        $data = $this->getOrderedProductModel($pager, $order);
        $collection = $data['model'];
        $productsData = $collection->getData();

        $productsInfo = [
            'data'  => $collection,
            'count' => $data['count']
        ];

        $productsInfo['categories'] = $this->getProductsCategories($productsData);
        return $productsInfo;
    }

    /**
     * @param $products
     * @return array
     */
    public function getProductsCategories($products) {
        $productIds = array_column($products, 'entity_id');

        // for optimisation, just 1 query
        $categoryCollection = $this->getCategoriesByProductIds($productIds);
        $categories = $categoryCollection->getItems();
        $result = [];
        foreach($productIds as $productId) {

            /** @var Category $category */
            foreach($categories as $category) {
                if ($category->getData('product_id') == $productId) {

                    // need to set entity_id back for $category->getId() properly work
                    $result[$productId][] = $category->setEntityId($category->getData('category_id'));
                }
            }
        }
        return $result;
    }

    /**
     * @param $pager Pager
     * @param $order string
     * @param $filterAttributes array
     * @return array
     */
    protected function getOrderedProductModel($pager = null, $order = 'rand', $filterAttributes = []) {

        // I become love Magento because of these collections ^.^
        $productCollection = $this->productCollectionFactory->create();

        if($order == 'rand') {
            $productCollection->getSelect()->orderRand();
        } else {
            $productCollection->getSelect()->order('created_at ' . $order);
        }

        $productCollection->addMinimalPrice()
            ->addAttributeToSelect('is_featured', 'left')
            ->addFinalPrice()
            ->addTaxPercents();

        if(!empty($filterAttributes)) {
            foreach($filterAttributes as $name => $value) {
                $productCollection->addAttributeToFilter($name, $value);
            }
        }

        $productsCount = $productCollection->count();

        if (isset($pager)) {
            $productCollection->setPage($pager->getCurrentPage(), $pager->getPageSize());
        }
        $productModel = $this->productFactory->create();

        /** @var \Magento\Catalog\Model\Product $product */
        foreach ($productCollection as &$product) {

            // for additional data
            $productModel->getResource()->load($productModel, $product->getId());
            $imageUrl = $this->imageFactoryHelper->create()->init($productModel, $this->baseImageId)->getUrl();
            $productModel->clearInstance();
            $product->setData('is_salable', $productModel->getIsSalable());
            $product->setData('image_url', $imageUrl);
            $product->setData('product_url', $product->getProductUrl());
            $product->setData('is_visible', $productModel->isVisibleInCatalog());
        }
        unset($product);

        return [
            'model' => $productCollection,
            'count' => $productsCount
        ];
    }

    /**
     * @param $productIds array
     * @return  \Magento\Catalog\Model\ResourceModel\Category\Collection | array
     */
    public function getCategoriesByProductIds($productIds) {

        $categoryCollection = $this->categoryCollectionFactory->create();

        // hack, because there will be many rows with the same entity_id
        // but different product_id. Need this for optimization
        // (get all categories of all specified products using single query).
        $categoryCollection->getSelect()->reset('columns')->columns([
            'entity_id as category_id',
            'attribute_set_id',
            'parent_id',
            'created_at',
            'updated_at',
            'path',
            'position',
            'level',
            'children_count'
        ]);
        $categoryCollection->removeAttributeToSelect('entity_id');
        if ($productIds) {
            $categoryCollection->joinField(
                'product_id',
                'catalog_category_product',
                'product_id',
                'category_id=entity_id',
                ['product_id' => $productIds]
            );
        }

        $categoryCollection
            ->addFieldToSelect('entity_id', 'category_id')
            ->removeFieldFromSelect('entity_id')
            ->joinField(
                'name',
                'catalog_category_entity_varchar',
                'value',
                'entity_id = entity_id',
                ['attribute_id' => self::ATTR_CATEGORY_NAME]
            )->joinField(
                'slug',
                'catalog_category_entity_varchar',
                'value',
                'entity_id = entity_id',
                ['attribute_id' => self::ATTR_CATEGORY_SLUG]
            );

        return $categoryCollection;
    }

    public function getSingleProduct($productId) {
        $product = $this->productFactory->create();
        $product->getResource()->load($product, $productId);

        $product->setData('is_salable', $product->getIsSalable());
        $product->setData('image_url', $this->imageFactoryHelper->create()->init($product, $this->baseImageId)->getUrl());
        $product->setData('product_url', $product->getProductUrl());
        $product->setData('is_visible', $product->isVisibleInCatalog());
//        die(var_dump($product->getData()));
        return $product;
    }
}