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

    public function show()
    {
        global $ms, $db, $tmpl;

        try {
            $service_levels = new \MasterShaper\Models\ServiceLevelsModel;
        } catch (\Exception $e) {
            $ms->raiseError(__METHOD__ .'(), failed to load ЅerviceLevelsModel!');
            return false;
        }

        if (($this->service_levels = $service_levels->getItems()) === false) {
            $ms->raiseError(get_class($service_levels) .'::getItems() returned false!');
            return false;
        }

        $tmpl->assign('language', $ms->getOption("language"));
        $tmpl->assign('ack_sl', $ms->getOption("ack_sl"));
        $tmpl->assign('classifier', $ms->getOption("classifier"));
        $tmpl->assign('qdisc', $ms->getOption("qdisc"));

        $tmpl->assign('esfq_default_perturb', $ms->getOption("esfq_default_perturb"));
        $tmpl->assign('esfq_default_limit', $ms->getOption("esfq_default_limit"));
        $tmpl->assign('esfq_default_depth', $ms->getOption("esfq_default_depth"));
        $tmpl->assign('esfq_default_divisor', $ms->getOption("esfq_default_divisor"));
        $tmpl->assign('esfq_default_hash', $ms->getOption("esfq_default_hash"));
        $tmpl->assign('filter', $ms->getOption("filter"));
        $tmpl->assign('msmode', $ms->getOption("msmode"));
        $tmpl->assign('authentication', $ms->getOption("authentication"));
        $tmpl->assign('use_hashkey', $ms->getOption("use_hashkey"));
        $tmpl->assign('hashkey_ip', $ms->getOption("hashkey_ip"));
        $tmpl->assign('hashkey_mask', $ms->getOption("hashkey_mask"));
        $tmpl->assign('hashkey_matchon', $ms->getOption("hashkey_matchon"));

        $tmpl->registerPlugin("block", "service_level_list", array(&$this, "smartyOptionsServiceLevelList"));
        return $tmpl->fetch("options.tpl");

    } // show()

    public function store()
    {
        global $ms, $db;
        $ms->setOption("ack_sl", $_POST['ack_sl']);
        $ms->setOption("classifier", $_POST['classifier']);
        $ms->setOption("qdisc", $_POST['qdisc']);
        $ms->setOption("filter", $_POST['filter']);
        $ms->setOption("authentication", $_POST['authentication']);
        $ms->setOption("msmode", $_POST['msmode']);
        $ms->setOption("language", $_POST['language']);
        if (isset($_POST['use_hashkey'])) {
            $ms->setOption("use_hashkey", $_POST['use_hashkey']);
        } else {
            $ms->setOption("use_hashkey", 'N');
        }
        if (isset($_POST['hashkey_ip'])) {
            $ms->setOption("hashkey_ip", $_POST['hashkey_ip']);
        } else {
            $ms->setOption("hashkey_ip", '');
        }
        if (isset($_POST['hashkey_mask'])) {
            $ms->setOption("hashkey_mask", $_POST['hashkey_mask']);
        }
        if (isset($_POST['hashkey_matchon'])) {
            $ms->setOption("hashkey_matchon", $_POST['hashkey_matchon']);
        }

        if ($_POST['qdisc'] == "ESFQ") {
            $ms->setOption("esfq_default_perturb", $_POST['esfq_default_perturb']);
            $ms->setOption("esfq_default_limit", $_POST['esfq_default_limit']);
            $ms->setOption("esfq_default_depth", $_POST['esfq_default_depth']);
            $ms->setOption("esfq_default_divisor", $_POST['esfq_default_divisor']);
            $ms->setOption("esfq_default_hash", $_POST['esfq_default_hash']);
        }

        return "ok";

    } // store()

    /* restore configuration from user upload */
    public function restoreConfig()
    {
        /* If authentication is enabled, check permissions */
        if ($ms->getOption("authentication") == "Y" &&
                !$ms->checkPermissions("user_manage_options")) {

            $ms->printError(
                "<img src=\"". ICON_OPTIONS ."\" alt=\"options icon\" />&nbsp;".  _("Manage Options"),
                _("You do not have enough permissions to access this module!")
            );
            return 0;

        }

        if (!isset($_GET['restoreit'])) {

            $ms->startTable(
                "<img src=\"". ICON_OPTIONS ."\" alt=\"option icon\" />&nbsp;".
                _("Restore MasterShaper Configuration")
            );
?>
<form enctype="multipart/form-data" action="<?php print $ms->self ."?mode=". $ms->mode; ?>&amp;restoreit=1" method="post">
 <table style="width: 100%;" class="withborder2">
  <tr>
   <td class="sysmessage" style="text-align: center;">
    <?php print _("Your current settings are lost after MasterShaper restored its configuration!"); ?>
   </td>
  </tr>
  <tr>
   <td>&nbsp;</td>
  </tr>
  <tr>
   <td style="text-align: center;">
    <input type="file" name="ms_config" />
    <input type="submit" value="<?php print _("Restore"); ?>" />
   </td>
  </tr>
 </table>
</form>
<?php
                $ms->closeTable();
        } else {

            $this->resetConfig(1);

            $config = array();

            if ($_FILES['ms_config']) {

                if ($config = fopen($_FILES['ms_config']['tmp_name'], "r")) {

                    while ($line = fgets($config, 2048)) {

                        $line = trim($line);

                        if (($line != "") && (!preg_match("/^#/", $line))) {

                            list($set, $parameters) = preg_split("/:/", $line, 2);

                            $object = unserialize(stripslashes($parameters));

                            $this->loadConfig($set, $object);
                        }
                    }
                    fclose($config);
                }
            }

            $ms->goStart();

        }

    } // restoreConfig()

    /* write configuration into database */
    public function loadConfig($set, $object)
    {
        switch ($set) {
            case 'Settings':
                $db->query("INSERT INTO TABLEPREFIXsettings (setting_key, setting_value) "
                        ."VALUES ('". $object->setting_key ."', '". $object->setting_value ."')");
                break;
            case 'Users':
                $db->query("INSERT INTO TABLEPREFIXusers (user_idx, user_name, user_pass, user_manage_chains, "
                        ."user_manage_pipes, user_manage_filters, user_manage_ports, user_manage_protocols, "
                        ."user_manage_targets, user_manage_users, user_manage_options, user_manage_servicelevels, "
                        ."user_show_rules, user_load_rules, user_show_monitor, user_active) VALUES ("
                        ."'". $object->user_idx ."', "
                        ."'". $object->user_name ."', "
                        ."'". $object->user_pass ."', "
                        ."'". $object->user_manage_chains ."', "
                        ."'". $object->user_manage_pipes ."', "
                        ."'". $object->user_manage_filters ."', "
                        ."'". $object->user_manage_ports ."', "
                        ."'". $object->user_manage_protocols ."', "
                        ."'". $object->user_manage_targets ."', "
                        ."'". $object->user_manage_users ."', "
                        ."'". $object->user_manage_options ."', "
                        ."'". $object->user_manage_servicelevels ."', "
                        ."'". $object->user_show_rules ."', "
                        ."'". $object->user_load_rules ."', "
                        ."'". $object->user_show_monitor ."', "
                        ."'". $object->user_active ."')");
                break;
            case 'Protocols':
                $db->query("INSERT INTO TABLEPREFIXprotocols (proto_name, proto_number, "
                        ."proto_user_defined) VALUES ('". $object->proto_name ."', "
                        ."'". $object->proto_name ."', 'Y')");
                break;
            case 'Ports':
                $db->query("INSERT INTO TABLEPREFIXports (port_name, port_desc, port_number, "
                        ."port_user_defined) VALUES ('". $object->port_name
                        ."', '". $object->port_desc ."', '". $object->port_number
                        ."', 'Y')");
                break;
            case 'Servicelevels':
                $db->query("INSERT INTO TABLEPREFIXservice_levels (sl_name, sl_htb_bw_in_rate, "
                        ."sl_htb_bw_in_ceil, sl_htb_bw_in_burst, sl_htb_bw_out_rate, "
                        ."sl_htb_bw_out_ceil, sl_htb_bw_out_burst, sl_htb_priority, "
                        ."sl_hfsc_in_umax, sl_hfsc_in_dmax, sl_hfsc_in_rate, sl_hfsc_in_ulrate, "
                        ."sl_hfsc_out_umax, sl_hfsc_out_dmax, sl_hfsc_out_rate, sl_hfsc_out_ulrate, "
                        ."sl_qdisc, sl_netem_delay, sl_netem_jitter, sl_netem_random, "
                        ."sl_netem_distribution, sl_netem_loss, sl_netem_duplication, sl_netem_gap, "
                        ."sl_netem_reorder_percentage, sl_netem_reorder_correlation, sl_esfq_perturb, "
                        ."sl_esfq_limit, sl_esfq_depth, sl_esfq_divisor, sl_esfq_hash) "
                        ."VALUES ('". $object->sl_name ."', '". $object->sl_htb_bw_in_rate
                        ."', '". $object->sl_htb_bw_in_ceil ."', '". $object->sl_htb_bw_in_burst
                        ."', '". $object->sl_htb_bw_out_rate ."', '". $object->sl_htb_bw_out_ceil
                        ."', '". $object->sl_htb_bw_out_burst ."', '". $object->sl_htb_priority
                        ."', '". $object->sl_hfsc_in_umax ."', '". $object->sl_hfsc_in_dmax
                        ."', '". $object->sl_hfsc_in_rate ."', '". $object->sl_hfsc_in_ulrate
                        ."', '". $object->sl_hfsc_out_umax ."', '". $object->sl_hfsc_out_dmax
                        ."', '". $object->sl_hfsc_out_rate ."', '". $object->sl_hfsc_out_ulrate
                        ."'". $object->sl_qdisc ."', "
                        ."'". $object->sl_netem_delay ."', "
                        ."'". $object->sl_netem_jitter ."', "
                        ."'". $object->sl_netem_random ."', "
                        ."'". $object->sl_netem_distribution ."', "
                        ."'". $object->sl_netem_loss ."', "
                        ."'". $object->sl_netem_duplication ."', "
                        ."'". $object->sl_netem_gap ."', "
                        ."'". $object->sl_netem_reorder_percentage ."', "
                        ."'". $object->sl_netem_reorder_correlation ."', "
                        ."'". $object->sl_esfq_perturb ."', "
                        ."'". $object->sl_esfq_limit ."', "
                        ."'". $object->sl_esfq_depth ."', "
                        ."'". $object->sl_esfq_divisor ."', "
                        ."'". $object->sl_esfq_hash ."')");
                break;
            case 'Targets':
                $db->query("INSERT INTO TABLEPREFIXtargets (target_name, target_match, target_ip, target_mac) "
                        ."VALUES ('". $object->target_name ."', '". $object->target_match ."', "
                        ."'". $object->target_ip ."', '". $object->target_mac ."')");

                $id = $db->db_getid();
                $members = preg_split('/#/', $object->target_members);
                foreach ($members as $member) {
                    $db->query("INSERT INTO TABLEPREFIXassign_targets_to_targets (atg_group_idx, atg_target_idx) "
                            ."VALUES ('". $id ."', '". $ms->getTargetByName($member) ."')");
                }
                break;
            case 'L7Proto':
                $db->query("INSERT INTO TABLEPREFIXl7_protocols (l7proto_idx, l7proto_name) "
                        ."VALUES ('". $object->l7proto_idx ."', '". $object->l7proto_name ."')");
                break;
            case 'Filters':
                $db->query("INSERT INTO TABLEPREFIXfilters (filter_idx, filter_name, filter_protocol_id, filter_tos, "
                        ."filter_tcpflag_syn, filter_tcpflag_ack, filter_tcpflag_fin, "
                        ."filter_tcpflag_rst, filter_tcpflag_urg, filter_tcpflag_psh, "
                        ."filter_packet_length, "
                        ."filter_time_use_range, filter_time_start, filter_time_stop, "
                        ."filter_time_day_mon, filter_time_day_tue, filter_time_day_wed, "
                        ."filter_time_day_thu, filter_time_day_fri, filter_time_day_sat, "
                        ."filter_time_day_sun, filter_match_ftp_data, filter_active) "
                        ."VALUES ('". $object->filter_idx ."', "
                        ."'". $object->filter_name ."', "
                        ."'". $ms->getProtocolByName($object->filter_protocol_id) ."', "
                        ."'". $object->filter_tos ."', "
                        ."'". $object->filter_tcpflag_syn ."', "
                        ."'". $object->filter_tcpflag_ack ."', "
                        ."'". $object->filter_tcpflag_fin ."', "
                        ."'". $object->filter_tcpflag_rst ."', "
                        ."'". $object->filter_tcpflag_urg ."', "
                        ."'". $object->filter_tcpflag_psh ."', "
                        ."'". $object->filter_packet_length ."', "
                        ."'". $object->filter_time_use_range. "', "
                        ."'". $object->filter_time_start ."', "
                        ."'". $object->filter_time_stop ."', "
                        ."'". $object->filter_time_day_mon ."', "
                        ."'". $object->filter_time_day_tue ."', "
                        ."'". $object->filter_time_day_wed ."', "
                        ."'". $object->filter_time_day_thu ."', "
                        ."'". $object->filter_time_day_fri ."', "
                        ."'". $object->filter_time_day_sat ."', "
                        ."'". $object->filter_time_day_sun ."', "
                        ."'". $object->filter_match_ftp_data ."', "
                        ."'". $object->filter_active ."')");

                $id = $db->db_getid();
                $ports = preg_split('/#/', $object->filter_ports);
                foreach ($ports as $port) {
                    $db->query("INSERT INTO TABLEPREFIXassign_ports_to_filters (afp_filter_idx, afp_port_idx) "
                            ."VALUES ('". $id ."', '". $ms->getPortByName($port) ."')");
                }
                $l7protos = preg_split('/#/', $object->l7_protocols);
                foreach ($l7protos as $l7proto) {
                    $db->query("INSERT INTO TABLEPREFIXassign_l7_protocols_to_filters (afl7_filter_idx, afl7_l7proto_idx) "
                            ."VALUES ('". $id ."', '". $ms->getL7ProtocolByName($l7proto) ."')");
                }
                break;
            case 'Chains':
                $db->query("INSERT INTO TABLEPREFIXchains (chain_name, chain_active, chain_sl_idx, "
                        ."chain_src_target, chain_dst_target, chain_position, chain_direction, "
                        ."chain_fallback_idx) VALUES ('". $object->chain_name
                        ."', '". $object->chain_active
                        ."', '". $ms->getServiceLevelByName($object->sl_name)
                        ."', '". $ms->getTargetByName($object->src_name)
                        ."', '". $ms->getTargetByName($object->dst_name)
                        ."', '". $object->chain_position ."', '". $object->chain_direction
                        ."', '". $ms->getServiceLevelByName($object->fb_name) ."')");
                break;
            case 'Pipes':
                $db->query("INSERT INTO TABLEPREFIXpipes (pipe_name, pipe_sl_idx, "
                        ."pipe_src_target, pipe_dst_target, pipe_direction, pipe_active)
                    VALUES ('". $object->pipe_name
                            ."', '". $ms->getServiceLevelByName($object->sl_name)
                            ."', '". $object->pipe_src_target ."', '". $object->pipe_dst_target ."', '". $object->pipe_direction ."', '". $object->pipe_active ."')");
                $id = $db->db_getid();
                $filters = preg_split('/#/', $object->filters);
                foreach ($filters as $filter) {
                    $db->query("INSERT INTO TABLEPREFIXassign_filters_to_pipes (apf_pipe_idx, apf_filter_idx) "
                            ."VALUES ('". $id ."', '". $ms->getFilterByName($filter) ."')");
                }
                break;
        }

    } // loadConfig()

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
            $db->db_truncate_table("TABLEPREFIXl7_protocols");
            $db->db_truncate_table("TABLEPREFIXassign_l7_protocols_to_filters");
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

    private function add($option, $object)
    {
        $object = addslashes(serialize($object));
        $this->string.= $option .":". $object ."\n";

    } // add()

    public function updateL7Protocols()
    {

        /* If authentication is enabled, check permissions */
        if ($ms->getOption("authentication") == "Y" &&
            !$ms->checkPermissions("user_manage_options")) {

            $ms->printError(
                "<img src=\"". ICON_OPTIONS ."\" alt=\"options icon\" />&nbsp;". _("Manage Options"),
                _("You do not have enough permissions to access this module!")
            );
            return 0;

        }

        if (!isset($_GET['doit'])) {

            $ms->startTable(
                "<img src=\"". ICON_UPDATE ."\" alt=\"option icon\" />&nbsp;"
                . _("Update Layer7 Protocols")
            );
?>
                <form action="<?php print $ms->self ."?mode=". $ms->mode ."&amp;doit=1"; ?>" method="POST">
                <table style="width: 100%; text-align: center;" class="withborder2">
                <tr>
                <td>
                <?php print _("Please enter the path in the local filesystem, where to find .pat files of layer7 iptables match."); ?>
                </td>
                </tr>
                <tr>
                <td>
                <input type="text" name="basedir" size="30" value="/etc/l7-protocols">
                <input type="submit" value="Submit">
                </td>
                </tr>
                </table>
                </form>
                <?php
                $ms->closeTable();

        } else {

            $ms->startTable("<img src=\"". ICON_UPDATE ."\" alt=\"option icon\" />&nbsp;". _("Update Layer7 Protocols"));
?>
                <table style="width: 100%; text-align: center;" class="withborder2">
                <tr>
                <td>
<?php
            $protocols = array();
            $retval = $this->findPatFiles($protocols, $_POST['basedir']);
            if ($retval == "") {
?>
Updating...<br />
<br />
<?php
                $new = 0;
                $deleted = 0;

                foreach ($protocols as $protocol) {

                    // Check if already in database
                    if (!$db->db_fetchSingleRow(
                        "SELECT l7proto_idx FROM TABLEPREFIXl7_protocols WHERE "
                                ."l7proto_name LIKE '". $protocol ."'"
                    )) {
                        $db->query(
                            "INSERT INTO TABLEPREFIXl7_protocols (l7proto_name) VALUES "
                                ."('". $protocol ."')"
                        );

                        $new++;
                    }
                }

                if (count($protocols) > 0) {

                    $result = $db->query("SELECT l7proto_idx, l7proto_name FROM TABLEPREFIXl7_protocols");
                    while ($row = $result->fetch()) {

                        if (!in_array($row->l7proto_name, $protocols)) {

                            $db->query(
                                "DELETE FROM TABLEPREFIXl7_protocols WHERE l7proto_idx='". $row->l7proto_idx ."'"
                            );
                            $deleted++;

                        }
                    }
                }
                ?>
                    <?php print $new ." ". _("Protocols have been added."); ?><br />
                    <?php print $deleted ." ". _("Protocols have been deleted."); ?><br />
                    <?php
            } else {
                ?>
                    <?php print $retval; ?>
                    <?php
            }
            ?>
                <br />
                <a href="<?php print $ms->self; ?>"><? print _("Back"); ?></a>
                </td>
                </tr>
                </table>
                <?php
                $ms->closeTable();

        }

    } // updateL7Protocols()

    private function findPatFiles(&$files, $path)
    {

        if (is_dir($path) && $dir = opendir($path)) {

            while ($file = readdir($dir)) {

                if ($file != "." && $file != "..") {

                    if (is_dir($path ."/". $file)) {

                        $this->findPatFiles($files, $path ."/". $file);

                    }

                    if (preg_match("/\.pat$/", $file)) {

                        array_push($files, str_replace(".pat", "", $file));

                    }

                }

            }

            return "";

        } else {

            return "<font style=\"color: '#FF0000';\">". _("Can't access directory") ." ". $path ."!</font><br />\n";

        }

    } // findPatFiles()

    /**
     * template function which will be called from the target listing template
     */
    public function smartyOptionsServiceLevelList($params, $content, &$smarty, &$repeat)
    {
        $index = $smarty->getTemplateVars('smarty.IB.sl_list.index');
        if (!$index) {
            $index = 0;
        }

        if ($index >= count($this->service_levels)) {
            $repeat = false;
            return $content;
        }

        $sl = $this->service_levels[$index];

        $smarty->assign('sl', $sl);

        $index++;
        $smarty->assign('smarty.IB.sl_list.index', $index);
        $repeat = true;
        return $content;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
