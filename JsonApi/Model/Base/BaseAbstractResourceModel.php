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

    /**#@+
     * Fetch modes
     */
    const FETCH_ALL     = 'fetch_all';
    const FETCH_ROW     = 'fetch_row';
    const FETCH_ASSOC   = 'fetch_assoc';
    /**#@-*/

    /**#@+
     * Data compare mode
     */
    const COMPARE_EXACT = 'exact';
    const COMPARE_LIKE  = 'like';
    /**#@-*/

    /**#@+
     * parts of WHERE condition
     */
    const WHERE_CONDITION   = 'where_condition';
    const WHERE_VALUE       = 'where_value';
    const WHERE_TYPE        = 'where_type';
    /**#@-*/

    /**#@+
     * JOIN types
     */
    const JOIN_LEFT     = 'join_left';
    const JOIN_JOIN     = 'join_join';
    const JOIN_INNER    = 'join_inner';
    const JOIN_RIGHT    = 'join_right';
    /**#@-*/

    /** @var  array $columns */
    protected $columns;

    /**
     * If $withCount = true then load function result will be
     *      presented as array contains 'data' and 'count' fields
     * @var $withCount bool
     */
    protected $withCount;

    /**
     * One of values self::COMPARE_*
     * @var string
     */
    protected $compareMode;

    /** @var  string */
    protected $tableName;

    /**
     * WHERE condition parts
     * [
     *  [ 'condition', 'value', 'type'],
     *  [ 'condition', 'value', 'type'],
     * .....
     * ]
     * @var array
     */
    protected $where;

    protected $joins;

    /** @var  integer */
    protected $limit;
    protected $offset;

    protected $fetchType;

    protected $order;

    /**
     * @var  array
    */
    protected $group;

    protected function _construct()
    {
        $this->_init($this->tableName, $this->_idFieldName);
    }

    /**
     * @param $order array
     * @return $this
     */
    public function setOrder($order) {
        $this->order = $order;
        return $this;
    }

    public function setRandOrder() {
        $this->order = 'rand';
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
        $this->joins[] = [
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
        $this->fetchType = $fetchType;
        return $this;
    }

    public function setWithCount($count = true)
    {
        $this->withCount = $count;
        return $this;
    }

    public function addWhere($condition, $value = null, $type = null) 
    {
        $this->where[] = [
            self::WHERE_CONDITION => $condition,
            self::WHERE_VALUE     => $value,
            self::WHERE_TYPE      => $type
        ];
        return $this;
    }

    public function clearJoin() 
    {
        $this->joins = [];
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
            $this->limit = $limit;
        }

        if (isset($offset)) {
            $this->offset = $offset;
        }
        return $this;
    }

    public function setCompareMode($compareMode = self::COMPARE_EXACT) 
    {
        $this->compareMode = $compareMode;
        return $this;
    }

    /**
     * @param $columns array|string
     * @return $this
     */
    public function setGroupBy($columns) 
    {

        if (is_array($columns)) {
            $this->columns = $columns;
        } else {
            $this->columns = [$columns];
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
        if(!empty($this->columns)) {
            $select->reset(\Zend_Db_Select::COLUMNS);
            $select->columns($this->columns);
        }

        if(!empty($this->joins)) {
            foreach($this->joins as $joinData) {
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

            switch($this->compareMode) {
                case self::COMPARE_LIKE:
                    $select->where("$field LIKE('$value')");
                    break;
                default:
                    $select->where($field . '=?', $value);
                    break;
            }
        }

        if(!empty($this->where)) {
            foreach($this->where as $_ => $row) {
                $select->where(
                    $row[self::WHERE_CONDITION],
                    $row[self::WHERE_VALUE],
                    $row[self::WHERE_TYPE]
                );
            }
        }

        if ($this->limit) {
            $select->limit(
                $this->limit,
                $this->offset ? $this->offset : null
            );
        }

        if($this->order) {
            if($this->order === 'rand') {
                $select->orderRand();
            } else {
                $select->order($this->order);
            }
        }

        if ($this->group) {
            $select->group($this->group);
        }
        //var_dump($select->assemble());
        return $select;
    }

    /**
     * @param $select \Magento\Framework\DB\Select
     * @return mixed
     */
    protected function setCountSelect($select)
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
        $this->columns = $columns;
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

            if (isset($this->fetchType)) {
                if ($this->fetchType == self::FETCH_ROW) {
                    $data = $connection->fetchRow($select);
                } elseif ($this->fetchType == self::FETCH_ASSOC) {
                    $data = $connection->fetchAssoc($select);
                } else {
                    $data = $connection->fetchAll($select);
                }
            } else {
                $data = $connection->fetchAll($select);
            }

            if ($this->withCount) {
                $select = $this->setCountSelect($select);
                $count = $connection->fetchOne($select);
            }

            if (!$data) {
                $data = [];
            }
//die(var_dump($select->assemble()));
            if ($this->withCount) {
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