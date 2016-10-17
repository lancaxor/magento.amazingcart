<?php
/**
 * Created by PhpStorm.
 * User: serf
 * Date: 07.09.16
 * Time: 14:35
 */

namespace Amazingcard\JsonApi\Helper;


class Category
{
    /**
     * @var \Magento\Catalog\Api\CategoryRepositoryInterface
     */
    protected $categoryRepository;

    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    protected $categoryFactory;

    public function __construct(
        \Magento\Catalog\Model\CategoryFactory $categoryFactory
    ) {
        $this->categoryFactory = $categoryFactory;
    }

    public function getList() {
        $model = $this->categoryFactory->create();
        $collection = $model->getCollection();

        $collection->addFieldToSelect('name')
            ->addFieldToSelect('image')
            ->addFieldToSelect('url_key');

        /** @var \Magento\Catalog\Model\Category $item */
//        foreach($collection as &$item) {
//            $item->setData('url', $item->getUrl());   // don't need this
//            $item->setData('url_key', $item->getUrlKey());
//            $item->setData('product_count', $item->getProductCount());
//            $item->setData('image_url', $item->getImageUrl());
//            var_dump($item->getData());
//        }
//        die('testted');
//        $data = $collection->getData();
//        die(var_dump($data));
        return $collection->load();
    }
}