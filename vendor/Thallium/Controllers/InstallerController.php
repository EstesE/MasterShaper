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

namespace Thallium\Controllers;

class InstallerController extends DefaultController
{
    protected $schema_version_before;
    protected $framework_schema_version_before;

    public function setup()
    {
        global $db, $config;

        if ($db->checkTableExists("TABLEPREFIXmeta")) {
            if (($this->schema_version_before = $db->getApplicationDatabaseSchemaVersion()) === false) {
                static::raiseError(get_class($db) .'::getApplicationDatabaseSchemaVersion() returned false!');
                return false;
            }
            if (($this->framework_schema_version_before = $db->getFrameworkDatabaseSchemaVersion()) === false) {
                static::raiseError(get_class($db) .'::getFrameworkDatabaseSchemaVersion() returned false!');
                return false;
            }
        }

        if (!isset($this->schema_version_before)) {
            $this->schema_version_before = 0;
        }

        if (!isset($this->framework_schema_version_before)) {
            $this->framework_schema_version_before = 0;
        }

        if ($this->schema_version_before < $db->getApplicationSoftwareSchemaVersion() ||
            $this->framework_schema_version_before < $db->getFrameworkSoftwareSchemaVersion()
        ) {
            if (!$this->createDatabaseTables()) {
                static::raiseError(__CLASS__ .'::createDatabaseTables() returned false!');
                return false;
            }
        }

        if ($db->getApplicationDatabaseSchemaVersion() < $db->getApplicationSoftwareSchemaVersion() ||
            $db->getFrameworkDatabaseSchemaVersion() < $db->getFrameworkSoftwareSchemaVersion()
        ) {
            if (!$this->upgradeDatabaseSchema()) {
                static::raiseError(__CLASS__ .'::upgradeDatabaseSchema() returned false!');
                return false;
            }
        }

        if (!empty($this->schema_version_before)) {
            print "Application database schema version before upgrade: {$this->schema_version_before}<br />\n";
        }
        print "Application software supported schema version: {$db->getApplicationSoftwareSchemaVersion()}<br />\n";
        print "Application database schema version after upgrade: {$db->getApplicationDatabaseSchemaVersion()}<br />\n";
        print "<br /><br />";
        if (!empty($this->framework_schema_version_before)) {
            print "Framework database schema version before upgrade: {$this->framework_schema_version_before}<br />\n";
        }
        print "Framework software supported schema version: {$db->getFrameworkSoftwareSchemaVersion()}<br />\n";
        print "Framework database schema version after upgrade: {$db->getFrameworkDatabaseSchemaVersion()}<br />\n";

        if (!($base_path = $config->getWebPath())) {
            static::raiseError(get_class($config) .'"::getWebPath() returned false!');
            return false;
        }

        print "<a href='{$base_path}'>Return to application</a><br />\n";

        return true;
    }

    protected function createDatabaseTables()
    {
        if (!($this->createFrameworkDatabaseTables())) {
            static::raiseError(__CLASS__ .'::createFrameworkDatabaseTables() returned false!');
            return false;
        }

        if (!($this->createApplicationDatabaseTables())) {
            static::raiseError(__CLASS__ .'::createApplicationDatabaseTables() returned false!');
            return false;
        }

        return true;
    }

    final protected function createFrameworkDatabaseTables()
    {
        global $db;

        if (!$db->checkTableExists("TABLEPREFIXaudit")) {
            $table_sql = "CREATE TABLE `TABLEPREFIXaudit` (
                `audit_idx` int(11) NOT NULL AUTO_INCREMENT,
                `audit_guid` varchar(255) DEFAULT NULL,
                `audit_type` varchar(255) DEFAULT NULL,
                `audit_scene` varchar(255) DEFAULT NULL,
                `audit_object_guid` varchar(255) DEFAULT NULL,
                `audit_message` text,
                `audit_time` timestamp(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
                PRIMARY KEY (`audit_idx`)
                )
                ENGINE=InnoDB DEFAULT CHARSET=utf8;";

            if ($db->query($table_sql) === false) {
                static::raiseError(__METHOD__ .'(), failed to create "audit" table!');
                return false;
            }
        }

        if (!$db->checkTableExists("TABLEPREFIXmessage_bus")) {
            $table_sql = "CREATE TABLE `TABLEPREFIXmessage_bus` (
                `msg_idx` int(11) NOT NULL AUTO_INCREMENT,
                `msg_guid` varchar(255) DEFAULT NULL,
                `msg_session_id` varchar(255) NOT NULL,
                `msg_submit_time` timestamp(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
                `msg_scope` varchar(255) DEFAULT NULL,
                `msg_command` varchar(255) NOT NULL,
                `msg_body` varchar(4096) NOT NULL,
                `msg_value` varchar(255) DEFAULT NULL,
                `msg_in_processing` varchar(1) DEFAULT NULL,
                PRIMARY KEY (`msg_idx`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8";

            if ($db->query($table_sql) === false) {
                static::raiseError(__METHOD__ .'(), failed to create "message_bus" table!');
                return false;
            }
        }

        if (!$db->checkTableExists("TABLEPREFIXjobs")) {
            $table_sql = "CREATE TABLE `TABLEPREFIXjobs` (
                `job_idx` int(11) NOT NULL AUTO_INCREMENT,
                `job_guid` varchar(255) DEFAULT NULL,
                `job_command` varchar(255) NOT NULL,
                `job_parameters` varchar(4096) DEFAULT NULL,
                `job_session_id` varchar(255) NOT NULL,
                `job_request_guid` varchar(255) DEFAULT NULL,
                `job_time` timestamp(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
                `job_in_processing` varchar(1) DEFAULT NULL,
                PRIMARY KEY (`job_idx`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8";

            if ($db->query($table_sql) === false) {
                static::raiseError(__METHOD__ .'(), failed to create "jobs" table!');
                return false;
            }
        }

        if (!$db->checkTableExists("TABLEPREFIXmeta")) {
            $table_sql = "CREATE TABLE `TABLEPREFIXmeta` (
                `meta_idx` int(11) NOT NULL auto_increment,
                `meta_key` varchar(255) default NULL,
                `meta_value` varchar(255) default NULL,
                PRIMARY KEY  (`meta_idx`),
                UNIQUE KEY `meta_key` (`meta_key`)
                )
                ENGINE=MyISAM DEFAULT CHARSET=utf8;";

            if ($db->query($table_sql) === false) {
                static::raiseError(__METHOD__ .'(), failed to create "meta" table!');
                return false;
            }

            if (!$db->setDatabaseSchemaVersion()) {
                static::raiseError(get_class($db) .'::setDatabaseFrameworkSchemaVersion() returned false!');
                return false;
            }

            if (!$db->setDatabaseSchemaVersion(null, 'framework')) {
                static::raiseError(get_class($db) .'::setDatabaseFrameworkSchemaVersion() returned false!');
                return false;
            }
        }

        if (!$db->getApplicationDatabaseSchemaVersion()) {
            if (!$db->setDatabaseSchemaVersion()) {
                static::raiseError(get_class($db) .'::setDatabaseSchemaVersion() returned false!');
                return false;
            }
        }

        if (!$db->getFrameworkDatabaseSchemaVersion()) {
            if (!$db->setDatabaseSchemaVersion(null, 'framework')) {
                static::raiseError(get_class($db) .'::setDatabaseSchemaVersion() returned false!');
                return false;
            }
        }

        return true;
    }

    protected function createApplicationDatabaseTables()
    {
        /* this method should be overloaded to install application specific tables. */
        return true;
    }

    protected function upgradeDatabaseSchema()
    {
        global $db;

        if (!$this->upgradeApplicationDatabaseSchema()) {
            static::raiseError(__CLASS__ .'::upgradeApplicationDatabaseSchema() returned false!');
            return false;
        }

        if (!$this->upgradeFrameworkDatabaseSchema()) {
            static::raiseError(__CLASS__ .'::upgradeFrameworkDatabaseSchema() returned false!');
            return false;
        }

        return true;
    }

    protected function upgradeApplicationDatabaseSchema()
    {
        global $db;

        if (!$software_version = $db->getApplicationSoftwareSchemaVersion()) {
            static::raiseError(get_class($db) .'::getSoftwareSchemaVersion() returned false!');
            return false;
        }

        if ($software_version < 1) {
            static::raiseError(__METHOD__ .'(), invalid framework schema version found!');
            return false;
        }

        if (($db_version = $db->getApplicationDatabaseSchemaVersion()) === false) {
            static::raiseError(get_class($db) .'::getApplicationDatabaseSchemaVersion() returned false!');
            return false;
        }

        if ($db_version >= $software_version) {
            return true;
        }

        for ($i = $db_version+1; $i <= $software_version; $i++) {
            $method_name = "upgradeApplicationDatabaseSchemaV{$i}";

            if (!method_exists($this, $method_name)) {
                static::raiseError(__METHOD__ .'(), no upgrade method found for version '. $i);
                return false;
            } else {
                print "Invoking {$method_name}().<br />\n";
            }

            if (!$this->$method_name()) {
                static::raiseError(__CLASS__ ."::{$method_name}() returned false!");
                return false;
            }
        }

        return true;
    }

    final protected function upgradeFrameworkDatabaseSchema()
    {
        global $db;

        if (!$software_version = $db->getFrameworkSoftwareSchemaVersion()) {
            static::raiseError(get_class($db) .'::getFrameworkSoftwareSchemaVersion() returned false!');
            return false;
        }

        if ($software_version < 1) {
            static::raiseError(__METHOD__ .'(), invalid framework schema version found!');
            return false;
        }

        if (($db_version = $db->getFrameworkDatabaseSchemaVersion()) === false) {
            static::raiseError(get_class($db) .'::getFrameworkDatabaseSchemaVersion() returned false!');
            return false;
        }

        if ($db_version >= $software_version) {
            return true;
        }

        for ($i = $db_version+1; $i <= $software_version; $i++) {
            $method_name = "upgradeFrameworkDatabaseSchemaV{$i}";

            if (!method_exists($this, $method_name)) {
                static::raiseError(__METHOD__ .'(), no upgrade method found for version '. $i);
                return false;
            } else {
                print "Invoking {$method_name}().<br />\n";
            }

            if (!$this->$method_name()) {
                static::raiseError(__CLASS__ ."::{$method_name}() returned false!");
                return false;
            }
        }

        return true;
    }

    protected function upgradeFrameworkDatabaseSchemaV2()
    {
        global $db;

        if ($db->checkColumnExists('TABLEPREFIXjobs', 'job_command')) {
            $db->setDatabaseSchemaVersion(2, 'framework');
            return true;
        }

        $result = $db->query(
            "ALTER TABLE
                TABLEPREFIXjobs
            ADD COLUMN
                `job_command` varchar(255) NOT NULL
            AFTER
                job_guid,
            ADD COLUMN
                `job_parameters` varchar(255) DEFAULT NULL
            AFTER
                job_command"
        );

        if ($result === false) {
            static::raiseError(__METHOD__ ." failed!");
            return false;
        }

        $db->setDatabaseSchemaVersion(2, 'framework');
        return true;
    }

    protected function upgradeFrameworkDatabaseSchemaV3()
    {
        global $db;

        $result = $db->query(
            "ALTER TABLE
                TABLEPREFIXmessage_bus
            MODIFY COLUMN
                `msg_body` varchar(4096) DEFAULT NULL"
        );

        if ($result === false) {
            static::raiseError(__METHOD__ ." failed!");
            return false;
        }

        $result = $db->query(
            "ALTER TABLE
                TABLEPREFIXjobs
            MODIFY COLUMN
                `job_parameters` varchar(4096) DEFAULT NULL"
        );


        $db->setDatabaseSchemaVersion(3, 'framework');
        return true;
    }

    protected function upgradeFrameworkDatabaseSchemaV4()
    {
        global $db;

        $result = $db->query(
            "ALTER TABLE
                TABLEPREFIXaudit
            ADD
                `audit_object_guid` varchar(255) DEFAULT NULL
            AFTER
                audit_scene"
        );

        if ($result === false) {
            static::raiseError(__METHOD__ ." failed!");
            return false;
        }

        $db->setDatabaseSchemaVersion(4, 'framework');
        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
