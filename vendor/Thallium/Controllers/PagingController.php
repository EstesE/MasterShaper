<?php

/**
 * This file is part of Thallium.
 *
 * Thallium, a PHP-based framework for web applications.
 * Copyright (C) <2015> <Andreas Unterkircher>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.

 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 */

namespace Thallium\Controllers;

class PagingController extends DefaultController
{
    protected $pagingData = array();
    protected $pagingParameters = array();
    protected $currentPage;
    protected $currentItemsLimit;
    protected $itemsPerPageLimits = array(
        10, 25, 50, 100, 0
    );

    public function __construct($params)
    {
        if (!isset($params) || empty($params) || !is_array($params)) {
            static::raiseError(__CLASS__ .'::__construct(), $params parameter is invalid!', true);
            return false;
        }

        if (!$this->setPagingParameters($params)) {
            static::raiseError(__CLASS__ .'::setPagingParameters() returned false!', true);
            return false;
        }

        return true;
    }

    public function setPagingData(&$data)
    {
        if (!isset($data) || empty($data) || !is_array($data)) {
            static::raiseError(__METHOD__ .'(), $data parameter is invalid!');
            return false;
        }

        if ($this->isPagingDataSet()) {
            static::raiseError(__METHOD__ .'(), paging data already set!');
            return false;
        }

        $this->pagingData = $data;
        return true;
    }

    public function getPagingData($data)
    {
        if (!$this->isPagingDataSet()) {
            return false;
        }

        return $this->pagingData;
    }

    protected function setPagingParameters($params)
    {
        if (!isset($params) || empty($params) || !is_array($params)) {
            static::raiseError(__METHOD__ .'(), $params is invalid!');
            return false;
        }

        if (isset($this->pagingParameters) && !empty($this->pagingParameters)) {
            static::raiseError(__METHOD__ .'(), paging parameters already set!');
            return false;
        }

        foreach ($params as $key => $value) {
            if (!($this->setParameter($key, $value))) {
                static::raiseError(__CLASS__ .'::setParameter() returned false!');
                return false;
            }
        }

        return true;
    }

    protected function setParameter($key, $value)
    {
        if (!isset($key) || empty($key) || !is_string($key) ||
            !isset($value) || empty($value) ||
            (!is_string($value) && !is_numeric($value))
        ) {
            static::raiseError(__METHOD__ .'(), $key and/or $value parameters are invalid!');
            return false;
        }

        $this->pagingParameters[$key] = $value;
        return true;
    }

    public function getParameter($key)
    {
        if (!isset($key) || empty($key) || !is_string($key)) {
            static::raiseError(__METHOD__ .'(), $key parameter is invalid!');
            return false;
        }

        if (!isset($this->pagingParameters[$key])) {
            return false;
        }

        return $this->pagingParameters[$key];
    }

    public function getNumberOfPages()
    {
        if (!$this->isPagingDataSet()) {
            static::raiseError(__METHOD__ .'(), paging data has not been set yet!');
            return false;
        }

        if (($items_per_page = $this->getCurrentItemsLimit()) === false) {
            static::raiseError(__CLASS__ .'::getCurrentItemsLimit() returned false!');
            return false;
        }

        if (!isset($items_per_page) || is_null($items_per_page) ||
            !is_numeric($items_per_page) || $items_per_page < 0
        ) {
            static::raiseError(__METHOD__ .'(), $items_per_page not correctly defined!');
            return false;
        }

        $totalItems = count($this->pagingData);

        if ($totalItems < 1) {
            return 1;
        }

        if ($items_per_page > 0) {
            $totalPages = ceil($totalItems/$items_per_page);
        } else {
            $totalPages = 1;
        }

        if (!isset($totalPages) ||
            empty($totalPages) ||
            !is_numeric($totalPages) ||
            $totalPages < 1
        ) {
            static::raiseError(__METHOD__ .'(), failure on calculating total pages!');
            return false;
        }

        return $totalPages;
    }

    public function getCurrentPage()
    {
        if (!isset($this->currentPage) ||
            empty($this->currentPage)
        ) {
            return false;
        }

        if ($this->currentPage > $this->getNumberOfPages()) {
            $this->currentPage = 1;
        }

        return $this->currentPage;
    }

    public function isCurrentPage($pageno)
    {
        if (($curpage = $this->getCurrentPage()) === false) {
            return false;
        }

        if ($pageno != $curpage) {
            return false;
        }

        return true;
    }

    public function setCurrentPage($pageno)
    {
        if (!isset($pageno) ||
            empty($pageno) ||
            !is_numeric($pageno) ||
            $pageno < 1
        ) {
            static::raiseError(__METHOD__ .'(), $pageno parameter is invalid!');
            return false;
        }

        if ($this->isPagingDataSet()) {
            if (($total = $this->getNumberOfPages()) === false) {
                static::raiseError(__CLASS__ .'::getNumberOfPages() returned false!');
                return false;
            }

            if ($pageno > $total) {
                $this->currentPage = 1;
                return true;
            }
        }

        $this->currentPage = $pageno;
        return true;
    }

    public function getPageData()
    {
        if (($page = $this->getCurrentPage()) === false) {
            $page = 1;
        }

        if (!$this->isPagingDataSet()) {
            static::raiseError(__METHOD__ .'(), paging data has not been set yet!');
            return false;
        }

        if (($total = $this->getNumberOfPages()) === false) {
            static::raiseError(__CLASS__ .'::getNumberOfPages() returned false!');
            return false;
        }

        if (($items_per_page = $this->getCurrentItemsLimit()) === false) {
            static::raiseError(__CLASS__ .'::getCurrentItemsLimit() returned false!');
            return false;
        }

        if ($page > $total) {
            $page = 1;
        }

        if (count($this->pagingData) <= $items_per_page) {
            $page = 1;
        }

        if ($items_per_page < 1) {
            return $this->pagingData;
        }

        $data = array_slice(
            $this->pagingData,
            ($page-1)*$items_per_page,
            $items_per_page
        );

        if (!isset($data) || empty($data) || !is_array($data)) {
            static::raiseError(__METHOD__ .'(), slicing paging data failed!');
            return false;
        }

        return $data;
    }

    public function isPagingDataSet()
    {
        if (!isset($this->pagingData) ||
            empty($this->pagingData) ||
            !is_array($this->pagingData)
        ) {
            return false;
        }

        return true;
    }

    public function getNextPageNumber()
    {
        if (($page = $this->getCurrentPage()) === false) {
            static::raiseError(__CLASS__ .'::getCurrentPage() returned false!');
            return false;
        }

        if (($total = $this->getNumberOfPages()) === false) {
            static::raiseError(__CLASS__ .'::getNumberOfPages() returned false!');
            return false;
        }

        if (!isset($page) || empty($page) || !is_numeric($page) ||
            !isset($total) || empty($total) || !is_numeric($total) ||
            $total < 0
        ) {
            static::raiseError(__METHOD__ .'(), incomplete informations!');
            return false;
        }

        if ($page >= $total) {
            return false;
        }

        return $page+1;
    }

    public function getPreviousPageNumber()
    {
        if (($page = $this->getCurrentPage()) === false) {
            static::raiseError(__CLASS__ .'::getCurrentPage() returned false!');
            return false;
        }

        if (!isset($page) || empty($page) || !is_numeric($page)) {
            static::raiseError(__METHOD__ .'(), incomplete informations!');
            return false;
        }

        if ($page <= 1) {
            return false;
        }

        return $page-1;
    }

    public function getFirstPageNumber()
    {
        return 1;
    }

    public function getLastPageNumber()
    {
        if (($pages = $this->getNumberOfPages()) === false) {
            return false;
        }

        return $pages;
    }

    public function getPageNumbers()
    {
        if (($total = $this->getNumberOfPages()) === false) {
            static::raiseError(__CLASS__ .'::getNumberOfPages() returned false!');
            return false;
        }

        if (!isset($total) ||
            empty($total) ||
            !is_numeric($total) ||
            $total < 0
        ) {
            static::raiseError(__CLASS__ .'::getNumberOfPages() returned invalid data!');
            return false;
        }

        $pages = array();
        for ($i = 1; $i <= $total; $i++) {
            $pages[] = $i;
        }

        return $pages;
    }

    public function getDeltaPageNumbers()
    {
        if (($pages = $this->getPageNumbers()) === false) {
            static::raiseError(__CLASS__ .'::getPageNumbers() returned false!');
            return false;
        }

        if (($delta = $this->getParameter('delta')) === false) {
            static::raiseError(__METHOD__ .'(), $delta has not been set!');
            return false;
        }

        if (!($page = $this->getCurrentPage())) {
            $page = 1;
        }

        if (!isset($pages) || empty($pages) || !is_array($pages) ||
            !isset($delta) || empty($delta) || !is_numeric($delta) || $delta < 1 ||
            !isset($page) || empty($page) || !is_numeric($page) || $page < 1
        ) {
            static::raiseError(__METHOD__ .'(), incomplete informations!');
            return false;
        }

        if ($delta >= count($pages)) {
            return $pages;
        }

        if ($delta == 1) {
            return $page;
        }

        if ($page <= $delta) {
            $start = 1;
            $end = ($page+$delta) >= count($pages) ? count($pages) : ($page+$delta) ;
        } elseif ($page+$delta >= count($pages)) {
            $start = $page-$delta;
            $end = count($pages);
        } else {
            $start = $page-$delta;
            $end = $page+$delta;
        }

        /*
        print_r(array('pages' => count($pages), 'page' => $page, 'delta' => $delta, 'start' => $start, 'end' => $end));
        */
        $deltaPages = array();
        for ($i = $start; $i <= $end; $i++) {
            $deltaPages[] = $i;
        }

        return $deltaPages;
    }

    public function getCurrentItemsLimit()
    {
        if (!isset($this->currentItemsLimit)) {
            return $this->itemsPerPageLimits[0];
        }

        return $this->currentItemsLimit;
    }

    public function getItemsLimits()
    {
        return $this->itemsPerPageLimits;
    }

    public function setItemsLimit($limit)
    {
        if (!isset($limit) || !is_numeric($limit)) {
            static::raiseError(__METHOD__ .'(), $limit parameter is invalid!');
            return false;
        }

        if (($limits = $this->getItemsLimits()) === false) {
            static::raiseError(__CLASS__ .'::getCurrentItemsLimits() returned false!');
            return false;
        }

        if ($limit < 0) {
            $this->currentItemsLimit = $limits[0];
            return true;
        }

        if (!in_array($limit, $limits)) {
            static::raiseError(__METHOD__ .'(), $limit parameter is not within allowed-limits list!');
            return false;
        }

        $this->currentItemsLimit = $limit;
        return true;
    }

    public function isCurrentItemsLimit($limit)
    {
        if (!isset($limit) || !is_numeric($limit)) {
            static::raiseError(__METHOD__ .'(), $limit parameter is invalid!');
            return false;
        }

        if (($cur_limit = $this->getCurrentItemsLimit()) === false) {
            static::raiseError(__CLASS__ .'::getCurrentItemsLimit() returned false!');
            return false;
        }

        if ($limit != $cur_limit) {
            return false;
        }

        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
