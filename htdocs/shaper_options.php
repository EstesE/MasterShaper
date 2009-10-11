<?php

/***************************************************************************
 *
 * Copyright (c) by Andreas Unterkircher, unki@netshadow.at
 * All rights reserved
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
 *
 ***************************************************************************/

class MASTERSHAPER_OPTIONS extends MASTERSHAPER_PAGE {

   /**
    * MASTERSHAPER_OPTIONS constructor
    *
    * Initialize the MASTERSHAPER_OPTIONS class
    */
   public function __construct()
   {
      $this->page = 'user_manage_rights';

   } // __construct()

   /* interface output */
   public function showList()
   {
      if($this->is_storing())
         $this->store();

      global $ms, $db, $tmpl;

      $this->avail_service_levels = Array();
      $this->service_levels = Array();

      $res_sl = $db->db_query("
         SELECT *
         FROM ". MYSQL_PREFIX ."service_levels
         ORDER BY sl_name ASC
      ");

      $cnt_sl = 0;

      while($sl = $res_sl->fetchrow()) {
         $this->avail_service_levels[$cnt_sl] = $sl->sl_idx;
         $this->service_levels[$sl->sl_idx] = $sl;
         $cnt_sl++;
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

      $tmpl->register_block("service_level_list", array(&$this, "smarty_opt_sl_list"));
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

      if($_POST['qdisc'] == "ESFQ") {
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
      if($ms->getOption("authentication") == "Y" &&
	 !$ms->checkPermissions("user_manage_options")) {

	 $ms->printError("<img src=\"". ICON_OPTIONS ."\" alt=\"options icon\" />&nbsp;". _("Manage Options"), _("You do not have enough permissions to access this module!"));
	 return 0;

      }

      if(!isset($_GET['restoreit'])) {

         $ms->startTable("<img src=\"". ICON_OPTIONS ."\" alt=\"option icon\" />&nbsp;". _("Restore MasterShaper Configuration"));

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
      }
      else {

         $this->resetConfig(1);

         $config = Array();

         if($_FILES['ms_config']) {

            if($config = fopen($_FILES['ms_config']['tmp_name'], "r")) {

               while($line = fgets($config, 2048)) {

                  $line = trim($line);

                  if(($line != "") && (!preg_match("/^#/", $line))) {

                     list($set, $parameters) = split(":", $line, 2);

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
      switch($set) {
         case 'Settings':
            $db->db_query("INSERT INTO ". MYSQL_PREFIX ."settings (setting_key, setting_value) "
                               ."VALUES ('". $object->setting_key ."', '". $object->setting_value ."')");
            break;
	 case 'Users':
	    $db->db_query("INSERT INTO ". MYSQL_PREFIX ."users (user_idx, user_name, user_pass, user_manage_chains, "
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
            $db->db_query("INSERT INTO ". MYSQL_PREFIX ."protocols (proto_name, proto_number, "
                               ."proto_user_defined) VALUES ('". $object->proto_name ."', "
                               ."'". $object->proto_name ."', 'Y')");
            break;
         case 'Ports':
            $db->db_query("INSERT INTO ". MYSQL_PREFIX ."ports (port_name, port_desc, port_number, "
                               ."port_user_defined) VALUES ('". $object->port_name
                               ."', '". $object->port_desc ."', '". $object->port_number 
                               ."', 'Y')");
            break;
         case 'Servicelevels':
            $db->db_query("INSERT INTO ". MYSQL_PREFIX ."service_levels (sl_name, sl_htb_bw_in_rate, "
                               ."sl_htb_bw_in_ceil, sl_htb_bw_in_burst, sl_htb_bw_out_rate, "
                               ."sl_htb_bw_out_ceil, sl_htb_bw_out_burst, sl_htb_priority, "
                               ."sl_hfsc_in_umax, sl_hfsc_in_dmax, sl_hfsc_in_rate, sl_hfsc_in_ulrate, "
                               ."sl_hfsc_out_umax, sl_hfsc_out_dmax, sl_hfsc_out_rate, sl_hfsc_out_ulrate, "
			       ."sl_cbq_in_rate, sl_cbq_in_priority, sl_cbq_out_rate, sl_cbq_out_priority, "
			       ."sl_cbq_bounded, sl_qdisc, sl_netem_delay, sl_netem_jitter, sl_netem_random, "
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
			       ."', '". $object->sl_cbq_in_rate ."', '". $object->sl_cbq_in_priority
			       ."', '". $object->sl_cbq_out_rate ."', '". $object->sl_cbq_out_priority
			       ."', '". $object->sl_cbq_bounded ."', "
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
            $db->db_query("INSERT INTO ". MYSQL_PREFIX ."targets (target_name, target_match, target_ip, target_mac) "
                               ."VALUES ('". $object->target_name ."', '". $object->target_match ."', "
			       ."'". $object->target_ip ."', '". $object->target_mac ."')");

            $id = $db->db_getid();
	    $members = split('#', $object->target_members);
	    foreach($members as $member) {
	       $db->db_query("INSERT INTO ". MYSQL_PREFIX ."assign_target_groups (atg_group_idx, atg_target_idx) "
				    ."VALUES ('". $id ."', '". $ms->getTargetByName($member) ."')");
	    }
            break;
	 case 'L7Proto':
	    $db->db_query("INSERT INTO ". MYSQL_PREFIX ."l7_protocols (l7proto_idx, l7proto_name) "
	                       ."VALUES ('". $object->l7proto_idx ."', '". $object->l7proto_name ."')");
	    break;
         case 'Filters':
            $db->db_query("INSERT INTO ". MYSQL_PREFIX ."filters (filter_idx, filter_name, filter_protocol_id, filter_tos, "
                               ."filter_tcpflag_syn, filter_tcpflag_ack, filter_tcpflag_fin, "
			       ."filter_tcpflag_rst, filter_tcpflag_urg, filter_tcpflag_psh, "
			       ."filter_packet_length, filter_p2p_edk, filter_p2p_kazaa, "
			       ."filter_p2p_dc, filter_p2p_gnu, filter_p2p_bit, filter_p2p_apple, "
			       ."filter_p2p_soul, filter_p2p_winmx, filter_p2p_ares, "
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
			       ."'". $object->filter_p2p_edk ."', "
			       ."'". $object->filter_p2p_kazaa ."', "
			       ."'". $object->filter_p2p_dc ."', "
			       ."'". $object->filter_p2p_gnu ."', "
			       ."'". $object->filter_p2p_bit ."', "
			       ."'". $object->filter_p2p_apple ."', "
			       ."'". $object->filter_p2p_soul ."', "
			       ."'". $object->filter_p2p_winmx ."', "
			       ."'". $object->filter_p2p_ares ."', "
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
            $ports = split('#', $object->filter_ports);
            foreach($ports as $port) {
               $db->db_query("INSERT INTO ". MYSQL_PREFIX ."assign_ports_to_filters (afp_filter_idx, afp_port_idx) "
                                  ."VALUES ('". $id ."', '". $ms->getPortByName($port) ."')");
            }
	    $l7protos = split('#', $object->l7_protocols);
	    foreach($l7protos as $l7proto) {
	       $db->db_query("INSERT INTO ". MYSQL_PREFIX ."assign_l7_protocols_to_filters (afl7_filter_idx, afl7_l7proto_idx) "
	                          ."VALUES ('". $id ."', '". $ms->getL7ProtocolByName($l7proto) ."')");
            }
            break;
         case 'Chains':
            $db->db_query("INSERT INTO ". MYSQL_PREFIX ."chains (chain_name, chain_active, chain_sl_idx, "
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
            $db->db_query("INSERT INTO ". MYSQL_PREFIX ."pipes (pipe_name, pipe_sl_idx, "
                               ."pipe_position, pipe_src_target, pipe_dst_target, pipe_direction, pipe_active)
             VALUES ('". $object->pipe_name 
                               ."', '". $ms->getServiceLevelByName($object->sl_name) 
                               ."', '". $object->pipe_position ."', '". $object->pipe_src_target ."', '". $object->pipe_dst_target ."', '". $object->pipe_direction ."', '". $object->pipe_active ."')");
            $id = $db->db_getid();
            $filters = split('#', $object->filters);
            foreach($filters as $filter) {
               $db->db_query("INSERT INTO ". MYSQL_PREFIX ."assign_filters_to_pipes (apf_pipe_idx, apf_filter_idx) "
                                  ."VALUES ('". $id ."', '". $ms->getFilterByName($filter) ."')");
            }
            break;
      }

   } // loadConfig()

   /* remove existing configuration */
   public function resetConfig($doit = 0)
   {

      /* If authentication is enabled, check permissions */
      if($ms->getOption("authentication") == "Y" &&
	 !$ms->checkPermissions("user_manage_options")) {

	 $ms->printError("<img src=\"". ICON_OPTIONS ."\" alt=\"options icon\" />&nbsp;". _("Manage Options"), _("You do not have enough permissions to access this module!"));
	 return 0;

      }
					     
      if(!isset($_GET['doit']) && !$doit) {
         $ms->printYesNo("<img src=\"". ICON_OPTIONS ."\" alt=\"option icon\" />&nbsp;". _("Reset MasterShaper Configuration"),
	                          _("This operation will completely reset your current MasterShaper configuration!<br />All your current settings, rules, chains, pipes, ... will be deleted !!!<br /><br />Of course this will also reset the version information of MasterShaper, you will be<br />forwarded to MasterShaper Installer after you have confirmed this procedure."));
      }
      else {

         $db->db_truncate_table(MYSQL_PREFIX ."assign_ports_to_filters");
         $db->db_truncate_table(MYSQL_PREFIX ."assign_filters_to_pipes");
         $db->db_truncate_table(MYSQL_PREFIX ."assign_target_groups");
         $db->db_truncate_table(MYSQL_PREFIX ."chains");
         $db->db_truncate_table(MYSQL_PREFIX ."pipes");
         $db->db_truncate_table(MYSQL_PREFIX ."service_levels");
         $db->db_truncate_table(MYSQL_PREFIX ."filters");
         $db->db_truncate_table(MYSQL_PREFIX ."settings"); 
         $db->db_truncate_table(MYSQL_PREFIX ."stats");
         $db->db_truncate_table(MYSQL_PREFIX ."targets");
         $db->db_truncate_table(MYSQL_PREFIX ."tc_ids");
	 $db->db_truncate_table(MYSQL_PREFIX ."l7_protocols");
	 $db->db_truncate_table(MYSQL_PREFIX ."assign_l7_protocols_to_filters");
	 $db->db_truncate_table(MYSQL_PREFIX ."users");
	 $db->db_truncate_table(MYSQL_PREFIX ."interfaces");
	 $db->db_truncate_table(MYSQL_PREFIX ."network_paths");
         $db->db_query("DELETE FROM ". MYSQL_PREFIX ."ports WHERE port_user_defined='Y'");
         $db->db_query("DELETE FROM ". MYSQL_PREFIX ."protocols WHERE proto_user_defined='Y'");

         /* If invoked by "Reset Configuration" and not "Restore Configuration" */
         if(isset($_GET['doit']))
	    $ms->goBack();

      }

   } // resetConfig()

   private function Add($option, $object)
   {
      $object = addslashes(serialize($object));
      $this->string.= $option .":". $object ."\n";
   } // Add()

   public function updateL7Protocols()
   {

      /* If authentication is enabled, check permissions */
      if($ms->getOption("authentication") == "Y" &&
	 !$ms->checkPermissions("user_manage_options")) {

	 $ms->printError("<img src=\"". ICON_OPTIONS ."\" alt=\"options icon\" />&nbsp;". _("Manage Options"), _("You do not have enough permissions to access this module!"));
	 return 0;

      }

      if(!isset($_GET['doit'])) {

         $ms->startTable("<img src=\"". ICON_UPDATE ."\" alt=\"option icon\" />&nbsp;". _("Update Layer7 Protocols"));
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

      }
      else {

	 $ms->startTable("<img src=\"". ICON_UPDATE ."\" alt=\"option icon\" />&nbsp;". _("Update Layer7 Protocols"));
?>
   <table style="width: 100%; text-align: center;" class="withborder2">
    <tr>
     <td>
<?php

	 $protocols = Array();

	 $retval = $this->findPatFiles($protocols, $_POST['basedir']);

	 if($retval == "") {
?>
      Updating...<br />
      <br />
<?php
	    $new = 0;
	    $deleted = 0;

	    foreach($protocols as $protocol) {

	       // Check if already in database
	       if(!$db->db_fetchSingleRow("SELECT l7proto_idx FROM ". MYSQL_PREFIX ."l7_protocols WHERE "
					      ."l7proto_name LIKE '". $protocol ."'")) {

		  $db->db_query("INSERT INTO ". MYSQL_PREFIX ."l7_protocols (l7proto_name) VALUES "
				     ."('". $protocol ."')");

		  $new++;

	       }
	    }

	    if(count($protocols) > 0) {

	       $result = $db->db_query("SELECT l7proto_idx, l7proto_name FROM ". MYSQL_PREFIX ."l7_protocols");
	       while($row = $result->fetchRow()) {

		  if(!in_array($row->l7proto_name, $protocols)) {

		     $db->db_query("DELETE FROM ". MYSQL_PREFIX ."l7_protocols WHERE l7proto_idx='". $row->l7proto_idx ."'");
		     $deleted++;

		  }
	       }
	    }
?>
      <?php print $new ." ". _("Protocols have been added."); ?><br />
      <?php print $deleted ." ". _("Protocols have been deleted."); ?><br />
<?php
	 }
	 else {
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

      if(is_dir($path) && $dir = opendir($path)) {

         while($file = readdir($dir)) {

           if($file != "." && $file != "..") {

              if(is_dir($path ."/". $file)) {
	      
                 $this->findPatFiles($files, $path ."/". $file);

	      }

	      if(preg_match("/\.pat$/", $file)) {

                 array_push($files, str_replace(".pat", "", $file));
		 
	      }

	   }

	 }

         return "";

      }
      else {

         return "<font style=\"color: '#FF0000';\">". _("Can't access directory") ." ". $path ."!</font><br />\n";

      }
      
   } // findPatFiles()

   /**
    * template function which will be called from the target listing template
    */
   public function smarty_opt_sl_list($params, $content, &$smarty, &$repeat)
   {
      global $tmpl;

      $index = $smarty->get_template_vars('smarty.IB.sl_list.index');
      if(!$index) {
         $index = 0;
      }

      if($index < count($this->avail_service_levels)) {

         $sl_idx = $this->avail_service_levels[$index];
         $sl =  $this->service_levels[$sl_idx];

         $tmpl->assign('sl_idx', $sl_idx);
         $tmpl->assign('sl_name', $sl->sl_name);

         $index++;
         $tmpl->assign('smarty.IB.sl_list.index', $index);
         $repeat = true;
      }
      else {
         $repeat =  false;
      }

      return $content;

   } // smarty_sl_list

} // class MASTERSHAPER_OPTIONS

$obj = new MASTERSHAPER_OPTIONS;
$obj->handler();

?>
