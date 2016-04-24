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

abstract class DefaultController
{
    const CONFIG_DIRECTORY = APP_BASE ."/config";
    const CACHE_DIRECTORY = APP_BASE ."/cache";
    const LOG_LEVEL = LOG_WARNING;

    public function __construct()
    {
        return;
    }

    final public function __set($name, $value)
    {
        global $thallium;

        if (!isset($thallium::$permit_undeclared_class_properties)) {
            static::raiseError(__METHOD__ ."(), trying to set an undeclared property {$name}!", true);
            return;
        }

        $this->$name = $value;
        return;
    }

    final public function sendMessage($command, $body, $value = null)
    {
        global $mbus;

        if (!isset($command) || empty($command) || !is_string($command)) {
            static::raiseError(__METHOD__ .', parameter \$command is mandatory and has to be a string!');
            return false;
        }
        if (!isset($body) || empty($body) || !is_string($body)) {
            static::raiseError(__METHOD__ .', parameter \$body is mandatory and has to be a string!');
            return false;
        }

        if (isset($value) && !empty($value) && !is_string($value)) {
            static::raiseError(__METHOD__ .', parameter \$value has to be a string!');
            return false;
        }

        if (!$mbus->sendMessageToClient($command, $body, $value)) {
            static::raiseError(get_class($mbus) .'::sendMessageToClient() returned false!');
            return false;
        }

        return true;
    }

    public static function raiseError($text, $stop_execution = false, $catched_exception = null)
    {
        if (defined('DB_NOERROR')) {
            return;
        }

        try {
            throw new ExceptionController($text, $catched_exception);
        } catch (ExceptionController $e) {
            print $e;
        }

        if ($stop_execution) {
            exit(1);
        }

        return true;
    }

    public function write($logtext, $loglevel = LOG_INFO, $override_output = null, $no_newline = null)
    {
        if (isset($this->config->logging)) {
            $logtype = $this->config->logging;
        } else {
            $logtype = 'display';
        }

        if (isset($override_output) || !empty($override_output)) {
            $logtype = $override_output;
        }

        if ($loglevel > $this->getVerbosity()) {
            return true;
        }

        switch ($logtype) {
            default:
            case 'display':
                print $logtext;
                if (!$this->isCmdline()) {
                    print "<br />";
                } elseif (!isset($no_newline)) {
                    print "\n";
                }
                break;
            case 'errorlog':
                error_log($logtext);
                break;
            case 'logfile':
                error_log($logtext, 3, $this->config->log_file);
                break;
        }

        return true;

    } // write()

    public function isCmdline()
    {
        if (php_sapi_name() == 'cli') {
            return true;
        }

        return false;

    } // isCmdline()

    public function getVerbosity()
    {
        return self::LOG_LEVEL;

    } // getVerbosity()

    public function requireModel($obj, $model)
    {
        global $thallium;

        if (!isset($obj) || empty($obj) || !is_object($obj) ||
            !isset($model) || empty($model) || !is_string($model)) {
            static::raiseError(__METHOD__ .'(), parameters are invalid!');
            return false;
        }

        if (!($prefix = $thallium->getNamespacePrefix())) {
            static::raiseError(get_class($thallium) .'::getNamespacePrefix() returned false!');
            return false;
        }

        $model_full = $prefix .'\\Models\\'. $model;

        if (get_class($obj) != $model_full) {
            return false;
        }

        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
