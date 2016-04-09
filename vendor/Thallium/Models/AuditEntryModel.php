<?php

/**
 * This file is part of Thallium.
 *
 * Thallium, a PHP-based framework for web applications.
 * Copyright (C) <2015> <Andreas Unterkircher>
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
    );

    protected function preSave()
    {
        if (!($time = microtime(true))) {
            static::raiseError("microtime() returned false!");
            return false;
        }

        if (!$this->setFieldValue('time', $time)) {
            static::raiseError(__CLASS__ .'::setFieldValue() returned false!');
            return false;
        }

        return true;
    }

    public function setEntryGuid($guid)
    {
        global $thallium;

        if (empty($guid)) {
            return true;
        }

        if (!$thallium->isValidGuidSyntax($guid)) {
            static::raiseError(get_class($thallium) .'::isValidGuidSyntax() returned false!');
            return false;
        }

        if (!$this->setFieldValue('guid', $guid)) {
            static::raiseError(__CLASS__ .'::setFieldValue() returned false!');
            return false;
        }

        return true;
    }

    public function setMessage($message)
    {
        if (empty($message)) {
            static::raiseError(__METHOD__ .", \$message can not be empty!");
            return false;
        }
        if (!is_string($message)) {
            static::raiseError(__METHOD__ .", \$message must be a string!");
            return false;
        }

        if (strlen($message) > 8192) {
            static::raiseError(__METHOD__ .", \$message is to long!");
            return false;
        }

        if (!$this->setFieldValue('message', $message)) {
            static::raiseError(__CLASS__ .'::setFieldValue() returned false!');
            return false;
        }

        return true;
    }

    public function setEntryType($entry_type)
    {
        if (empty($entry_type)) {
            static::raiseError(__METHOD__ .", \$entry_type can not be empty!");
            return false;
        }
        if (!is_string($entry_type)) {
            static::raiseError(__METHOD__ .", \$entry_type must be a string!");
            return false;
        }

        if (strlen($entry_type) > 255) {
            static::raiseError(__METHOD__ .", \$entry_type is to long!");
            return false;
        }

        if (!$this->setFieldValue('type', $entry_type)) {
            static::raiseError(__CLASS__ .'::setFieldValue() returned false!');
            return false;
        }

        return true;
    }

    public function setScene($scene)
    {
        if (empty($scene)) {
            static::raiseError(__METHOD__ .", \$scene can not be empty!");
            return false;
        }
        if (!is_string($scene)) {
            static::raiseError(__METHOD__ .", \$scene must be a string!");
            return false;
        }

        if (strlen($scene) > 255) {
            static::raiseError(__METHOD__ .", \$scene is to long!");
            return false;
        }

        if (!$this->setFieldValue('scene', $scene)) {
            static::raiseError(__CLASS__ .'::setFieldValue() returned false!');
            return false;
        }

        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
