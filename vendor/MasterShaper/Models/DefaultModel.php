<?php

/**
 * This file is part of MasterShaper.
 *
 * MasterShaper, a web application to handle Linux's traffic shaping
 * Copyright (C) 2007-2016 Andreas Unterkircher <unki@netshadow.net>
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

namespace MasterShaper\Models;

abstract class DefaultModel extends \Thallium\Models\DefaultModel
{
    public function hasName()
    {
        if (!isset(static::$model_column_prefix) || empty(static::$model_column_prefix)) {
            $this->raiseError(__METHOD__ .'(), can not continue without column name!');
            return false;
        }

        if (!static::hasFields()) {
            $this->raiseError(__METHOD__ .'(), model has no fields defined!');
            return false;
        }

        $name_field = static::$model_column_prefix .'_name';

        if (!static::hasField('name')) {
            return false;
        }

        if (!isset($this->$name_field) ||
            empty($this->$name_field) ||
            !is_string($this->$name_field)
        ) {
            return false;
        }

        return true;
    }

    public function getName()
    {
        if (!isset(static::$model_column_prefix) || empty(static::$model_column_prefix)) {
            $this->raiseError(__METHOD__ .'(), can not continue without column name!');
            return false;
        }

        if (!static::hasFields()) {
            $this->raiseError(__METHOD__ .'(), model has no fields defined!');
            return false;
        }

        $name_field = static::$model_column_prefix .'_name';

        if (static::hasField('name')) {
            return $this->$name_field;
        }

        $file_field = static::$model_column_prefix .'_file_name';

        if (static::hasField('file_name')) {
            return $this->$file_field;
        }

        $this->raiseError(__METHOD__ .'(), no clue where to get the name from!');
        return false;
    }

    public function isActive()
    {
        if (!isset(static::$model_column_prefix) || empty(static::$model_column_prefix)) {
            $this->raiseError(__METHOD__ .'(), can not continue without column name!');
            return false;
        }

        if (!static::hasFields()) {
            $this->raiseError(__METHOD__ .'(), model has no fields defined!');
            return false;
        }

        $active_field = static::$model_column_prefix .'_active';

        if (!static::hasField('active')) {
            $this->raiseError(__METHOD__ .'(), model has no "active" field!');
            return false;
        }

        if ($this->$active_field != 'Y') {
            return false;
        }

        return true;
    }

    public function getSafeLink()
    {
        if (!static::hasFields()) {
            $this->raiseError(__METHOD__ .'(), model has no fields defined!');
            return false;
        }

        if (!static::hasField('idx')) {
            $this->raiseError(__METHOD__ .'(), model has no "idx" field!');
            return false;
        }

        if (!static::hasField('guid')) {
            $this->raiseError(__METHOD__ .'(), model has no "guid" field!');
            return false;
        }

        return sprintf(
            "%s-%s",
            $this->getId(),
            $this->getGuid()
        );
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
