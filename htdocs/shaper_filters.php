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

class MASTERSHAPER_FILTERS {

   var $db;
   var $parent;
   var $tmpl;

   /* Class constructor */
   function MASTERSHAPER_FILTERS($parent)
   {
      $this->parent = &$parent;
      $this->db = &$parent->db;
      $this->tmpl = &$parent->tmpl;

   } // MASTERSHAPER_FILTERS()

   /* interface output */
   function show()
   {
      /* If authentication is enabled, check permissions */
      if($this->parent->getOption("authentication") == "Y" &&
         !$this->parent->checkPermissions("user_manage_filters")) {

         $this->parent->printError("<img src=\"". ICON_FILTERS ."\"
            alt=\"filter icon\" />&nbsp;". _("Manage Filters"),
            _("You do not have enough permissions to access this module!")
         );
         
         return 0;

      }

      if(!isset($_GET['mode'])) {
         $_GET['mode'] = "show";
      }
      if(!isset($_GET['idx']) ||
         (isset($_GET['idx']) && !is_numeric($_GET['idx'])))
         $_GET['idx'] = 0;
      
      switch($_GET['mode']) {
         default:
         case 'show':
            $this->showList();
            break;
         case 'new':
         case 'edit':
            $this->showEdit($_GET['idx']);
            break;
      }
   
   } // show()

   /**
    * display all filters
    */
   private function showList()
   {
      $this->avail_filters = Array();
      $this->filters = Array();

      $res_filters = $this->db->db_query("
         SELECT *
         FROM ". MYSQL_PREFIX ."filters
         ORDER BY filter_name ASC
      ");
   
      $cnt_filters = 0;

      while($filter = $res_filters->fetchrow()) {
         $this->avail_filters[$cnt_filters] = $filter->filter_idx;
         $this->filters[$filter->filter_idx] = $filter;
         $cnt_filters++;
      }

      $this->tmpl->register_block("filter_list", array(&$this, "smarty_filter_list"));
      $this->tmpl->show("filters_list.tpl");

   } // showList()

   /** 
    * filter for handling
    */
   private function showEdit($idx)
   {
      if($idx != 0) {
         $filter = $this->db->db_fetchSingleRow("
            SELECT *
            FROM ". MYSQL_PREFIX ."filters
            WHERE
               filter_idx='". $idx ."'
         ");
      }
      else {
         $filter->filter_active = 'Y'; 
      }

      $this->tmpl->assign('filter_idx', $idx);
      $this->tmpl->assign('filter_mode', $this->parent->getOption("filter"));
      $this->tmpl->assign('filter_name', $filter->filter_name);
      $this->tmpl->assign('filter_active', $filter->filter_active);
      $this->tmpl->assign('filter_protocol_id', $filter->filter_protocol_id);
      $this->tmpl->assign('filter_tos', $filter->filter_tos);
      $this->tmpl->assign('filter_tcpflag_syn', $filter->filter_tcpflag_syn);
      $this->tmpl->assign('filter_tcpflag_ack', $filter->filter_tcpflag_ack);
      $this->tmpl->assign('filter_tcpflag_fin', $filter->filter_tcpflag_fin);
      $this->tmpl->assign('filter_tcpflag_rst', $filter->filter_tcpflag_rst);
      $this->tmpl->assign('filter_tcpflag_urg', $filter->filter_tcpflag_urg);
      $this->tmpl->assign('filter_tcpflag_psh', $filter->filter_tcpflag_psh);
      $this->tmpl->assign('filter_packet_length', $filter->filter_packet_length);
      $this->tmpl->assign('filter_p2p_edk', $filter->filter_p2p_edk);
      $this->tmpl->assign('filter_p2p_kazaa', $filter->filter_p2p_kazaa);
      $this->tmpl->assign('filter_p2p_dc', $filter->filter_p2p_dc);
      $this->tmpl->assign('filter_p2p_gnu', $filter->filter_p2p_gnu);
      $this->tmpl->assign('filter_p2p_bit', $filter->filter_p2p_bit);
      $this->tmpl->assign('filter_p2p_apple', $filter->filter_p2p_apple);
      $this->tmpl->assign('filter_p2p_soul', $filter->filter_p2p_soul);
      $this->tmpl->assign('filter_p2p_winmx', $filter->filter_p2p_winmx);
      $this->tmpl->assign('filter_p2p_ares', $filter->filter_p2p_ares);
      $this->tmpl->assign('filter_time_use_range', $filter->filter_time_use_range);
      $this->tmpl->assign('filter_time_start', $filter->filter_time_start);
      $this->tmpl->assign('filter_time_stop', $filter->filter_time_stop);
      $this->tmpl->assign('filter_time_day_mon', $filter->filter_time_day_mon);
      $this->tmpl->assign('filter_time_day_tue', $filter->filter_time_day_tue);
      $this->tmpl->assign('filter_time_day_wed', $filter->filter_time_day_wed);
      $this->tmpl->assign('filter_time_day_thu', $filter->filter_time_day_thu);
      $this->tmpl->assign('filter_time_day_fri', $filter->filter_time_day_fri);
      $this->tmpl->assign('filter_time_day_sat', $filter->filter_time_day_sat);
      $this->tmpl->assign('filter_time_day_sun', $filter->filter_time_day_sun);
      $this->tmpl->assign('filter_match_ftp_data', $filter->filter_match_ftp_data);
      $this->tmpl->assign('filter_match_sip', $filter->filter_match_sip);

      $this->tmpl->register_function("protocol_select_list", array(&$this, "smarty_protocol_select_list"), false);
      $this->tmpl->register_function("port_select_list", array(&$this, "smarty_port_select_list"), false);
      $this->tmpl->register_function("l7_select_list", array(&$this, "smarty_l7_select_list"), false);
      $this->tmpl->show("filters_edit.tpl");

   } // showEdit()

   /**
    * handle updates
    */
   public function store()
   {
      isset($_POST['filter_new']) && $_POST['filter_new'] == 1 ? $new = 1 : $new = NULL;

      if(!isset($_POST['filter_name']) || $_POST['filter_name'] == "") {
         return _("Please enter a filter name!");
      }
      if(isset($new) && $this->checkFilterExists($_POST['filter_name'])) {
         return _("A filter with that name already exists!");
      }
      if(!isset($new) && $_POST['namebefore'] != $_POST['filter_name'] &&
         $this->checkFilterExists($_POST['filter_name'])) {
         return _("A filter with that name already exists!");
      }
      if($_POST['filter_protocol_id'] == -1 &&
         count($_POST['used']) <= 1 &&
         $_POST['filter_tos'] == -1 &&
         !$_POST['filter_tcpflag_syn'] &&
         !$_POST['filter_tcpflag_ack'] &&
         !$_POST['filter_tcpflag_fin'] &&
         !$_POST['filter_tcpflag_rst'] &&
         !$_POST['filter_tcpflag_urg'] &&
         !$_POST['filter_tcpflag_psh'] &&
         !$_POST['filter_packet_length'] &&
         !$_POST['filter_p2p_edk'] &&
         !$_POST['filter_p2p_kazaa'] &&
         !$_POST['filter_p2p_dc'] &&
         !$_POST['filter_p2p_gnu'] &&
         !$_POST['filter_p2p_bit'] &&
         !$_POST['filter_p2p_apple'] &&
         !$_POST['filter_p2p_soul'] &&
         !$_POST['filter_p2p_winmx'] &&
         !$_POST['filter_p2p_ares'] &&
         !$_POST['filter_time_use_range'] &&
         !$_POST['filter_time_day_mon'] &&
         !$_POST['filter_time_day_tue'] &&
         !$_POST['filter_time_day_wed'] &&
         !$_POST['filter_time_day_thu'] &&
         !$_POST['flter_time_day_fri'] &&
         !$_POST['filter_time_day_sat'] &&
         !$_POST['filter_time_day_sun'] &&
         !$_POST['filter_match_sip'] &&
         count($_POST['filter_l7_used']) <= 1) {
         return _("This filter has nothing to do. Please select at least one match!");
      }
      /* Ports can only be used with TCP, UDP or IP protocol */
      if(count($_POST['used']) > 1 &&
         (
            $this->parent->getProtocolNumberById($_POST['filter_protocol_id']) != 4 &&
            $this->parent->getProtocolNumberById($_POST['filter_protocol_id']) != 17 &&
            $this->parent->getProtocolNumberById($_POST['filter_protocol_id']) != 6
         )) {
         return _("Ports can only be used in combination with IP, TCP or UDP protocol!");
      }
      /* TCP-flags can only be used with TCP protocol */
      if((
            $_POST['filter_tcpflag_syn'] ||
            $_POST['filter_tcpflag_ack'] ||
            $_POST['filter_tcpflag_fin'] ||
            $_POST['filter_tcpflag_rst'] ||
            $_POST['filter_tcpflag_urg'] ||
            $_POST['filter_tcpflag_psh']
         ) &&
         $this->parent->getProtocolNumberById($_POST['filter_protocol_id']) != 6) {
         return _("TCP-Flags can only be used in combination with TCP protocol!");
      }
      /* ipp2p can only be used with no ports, no l7 filters and tcp &| udp protocol */
      if((
            $_POST['filter_p2p_edk'] ||
            $_POST['filter_p2p_kazaa'] ||
            $_POST['filter_p2p_dc'] ||
            $_POST['filter_p2p_gnu'] || 
            $_POST['filter_p2p_bit'] ||
            $_POST['filter_p2p_apple'] ||
            $_POST['filter_p2p_soul'] ||
            $_POST['filter_p2p_winmx'] ||
            $_POST['filter_p2p_ares']
         ) &&
         (
            count($_POST['used']) > 1 || 
            (
               (
                  $this->parent->getProtocolNumberById(
                     $_POST['filter_protocol_id']) != 17 &&
                  $this->parent->getProtocolNumberById($_POST['filter_protocol_id']) != 6
               ) &&
               $_POST['filter_protocol_id'] != -1
            ) ||
            count($_POST['filter_l7_used']) > 1)) {
         return _("IPP2P match can only be used with no ports select and only with protocols TCP or UDP or completly ignoring protocols!<br />Also IPP2P can not be used in combination with layer7 filters.");
      }
      /* layer7 protocol match can only be used with no ports and no tcp &| udp protocols */
      if(count($_POST['filter_l7_used']) > 1 &&
         $_POST['filter_protocol_id'] != -1) {
            return _("Layer7 match can only be used with no ports select and no protocol definitions!");
      }
      $start_time = strtotime(sprintf("%04d-%02d-%02d %02d:%02d:00", 
         $_POST['filter_time_start_year'],
         $_POST['filter_time_start_month'],
         $_POST['filter_time_start_day'],
         $_POST['filter_time_start_hour'], 
         $_POST['filter_time_start_minute']));
      $stop_time = strtotime(sprintf("%04d-%02d-%02d %02d:%02d:00",
         $_POST['filter_time_stop_year'],
         $_POST['filter_time_stop_month'],
         $_POST['filter_time_stop_day'],
         $_POST['filter_time_stop_hour'],
         $_POST['filter_time_stop_minute']));

      if(isset($new)) {
         $this->db->db_query("
            INSERT INTO ". MYSQL_PREFIX ."filters (
               filter_name, filter_protocol_id, filter_tos,
               filter_tcpflag_syn, filter_tcpflag_ack,
               filter_tcpflag_fin, filter_tcpflag_rst,
               filter_tcpflag_urg, filter_tcpflag_psh, 
               filter_packet_length, filter_p2p_edk,
               filter_p2p_kazaa, filter_p2p_dc, 
               filter_p2p_gnu, filter_p2p_bit, filter_p2p_apple,
               filter_p2p_soul, filter_p2p_winmx, filter_p2p_ares,
               filter_time_use_range, filter_time_start,
               filter_time_stop, filter_time_day_mon, 
               filter_time_day_tue, filter_time_day_wed,
               filter_time_day_thu, filter_time_day_fri,
               filter_time_day_sat, filter_time_day_sun, 
               filter_match_ftp_data, filter_match_sip,
               filter_active
            ) VALUES (
               '". $_POST['filter_name'] ."',
               '". $_POST['filter_protocol_id'] ."',
               '". $_POST['filter_tos'] ."', 
               '". $_POST['filter_tcpflag_syn'] ."',
               '". $_POST['filter_tcpflag_ack'] ."',
               '". $_POST['filter_tcpflag_fin'] ."',
               '". $_POST['filter_tcpflag_rst'] ."',
               '". $_POST['filter_tcpflag_urg'] ."',
               '". $_POST['filter_tcpflag_psh'] ."',
               '". $_POST['filter_packet_length'] ."',
               '". $_POST['filter_p2p_edk'] ."',
               '". $_POST['filter_p2p_kazaa'] ."',
               '". $_POST['filter_p2p_dc'] ."',
               '". $_POST['filter_p2p_gnu'] ."', 
               '". $_POST['filter_p2p_bit'] ."',
               '". $_POST['filter_p2p_apple'] ."',
               '". $_POST['filter_p2p_soul'] ."', 
               '". $_POST['filter_p2p_winmx'] ."',
               '". $_POST['filter_p2p_ares'] ."',
               '". $_POST['filter_time_use_range'] ."',
               '". $start_time ."',
               '". $stop_time ."',
               '". $_POST['filter_time_day_mon'] ."',
               '". $_POST['filter_time_day_tue'] ."',
               '". $_POST['filter_time_day_wed'] ."',
               '". $_POST['filter_time_day_thu'] ."',
               '". $_POST['filter_time_day_fri'] ."',
               '". $_POST['filter_time_day_sat'] ."',
               '". $_POST['filter_time_day_sun'] ."',
               '". $_POST['filter_match_ftp_data'] ."', 
               '". $_POST['filter_match_sip'] ."',
               '". $_POST['filter_active'] ."
            ')
         ");
                          
         $_POST['filter_idx'] = $this->db->db_getid();

      }
      else {
         switch($this->parent->getOption("filter")) {
            case 'ipt':
               $this->db->db_query("
                  UPDATE ". MYSQL_PREFIX ."filters 
                  SET
                     filter_name='". $_POST['filter_name'] ."', 
                     filter_protocol_id='". $_POST['filter_protocol_id'] ."', 
                     filter_tos='". $_POST['filter_tos'] ."', 
                     filter_tcpflag_syn='". $_POST['filter_tcpflag_syn'] ."', 
                     filter_tcpflag_ack='". $_POST['filter_tcpflag_ack'] ."', 
                     filter_tcpflag_fin='". $_POST['filter_tcpflag_fin'] ."', 
                     filter_tcpflag_rst='". $_POST['filter_tcpflag_rst'] ."', 
                     filter_tcpflag_urg='". $_POST['filter_tcpflag_urg'] ."', 
                     filter_tcpflag_psh='". $_POST['filter_tcpflag_psh'] ."', 
                     filter_packet_length='". $_POST['filter_packet_length'] ."', 
                     filter_p2p_edk='". $_POST['filter_p2p_edk'] ."', 
                     filter_p2p_kazaa='". $_POST['filter_p2p_kazaa'] ."', 
                     filter_p2p_dc='". $_POST['filter_p2p_dc'] ."', 
                     filter_p2p_gnu='". $_POST['filter_p2p_gnu'] ."', 
                     filter_p2p_bit='". $_POST['filter_p2p_bit'] ."', 
                     filter_p2p_apple='". $_POST['filter_p2p_apple'] ."', 
                     filter_p2p_soul='". $_POST['filter_p2p_soul'] ."', 
                     filter_p2p_winmx='". $_POST['filter_p2p_winmx'] ."', 
                     filter_p2p_ares='". $_POST['filter_p2p_ares'] ."', 
                     filter_time_use_range='". $_POST['filter_time_use_range'] ."', 
                     filter_time_start='". $start_time ."', 
                     filter_time_stop='". $stop_time ."', 
                     filter_time_day_mon='". $_POST['filter_time_day_mon'] ."', 
                     filter_time_day_tue='". $_POST['filter_time_day_tue'] ."', 
                     filter_time_day_wed='". $_POST['filter_time_day_wed'] ."', 
                     filter_time_day_thu='". $_POST['filter_time_day_thu'] ."',
                     filter_time_day_fri='". $_POST['filter_time_day_fri'] ."',
                     filter_time_day_sat='". $_POST['filter_time_day_sat'] ."',
                     filter_time_day_sun='". $_POST['filter_time_day_sun'] ."',
                     filter_match_ftp_data='". $_POST['filter_match_ftp_data'] ."', 
                     filter_match_sip='". $_POST['filter_match_sip'] ."', 
                     filter_active='". $_POST['filter_active'] ."' 
                  WHERE
                     filter_idx='". $_POST['filter_idx'] ."'
               ");
               break;
            case 'tc':
               $this->db->db_query("
                  UPDATE ". MYSQL_PREFIX ."filters
                  SET
                     filter_name='". $_POST['filter_name'] ."',
                     filter_protocol_id='". $_POST['filter_protocol_id'] ."',
                     filter_tos='". $_POST['filter_tos'] ."', 
                     filter_active='". $_POST['filter_active'] ."' 
                  WHERE
                     filter_idx='". $_POST['filter_idx'] ."'
               ");
               break;
         }
      }

      if($_POST['used']) {
         $this->db->db_query("
            DELETE FROM ". MYSQL_PREFIX ."assign_ports
            WHERE
               afp_filter_idx='". $_POST['filter_idx'] ."'
         ");

         foreach($_POST['used'] as $use) {
            if($use != "") {
               $this->db->db_query("
                  INSERT INTO ". MYSQL_PREFIX ."assign_ports (
                     afp_filter_idx, afp_port_idx
                  ) VALUES (
                     '". $_POST['filter_idx'] ."',
                     '". $use ."'
                  )
               ");
            }
         }

         if($_POST['filter_l7_used']) {
            $this->db->db_query("
               DELETE FROM ". MYSQL_PREFIX ."assign_l7_protocols
               WHERE
                  afl7_filter_idx='". $_POST['filter_idx'] ."'
            ");
            foreach($_POST['filter_l7_used'] as $use) {
               if($use != "") {
                  $this->db->db_query("
                     INSERT INTO ". MYSQL_PREFIX ."assign_l7_protocols (
                        afl7_filter_idx, afl7_l7proto_idx
                     ) VALUES (
                        '". $_POST['filter_idx'] ."',
                        '". $use ."'
                     )
                  ");
               }
            }
         }
   
         return "ok";

      }

   } // store()

   /**
    * delete filter
    */
   public function delete()
   {
      if(isset($_POST['idx']) && is_numeric($_POST['idx'])) {
         $idx = $_POST['idx'];
      
         $this->db->db_query("
            DELETE FROM ". MYSQL_PREFIX ."filters
            WHERE
               filter_idx='". $idx ."'
         ");
         $this->db->db_query("
            DELETE FROM ". MYSQL_PREFIX ."assign_ports
            WHERE
               afp_filter_idx='". $idx ."'
         ");
         $this->db->db_query("
            DELETE FROM ". MYSQL_PREFIX ."assign_l7_protocols
            WHERE
               afl7_filter_idx='". $idx ."'
         ");
         $this->db->db_query("
            DELETE FROM ". MYSQL_PREFIX ."assign_filters
            WHERE
               apf_filter_idx='". $idx ."'
         ");

         return "ok";

      } 
      
      return "unkown error";
      
   } // delete()

   /**
    * toggle filter status
    */
   public function toggleStatus()
   {
      if(isset($_POST['idx']) && is_numeric($_POST['idx'])) {
         $idx = $_POST['idx'];

         if($_POST['to'] == 1)
            $new_status = 'Y';
         else
            $new_status = 'N';

         $this->db->db_query("
            UPDATE ". MYSQL_PREFIX ."filters
            SET
               filter_active='". $new_status ."'
            WHERE
               filter_idx='". $idx ."'
         ");
      
         return "ok";

      }

      return "unkown error";

   } // toggleFilterStatus()

   /**
    * return true if the provided filter with the specified name is
    * already existing
    */
   private function checkFilterExists($filter_name)
   {
      if($this->db->db_fetchSingleRow("
         SELECT filter_idx 
         FROM ". MYSQL_PREFIX ."filters
         WHERE
            filter_name LIKE BINARY '". $_POST['filter_name'] ."'
         ")) {
         return true;
      }

      return false;

   } // checkFilterExists()  

   /**
    * template function which will be called from the filter listing template
    */
   public function smarty_filter_list($params, $content, &$smarty, &$repeat)
   {
      $index = $this->tmpl->get_template_vars('smarty.IB.filter_list.index');
      if(!$index) {
         $index = 0;
      }

      if($index < count($this->avail_filters)) {

        $filter_idx = $this->avail_filters[$index];
        $filter =  $this->filters[$filter_idx];

         $this->tmpl->assign('filter_idx', $filter_idx);
         $this->tmpl->assign('filter_name', $filter->filter_name);
         $this->tmpl->assign('filter_active', $filter->filter_active);

         $index++;
         $this->tmpl->assign('smarty.IB.filter_list.index', $index);
         $repeat = true;
      }
      else {
         $repeat =  false;
      }

      return $content;

   } // smarty_filter_list()

   public function smarty_protocol_select_list($params, &$smarty)
   {
      if(!array_key_exists('proto_idx', $params)) {
         $this->tmpl->trigger_error("getSLList: missing 'proto_idx' parameter", E_USER_WARNING);
         $repeat = false;
         return;
      } 

      $result = $this->db->db_query("
         SELECT *
         FROM ". MYSQL_PREFIX ."protocols
         ORDER BY proto_name ASC
      ");
      
      while($row = $result->fetchRow()) {
         $string.= "<option value=\"". $row->proto_idx ."\"";
         if($row->proto_idx == $params['proto_idx'])
             $string.= "selected=\"selected\"";
         $string.= ">". $row->proto_name ."</option>\n";
      }
   
      return $string;

   } // smarty_protocol_select_list()

   public function smarty_port_select_list($params, &$smarty)
   {
      if(!array_key_exists('filter_idx', $params)) {
         $this->tmpl->trigger_error("getSLList: missing 'filter_idx' parameter", E_USER_WARNING);
         $repeat = false;
         return;
      } 
      if(!array_key_exists('mode', $params)) {
         $this->tmpl->trigger_error("getSLList: missing 'mode' parameter", E_USER_WARNING);
         $repeat = false;
         return;
      } 

      switch($params['mode']) {
         case 'unused':
            $ports = $this->db->db_query("
               SELECT port_idx, port_name, port_number
               FROM ". MYSQL_PREFIX ."ports
               LEFT JOIN ". MYSQL_PREFIX ."assign_ports
                  ON port_idx=". MYSQL_PREFIX ."assign_ports.afp_port_idx
               WHERE
                  ". MYSQL_PREFIX ."assign_ports.afp_filter_idx <> '". $params['filter_idx'] ."'
               OR
                  ISNULL(". MYSQL_PREFIX ."assign_ports.afp_filter_idx)
               ORDER BY port_name ASC
            ");
            break;
         case 'used':
            $ports = $this->db->db_query("
               SELECT p.port_idx, p.port_name, p.port_number
               FROM ". MYSQL_PREFIX ."assign_ports
               LEFT JOIN ". MYSQL_PREFIX ."ports p
                  ON p.port_idx = afp_port_idx
               WHERE
                  afp_filter_idx = '". $params['filter_idx'] ."'
               ORDER BY p.port_name ASC
            ");
            break;
      }

      while($port = $ports->fetchRow()) {
         $string.= "<option value=\"". $port->port_idx ."\">". $port->port_name ." (". $port->port_number .")</option>\n";
      }

      return $string;

   } // smarty_port_select_list()

   public function smarty_l7_select_list($params, &$smarty)
   {
      if(!array_key_exists('filter_idx', $params)) {
         $this->tmpl->trigger_error("getSLList: missing 'filter_idx' parameter", E_USER_WARNING);
         $repeat = false;
         return;
      } 
      if(!array_key_exists('mode', $params)) {
         $this->tmpl->trigger_error("getSLList: missing 'mode' parameter", E_USER_WARNING);
         $repeat = false;
         return;
      } 


      switch($params['mode']) {
         case 'unused':
            $l7protos = $this->db->db_query("
               SELECT l7proto_idx, l7proto_name
               FROM ". MYSQL_PREFIX ."l7_protocols
               LEFT JOIN ". MYSQL_PREFIX ."assign_l7_protocols
                  ON l7proto_idx=afl7_l7proto_idx
                     AND afl7_filter_idx = '". $params['filter_idx'] ."'
               WHERE
                  afl7_filter_idx <> '". $params['filter_idx'] ."'
               OR ISNULL(afl7_filter_idx)
               ORDER BY l7proto_name ASC
            ");              
            break;
         case 'used':
            $l7protos = $this->db->db_query("
               SELECT l7proto_idx, l7proto_name
               FROM ". MYSQL_PREFIX ."assign_l7_protocols
               LEFT JOIN ". MYSQL_PREFIX ."l7_protocols
                  ON l7proto_idx=afl7_l7proto_idx
               WHERE afl7_filter_idx = '". $params['filter_idx'] ."'
               ORDER BY l7proto_name ASC
            "); 
            break;
      }

      while($l7proto = $l7protos->fetchRow()) { 
         $string.= "<option value=\"" . $l7proto->l7proto_idx ."\">". $l7proto->l7proto_name ."</option>\n";
      }

      return $string;

   } // smarty_l7_select_list()
   
}

?>
