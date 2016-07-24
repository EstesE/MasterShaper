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

namespace Thallium\Models;

use \PDO;

abstract class DefaultModel
{
    protected static $model_table_name;
    protected static $model_column_prefix;
    protected static $model_fields = array();
    protected static $model_fields_index = array();
    protected static $model_has_items = false;
    protected static $model_items_model;
    protected static $model_links = array();
    protected static $model_bulk_load_limit = 10;
    protected static $model_friendly_name;
    protected $model_load_by = array();
    protected $model_sort_order = array();
    protected $model_items = array();
    protected $model_items_lookup_index = array();
    protected $model_permit_rpc_updates = false;
    protected $model_rpc_allowed_fields = array();
    protected $model_rpc_allowed_actions = array();
    protected $model_virtual_fields = array();
    protected $model_init_values = array();
    protected $model_values = array();
    protected $id;

    protected $child_names;
    protected $ignore_child_on_clone;

    public function __construct($load_by = array(), $sort_order = array())
    {
        if (!isset($load_by) || (!is_array($load_by) && !is_null($load_by))) {
            static::raiseError(__METHOD__ .'(), parameter $load_by has to be an array!', true);
            return;
        }

        if (!isset($sort_order) || (!is_array($sort_order) && !is_null($sort_order))) {
            static::raiseError(__METHOD__ .'(), parameter $sort_order has to be an array!', true);
            return;
        }

        $this->model_load_by = $load_by;
        $this->model_sort_order = $sort_order;

        if (method_exists($this, '__init') && is_callable(array($this, '__init'))) {
            if (!$this->__init()) {
                static::raiseError(__METHOD__ .'(), __init() returned false!', true);
                return;
            }
        }

        if (!$this->validateModelSettings()) {
            return;
        }

        if (static::hasFields() && $this->isNewModel()) {
            $this->initFields();
            return;
        }

        if (!$this->load()) {
            static::raiseError(__CLASS__ ."::load() returned false!", true);
            return false;
        }

        return true;

    } // __construct()

    protected function validateModelSettings()
    {
        global $thallium;

        if (!isset(static::$model_table_name) ||
            empty(static::$model_table_name) ||
            !is_string(static::$model_table_name)
        ) {
            static::raiseError(__METHOD__ .'(), missing property "model_table_name"', true);
            return false;
        }

        if (!isset(static::$model_column_prefix) ||
            empty(static::$model_column_prefix) ||
            !is_string(static::$model_column_prefix)
        ) {
            static::raiseError(__METHOD__ .'(), missing property "model_column_prefix"', true);
            return false;
        }

        if (static::hasFields() && static::hasModelItems()) {
            static::raiseError(__METHOD__ .'(), model must no have fields and items at the same times!', true);
            return false;
        }

        if (static::hasModelItems()) {
            if (!static::hasModelItemsModel() ||
                ($items_model = static::getModelItemsModel()) === false ||
                !$thallium->isRegisteredModel(null, $items_model)
            ) {
                static::raiseError(__METHOD__ .'(), $model_items_model is invalid!', true);
                return false;
            }
        }

        if (!isset(static::$model_fields) || !is_array(static::$model_fields)) {
            static::raiseError(__METHOD__ .'(), missing property "model_fields"', true);
            return false;
        }

        if (!empty(static::$model_fields)) {
            $known_field_types = array(
                FIELD_TYPE,
                FIELD_INT,
                FIELD_STRING,
                FIELD_BOOL,
                FIELD_TIMESTAMP,
                FIELD_YESNO,
                FIELD_DATE,
                FIELD_GUID,
            );

            foreach (static::$model_fields as $field => $params) {
                if (!isset($field) ||
                    empty($field) ||
                    !is_string($field) ||
                    !preg_match('/^[a-zA-Z0-9_]+$/', $field)
                ) {
                    static::raiseError(__METHOD__ .'(), invalid field entry (field name) found!', true);
                    return false;
                }
                if (!isset($params) || empty($params) || !is_array($params)) {
                    static::raiseError(__METHOD__ .'(), invalid field params found!', true);
                    return false;
                }
                if (!isset($params[FIELD_TYPE]) ||
                    empty($params[FIELD_TYPE]) ||
                    !is_string($params[FIELD_TYPE]) ||
                    !ctype_alnum($params[FIELD_TYPE])
                ) {
                    static::raiseError(__METHOD__ .'(), invalid field type found!', true);
                    return false;
                }
                if (!in_array($params[FIELD_TYPE], $known_field_types)) {
                    static::raiseError(__METHOD__ .'(), unknown field type found!', true);
                    return false;
                }
                if (array_key_exists(FIELD_LENGTH, $params)) {
                    if (!is_int($params[FIELD_LENGTH])) {
                        static::raiseError(__METHOD__ ."(), FIELD_LENGTH of {$field} is not an integer!", true);
                        return false;
                    }
                    if ($params[FIELD_LENGTH] < 0 && $params[FIELD_LENGTH] < 16384) {
                        static::raiseError(__METHOD__ ."(), FIELD_LENGTH of {$field} is out of bound!", true);
                        return false;
                    }
                }
            }
        }

        if (isset(static::$model_fields_index) &&
            !empty(static::$model_fields_index) &&
            is_array(static::$model_fields_index)
        ) {
            foreach (static::$model_fields_index as $field) {
                if (!$static::hasField($field)) {
                    static::raiseError(__CLASS__ .'::hasField() returned false!');
                    return false;
                }
            }
        }

        if (!isset($this->model_load_by) || !is_array($this->model_load_by)) {
            static::raiseError(__METHOD__ .'(), missing property "model_load_by"', true);
            return false;
        }

        if (!empty($this->model_load_by)) {
            foreach ($this->model_load_by as $field => $value) {
                if (!isset($field) ||
                    empty($field) ||
                    !is_string($field)
                ) {
                    static::raiseError(__METHOD__ .'(), $model_load_by contains an invalid field!', true);
                    return false;
                }
                if ((isset($this) && $this->hasVirtualFields() && !$this->hasVirtualField($field)) &&
                    !static::hasField($field) &&
                    (static::hasModelItems() &&
                    ($items_model = static::getModelItemsModel()) !== false &&
                    ($full_model = $thallium->getFullModelName($items_model)) !== false &&
                    !$full_model::hasField($field))
                ) {
                    static::raiseError(__METHOD__ .'(), $model_load_by contains an unknown field!', true);
                    return false;
                }
                if (static::hasField($field) && !$this->validateField($field, $value)) {
                    static::raiseError(__METHOD__ .'(), $model_load_by contains an invalid value!', true);
                    return false;
                }
            }
        }

        if (!empty($this->model_sort_order)) {
            foreach ($this->model_sort_order as $field => $mode) {
                if (($items_model = static::getModelItemsModel()) === false) {
                    static::raiseError(__CLASS__ .'::getModelItemsModel() returned false!');
                    return false;
                }
                if (($full_model = $thallium->getFullModelName($items_model)) === false) {
                    static::raiseError(get_class($thallium) .'::getFullModelName() returned false!', true);
                    return false;
                }
                if (!isset($field) ||
                    empty($field) ||
                    !is_string($field) ||
                    !$full_model::hasFields() ||
                    !$full_model::hasField($field)
                ) {
                    static::raiseError(__METHOD__ ."(), \$model_sort_order contains an invalid field {$field}!", true);
                    return false;
                }
                if (!in_array(strtoupper($mode), array('ASC', 'DESC'))) {
                    static::raiseError(__METHOD__ .'(), \$order is invalid!');
                    return false;
                }
            }
        }

        if (static::hasModelLinks()) {
            if (!is_array(static::$model_links)) {
                static::raiseError(__METHOD__ .'(), $model_links is not an array!', true);
                return false;
            }
            foreach (static::$model_links as $target => $field) {
                if (!isset($target) || empty($target) || !is_string($target)) {
                    static::raiseError(__METHOD__ .'(), $model_links link target is invalid!', true);
                    return false;
                }

                if (!isset($field) || empty($field) || !is_string($field)) {
                    static::raiseError(__METHOD__ .'(), $model_links link field is invalid!', true);
                    return false;
                }

                if (!static::hasField($field)) {
                    static::raiseError(__METHOD__ .'(), $model_links link field is unknown!', true);
                    return false;
                }

                if (($parts = explode('/', $target)) === false) {
                    static::raiseError(__METHOD__ .'(), failed to explode() $model_links target!', true);
                    return false;
                }

                if (count($parts) < 2) {
                    static::raiseError(__METHOD__ .'(), link information incorrectly declared!');
                    return false;
                }

                $target_model = $parts[0];
                $target_field = $parts[1];

                if (!isset($target_model) || empty($target_model) || !is_string($target_model)) {
                    static::raiseError(__METHOD__ .'(), $model_links member model value is invalid!', true);
                    return false;
                }

                if (!$thallium->isValidModel($target_model)) {
                    static::raiseError(
                        __METHOD__ .'(), $model_links member model value refers an unknown model!',
                        true
                    );
                    return false;
                }

                if (($target_full_model = $thallium->getFullModelName($target_model)) === false) {
                    static::raiseError(get_class($thallium) .'::getFullModelName() returned false!', true);
                    return false;
                }

                if (!isset($target_field) || empty($target_field) || !is_string($target_field)) {
                    static::raiseError(__METHOD__ .'(), $model_links member model field is invalid!', true);
                    return false;
                }

                if (!$target_full_model::hasModelItems() && !$target_full_model::hasField($target_field)) {
                    static::raiseError(sprintf('%s::hasField() returned false!', $target_full_model), true);
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * load
     *
     */
    protected function load($extend_query_where = null)
    {
        global $thallium, $db;

        if (!static::hasFields() && !static::hasModelItems()) {
            return true;
        }

        if (static::hasFields() && empty($this->model_load_by)) {
            return true;
        }

        if (!isset($this->model_sort_order) ||
            !is_array($this->model_sort_order)
        ) {
            static::raiseError(__METHOD__ .'(), $model_sort_order is invalid!');
            return false;
        }

        if (!empty($this->model_sort_order)) {
            $order_by = array();
            foreach ($this->model_sort_order as $field => $mode) {
                if (($column = static::column($field)) === false) {
                    static::raiseError(__CLASS__ .'::column() returned false!');
                    return false;
                }
                array_push($order_by, "{$column} {$mode}");
            }
        }

        if (isset($extend_query_where) &&
            !empty($extend_query_where) &&
            !is_string($extend_query_where)
        ) {
            static::raiseError(__METHOD__ .'(), $extend_query_where parameter is invalid!');
            return false;
        }

        if (method_exists($this, 'preLoad') && is_callable(array($this, 'preLoad'))) {
            if (!$this->preLoad()) {
                static::raiseError(get_called_class() ."::preLoad() method returned false!");
                return false;
            }
        }

        $sql_query_columns = array();
        $sql_query_data = array();

        if (static::hasFields()) {
            if (($fields = $this->getFieldNames()) === false) {
                static::raiseError(__CLASS__ .'::getFieldNames() returned false!');
                return false;
            }
        } elseif (static::hasModelItems()) {
            $fields = array(
                FIELD_IDX,
                FIELD_GUID,
            );
        }

        if (!isset($fields) || empty($fields)) {
            return true;
        }

        foreach ($fields as $field) {
            if (($column = static::column($field)) === false) {
                static::raiseError(__CLASS__ .'::column() returned false!');
                return false;
            }
            if ($field == 'time') {
                $sql_query_columns[] = sprintf("UNIX_TIMESTAMP(%s) as %s", $column, $column);
                continue;
            }
            $sql_query_columns[$field] = $column;
        }

        foreach ($this->model_load_by as $field => $value) {
            if (($column = static::column($field)) === false) {
                static::raiseError(__CLASS__ .'::column() returned false!');
                return false;
            }
            $sql_query_data[$column] = $value;
        }

        $bind_params = array();

        if (($sql = $db->buildQuery(
            "SELECT",
            self::getTableName(),
            $sql_query_columns,
            $sql_query_data,
            $bind_params,
            $extend_query_where
        )) === false) {
            static::raiseError(get_class($db) .'::buildQuery() returned false!');
            return false;
        }

        if (isset($order_by) &&
            !empty($order_by) &&
            is_array($order_by)
        ) {
            $sql.= ' ORDER BY '. implode(', ', $order_by);
        }

        try {
            $sth = $db->prepare($sql);
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), unable to prepare database query!');
            return false;
        }

        if (!$sth) {
            static::raiseError(get_class($db) ."::prepare() returned invalid data!");
            return false;
        }

        foreach ($bind_params as $key => $value) {
            $sth->bindParam($key, $value);
        }

        if (!$db->execute($sth, $bind_params)) {
            $db->freeStatement($sth);
            static::raiseError(__METHOD__ ."(), unable to execute query!");
            return false;
        }

        $num_rows = $sth->rowCount();

        if (static::hasFields()) {
            if ($num_rows < 1) {
                $db->freeStatement($sth);
                static::raiseError(__METHOD__ ."(), no object with id {$this->id} found!");
                return false;
            } elseif ($num_rows > 1) {
                $db->freeStatement($sth);
                static::raiseError(__METHOD__ ."(), more than one object with id {$this->id} found!");
                return false;
            }
        }

        if ($num_rows == 0) {
            $db->freeStatement($sth);
            return true;
        }

        if (static::hasFields()) {
            if (($row = $sth->fetch(\PDO::FETCH_ASSOC)) === false) {
                $db->freeStatement($sth);
                static::raiseError(__METHOD__ ."(), unable to fetch SQL result for object id {$this->id}!");
                return false;
            }

            $db->freeStatement($sth);

            foreach ($row as $key => $value) {
                if (($field = static::getFieldNameFromColumn($key)) === false) {
                    static::raiseError(__CLASS__ .'() returned false!');
                    return false;
                }
                if (!static::hasField($field)) {
                    static::raiseError(__METHOD__ ."(), received data for unknown field '{$field}'!");
                    return false;
                }
                if (!$this->validateField($field, $value)) {
                    static::raiseError(__CLASS__ ."::validateField() returned false for field {$field}!");
                    return false;
                }
                // type casting, as fixed point numbers are returned as string!
                if ($this->getFieldType($field) === FIELD_INT &&
                    is_string($value) &&
                    is_numeric($value)
                ) {
                    $value = intval($value);
                }
                if (!$this->setFieldValue($field, $value)) {
                    static::raiseError(__CLASS__ ."::setFieldValue() returned false for field {$field}!");
                    return false;
                }
                $this->model_init_values[$field] = $value;
            }
        } elseif (static::hasModelItems()) {
            while (($row = $sth->fetch(\PDO::FETCH_ASSOC)) !== false) {
                if (($items_model = static::getModelItemsModel()) === false) {
                    static::raiseError(__CLASS__ .'::getModelItemsModel() returned false!');
                    return false;
                }
                if (($child_model_name = $thallium->getFullModelName($items_model)) === false) {
                    $db->freeStatement($sth);
                    static::raiseError(get_class($thallium) .'::getFullModelName() returned false!');
                    return false;
                }
                foreach ($row as $key => $value) {
                    if (($field = $child_model_name::getFieldNameFromColumn($key)) === false) {
                        $db->freeStatement($sth);
                        static::raiseError(__CLASS__ .'() returned false!');
                        return false;
                    }
                    if (!$child_model_name::validateField($field, $value)) {
                        $db->freeStatement($sth);
                        static::raiseError(__CLASS__ ."::validateField() returned false for field {$field}!");
                        return false;
                    }
                }

                $item = array(
                    FIELD_MODEL => $child_model_name,
                    FIELD_IDX => $row[$child_model_name::column(FIELD_IDX)],
                    FIELD_GUID => $row[$child_model_name::column(FIELD_GUID)],
                    FIELD_INIT => false,
                );

                if (!$this->addItem($item)) {
                    $db->freeStatement($sth);
                    static::raiseError(__CLASS__ .'::addItem() returned false!');
                    return false;
                }
            }

            $db->freeStatement($sth);
        }

        if (method_exists($this, 'postLoad') && is_callable(array($this, 'postLoad'))) {
            if (!$this->postLoad()) {
                static::raiseError(get_called_class() ."::postLoad() method returned false!");
                return false;
            }
        }

        if (!isset($this->id) || empty($this->id) && static::hasField(FIELD_IDX)) {
            if (isset($this->model_values[FIELD_IDX]) && !empty($this->model_values[FIELD_IDX])) {
                $this->id = $this->model_values[FIELD_IDX];
            }
        }

        return true;

    } // load();

    final protected function bulkLoad($keys)
    {
        global $thallium, $db;

        if (!isset($keys) || empty($keys) || !is_array($keys)) {
            static::raiseError(__METHOD__ .'(), $keys parameter is invalid!');
            return false;
        }

        $key_check_func = function ($key) {
            if (!is_numeric($key) || !is_int($key)) {
                static::raiseError(__METHOD__ .'(), $keys parameter contains an invalid key!');
                return false;
            }
            return true;
        };

        if (!array_walk($keys, $key_check_func)) {
            static::raiseError(__METHOD__ .'(), $keys parameter failed validation!');
            return false;
        }

        if (($keys_str = implode(',', $keys)) === false) {
            static::raiseError(__METHOD__ .'(), something went wrong on implode()!');
            return false;
        }

        if (!isset($keys_str) || empty($keys_str) || !is_string($keys_str)) {
            static::raiseError(__METHOD__ .'(), implode() returned something unexcepted!');
            return false;
        }

        $result = $db->query(sprintf(
            "SELECT
                *
            FROM
                TABLEPREFIX%s
            WHERE
                %s_idx IN (%s)",
            static::$model_table_name,
            static::$model_column_prefix,
            $keys_str
        ));

        if (!$result) {
            static::raiseError(get_class($db) .'::query() returned false!');
            return false;
        }

        if ($result->rowCount() < 1) {
            return true;
        }

        if (($items_model = static::getModelItemsModel()) === false) {
            static::raiseError(__CLASS__ .'::getModelItemsModel() returned false!');
            return false;
        }

        if (($full_model = $thallium->getFullModelName($items_model)) === false) {
            static::raiseError(get_class($thallium) .'::getFullModelName() returned false!');
            return false;
        }

        while ($row = $result->fetch(\PDO::FETCH_ASSOC)) {
            $item = array();

            foreach ($row as $key => $value) {
                if (($field = $full_model::getFieldNameFromColumn($key)) === false) {
                    static::raiseError(__CLASS__ .'() returned false!');
                    return false;
                }
                if (!$full_model::validateField($field, $value)) {
                    static::raiseError(__CLASS__ ."::validateField() returned false for field {$field}!");
                    return false;
                }
                $item[$field] = $value;
            }

            if (!array_key_exists(FIELD_IDX, $item)) {
                static::raiseError(__METHOD__ .'(), retrieved item misses idx field!');
                return false;
            }

            if (!$this->setItemData($item[FIELD_IDX], $item)) {
                static::raiseError(__CLASS__ .'::setItemData() returned false!');
                return false;
            }
        }

        return true;
    }

    /**
     * update object variables via array
     *
     * @param mixed $data
     * @return bool
     */
    final public function update($data)
    {
        if (!isset($data) ||
            empty($data) ||
            (!is_array($data) && !is_object($data))
        ) {
            static::raiseError(__METHOD__ .'(), $data parameter is invalid!');
            return false;
        }

        foreach ($data as $key => $value) {
            if (($field = static::getFieldNameFromColumn($key)) === false) {
                static::raiseError(__METHOD__ .'(), unknown field found!');
                return false;
            }

            if (static::hasField($field)) {
                // this will trigger the __set() method.
                $this->$key = $value;
                continue;
            }

            if ($this->hasVirtualFields() && $this->hasVirtualField($field)) {
                if (($method_name = $this->getVirtualFieldSetMethod($field)) === false) {
                    static::raiseError(__CLASS__ .'::getVirtualFieldSetMethod() returned false!');
                    return false;
                }

                if (($retval = call_user_func(array($this, $method_name), $value)) === false) {
                    static::raiseError(__CLASS__ ."::{$method_name}() returned false!");
                    return false;
                }

                continue;
            }
            static::raiseError(__METHOD__ .'(), model has no field like that!');
            return false;
        }

        return true;
    }

    /**
     * flood object variables via array
     *
     * @param mixed $data
     * @return bool
     */
    final protected function flood($data)
    {
        if (!isset($data) ||
            empty($data) ||
            (!is_array($data) && !is_object($data))
        ) {
            static::raiseError(__METHOD__ .'(), $data parameter is invalid!');
            return false;
        }

        foreach ($data as $key => $value) {
            if (($field = static::getFieldNameFromColumn($key)) === false) {
                static::raiseError(__METHOD__ .'(), unknown field found!');
                return false;
            }
            if (static::hasField($field)) {
                // this will trigger the __set() method.
                $this->$key = $value;
                $this->model_init_values[$key] = $value;
                continue;
            }
            if ($this->hasVirtualFields() && $this->hasVirtualField($field)) {
                if (($method_name = $this->getVirtualFieldSetMethod($field)) === false) {
                    static::raiseError(__CLASS__ .'::getVirtualFieldSetMethod() returned false!');
                    return false;
                }

                if (($retval = call_user_func(array($this, $method_name), $value))) {
                    static::raiseError(__CLASS__ ."::{$method_name}() returned false!");
                    return false;
                }

                $this->model_init_values[$key] = $value;
                continue;
            }
            static::raiseError(__METHOD__ .'(), model has no field like that!');
            return false;
        }

        return true;
    }

    public function delete()
    {
        global $db;

        if (!isset(static::$model_table_name)) {
            static::raiseError(__METHOD__ .'(), table name is not set!');
            return false;
        }

        if (!isset(static::$model_column_prefix)) {
            static::raiseError(__METHOD__ .'(), column name is not set!');
            return false;
        }

        if (static::hasFields() && $this->isNew()) {
            return true;
        }

        if (static::hasModelItems() && !$this->hasItems()) {
            return true;
        }

        if (method_exists($this, 'preDelete') && is_callable(array($this, 'preDelete'))) {
            if (!$this->preDelete()) {
                static::raiseError(get_called_class() ."::preDelete() method returned false!");
                return false;
            }
        }

        if (static::hasModelLinks()) {
            if (!$this::deleteModelLinks()) {
                static::raiseError(__CLASS__ .'::deleteModelLinks() returned false!');
                return false;
            }
        }

        if (static::hasModelItems()) {
            if (!$this->deleteItems()) {
                static::raiseError(__CLASS__ .'::deleteItems() returned false!');
                return false;
            }
        } else {
            if (!isset($this->id)) {
                static::raiseError(__METHOD__ .'(), can not delete without knowing what to delete!');
                return false;
            }

            /* generic delete */
            $sth = $db->prepare(sprintf(
                "DELETE FROM
                    TABLEPREFIX%s
                WHERE
                    %s_idx LIKE ?",
                static::$model_table_name,
                static::$model_column_prefix
            ));

            if (!$sth) {
                static::raiseError(__METHOD__ ."(), unable to prepare query");
                return false;
            }

            if (!$db->execute($sth, array($this->id))) {
                static::raiseError(__METHOD__ ."(), unable to execute query");
                return false;
            }

            $db->freeStatement($sth);
        }

        if (method_exists($this, 'postDelete') && is_callable(array($this, 'postDelete'))) {
            if (!$this->postDelete()) {
                static::raiseError(get_called_class() ."::postDelete() method returned false!");
                return false;
            }
        }

        return true;

    } // delete()

    public function deleteItems()
    {
        if (!static::hasModelItems()) {
            static::raiseError(__METHOD__ .'(), model '. __CLASS__ .' is not declared to have items!');
            return false;
        }

        if (!$this->hasItems()) {
            return true;
        }

        if (($items = $this->getItems()) === false) {
            static::raiseError(__CLASS__ .'::getItems() returned false!');
            return false;
        }

        foreach ($items as $item) {
            if (!method_exists($item, 'delete') || !is_callable(array($item, 'delete'))) {
                static::raiseError(__METHOD__ .'(), model '. get_class($item) .' does not provide a delete() method!');
                return false;
            }
            if (!$item->delete()) {
                static::raiseError(get_class($item) .'::delete() returned false!');
                return false;
            }
        }

        return true;
    }

    /**
     * clone
     */
    final public function createClone(&$srcobj)
    {
        global $thallium, $db;

        if (!isset($srcobj->id)) {
            return false;
        }

        if (!is_numeric($srcobj->id)) {
            return false;
        }

        if (!isset($srcobj::$model_fields)) {
            return false;
        }

        if (($src_fields = $srcobj->getFields()) === false) {
            static::raiseError(get_class($srcobj) .'::getFields() returned false!');
            return false;
        }

        foreach (array_keys($src_fields) as $field) {
            // check for a matching key in clone's model_fields array
            if (!static::hasField($field)) {
                continue;
            }

            if (!$srcobj->hasFieldValue($field)) {
                continue;
            }

            if (($src_value = $srcobj->getFieldValue($field)) === false) {
                static::raiseError(get_class($srcobj) .'::getFieldValue() returned false!');
                return false;
            }

            if (!$this->setFieldValue($field, $src_value)) {
                static::raiseError(__CLASS__ .':setFieldValue() returned false!');
                return false;
            }
        }

        if (method_exists($this, 'preClone') && is_callable(array($this, 'preClone'))) {
            if (!$this->preClone($srcobj)) {
                static::raiseError(get_called_class() ."::preClone() method returned false!");
                return false;
            }
        }

        $pguid = 'derivation_guid';

        $this->id = null;
        if (isset($this->model_values[FIELD_IDX])) {
            $this->model_values[FIELD_IDX] = null;
        }
        if (isset($this->model_values[FIELD_GUID])) {
            $this->model_values[FIELD_GUID] = $thallium->createGuid();
        }

        // record the parent objects GUID
        if (isset($srcobj->model_values[FIELD_GUID]) &&
            !empty($srcobj->model_values[FIELD_GUID]) &&
            static::hasField($pguid)
        ) {
            $this->model_values[$pguid] = $srcobj->getGuid();
        }

        if (!$this->save()) {
            static::raiseError(__CLASS__ .'::save() returned false!');
            return false;
        }

        // if saving was successful, our new object should have an ID now
        if (!isset($this->id) || empty($this->id)) {
            static::raiseError(__METHOD__ ."(), error on saving clone. no ID was returned from database!");
            return false;
        }

        $this->model_values[FIELD_IDX] = $this->id;

        // now check for assigned childrens and duplicate those links too
        if (isset($this->child_names) && !isset($this->ignore_child_on_clone)) {
            // loop through all (known) childrens
            foreach (array_keys($this->child_names) as $child) {
                $prefix = $this->child_names[$child];

                // initate an empty child object
                if (($child_obj = $thallium->load_class($child)) === false) {
                    static::raiseError(__METHOD__ ."(), unable to locate class for {$child_obj}");
                    return false;
                }

                $sth = $db->prepare(sprintf(
                    "SELECT
                        *
                    FROM
                        TABLEPREFIXassign_%s_to_%s
                    WHERE
                        %s_%s_idx LIKE ?",
                    $child_obj->model_table_name,
                    static::$model_table_name,
                    $prefix,
                    static::$model_column_prefix
                ));

                if (!$sth) {
                    static::raiseError(__METHOD__ ."(), unable to prepare query");
                    return false;
                }

                if (!$db->execute($sth, array($srcobj->id))) {
                    static::raiseError(__METHOD__ ."(), unable to execute query");
                    return false;
                }

                while ($row = $sth->fetch(\PDO::FETCH_ASSOC)) {
                    $query = sprintf(
                        "INSERT INTO
                            TABLEPREFIXassign_%s_to_%s (",
                        $child_obj->model_table_name,
                        static::$model_table_name
                    );
                    $values = "";

                    foreach (array_keys($row) as $key) {
                        $query.= $key .",";
                        $values.= "?,";
                    }

                    $query = substr($query, 0, strlen($query)-1);
                    $values = substr($values, 0, strlen($values)-1);

                    $query = $query ."
                        ) VALUES (
                            $values
                            )
                        ";

                    $row[$this->child_names[$child] .'_idx'] = null;
                    $row[$this->child_names[$child] .'_'.static::$model_column_prefix.'_idx'] = $this->id;
                    if (isset($row[$this->child_names[$child] .'_guid'])) {
                        $row[$this->child_names[$child] .'_guid'] = $thallium->createGuid();
                    }

                    if (!isset($child_sth)) {
                        $child_sth = $db->prepare($query);
                    }

                    $db->execute($child_sth, array_values($row));
                }

                if (isset($child_sth)) {
                    $db->freeStatement($child_sth);
                }
                $db->freeStatement($sth);
            }
        }

        if (method_exists($this, 'postClone') && is_callable(array($this, 'postClone'))) {
            if (!$this->postClone($srcobj)) {
                static::raiseError(get_called_class() ."::postClone() method returned false!");
                return false;
            }
        }

        return true;

    } // createClone()

    /**
     * init fields
     */
    final protected function initFields($override = null)
    {
        if (!static::hasFields()) {
            return true;
        }

        foreach (array_keys(static::$model_fields) as $field) {
            // check for a matching key in clone's model_fields array
            if (isset($override) &&
                !empty($override) &&
                is_array($override) &&
                in_array($field, array_keys($override))
            ) {
                $this->model_values[$field] = $override[$field];
                continue;
            }

            if (!$this->hasDefaultValue($field)) {
                $this->model_values[$field] = null;
                continue;
            }

            if (($this->model_values[$field] = $this->getDefaultValue($field)) === false) {
                static::raiseError(__CLASS__ .'::getDefaultValue() returned false!');
                return false;
            }
        }

        return true;

    } // initFields()

    /* override PHP's __set() function */
    final public function __set($name, $value)
    {
        global $thallium;

        if (!static::hasFields() && !static::hasModelItems()) {
            if (!isset($thallium::$permit_undeclared_class_properties)) {
                static::raiseError(__METHOD__ ."(), trying to set an undeclared property {$name}!", true);
                return;
            }
            $this->$name = $value;
            return;
        }

        global $thallium;

        if ($this->hasVirtualFields() && $this->hasVirtualField($name)) {
            if (($name = static::getFieldNamefromColumn($name)) === false) {
                static::raiseError(__CLASS__ .'::getFieldNameFromColumn() returned false!', true);
                return;
            }

            if (($method_name = $this->getVirtualFieldSetMethod($name)) === false) {
                static::raiseError(__CLASS__ .'::getVirtualFieldSetMethod() returned false!', true);
                return;
            }

            if (($retval = call_user_func(array($this, $method_name), $value)) === false) {
                static::raiseError(__CLASS__ ."::{$method_name}() returned false!", true);
                return;
            }

            return;
        }

        if (!static::hasFields()) {
            static::raiseError(__METHOD__ ."(), model_fields array not set for class ". get_class($this), true);
            return;
        }

        if (($field = static::getFieldNameFromColumn($name)) === false) {
            $this->raiseEerror(__CLASS__ .'::getFieldNameFromColumn() returned false!', true);
            return;
        }

        // virtual fields have to validate themself via their get/set methods.
        if ($this->hasVirtualFields() && $this->hasVirtualField($field)) {
            return;
        }

        if (!$this->hasField($field) && $field != 'id') {
            static::raiseError(__METHOD__ ."(), unknown key in ". __CLASS__ ."::__set(): {$field}", true);
            return;
        }

        if (($field_type = static::getFieldType($field)) === false || empty($field_type)) {
            static::raiseError(__CLASS__ .'::getFieldType() returned false!', true);
            return;
        }

        if (($value_type = gettype($value)) === 'unknown type' || empty($value_type)) {
            static::raiseError(__METHOD__ .'(), value is of an unknown type!', true);
            return;
        }

        if (!static::validateField($field, $value)) {
            static::raiseError(__CLASS__ .'::validateField() returned false!', true);
            return;
        }

        // NULL values can not be checked closer.
        if (is_null($value)) {
            $this->model_values[$field] = $value;
            return;
        }

        /* if an empty string has been provided as value and the field type is
         * an integer value, cast the value to 0 instead.
         */
        if ($value_type == 'string' && $value === '' && $field_type == FIELD_INT) {
            $value_type = FIELD_INT;
            $value = 0;
        }

        /* values have been validated already by validateField(), but
           sometimes we have to cast values to their field types.
        */

        /* positiv integers */
        if ($field_type == FIELD_INT &&
            $value_type == 'string' &&
            ctype_digit($value) &&
            is_numeric($value)
        ) {
            $value = (int) $value;
            $value_type = $field_type;
        }
        /* negative integers */
        if ($field_type == FIELD_INT &&
            $value_type == 'string' &&
            preg_match("/^-?[1-9][0-9]*$/", $value) === 1
        ) {
            $value = (int) $value;
            $value_type = $field_type;
        /* distinguish GUIDs */
        } elseif ($field_type == FIELD_GUID &&
            $value_type == 'string'
        ) {
            if (!empty($value) &&
                $thallium->isValidGuidSyntax($value)
            ) {
                $value_type = FIELD_GUID;
            } elseif (empty($value)) {
                $value_type = FIELD_GUID;
            }
        /* distinguish YESNO */
        } elseif ($field_type == FIELD_YESNO &&
            $value_type == 'string' &&
            in_array($value, array('yes', 'no', 'Y', 'N'))
        ) {
            $value_type = 'yesno';
        /* distinguiѕh dates */
        } elseif ($field_type == FIELD_DATE &&
            $value_type == 'string' &&
            preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $value)
        ) {
            $value_type = 'date';
        /* distinguish timestamps */
        } elseif ($field_type == FIELD_TIMESTAMP &&
            $value_type == 'string'
        ) {
            $value_type = 'timestamp';
        } elseif ($field_type == FIELD_TIMESTAMP &&
            $value_type == 'double'
        ) {
            if (is_float($value)) {
                $value_type = 'timestamp';
            }
        }

        if ($value_type !== $field_type) {
            static::raiseError(
                __METHOD__
                ."(), field {$field}, value type ({$value_type}) does not match field type ({$field_type})!",
                true
            );
            return;
        }

        if (!static::hasFieldSetMethod($field)) {
            $this->model_values[$field] = $value;
            return;
        }

        if (($set_method = static::getFieldSetMethod($field)) === false) {
            static::raiseError(__CLASS__ .'::getFieldSetMethod() returned false!', true);
            return;
        }

        if (!is_callable(array($this, $set_method))) {
            static::raiseError(__CLASS__ ."::{$set_method}() is not callable!", true);
            return;
        }

        if (($retval = call_user_func(array($this, $set_method), $value)) === false) {
            static::raiseError(__CLASS__ ."::{$set_method}() returned false!", true);
            return;
        }

        return;
    }

    /* override PHP's __get() function */
    final public function __get($name)
    {
        if (!static::hasFields() && !static::hasModelItems()) {
            return isset($this->$name) ? $this->$name : null;
        }

        if (($field = static::getFieldNamefromColumn($name)) === false) {
            static::raiseError(__CLASS__ .'::getFieldNameFromColumn() returned false!', true);
            return;
        }

        if (isset($this->model_values[$field])) {
            if (!static::hasFieldGetMethod($field)) {
                return $this->model_values[$field];
            }

            if (($get_method = static::getFieldGetMethod($field)) === false) {
                static::raiseError(__CLASS__ .'::getFieldGetMethod() returned false!', true);
                return;
            }

            if (!is_callable(array($this, $get_method))) {
                static::raiseError(__CLASS__ ."::{$get_method}() is not callable!", true);
                return;
            }

            if (($retval = call_user_func(array($this, $get_method), $value)) === false) {
                static::raiseError(__CLASS__ ."::{$get_method}() returned false!", true);
                return;
            }

            return $retval;
        }

        if (!$this->hasVirtualFields()) {
            return null;
        }

        if (!$this->hasVirtualField($field)) {
            return null;
        }

        if (($method_name = $this->getVirtualFieldGetMethod($field)) === false) {
            static::raiseError(__CLASS__ .'::getVirtualFieldGetMethod() returned false!');
            return false;
        }

        if (($value = call_user_func(array($this, $method_name), $value)) === false) {
            static::raiseError(__CLASS__ ."::{$method_name}() returned false!", true);
            return false;
        }

        return $value;
    }

    final public function save()
    {
        global $thallium, $db;

        if (!static::hasFields()) {
            static::raiseError(__METHOD__ ."(), model_fields array not set for class ". get_class($this));
        }

        if (method_exists($this, 'preSave') && is_callable(array($this, 'preSave'))) {
            if (!$this->preSave()) {
                static::raiseError(get_called_class() ."::preSave() method returned false!");
                return false;
            }
        }

        $time_field = static::column('time');

        if (!isset($this->model_values[FIELD_GUID]) ||
            empty($this->model_values[FIELD_GUID])
        ) {
            $this->model_values[FIELD_GUID] = $thallium->createGuid();
        }

        /* new object */
        if (!isset($this->id) || empty($this->id)) {
            $sql = 'INSERT INTO ';
        /* existing object */
        } else {
            $sql = 'UPDATE ';
        }

        $sql.= sprintf("TABLEPREFIX%s SET ", static::$model_table_name);

        $arr_values = array();
        $arr_columns = array();

        foreach (array_keys(static::$model_fields) as $field) {
            if (($column = static::column($field)) === false) {
                static::raiseError(__METHOD__ .'(), invalid column found!');
                return false;
            }

            if (!isset($this->model_values[$field])) {
                continue;
            }

            if ($column == $time_field) {
                $arr_columns[] = sprintf("%s = FROM_UNIXTIME(?)", $column);
            } else {
                $arr_columns[] = sprintf("%s = ?", $column);
            }
            $arr_values[] = $this->model_values[$field];
        }
        $sql.= implode(', ', $arr_columns);

        if (!isset($this->id) || empty($this->id)) {
            $this->model_values[FIELD_IDX] = null;
        } else {
            $sql.= sprintf(" WHERE %s LIKE ?", static::column(FIELD_IDX));
            $arr_values[] = $this->id;
        }

        if (($sth = $db->prepare($sql)) === false) {
            static::raiseError(__METHOD__ ."(), unable to prepare query");
            return false;
        }

        if (!$db->execute($sth, $arr_values)) {
            static::raiseError(__METHOD__ ."(), unable to execute query");
            return false;
        }

        if (!isset($this->id) || empty($this->id)) {
            if (($this->id = $db->getId()) === false) {
                static::raiseError(get_class($db) .'::getId() returned false!');
                return false;
            }
        }

        if (!isset($this->model_values[FIELD_IDX]) ||
            empty($this->model_values[FIELD_IDX]) ||
            is_null($this->model_values[FIELD_IDX])) {
            $this->model_values[FIELD_IDX] = $this->id;
        }

        $db->freeStatement($sth);

        if (method_exists($this, 'postSave') && is_callable(array($this, 'postSave'))) {
            if (!$this->postSave()) {
                static::raiseError(get_called_class() ."::postSave() method returned false!");
                return false;
            }
        }

        // now we need to update the model_init_values array.

        $this->model_init_values = array();

        foreach (array_keys(static::$model_fields) as $field) {
            if (!isset($this->model_values[$field])) {
                continue;
            }

            $this->model_init_values[$field] = $this->model_values[$field];
        }

        return true;

    } // save()

    /*final public function toggleStatus($to)
    {
        global $db;

        if (!isset($this->id)) {
            return false;
        }
        if (!is_numeric($this->id)) {
            return false;
        }
        if (!isset(static::$model_table_name)) {
            return false;
        }
        if (!isset(static::$model_column_prefix)) {
            return false;
        }
        if (!in_array($to, array('off', 'on'))) {
            return false;
        }

        if ($to == "on") {
            $new_status = 'Y';
        } elseif ($to == "off") {
            $new_status = 'N';
        }

        $sth = $db->prepare(sprintf(
            "UPDATE
                TABLEPREFIX%s
            SET
                %s_active = ?
            WHERE
                %s_idx LIKE ?",
            static::$model_table_name,
            static::$model_column_prefix,
            static::$model_column_prefix
        ));

        if (!$sth) {
            static::raiseError(__METHOD__ ."(), unable to prepare query");
            return false;
        }

        if (!$db->execute($sth, array($new_status, $this->id))) {
            static::raiseError(__METHOD__ ."(), unable to execute query");
            return false;
        }

        $db->freeStatement($sth);
        return true;

    } // toggleStatus()*/

    /*final public function toggleChildStatus($to, $child_obj, $child_id)
    {
        global $db, $thallium;

        if (!isset($this->child_names)) {
            static::raiseError(__METHOD__ ."(), this object has no childs at all!");
            return false;
        }
        if (!isset($this->child_names[$child_obj])) {
            static::raiseError(__METHOD__ ."(), requested child is not known to this object!");
            return false;
        }

        $prefix = $this->child_names[$child_obj];

        if (($child_obj = $thallium->load_class($child_obj, $child_id)) === false) {
            static::raiseError(__METHOD__ ."(), unable to locate class for {$child_obj}");
            return false;
        }

        if (!isset($this->id)) {
            return false;
        }
        if (!is_numeric($this->id)) {
            return false;
        }
        if (!isset(static::$model_table_name)) {
            return false;
        }
        if (!isset(static::$model_column_prefix)) {
            return false;
        }
        if (!in_array($to, array('off', 'on'))) {
            return false;
        }

        if ($to == "on") {
            $new_status = 'Y';
        } elseif ($to == "off") {
            $new_status = 'N';
        }

        $sth = $db->prepare(sprintf(
            "UPDATE
                TABLEPREFIXassign_%s_to_%s
            SET
                %s_%s_active = ?
            WHERE
                %s_%s_idx LIKE ?
            AND
                %s_%s_idx LIKE ?",
            $child_obj->model_table_name,
            static::$model_table_name,
            $prefix,
            $child_obj->model_column_prefix,
            $prefix,
            static::$model_column_prefix,
            $prefix,
            $child_obj->model_column_prefix
        ));

        if (!$sth) {
            static::raiseError(__METHOD__ ."(), unable to prepare query");
            return false;
        }

        if (!$db->execute($sth, array(
            $new_status,
            $this->id,
            $child_id
        ))) {
            static::raiseError(__METHOD__ ."(), unable to execute query");
            return false;
        }

        $db->freeStatement($sth);
        return true;

    } // toggleChildStatus() */

    final public function prev()
    {
        global $thallium, $db;

        $idx_field = static::column(FIELD_IDX);
        $guid_field = static::column(FIELD_GUID);

        $result = $db->fetchSingleRow(sprintf(
            "SELECT
                %s,
                %s
            FROM
                TABLEPREFIX%s
            WHERE
                %s = (
                    SELECT
                        MAX(%s)
                    FROM
                        TABLEPREFIX%s
                    WHERE
                        %s < %s
                )",
            $id,
            $guid_field,
            static::$model_table_name,
            $id,
            $id,
            static::$model_table_name,
            $id,
            $this->id
        ));

        if (!isset($result)) {
            static::raiseError(__METHOD__ ."(), unable to locate previous record!");
            return false;
        }

        if (!isset($result->$idx_field) || !isset($result->$guid_field)) {
            static::raiseError(__METHOD__ ."(), no previous record available!");
            return false;
        }

        if (!is_numeric($result->$idx_field) || !$thallium->isValidGuidSyntax($result->$guid_field)) {
            static::raiseError(
                __METHOD__ ."(), Invalid previous record found: ". htmlentities($result->$id, ENT_QUOTES)
            );
            return false;
        }

        return $result->$id ."-". $result->$guid_field;
    }

    final public function next()
    {
        global $thallium, $db;

        $idx_field = static::column(FIELD_IDX);
        $guid_field = static::column(FIELD_GUID);

        $result = $db->fetchSingleRow(sprintf(
            "SELECT
                %s,
                %s
            FROM
                TABLEPREFIX%s
            WHERE
                %s = (
                    SELECT
                        MIN(%s)
                    FROM
                        TABLEPREFIX%s
                    WHERE
                        %s > %s
                )",
            $id,
            $guid_field,
            static::$model_table_name,
            $id,
            $id,
            static::$model_table_name,
            $id,
            $this->id
        ));

        if (!isset($result)) {
            static::raiseError(__METHOD__ ."(), unable to locate next record!");
            return false;
        }

        if (!isset($result->$idx_field) || !isset($result->$guid_field)) {
            static::raiseError(__METHOD__ ."(), no next record available!");
            return false;
        }

        if (!is_numeric($result->$idx_field) || !$thallium->isValidGuidSyntax($result->$guid_field)) {
            static::raiseError(__METHOD__ ."(), invalid next record found: ". htmlentities($result->$id, ENT_QUOTES));
            return false;
        }

        return $result->$id ."-". $result->$guid_field;
    }

    final protected function isDuplicate()
    {
        global $db;

        // no need to check yet if $id isn't set
        if (empty($this->id)) {
            return false;
        }

        if ((!isset($this->model_values[FIELD_IDX]) || empty($this->model_values[FIELD_IDX])) &&
            (!isset($this->model_values[FIELD_GUID]) || empty($this->model_values[FIELD_GUID]))
        ) {
            static::raiseError(
                __METHOD__ ."(), can't check for duplicates if neither \$idx_field or \$guid_field is set!"
            );
            return false;
        }

        $idx_field = static::column(FIELD_IDX);
        $guid_field = static::column(FIELD_GUID);

        $arr_values = array();
        $where_sql = '';
        if (isset($this->model_values[FIELD_IDX]) && !empty($this->model_values[FIELD_IDX])) {
            $where_sql.= "
                {$idx_field} LIKE ?
            ";
            $arr_values[] = $this->model_values[FIELD_IDX];
        }
        if (isset($this->model_values[FIELD_GUID]) && !empty($this->model_values[FIELD_GUID])) {
            if (!empty($where_sql)) {
                $where_sql.= "
                    AND
                ";
            }
            $where_sql.= "
                {$guid_field} LIKE ?
            ";
            $arr_values[] = $this->model_values[FIELD_GUID];
        }

        if (!isset($where_sql) ||
            empty($where_sql) ||
            !is_string($where_sql)
        ) {
            return false;
        }

        $sql = sprintf(
            "SELECT
                %s
            FROM
                TABLEPREFIX%s
            WHERE
                %s <> %s
            AND
                %s",
            $idx_field,
            static::$model_table_name,
            $idx_field,
            $this->id,
            $where_sql
        );

        if (($sth = $db->prepare($sql)) === false) {
            static::raiseError(get_class($db) .'::prepare() returned false!');
            return false;
        }

        if (!$db->execute($sth, $arr_values)) {
            static::raiseError(get_class($db) .'::execute() returned false!');
            return false;
        }

        if ($sth->rowCount() <= 0) {
            $db->freeStatement($sth);
            return false;
        }

        $db->freeStatement($sth);
        return true;
    }

    final protected static function column($suffix)
    {
        if (!isset(static::$model_column_prefix) ||
            empty(static::$model_column_prefix) ||
            !is_string(static::$model_column_prefix)
        ) {
            return $suffix;
        }

        return static::$model_column_prefix .'_'. $suffix;
    }

    final protected function permitRpcUpdates($state)
    {
        if (!is_bool($state)) {
            static::raiseError(__METHOD__ .'(), parameter must be a boolean value', true);
            return false;
        }

        $this->model_permit_rpc_updates = $state;
        return true;
    }

    final public function permitsRpcUpdates()
    {
        if (!isset($this->model_permit_rpc_updates) ||
            !$this->model_permit_rpc_updates
        ) {
            return false;
        }

        return true;
    }

    final protected function addRpcEnabledField($field)
    {
        if (!is_array($this->model_rpc_allowed_fields)) {
            static::raiseError(__METHOD__ .'(), $model_rpc_allowed_fields is not an array!', true);
            return false;
        }

        if (!isset($field) ||
            empty($field) ||
            !is_string($field) ||
            (!static::hasField($field) &&
            (!isset($this) ||
            empty($this) ||
            !$this->hasVirtualFields() ||
            !$this->hasVirtualField($field)))
        ) {
            static::raiseError(__METHOD__ .'(), $field is invalid!', true);
            return false;
        }

        if (in_array($field, $this->model_rpc_allowed_fields)) {
            return true;
        }

        array_push($this->model_rpc_allowed_fields, $field);
        return true;
    }

    final protected function addRpcAction($action)
    {
        if (!is_array($this->model_rpc_allowed_actions)) {
            static::raiseError(__METHOD__ .'(), $model_rpc_allowed_actions is not an array!', true);
            return false;
        }

        if (!isset($action) ||
            empty($action) ||
            !is_string($action)
        ) {
            static::raiseError(__METHOD__ .'(), $action parameter is invalid!', true);
            return false;
        }

        if (in_array($action, $this->model_rpc_allowed_actions)) {
            return true;
        }

        array_push($this->model_rpc_allowed_actions, $action);
        return true;
    }

    final public function permitsRpcUpdateToField($field)
    {
        if (!is_array($this->model_rpc_allowed_fields)) {
            static::raiseError(__METHOD__ .'(), $model_rpc_allowed_fields is not an array!', true);
            return false;
        }

        if (!isset($field) ||
            empty($field) ||
            !is_string($field)
        ) {
            static::raiseError(__METHOD__ .'(), $field parameter is invalid!', true);
            return false;
        }

        if (($field_name = static::getFieldNameFromColumn($field)) === false) {
            static::raiseError(get_called_class() .'::getFieldNameFromColumn() returned false!');
            return false;
        }

        if (!static::hasField($field_name) &&
            (isset($this) &&
            !empty($this) &&
            $this->hasVirtualFields() &&
            !$this->hasVirtualField($field_name))
        ) {
            static::raiseError(__METHOD__ .'(), $field parameter refers an unknown field!', true);
            return false;
        }

        if (empty($this->model_rpc_allowed_fields)) {
            return false;
        }

        if (!in_array($field_name, $this->model_rpc_allowed_fields)) {
            return false;
        }

        return true;
    }

    final public function permitsRpcActions($action)
    {
        if (!is_array($this->model_rpc_allowed_actions)) {
            static::raiseError(__METHOD__ .'(), $model_rpc_allowed_actions is not an array!', true);
            return false;
        }

        if (!isset($action) ||
            empty($action) ||
            !is_string($action)
        ) {
            static::raiseError(__METHOD__ .'(), $action parameter is invalid!', true);
            return false;
        }

        if (empty($this->model_rpc_allowed_actions)) {
            return false;
        }

        if (!in_array($action, $this->model_rpc_allowed_actions)) {
            return false;
        }

        return true;
    }

    final public function hasIdx()
    {
        if (!static::hasFields()) {
            static::raiseError(__METHOD__ .'(), this model has no fields!');
            return false;
        }

        if (!static::hasField(FIELD_IDX)) {
            static::raiseError(__METHOD__ .'(), this model has no idx field!');
            return false;
        }

        if (!isset($this->model_values[FIELD_IDX]) ||
            empty($this->model_values[FIELD_IDX])
        ) {
            return false;
        }

        return true;
    }

    final public function getId()
    {
        error_log(__METHOD__ .'(), legacy getId() has been called, '
            .'update your application to getIdx() to avoid this message.');
        return $this->getIdx();
    }

    final public function getIdx()
    {
        if (!$this->hasIdx()) {
            static::raiseError(__CLASS__ .'::getIdx() returned false!');
            return false;
        }

        if (($value = $this->getFieldValue(FIELD_IDX)) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        return $value;
    }

    final public function hasGuid()
    {
        if (!static::hasFields()) {
            static::raiseError(__METHOD__ .'(), this model has no fields!');
            return false;
        }

        if (!static::hasField(FIELD_GUID)) {
            static::raiseError(__METHOD__ .'(), this model has no guid field!');
            return false;
        }

        if (!isset($this->model_values[FIELD_GUID]) ||
            empty($this->model_values[FIELD_GUID])
        ) {
            return false;
        }

        return true;
    }

    final public function getGuid()
    {
        if (!$this->hasGuid()) {
            static::raiseError(__CLASS__ .'hasGuid() returned false!');
            return false;
        }


        if (!isset($this->model_values[FIELD_GUID])) {
            return false;
        }

        if (($value = $this->getFieldValue(FIELD_GUID)) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        return $value;
    }

    final public function setGuid($guid)
    {
        global $thallium;

        if (!isset($guid) || empty($guid) || !is_string($guid)) {
            static::raiseError(__METHOD__ .'(), $guid parameter is invalid!');
            return false;
        }

        if (!$thallium->isValidGuidSyntax($guid)) {
            static::raiseError(get_class($thallium) .'::isValidGuidSyntax() returned false!');
            return false;
        }

        $this->model_values[FIELD_GUID] = $guid;
        return true;
    }

    final public static function hasFields()
    {
        $called_class = get_called_class();

        if (!property_exists($called_class, 'model_fields')) {
            return false;
        }

        if (empty($called_class::$model_fields) ||
            !is_array($called_class::$model_fields)
        ) {
            return false;
        }

        return true;
    }

    final public function getFields($no_virtual = false)
    {
        if (!static::hasFields()) {
            static::raiseError(__METHOD__ .'(), this model has no fields defined!');
            return false;
        }

        $fields = array();

        foreach (static::$model_fields as $field => $sec) {
            if ($this->hasFieldValue($field)) {
                if (($value = $this->getFieldValue($field)) === false) {
                    static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
                    return false;
                }
            } else {
                $value = null;
            }

            $field_ary = array(
                'name' => $field,
                'value' => $value,
                'privacy' => $sec,
            );
            $fields[$field] = $field_ary;
        }

        if (!$this->hasVirtualFields() || (isset($no_virtual) && $no_virtual === true)) {
            return $fields;
        }

        if (($virtual_fields = $this->getVirtualFields()) === false) {
            static::raiseError(__CLASS__ .'::getVirtualFields() returned false!');
            return false;
        }

        foreach ($virtual_fields as $field) {
            if ($this->hasVirtualFieldValue($field)) {
                if (($value = $this->getVirtualFieldValue($field)) === false) {
                    static::raiseError(__CLASS__ .'::getVirtualFieldValue() returned false!');
                    return false;
                }
            } else {
                $value = null;
            }

            $field_ary = array(
                'name' => $field,
                'value' => $value,
                'privacy' => 'public'
            );
            $fields[$field] = $field_ary;
        }


        return $fields;
    }

    final public function getFieldNames()
    {
        if (!static::hasFields()) {
            static::raiseError(__METHOD__ .'(), this model has no fields defined!');
            return false;
        }

        return array_keys(static::$model_fields);
    }

    final public static function hasField($field_name, $ignore_virtual = false)
    {
        if (!isset($field_name) ||
            empty($field_name) ||
            !is_string($field_name)
        ) {
            static::raiseError(__METHOD__ .'(), do not know what to look for!');
            return false;
        }

        $called_class = get_called_class();
        if (!$called_class::hasFields()) {
            return false;
        }

        if (!in_array($field_name, array_keys($called_class::$model_fields))) {
            return false;
        }

        return true;
    }

    final public function getFieldPrefix()
    {
        if (!isset(static::$model_column_prefix) ||
            empty(static::$model_column_prefix) ||
            !is_string(static::$model_column_prefix)
        ) {
            static::raiseError(__METHOD__ .'(), column name is not set!');
            return false;
        }

        return static::$model_column_prefix;
    }

    final public function isNew()
    {
        if (isset($this->id) && !empty($this->id)) {
            return false;
        }

        return true;
    }

    protected static function raiseError($string, $stop_execution = false, $exception = null)
    {
        global $thallium;

        $thallium::raiseError(
            $string,
            $stop_execution,
            $exception
        );

        return true;
    }

    final public function hasVirtualFields()
    {
        if (empty($this->model_virtual_fields)) {
            return true;
        }

        return true;
    }

    final public function hasVirtualFieldValue($field)
    {
        if (!isset($field) && empty($field) && !is_string($field)) {
            static::raіseError(__METHOD__. '(), $field parameter is invalid!');
            return false;
        }

        if (!$this->hasVirtualField($field)) {
            static::raiseError(__CLASS__ .'::hasVirtualField() returned false!');
            return false;
        }

        if (($method_name = $this->getVirtualFieldGetMethod($field)) === false) {
            static::raiseError(__CLASS__ .'::getVirtualFieldGetMethod() returned false!');
            return false;
        }

        if (($value = call_user_func(array($this, $method_name))) === false) {
            static::raiseError(__CLASS__ .'::'. $method_name .'() returned false!');
            return false;
        }

        if (!isset($value) || empty($value)) {
            return false;
        }

        return true;
    }

    final public function getVirtualFieldValue($field)
    {
        if (!isset($field) && empty($field) && !is_string($field)) {
            static::raіseError(__METHOD__. '(), $field parameter is invalid!');
            return false;
        }

        if (!$this->hasVirtualFieldValue($field)) {
            static::raiseError(__CLASS__ .'::hasVirtualFieldValue() returned false!');
            return false;
        }

        if (($method_name = $this->getVirtualFieldGetMethod($field)) === false) {
            static::raiseError(__CLASS__ .'::getVirtualFieldGetMethod() returned false!');
            return false;
        }

        if (($value = call_user_func(array($this, $method_name))) === false) {
            static::raiseError(__CLASS__ ."::{$method_name}() returned false!", true);
            return;
        }

        return $value;
    }

    final public function setVirtualFieldValue($field, $value)
    {
        if (!isset($field) && empty($field) && !is_string($field)) {
            static::raіseError(__METHOD__. '(), $field parameter is invalid!');
            return false;
        }

        if (!$this->hasVirtualField($field)) {
            static::raiseError(__CLASS__ .'::getVirtualFieldValue() returned false!');
            return false;
        }

        if (($method_name = $this->getVirtualFieldSetMethod($field)) === false) {
            static::raiseError(__CLASS__ .'::getVirtualFieldSetMethod() returned false!');
            return false;
        }

        if (($retval = call_user_func(array($this, $method_name), $value)) === false) {
            static::raiseError(__CLASS__ ."::{$method_name}() returned false!", true);
            return;
        }

        return $retval;
    }

    final public function getVirtualFieldGetMethod($field)
    {
        if (!isset($field) || empty($field) || !is_string($field)) {
            static::raiseError(__METHOD__ .'(), $field parameter is invalid!');
            return false;
        }

        if (!$this->hasVirtualField($field)) {
            static::raiseError(__CLASS__ .'::hasVirtualField() returned false!');
            return false;
        }

        $method_name = sprintf('get%s', ucwords(strtolower($field)));

        if (!method_exists($this, $method_name) ||
            !is_callable(array($this, $method_name))
        ) {
            static::raiseError(__METHOD__ .'(), there is not callable get-method for that field!');
            return false;
        }

        return $method_name;
    }

    final public function getVirtualFieldSetMethod($field)
    {
        if (!isset($field) || empty($field) || !is_string($field)) {
            static::raiseError(__METHOD__ .'(), $field parameter is invalid!');
            return false;
        }

        if (!$this->hasVirtualField($field)) {
            static::raiseError(__CLASS__ .'::hasVirtualField() returned false!');
            return false;
        }

        $method_name = sprintf('set%s', ucwords(strtolower($field)));

        if (!method_exists($this, $method_name) ||
            !is_callable(array($this, $method_name))
        ) {
            static::raiseError(__METHOD__ .'(), there is not callable set-method for that field!');
            return false;
        }

        return $method_name;
    }

    final public function getVirtualFields()
    {
        if (!$this->hasVirtualFields()) {
            static::raiseError(__CLASS__ .'::hasVirtualFields() returned false!');
            return false;
        }

        return $this->model_virtual_fields;
    }

    final public function hasVirtualField($vfield)
    {
        if (!isset($vfield) || empty($vfield) || !is_string($vfield)) {
            static::raiseError(__METHOD__ .'(), $vfield parameter is invalid!');
            return false;
        }

        if (!in_array($vfield, $this->model_virtual_fields)) {
            return false;
        }

        return true;
    }

    final public function addVirtualField($vfield)
    {
        if (!isset($vfield) || empty($vfield) || !is_string($vfield)) {
            static::raiseError(__METHOD__ .'(), $vfield parameter is invalid!');
            return false;
        }

        if ($this->hasVirtualField($vfield)) {
            return true;
        }

        array_push($this->model_virtual_fields, $vfield);
        return true;
    }

    public function getItemsKeys()
    {
        if (!static::hasModelItems()) {
            static::raiseError(__METHOD__ .'(), model '. __CLASS__ .' is not declared to have items!');
            return false;
        }

        if (!$this->hasItems()) {
            static::raiseError(__CLASS__ .'::hasItems() returned false!');
            return false;
        }

        return array_keys($this->model_items);
    }

    public function getItems($offset = null, $limit = null, $filter = null)
    {
        if (!static::hasModelItems()) {
            static::raiseError(__METHOD__ .'(), model '. __CLASS__ .' is not declared to have items!');
            return false;
        }

        if (!$this->hasItems()) {
            static::raiseError(__CLASS__ .'::hasItems() returned false!');
            return false;
        }

        if (($keys = $this->getItemsKeys()) === false) {
            static::raiseError(__CLASS__ .'::getItemsKeys() returned false!');
            return false;
        }

        if (!isset($keys) || empty($keys) || !is_array($keys)) {
            static::raiseError(__CLASS__ .'::getItemsKeys() returned false!');
            return false;
        }

        if (isset($offset) &&
            !is_null($offset) &&
            !is_int($offset)
        ) {
            static::raiseError(__METHOD__ .'(), $offset parameter is invalid!');
            return false;
        } elseif (isset($offset) && is_null($offset)) {
            $offset = 0;
        }

        if (isset($limit) &&
            !is_null($limit) &&
            !is_int($limit)
        ) {
            static::raiseError(__METHOD__ .'(), $limit parameter is invalid!');
            return false;
        }

        $keys = array_slice(
            $keys,
            $offset,
            $limit,
            true /* preserve_keys on */
        );

        if (!isset($keys) || empty($keys) || !is_array($keys)) {
            return array();
        }

        if (count($keys) > static::$model_bulk_load_limit) {
            if (!$this->bulkLoad($keys)) {
                static::raiseError(__CLASS__ .'::buldLoad() returned false!');
                return false;
            }
        }

        $result = array();

        foreach ($keys as $key) {
            if (($item = $this->getItem($key)) === false) {
                static::raiseError(__CLASS__ .'::getItem() returned false!');
                return false;
            }
            array_push($result, $item);
        }

        if (!isset($result) || empty($result) || !is_array($result)) {
            static::raiseError(__METHOD__ .'(), no items retrieved!');
            return false;
        }

        if (!isset($filter) || is_null($filter)) {
            return $result;
        }

        if (!is_array($filter)) {
            static::raiseError(__METHOD__ .'(), $filter parameter is invalid!');
            return false;
        }

        if (($items = $this->filterItems($result, $filter)) === false) {
            static::raiseError(__CLASS__ .'::filterItems() returned false!');
            return false;
        }

        return $items;
    }

    protected function filterItems($items, $filter)
    {
        if (!isset($items) || empty($items) || !is_array($items)) {
            static::raiseError(__METHOD__ .'(), $items parameter is invalid!');
            return false;
        }

        if (!isset($filter) || empty($filter) || !is_array($filter)) {
            static::raiseError(__METHOD__ .'(), $filter parameter is invalid!');
            return false;
        }

        if (!static::validateItemsFilter($filter)) {
            static::raiseError(__CLASS__ .'::validateItemsFilter() returned false!');
            return false;
        }

        $result = array();
        $hits = array();
        $hits_required = count($filter);

        foreach ($items as $key => $item) {
            if (!isset($item) || empty($item) || (!is_object($item) && !is_array($item))) {
                static::raiseError(__METHOD__ .'(), $items parameter contains an invalid іtem!');
                return false;
            }
            if (!isset($hits[$key])) {
                $hits[$key] = 0;
            }
            foreach ($filter as $field => $pattern) {
                /* use the lookup-index */
                if (isset($this->model_items_lookup_index[$field]) &&
                    isset($this->model_items_lookup_index[$field][$key]) &&
                    $this->model_items_lookup_index[$field][$key] === $pattern
                ) {
                    $hits[$key]++;
                    continue;
                }

                if (!$item::hasField($field)) {
                    static::raiseError(__METHOD__ .'(), $filter parameter refers an unknown field!');
                    return false;
                }
                if (($value = $item->getFieldValue($field)) === false) {
                    static::raiseError(get_class($item) .'::getFieldValue() returned false!');
                    return false;
                }
                if ($value === $pattern) {
                    $hits[$key]++;
                }
            }
        }

        foreach ($hits as $key => $hits_present) {
            if ($hits_present !== $hits_required) {
                continue;
            }
            $result[$key] = $items[$key];
        }

        return $result;
    }

    final public static function isHavingItems()
    {
        error_log(__METHOD__ .'(), legacy isHavingItems() has been called, '
            .'update your application to hasModelItems() to avoid this message.');

        return static::hasModelItems();
    }

    final public static function hasModelItems()
    {
        $called_class = get_called_class();

        if (!property_exists($called_class, 'model_has_items')) {
            return false;
        }

        if (empty($called_class::$model_has_items) ||
            !is_bool($called_class::$model_has_items) ||
            !$called_class::$model_has_items
        ) {
            return false;
        }

        return true;
    }

    public function hasItems()
    {
        $called_class = get_called_class();
        if (!$called_class::hasModelItems()) {
            static::raiseError(__METHOD__ ."(), model {$called_class} is not declared to have items!", true);
            return false;
        }

        if (!isset($this->model_items) ||
            empty($this->model_items) ||
            !is_array($this->model_items)
        ) {
            return false;
        }

        return true;
    }

    public function addItem($item)
    {
        if (!static::hasModelItems()) {
            static::raiseError(__METHOD__ .'(), model '. __CLASS__ .' is not declared to have items!');
            return false;
        }

        if (!isset($item) || empty($item)) {
            static::raiseError(__METHOD__ .'(), $item parameter is invalid!');
            return false;
        }

        if (is_array($item)) {
            if (!array_key_exists(FIELD_MODEL, $item)) {
                static::raiseError(__METHOD__ .'(), $item misses FIELD_MODEL key!');
                return false;
            }
            if (!array_key_exists(FIELD_IDX, $item)) {
                static::raiseError(__METHOD__ .'(), $item misses FIELD_IDX key!');
                return false;
            }
            if (!array_key_exists(FIELD_GUID, $item)) {
                static::raiseError(__METHOD__ .'(), $item misses FIELD_GUID key!');
                return false;
            }
            if (!isset($item[FIELD_IDX]) || empty($item[FIELD_IDX]) || !is_numeric($item[FIELD_IDX])) {
                static::raiseError(__METHOD__ .'(), $item FIELD_IDX is invalid!');
                return false;
            }
            if (!isset($item[FIELD_GUID]) || empty($item[FIELD_GUID]) || !is_string($item[FIELD_GUID])) {
                static::raiseError(__METHOD__ .'(), $item FIELD_GUID is invalid!');
                return false;
            }
            if (!isset($item[FIELD_MODEL]) || empty($item[FIELD_MODEL]) || !is_string($item[FIELD_MODEL])) {
                static::raiseError(__METHOD__ .'(), $item FIELD_MODEL is invalid!');
                return false;
            }
            $idx = $item[FIELD_IDX];
            $model = $item[FIELD_MODEL];
            unset($item[FIELD_MODEL]);
        } elseif (is_object($item)) {
            if (!method_exists($item, 'getIdx') || !is_callable(array(&$item, 'getIdx'))) {
                static::raiseError(__METHOD__ .'(), item model '. get_class($item) .' has no getIdx() method!');
                return false;
            }
            if (!method_exists($item, 'getGuid') || !is_callable(array(&$item, 'getGuid'))) {
                static::raiseError(__METHOD__ .'(), item model '. get_class($item) .' has no getGuid() method!');
                return false;
            }
            if (($idx = $item->getIdx()) === false) {
                static::raiseError(get_class($item) .'::getIdx() returned false!');
                return false;
            }
            $model = $item::$model_column_prefix;
        } else {
            static::raiseError(__METHOD__ .'(), $item type is not supported!');
            return false;
        }

        if (array_key_exists($idx, $this->model_items)) {
            static::raiseError(__METHOD__ ."(), item with key {$idx} does already exist!");
            return false;
        }

        if (!$this->setItemModel($idx, $model)) {
            static::raiseError(__CLASS__ .'::setItemModel() returned false!');
            return false;
        }

        if (!$this->setItemData($idx, $item, false)) {
            static::raiseError(__CLASS__ .'::setItemData() returned false!');
            return false;
        }

        return true;
    }

    public function getItem($idx, $reset = true, $allow_cached = true)
    {
        global $cache;

        if (!isset($idx) || empty($idx) || (!is_string($idx) && !is_numeric($idx))) {
            static::raiseError(__METHOD__ .'(), $idx parameter is invalid!');
            return false;
        }

        if (!$this->hasItem($idx)) {
            static::raiseError(__CLASS__ .'::hasItem() returned false!');
            return false;
        }

        if (($item_model = $this->getItemModel($idx)) === false) {
            static::raiseError(__CLASS__ .'::getItemData() returned false!');
            return false;
        }

        $cache_key = sprintf("%s_%s", $item_model, $idx);

        if (isset($allow_cached) &&
            $allow_cached === true &&
            $cache->has($cache_key)
        ) {
            if (($item = $cache->get($cache_key)) === false) {
                static::raiseError(get_class($cache) .'::get() returned false!');
                return false;
            }
            if (isset($reset) && $reset === true) {
                if (!$item->resetFields()) {
                    static::raiseError(get_class($item) .'::resetFields() returned false!');
                    return false;
                }
            }
            return $item;
        }

        /* item fields data may be already available thru bulkloading. */
        if ($this->hasItemData($idx)) {
            if (($item_data = $this->getItemData($idx)) === false) {
                static::raiseError(__CLASS__ .'::getItemData() returned false!');
                return false;
            }

            if (!isset($item_data) ||
                empty($item_data) ||
                !is_array($item_data)
            ) {
                static::raiseError(__CLASS__ .'::getItemData() returned invalid data!');
                return flase;
            }

            try {
                $item = new $item_model;
            } catch (\Exception $e) {
                static::raiseError(__METHOD__ ."(), failed to load {$item_model}!", false, $e);
                return false;
            }

            if (!$item->flood($item_data)) {
                static::raiseError(get_class($item) .'::flood() returned false!');
                return false;
            }
        } else {
            try {
                $item = new $item_model(array(
                    FIELD_IDX => $idx
                ));
            } catch (\Exception $e) {
                static::raiseError(__METHOD__ ."(), failed to load {$item_model}!", false, $e);
                return false;
            }
        }

        if (!isset($item) || empty($item)) {
            static::raiseError(__METHOD__ .'(), no valid item found!');
            return false;
        }

        if (!$cache->add($item, $cache_key)) {
            static::raiseError(get_class($cache) .'::add() returned false!');
            return false;
        }

        if (!$this->updateItemsLookupCache($item)) {
            static::raiseError(__CLASS__ .'::updateItemsLookupCache() returned false!');
            return false;
        }

        return $item;
    }

    public function hasItem($idx)
    {
        if (!isset($idx) || empty($idx) || (!is_string($idx) && !is_numeric($idx))) {
            static::raiseError(__METHOD__ .'(), $idx parameter is invalid!');
            return false;
        }

        if (!array_key_exists($idx, $this->model_items)) {
            return false;
        }

        return true;
    }

    protected function hasItemData($key)
    {
        if (!isset($key) ||
            empty($key) ||
            (!is_integer($key) && !is_numeric($key))
        ) {
            static::raiseError(__METHOD__ .'(), $key parameter is invalid!');
            return false;
        }

        if (!$this->hasItem($key)) {
            static::raiseError(__CLASS__ .'::hasItem() returned false!');
            return false;
        }

        if (!isset($this->model_items[$key][FIELD_INIT]) ||
            !is_bool($this->model_items[$key][FIELD_INIT]) ||
            !$this->model_items[$key][FIELD_INIT]
        ) {
            return false;
        }

        return true;
    }

    protected function hasItemModel($key)
    {
        if (!isset($key) ||
            empty($key) ||
            (!is_integer($key) && !is_numeric($key))
        ) {
            static::raiseError(__METHOD__ .'(), $key parameter is invalid!');
            return false;
        }

        if (!$this->hasItem($key)) {
            static::raiseError(__CLASS__ .'::hasItem() returned false!');
            return false;
        }

        if (!isset($this->model_items[$key][FIELD_MODEL]) ||
            empty($this->model_items[$key][FIELD_MODEL]) ||
            !is_string($this->model_items[$key][FIELD_MODEL])
        ) {
            return false;
        }

        return true;
    }

    protected function getItemModel($key)
    {
        if (!isset($key) ||
            empty($key) ||
            (!is_integer($key) && !is_numeric($key))
        ) {
            static::raiseError(__METHOD__ .'(), $key parameter is invalid!');
            return false;
        }

        if (!$this->hasItemModel($key)) {
            static::raiseError(__CLASS__ .'::hasItemModel() returned false!');
            return false;
        }

        if (!array_key_exists(FIELD_MODEL, $this->model_items[$key])) {
            static::raiseError(__METHOD__ .'(), item contains no model key!');
            return false;
        }

        return $this->model_items[$key][FIELD_MODEL];
    }

    protected function setItemModel($key, $model)
    {
        if (!isset($key) ||
            empty($key) ||
            (!is_integer($key) && !is_numeric($key))
        ) {
            static::raiseError(__METHOD__ .'(), $key parameter is invalid!');
            return false;
        }

        if (!isset($model) || empty($model) || !is_string($model)) {
            static::raiseError(__METHOD__ .'(), $model parameter is invalid!');
            return false;
        }

        if (!$this->hasItem($key)) {
            $this->model_items[$key] = array();
        }

        $this->model_items[$key][FIELD_MODEL] = $model;
        return true;
    }

    protected function getItemData($key)
    {
        if (!isset($key) ||
            empty($key) ||
            (!is_integer($key) && !is_numeric($key))
        ) {
            static::raiseError(__METHOD__ .'(), $key parameter is invalid!');
            return false;
        }

        if (!$this->hasItemData($key)) {
            static::raiseError(__CLASS__ .'::hasItemData() returned false!');
            return false;
        }

        return $this->model_items[$key][FIELD_DATA];
    }

    protected function setItemData($key, $data, $init = true)
    {
        if (!isset($key) ||
            empty($key) ||
            (!is_integer($key) && !is_numeric($key))
        ) {
            static::raiseError(__METHOD__ .'(), $key parameter is invalid!');
            return false;
        }

        if (!isset($data) || empty($data) || !is_array($data)) {
            static::raiseError(__METHOD__ .'(), $data parameter is invalid!');
            return false;
        }

        if (!$this->hasItem($key)) {
            $this->model_items[$key] = array();
        }

        $this->model_items[$key][FIELD_DATA] = $data;
        $this->model_items[$key][FIELD_INIT] = $init;
        return true;
    }

    public function getItemsCount()
    {
        if (!$this->hasItems()) {
            return false;
        }

        if (!isset($this->model_items)) {
            return false;
        }

        return count($this->model_items);
    }

    public function isNewModel()
    {
        if (isset($this->model_load_by) &&
            !empty($this->model_load_by) &&
            is_array($this->model_load_by)
        ) {
            return false;
        }

        return true;
    }

    public static function getFieldType($field_name)
    {
        if (!isset($field_name) || empty($field_name) || !is_string($field_name)) {
            static::raiseError(__METHOD__ .'(), $field_name parameter is invalid!');
            return false;
        }

        if (!static::hasField($field_name)) {
            static::raiseError(__METHOD__ ."(), model has no field {$field_name}!");
            return false;
        }

        return static::$model_fields[$field_name][FIELD_TYPE];
    }

    public static function getFieldLength($field_name)
    {
        if (!isset($field_name) || empty($field_name) || !is_string($field_name)) {
            static::raiseError(__METHOD__ .'(), $field_name parameter is invalid!');
            return false;
        }

        if (!static::hasField($field_name)) {
            static::raiseError(__METHOD__ ."(), model has no field {$field_name}!");
            return false;
        }

        if (!static::hasFieldLength($field_name) && static::getFieldType($field_name) === FIELD_STRING) {
            return 255;
        }

        return static::$model_fields[$field_name][FIELD_LENGTH];
    }

    public static function hasFieldLength($field_name)
    {
        if (!isset($field_name) || empty($field_name) || !is_string($field_name)) {
            static::raiseError(__METHOD__ .'(), $field_name parameter is invalid!');
            return false;
        }

        if (!static::hasField($field_name)) {
            static::raiseError(__METHOD__ ."(), model has no field {$field_name}!");
            return false;
        }

        if (!array_key_exists(FIELD_LENGTH, static::$model_fields[$field_name])) {
            return false;
        }

        return true;
    }

    public function getTableName()
    {
        return sprintf("TABLEPREFIX%s", static::$model_table_name);
    }

    public static function getFieldNameFromColumn($column)
    {
        if (!isset($column) || empty($column) || !is_string($column)) {
            static::raiseError(__METHOD__ .'(), $column parameter is invalid!');
            return false;
        }

        if (strpos($column, static::$model_column_prefix .'_') === false) {
            return $column;
        }

        $field_name = str_replace(static::$model_column_prefix .'_', '', $column);

        if (!static::hasField($field_name) &&
            (isset($this) &&
            !empty($this) &&
            $this->hasVirtualFields() &&
            !$this->hasVirtualField($field_name))
        ) {
            static::raiseError(__CLASS__ .'::hasField() returned false!');
            return false;
        }

        return $field_name;
    }

    public static function validateField($field, $value)
    {
        global $thallium;

        if (!isset($field) || empty($field) || !is_string($field)) {
            static::raiseError(__METHOD__ .'(), $field parameter is invalid!');
            return false;
        }

        if (!static::hasFields()) {
            static::raiseError(__CLASS__ .'::hasFields() returned false!');
            return false;
        }

        if (($type = static::getFieldType($field)) === false) {
            static::raiseError(__CLASS__ .'::getFieldType() returned false!');
            return false;
        }

        // empty values we can not check
        if (empty($value)) {
            return true;
        }

        switch ($type) {
            case FIELD_STRING:
                if (!is_string($value)) {
                    return false;
                }
                break;
            case FIELD_INT:
                if (!is_numeric($value) || !is_int((int) $value)) {
                    return false;
                }
                break;
            case FIELD_BOOL:
                if (!is_bool($value)) {
                    return false;
                }
                break;
            case FIELD_YESNO:
                if (!in_array($value, array('yes', 'no', 'Y', 'N'))) {
                    return false;
                }
                break;
            case FIELD_TIMESTAMP:
                if (is_float((float) $value)) {
                    if ((float) $value >= PHP_INT_MAX || (float) $value <= ~PHP_INT_MAX) {
                        return false;
                    }
                } elseif (is_int((int) $value)) {
                    if ((int) $value >= PHP_INT_MAX || (int) $value <= ~PHP_INT_MAX) {
                        return false;
                    }
                } elseif (is_string($value)) {
                    if (strtotime($value) === false) {
                        return false;
                    }
                } else {
                    static::raiseError(__METHOD__ .'(), unsupported timestamp type found!');
                    return false;
                }
                break;
            case FIELD_DATE:
                if ($value !== "0000-00-00" &&
                    strtotime($value) === false
                ) {
                    return false;
                }
                break;
            case FIELD_GUID:
                if (!$thallium->isValidGuidSyntax($value)) {
                    return false;
                }
                break;
            default:
                static::raiseError(__METHOD__ ."(), unsupported type {$type} received!");
                return false;
                break;
        }

        return true;
    }

    public static function validateItemsFilter($filter)
    {
        if (!isset($filter) || empty($filter) || !is_array($filter)) {
            static::raiseError(__METHOD__ .'(), $filter parameter is invalid!');
            return false;
        }

        foreach ($filter as $field => $pattern) {
            if (!isset($field) || empty($field) || !is_string($field)) {
                static::raiseError(__METHOD__ .'(), $filter parameter contains an invalid $field name!');
                return false;
            }
            if (!isset($pattern) || empty($pattern) || (!is_string($pattern) && !is_int($pattern))) {
                static::raiseError(__METHOD__ .'(), $filter parameter contains an invalid $pattern!' . $pattern);
                return false;
            }
        }

        return true;
    }

    public function flush()
    {
        if (!static::hasModelItems()) {
            return $this->flushTable();
        }

        if (!$this->delete()) {
            static::raiseError(__CLASS__ .'::delete() returned false!');
            return false;
        }

        if (!$this->flushTable()) {
            static::raiseError(__CLASS__ .'::flushTable() returned false!');
            return false;
        }

        return true;
    }

    public function flushTable()
    {
        global $db;

        try {
            $db->query(sprintf(
                "TRUNCATE TABLE TABLEPREFIX%s",
                static::$model_table_name
            ));
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), SQL command TRUNCATE TABLE failed!');
            return false;
        }

        return true;
    }

    protected function hasFieldSetMethod($field)
    {
        if (!isset($field) || empty($field) || !is_string($field)) {
            static::raiseError(__METHOD__ .'(), $field parameter is invalid!');
            return false;
        }

        if (!static::hasFields()) {
            static::raiseError(__METHOD__ .'(), this model has no fields!');
            return false;
        }

        if (!static::hasField($field)) {
            static::raiseError(__METHOD__ .'(), model does not provide the requested field!');
            return false;
        }

        if (!isset(static::$model_fields[$field]) ||
            empty(static::$model_fields[$field]) ||
            !is_array(static::$model_fields[$field])
        ) {
            static::raiseError(__METHOD__ .'(), $model_fields does not contain requested field!');
            return false;
        }

        if (!isset(static::$model_fields[$field][FIELD_SET]) ||
            empty(static::$model_fields[$field][FIELD_SET]) ||
            !is_string(static::$model_fields[$field][FIELD_SET]) ||
            !method_exists(get_called_class(), static::$model_fields[$field][FIELD_SET])
        ) {
            return false;
        }

        return true;
    }

    protected function getFieldSetMethod($field)
    {
        if (!isset($field) || empty($field) || !is_string($field)) {
            static::raiseError(__METHOD__ .'(), $field parameter is invalid!');
            return false;
        }

        if (!static::hasFieldSetMethod($field)) {
            static::raiseError(__CLASS__ .'::hasFieldSetMethod() returned false!');
            return false;
        }

        return static::$model_fields[$field][FIELD_SET];
    }

    protected function hasFieldGetMethod($field)
    {
        if (!isset($field) || empty($field) || !is_string($field)) {
            static::raiseError(__METHOD__ .'(), $field parameter is invalid!');
            return false;
        }

        if (!static::hasFields()) {
            static::raiseError(__METHOD__ .'(), this model has no fields!');
            return false;
        }

        if (!static::hasField($field)) {
            static::raiseError(__METHOD__ .'(), model does not provide the requested field!');
            return false;
        }

        if (!isset(static::$model_fields[$field]) ||
            empty(static::$model_fields[$field]) ||
            !is_array(static::$model_fields[$field])
        ) {
            static::raiseError(__METHOD__ .'(), $model_fields does not contain requested field!');
            return false;
        }

        if (!isset(static::$model_fields[$field][FIELD_GET]) ||
            empty(static::$model_fields[$field][FIELD_GET]) ||
            !is_string(static::$model_fields[$field][FIELD_GET]) ||
            !method_exists(get_called_class(), static::$model_fields[$field][FIELD_GET])
        ) {
            return false;
        }

        return true;
    }

    protected function getFieldGetMethod($field)
    {
        if (!isset($field) || empty($field) || !is_string($field)) {
            static::raiseError(__METHOD__ .'(), $field parameter is invalid!');
            return false;
        }

        if (!static::hasFieldGetMethod($field)) {
            static::raiseError(__CLASS__ .'::hasFieldGetMethod() returned false!');
            return false;
        }

        return static::$model_fields[$field][FIELD_GET];
    }

    final public function hasFieldValue($field)
    {
        if (!isset($field) || empty($field) || !is_string($field)) {
            static::raiseError(__METHOD__ .'(), $field parameter is invalid!');
            return false;
        }

        if (!static::hasFields() && isset($this) && !$this->hasVirtualFields()) {
            static::raiseError(__METHOD__ .'(), this model has no fields!');
            return false;
        }

        if (!static::hasField($field) && isset($this) && !$this->hasVirtualField($field)) {
            static::raiseError(__METHOD__ .'(), this model has not that field!');
            return false;
        }

        if (static::hasField($field)) {
            if (!isset($this->model_values[$field]) ||
                empty($this->model_values[$field])
            ) {
                return false;
            }
        } elseif (isset($this) && !$this->hasVirtualField($field)) {
            if (!$this->hasVirtualFieldValue($field)) {
                return false;
            }
        } else {
            static::raiseError(__METHOD__ .'(), do not know how to locate that field!');
            return false;
        }

        return true;
    }

    final public function setFieldValue($field, $value)
    {
        if (!isset($field) || empty($field) || !is_string($field)) {
            static::raiseError(__METHOD__ .'(), $field parameter is invalid!');
            return false;
        }

        if (!static::hasFields()) {
            static::raiseError(__METHOD__ .'(), this model has no fields!');
            return false;
        }

        if (!static::hasField($field) && !$this->hasVirtualField($field)) {
            static::raiseError(__METHOD__ .'(), this model has not that field!');
            return false;
        }

        if (!isset($value)) {
            if (static::hasField($field)) {
                $this->model_values[$field] = null;
            } elseif ($this->hasVirtualField($field)) {
                if (!$this->setVirtualFieldValue($field, null)) {
                    static::raiseError(__CLASS__ .'::setVirtualFieldValue() returned false!');
                    return false;
                }
            } else {
                static::raiseError(__METHOD__ .'(), do not know how to set that field!');
                return false;
            }
            return true;
        }

        if ($this->hasFieldLength($field)) {
            if ($this->getFieldType($field) === FIELD_STRING) {
                if (($field_length = $this->getFieldLength($field)) === false) {
                    static::raiseError(__CLASS__ .'::getFieldLength() returned false!');
                    return false;
                }
                $value_length = strlen($value);
                if ($value_length > $field_length) {
                    static::raiseError(
                        __METHOD__ ."(), values length ({$value_length}) exceeds fields length ({$field_length})!"
                    );
                    return false;
                }
            }
        }

        if (static::hasField($field)) {
            $this->model_values[$field] = $value;
        } elseif ($this->hasVirtualField($field)) {
            if (!$this->setVirtualFieldValue($field, $value)) {
                static::raiseError(__CLASS__ .'::setVirtualFieldValue() returned false!');
                return false;
            }
        } else {
            static::raiseError(__METHOD__ .'(), do not know how to set that field!');
            return false;
        }

        return true;
    }

    final public function getFieldValue($field)
    {
        if (!isset($field) || empty($field) || !is_string($field)) {
            static::raiseError(__METHOD__ .'(), $field parameter is invalid!');
            return false;
        }

        if (!$this->hasFieldValue($field)) {
            static::raiseError(__CLASS__ .'::hasFieldValue() returned false!');
            return false;
        }

        return $this->model_values[$field];
    }

    final public function hasDefaultValue($field)
    {
        if (!isset($field) || empty($field) || !is_string($field)) {
            static::raiseError(__METHOD__ .'(), $field parameter is invalid!');
            return false;
        }

        if (!static::hasFields()) {
            static::raiseError(__METHOD__ .'(), this model has no fields!');
            return false;
        }

        if (!static::hasField($field)) {
            static::raiseError(__METHOD__ .'(), this model has not that field!');
            return false;
        }

        if (!isset($this->model_fields[$field][FIELD_DEFAULT])) {
            return false;
        }

        return true;
    }

    final public function getDefaultValue($field)
    {
        if (!isset($field) || empty($field) || !is_string($field)) {
            static::raiseError(__METHOD__ .'(), $field parameter is invalid!');
            return false;
        }

        if (!$this->hasDefaultValue($field)) {
            static::raiseError(__CLASS__ .'::hasDefaultValue() returned false!');
            return false;
        }

        return $this->model_model_fields[$field][FIELD_DEFAULT];
    }

    public static function exists($load_by = array())
    {
        global $db;

        if (!isset($load_by) || empty($load_by) || (!is_array($load_by) && !is_null($load_by))) {
            static::raiseError(__METHOD__ .'(), parameter $load_by has to be an array!', true);
            return;
        }

        if (($idx = static::column('idx')) === false) {
            static::raiseError(__CLASS__ .'::column() returned false!');
            return false;
        }

        $query_columns = array(
            $idx
        );

        $query_where = array();

        foreach ($load_by as $field => $value) {
            if (($column = static::column($field)) === false) {
                static::raiseError(__CLASS__ .'::column() returned false!');
                return false;
            }
            $query_where[$column] = $value;
        }

        $bind_params = array();

        if (($sql = $db->buildQuery(
            "SELECT",
            self::getTableName(),
            $query_columns,
            $query_where,
            $bind_params
        )) === false) {
            static::raiseError(get_class($db) .'::buildQuery() returned false!');
            return false;
        }

        try {
            $sth = $db->prepare($sql);
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), unable to prepare database query!');
            return false;
        }

        if (!$sth) {
            static::raiseError(get_class($db) ."::prepare() returned invalid data!");
            return false;
        }

        foreach ($bind_params as $key => $value) {
            $sth->bindParam($key, $value);
        }

        if (!$db->execute($sth, $bind_params)) {
            static::raiseError(__METHOD__ ."(), unable to execute query!");
            return false;
        }

        $num_rows = $sth->rowCount();
        $db->freeStatement($sth);

        if ($num_rows < 1) {
            return false;
        }

        if ($num_rows > 1) {
            static::raiseError(__METHOD__ .'(), more than one object found!');
            return false;
        }

        return true;
    }

    public static function hasModelLinks()
    {
        if (!isset(static::$model_links) ||
            empty(static::$model_links)
        ) {
            return false;
        }

        return true;
    }

    public static function getModelLinks()
    {
        if (!static::hasModelLinks()) {
            static::raiseError(__CLASS__ .'::hasModelLinks() returned false!');
            return false;
        }

        return static::$model_links;
    }

    protected function deleteModelLinks()
    {
        global $thallium;

        if (!static::hasModelLinks()) {
            return true;
        }

        if (($links = static::getModelLinks()) === false) {
            static::raiseError(__CLASS__ .'::getModelLinks() returned false!');
            return false;
        }

        foreach ($links as $link) {
            list($model, $field) = explode('/', $link);

            if (($model_name = $thallium->getFullModelName($model)) === false) {
                static::raiseError(get_class($thallium) .'::getFullModelName() returned false!');
                return false;
            }

            if (($idx = $this->getIdx()) === false) {
                static::raiseError(__CLASS__ .'::getIdx() returned false!');
                return false;
            }

            try {
                $model = new $model_name(array(
                    $field => $idx,
                ));
            } catch (\Exception $e) {
                static::raiseError(__METHOD__ ."(), failed to load {$model_name}!", false, $e);
                return false;
            }

            if (!$model->delete()) {
                static::raiseError(get_class($model) .'::delete() returned false!');
                return false;
            }
        }

        return true;
    }

    public function resetFields()
    {
        if (!static::hasFields()) {
            static::raiseError(__CLASS__ .'::hasFields() returned false!');
            return false;
        }

        if (!isset($this->model_init_values) ||
            empty($this->model_init_values) ||
            !is_array($this->model_init_values)
        ) {
            static::raiseError(__METHOD__ .'(), no inital field values found!');
            return false;
        }

        if (!$this->update($this->model_init_values)) {
            static::raiseError(__CLASS__ .'::update() returned false!');
            return false;
        }

        return true;
    }

    protected function updateItemsLookupCache($item)
    {
        if (!isset($item) || empty($item) || !is_object($item)) {
            static::raiseError(__METHOD__ .'(), $item parameter is invalid!');
            return false;
        }

        if (!$item::hasFields()) {
            static::raiseError(get_class($item) .'::hasFields() returned false!');
            return false;
        }

        $index_fields = array(
            FIELD_IDX,
            FIELD_GUID,
        );

        if (isset(static::$model_fields_index) &&
            !empty(static::$model_fields_index) &&
            is_array(static::$model_fields_index)
        ) {
            foreach (static::$model_fields_index as $field) {
                if (!$static::hasField($field)) {
                    static::raiseError(__CLASS__ .'::hasField() returned false!');
                    return false;
                }
                array_push($index_fields, $field);
            }
        }

        if (($fields = $item->getFieldNames()) === false) {
            static::raiseError(get_class($item) .'::getFields() returned false!');
            return false;
        }

        foreach ($fields as $field) {
            if (!in_array($field, $index_fields)) {
                continue;
            }

            if (!isset($this->model_items_lookup_index[$field])) {
                $this->model_items_lookup_index[$field] = array();
            }

            if (($idx = $item->getIdx()) === false) {
                static::raiseError(get_class($item) .'::getIdx() returned false!');
                return false;
            }

            if (!$item->hasFieldValue($field)) {
                continue;
            }

            if (($value = $item->getFieldValue($field)) === false) {
                static::raiseError(get_class($field) .'::getFieldValue() returned false!');
                return false;
            }

            if (isset($this->model_items_lookup_index[$field][$idx])) {
                static::raiseError(__METHOD__ .'(), a lookup index entry is already present for that item!');
                return false;
            }

            $this->model_items_lookup_index[$field][$idx] = $value;
        }

        return true;
    }

    public static function getModelName($short = false)
    {
        if (!isset($short) || $short === false) {
            return static::class;
        }

        $parts = explode('\\', static::class);

        return array_pop($parts);
    }

    public function __toString()
    {
        if (($model_name = static::getModelName(true)) === false) {
            static::raiseError(__CLASS__ .'::getModelName() returned false!');
            return false;
        }

        if (($idx = $this->getIdx()) === false) {
            static::raiseError(__CLASS__ .'::getIdx() returend false!');
            return false;
        }

        if (($guid = $this->getGuid()) === false) {
            static::raiseError(__CLASS__ .'::getGuid() returend false!');
            return false;
        }

        if (method_exists($this, 'hasName') && $this->hasName()) {
            if (($name = $this->getName()) === false) {
                static::raiseError(__CLASS__ .'::getName() returned false!');
                return false;
            }
        }

        if (!isset($name)) {
            return sprintf('%s_%s_%s', $model_name, $idx, $guid);
        }

        return sprintf('%s_%s_%s_%s', $model_name, $name, $idx, $guid);
    }

    public function getModelLinkedList($sorted = false, $unique = false)
    {
        global $thallium;

        if (($model_links = static::getModelLinks()) === false) {
            static::raiseError(__CLASS__ .'::getModelLinks() returned false!');
            return false;
        }

        if (!is_array($model_links) || empty($model_links)) {
            return true;
        }

        if (($model_idx = $this->getIdx()) === false) {
            static::raiseError(__CLASS__ .'::getIdx() returned false!');
            return false;
        }

        $links = array();

        foreach ($model_links as $target => $field) {
            if (!$this->hasFieldValue($field)) {
                continue;
            }

            if (($field_value = $this->getFieldValue($field)) === false) {
                static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
                return false;
            }

            if (($link_target = $this->getModelLinkTarget($target, $field_value)) === false) {
                static::raiseError(__CLASS__ .'::getModelLinkTarget() returned false!');
                return false;
            }

            if (!isset($link_target) || empty($link_target)) {
                continue;
            }

            if (is_object($link_target)) {
                array_push($links, $link_target);
                continue;
            }

            if (!is_array($link_target)) {
                static::raiseError(__CLASS__ .'::getModelLinkTarget() returned unexpected data!');
                return false;
            }

            $links = array_merge($links, $link_target);
        }

        if (isset($sorted) && $sorted === true) {
            sort($links);
        }

        if (isset($unique) && $unique === true) {
            $links = array_unique($links);
        }

        return $links;
    }

    protected function getModelLinkTarget($link, $value)
    {
        global $thallium;

        if (!isset($link) || empty($link) || !is_string($link)) {
            static::raiseError(__METHOD__ .'(), $link parameter is invalid!');
            return false;
        }

        if (!isset($value) ||
            empty($value) ||
            (!is_string($value) && !is_numeric($value))
        ) {
            static::raiseError(__METHOD__ .'(), $value parameter is invalid!');
            return false;
        }

        if (($parts = explode('/', $link)) === false) {
            static::raiseError(__METHOD__ .'(), explode() returned false!');
            return false;
        }

        if (count($parts) < 2 ||
            !isset($parts[0]) || empty($parts[0]) || !is_string($parts[0]) ||
            !isset($parts[1]) || empty($parts[1]) || !is_string($parts[1])
        ) {
            static::raiseError(__METHOD__ .'(), link information incorrectly declared!');
            return false;
        }

        $model = $parts[0];
        $field = $parts[1];

        if (($full_model = $thallium->getFullModelName($model)) === false) {
            static::raiseError(get_class($thallium) .'::getFullModelName() returned false!');
            return false;
        }

        try {
            $obj = new $full_model(array(
                $field => $value,
            ));
        } catch (\Exception $e) {
            static::raiseError(sprintf('%s(), failed to load %s!', __METHOD__, $full_model), false, $e);
            return false;
        }

        if (!$obj->hasModelItems() && $obj->isNew()) {
            return;
        } elseif ($obj->hasModelItems() && !$obj->hasItems()) {
            return;
        }

        if (!$obj->hasModelItems()) {
            return $obj;
        }

        if (($items = $obj->getItems()) === false) {
            static::raiseError(get_class($obj) .'::getItems() returned false!');
            return false;
        }

        return $items;
    }

    final public static function hasModelItemsModel()
    {
        $called_class = get_called_class();

        if (!$called_class::hasModelItems()) {
            static::raiseError(sprintf('%s(), %s::hasModelItems() returned false!', __METHOD__, $called_class));
            return false;
        }

        if (!property_exists($called_class, 'model_items_model') ||
            empty($called_class::$model_items_model) ||
            !is_string($called_class::$model_items_model)
        ) {
            return false;
        }

        return true;
    }

    final public static function getModelItemsModel()
    {
        if (!static::hasModelItemsModel()) {
            static::raiseError(__CLASS__ .'::hasModelItemsModel() returned false!');
            return false;
        }

        $called_class = get_called_class();

        return $called_class::$model_items_model;
    }

    final public static function hasModelFriendlyName()
    {
        $called_class = get_called_class();

        if (!property_exists($called_class, 'model_friendly_name') ||
            !isset($called_class::$model_friendly_name) ||
            empty($called_class::$model_friendly_name) ||
            !is_string($called_class::$model_friendly_name)
        ) {
            return false;
        }

        return true;
    }

    final public static function getModelFriendlyName()
    {
        if (!static::hasModelFriendlyName()) {
            static::raiseError(__CLASS__ .'::hasModelFriendlyName() returned false!');
            return false;
        }

        $called_class = get_called_class();

        return $called_class::$model_friendly_name;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
