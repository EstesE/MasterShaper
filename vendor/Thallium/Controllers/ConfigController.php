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

class ConfigController extends DefaultController
{
    protected $config_file_local = "config.ini";
    protected $config_file_dist = "config.ini.dist";
    protected $config;

    public function __construct()
    {
        if (!file_exists(self::CONFIG_DIRECTORY)) {
            static::raiseError(
                __METHOD__ ."(), configuration directory ". self::CONFIG_DIRECTORY ." does not exist!",
                true
            );
            return false;
        }

        if (!is_executable(self::CONFIG_DIRECTORY)) {
            static::raiseError(
                __METHOD__ ."(), unable to enter config directory ". self::CONFIG_DIRECTORY ."!",
                true
            );
            return false;
        }

        if (!function_exists("parse_ini_file")) {
            static::raiseError(
                __METHOD__ .'(), PHP does not provide required parse_ini_file() function!',
                true
            );
            return false;
        }

        $config_pure = array();

        foreach (array('dist', 'local') as $config) {
            if (!($config_pure[$config] = $this->readConfig($config))) {
                static::raiseError(__METHOD__ ."(), readConfig({$config}) returned false!", true);
                return false;
            }
        }

        if (!isset($config_pure['dist']) ||
            empty($config_pure['dist']) ||
            !is_array($config_pure['dist'])
        ) {
            static::raiseError(__METHOD__ .'(), no valid config.ini.dist available!', true);
            return false;
        }

        if (!isset($config_pure['local']) ||
            !is_array($config_pure['local'])
        ) {
            $config_pure['local'] = array();
        }

        if (!($this->config = array_replace_recursive($config_pure['dist'], $config_pure['local']))) {
            static::raiseError(
                __METHOD__ ."(), failed to merge {$this->config_file_local} with {$this->config_file_dist}."
            );
            return false;
        }

        return true;
    }

    protected function readConfig($config_target)
    {
        $config_file = "config_file_{$config_target}";
        $config_fqpn = self::CONFIG_DIRECTORY ."/". $this->$config_file;

        // missing config.ini is ok
        if ($config_target == 'local' && !file_exists($config_fqpn)) {
            return true;
        }

        if (!file_exists($config_fqpn)) {
            static::raiseError(__METHOD__ ."(), configuration file {$config_fqpn} does not exist!", true);
            return false;
        }

        if (!is_readable($config_fqpn)) {
            static::raiseError(
                __METHOD__ ."(), unable to read configuration file {$config_fqpn}!",
                true
            );
            return false;
        }

        if (($config_ary = parse_ini_file($config_fqpn, true)) === false) {
            static::raiseError(
                __METHOD__ ."(), parse_ini_file() function failed on {$config_fqpn} - please check syntax!",
                true
            );
            return false;
        }

        if (empty($config_ary) || !is_array($config_ary)) {
            static::raiseError(
                __METHOD__ ."(), invalid configuration retrieved from {$config_fqpn} - please check syntax!",
                true
            );
            return false;
        }

        if (!isset($config_ary['app']) || empty($config_ary['app']) || !array($config_ary['app'])) {
            static::raiseError(__METHOD__.'(), mandatory config section [app] is not configured!', true);
            return false;
        }

        // remove trailing slash from base_web_path if any, but not if base_web_path = /
        if (isset($config_ary['app']['base_web_path']) &&
            !empty($config_ary['app']['base_web_path']) &&
            $config_ary['app']['base_web_path'] != '/'
        ) {
            $config_ary['app']['base_web_path'] = rtrim($config_ary['app']['base_web_path'], '/');
        }

        return $config_ary;
    }

    public function getDatabaseConfiguration()
    {
        if (!isset($this->config['database']) ||
            empty($this->config['database']) ||
            !is_array($this->config['database'])
        ) {
            return false;
        }

        return $this->config['database'];
    }

    public function getDatabaseType()
    {
        if ($dbconfig = $this->getDatabaseConfiguration()) {
            if (isset($dbconfig['type']) && !empty($dbconfig['type']) && is_string($dbconfig['type'])) {
                return $dbconfig['type'];
            }
        }

        return false;
    }

    public function getWebPath()
    {
        if (!isset($this->config['app']['base_web_path']) ||
            empty($this->config['app']['base_web_path']) ||
            !is_string($this->config['app']['base_web_path'])
        ) {
            return false;
        }

        return $this->config['app']['base_web_path'];
    }

    public function getPageTitle()
    {
        if (isset($this->config['app']['page_title']) &&
            !empty($this->config['app']['page_title']) &&
            is_string($this->config['app']['page_title'])
        ) {
            return $this->config['app']['page_title'];
        }

        return false;
    }

    public function isEnabled($value)
    {
        if (!in_array($value, array('yes','y','true','on','1'))) {
            return false;
        }

        return true;
    }

    public function isDisabled($value)
    {
        if (!in_array($value, array('no','n','false','off','0'))) {
            return false;
        }

        return true;
    }

    public function inMaintenanceMode()
    {
        if (!isset($this->config['app']['maintenance_mode']) ||
            empty($this->config['app']['maintenance_mode'])
        ) {
            return false;
        }

        if ($this->isDisabled($this->config['app']['maintenance_mode'])) {
            return false;
        }

        if ($this->isEnabled($this->config['app']['maintenance_mode'])) {
            return true;
        }

        static::raiseError(
            __METHOD__ .'(), configuration option "maintenance_mode" in [app] section is invalid!',
            true
        );
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
