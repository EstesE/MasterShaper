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

namespace MasterShaper\Controllers;

class MainController extends \Thallium\Controllers\MainController
{
    const VERSION = "1.0";

    private $ms_settings = array();

    public function __construct($mode = null)
    {
        if (!$this->setNamespacePrefix('MasterShaper')) {
            static::raiseError(__METHOD__ .'(), unable to set namespace prefix!', true);
            return;
        }

        try {
            $this->registerModel('chain', 'ChainModel');
            $this->registerModel('chains', 'ChainsModel');
            $this->registerModel('auditentry', 'AuditEntryModel');
            $this->registerModel('auditlog', 'AuditLogModel');
            $this->registerModel('filter', 'FilterModel');
            $this->registerModel('filters', 'FiltersModel');
            $this->registerModel('host_profile', 'HostProfileModel');
            $this->registerModel('host_task', 'HostTaskModel');
            $this->registerModel('network_interface', 'NetworkInterfaceModel');
            $this->registerModel('network_interfaces', 'NetworkInterfacesModel');
            $this->registerModel('network_path', 'NetworkPathModel');
            $this->registerModel('network_paths', 'NetworkPathsModel');
            $this->registerModel('pipe', 'PipeModel');
            $this->registerModel('pipes', 'PipesModel');
            $this->registerModel('port', 'PortModel');
            $this->registerModel('ports', 'PortsModel');
            $this->registerModel('protocol', 'ProtocolModel');
            $this->registerModel('protocols', 'ProtocolsModel');
            $this->registerModel('service_level', 'ServiceLevelModel');
            $this->registerModel('service_levels', 'ServiceLevelsModel');
            $this->registerModel('target', 'TargetModel');
            $this->registerModel('user', 'UserModel');
            $this->registerModel('atg', 'AssignTargetToGroupModel');
            $this->registerModel('atgs', 'AssignTargetToGroupsModel');
            $this->registerModel('apf', 'AssignPortToFilterModel');
            $this->registerModel('apfs', 'AssignPortToFiltersModel');
            $this->registerModel('apc', 'AssignPipeToChainModel');
            $this->registerModel('apcs', 'AssignPipeToChainsModel');
            $this->registerModel('afp', 'AssignFilterToPipeModel');
            $this->registerModel('afps', 'AssignFilterToPipesModel');
            $this->registerModel('setting', 'SettingModel');
            $this->registerModel('settings', 'SettingsModel');
            $this->registerModel('tcid', 'TcIdModel');
            $this->registerModel('tcids', 'TcIdsModel');
        } catch (\Exception $e) {
            static::raiseError(__CLASS__ .'::__construct(), error on registering models!"', true);
            return;
        }

        $GLOBALS['ms'] =& $this;
        parent::__construct();

        global $config;

        if (($timeout = $config->getScriptTimeout()) !== false) {
            set_time_limit($timeout);
        }

        if (!$this->loadSettings()) {
            static::raiseError(__CLASS__ .'::loadSettings() returned false!', true);
            return false;
        }

        return;
    }

    protected function loadSettings()
    {
        try {
            $settings = new \MasterShaper\Models\SettingsModel;
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to load SettingsModel!');
            return false;
        }

        if (!$settings->hasItems()) {
            return true;
        }

        if (($items = $settings->getItems()) === false) {
            static::raiseError(get_class($settings) .'::getItems() returned false!');
            return false;
        }

        if (!isset($items) || empty($items) || !is_array($items)) {
            return true;
        }

        foreach ($items as $item) {
            if (!$item->hasKey()) {
                continue;
            }

            if (($key = $item->getKey()) === false) {
                static::raiseError(get_class($item) .'::getKey() returned false!');
                return false;
            }

            $this->ms_settings[$key] = $item;
        }

        return true;
    }

    public function hasOption($option)
    {
        if (!array_key_exists($option, $this->ms_settings)) {
            return false;
        }

        return true;
    }

    public function getOption($option, $no_fail = false)
    {
        if (!$this->hasOption($option)) {
            if (!isset($no_fail) || !$no_fail) {
                static::raiseError(__CLASS__ .'::hasOption() returned false!');
            }
            return false;
        }

        if (!$this->ms_settings[$option]->hasValue()) {
            return null;
        }

        if (($value = $this->ms_settings[$option]->getValue()) === false) {
            static::raiseError(get_class($this->ms_settings[$option]) .'::getValue() returned false!');
            return false;
        }

        return $value;
    }

    public function checkPermissions($permission)
    {
        error_log("TODO: remove after Thallium migration!!");
        return true;

        global $db, $session;

        if (($user_idx = $session->getVariable('user_idx') === false)) {
            return false;
        }

        $user = $db->fetchSingleRow(
            "SELECT
                ". $permission ."
            FROM
                TABLEPREFIXusers
            WHERE
                user_idx='". $_SESSION['user_idx'] ."'"
        );

        if (isset($user) && isset($user->$permission) && $user->$permission == "Y") {
            return true;
        }

        return false;
    }

    public function getKbit($bw)
    {
        if (preg_match("/^(\d+)k$/i", $bw)) {
            return preg_replace("/k/i", "", $bw);
        }
        if (preg_match("/^(\d+)m$/i", $bw)) {
            return (intval(preg_replace("/m/i", "", $bw)) * 1024);
        }

        return $bw;

    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
