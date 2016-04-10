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

class AssignPortToFilterModel extends DefaultModel
{
    protected static $model_table_name = 'assign_ports_to_filters';
    protected static $model_column_prefix = 'afp';
    protected static $model_fields = array(
        'idx' => array(
            FIELD_TYPE => FIELD_INT,
        ),
        'guid' => array(
            FIELD_TYPE => FIELD_GUID,
        ),
        'filter_idx' => array(
            FIELD_TYPE => FIELD_INT,
        ),
        'port_idx' => array(
            FIELD_TYPE => FIELD_INT,
        ),
    );

    public function hasFilter()
    {
        if (!$this->hasFieldValue('filter_idx')) {
            return false;
        }

        return true;
    }

    public function getFilter()
    {
        if (!$this->hasFilter()) {
            static::raiseError(__CLASS__ .'::hasFilter() returned false!');
            return false;
        }

        if (($value = $this->getFieldValue('filter_idx')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        return $value;
    }

    public function setFilter($idx)
    {
        if (!isset($idx) || empty($idx) || !is_numeric($idx)) {
            static::raiseError(__METHOD__ .'(), $idx parameter is invalid!');
            return false;
        }

        if (!$this->setFieldValue('filter_idx', $idx)) {
            static::raiseError(__CLASS__ .'::setFieldValue() returned false!');
            return false;
        }

        return true;
    }

    public function hasPort()
    {
        if (!$this->hasFieldValue('port_idx')) {
            return false;
        }

        return true;
    }

    public function getPort()
    {
        if (!$this->hasPort()) {
            static::raiseError(__CLASS__ .'::hasPort() returned false!');
            return false;
        }

        if (($value = $this->getFieldValue('port_idx')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        return $value;
    }

    public function setPort($idx)
    {
        if (!isset($idx) || empty($idx) || !is_numeric($idx)) {
            static::raiseError(__METHOD__ .'(), $idx parameter is invalid!');
            return false;
        }

        if (!$this->setFieldValue('port_idx', $idx)) {
            static::raiseError(__CLASS__ .'::setFieldValue() returned false!');
            return false;
        }

        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
