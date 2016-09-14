<?php

/**
 * Created by PhpStorm.
 * User: serf
 * Date: 06.09.16
 * Time: 15:55
 */

namespace Amazingcard\JsonApi\Model\OverrideCore\ResourceModel;

class Review extends \Magento\Review\Model\ResourceModel\Review
{

    /**
     * @param \Amazingcard\JsonApi\Model\OverrideCore\Review $object
     * @param null $productId
     * @return array
     */
    public function getList($object, $productId = 0) {

        $connection = $this->getConnection();
        $select = $this->_getLoadSelect('entity_pk_value', $productId, $object);
        $select->columns(new \Zend_Db_Expr('unix_timestamp(created_at) as timestamp'));
//        die(var_dump($select->assemble()));
        $data = $connection->fetchAll($select);
        return $data;
    }

    /**
     * Retrieve select object for load object data
     *
     * @param string $field
     * @param mixed $value
     * @param AbstractModel $object
     * @return \Magento\Framework\DB\Select
     */
//    protected function _getLoadSelect($field, $value, $object)
//    {
//        $select = parent::_getLoadSelect($field, $value, $object);
////        $select = $this->getConnection()->select()->from($this->getMainTable())->where($field . '=?', $value);
//        $select->join(
//            $this->_reviewDetailTable,
//            $this->getMainTable() . ".review_id = {$this->_reviewDetailTable}.review_id"
//        );
//        return $select;
//    }
}