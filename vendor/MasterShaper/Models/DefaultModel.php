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
    protected static $model_icon;

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

        if (!static::hasField('name')) {
            return false;
        }

        if (!isset($this->model_values['name']) ||
            empty($this->model_values['name']) ||
            !is_string($this->model_values['name'])
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
            return $this->model_values['name'];
        }

        $file_field = static::$model_column_prefix .'_file_name';

        if (static::hasField('file_name')) {
            return $this->model_values['file_name'];
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

        if (!static::hasField('active')) {
            $this->raiseError(__METHOD__ .'(), model has no "active" field!');
            return false;
        }

        if ($this->model_values['active'] != 'Y') {
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
            $this->getIdx(),
            $this->getGuid()
        );
    }

    public static function hasModelIcon()
    {
        $called_class = get_called_class();

        if (!property_exists($called_class, 'model_icon') ||
            !isset($called_class::$model_icon) ||
            empty($called_class::$model_icon) ||
            !is_string($called_class::$model_icon)
        ) {
            return false;
        }

        return true;
    }

    public static function getModelIcon()
    {
        if (!static::hasModelIcon()) {
            static::raiseError(__CLASS__ .'::hasModelIcon() returned false!');
        }

        $called_class = get_called_class();

        return $called_class::$model_icon;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
