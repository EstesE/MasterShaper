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

class AssignFilterToPipeModel extends DefaultModel
{
    protected static $model_table_name = 'assign_filters_to_pipes';
    protected static $model_column_prefix = 'afp';
    protected static $model_fields = array(
        'idx' => array(
            FIELD_TYPE => FIELD_INT,
        ),
        'guid' => array(
            FIELD_TYPE => FIELD_GUID,
        ),
        'pipe_idx' => array(
            FIELD_TYPE => FIELD_INT,
        ),
        'filter_idx' => array(
            FIELD_TYPE => FIELD_INT,
        ),
    );
    protected static $model_links = array(
        'FilterModel/idx' => 'filter_idx',
        'PipeModel/idx' => 'pipe_idx',
    );

    public function hasPipe()
    {
        if (!$this->hasFieldValue('pipe_idx')) {
            return false;
        }

        return true;
    }

    public function getPipe()
    {
        if (!$this->hasPipe()) {
            static::raiseError(__CLASS__ .'::hasPipe() returned false!');
            return false;
        }

        if (($value = $this->getFieldValue('pipe_idx')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        return $value;
    }

    public function setPipe($idx)
    {
        if (!isset($idx) || empty($idx) || !is_numeric($idx)) {
            static::raiseError(__METHOD__ .'(), $idx parameter is invalid!');
            return false;
        }

        if (!$this->setFieldValue('pipe_idx', $idx)) {
            static::raiseError(__CLASS__ .'::setFieldValue() returned false!');
            return false;
        }

        return true;
    }

    public function hasFilter()
    {
        if (!$this->hasFieldValue('filter_idx')) {
            return false;
        }

        return true;
    }

    public function getFilter($load = false)
    {
        global $cache;

        if (!$this->hasFilter()) {
            static::raiseError(__CLASS__ .'::hasFilter() returned false!');
            return false;
        }

        if (($value = $this->getFieldValue('filter_idx')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        if (!isset($load) || $load === false) {
            return $value;
        }

        if (!$cache->has("filter_${value}")) {
            try {
                $filter = new \MasterShaper\Models\FilterModel(array(
                    FIELD_IDX => $value,
                ));
            } catch (\Exception $e) {
                static::raiseError(__METHOD__ .'(), failed to load FilterModel!', false, $e);
                return false;
            }
            if (!$cache->add($filter, "filter_${value}")) {
                static::raiseError(get_class($cache) .'::add() returned false!');
                return false;
            }
        } else {
            if (($filter = $cache->get("filter_${value}")) === false) {
                static::raiseError(get_class($cache) .'::get() returned false!');
                return false;
            }
            if (!$filter->resetFields()) {
                static::raiseError(get_class($filter) .'::resetFields() returned false!');
                return false;
            }
        }

        return $filter;
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

    public function isFilterActive()
    {
        if (!$this->hasFieldValue('filter_active')) {
            return false;
        }

        if (($value = $this->getFieldValue('filter_active')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        if ($value !== 'Y') {
            return false;
        }

        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
