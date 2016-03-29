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

class ProtocolModel extends DefaultModel
{
    protected static $model_table_name = 'protocols';
    protected static $model_column_prefix = 'proto';
    protected static $model_fields = array(
        'idx' => array(
            FIELD_TYPE => FIELD_INT,
        ),
        'guid' => array(
            FIELD_TYPE => FIELD_GUID,
        ),
        'name' => array(
            FIELD_TYPE => FIELD_STRING,
        ),
        'desc' => array(
            FIELD_TYPE => FIELD_STRING,
        ),
        'number' => array(
            FIELD_TYPE => FIELD_INT,
        ),
        'user_defined' => array(
            FIELD_TYPE => FIELD_YESNO,
        ),
        'active' => array(
            FIELD_TYPE => FIELD_YESNO,
        ),
    );

    protected function __init()
    {
        $this->permitRpcUpdates(true);
        $this->addRpcAction('delete');
        $this->addRpcAction('update');
        $this->addRpcEnabledField('name');
        return true;
    }

    public function hasDescription()
    {
        if (!isset($this->model_values['desc']) ||
            empty($this->model_values['desc']) ||
            !is_string($this->model_values['desc'])
        ) {
            return false;
        }

        return true;
    }

    public function getDescription()
    {
        if (!$this->hasDescription()) {
            $this->raiseError(__CLASS__ .'::hasDescription() returned false!');
            return false;
        }

        return $this->model_values['desc'];
    }

    public function hasNumber()
    {
        if (!isset($this->model_values['number']) ||
            empty($this->model_values['number']) ||
            !is_string($this->model_values['number'])
        ) {
            return false;
        }

        return true;
    }

    public function getNumber()
    {
        if (!$this->hasNumber()) {
            $this->raiseError(__CLASS__ .'::hasNumber() returned false!');
            return false;
        }

        return $this->model_values['number'];
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
