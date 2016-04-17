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

class InstallerController extends \Thallium\Controllers\InstallerController
{
    protected $schema_version_before;

    protected function createApplicationDatabaseTables()
    {
        global $db, $ms;

        if (!$db->checkTableExists("TABLEPREFIXassign_filters_to_pipes")) {
            $table_sql = "CREATE TABLE `TABLEPREFIXassign_filters_to_pipes` (
                `apf_idx` int(11) NOT NULL auto_increment,
                `apf_guid` VARCHAR(255) DEFAULT NULL,
                `apf_pipe_idx` int(11) default NULL,
                `apf_filter_idx` int(11) default NULL,
                PRIMARY KEY  (`apf_idx`),
                KEY `apf_pipe_idx` (`apf_pipe_idx`),
                KEY `apf_filter_idx` (`apf_filter_idx`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8;";

            if ($db->query($table_sql) === false) {
                static::raiseError("Failed to create 'archive' table");
                return false;
            }
        }

        if (!$db->checkTableExists('TABLEPREFIXassign_ports_to_filters')) {
            $table_sql = "CREATE TABLE `TABLEPREFIXassign_ports_to_filters` (
                `afp_idx` int(11) NOT NULL auto_increment,
                `afp_guid` VARCHAR(255) DEFAULT NULL,
                `afp_filter_idx` int(11) default NULL,
                `afp_port_idx` int(11) default NULL,
                PRIMARY KEY  (`afp_idx`),
                KEY `afp_filter_idx` (`afp_filter_idx`),
                KEY `afp_port_idx` (`afp_port_idx`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8;";

            if ($db->query($table_sql) === false) {
                static::raiseError("Failed to create 'archive' table");
                return false;
            }
        }

        if (!$db->checkTableExists('TABLEPREFIXassign_targets_to_targets')) {
            $table_sql = "CREATE TABLE `TABLEPREFIXassign_targets_to_targets` (
                `atg_idx` int(11) NOT NULL auto_increment,
                `atg_guid` VARCHAR(255) DEFAULT NULL,
                `atg_group_idx` int(11) NOT NULL,
                `atg_target_idx` int(11) NOT NULL,
                PRIMARY KEY  (`atg_idx`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8;";

            if ($db->query($table_sql) === false) {
                static::raiseError("Failed to create 'archive' table");
                return false;
            }
        }

        if (!$db->checkTableExists('TABLEPREFIXassign_pipes_to_chains')) {
            $table_sql = "CREATE TABLE `TABLEPREFIXassign_pipes_to_chains` (
                `apc_idx` int(11) NOT NULL auto_increment,
                `apc_guid` VARCHAR(255) DEFAULT NULL,
                `apc_pipe_idx` int(11) NOT NULL,
                `apc_chain_idx` int(11) NOT NULL,
                `apc_sl_idx` int(11) NOT NULL,
                `apc_pipe_active` char(1) default NULL,
                `apc_pipe_pos` int(11) DEFAULT NULL,
                PRIMARY KEY  (`apc_idx`),
                KEY `apc_pipe_to_chain`  (`apc_pipe_idx`,`apc_chain_idx`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8;";

            if ($db->query($table_sql) === false) {
                static::raiseError("Failed to create 'archive' table");
                return false;
            }
        }

        if (!$db->checkTableExists('TABLEPREFIXchains')) {
            $table_sql = "CREATE TABLE `TABLEPREFIXchains` (
                `chain_idx` int(11) NOT NULL auto_increment,
                `chain_guid` VARCHAR(255) DEFAULT NULL,
                `chain_name` varchar(255) default NULL,
                `chain_active` char(1) default NULL,
                `chain_sl_idx` int(11) default NULL,
                `chain_src_target` int(11) default NULL,
                `chain_dst_target` int(11) default NULL,
                `chain_position` int(11) default NULL,
                `chain_direction` int(11) default NULL,
                `chain_fallback_idx` int(11) default NULL,
                `chain_action` varchar(16) default NULL,
                `chain_tc_id` varchar(16) default NULL,
                `chain_netpath_idx` int(11) default NULL,
                `chain_host_idx` int(11) default NULL,
                PRIMARY KEY  (`chain_idx`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8;";

            if ($db->query($table_sql) === false) {
                static::raiseError("Failed to create 'archive' table");
                return false;
            }
        }

        if (!$db->checkTableExists('TABLEPREFIXfilters')) {
            $table_sql = "CREATE TABLE `TABLEPREFIXfilters` (
                `filter_idx` int(11) NOT NULL auto_increment,
                `filter_guid` VARCHAR(255) DEFAULT NULL,
                `filter_name` varchar(255) default NULL,
                `filter_protocol_id` int(11) default NULL,
                `filter_tos` varchar(4) default NULL,
                `filter_dscp` varchar(4) default NULL,
                `filter_tcpflag_syn` char(1) default NULL,
                `filter_tcpflag_ack` char(1) default NULL,
                `filter_tcpflag_fin` char(1) default NULL,
                `filter_tcpflag_rst` char(1) default NULL,
                `filter_tcpflag_urg` char(1) default NULL,
                `filter_tcpflag_psh` char(1) default NULL,
                `filter_packet_length` varchar(255) default NULL,
                `filter_time_use_range` char(1) default NULL,
                `filter_time_start` int(11) default NULL,
                `filter_time_stop` int(11) default NULL,
                `filter_time_day_mon` char(1) default NULL,
                `filter_time_day_tue` char(1) default NULL,
                `filter_time_day_wed` char(1) default NULL,
                `filter_time_day_thu` char(1) default NULL,
                `filter_time_day_fri` char(1) default NULL,
                `filter_time_day_sat` char(1) default NULL,
                `filter_time_day_sun` char(1) default NULL,
                `filter_match_ftp_data` char(1) default NULL,
                `filter_match_sip` char(1) default NULL,
                `filter_active` char(1) default NULL,
                PRIMARY KEY  (`filter_idx`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8;";

            if ($db->query($table_sql) === false) {
                static::raiseError("Failed to create 'archive' table");
                return false;
            }
        }

        if (!$db->checkTableExists('TABLEPREFIXinterfaces')) {
            $table_sql = "CREATE TABLE `TABLEPREFIXinterfaces` (
                `if_idx` int(11) NOT NULL auto_increment,
                `if_guid` VARCHAR(255) DEFAULT NULL,
                `if_name` varchar(255) default NULL,
                `if_speed` varchar(255) default NULL,
                `if_fallback_idx` int(11) default NULL,
                `if_ifb` char(1) default NULL,
                `if_active` char(1) default NULL,
                `if_host_idx` int(11) default NULL,
                PRIMARY KEY  (`if_idx`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8;";

            if ($db->query($table_sql) === false) {
                static::raiseError("Failed to create 'archive' table");
                return false;
            }
        }

        if (!$db->checkTableExists('TABLEPREFIXnetwork_paths')) {
            $table_sql = "CREATE TABLE `TABLEPREFIXnetwork_paths` (
                `netpath_idx` int(11) NOT NULL auto_increment,
                `netpath_guid` VARCHAR(255) DEFAULT NULL,
                `netpath_name` varchar(255) default NULL,
                `netpath_if1` int(11) default NULL,
                `netpath_if1_inside_gre` varchar(1) default NULL,
                `netpath_if2` int(11) default NULL,
                `netpath_if2_inside_gre` varchar(1) default NULL,
                `netpath_position` int(11) default NULL,
                `netpath_imq` varchar(1) default NULL,
                `netpath_active` varchar(1) default NULL,
                `netpath_host_idx` int(11) default NULL,
                PRIMARY KEY  (`netpath_idx`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8;";

            if ($db->query($table_sql) === false) {
                static::raiseError("Failed to create 'archive' table");
                return false;
            }
        }

        if (!$db->checkTableExists('TABLEPREFIXpipes')) {
            $table_sql = "CREATE TABLE `TABLEPREFIXpipes` (
                `pipe_idx` int(11) NOT NULL auto_increment,
                `pipe_guid` VARCHAR(255) DEFAULT NULL,
                `pipe_name` varchar(255) default NULL,
                `pipe_sl_idx` int(11) default NULL,
                `pipe_src_target` int(11) default NULL,
                `pipe_dst_target` int(11) default NULL,
                `pipe_direction` int(11) default NULL,
                `pipe_action` varchar(15) default NULL,
                `pipe_active` char(1) default NULL,
                `pipe_tc_id` varchar(16) default NULL,
                PRIMARY KEY  (`pipe_idx`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8;";

            if ($db->query($table_sql) === false) {
                static::raiseError("Failed to create 'archive' table");
                return false;
            }
        }

        if (!$db->checkTableExists('TABLEPREFIXports')) {
            $table_sql = "CREATE TABLE `TABLEPREFIXports` (
                `port_idx` int(11) NOT NULL auto_increment,
                `port_guid` VARCHAR(255) DEFAULT NULL,
                `port_name` varchar(255) default NULL,
                `port_desc` varchar(255) default NULL,
                `port_number` varchar(255) default NULL,
                `port_user_defined` char(1) default NULL,
                `port_active` char(1) default NULL,
                PRIMARY KEY  (`port_idx`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8;";

            if ($db->query($table_sql) === false) {
                static::raiseError("Failed to create 'archive' table");
                return false;
            }
        }

        if (!$db->checkTableExists('TABLEPREFIXprotocols')) {
            $table_sql = "CREATE TABLE `TABLEPREFIXprotocols` (
                `proto_idx` int(11) NOT NULL auto_increment,
                `proto_guid` VARCHAR(255) DEFAULT NULL,
                `proto_number` varchar(255) default NULL,
                `proto_name` varchar(255) default NULL,
                `proto_desc` varchar(255) default NULL,
                `proto_user_defined` char(1) default NULL,
                `proto_active` char(1) default NULL,
                PRIMARY KEY  (`proto_idx`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8;";

            if ($db->query($table_sql) === false) {
                static::raiseError("Failed to create 'archive' table");
                return false;
            }
        }

        if (!$db->checkTableExists('TABLEPREFIXservice_levels')) {
            $table_sql = "CREATE TABLE `TABLEPREFIXservice_levels` (
                `sl_idx` int(11) NOT NULL auto_increment,
                `sl_guid` VARCHAR(255) DEFAULT NULL,
                `sl_name` varchar(255) default NULL,
                `sl_active` char(1) default NULL,
                `sl_htb_bw_in_rate` varchar(255) default NULL,
                `sl_htb_bw_in_ceil` varchar(255) default NULL,
                `sl_htb_bw_in_burst` varchar(255) default NULL,
                `sl_htb_bw_out_rate` varchar(255) default NULL,
                `sl_htb_bw_out_ceil` varchar(255) default NULL,
                `sl_htb_bw_out_burst` varchar(255) default NULL,
                `sl_htb_priority` varchar(255) default NULL,
                `sl_hfsc_in_umax` varchar(255) default NULL,
                `sl_hfsc_in_dmax` varchar(255) default NULL,
                `sl_hfsc_in_rate` varchar(255) default NULL,
                `sl_hfsc_in_ulrate` varchar(255) default NULL,
                `sl_hfsc_out_umax` varchar(255) default NULL,
                `sl_hfsc_out_dmax` varchar(255) default NULL,
                `sl_hfsc_out_rate` varchar(255) default NULL,
                `sl_hfsc_out_ulrate` varchar(255) default NULL,
                `sl_qdisc` varchar(255) default NULL,
                `sl_sfq_perturb` varchar(255) default NULL,
                `sl_sfq_quantum` varchar(255) default NULL,
                `sl_netem_delay` varchar(255) default NULL,
                `sl_netem_jitter` varchar(255) default NULL,
                `sl_netem_random` varchar(255) default NULL,
                `sl_netem_distribution` varchar(255) default NULL,
                `sl_netem_loss` varchar(255) default NULL,
                `sl_netem_duplication` varchar(255) default NULL,
                `sl_netem_gap` varchar(255) default NULL,
                `sl_netem_reorder_percentage` varchar(255) default NULL,
                `sl_netem_reorder_correlation` varchar(255) default NULL,
                `sl_esfq_perturb` varchar(255) default NULL,
                `sl_esfq_limit` varchar(255) default NULL,
                `sl_esfq_depth` varchar(255) default NULL,
                `sl_esfq_divisor` varchar(255) default NULL,
                `sl_esfq_hash` varchar(255) default NULL,
                PRIMARY KEY  (`sl_idx`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8;";

            if ($db->query($table_sql) === false) {
                static::raiseError("Failed to create 'archive' table");
                return false;
            }
        }

        if (!$db->checkTableExists('TABLEPREFIXsettings')) {
            $table_sql = "CREATE TABLE `TABLEPREFIXsettings` (
                `setting_idx` int(11) NOT NULL auto_increment,
                `setting_guid` VARCHAR(255) DEFAULT NULL,
                `setting_key` varchar(255) NOT NULL default '',
                `setting_value` varchar(255) default NULL,
                PRIMARY KEY  (`setting_idx`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8;";

            if ($db->query($table_sql) === false) {
                static::raiseError("Failed to create 'archive' table");
                return false;
            }
        }

        if (!$db->checkTableExists('TABLEPREFIXstats')) {
            $table_sql = "CREATE TABLE `TABLEPREFIXstats` (
                `stat_time` int(11) NOT NULL default '0',
                `stat_data` text,
                `stat_host_idx` int(11) default NULL,
                PRIMARY KEY  (`stat_time`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8;";

            if ($db->query($table_sql) === false) {
                static::raiseError("Failed to create 'archive' table");
                return false;
            }
        }

        if (!$db->checkTableExists('TABLEPREFIXtargets')) {
            $table_sql = "CREATE TABLE `TABLEPREFIXtargets` (
                `target_idx` int(11) NOT NULL auto_increment,
                `target_guid` VARCHAR(255) DEFAULT NULL,
                `target_name` varchar(255) default NULL,
                `target_match` varchar(16) default NULL,
                `target_ip` varchar(255) default NULL,
                `target_mac` varchar(255) default NULL,
                `target_active` VARCHAR(1) DEFAULT NULL,
                PRIMARY KEY  (`target_idx`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8;";

            if ($db->query($table_sql) === false) {
                static::raiseError("Failed to create 'archive' table");
                return false;
            }
        }

        if (!$db->checkTableExists('TABLEPREFIXtc_ids')) {
            $table_sql = "CREATE TABLE `TABLEPREFIXtc_ids` (
                `id_pipe_idx` int(11) default NULL,
                `id_chain_idx` int(11) default NULL,
                `id_if` varchar(255) default NULL,
                `id_tc_id` varchar(255) default NULL,
                `id_color` varchar(7) default NULL,
                `id_host_idx` int(11) default NULL,
                `id_guid` varchar(36) DEFAULT NULL,
                KEY `id_pipe_idx` (`id_pipe_idx`),
                KEY `id_chain_idx` (`id_chain_idx`),
                KEY `id_if` (`id_if`),
                KEY `id_tc_id` (`id_tc_id`),
                KEY `id_color` (`id_color`)
                ) ENGINE=MEMORY DEFAULT CHARSET=utf8;";

            if ($db->query($table_sql) === false) {
                static::raiseError("Failed to create 'archive' table");
                return false;
            }
        }

        if (!$db->checkTableExists('TABLEPREFIXusers')) {
            $table_sql = "CREATE TABLE `TABLEPREFIXusers` (
                `user_idx` int(11) NOT NULL auto_increment,
                `user_guid` VARCHAR(255) DEFAULT NULL,
                `user_name` varchar(32) default NULL,
                `user_password` varchar(255) default NULL,
                `user_manage_chains` char(1) default NULL,
                `user_manage_pipes` char(1) default NULL,
                `user_manage_filters` char(1) default NULL,
                `user_manage_ports` char(1) default NULL,
                `user_manage_protocols` char(1) default NULL,
                `user_manage_targets` char(1) default NULL,
                `user_manage_users` char(1) default NULL,
                `user_manage_options` char(1) default NULL,
                `user_manage_servicelevels` char(1) default NULL,
                `user_show_rules` char(1) default NULL,
                `user_load_rules` char(1) default NULL,
                `user_show_monitor` char(1) default NULL,
                `user_active` char(1) default NULL,
                PRIMARY KEY  (`user_idx`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8;";

            if ($db->query($table_sql) === false) {
                static::raiseError("Failed to create 'archive' table");
                return false;
            }

            $db->query(
                "INSERT INTO TABLEPREFIXusers VALUES (
                    NULL,
                    '". $ms->createGuid() ."',
                    'admin',
                    MD5('admin'),
                    'Y',
                    'Y',
                    'Y',
                    'Y',
                    'Y',
                    'Y',
                    'Y',
                    'Y',
                    'Y',
                    'Y',
                    'Y',
                    'Y',
                    'Y'
                )"
            );
        }

        if (!$db->checkTableExists('TABLEPREFIXhost_profiles')) {
            $table_sql = "CREATE TABLE `TABLEPREFIXhost_profiles` (
                `host_idx` int(11) NOT NULL auto_increment,
                `host_guid` VARCHAR(255) DEFAULT NULL,
                `host_name` varchar(32) default NULL,
                `host_active` char(1) default NULL,
                `host_heartbeat` int(11) default NULL,
                PRIMARY KEY  (`host_idx`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8;";

            if ($db->query($table_sql) === false) {
                static::raiseError("Failed to create 'archive' table");
                return false;
            }

            $db->query(
                "INSERT INTO TABLEPREFIXhost_profiles VALUES (
                    NULL,
                    '". $ms->createGuid() ."',
                    'Default Host',
                    'Y',
                    0
                )"
            );
        }

        if (!$db->checkTableExists('TABLEPREFIXtasks')) {
            $table_sql = "CREATE TABLE `TABLEPREFIXtasks` (
                `task_idx` int(11) NOT NULL auto_increment,
                `task_guid` VARCHAR(255) DEFAULT NULL,
                `task_job` varchar(255) default NULL,
                `task_submit_time` int(11) NOT NULL default '0',
                `task_run_time` int(11) NOT NULL default '0',
                `task_host_idx` int(11) default NULL,
                `task_state` varchar(1) default NULL,
                PRIMARY KEY  (`task_idx`),
                UNIQUE KEY `task_job` (`task_job`,`task_run_time`,`task_host_idx`,`task_state`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8;";

            if ($db->query($table_sql) === false) {
                static::raiseError("Failed to create 'archive' table");
                return false;
            }
        }

        return true;
    }

    protected function upgradeApplicationDatabaseSchemaV23()
    {
        global $db;

        $db->query(
            "ALTER TABLE
                TABLEPREFIXassign_filters_to_pipes
            ADD
                `apf_guid` VARCHAR(255) DEFAULT NULL
            AFTER
                `apf_idx`"
        ) or static::raiseError(__METHOD__ .'(), SQL failure!');

        $db->query(
            "ALTER TABLE
                TABLEPREFIXassign_ports_to_filters
            ADD
                `afp_guid` VARCHAR(255) DEFAULT NULL
            AFTER
                `afp_idx`"
        ) or static::raiseError(__METHOD__ .'(), SQL failure!');

        $db->query(
            "ALTER TABLE
                TABLEPREFIXassign_targets_to_targets
            ADD
                `atg_guid` VARCHAR(255) DEFAULT NULL
            AFTER
                `atg_idx`"
        ) or static::raiseError(__METHOD__ .'(), SQL failure!');

        $db->query(
            "ALTER TABLE
                TABLEPREFIXassign_pipes_to_chains
            MODIFY
                `apc_guid` VARCHAR(255) DEFAULT NULL
            AFTER
                `apc_idx`"
        ) or static::raiseError(__METHOD__ .'(), SQL failure!');

        $db->query(
            "ALTER TABLE
                TABLEPREFIXchains
            MODIFY
                `chain_guid` VARCHAR(255) DEFAULT NULL
            AFTER
                `chain_idx`"
        ) or static::raiseError(__METHOD__ .'(), SQL failure!');

        $db->query(
            "ALTER TABLE
                TABLEPREFIXfilters
            ADD
                `filter_guid` VARCHAR(255) DEFAULT NULL
            AFTER
                `filter_idx`"
        ) or static::raiseError(__METHOD__ .'(), SQL failure!');

        $db->query(
            "ALTER TABLE
                TABLEPREFIXinterfaces
            ADD
                `if_guid` VARCHAR(255) DEFAULT NULL
            AFTER
                `if_idx`"
        ) or static::raiseError(__METHOD__ .'(), SQL failure!');

        $db->query(
            "ALTER TABLE
                TABLEPREFIXnetwork_paths
            ADD
                `netpath_guid` VARCHAR(255) DEFAULT NULL
            AFTER
                `netpath_idx`"
        ) or static::raiseError(__METHOD__ .'(), SQL failure!');

        $db->query(
            "ALTER TABLE
                TABLEPREFIXpipes
            ADD
                `pipe_guid` VARCHAR(255) DEFAULT NULL
            AFTER
                `pipe_idx`"
        ) or static::raiseError(__METHOD__ .'(), SQL failure!');

        $db->query(
            "ALTER TABLE
                TABLEPREFIXports
            ADD
                `port_guid` VARCHAR(255) DEFAULT NULL
            AFTER
                `port_idx`"
        ) or static::raiseError(__METHOD__ .'(), SQL failure!');

        $db->query(
            "ALTER TABLE
                TABLEPREFIXprotocols
            ADD
                `proto_guid` VARCHAR(255) DEFAULT NULL
            AFTER
                `proto_idx`"
        ) or static::raiseError(__METHOD__ .'(), SQL failure!');

        $db->query(
            "ALTER TABLE
                TABLEPREFIXservice_levels
            ADD
                `sl_guid` VARCHAR(255) DEFAULT NULL
            AFTER
                `sl_idx`"
        ) or static::raiseError(__METHOD__ .'(), SQL failure!');

        $db->query(
            "ALTER TABLE
                TABLEPREFIXtargets
            ADD
                `target_guid` VARCHAR(255) DEFAULT NULL
            AFTER
                `target_idx`"
        ) or static::raiseError(__METHOD__ .'(), SQL failure!');

        $db->query(
            "ALTER TABLE
                TABLEPREFIXusers
            ADD
                `user_guid` VARCHAR(255) DEFAULT NULL
            AFTER
                `user_idx`"
        ) or static::raiseError(__METHOD__ .'(), SQL failure!');

        $db->query(
            "ALTER TABLE
                TABLEPREFIXhost_profiles
            ADD
                `host_guid` VARCHAR(255) DEFAULT NULL
            AFTER
                `host_idx`"
        ) or static::raiseError(__METHOD__ .'(), SQL failure!');

        $db->query(
            "ALTER TABLE
                TABLEPREFIXtasks
            ADD
                `task_guid` VARCHAR(255) DEFAULT NULL
            AFTER
                `task_idx`"
        ) or static::raiseError(__METHOD__ .'(), SQL failure!');

        $db->setDatabaseSchemaVersion(23);
        return true;
    }

    protected function upgradeApplicationDatabaseSchemaV24()
    {
        global $ms, $db;

        $db->query(
            "UPDATE
                TABLEPREFIXusers
            SET
                user_guid='". $ms->createGuid() ."'
            WHERE
                user_idx LIKE 1
            AND
                user_name LIKE 'admin'
            AND
                user_guid IS NULL"
        );

        $db->query(
            "UPDATE
                TABLEPREFIXhost_profiles
            SET
                host_guid='". $ms->createGuid() ."'
            WHERE
                host_idx LIKE 1
            AND
                host_name LIKE 'Default Host'
            AND
                host_guid IS NULL"
        );

        $db->setDatabaseSchemaVersion(24);
        return true;
    }

    protected function upgradeApplicationDatabaseSchemaV25()
    {
        global $db;

        $db->query(
            "ALTER TABLE
                TABLEPREFIXtargets
            ADD
                `target_active` VARCHAR(1) DEFAULT NULL
            AFTER
                `target_mac`"
        ) or static::raiseError(__METHOD__ .'(), SQL failure!');

        $db->setDatabaseSchemaVersion(25);
        return true;
    }

    protected function upgradeApplicationDatabaseSchemaV26()
    {
        global $db;

        $db->query(
            "ALTER TABLE
                TABLEPREFIXports
            ADD
                `port_active` char(1) default NULL
            AFTER
                `port_user_defined`"
        ) or static::raiseError(__METHOD__ .'(), SQL failure!');

        $db->query(
            "ALTER TABLE
                TABLEPREFIXprotocols
            ADD
                `proto_active` char(1) default NULL
            AFTER
                `proto_user_defined`"
        ) or static::raiseError(__METHOD__ .'(), SQL failure!');

        $db->setDatabaseSchemaVersion(26);
        return true;
    }

    protected function upgradeApplicationDatabaseSchemaV27()
    {
        global $db;

        $db->query(
            "ALTER TABLE
                TABLEPREFIXservice_levels
            ADD
                `sl_active` char(1) default NULL
            AFTER
                `sl_name`"
        ) or static::raiseError(__METHOD__ .'(), SQL failure!');

        $db->setDatabaseSchemaVersion(27);
        return true;
    }

    protected function upgradeApplicationDatabaseSchemaV28()
    {
        global $db;

        $db->query(
            "ALTER TABLE
                TABLEPREFIXusers
            CHANGE
                `user_pass` `user_password` varchar(32) default NULL"
        ) or static::raiseError(__METHOD__ .'(), SQL failure!');

        $db->setDatabaseSchemaVersion(28);
        return true;
    }

    protected function upgradeApplicationDatabaseSchemaV29()
    {
        global $db;

        $db->query(
            "ALTER TABLE
                TABLEPREFIXusers
            MODIFY
                `user_password` varchar(255) default NULL"
        ) or static::raiseError(__METHOD__ .'(), SQL failure!');

        $db->setDatabaseSchemaVersion(29);
        return true;
    }

    protected function upgradeApplicationDatabaseSchemaV30()
    {
        global $db;

        $db->query(
            "DROP TABLE
                TABLEPREFIXassign_l7_protocols_to_filters,
                TABLEPREFIXl7_protocols"
        ) or static::raiseError(__METHOD__ .'(), SQL failure!');

        $db->setDatabaseSchemaVersion(30);
        return true;
    }

    protected function upgradeApplicationDatabaseSchemaV31()
    {
        global $ms, $db;

        $db->query(
            "ALTER TABLE
                TABLEPREFIXsettings
            DROP
                PRIMARY KEY"
        ) or static::raiseError(__METHOD__ .'(), SQL failure!');

        $db->query(
            "ALTER TABLE
                TABLEPREFIXsettings
            ADD
                `setting_idx` int(11) NOT NULL auto_increment PRIMARY KEY FIRST,
            ADD
                `setting_guid` VARCHAR(255) DEFAULT NULL
            AFTER
                `setting_idx`"
        ) or static::raiseError(__METHOD__ .'(), SQL failure!');

        $result = $db->query(
            "SELECT
                *
            FROM
                TABLEPREFIXsettings"
        ) or static::raiseError(__METHOD__ .'(), SQL failure!');

        $sth = $db->prepare(
            "UPDATE
                TABLEPREFIXsettings
            SET
                setting_guid=?
            WHERE
                setting_idx=?
            AND
                setting_key=?"
        ) or static::raiseError(__METHOD__ .'(), SQL statement prepare failure!');

        while ($row = $result->fetch()) {
            $db->execute($sth, array(
                $ms->createGuid(),
                $row->setting_idx,
                $row->setting_key
            )) or static::raiseError(__METHOD__ .'(), SQL statement execute failure!');
        }

        $db->freeStatement($sth);
        $db->setDatabaseSchemaVersion(31);
        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
