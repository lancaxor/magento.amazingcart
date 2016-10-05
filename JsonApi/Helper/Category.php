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
    protected $categoryFactory;

    public function __construct(
        \Magento\Catalog\Model\CategoryFactory $categoryFactory
    ) {
        $this->categoryFactory = $categoryFactory;
    }

    public function getCategoriesByProductIds($productIds) {
        $categoryModel = $this->categoryFactory->create();
        $categories = $categoryModel->getCollection()
            ->addFieldToFilter('product_id', $productIds)
            ->addFieldToSelect('*');

        /** @var \Magento\Catalog\Model\Category $category */
        foreach($categories as $category) {
            var_dump($category->getName());
        }
        die('damnit');
        return $categories;
    }
}