<?php

/**
 * This file is part of MasterShaper.
 *
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

class SettingsModel extends DefaultModel
{
    protected static $model_table_name = 'settings';
    protected static $model_column_prefix = 'setting';
    protected static $model_has_items = true;
    protected static $model_items_model = 'SettingModel';
    private $settings = array();

    protected function __init()
    {
        $this->permitRpcUpdates(true);
        $this->addRpcAction('delete');
        return true;
    }

    public function __construct()
    {
        parent::__construct();

        if (!$this->hasItems()) {
            return false;
        }

        if (($items = $this->getItems()) === false) {
            static::raiseError(__CLASS__ .'::getItems() returned false!');
            return false;
        }

        if (!isset($items) || empty($items) || !is_array($items)) {
            static::raiseError(__CLASS__ .'::getItems() returned invalid data!');
            return false;
        }

        foreach ($items as $item) {
            if (!$item->hasKey()) {
                continue;
            }

            if (($key = $item->getKey()) === false) {
                static::raiseError(get_class($item) .'::getKey() returned false!');
                return false;
            }

            $this->settings[$key] = $item;
        }

        return;
    }

    protected function hasOption($option)
    {
        if (!isset($option) || empty($option) || !is_string($option)) {
            static::raiseError(__METHOD__ .'(), $option parameter is invalid!');
            return false;
        }

        if (!array_key_exists($option, $this->settings)) {
            return false;
        }

        return true;
    }

    public function hasSetting($option)
    {
        if (!isset($option) || empty($option) || !is_string($option)) {
            static::raiseError(__METHOD__ .'(), $option parameter is invalid!');
            return false;
        }

        if (!$this->hasOption($option)) {
            return false;
        }

        return true;
    }

    protected function getOption($option)
    {
        if (!isset($option) || empty($option) || !is_string($option)) {
            static::raiseError(__METHOD__ .'(), $option parameter is invalid!');
            return false;
        }

        if (!$this->hasOption($option)) {
            static::raiseError(__CLASS__ .'::hasOption() returned false!');
            return false;
        }

        if (!array_key_exists($option, $this->settings)) {
            static::raiseError(__METHOD__ .'(), requested option not found in settings!');
            return false;
        }

        return $this->settings[$option];
    }

    public function getSettingId($option)
    {
        if (!isset($option) || empty($option) || !is_string($option)) {
            static::raiseError(__METHOD__ .'(), $option parameter is invalid!');
            return false;
        }

        if (!$this->hasOption($option)) {
            static::raiseError(__CLASS__ .'::hasOption() returned false!');
            return false;
        }

        if (($setting =& $this->getOption($option)) === false) {
            static::raiseError(__CLASS__ .'::getOption() returned false!');
            return false;
        }

        if (($id = $setting->getId()) === false) {
            static::raiseError(get_class($setting) .'::getId() returned false!');
            return false;
        }

        return $id;
    }

    public function getSettingGuid($option)
    {
        if (!isset($option) || empty($option) || !is_string($option)) {
            static::raiseError(__METHOD__ .'(), $option parameter is invalid!');
            return false;
        }

        if (!$this->hasOption($option)) {
            static::raiseError(__CLASS__ .'::hasOption() returned false!');
            return false;
        }

        if (($setting =& $this->getOption($option)) === false) {
            static::raiseError(__CLASS__ .'::getOption() returned false!');
            return false;
        }

        if (($guid = $setting->getGuid()) === false) {
            static::raiseError(get_class($setting) .'::getGuid() returned false!');
            return false;
        }

        return $guid;
    }

    public function hasSettingValue($option)
    {
        if (!isset($option) || empty($option) || !is_string($option)) {
            static::raiseError(__METHOD__ .'(), $option parameter is invalid!');
            return false;
        }

        if (!$this->hasOption($option)) {
            static::raiseError(__CLASS__ .'::hasOption() returned false!');
            return false;
        }

        if (($setting =& $this->getOption($option)) === false) {
            static::raiseError(__CLASS__ .'::getOption() returned false!');
            return false;
        }

        if (!$setting->hasValue()) {
            return false;
        }

        return true;
    }

    public function getSettingValue($option)
    {
        if (!isset($option) || empty($option) || !is_string($option)) {
            static::raiseError(__METHOD__ .'(), $option parameter is invalid!');
            return false;
        }

        if (!$this->hasOption($option)) {
            static::raiseError(__CLASS__ .'::hasOption() returned false!');
            return false;
        }

        if (!$this->hasSettingValue($option)) {
            static::raiseError(__CLASS__ .'::hasSettingValue() returned false!');
            return false;
        }

        if (($setting =& $this->getOption($option)) === false) {
            static::raiseError(__CLASS__ .'::getOption() returned false!');
            return false;
        }

        if (($value = $setting->getValue()) === false) {
            static::raiseError(get_class($setting) .'::getValue() returned false!');
            return false;
        }

        return $value;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
