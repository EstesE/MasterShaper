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

use \PDO;

class RequirementsController extends DefaultController
{
    public function __construct()
    {
        if (!constant('APP_BASE')) {
            static::raiseError(__METHOD__ .'(), APP_BASE is not defined!', true);
            return false;
        }
    }

    public function check()
    {
        $missing = false;

        if (!$this->checkPhp()) {
            $missing = true;
        }

        if (!$this->checkDatabaseSupport()) {
            $missing = true;
        }

        if (!$this->checkExternalLibraries()) {
            $missing = true;
        }

        if (!$this->checkDirectoryPermissions()) {
            $missing = true;
        }

        if ($missing) {
            return false;
        }

        return true;
    }

    public function checkPhp()
    {
        global $config;

        $missing = false;

        if (!(function_exists("microtime"))) {
            static::raiseError("microtime() function does not exist!");
            $missing = true;
        }

        if ($missing) {
            return false;
        }

        return true;
    }

    public function checkDatabaseSupport()
    {
        global $config;

        $missing = false;

        if (!($dbtype = $config->getDatabaseType())) {
            static::raiseError("Error - incomplete configuration found, can not check requirements!");
            return false;
        }

        switch ($dbtype) {
            case 'mariadb':
            case 'mysql':
                $db_class_name = "mysqli";
                $db_pdo_name = "mysql";
                break;
            case 'sqlite3':
                $db_class_name = "Sqlite3";
                $db_pdo_name = "sqlite";
                break;
            default:
                $db_class_name = null;
                $db_pdo_name = null;
                break;
        }

        if (!$db_class_name) {
            $this->write("Error - unsupported database configuration, can not check requirements!", LOG_ERR);
            $missing = true;
        }

        if (!class_exists($db_class_name)) {
            $this->write("PHP {$dbtype} extension is missing!", LOG_ERR);
            $missing = true;
        }

        // check for PDO database support support
        if ((array_search($db_pdo_name, PDO::getAvailableDrivers())) === false) {
            $this->write("PDO {$db_pdo_name} support not available", LOG_ERR);
            $missing = true;
        }

        if ($missing) {
            return false;
        }

        return true;
    }

    public function checkExternalLibraries()
    {
        global $config;

        $missing = false;

        ini_set('track_errors', 1);

        /*@include_once 'Pager.php';
        if (isset($php_errormsg) && preg_match('/Failed opening.*for inclusion/i', $php_errormsg)) {
            print "PEAR Pager package is missing<br />\n";
            $missing = true;
            unset($php_errormsg);
        }*/
        @include_once 'smarty3/Smarty.class.php';
        if (isset($php_errormsg) && preg_match('/Failed opening.*for inclusion/i', $php_errormsg)) {
            $this->write("Smarty3 template engine is missing!", LOG_ERR);
            $missing = true;
            unset($php_errormsg);
        }

        ini_restore('track_errors');

        if ($missing) {
            return false;
        }

        return true;
    }

    public function checkDirectoryPermissions()
    {
        global $thallium;
        $missing = false;

        if (!$uid = $thallium->getProcessUserId()) {
            static::raiseError(get_class($thallium) .'::getProcessUserId() returned false!');
            return false;
        }

        if (!$gid = $thallium->getProcessGroupId()) {
            static::raiseError(get_class($thallium) .'::getProcessGroupId() returned false!');
            return false;
        }

        $directories = array(
            self::CONFIG_DIRECTORY => 'r',
            self::CACHE_DIRECTORY => 'w',
        );

        foreach ($directories as $dir => $perm) {
            if (!file_exists($dir) && !mkdir($dir, 0700)) {
                $this->write("failed to create {$dir} directory!", LOG_ERR);
                $missing = true;
                continue;
            }

            if (file_exists($dir) && !is_readable($dir)) {
                $this->write("{$dir} is not readable for {$uid}:{$gid}!", LOG_ERR);
                $missing = true;
                continue;
            }

            if (file_exists($dir) && $perm == 'w' && !is_writeable($dir)) {
                $this->write("{$dir} is not writeable for {$uid}:{$gid}!", LOG_ERR);
                $missing = true;
                continue;
            }
        }

        if ($missing) {
            return false;
        }

        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
