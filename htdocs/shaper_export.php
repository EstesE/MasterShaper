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

require_once "shaper.class.php";

class MASTERSHAPER_EXPORT {

   var $parent;
   var $db;

   /* Class constructor */
   function MASTERSHAPER_EXPORT()
   {
      $this->parent = new MASTERSHAPER;
      $this->db = $this->parent->db;

      $this->saveConfig();

   } // MASTERSHAPER_EXPORT()

   /**
    * export current MasterShaper ruleset & configuration
    *
    * Exports the whole MasterShaper ruleset & configuration into XML code
    * and sends it as downloadable file to the browser
    *    
    */
   function saveConfig()
   {
      if($this->parent->getOption("authentication") == "Y" &&
         !$this->parent->checkPermissions("user_manage_options")) {

         print _("You do not have enough permissions to access this module!");
         return 0;

      }

      $config = new DOMDocument('1.0');
      $config->formatOutput = true;

      $comment = $config->createComment("MasterShaper configured, dumped on ". strftime("%Y-%m-%d %H:%M"));
      $config->appendChild($comment);

      $root = $config->createElement('config');
      $config->appendChild($root);
      $settings = $config->createElement('settings');
      $settings = $root->appendChild($settings);

      // Settings
      $result = $this->db->db_query("SELECT * FROM ". MYSQL_PREFIX ."settings");
      while($row = $result->fetchRow()) {

         $temp = $config->createElement($row->setting_key, $row->setting_value);
         $settings->appendChild($temp);

      }

      $config->appendChild($root);
      $users = $config->createElement('users');
      $users = $root->appendChild($users);

      // Users
      $result = $this->db->db_query("SELECT * FROM ". MYSQL_PREFIX ."users", MDB2_FETCHMODE_ASSOC);
      while($row = $result->fetchRow()) {

         $user = $config->createElement('user', $row['user_idx']);
         $user = $users->appendChild($user);

         $keys = array_keys($row);
         foreach($keys as $key) {
            $temp = $config->createElement(htmlspecialchars($key), htmlspecialchars($row[$key]));
            $user->appendChild($temp);
         }
      }

      Header("Content-type: text/xml; charset=utf-8");
      print $config->saveXML();
      exit;

      // User definied protocols
      $result = $this->db->db_query("
         SELECT proto_name, proto_number, proto_desc
         FROM ". MYSQL_PREFIX ."protocols
         WHERE proto_user_defined='Y'
      ");

      while($row = $result->fetchRow()) {

         $this->Add("Protocols", $row);

      }

      // User definied ports 
      $result = $this->db->db_query("
         SELECT port_name, port_desc, port_number
         FROM ". MYSQL_PREFIX ."ports 
         WHERE port_user_defined='Y'
      ");

      while($row = $result->fetchRow()) {

         $this->Add("Ports", $row);

      }

      // Service Levels 
      $result = $this->db->db_query("
         SELECT sl_name, sl_htb_bw_in_rate, sl_htb_bw_in_ceil,
         sl_htb_bw_in_burst, sl_htb_bw_out_rate, sl_htb_bw_out_ceil,
         sl_htb_bw_out_burst, sl_htb_priority, sl_hfsc_in_umax,
         sl_hfsc_in_dmax, sl_hfsc_in_rate, sl_hfsc_in_ulrate,
         sl_hfsc_out_umax, sl_hfsc_out_dmax, sl_hfsc_out_rate,
         sl_hfsc_out_ulrate, sl_cbq_in_rate, sl_cbq_in_priority,
         sl_cbq_out_rate, sl_cbq_out_priority, sl_cbq_bounded,
         sl_netem_delay, sl_netem_jitter, sl_netem_random, sl_qdisc,
         sl_netem_distribution, sl_netem_loss, sl_netem_duplication,
         sl_netem_gap, sl_netem_reorder_percentage,
         sl_netem_reorder_correlation, sl_esfq_perturb, sl_esfq_limit,
         sl_esfq_depth, sl_esfq_divisor, sl_esfq_hash

         FROM ". MYSQL_PREFIX ."service_levels
      ");

      while($row = $result->fetchRow()) {

         $this->Add("Servicelevels", $row);

      }

      // Targets, reverse order so groups are on the last position! 
      $result = $this->db->db_query("
         SELECT target_idx, target_name, target_match, target_ip,
         target_mac

         FROM ". MYSQL_PREFIX ."targets ORDER BY target_match DESC
      ");

      while($row = $result->fetchRow()) {

         $members = $this->db->db_query("

            SELECT a.target_name FROM ". MYSQL_PREFIX ."targets a,
            ". MYSQL_PREFIX ."assign_target_groups b
            
            WHERE b.atg_group_idx='". $row->target_idx ."'
            AND b.atg_target_idx=a.target_idx

         ");

         $string = '';

         while($member = $members->fetchRow()) {

            $string.= $member->target_name ."#";
          }

         $string = substr($string, 0, strlen($string)-1);
         $row->target_members = $string;
         $this->Add("Targets", $row);

      }

      /* L7 Protocol definitions */
      $result = $this->db->db_query("
         SELECT l7proto_idx, l7proto_name FROM ". MYSQL_PREFIX ."l7_protocols
         ORDER BY l7proto_name ASC
      ");

      while($row = $result->fetchRow()) {

         $this->Add("L7Proto", $row);

      }
		
      // Filters 
      $result = $this->db->db_query("
         SELECT filter_idx, filter_name, filter_protocol_id, filter_tos,
         filter_tcpflag_syn, filter_tcpflag_ack, filter_tcpflag_fin,
         filter_tcpflag_rst, filter_tcpflag_urg, filter_tcpflag_psh,
         filter_packet_length, filter_p2p_edk, filter_p2p_kazaa,
         filter_p2p_dc, filter_p2p_gnu, filter_p2p_bit, filter_p2p_apple,
         filter_p2p_soul, filter_p2p_winmx, filter_p2p_ares,
         filter_time_use_range, filter_time_start, filter_time_stop,
         filter_time_day_mon, filter_time_day_tue, filter_time_day_wed,
         filter_time_day_thu, filter_time_day_fri, filter_time_day_sat,
         filter_time_day_sun, filter_match_ftp_data, filter_active 
         FROM ". MYSQL_PREFIX ."filters
      ");

      while($row = $result->fetchRow()) {

         $row->filter_protocol_id = $this->parent->getProtocolById($row->filter_protocol_id);

         $ports = $this->db->db_query("
            SELECT b.port_name FROM ". MYSQL_PREFIX ."assign_ports a,
             ". MYSQL_PREFIX ."ports b 

            WHERE a.afp_filter_idx='". $row->filter_idx ."'
            AND b.port_idx=a.afp_port_idx
         ");

         $l7protos = $this->db->db_query("
            SELECT b.l7proto_name FROM ". MYSQL_PREFIX ."assign_l7_protocols a,
            ". MYSQL_PREFIX ."l7_protocols b

            WHERE a.afl7_filter_idx='". $row->filter_idx ."' 
            AND b.l7proto_idx=a.afl7_l7proto_idx
         ");

         $string = '';

         while($port = $ports->fetchRow()) {
   
            $string .= $port->port_name ."#";
   
         }
   
         $string = substr($string, 0, strlen($string)-1);
         $row->filter_ports = $string;
         $string = '';

         while($l7proto = $l7protos->fetchRow()) {

            $string .= $l7proto->l7proto_name ."#";

         }

         $string = substr($string, 0, strlen($string)-1);
         $row->l7_protocols = $string;
         $this->Add("Filters", $row);

      }

      // Chains 
      $result = $this->db->db_query("
         SELECT chain_name, chain_sl_idx, chain_fallback_idx, chain_src_target,
         chain_dst_target, chain_direction, chain_position, chain_active 
         FROM ". MYSQL_PREFIX ."chains
      ");

      while($row = $result->fetchRow()) {

         $row->sl_name  = $this->parent->getServiceLevelName($row->chain_sl_idx);
         $row->fb_name  = $this->parent->getServiceLevelName($row->chain_fallback_idx);
         $row->src_name = $this->parent->getTargetName($row->chain_src_target);
         $row->dst_name = $this->parent->getTargetName($row->chain_dst_target);
         $this->Add("Chains", $row);

      }

      // Pipes 
      $result = $this->db->db_query("
         SELECT pipe_idx, pipe_name, pipe_chain_idx, pipe_sl_idx, pipe_direction,
         pipe_position, pipe_src_target, pipe_dst_target, pipe_direction, pipe_active 

         FROM ". MYSQL_PREFIX ."pipes
      ");

      while($row = $result->fetchRow()) {

         $string = "";
         $filters = $this->db->db_query("
            SELECT b.filter_name FROM ". MYSQL_PREFIX ."assign_filters a,
            ". MYSQL_PREFIX ."filters b 
            
            WHERE a.apf_pipe_idx='". $row->pipe_idx ."'
            AND a.apf_filter_idx=b.filter_idx
         ");

         while($filter = $filters->fetchRow()) {

            $string .= $filter->filter_name ."#";

         }

         $string = substr($string, 0, strlen($string)-1);
         $row->chain_name = $this->parent->getChainName($row->pipe_chain_idx);
         $row->sl_name    = $this->parent->getServiceLevelName($row->pipe_sl_idx);
         $row->filters   = $string;
         $this->Add("Pipes", $row);

      }
		
      /* create output */
      $this->string = "# MasterShaper ". $this->parent->version ." configuration\n" 
                     ."# Andreas Unterkircher, unki@netshadow.at\n"
                     ."# \n"
                     ."# dumped on ". strftime("%Y-%m-%d %H:%M") ."\n\n" . $this->string;
		
      Header("Content-Type: application/octet-stream");
      Header("Content-Transfer-Encoding: binary\n");
      $user_agent = strtolower ($_SERVER["HTTP_USER_AGENT"]);
      if ((is_integer (strpos($user_agent, "msie"))) && (is_integer (strpos($user_agent, "win"))))
         Header("Content-Disposition: inline; filename=\"ms_config_". strftime("%Y%m%d") .".cfg\"");
      else
         Header("Content-Disposition: attachement; filename=\"ms_config_". strftime("%Y%m%d") .".cfg\"");
      Header("Content-Length: ". strlen($this->string));
      Header("Content-Description: PHP4 Download Data" );
      Header("Accept-Ranges: bytes");
      Header("Pragma: no-cache");
      Header("Cache-Control: no-cache, must-revalidate");
      Header("Cache-Control: post-check=0, pre-check=0", false);
      Header("Cache-Control: private");
      Header("Connection: close");

      print $this->string;

   } // saveConfig()

   function Add($option, $object)
   {
      $object = addslashes(serialize($object));
      $this->string.= $option .":". $object ."\n";
   } // Add()

}

$obj = new MASTERSHAPER_EXPORT();
$obj->show();

?>
