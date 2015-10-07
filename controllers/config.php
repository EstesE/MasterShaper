<?php

/**
 *
 * This file is part of MasterShaper.

 * MasterShaper, a web application to handle Linux's traffic shaping
 * Copyright (C) 2015 Andreas Unterkircher <unki@netshadow.net>

 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.

 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.

 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace MasterShaper\Controllers;

class ConfigController extends DefaultController
{
    private $config_file_local = "config.ini";
    private $config_file_dist = "config.ini.dist";
    private $config;

    public function __construct()
    {
        global $ms;

        if (!file_exists($this::CONFIG_DIRECTORY)) {
            $ms->raiseError(
                "Configuration directory ". $this::CONFIG_DIRECTORY ." does not exist!"
            );
            return false;
        }

        if (!is_executable($this::CONFIG_DIRECTORY)) {
            $ms->raiseError(
                "Unable to enter config directory ". $this::CONFIG_DIRECTORY ." - please check permissions!"
            );
            return false;
        }

        if (!function_exists("parse_ini_file")) {
            $ms->raiseError(
                "This PHP installation does not provide required parse_ini_file() function!"
            );
            return false;
        }

        $config_pure = array();

        foreach (array('dist', 'local') as $config) {

            if (!($config_pure[$config] = $this->readConfig($config))) {
                $ms->raiseError("readConfig({$config}) returned false!");
                return false;
            }
        }

        if (
            !isset($config_pure['dist']) ||
            empty($config_pure['dist']) ||
            !is_array($config_pure['dist'])
        ) {
            $ms->raiseError("no valid config.ini.dist available!");
            return false;
        }

        if (!($this->config = array_replace_recursive($config_pure['dist'], $config_pure['local']))) {
            $ms->raiseError("Failed to merge {$this->config_file_local} with {$this->config_file_dist}.");
            return false;
        }

        return true;
    }

    private function readConfig($config_target)
    {
        global $ms;

        $config_file = "config_file_{$config_target}";
        $config_fqpn = $this::CONFIG_DIRECTORY ."/". $this->$config_file;

        // missing config.ini is ok
        if ($config_target == 'local' && !file_exists($config_fqpn)) {
            continue;
        }

        if (!file_exists($config_fqpn)) {
            $ms->raiseError("Configuration file {$config_fqpn} does not exist!");
            return false;
        }

        if (!is_readable($config_fqpn)) {
            $ms->raiseError(
                "Unable to read configuration file {$config_fqpn} - please check permissions!"
            );
            return false;
        }

        if (($config_ary = parse_ini_file($config_fqpn, true)) === false) {
            $ms->raiseError(
                "parse_ini_file() function failed on {$config_fqpn} - please check syntax!"
            );
            return false;
        }

        if (empty($config_ary) || !is_array($config_ary)) {
            $ms->raiseError(
                "Error - invalid configuration retrieved from {$config_fqpn} - please check syntax!"
            );
            exit(1);
        }

        if (!isset($config_ary['app']) || empty($config_ary['app']) || !array($config_ary['app'])) {
            $ms->raiseError("Mandatory config section [app] is not configured!");
            exit(1);
        }

        // remove trailing slash from base_web_path if any, but not if base_web_path = /
        if (
            isset($config_ary['app']['base_web_path']) &&
            !empty($config_ary['app']['base_web_path']) &&
            $config_ary['app']['base_web_path'] != '/'
        ) {

            $config_ary['app']['base_web_path'] = rtrim($config_ary['app']['base_web_path'], '/');
        }

        return $config_ary;
    }

    public function getDatabaseConfiguration()
    {
        if (
            !isset($this->config['database']) ||
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
        if (
            !isset($this->config['app']['base_web_path']) ||
            empty($this->config['app']['base_web_path']) ||
            !is_string($this->config['app']['base_web_path'])
        ) {
            return false;
        }

        return $this->config['app']['base_web_path'];
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
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
