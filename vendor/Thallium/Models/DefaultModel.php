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

namespace Thallium\Models;

use \PDO;

abstract class DefaultModel
{
    protected static $model_table_name;
    protected static $model_column_prefix;
    protected static $model_fields = array();
    protected static $model_has_items = false;
    protected static $model_items_model;
    protected static $model_links = array();
    protected $model_load_by = array();
    protected $model_sort_order = array();
    protected $model_items = array();
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

        $this->model_init_values = array();

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

        if (static::hasFields() && static::isHavingItems()) {
            static::raiseError(__METHOD__ .'(), model must no have fields and items at the same times!', true);
            return false;
        }

        if (static::isHavingItems()) {
            if (!isset(static::$model_items_model) ||
                empty(static::$model_items_model) ||
                !is_string(static::$model_items_model) ||
                !$thallium->isRegisteredModel(null, static::$model_items_model)
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
                    (static::isHavingItems() &&
                    ($full_model = $thallium->getFullModelName(static::$model_items_model)) !== false &&
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
                if (($full_model = $thallium->getFullModelName(static::$model_items_model)) === false) {
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

        if (isset(static::$model_links) || !empty(static::$model_links)) {
            if (!is_array(static::$model_links)) {
                static::raiseError(__METHOD__ .'(), $model_links is not an array!', true);
                return false;
            }
            foreach (static::$model_links as $link) {
                if (!isset($link) || empty($link) || !is_string($link)) {
                    static::raiseError(__METHOD__ .'(), $model_links contains invalid member!', true);
                    return false;
                }
                if ((list($model, $field) = explode('/', $link)) === false) {
                    static::raiseError(__METHOD__ .'(), failed to explode() $model_links member!', true);
                    return false;
                }
                if (!isset($model) || empty($model) || !is_string($model)) {
                    static::raiseError(__METHOD__ .'(), $model_links member model value is invalid!', true);
                    return false;
                }
                if (!$thallium->isValidModel($model)) {
                    static::raiseError(
                        __METHOD__ .'(), $model_links member model value refers an unknown model!',
                        true
                    );
                    return false;
                }
                if (!isset($field) || empty($field) || !is_string($field)) {
                    static::raiseError(__METHOD__ .'(), $model_links member model field is invalid!', true);
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

        if (!static::hasFields() && !static::isHavingItems()) {
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
        } elseif (static::isHavingItems()) {
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
                if (!$this->setFieldValue($field, $value)) {
                    static::raiseError(__CLASS__ ."::setFieldValue() returned false for field {$field}!");
                    return false;
                }
                $this->model_init_values[$field] = $value;
            }
        } elseif (static::isHavingItems()) {
            while (($row = $sth->fetch(\PDO::FETCH_ASSOC)) !== false) {
                if (($child_model_name = $thallium->getFullModelName(static::$model_items_model)) === false) {
                    static::raiseError(get_class($thallium) .'::getFullModelName() returned false!');
                    return false;
                }
                foreach ($row as $key => $value) {
                    if (($field = $child_model_name::getFieldNameFromColumn($key)) === false) {
                        static::raiseError(__CLASS__ .'() returned false!');
                        return false;
                    }
                    if (!in_array($field, array(FIELD_IDX, FIELD_GUID))) {
                        static::raiseError(__METHOD__ ."(), received data for unknown field '{$field}'!");
                        return false;
                    }
                    if (!$child_model_name::validateField($field, $value)) {
                        static::raiseError(__CLASS__ ."::validateField() returned false for field {$field}!");
                        return false;
                    }
                }

                $item = array(
                    FIELD_MODEL => $child_model_name,
                    FIELD_IDX => $row[$child_model_name::column(FIELD_IDX)],
                    FIELD_GUID => $row[$child_model_name::column(FIELD_GUID)],
                );

                if (!$this->addItem($item)) {
                    static::raiseError(__CLASS__ .'::addItem() returned false!');
                    return false;
                }
            }
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
                $set_method = sprintf("set%s", ucwords($field));
                if (!is_callable(array($this, $set_method))) {
                    static::raiseError(__CLASS__ ."::{$set_method}() is not callable!");
                    return false;
                }
                if (!call_user_func(array($this, $set_method), $value)) {
                    static::raiseError(__CLASS__ ."::{$set_method}() returned false!");
                    return false;
                }
                continue;
            }
            static::raiseError(__METHOD__ .'(), model has no field like that!');
            return false;
        }

        return true;

    } // update()

    /**
     * delete
     */
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

        if (static::isHavingItems() && !$this->hasItems()) {
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

        if (static::isHavingItems()) {
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
        if (!static::isHavingItems()) {
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

        foreach (array_keys($srcobj::model_fields) as $field) {
            // check for a matching key in clone's model_fields array
            if (!static::hasField($field)) {
                continue;
            }

            $this->model_values[$field] = $srcobj->model_values[$field];
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
        global $ms;

        if ($this->hasVirtualFields() && $this->hasVirtualField($name)) {
            if (($name = static::getFieldNamefromColumn($name)) === false) {
                static::raiseError(__CLASS__ .'::getFieldNameFromColumn() returned false!', true);
                return;
            }

            $method_name = 'set'.ucwords(strtolower($name));

            if (!method_exists($this, $method_name) && is_callable(array($this, $method_name))) {
                static::raiseError(__METHOD__ .'(), virtual field exists but there is no set-method for it!', true);
                return;
            }

            if (!$this->$method_name($value)) {
                static::raiseError(__CLASS__ ."::{$method_name} returned false!", true);
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
        if ($value_type == 'string' &&
            $field_type == FIELD_INT &&
            ctype_digit($value) &&
            is_numeric($value)
        ) {
            $value = (int) $value;
            $value_type = $field_type;
        /* distinguish GUIDs */
        } elseif ($value_type == 'string' &&
            $field_type == FIELD_GUID &&
            $ms->isValidGuidSyntax($value)
        ) {
            $value_type = FIELD_GUID;
        /* distinguish YESNO */
        } elseif ($value_type == 'string' &&
            $field_type == FIELD_YESNO &&
            in_array($value, array('yes', 'no', 'Y', 'N'))
        ) {
            $value_type = 'yesno';
        /* distinguish timestamps */
        } elseif ($value_type == 'string' &&
            $field_type == FIELD_TIMESTAMP
        ) {
            $value_type = 'timestamp';
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

        if (!call_user_func(array($this, $set_method), $value)) {
            static::raiseError(__CLASS__ ."::{$set_method}() returned false!", true);
            return;
        }
    }

    /* override PHP's __get() function */
    final public function __get($name)
    {
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

        if (!$this->hasVirtualField($name)) {
            return null;
        }

        if (($name = static::getFieldNamefromColumn($name)) === false) {
            static::raiseError(__CLASS__ .'::getFieldNameFromColumn() returned false!');
            return false;
        }

        $method_name = 'get'.ucwords(strtolower($name));

        if (!method_exists($this, $method_name) && is_callable(array($this, $method_name))) {
            return null;
        }

        if (($value = $this->$method_name()) === false) {
            return null;
        }

        return $value;
    }

    public function save()
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
            $this->id = $db->getid();
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
            static::column(FIELD_IDX),
            static::$model_table_name,
            static::column(FIELD_IDX),
            $this->id,
            $where_sql
        );

        $sth = $db->prepare($sql);

        if (!$sth) {
            static::raiseError(__METHOD__ ."(), unable to prepare query");
            return false;
        }

        if (!$db->execute($sth, $arr_values)) {
            static::raiseError(__METHOD__ ."(), unable to execute query");
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

    final public function getId()
    {
        if (!static::hasField(FIELD_IDX)) {
            static::raiseError(__METHOD__ .'(), model has no idx field!');
            return false;
        }

        if (!isset($this->model_values[FIELD_IDX])) {
            return false;
        }

        return $this->model_values[FIELD_IDX];
    }

    final public function getGuid()
    {
        if (!static::hasField(FIELD_GUID)) {
            static::raiseError(__METHOD__ .'(), model has no guid field!');
            return false;
        }

        if (!isset($this->model_values[FIELD_GUID])) {
            return false;
        }

        return $this->model_values[FIELD_GUID];
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
            $field_ary = array(
                'name' => $field,
                'value' => $this->model_values[$field],
                'privacy' => $sec,
            );
            $fields[$field] = $field_ary;
        }

        if (!$this->hasVirtualFields() || (isset($no_virtual) && $no_virtual)) {
            return $fields;
        }

        foreach ($this->model_virtual_fields as $field) {
            $field_ary = array(
                'name' => $field,
                'value' => $this->model_values[$field],
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

    final public function setField($field, $value)
    {
        if (!isset($field) || empty($field) || !is_string($field)) {
            static::raiseError(__METHOD__ .'(), $field parameter is invalid!');
            return false;
        }

        if (!static::hasField($field)) {
            static::raiseError(__METHOD__ .'(), invalid field specified!');
            return false;
        }

        $this->model_values[$field] = $value;
        return true;
    }

    public function getItemsKeys()
    {
        if (!static::isHavingItems()) {
            static::raiseError(__METHOD__ .'(), model '. __CLASS__ .' is not declared to have items!');
            return false;
        }

        if (!$this->hasItems()) {
            static::raiseError(__CLASS__ .'::hasItems() returned false!');
            return false;
        }

        return array_keys($this->model_items);
    }

    public function getItems($offset = null, $limit = null)
    {
        if (!static::isHavingItems()) {
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
            $limit
        );

        $result = array();

        foreach ($keys as $key) {
            if (($item = $this->getItem($key)) === false) {
                static::raiseError(__CLASS__ .'::getItem() returned false!');
                return false;
            }
            array_push($result, $item);
            continue;
        }

        if (!isset($result) || empty($result) || !is_array($result)) {
            static::raiseError(__METHOD__ .'(), no items retrieved!');
            return false;
        }

        return $result;
    }

    final public static function isHavingItems()
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
        if (!$called_class::isHavingItems()) {
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
        if (!static::isHavingItems()) {
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
        } elseif (is_object($item)) {
            if (!method_exists($item, 'getId') || !is_callable(array(&$item, 'getId'))) {
                static::raiseError(__METHOD__ .'(), item model '. get_class($item) .' has no getId() method!');
                return false;
            }
            if (!method_exists($item, 'getGuid') || !is_callable(array(&$item, 'getGuid'))) {
                static::raiseError(__METHOD__ .'(), item model '. get_class($item) .' has no getGuid() method!');
                return false;
            }
            if (($idx = $item->getId()) === false) {
                static::raiseError(get_class($item) .'::getId() returned false!');
                return false;
            }
        } else {
            static::raiseError(__METHOD__ .'(), $item type is not supported!');
            return false;
        }

        if (array_key_exists($idx, $this->model_items)) {
            static::raiseError(__METHOD__ ."(), item with key {$idx} does already exist!");
            return false;
        }

        $this->model_items[$idx] = $item;
        return true;
    }

    public function getItem($idx)
    {
        if (!isset($idx) || empty($idx) || (!is_string($idx) && !is_numeric($idx))) {
            static::raiseError(__METHOD__ .'(), $idx parameter is invalid!');
            return false;
        }

        if (!$this->hasItem($idx)) {
            static::raiseError(__CLASS__ .'::hasItem() returned false!');
            return false;
        }

        if (is_object($this->model_items[$idx])) {
            return $this->model_items[$idx];
        }

        if (!is_array($this->model_items[$idx])) {
            static::raiseError(__METHOD__ .'(), got an unsupported item!');
            return false;
        }

        $item = $this->model_items[$idx];

        try {
            $child_model_name = $item[FIELD_MODEL];
            unset($item[FIELD_MODEL]);
            $item = new $child_model_name($item);
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ ."(), failed to load {$child_model_name}!");
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

        if (!in_array($idx, array_keys($this->model_items))) {
            return false;
        }

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
                if (strtotime($value) === false) {
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

    public function flush()
    {
        if (!static::isHavingItems()) {
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
        if (!static::hasFieldSetMethod($field)) {
            static::raiseError(__CLASS__ .'::hasFieldSetMethod() returned false!');
            return false;
        }

        return static::$model_fields[$field][FIELD_SET];
    }

    protected function hasFieldGetMethod($field)
    {
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
        if (!static::hasFieldGetMethod($field)) {
            static::raiseError(__CLASS__ .'::hasFieldGetMethod() returned false!');
            return false;
        }

        return static::$model_fields[$field][FIELD_GET];
    }

    public function hasIdx()
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

    public function hasGuid()
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

    final public function hasFieldValue($field)
    {
        if (!static::hasFields()) {
            static::raiseError(__METHOD__ .'(), this model has no fields!');
            return false;
        }

        if (!static::hasField($field)) {
            static::raiseError(__METHOD__ .'(), this model has not that field!');
            return false;
        }

        if (!isset($this->model_values[$field]) ||
            empty($this->model_values[$field])
        ) {
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

        if (!static::hasField($field)) {
            static::raiseError(__METHOD__ .'(), this model has not that field!');
            return false;
        }

        if (!isset($value)) {
            $this->model_values[$field] = null;
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

        $this->model_values[$field] = $value;
        return true;
    }

    final public function getFieldValue($field)
    {
        if (!$this->hasFieldValue($field)) {
            static::raiseError(__CLASS__ .'::hasFieldValue() returned false!');
            return false;
        }

        return $this->model_values[$field];
    }

    final public function hasDefaultValue($field)
    {
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

    protected static function hasModelLinks()
    {
        if (!isset(static::$model_links) ||
            empty(static::$model_links)
        ) {
            return false;
        }

        return true;
    }

    protected function deleteModelLinks()
    {
        global $thallium;

        foreach (static::$model_links as $link) {
            list($model, $field) = explode('/', $link);

            if (($model_name = $thallium->getFullModelName($model)) === false) {
                static::raiseError(get_class($thallium) .'::getFullModelName() returned false!');
                return false;
            }

            try {
                $model = new $model_name(array(
                    $field => $this->getId()
                ));
            } catch (\Exception $e) {
                static::raiseError(__METHOD__ ."(), failed to load {$model_name}!");
                return false;
            }

            if (!$model->delete()) {
                static::raiseError(get_class($model) .'::delete() returned false!');
                return false;
            }
        }

        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
