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

namespace MasterShaper\Views;

class UsersView extends DefaultView
{
    protected static $view_default_mode = 'list';
    protected static $view_class_name = 'users';

    public function __construct()
    {
        try {
            $users = new \MasterShaper\Models\UsersModel;
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to load UsersModel!', true, $e);
            return;
        }

        if (!$this->setViewData($users)) {
            static::raiseError(__CLASS__ .'::setViewData() returned false!', true);
            return;
        }

        parent::__construct();
    }

    private function getPermissions($user_idx)
    {
        global $db;

        $string = "";

        if ($user = $db->db_fetchSingleRow("SELECT * FROM TABLEPREFIXusers WHERE user_idx='". $user_idx ."'")) {
            if ($user->user_manage_chains == "Y") {
                $string.= "Chains, ";
            }
            if ($user->user_manage_pipes == "Y") {
                $string.= "Pipes, ";
            }
            if ($user->user_manage_filters == "Y") {
                $string.= "Filters, ";
            }
            if ($user->user_manage_ports == "Y") {
                $string.= "Ports, ";
            }
            if ($user->user_manage_protocols == "Y") {
                $string.= "Protocols, ";
            }
            if ($user->user_manage_targets == "Y") {
                $string.= "Targets, ";
            }
            if ($user->user_manage_users == "Y") {
                $string.= "Users, ";
            }
            if ($user->user_manage_options == "Y") {
                $string.= "Options, ";
            }
            if ($user->user_manage_servicelevels == "Y") {
                $string.= "Service Levels, ";
            }
            if ($user->user_load_rules == "Y") {
                $string.= "Load Rules, ";
            }
            if ($user->user_show_rules == "Y") {
                $string.= "Show Rules, ";
            }
            if ($user->user_show_monitor == "Y") {
                $string.= "Show Monitoring, ";
            }
        }

        return substr($string, 0, strlen($string)-2);
    }

    public function showEdit($id, $guid)
    {
        global $tmpl;

        try {
            $item = new \MasterShaper\Models\UserModel(array(
                'idx' => $id,
                'guid' => $guid
            ));
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to load UserModel!', false, $e);
            return false;
        }

        $tmpl->assign('user', $item);
        return parent::showEdit($id, $guid);
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
