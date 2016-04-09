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

class UserModel extends DefaultModel
{
    protected static $garbled_password = "**+*!!!*+**";
    protected static $model_table_name = 'users';
    protected static $model_column_prefix = 'user';
    protected static $model_fields = array(
        'idx' => array(
            FIELD_TYPE => FIELD_INT,
        ),
        'guid'  => array(
            FIELD_TYPE => FIELD_GUID,
        ),
        'name'  => array(
            FIELD_TYPE => FIELD_STRING,
        ),
        'password'  => array(
            FIELD_TYPE => FIELD_STRING,
            FIELD_SET => 'setPassword',
        ),
        'manage_chains'  => array(
            FIELD_TYPE => FIELD_YESNO,
        ),
        'manage_pipes'  => array(
            FIELD_TYPE => FIELD_YESNO,
        ),
        'manage_filters'  => array(
            FIELD_TYPE => FIELD_YESNO,
        ),
        'manage_ports'  => array(
            FIELD_TYPE => FIELD_YESNO,
        ),
        'manage_protocols'  => array(
            FIELD_TYPE => FIELD_YESNO,
        ),
        'manage_targets'  => array(
            FIELD_TYPE => FIELD_YESNO,
        ),
        'manage_users'  => array(
            FIELD_TYPE => FIELD_YESNO,
        ),
        'manage_options'  => array(
            FIELD_TYPE => FIELD_YESNO,
        ),
        'manage_servicelevels'  => array(
            FIELD_TYPE => FIELD_YESNO,
        ),
        'show_rules'  => array(
            FIELD_TYPE => FIELD_YESNO,
        ),
        'load_rules'  => array(
            FIELD_TYPE => FIELD_YESNO,
        ),
        'show_monitor'  => array(
            FIELD_TYPE => FIELD_YESNO,
        ),
        'active'  => array(
            FIELD_TYPE => FIELD_YESNO,
            FIELD_DEFAULT => 'Y',
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

    protected function preDelete()
    {
        if (isset($this->user_idx) and $this->user_idx == 1) {
            static::raiseError(__METHOD__ .'(), it is not allowed to delete the user with ID 1!');
            return false;
        }

        return true;
    }

    public function hasPassword()
    {
        if (!$this->hasFieldValue('password')) {
            return false;
        }

        return true;
    }

    public function getGarbledPassword()
    {
        return static::$garbled_password;
    }

    public function setPassword($password)
    {
        if (!isset($password) || empty($password) || !is_string($password)) {
            static::raiseError(__METHOD__ .'(), $password parameter is invalid!');
            return false;
        }

        if ($password == static::$garbled_password) {
            return true;
        }

        if (($hashed = hash('sha256', $password)) === false) {
            static::raiseError(__METHOD__ .'(), hash() returned false!');
            return false;
        }

        if (!isset($hashed) || empty($hashed) || !is_string($hashed)) {
            static::raiseError(__METHOD__ .'(), hash() returned invalid data!');
            return false;
        }

        if (!$this->setFieldValue('password', $hashed)) {
            static::raiseError(__CLASS__ .'::setFieldValue() returned false!');
            return false;
        }

        return true;
    }

    public function doesManage($permission)
    {
        if (!isset($permission) || empty($permission) || !is_string($permission)) {
            static::raiseError(__METHOD__ .'(), $permission parameter is invalid!');
            return false;
        }

        if (!static::hasField($permission)) {
            static::raiseError(__METHOD__ .'(), $permission parameter refers an invalid field!');
            return false;
        }

        if (!$this->hasFieldValue($permission)) {
            return false;
        }

        if (($value = $this->getFieldValue($permission)) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        if ($value != 'Y') {
            return false;
        }

        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
