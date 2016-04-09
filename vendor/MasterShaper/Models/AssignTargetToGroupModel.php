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

class AssignTargetToGroupModel extends DefaultModel
{
    protected static $model_table_name = 'assign_targets_to_targets';
    protected static $model_column_prefix = 'atg';
    protected static $model_fields = array(
        'idx' => array(
            FIELD_TYPE => FIELD_INT,
        ),
        'guid' => array(
            FIELD_TYPE => FIELD_GUID,
        ),
        'group_idx' => array(
            FIELD_TYPE => FIELD_INT,
        ),
        'target_idx' => array(
            FIELD_TYPE => FIELD_INT,
        ),
    );

    public function hasGroup()
    {
        if (!$this->hasFieldValue('group_idx')) {
            return false;
        }

        return true;
    }

    public function getGroup()
    {
        if (!$this->hasGroup()) {
            static::raiseError(__CLASS__ .'::hasGroup() returned false!');
            return false;
        }

        if (($value = $this->getFieldValue('group_idx')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        return $value;
    }

    public function setGroup($idx)
    {
        if (!isset($idx) || empty($idx) || !is_numeric($idx)) {
            static::raiseError(__METHOD__ .'(), $idx parameter is invalid!');
            return false;
        }

        if (!$this->setFieldValue('group_idx', $idx)) {
            static::raiseError(__CLASS__ .'::setFieldValue() returned false!');
            return false;
        }

        return true;
    }

    public function hasTarget()
    {
        if (!$this->hasFieldValue('target_idx')) {
            return false;
        }

        return true;
    }

    public function getTarget()
    {
        if (!$this->hasTarget()) {
            static::raiseError(__CLASS__ .'::hasTarget() returned false!');
            return false;
        }

        if (($value = $this->getFieldValue('target_idx')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        return $value;
    }

    public function setTarget($idx)
    {
        if (!isset($idx) || empty($idx) || !is_numeric($idx)) {
            static::raiseError(__METHOD__ .'(), $idx parameter is invalid!');
            return false;
        }

        if (!$this->setFieldValue('target_idx', $idx)) {
            static::raiseError(__CLASS__ .'::setFieldValue() returned false!');
            return false;
        }

        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
