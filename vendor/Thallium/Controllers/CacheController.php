<?php

/**
 * This file is part of Thallium.
 *
 * Thallium, a PHP-based framework for web applications.
 * Copyright (C) <2015-2016> <Andreas Unterkircher>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.

 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 */

namespace Thallium\Controllers;

class CacheController extends DefaultController
{
    protected $cache = array();
    protected $cache_index = array();

    public function __construct()
    {
        parent::__construct();

        if (!isset($this->cache) || !is_array($this->cache)) {
            static::raiseError(__METHOD__ .'(), $cache property is not an array!', true);
            return;
        }

        if (!isset($this->cache_index) || !is_array($this->cache_index)) {
            static::raiseError(__METHOD__ .'(), $cache_index property is not an array!', true);
            return;
        }
    }

    public function add(&$cacheobj, $lookup_key = null)
    {
        global $thallium;

        if (!isset($cacheobj)) {
            static::raiseError(__METHOD__ .'(), $cacheobj parameter is invalid!');
            return false;
        }

        if (isset($lookup_key) &&
            !is_null($lookup_key) && (
            empty($lookup_key) || (
            !is_numeric($lookup_key) &&
            !is_string($lookup_key) &&
            !is_integer($lookup_key)))
        ) {
            static::raiseError(__METHOD__ .'(), $lookup_key parameter is invalid!');
        } elseif (!isset($lookup_key) || is_null($lookup_key)) {
            if (($lookup_key = $thallium->createGuid()) === false) {
                static::raiseError(get_class($thallium) .'::createGuid() returned false!');
                return false;
            }
        } elseif (isset($lookup_key) && is_bool($lookup_key) && $lookup_key === true) {
            $err_prefix = __METHOD__ .'(), lookup key auto-detection has been activated, but model has no ';
            if (!isset($cacheobj::$model_column_prefix)) {
                static::raiseError($err_prefix .'model_column_prefix constant!');
                return false;
            }
            if (!method_exists($cacheobj, 'getGuid') || !is_callable(array(&$this, 'getGuid'))) {
                static::raiseError($err_prefix .'has no getGuid() method!');
                return false;
            }
            if (($cacheobj_guid = $cacheobj->getGuid()) === false) {
                static::raiseError($err_prefix .'has no GUID!');
                return false;
            }
            $lookup_key = sprintf("%s_%s", $cacheobj::$model_column_prefix, $cacheobj_guid);
        }

        if ($this->has($lookup_key)) {
            static::raiseError(__METHOD__ .'(), an object with the same lookup key is already present!');
            return false;
        }

        $this->cache[] = $cacheobj;
        if (end($this->cache) === false) {
            static::raiseError(__METHOD__ .'(), unable to set array pointer to the last element!');
            return false;
        }

        if (($cache_key = key($this->cache)) === null) {
            static::raiseError(__METHOD__ .'(), unable to get the cache key!');
            return false;
        }

        $this->cache_index[$lookup_key] = $cache_key;
        return $lookup_key;
    }

    public function get($lookup_key)
    {
        if (!isset($lookup_key) ||
            empty($lookup_key) || (
            !is_string($lookup_key) &&
            !is_numeric($lookup_key) &&
            !is_integer($lookup_key))
        ) {
            static::raiseError(__METHOD__ .'(), $lookup_key parameter is invalid!');
            return false;
        }

        if (!$this->has($lookup_key)) {
            static::raiseError(__CLASS__ .'::has() returned false!');
            return false;
        }

        $cache_key = $this->cache_index[$lookup_key];

        return $this->cache[$cache_key];
    }

    public function has($lookup_key)
    {
        if (!isset($lookup_key) ||
            empty($lookup_key) || (
            !is_string($lookup_key) &&
            !is_numeric($lookup_key) &&
            !is_integer($lookup_key))
        ) {
            static::raiseError(__METHOD__ .'(), $lookup_key parameter is invalid!');
            return false;
        }

        if (!array_key_exists($lookup_key, $this->cache_index)) {
            return false;
        }

        return true;
    }

    public function del($lookup_key)
    {
        if (!isset($lookup_key) ||
            empty($lookup_key) || (
            !is_string($lookup_key) &&
            !is_numeric($lookup_key) &&
            !is_integer($lookup_key))
        ) {
            static::raiseError(__METHOD__ .'(), $lookup_key parameter is invalid!');
            return false;
        }

        if (!$this->has($lookup_key)) {
            static::raiseError(__CLASS__ .'::has() returned false!');
            return false;
        }

        $cache_key = $this->cache_index[$lookup_key];
        unset($this->cache_index[$lookup_key]);
        unset($this->cache[$cache_key]);
        return true;
    }

    public function dump()
    {
        if (!isset($this->cache) || empty($this->cache)) {
            return null;
        }

        return $this->cache;
    }

    public function dumpIndex()
    {
        if (!isset($this->cache_index) || empty($this->cache_index)) {
            return null;
        }

        return $this->cache_index;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
