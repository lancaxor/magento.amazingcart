<?php

/**
 * Created by PhpStorm.
 * User: serf
 * Date: 01.09.16
 * Time: 15:57
 */

namespace Amazingcard\JsonApi\Model\Base;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

abstract class BaseAbstractResourceModel extends AbstractDb
{

    // fetch mode
    const FETCH_ALL = 'fetch_all',
        FETCH_ROW   = 'fetch_row',
        FETCH_ASSOC = 'fetch_assoc';

    // modes for comparing db
    const COMPARE_EXACT = 'exact',
        COMPARE_LIKE    = 'like';

    // parts for 'where'
    const WHERE_CONDITION   = 'where_condition',
        WHERE_VALUE         = 'where_value',
        WHERE_TYPE          = 'where_type';

    const JOIN_LEFT = 'join_left',
        JOIN_JOIN   = 'join_join',
        JOIN_INNER  = 'join_inner',
        JOIN_RIGHT  = 'join_right';

    protected $_columns;

    protected $_withCount;

    protected $_compareMode;

    protected $_tableName;

    /*
     * [
     *  [ 'condition', 'value', 'type'],
     *  [ 'condition', 'value', 'type'],
     * .....
     * ]
     */
    protected $_where;

    protected $_joins;

    protected $_limit;
    protected $_offset;

    protected $_fetchType;

    protected $_order;

    /**
     * @var  array
    */
    protected $_group;

    protected function _construct()
    {
        $this->_init($this->_tableName, $this->_idFieldName);
    }

    /**
     * @param $order array
     * @return $this
     */
    public function setOrder($order) {
        $this->_order = $order;
        return $this;
    }

    /**
     * Register joins
     * Only joins, no right/left/inner/outer/etc!
     * ~I`m too lazy to add them~
     *
     * @param  $name
     * @param  $condition
     * @param  $columns
     * @param  $schema
     * @param  $joinType
     * @return $this
     */
    public function addJoin($name, $condition, $columns = '*', $schema = null, $joinType = BaseAbstractResourceModel::JOIN_JOIN)
    {
        $this->_joins[] = [
            'name'      => $name,
            'condition' => $condition,
            'columns'   => $columns,
            'schema'    => $schema,
            'joinType'  => $joinType
        ];
        return $this;
    }

    public function setFetchType($fetchType) 
    {
        $this->_fetchType = $fetchType;
        return $this;
    }

    public function setWithCount($count = true)
    {
        $this->_withCount = $count;
        return $this;
    }

    public function addWhere($condition, $value = null, $type = null) 
    {
        $this->_where[] = [
            self::WHERE_CONDITION => $condition,
            self::WHERE_VALUE     => $value,
            self::WHERE_TYPE      => $type
        ];
        return $this;
    }

    public function clearJoin() 
    {
        $this->_joins = [];
        return $this;
    }

    /**
     * Set limit and offset for loadSelect
     * If limit or offset = null then it will not be set
     *
     * @param  int $limit
     * @param  int $offset
     * @return $this
     */
    public function setLimitOffset($limit = null, $offset = null) 
    {
        if (isset($limit)) {
            $this->_limit = $limit;
        }

        if (isset($offset)) {
            $this->_offset = $offset;
        }
        return $this;
    }

    public function setCompareMode($compareMode = self::COMPARE_EXACT) 
    {
        $this->_compareMode = $compareMode;
        return $this;
    }

    /**
     * @param $columns array|string
     * @return $this
     */
    public function setGroupBy($columns) 
    {

        if (is_array($columns)) {
            $this->_columns = $columns;
        } else {
            $this->_columns = [$columns];
        }
        return $this;
    }

    /**
     * I need all values!!1!
     *
     * @param  string                                 $field
     * @param  mixed                                  $value
     * @param  \Magento\Framework\Model\AbstractModel $object
     * @return \Magento\Framework\DB\Select
     */
    protected function _getLoadSelect($field, $value = null, $object = null) 
    {

        $select = $this->getConnection()->select()->from($this->getMainTable());

        // select custom columns
        if(!empty($this->_columns)) {
            $select->reset(\Zend_Db_Select::COLUMNS);
            $select->columns($this->_columns);
        }

        if(!empty($this->_joins)) {
            foreach($this->_joins as $joinData) {
                switch($joinData['joinType']) {
                    case self::JOIN_LEFT:
                        $select->joinLeft(
                            $joinData['name'],
                            $joinData['condition'],
                            $joinData['columns'],
                            $joinData['schema']
                        );
                        break;
                    default:
                        $select->join(
                            $joinData['name'],
                            $joinData['condition'],
                            $joinData['columns'],
                            $joinData['schema']
                        );
                        break;
                }
            }
        }

        // ability to select all data without filtering
        if (isset($value) and !empty($field)) {
            $field = $this->getConnection()->quoteIdentifier(sprintf('%s.%s', $this->getMainTable(), $field));

            switch($this->_compareMode) {
            case self::COMPARE_LIKE:
                $select->where("$field LIKE('$value')");
                break;
            default:
                $select->where($field . '=?', $value);
                break;
            }
        }

        if(!empty($this->_where)) {
            foreach($this->_where as $_ => $row) {
                $select->where(
                    $row[self::WHERE_CONDITION],
                    $row[self::WHERE_VALUE],
                    $row[self::WHERE_TYPE]
                );
            }
        }

        if ($this->_limit) {
            $select->limit(
                $this->_limit,
                $this->_offset ? $this->_offset : null
            );
        }

        if($this->_order) {
            $select->order($this->_order);
        }

        if ($this->_group) {
            $select->group($this->_group);
        }
        //var_dump($select->assemble());
        return $select;
    }

    /**
     * @param $select \Magento\Framework\DB\Select
     * @return mixed
     */
    protected function _setCountSelect($select) 
    {
        $countSelect = clone $select;
        $countSelect->reset(\Magento\Framework\DB\Select::ORDER);
        $countSelect->reset(\Magento\Framework\DB\Select::LIMIT_COUNT);
        $countSelect->reset(\Magento\Framework\DB\Select::LIMIT_OFFSET);
        $countSelect->reset(\Magento\Framework\DB\Select::COLUMNS);

        if (!count($select->getPart(\Magento\Framework\DB\Select::GROUP))) {
            $countSelect->columns(new \Zend_Db_Expr('COUNT(*)'));
            return $countSelect;
        }

        $countSelect->reset(\Magento\Framework\DB\Select::GROUP);
        $group = $select->getPart(\Magento\Framework\DB\Select::GROUP);
        $countSelect->columns(new \Zend_Db_Expr(("COUNT(DISTINCT ".implode(", ", $group).")")));
        return $countSelect;
    }

    /**
     * @param $columns
     * @return $this
     */
    public function setColumns($columns) 
    {
        $this->_columns = $columns;
        return $this;
    }

    /**
     * Load an object
     *
     * @param  \Magento\Framework\Model\AbstractModel $object
     * @param  mixed                                  $value
     * @param  string                                 $field  field to load by (defaults to model id)
     * @return $this
     */
    public function load(\Magento\Framework\Model\AbstractModel $object, $value = null, $field = null)
    {
        $count = 0;
        if ($field === null) {
            $field = $this->getIdFieldName();
        }

        $connection = $this->getConnection();
        if ($connection) {

            $select = $this->_getLoadSelect($field, $value, $object);

            if (isset($this->_fetchType)) {
                if ($this->_fetchType == self::FETCH_ROW) {
                    $data = $connection->fetchRow($select);
                } elseif ($this->_fetchType == self::FETCH_ASSOC) {
                    $data = $connection->fetchAssoc($select);
                } else {
                    $data = $connection->fetchAll($select);
                }
            } else {
                $data = $connection->fetchAll($select);
            }

            if ($this->_withCount) {
                $select = $this->_setCountSelect($select);
                $count = $connection->fetchOne($select);
            }

            if (!$data) {
                $data = [];
            }
//die(var_dump($select->assemble()));
            if ($this->_withCount) {
                $object->setData(
                    [
                        'data'  => $data,
                        'count' => $count
                    ]
                );
            } else {
                $object->setData($data);
            }
        }

        $this->unserializeFields($object);
        $this->_afterLoad($object);
        return $this;
    }

}