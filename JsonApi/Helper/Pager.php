<?php
/**
 * Created by PhpStorm.
 * User: serf
 * Date: 15.09.16
 * Time: 12:24
 */

namespace Amazingcard\JsonApi\Helper;

/**
 * Class Pager
 * Class name is sayin' for itself
 * @package Amazingcard\JsonApi\Helper
 */
class Pager
{

    /**
     * @var bool strict mode. If true, user cannot call getTotalPages and getTotalItems
     *  before setTotalCount call. Additionally, all input values will be checked before assigning.
     *  By default, strict mode is off.
     */
    protected $strict = false;

    protected $pageNumber = null;
    protected $itemsPerPage = null;
    protected $pagesTotal = null;
    protected $itemsTotal = null;

    #region setters
    public function setStrict($strict = true) {
        $this->strict = $strict;
        return $this;
    }

    public function setPage($pageNumber = 0) {

        if($this->strict && $pageNumber < 0) {
            $pageNumber = 0;
        }
        $this->pageNumber = $pageNumber;
        return $this;
    }

    public function setPageSize($itemsPerPage = 10) {

        if($this->strict && $itemsPerPage < 0) {
            $itemsPerPage = 0;
        }

        $this->itemsPerPage = $itemsPerPage;
        return $this;
    }

    public function setTotalCount($totalCount) {

        if($this->strict && !$totalCount) {
            $totalCount = 0;
        }

        $this->itemsTotal = $totalCount;
        return $this;
    }
    #endregion setters

    #region getters
    public function getLimit() {
        return $this->itemsPerPage;
    }

    public function getOffset() {
        return ($this->itemsPerPage * $this->pageNumber);
    }

    public function getCurrentPage() {
        return $this->pageNumber;
    }

    public function getPageSize() {
        return $this->itemsPerPage;
    }

    public function getTotalPages() {

        if($this->strict && !isset($this->itemsTotal)) {
            throw new \Exception('Missing required value itemsTotal!');
        }

        $this->pagesTotal = ($this->itemsPerPage ? ceil($this->itemsTotal / $this->itemsPerPage): 1);
        return $this->pagesTotal;
    }

    public function getTotalItems() {

        if($this->strict && !isset($this->itemsTotal)) {
            throw new \Exception('Missing required value itemsTotal!');
        }

        return $this->itemsTotal;
    }
    #endregion getters

    public function reset() {
        $this->strict = false;
        $this->pageNumber = null;
        $this->itemsPerPage = null;
        $this->pagesTotal = null;
        $this->itemsTotal = null;
        return $this;
    }
}