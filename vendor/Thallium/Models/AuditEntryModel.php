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
 */

namespace Thallium\Models ;

class AuditEntryModel extends DefaultModel
{
    protected static $model_table_name = 'audit';
    protected static $model_column_prefix = 'audit';
    protected static $model_fields = array(
        'idx' => array(
            FIELD_TYPE => FIELD_INT,
        ),
        'guid' => array(
            FIELD_TYPE => FIELD_STRING,
        ),
        'type' => array(
            FIELD_TYPE => FIELD_STRING,
        ),
        'scene' => array(
            FIELD_TYPE => FIELD_STRING,
        ),
        'message' => array(
            FIELD_TYPE => FIELD_STRING,
        ),
        'time' => array(
            FIELD_TYPE => FIELD_TIMESTAMP,
        ),
        'object_guid' => array(
            FIELD_TYPE => FIELD_STRING,
        ),
    );

    protected function preSave()
    {
        if (($time = microtime(true)) === false) {
            static::raiseError(__METHOD__ .'microtime() returned false!');
            return false;
        }

        if (!$this->setFieldValue('time', $time)) {
            static::raiseError(__CLASS__ .'::setFieldValue() returned false!');
            return false;
        }

        return true;
    }

    public function hasMessage()
    {
        if (!$this->hasFieldValue('message')) {
            return false;
        }

        return true;
    }

    public function getMessage()
    {
        if (!$this->hasMessage()) {
            static::raiseError(__CLASS__ .'::hasMessage() returned false!');
            return false;
        }

        if (($message = $this->getFieldValue('message')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        return $message;
    }

    public function setMessage($message)
    {
        if (!isset($message) || empty($message) || !is_string($message)) {
            static::raiseError(__METHOD__ .'(), $message parameter is invalid!');
            return false;
        }

        if (strlen($message) > 8192) {
            static::raiseError(__METHOD__ .'(), $message is too long!');
            return false;
        }

        if (!$this->setFieldValue('message', $message)) {
            static::raiseError(__CLASS__ .'::setFieldValue() returned false!');
            return false;
        }

        return true;
    }

    public function hasEntryType()
    {
        if (!$this->hasFieldValue('type')) {
            return false;
        }

        return true;
    }

    public function getEntryType()
    {
        if (!$this->hasEntryType()) {
            static::raiseError(__CLASS__ .'::hasEntryType() returned false!');
            return false;
        }

        if (($type = $this->getFieldValue('type')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        return $type;
    }

    public function setEntryType($entry_type)
    {
        if (!isset($entry_type) || empty($entry_type) || !is_string($entry_type)) {
            static::raiseError(__METHOD__ .'(), $entry_type parameter is invalid!');
            return false;
        }

        if (strlen($entry_type) > 255) {
            static::raiseError(__METHOD__ .'(), $entry_type is tooo long!');
            return false;
        }

        if (!$this->setFieldValue('type', $entry_type)) {
            static::raiseError(__CLASS__ .'::setFieldValue() returned false!');
            return false;
        }

        return true;
    }

    public function hasScene()
    {
        if (!$this->hasFieldValue('scene')) {
            return false;
        }

        return true;
    }

    public function getScene()
    {
        if (!$this->hasScene()) {
            static::raiseError(__CLASS__ .'::hasScene() returned false!');
            return false;
        }

        if (($scene = $this->getFieldValue('scene')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        return $message;
    }

    public function setScene($scene)
    {
        if (!isset($scene) || empty($scene) || !is_string($scene)) {
            static::raiseError(__METHOD__ .'(), $scene parameter is invalid!');
            return false;
        }

        if (strlen($scene) > 255) {
            return false;
        }

        if (!$this->setFieldValue('scene', $scene)) {
            static::raiseError(__CLASS__ .'::setFieldValue() returned false!');
            return false;
        }

        return true;
    }

    public function hasEntryGuid()
    {
        if (!$this->hasFieldValue('object_guid')) {
            return false;
        }

        return true;
    }

    public function getEntryGuid()
    {
        if (!$this->hasEntryGuid()) {
            static::raiseError(__CLASS__ .'::hasEntryGuid() returned false!');
            return false;
        }

        if (($guid = $this->getFieldValue('object_guid')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        return $guid;
    }

    public function setEntryGuid($guid)
    {
        if (!isset($guid) || empty($guid) || !is_string($guid)) {
            static::raiseError(__METHOD__ .'(), $guid parameter is invalid!');
            return false;
        }

        if (strlen($guid) > 255) {
            static::raiseError(__METHOD__ .'(), $guid is tooo long!');
            return false;
        }

        if (!$this->setFieldValue('object_guid', $guid)) {
            static::raiseError(__CLASS__ .'::setFieldValue() returned false!');
            return false;
        }

        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
