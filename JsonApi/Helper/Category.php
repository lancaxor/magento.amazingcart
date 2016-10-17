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

        return $collection->load();
    }

    public function getById($categoryId) {
        $category = $this->categoryFactory
            ->create();
        $category->getResource()
            ->load($category, $categoryId);
        return $category;
    }
}