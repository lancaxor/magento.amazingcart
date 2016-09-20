<?php
/**
 * Created by PhpStorm.
 * User: serf
 * Date: 12.09.16
 * Time: 11:33
 */

namespace Amazingcard\JsonApi\Helper;

use \Amazingcard\JsonApi\Model\OverrideCore\Factory\ReviewFactory;
use Magento\Review\Model\RatingFactory;


class Review
{
    /**
     * @var ReviewFactory
     */
    protected $reviewFactory;

    /**
     * @var RatingFactory
     */
    protected $ratingFactory;

    /**
     * @var User
     */
    protected $userHelper;


    public function __construct(
        ReviewFactory $reviewFactory,
        RatingFactory $ratingFactory,
        User    $userHelper
    ) {
        $this->reviewFactory = $reviewFactory;
        $this->ratingFactory = $ratingFactory;
        $this->userHelper = $userHelper;
    }

    public function getReviewsByProduct($productId = null) {
        return $this->reviewFactory->create()->getList($productId);
    }

    /**
     * @param $reviewId integer
     * @return array
     */
    public function getReviewById($reviewId) {
        if(!isset($reviewId)) {
            return $this->generateError('No Input', -1);
        }
        $model = $this->reviewFactory->create();
        $model->getResource()->load($model, $reviewId, 'review_id');
        $reviewData = $model->getData();
        $customer = $this->userHelper->getUserById($reviewData['customer_id']);
        $reviewData['statusId'] = $model->getStatusId();
        return [
            'status'    => '0',
            'data'      => [
                'review'    => $reviewData,
                'customer'  => [
                    'id'    => $customer->getId(),
                    'email' => $customer->getEmail(),
                    'first_name'    => $customer->getFirstname()
                ]
            ]
        ];
    }

    public function addReview($userLogin, $userPassword, $reviewData) {

        $loginData = $this->userHelper->login($userLogin, $userPassword);
        if(isset($loginData['error'])) {

            return $loginData;
        }

        if(!isset($reviewData['productId'], $reviewData['comment']/*, $reviewData['rating']*/ )) {
            return $this->generateError('No input', -1);
        }
        /**
         * @var $customer \Magento\Customer\Api\Data\CustomerInterface
         */
        $customer = $loginData['data']['customer'];

        $reviewTitleLimit = 100;

        $model = $this->reviewFactory->create();

        // edit entity
        $model->setEntityId(1)  // product
            ->setEntityPkValue($reviewData['productId'])
            ->setStatusId(\Magento\Review\Model\Review::STATUS_APPROVED);

        // edit details
        $model->setDetail($reviewData['comment'])       //idk why, but it works 0_o
            ->setTitle(mb_substr($reviewData['comment'], 0, $reviewTitleLimit))
            ->setNickname($customer->getFirstname())
            ->setCustomerId($customer->getId())
            ->setStoreId($customer->getStoreId())
            ->setStores([$customer->getStoreId()]);

        try {
            $model->getResource()->save($model);
        } catch (\Exception $e) {
            return $this->generateError($e->getMessage());
        }

        $reviewId = $model->getId();
        return [
            'status'    => 0,
            'reason'    => 'Successfully inserted new comment',
            'commentID' => $reviewId
        ];
    }

    protected function generateError($message, $code = 1) {
        return [
            'error' => $code,
            'status'    => $code,
            'reason'    => $message,
            'data'      => null
        ];
    }
}