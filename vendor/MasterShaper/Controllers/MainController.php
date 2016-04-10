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

    public function __construct($mode = null)
    {
        if (!$this->setNamespacePrefix('MasterShaper')) {
            $this->raiseError(__METHOD__ .'(), unable to set namespace prefix!', true);
            return false;
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
            $this->registerModel('protocol', 'ProtocolModel');
            $this->registerModel('service_level', 'ServiceLevelModel');
            $this->registerModel('service_levels', 'ServiceLevelsModel');
            $this->registerModel('target', 'TargetModel');
            $this->registerModel('user', 'UserModel');
            $this->registerModel('atg', 'AssignTargetToGroupModel');
            $this->registerModel('atgs', 'AssignTargetToGroupsModel');
            $this->registerModel('apf', 'AssignPortToFilterModel');
            $this->registerModel('apfs', 'AssignPortToFiltersModel');
        } catch (\Exception $e) {
            $this->raiseError(__CLASS__ .'::__construct(), error on registering models!"', true);
            return false;
        }

        $GLOBALS['ms'] =& $this;

        parent::__construct();
        return;
    }

    public function getOption($object)
    {
        global $db;

        $result = $db->fetchSingleRow(
            "SELECT
                setting_value
            FROM
                TABLEPREFIXsettings
            WHERE
                setting_key LIKE '". $object ."'"
        );

        if (isset($result->setting_value)) {
            return $result->setting_value;
        }

        /* return default options if not set yet */
        if ($object == "filter") {
            return "HTB";
        }

        if ($object == "msmode") {
            return "router";
        }

        if ($object == "authentication") {
            return "Y";
        }

        return "unknown";

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
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
