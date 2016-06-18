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

class RulesetClassModel extends DefaultModel
{
    protected $if_name;
    protected $handle;
    protected $parent;
    protected $type;
    protected $params;

    static protected $tc_bin;

    public function __construct()
    {
        global $config;

        static::$tc_bin = $config->getTcPath();
        return;
    }

    final public function hasInterface()
    {
        if (!isset($this->if_name) || empty($this->if_name)) {
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

        return $this->if_name;
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

        $this->if_name = $if;
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

    final public function hasHandle()
    {
        if (!isset($this->handle) || empty($this->handle)) {
            return false;
        }

        return true;
    }

    final public function getHandle()
    {
        if (!$this->hasHandle()) {
            static::raiseError(__CLASS__ .'::hasHandle() returned false!');
            return false;
        }

        return $this->handle;
    }

    final public function setHandle($handle)
    {
        if (!isset($handle) || empty($handle) || !is_string($handle)) {
            static::raiseError(__METHOD__ .'(), $handle parameter is invalid!');
            return false;
        }

        $this->handle = $handle;
        return true;
    }

    final public function hasType()
    {
        if (!isset($this->type) || empty($this->type)) {
            return false;
        }

        return true;
    }

    final public function getType()
    {
        if (!$this->hasType()) {
            static::raiseError(__CLASS__ .'::hasType() returned false!');
            return false;
        }

        return $this->type;
    }

    final public function setType($type)
    {
        if (!isset($type) || empty($type) || !is_string($type)) {
            static::raiseError(__METHOD__ .'(), $type parameter is invalid!');
            return false;
        }

        $this->type = $type;
        return true;
    }

    final public function hasParams()
    {
        if (!isset($this->params) || empty($this->params)) {
            return false;
        }

        return true;
    }

    final public function getParams($as_string = false)
    {
        if (!$this->hasParams()) {
            static::raiseError(__CLASS__ .'::hasParams() returned false!');
            return false;
        }

        if (!isset($as_string) && !$as_string) {
            return $this->params;
        }

        $params_str = "";

        foreach ($this->params as $key => $value) {
            $params_str.= sprintf("%s %s ", $key, $value);
        }

        return trim($params_str);
    }

    final public function setParams($params)
    {
        if (!isset($params) || empty($params) || !is_array($params)) {
            static::raiseError(__METHOD__ .'(), $params parameter is invalid!');
            return false;
        }

        $this->params = $params;
        return true;
    }

    final public function __toString()
    {
        if (!$this->hasInterface() ||
            !$this->hasParent() ||
            !$this->hasHandle() ||
            !$this->hasType() ||
            !$this->hasParams()
        ) {
            return "";
        }

        if (($if = $this->getInterfaceName()) === false) {
            return "";
        }
        if (($parent = $this->getParent()) === false) {
            return "";
        }
        if (($handle = $this->getHandle()) === false) {
            return "";
        }
        if (($type = $this->getType()) === false) {
            return "";
        }
        if (($params = $this->getParams()) === false) {
            return "";
        }

        return sprintf(
            "%s class add dev %s parent %s classid %s %s %s",
            static::$tc_bin,
            $if,
            $parent,
            $handle,
            $type,
            $params
        );
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
