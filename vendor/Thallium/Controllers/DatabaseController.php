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

namespace Thallium\Controllers;

use \PDO;

class DatabaseController extends DefaultController
{
    const SCHEMA_VERSION = 1;
    const FRAMEWORK_SCHEMA_VERSION = 3;

    protected $db;
    protected $db_cfg;
    protected $is_connected = false;
    protected $is_open_transaction = false;

    public function __construct()
    {
        global $config;

        $this->is_connected = false;

        if (!($dbconfig = $config->getDatabaseConfiguration())) {
            static::raiseError(
                "Database configuration is missing or incomplete"
                ." - please check configuration!",
                true
            );
            return false;
        }

        if (!isset(
            $dbconfig['type'],
            $dbconfig['host'],
            $dbconfig['db_name'],
            $dbconfig['db_user'],
            $dbconfig['db_pass']
        )) {
            $this->raiseErrror(
                "Incomplete database configuration - please check configuration!",
                true
            );
            return false;
        }

        $this->db_cfg = $dbconfig;

        if (!$this->connect()) {
            static::raiseError(__CLASS__ ."::connect() returned false!");
            return false;
        }

        if (!$this->checkDatabaseSoftwareVersion()) {
            static::raiseError(__CLASS__ ."::checkDatabaseSoftwareVersion() returned false!");
            return false;
        }

        return true;
    }

    protected function connect()
    {
        $options = array(
                'debug' => 2,
                'portability' => 'DB_PORTABILITY_ALL'
                );

        switch ($this->db_cfg['type']) {
            default:
            case 'mariadb':
            case 'mysql':
                $dsn = "mysql:dbname=". $this->db_cfg['db_name'] .";host=". $this->db_cfg['host'];
                $user = $this->db_cfg['db_user'];
                $pass = $this->db_cfg['db_pass'];
                break;
            case 'sqlite':
                $dsn = "sqlite:".$this->db_cfg['host'];
                $user = null;
                $pass = null;
                break;
        }

        try {
            $this->db = new \PDO($dsn, $user, $pass);
            $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $this->db->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_OBJ);
        } catch (\PDOException $e) {
            static::raiseError(__METHOD__ .'(), unable to connect to database!', true, $e);
            return false;
        }

        $this->SetConnectionStatus(true);
        return true;
    }

    protected function setConnectionStatus($status)
    {
        $this->is_connected = $status;
    }

    protected function getConnectionStatus()
    {
        return $this->is_connected;
    }

    public function query($query = "", $mode = \PDO::FETCH_OBJ)
    {
        if (!$this->getConnectionStatus()) {
            $this->connect();
        }

        if ($this->hasTablePrefix()) {
            $this->insertTablePrefix($query);
        }

        /* for manipulating queries use exec instead of query. can save
         * some resource because nothing has to be allocated for results.
         */
        if (preg_match('/^[[:blank:]]*(update|insert|create|replace|truncate|delete|alter)[[:blank:]]/i', $query)) {
            try {
                $result = $this->db->exec($query);
            } catch (\PDOException $e) {
                static::raiseError(__METHOD__ .'(), query failed!', false, $e);
            }

            /* PDO::exec() sometimes returns false even if operation was successful.
             * http://php.net/manual/de/pdo.exec.php#118156
             * so overrule fow now.
             */

            return true;
            if (!isset($result) || $result === false) {
                return false;
            }
            return $result;
        }

        try {
            $result = $this->db->query($query, $mode);
        } catch (\PDOException $e) {
            static::raiseError(__METHOD__ .'(), query failed!', false, $e);
        }

        if (!isset($result) || $result === false) {
            return false;
        }

        return $result;
    }

    public function prepare($query = "")
    {
        if (!$this->getConnectionStatus()) {
            static::raiseError(__CLASS__ .'::getConnectionStatus() returned false!');
            return false;
        }

        if ($this->hasTablePrefix()) {
            $this->insertTablePrefix($query);
        }

        try {
            $result = $this->db->prepare($query);
        } catch (\PDOException $e) {
            static::raiseError(__METHOD__ .'(), unable to prepare statement!', false, $e);
            return false;
        }

        return $result;

    } // db_prepare()

    public function execute($sth, $data = array())
    {
        if (!$this->getConnectionStatus()) {
            static::raiseError(__CLASS__ .'::getConnectionStatus() returned false!');
            return false;
        }

        if (!is_object($sth)) {
            return false;
        }

        if (get_class($sth) != "PDOStatement") {
            return false;
        }

        /* if empty array is provided, we have to unset the $data.
           otherwise an empty array may clear all previously done
           (bindParam(), bindValue(), ...) bindings.
        */
        if (!isset($data) || empty($data) || !is_array($data)) {
            $data = null;
        } else {
            foreach ($data as $key => $value) {
                $sth->bindParam(
                    ":{$key}",
                    $value
                );
            }
        }

        try {
            $result = $sth->execute($data);
        } catch (\PDOException $e) {
            static::raiseError(__METHOD__ .'(), unable to execute statement!', false, $e);
            return false;
        }

        return $result;

    } // execute()

    public function freeStatement($sth)
    {
        if (!is_object($sth)) {
            return false;
        }

        if (get_class($sth) != "PDOStatement") {
            return false;
        }

        try {
            $sth->closeCursor();
        } catch (Exception $e) {
            $sth = null;
            return false;
        }

        return true;

    } // freeStatement()

    public function fetchSingleRow($query = "", $mode = \PDO::FETCH_OBJ)
    {
        if (!$this->getConnectionStatus()) {
            static::raiseError(__CLASS__ .'::getConnectionStatus() returned false!');
            return false;
        }

        if (empty($query)) {
            return false;
        }

        if (($result = $this->query($query, $mode)) === false) {
            return false;
        }

        if ($result->rowCount() == 0) {
            return false;
        }

        try {
            $row = $result->fetch($mode);
        } catch (\PDOException $e) {
            static::raiseError(__METHOD__ .'(), unable to fetch from database!', false, $e);
            return false;
        }

        return $row;

    } // fetchSingleRow()

    public function hasTablePrefix()
    {
        if (isset($this->db_cfg['table_prefix']) &&
            !empty($this->db_cfg['table_prefix']) &&
            is_string($this->db_cfg['table_prefix'])
        ) {
            return true;
        }

        return false;
    }

    public function getTablePrefix()
    {
        if (!isset($this->db_cfg) || empty($this->db_cfg)) {
            return false;
        }

        if (!isset($this->db_cfg['table_prefix']) || empty($this->db_cfg['table_prefix'])) {
            return false;
        }

        return $this->db_cfg['table_prefix'];
    }

    public function insertTablePrefix(&$query)
    {
        $query = str_replace("TABLEPREFIX", $this->getTablePrefix(), $query);
    }

    public function getid()
    {
        if (!$this->getConnectionStatus()) {
            static::raiseError(__CLASS__ .'::getConnectionStatus() returned false!');
            return false;
        }

        try {
            $lastid = $this->db->lastInsertId();
        } catch (\PDOException $e) {
            static::raiseError(__METHOD__ .'(), unable to detect last inserted row ID!');
            return false;
        }

        /* Get the last primary key ID from execute query */
        return $lastid;
    }

    public function checkTableExists($table_name)
    {
        if (!$this->getConnectionStatus()) {
            static::raiseError(__CLASS__ .'::getConnectionStatus() returned false!');
            return false;
        }

        if (($tables = $this->getDatabaseTables()) === false) {
            static::raiseError(__CLASS__ .'::getDatabaseTables() returned false!');
            return false;
        }

        if ($this->hasTablePrefix()) {
            $table_name = str_replace("TABLEPREFIX", $this->getTablePrefix(), $table_name);
        }

        if (!in_array($table_name, $tables)) {
            return false;
        }

        return true;
    }

    public function getDatabaseTables()
    {
        $tables = array();

        if (!($result = $this->query("SHOW TABLES"))) {
            static::raiseError(__METHOD__ .'(), SHOW TABLES query failed!');
            return false;
        }

        if (!$result) {
            return $tables;
        }

        $tables_in = "Tables_in_{$this->db_cfg['db_name']}";

        while ($row = $result->fetch()) {
            array_push($tables, $row->$tables_in);
        }

        return $tables;
    }

    public function getApplicationDatabaseSchemaVersion()
    {
        if (!$this->getConnectionStatus()) {
            static::raiseError(__CLASS__ .'::getConnectionStatus() returned false!');
            return false;
        }

        if (!$this->checkTableExists("TABLEPREFIXmeta")) {
            return false;
        }

        $result = $this->fetchSingleRow(
            "SELECT
                meta_value
            FROM
                TABLEPREFIXmeta
            WHERE
                meta_key LIKE 'schema_version'"
        );

        if (isset($result->meta_value) && is_numeric($result->meta_value)) {
            return $result->meta_value;
        } elseif (isset($result->meta_value) && !is_numeric($result->meta_value)) {
            return false;
        }

        // in doubt we claim it's version 0
        return 0;
    }

    public function getFrameworkDatabaseSchemaVersion()
    {
        if (!$this->getConnectionStatus()) {
            static::raiseError(__CLASS__ .'::getConnectionStatus() returned false!');
            return false;
        }

        if (!$this->checkTableExists("TABLEPREFIXmeta")) {
            return false;
        }

        $result = $this->fetchSingleRow(
            "SELECT
                meta_value
            FROM
                TABLEPREFIXmeta
            WHERE
                meta_key LIKE 'framework_schema_version'"
        );

        if (isset($result->meta_value) && is_numeric($result->meta_value)) {
            return $result->meta_value;
        } elseif (isset($result->meta_value) && !is_numeric($result->meta_value)) {
            return false;
        }

        // in doubt we claim it's version 0
        return 0;
    }

    public function setDatabaseSchemaVersion($version = null, $mode = 'application')
    {
        if (!$this->checkTableExists("TABLEPREFIXmeta")) {
            static::raiseError(__METHOD__ .'(), can not set schema version as "meta" table does not exist!');
            return false;
        }

        if ($mode == 'application') {
            $key = 'schema_version';
        } elseif ($mode == 'framework') {
            $key = 'framework_schema_version';
        } else {
            static::raiseError(__METHOD__ .'(), unsupported $mode parameter!');
            return false;
        }

        if (!isset($version) || empty($version)) {
            if ($mode == 'application') {
                $version = $this->getApplicationSoftwareSchemaVersion();
            } elseif ($mode == 'framework') {
                $version = $this->getFrameworkSoftwareSchemaVersion();
            }
        }

        $result = $this->query(
            "REPLACE INTO TABLEPREFIXmeta (
                meta_key,
                meta_value
            ) VALUES (
                '{$key}',
                '{$version}'
            )"
        );

        if (!$result) {
            static::raiseError(__METHOD__ ."(), unable to set {$key} in meta table!");
            return false;
        }

        return true;
    }

    public function getApplicationSoftwareSchemaVersion()
    {
        return static::SCHEMA_VERSION;
    }

    public function getFrameworkSoftwareSchemaVersion()
    {
        return self::FRAMEWORK_SCHEMA_VERSION;
    }

    public function truncateDatabaseTables()
    {
        if (($tables = $this->getDatabaseTables()) === false) {
            static::raiseError(__CLASS__ .'::getDatabaseTables() returned false!');
            return false;
        }

        foreach ($tables as $table) {
            if (($this->query("TRUNCATE TABLE ${table}")) === false) {
                static::raiseError(__METHOD__ ."(), failed to truncate '{$table}' table!");
                return false;
            }
        }

        return true;
    }

    public function checkDatabaseSoftwareVersion()
    {
        if (!$version = $this->db->getAttribute(\PDO::ATTR_SERVER_VERSION)) {
            static::raiseError(__METHOD__ .'(), failed to detect database software version!');
            return false;
        }

        if (!isset($version) || empty($version)) {
            static::raiseError(__METHOD__ .'(), unable to fetch version information from database!');
            return false;
        }

        // extract the pure version without extra build specifics
        if (($version = preg_replace("/^(\d+)\.(\d+)\.(\d+).*$/", '${1}.${2}.${3}', $version)) === false) {
            static::raiseError(__METHOD__ ."(), failed to parse version string (${version})!");
            return false;
        }

        if (strtolower($this->db_cfg['type']) == "mysql" && version_compare($version, "5.6.4", "<")) {
            static::raiseError(__METHOD__ ."(), MySQL server version 5.6.4 or later is required (found {$version})!");
            return false;
        }

        return true;
    }

    public function quote($text)
    {
        if (!method_exists($this->db, 'quote')) {
            static::raiseError(__METHOD__ .'(), PDO driver does not provide quote method!');
            return false;
        }

        if (!is_string($text)) {
            static::raiseError(__METHOD__ .'(), \$text is not a string!');
            return false;
        }

        if (($quoted = $this->db->quote($text)) === false) {
            static::raiseError(__METHOD__ .'(), PDO driver does not support quote!');
            return false;
        }

        if (!empty($text) && empty($quoted)) {
            static::raiseError(__METHOD__ .'(), something must have gone wrong!');
            return false;
        }

        return $text;
    }

    public function checkColumnExists($table_name, $column)
    {
        if (!$this->getConnectionStatus()) {
            static::raiseError(__CLASS__ .'::getConnectionStatus() returned false!');
            return false;
        }

        if (!isset($table_name) || empty($table_name) ||
            !isset($column) || empty($column)
        ) {
            static::raiseError(__METHOD__ .'(), incomplete parameters!');
            return false;
        }

        if (!($result = $this->query("DESC ". $table_name, \PDO::FETCH_NUM))) {
            static::raiseError(__METHOD__ .'(), failed to fetch table structure!');
            return false;
        }

        while ($row = $result->fetch()) {
            if (in_array($column, $row)) {
                return true;
            }
        }

        return false;
    }

    public function buildQuery(
        $type,
        $table_name,
        $query_columns = "*",
        $query_data = array(),
        &$bind_params = array(),
        $extend_where_query = null
    ) {
        if (!isset($type) || empty($type) || !is_string($type)) {
            static::raiseError(__METHOD__ .'(), $type parameter is invalid!');
            return false;
        }

        if (!isset($table_name) || empty($table_name) || !is_string($table_name)) {
            static::raiseError(__METHOD__ .'(), $table_name parameter is invalid!');
            return false;
        }

        if (!isset($query_columns) || (!is_array($query_columns) && !is_string($query_columns))) {
            static::raiseError(__METHOD__ .'(), $query_columns parameter is invalid!');
            return false;
        }

        if (!isset($query_data) || !is_array($query_data)) {
            static::raiseError(__METHOD__ .'(), $query_data parameter is invalid!');
            return false;
        }

        if (!isset($bind_params) || !is_array($bind_params)) {
            static::raiseError(__METHOD__ .'(), $bind_params parameter is invalid!');
            return false;
        }

        if (isset($extend_where_query) &&
            !empty($extend_where_query) &&
            !is_string($extend_where_query)
        ) {
            static::raiseError(__METHOD__ .'(), $extend_where_query is invalid!');
            return false;
        }

        if (is_string($query_columns)) {
            $query_columns_str = $query_columns;
        } elseif (is_array($query_columns)) {
            if (count($query_columns) < 1) {
                $query_columns_str = "*";
            } else {
                $columns = array();
                foreach ($query_columns as $key => $value) {
                    $columns[] = $value;
                }
                if (($query_columns_str = implode(', ', $columns)) === false) {
                    static::raiseError(__METHOD__ .'(), implode() returned false!');
                    return false;
                }
            }
        } else {
            static::raiseError(__METHOD__ .'(), $query_columns parameter has an unsupported type!');
            return false;
        }

        if (is_string($query_data)) {
            if (empty($query_data)) {
                return sprintf(
                    "%s %s FROM %s %s",
                    $type,
                    $query_columns_str,
                    $table_name,
                    !empty($extend_where_query) ? "WHERE {$extend_where_query}" : null
                );
            } else {
                return sprintf(
                    "%s %s FROM %s WHERE %s %s",
                    $type,
                    $query_columns_str,
                    $table_names,
                    $query_data,
                    $extend_where_query
                );
            }
        } elseif (is_array($query_data) && count($query_data) < 1) {
            return sprintf(
                "%s %s FROM %s %s",
                $type,
                $query_columns_str,
                $table_name,
                !empty($extend_where_query) ? "WHERE {$extend_where_query}" : null
            );
            return $sql;
        }

        $query_where_str = '';
        $wheres = array();

        foreach ($query_data as $key => $value) {
            $value_key = sprintf("v_%s", $key);
            $wheres[] = sprintf("%s LIKE :%s", $key, $value_key);
            $bind_params[$value_key] = $value;
        }
        if (($query_wheres_str = implode(' AND ', $wheres)) === false) {
            static::raiseError(__METHOD__ .'(), implode() returned false!');
            return false;
        }

        $sql = sprintf(
            "%s %s FROM %s WHERE %s %s",
            $type,
            $query_columns_str,
            $table_name,
            $query_wheres_str,
            $extend_where_query
        );

        return $sql;
    }

    public function getColumns($table_name)
    {
        if (!$this->getConnectionStatus()) {
            static::raiseError(__CLASS__ .'::getConnectionStatus() returned false!');
            return false;
        }

        if (!($result = $this->query("DESC ". $table_name, \PDO::FETCH_NUM))) {
            static::raiseError(__METHOD__ .'(), failed to fetch table structure!');
            return false;
        }

        return $result->fetchAll();
    }

    public function newTransaction()
    {
        if (!$this->getConnectionStatus()) {
            static::raiseError(__CLASS__ .'::getConnectionStatus() returned false!');
            return false;
        }

        if (isset($this->is_open_transaction) and $this->is_open_transaction === true) {
            static::raiseError(__METHOD__ .'(), there is already an ongoing transaction!');
            return false;
        }

        try {
            $this->db->beginTransaction();
        } catch (\PDOException $e) {
            static::raiseError(get_class($this->db) .'::beginTransaction() failed!', false, $e);
            return false;
        }

        $this->is_open_transaction = true;
        return true;
    }

    public function closeTransaction()
    {
        if (!isset($this->is_open_transaction) or $this->is_open_transaction !== true) {
            return true;
        }

        try {
            $this->db->commit();
        } catch (\PDOException $e) {
            static::raiseError(get_class($this->db) .'::commit() failed!', false, $e);
            return false;
        }

        $this->is_open_transaction = false;
        return true;
    }

    public function getDatabaseName()
    {
        return $this->db_cfg['db_name'];
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
