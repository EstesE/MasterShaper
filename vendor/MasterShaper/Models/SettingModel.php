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

class SettingModel extends DefaultModel
{
    protected static $model_table_name = 'settings';
    protected static $model_column_prefix = 'setting';
    protected static $model_friendly_name = "Setting";
    protected static $model_fields = array(
        'idx' => array(
            FIELD_TYPE => FIELD_INT,
        ),
        'guid' => array(
            FIELD_TYPE => FIELD_GUID,
        ),
        'key' => array(
            FIELD_TYPE => FIELD_STRING,
        ),
        'value' => array(
            FIELD_TYPE => FIELD_STRING,
        ),
    );

    protected function __init()
    {
        $this->permitRpcUpdates(true);
        $this->addRpcAction('delete');
        $this->addRpcAction('update');
        return true;
    }

    public function hasKey()
    {
        if (!$this->hasFieldValue('key')) {
            return false;
        }
        
        return true;
    }

    public function getKey()
    {
        if (!$this->hasKey()) {
            static::raiseError(__CLASS__ .'::hasKey() returned false!');
            return false;
        }

        if (($key = $this->getFieldValue('key')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        return $key;
    }

    public function hasValue()
    {
        if (!$this->hasFieldValue('value')) {
            return false;
        }

        return true;
    }

    public function getValue()
    {
        if (!$this->hasValue()) {
            static::raiseError(__CLASS__ .'::hasValue() returned false!');
            return false;
        }

        if (($value = $this->getFieldValue('value')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        return $value;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
