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

class OptionsView extends DefaultView
{
    protected static $view_default_mode = 'show';
    protected static $view_class_name = 'options';
    private $service_levels;
    private $service_levels_keys;
    private $settings;

    public function show()
    {
        global $ms, $db, $tmpl;

        try {
            $this->settings = new \MasterShaper\Models\SettingsModel;
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to load SettingsModel!');
            return false;
        }

        try {
            $service_levels = new \MasterShaper\Models\ServiceLevelsModel;
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to load ЅerviceLevelsModel!');
            return false;
        }

        if ($service_levels->hasItems() && ($this->service_levels = $service_levels->getItems()) === false) {
            static::raiseError(get_class($service_levels) .'::getItems() returned false!');
            return false;
        }

        if ($service_levels->hasItems() && ($this->service_levels_keys = $service_levels->getItemsKeys()) === false) {
            static::raiseError(get_class($service_levels) .'::getItemsKeys() returned false');
            return false;
        }

        $tmpl->assign('settings', $this->settings);

        $tmpl->registerPlugin(
            "block",
            "service_level_list",
            array(&$this, "smartyOptionsServiceLevelList")
        );
        return $tmpl->fetch("options.tpl");

    } // show()

    /* remove existing configuration */
    public function resetConfig($doit = 0)
    {

        /* If authentication is enabled, check permissions */
        if ($ms->getOption("authentication") == "Y" &&
            !$ms->checkPermissions("user_manage_options")
        ) {
            $ms->printError(
                "<img src=\"". ICON_OPTIONS ."\" alt=\"options icon\" />&nbsp;" . _("Manage Options"),
                _("You do not have enough permissions to access this module!")
            );
            return 0;
        }

        if (!isset($_GET['doit']) && !$doit) {
            $ms->printYesNo(
                "<img src=\"". ICON_OPTIONS ."\" alt=\"option icon\" />&nbsp;"
                . _("Reset MasterShaper Configuration"),
                _("This operation will completely reset your current MasterShaper configuration!<br />"
                ."All your current settings, rules, chains, pipes, ... will be deleted !!!<br /><br />"
                ."Of course this will also reset the version information of MasterShaper, you will be<br />"
                ."forwarded to MasterShaper Installer after you have confirmed this procedure.")
            );
        } else {
            $db->db_truncate_table("TABLEPREFIXassign_ports_to_filters");
            $db->db_truncate_table("TABLEPREFIXassign_filters_to_pipes");
            $db->db_truncate_table("TABLEPREFIXassign_targets_to_targets");
            $db->db_truncate_table("TABLEPREFIXchains");
            $db->db_truncate_table("TABLEPREFIXpipes");
            $db->db_truncate_table("TABLEPREFIXservice_levels");
            $db->db_truncate_table("TABLEPREFIXfilters");
            $db->db_truncate_table("TABLEPREFIXsettings");
            $db->db_truncate_table("TABLEPREFIXstats");
            $db->db_truncate_table("TABLEPREFIXtargets");
            $db->db_truncate_table("TABLEPREFIXtc_ids");
            $db->db_truncate_table("TABLEPREFIXusers");
            $db->db_truncate_table("TABLEPREFIXinterfaces");
            $db->db_truncate_table("TABLEPREFIXnetwork_paths");
            $db->query("DELETE FROM TABLEPREFIXports WHERE port_user_defined='Y'");
            $db->query("DELETE FROM TABLEPREFIXprotocols WHERE proto_user_defined='Y'");

            /* If invoked by "Reset Configuration" and not "Restore Configuration" */
            if (isset($_GET['doit'])) {
                $ms->goBack();
            }
        }

    } // resetConfig()

    public function smartyOptionsServiceLevelList($params, $content, &$smarty, &$repeat)
    {
        $index = $smarty->getTemplateVars('smarty.IB.sl_list.index');
        if (!$index) {
            $index = 0;
        }

        if ($index >= count($this->service_levels_keys)) {
            $repeat = false;
            return $content;
        }

        $key = $this->service_levels_keys[$index];

        if (!isset($this->service_levels[$key])) {
            $repeat = false;
            return $content;
        }

        $smarty->assign('sl', $this->service_levels[$key]);

        $index++;
        $smarty->assign('smarty.IB.sl_list.index', $index);
        $repeat = true;
        return $content;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
