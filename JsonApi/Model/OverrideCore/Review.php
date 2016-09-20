<?php

/**
 * Created by PhpStorm.
 * User: serf
 * Date: 06.09.16
 * Time: 15:51
 */

namespace Amazingcard\JsonApi\Model\OverrideCore;

/**
 * Class Review
 * @package Amazingcard\JsonApi\Model\OverrideCore
 * @SuppressWarnings(UnnecessaryFullyQualifiedName)
 */
class Review extends \Magento\Review\Model\Review
{

    protected $_customResource;
    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Review\Model\ResourceModel\Review\Product\CollectionFactory $productFactory
     * @param \Magento\Review\Model\ResourceModel\Review\Status\CollectionFactory $statusFactory
     * @param \Magento\Review\Model\ResourceModel\Review\Summary\CollectionFactory $summaryFactory
     * @param \Magento\Review\Model\Review\SummaryFactory $summaryModFactory
     * @param \Magento\Review\Model\Review\Summary $reviewSummary
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\UrlInterface $urlModel
     * @param \Amazingcard\JsonApi\Model\OverrideCore\ResourceModel\Review $resource    // /this is why I've overloaded the constructor -_-
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Review\Model\ResourceModel\Review\Product\CollectionFactory $productFactory,
        \Magento\Review\Model\ResourceModel\Review\Status\CollectionFactory $statusFactory,
        \Magento\Review\Model\ResourceModel\Review\Summary\CollectionFactory $summaryFactory,
        \Magento\Review\Model\Review\SummaryFactory $summaryModFactory,
        \Magento\Review\Model\Review\Summary $reviewSummary,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\UrlInterface $urlModel,
        \Amazingcard\JsonApi\Model\OverrideCore\ResourceModel\Review $resource = null,      // only for this! >.<
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
//        $this->productCollectionFactory = $productFactory;
//        $this->_statusFactory = $statusFactory;
//        $this->_summaryFactory = $summaryFactory;
//        $this->_summaryModFactory = $summaryModFactory;
//        $this->_reviewSummary = $reviewSummary;
//        $this->_storeManager = $storeManager;
//        $this->_urlModel = $urlModel;
        $this->_customResource = $resource;
        parent::__construct(
            $context, $registry,
            $productFactory,
            $statusFactory,
            $summaryFactory,
            $summaryModFactory,
            $reviewSummary,
            $storeManager,
            $urlModel,
            $resource,
            $resourceCollection
        );  // damn....

//        $this->_resource = $resource;
    }

    /**
     * Initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Amazingcard\JsonApi\Model\OverrideCore\ResourceModel\Review::class);
    }

    /**
     * @param $productId
     * @return mixed
     */
    public function getList($productId) {

        /** @var $resource \Amazingcard\JsonApi\Model\OverrideCore\ResourceModel\Review */
        $resource = $this->_getResource();
        return $resource->getList($this, $productId);
    }
}