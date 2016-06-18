<?php

/**

 * This file is part of MasterShaper.

 * MasterShaper, a web application to handle Linux's traffic shaping
 * Copyright (C) 2007-2016 Andreas Unterkircher <unki@netshadow.net>

 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.

 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.

 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace MasterShaper\Models;

class RulesetFilterModel extends DefaultModel
{
    protected $if;
    protected $parent;
    protected $filter;

    static protected $tc_bin;

    public function __construct()
    {
        global $config;

        static::$tc_bin = $config->getTcPath();
        return;
    }

    final public function hasInterface()
    {
        if (!isset($this->if) || empty($this->if)) {
            return false;
        }

        return true;
    }

    final public function getInterface()
    {
        if (!$this->hasInterface()) {
            static::raiseError(__CLASS__ .'::hasInterface() returned false!');
            return false;
        }

        return $this->if;
    }

    final public function getInterfaceName()
    {
        if (!$this->hasInterface()) {
            static::raiseError(__CLASS__ .'::hasInterface() returned false!');
            return false;
        }

        if (($if = $this->getInterface()) === false) {
            static::raiseError(__CLASS__ .'::getInterface() returned false!');
            return false;
        }

        if (!isset($if) ||
            empty($if) ||
            !is_object($if) ||
            !is_a($if, 'MasterShaper\Models\RulesetInterfaceModel')
        ) {
            static::raiseError(__CLASS__.'::getInterface() returned invalid data!');
            return false;
        }

        if (($name = $if->getInterfaceName()) === false) {
            static::raiseError(get_class($if) .'::getName() returned false!');
            return false;
        }

        return $name;
    }

    final public function setInterface(&$if)
    {
        if (!isset($if) ||
            empty($if) ||
            !is_object($if) ||
            is_a($if, 'MasterShaper\Models\NetworkInterfaceModel')
        ) {
            static::raiseError(__METHOD__ .'(), $if parameter is invalid!');
            return false;
        }

        $this->if = $if;
        return true;
    }

    final public function hasParent()
    {
        if (!isset($this->parent) || empty($this->parent)) {
            return false;
        }

        return true;
    }

    final public function getParent()
    {
        if (!$this->hasParent()) {
            static::raiseError(__CLASS__ .'::hasParent() returned false!');
            return false;
        }

        return $this->parent;
    }

    final public function setParent($parent)
    {
        if (!isset($parent) || empty($parent) || !is_string($parent)) {
            static::raiseError(__METHOD__ .'(), $parent parameter is invalid!');
            return false;
        }

        $this->parent = $parent;
        return true;
    }

    final public function hasFilter()
    {
        if (!isset($this->filter) || empty($this->filter)) {
            return false;
        }

        return true;
    }

    final public function getFilter()
    {
        if (!$this->hasFilter()) {
            static::raiseError(__CLASS__ .'::hasFilter() returned false!');
            return false;
        }

        return $this->filter;
    }

    final public function setFilter($filter)
    {
        if (!isset($filter) || empty($filter) || !is_string($filter)) {
            static::raiseError(__METHOD__ .'(), $filter parameter is invalid!');
            return false;
        }

        $this->filter = $filter;
        return true;
    }

    final public function __toString()
    {
        if (!$this->hasInterface() ||
            !$this->hasParent() ||
            !$this->hasFilter()
        ) {
            return null;
        }

        if (($if = $this->getInterfaceName()) === false) {
            return null;
        }
        if (($parent = $this->getParent()) === false) {
            return null;
        }
        if (($filter = $this->getFilter()) === false) {
            return null;
        }

        return sprintf(
            "%s filter add dev %s parent %s %s",
            static::$tc_bin,
            $if,
            $parent,
            $filter
        );
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
