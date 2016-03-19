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

class TemplatesController extends \Thallium\Controllers\TemplatesController
{
    public $supported_modes = array (
            'list',
            'show',
            'edit',
            'delete',
            'add',
            'upload',
            'truncate',
            );
    public $default_mode = "list";

    public function __construct()
    {
        global $config;

        parent::__construct();

        if (($base_web_path = $config->getWebPath()) === false) {
            $this->raiseError(get_class($config) .'::getWebPath() returned false!');
            return false;
        }

        $this->assign('icon_chains', $base_web_path .'/resources/icons/flag_blue.gif');
        $this->assign('icon_chains_assign_pipe', $base_web_path .'/resources/icons/flag_blue_with_purple_arrow.gif');
        $this->assign('icon_options', $base_web_path .'/resources/icons/options.gif');
        $this->assign('icon_pipes', $base_web_path .'/resources/icons/flag_pink.gif');
        $this->assign('icon_ports', $base_web_path .'/resources/icons/flag_orange.gif');
        $this->assign('icon_protocols', $base_web_path .'/resources/icons/flag_red.gif');
        $this->assign('icon_servicelevels', $base_web_path .'/resources/icons/flag_yellow.gif');
        $this->assign('icon_filters', $base_web_path .'/resources/icons/flag_green.gif');
        $this->assign('icon_targets', $base_web_path .'/resources/icons/flag_purple.gif');
        $this->assign('icon_clone', $base_web_path .'/resources/icons/clone.png');
        $this->assign('icon_delete', $base_web_path .'/resources/icons/delete.png');
        $this->assign('icon_active', $base_web_path .'/resources/icons/active.gif');
        $this->assign('icon_inactive', $base_web_path .'/resources/icons/inactive.gif');
        $this->assign('icon_arrow_left', $base_web_path .'/resources/icons/arrow_left.gif');
        $this->assign('icon_arrow_right', $base_web_path .'/resources/icons/arrow_right.gif');
        $this->assign('icon_chains_arrow_up', $base_web_path .'/resources/icons/ms_chains_arrow_up_14.gif');
        $this->assign('icon_chains_arrow_down', $base_web_path .'/resources/icons/ms_chains_arrow_down_14.gif');
        $this->assign('icon_pipes_arrow_up', $base_web_path .'/resources/icons/ms_pipes_arrow_up_14.gif');
        $this->assign('icon_pipes_arrow_down', $base_web_path .'/resources/icons/ms_pipes_arrow_down_14.gif');
        $this->assign('icon_users', $base_web_path .'/resources/icons/ms_users_14.gif');
        $this->assign('icon_about', $base_web_path .'/resources/icons/home.gif');
        $this->assign('icon_home', $base_web_path .'/resources/icons/home.gif');
        $this->assign('icon_new', $base_web_path .'/resources/icons/page_white.gif');
        $this->assign('icon_monitor', $base_web_path .'/resources/icons/chart_pie.gif');
        $this->assign('icon_shaper_start', $base_web_path .'/resources/icons/enable.gif');
        $this->assign('icon_shaper_stop', $base_web_path .'/resources/icons/disable.gif');
        $this->assign('icon_bandwidth', $base_web_path .'/resources/icons/bandwidth.gif');
        $this->assign('icon_update', $base_web_path .'/resources/icons/update.gif');
        $this->assign('icon_interfaces', $base_web_path .'/resources/icons/network_card.gif');
        $this->assign('icon_hosts', $base_web_path .'/resources/icons/host.png');
        $this->assign('icon_treeend', $base_web_path .'/resources/icons/tree_end.gif');
        $this->assign('icon_rules_show', $base_web_path .'/resources/icons/show.gif');
        $this->assign('icon_rules_load', $base_web_path .'/resources/icons/enable.gif');
        $this->assign('icon_rules_unload', $base_web_path .'/resources/icons/disable.gif');
        $this->assign('icon_rules_export', $base_web_path .'/resources/icons/disk.gif');
        $this->assign('icon_rules_restore', $base_web_path .'/resources/icons/restore.gif');
        $this->assign('icon_rules_reset', $base_web_path .'/resources/icons/reset.gif');
        $this->assign('icon_rules_update', $base_web_path .'/resources/icons/update.gif');
        $this->assign('icon_pdf', $base_web_path .'/resources/icons/page_white_acrobat.gif');
        $this->assign('icon_menu_down', $base_web_path .'/resources/icons/bullet_arrow_down.png');
        $this->assign('icon_menu_right', $base_web_path .'/resources/icons/bullet_arrow_right.png');
        $this->assign('icon_busy', $base_web_path .'/resources/icons/busy.png');
        $this->assign('icon_ready', $base_web_path .'/resources/icons/ready.png');
        $this->assign('icon_process', $base_web_path .'/resources/icons/task.png');
        $this->assign('web_path', $base_web_path);

        $this->registerPlugin("function", "start_table", array(&$this, "smartyStartTable"), false);
        $this->registerPlugin("function", "page_end", array(&$this, "smartyPageEnd"), false);
        $this->registerPlugin("function", "year_select", array(&$this, "smartyYearSelect"), false);
        $this->registerPlugin("function", "month_select", array(&$this, "smartyMonthSelect"), false);
        $this->registerPlugin("function", "day_select", array(&$this, "smartyDaySelect"), false);
        $this->registerPlugin("function", "hour_select", array(&$this, "smartyHourSelect"), false);
        $this->registerPlugin("function", "minute_select", array(&$this, "smartyMinuteSelect"), false);
        $this->registerPlugin("function", "chain_select_list", array(&$this, "smartyChainSelectList"), false);
        $this->registerPlugin("function", "pipe_select_list", array(&$this, "smartyPipeSelectList"), false);
        $this->registerPlugin("function", "target_select_list", array(&$this, "smartyTargetSelectList"), false);
        $this->registerPlugin(
            "function",
            "service_level_select_list",
            array(&$this, "smartyServiceLevelSelectList"),
            false
        );
        $this->registerPlugin(
            "function",
            "network_path_select_list",
            array(&$this, "smartyNetworkPathSelectList"),
            false
        );
        $this->registerPlugin(
            "function",
            "host_profile_select_list",
            array(&$this, "smartyHostProfileSelectList"),
            false
        );
        $this->registerPlugin("function", "get_item_name", array(&$this, "smartyGetItemName"), false);
        $this->registerPlugin("function", "get_menu_state", array(&$this, "getMenuState"), false);

        return true;
    }

    public function smartyStartTable($params, &$smarty)
    {
        $this->assign('title', $params['title']);
        $this->assign('icon', $params['icon']);
        $this->assign('alt', $params['alt']);
        $this->fetch('start_table.tpl');
    }

    public function smartyPageEnd($params, &$smarty)
    {
        if (isset($params['focus_to'])) {
            $this->assign('focus_to', $params['focus_to']);
        }

        return $this->fetch('page_end.tpl');
    }

    public function smartyYearSelect($params, &$smarty)
    {
        global $ms;
        print $ms->getYearList($params['current']);
    }

    public function smartyMonthSelect($params, &$smarty)
    {
        global $ms;
        print $ms->getMonthList($params['current']);
    }

    public function smartyDaySelect($params, &$smarty)
    {
        global $ms;
        print $ms->getDayList($params['current']);
    }

    public function smartyHourSelect($params, &$smarty)
    {
        global $ms;
        print $ms->getHourList($params['current']);
    }

    public function smartyMinuteSelect($params, &$smarty)
    {
        global $ms;
        print $ms->getMinuteList($params['current']);
    }

    public function smartyChainSelectList($params, &$smarty)
    {
        global $db;

        if (!array_key_exists('chain_idx', $params)) {
            $this->trigger_error("smarty_chain_select_list: missing 'chain_idx' parameter", E_USER_WARNING);
            $repeat = false;
            return;
        }

        $result = $db->query("
                SELECT
                *
                FROM
                TABLEPREFIXchains
                ");

        $string = "";
        while ($row = $result->fetch()) {
            $string.= "<option value='". $row->chain_idx ."'";
            if ($row->chain_idx == $params['chain_idx']) {
                $string.= " selected=\"selected\"";
            }
            $string.= ">". $row->chain_name ."</option>\n";
        }

        return $string;
    }

    public function smartyPipeSelectList($params, &$smarty)
    {
        global $db;

        if (!array_key_exists('pipe_idx', $params)) {
            $this->trigger_error("smarty_pipe_select_list: missing 'pipe_idx' parameter", E_USER_WARNING);
            $repeat = false;
            return;
        }

        $result = $db->query("
                SELECT
                *
                FROM
                TABLEPREFIXpipes
                ");

        $string = "";
        while ($row = $result->fetch()) {
            $string.= "<option value='". $row->pipe_idx ."'";
            if ($row->pipe_idx == $params['pipe_idx']) {
                $string.= " selected=\"selected\"";
            }
            $string.= ">". $row->pipe_idx ."</option>\n";
        }

        return $string;
    }

    public function smartyTargetSelectList($params, &$smarty)
    {
        global $db;

        if (!array_key_exists('target_idx', $params)) {
            $this->trigger_error("smarty_target_select_list: missing 'target_idx' parameter", E_USER_WARNING);
            $repeat = false;
            return;
        }

        $result = $db->query("
                SELECT
                target_idx,
                target_name
                FROM
                TABLEPREFIXtargets
                ORDER BY
                target_name
                ");

        $string = "";
        while ($row = $result->fetch()) {
            $string.= "<option value=\"". $row->target_idx ."\" ";
            if ($row->target_idx == $params['target_idx']) {
                $string.= " selected=\"selected\"";
            }
            $string.= ">". $row->target_name ."</option>\n";
        }

        return $string;
    }

    public function smartyServiceLevelSelectList($params, &$smarty)
    {
        global $ms, $db;

        // per default we show all service level details
        if (!array_key_exists('details', $params)) {
            $params['details'] = 'yes';
        }

        $result = $db->query("
                SELECT
                *
                FROM
                TABLEPREFIXservice_levels
                ORDER BY
                sl_name ASC
                ");

        $string = "";
        while ($row = $result->fetch()) {

            $string.= "<option value=\"". $row->sl_idx ."\"";

            if (isset($params['sl_idx']) && $row->sl_idx == $params['sl_idx']) {
                $string.= " selected=\"selected\"";
            }

            $string.= ">";

            if (isset($params['sl_default']) && $row->sl_idx == $params['sl_default']) {
                $string.= "*** ";
            }
            $string.= $row->sl_name;

            if ($params['details'] == 'yes') {

                switch ($ms->getOption("classifier")) {
                    case 'HTB':
                        $string.= "(in: ".
                            $row->sl_htb_bw_in_rate ."kbit/s, out: ".
                            $row->sl_htb_bw_out_rate ."kbit/s)";
                        break;
                    case 'HFSC':
                        $string.= "(in: ". $row->sl_hfsc_in_dmax .
                            "ms,". $row->sl_hfsc_in_rate ."kbit/s, out: ".
                            $row->sl_hfsc_out_dmax ."ms,".
                            $row->sl_hfsc_bw_out_rate ."kbit/s)";
                        break;
                }
            }

            if (isset($params['sl_default']) && $row->sl_idx == $params['sl_default']) {
                $string.= " ***";
            }

            $string.= "</option>\n";
        }

        return $string;
    }

    public function smartyNetworkPathSelectList($params, &$smarty)
    {
        global $db;

        if (!array_key_exists('np_idx', $params)) {
            $this->trigger_error("smarty_network_path_select_list: missing 'np_idx' parameter", E_USER_WARNING);
            $repeat = false;
            return;
        }

        $result = $db->query("
                SELECT
                *
                FROM
                TABLEPREFIXnetwork_paths
                ORDER BY
                netpath_name ASC
                ");

        $string = "";
        while ($row = $result->fetch()) {
            $string.= "<option value=\"". $row->netpath_idx ."\"";
            if ($row->netpath_idx == $params['np_idx']) {
                $string.= " selected=\"selected\"";
            }
            $string.= ">". $row->netpath_name ."</option>\n";
        }

        return $string;
    }

    public function smartyHostProfileSelectList($params, &$smarty)
    {
        global $ms, $db;

        $result = $db->query("
                SELECT
                *
                FROM
                TABLEPREFIXhost_profiles
                ORDER BY
                host_name ASC
                ");

        $string = "";
        while ($row = $result->fetch()) {
            $string.= "<option value=\"". $row->host_idx ."\"";
            if ($row->host_idx == $ms->get_current_host_profile()) {
                $string.= " selected=\"selected\"";
            }
            $string.= ">". $row->host_name ."</option>\n";
        }

        return $string;

    }

    public function smartyGetItemName($params, &$smarty)
    {
        global $ms, $db;

        if (!array_key_exists('idx', $params)) {
            $this->trigger_error("smarty_get_item_name: missing 'idx' parameter", E_USER_WARNING);
            $repeat = false;
            return;
        }
        if (!array_key_exists('type', $params)) {
            $this->trigger_error("smarty_get_item_name: missing 'type' parameter", E_USER_WARNING);
            $repeat = false;
            return;
        }

        switch ($params['type']) {

            case 'sl':
                $table = 'service_levels';
                $column_prefix = 'sl';
                $zero = 'Ignore QoS';
                break;

            case 'fallsl':
                $table = 'service_levels';
                $column_prefix = 'sl';
                $zero = 'No Fallback';
                break;

            case 'target':
                $table = 'targets';
                $column_prefix = 'target';
                $zero = 'any';
                break;

            case 'direction':
                switch ($params['idx']) {
                    case 1:
                        return "--&gt;";
                        break;
                    case 2:
                        return "&lt;-&gt;";
                        break;
                }
                break;
        }

        // if idx is zero, return immediately
        if ($params['idx'] == 0) {
            return $zero;
        }

        $result = $db->query(
            "SELECT
                ". $column_prefix ."_name
            FROM
                TABLEPREFIX{$table}
            WHERE
                ". $column_prefix ."_idx LIKE '". $params['idx'] ."'"
        );

        if ($row = $result->fetch(PDO::FETCH_NUM)) {
            $db->db_sth_free($result);
            return $row[0];
        }

        return $string;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
