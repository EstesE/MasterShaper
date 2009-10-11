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

class MASTERSHAPER_FILTERS extends MASTERSHAPER_PAGE {

   /**
    * MASTERSHAPER_FILTERS constructor
    *
    * Initialize the MASTERSHAPER_FILTERS class
    */
   public function __construct()
   {
      $this->rights = 'user_manage_filters';

   } // __constrcut()

   /**
    * display all filters
    */
   public function showList()
   {
      global $db, $tmpl;

      $this->avail_filters = Array();
      $this->filters = Array();

      $res_filters = $db->db_query("
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

      $tmpl->register_block("filter_list", array(&$this, "smarty_filter_list"));
      return $tmpl->fetch("filters_list.tpl");

   } // showList()

   /** 
    * filter for handling
    */
   public function showEdit()
   {
      if($this->is_storing())
         $this->store();

      global $ms, $db, $tmpl, $page;

      if($page->id != 0) {

         $filter = $db->db_fetchSingleRow("
            SELECT *
            FROM ". MYSQL_PREFIX ."filters
            WHERE
               filter_idx='". $page->id ."'
         ");

         $tmpl->assign('filter_idx', $page->id);
         $tmpl->assign('filter_mode', $ms->getOption("filter"));
         $tmpl->assign('filter_name', $filter->filter_name);
         $tmpl->assign('filter_active', $filter->filter_active);
         $tmpl->assign('filter_protocol_id', $filter->filter_protocol_id);
         $tmpl->assign('filter_tos', $filter->filter_tos);
         $tmpl->assign('filter_tcpflag_syn', $filter->filter_tcpflag_syn);
         $tmpl->assign('filter_tcpflag_ack', $filter->filter_tcpflag_ack);
         $tmpl->assign('filter_tcpflag_fin', $filter->filter_tcpflag_fin);
         $tmpl->assign('filter_tcpflag_rst', $filter->filter_tcpflag_rst);
         $tmpl->assign('filter_tcpflag_urg', $filter->filter_tcpflag_urg);
         $tmpl->assign('filter_tcpflag_psh', $filter->filter_tcpflag_psh);
         $tmpl->assign('filter_packet_length', $filter->filter_packet_length);
         $tmpl->assign('filter_p2p_edk', $filter->filter_p2p_edk);
         $tmpl->assign('filter_p2p_kazaa', $filter->filter_p2p_kazaa);
         $tmpl->assign('filter_p2p_dc', $filter->filter_p2p_dc);
         $tmpl->assign('filter_p2p_gnu', $filter->filter_p2p_gnu);
         $tmpl->assign('filter_p2p_bit', $filter->filter_p2p_bit);
         $tmpl->assign('filter_p2p_apple', $filter->filter_p2p_apple);
         $tmpl->assign('filter_p2p_soul', $filter->filter_p2p_soul);
         $tmpl->assign('filter_p2p_winmx', $filter->filter_p2p_winmx);
         $tmpl->assign('filter_p2p_ares', $filter->filter_p2p_ares);
         $tmpl->assign('filter_time_use_range', $filter->filter_time_use_range);
         $tmpl->assign('filter_time_start', $filter->filter_time_start);
         $tmpl->assign('filter_time_stop', $filter->filter_time_stop);
         $tmpl->assign('filter_time_day_mon', $filter->filter_time_day_mon);
         $tmpl->assign('filter_time_day_tue', $filter->filter_time_day_tue);
         $tmpl->assign('filter_time_day_wed', $filter->filter_time_day_wed);
         $tmpl->assign('filter_time_day_thu', $filter->filter_time_day_thu);
         $tmpl->assign('filter_time_day_fri', $filter->filter_time_day_fri);
         $tmpl->assign('filter_time_day_sat', $filter->filter_time_day_sat);
         $tmpl->assign('filter_time_day_sun', $filter->filter_time_day_sun);
         $tmpl->assign('filter_match_ftp_data', $filter->filter_match_ftp_data);
         $tmpl->assign('filter_match_sip', $filter->filter_match_sip);

      }
      else {
         $tmpl->assign('filter_active', 'Y');
      }

      $tmpl->register_function("protocol_select_list", array(&$this, "smarty_protocol_select_list"), false);
      $tmpl->register_function("port_select_list", array(&$this, "smarty_port_select_list"), false);
      $tmpl->register_function("l7_select_list", array(&$this, "smarty_l7_select_list"), false);
      return $tmpl->fetch("filters_edit.tpl");

   } // showEdit()

   /**
    * handle updates
    */
   public function store()
   {
      global $ms, $db;

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
      if(isset($_POST['used']) && count($_POST['used']) > 1 &&
         (
            $ms->getProtocolNumberById($_POST['filter_protocol_id']) != 4 &&
            $ms->getProtocolNumberById($_POST['filter_protocol_id']) != 17 &&
            $ms->getProtocolNumberById($_POST['filter_protocol_id']) != 6
         )) {
         return _("Ports can only be used in combination with IP, TCP or UDP protocol!");
      }
      /* TCP-flags can only be used with TCP protocol */
      if(isset($_POST['filter_ipt']) && (
            $_POST['filter_tcpflag_syn'] ||
            $_POST['filter_tcpflag_ack'] ||
            $_POST['filter_tcpflag_fin'] ||
            $_POST['filter_tcpflag_rst'] ||
            $_POST['filter_tcpflag_urg'] ||
            $_POST['filter_tcpflag_psh']
         ) &&
         $ms->getProtocolNumberById($_POST['filter_protocol_id']) != 6) {
         return _("TCP-Flags can only be used in combination with TCP protocol!");
      }
      /* ipp2p can only be used with no ports, no l7 filters and tcp &| udp protocol */
      if(isset($_POST['filter_ipt']) && (
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
                  $ms->getProtocolNumberById(
                     $_POST['filter_protocol_id']) != 17 &&
                  $ms->getProtocolNumberById($_POST['filter_protocol_id']) != 6
               ) &&
               $_POST['filter_protocol_id'] != -1
            ) ||
            count($_POST['filter_l7_used']) > 1)) {
         return _("IPP2P match can only be used with no ports select and only with protocols TCP or UDP or completly ignoring protocols!<br />Also IPP2P can not be used in combination with layer7 filters.");
      }
      /* layer7 protocol match can only be used with no ports and no tcp &| udp protocols */
      if(isset($_POST['filter_ipt']) &&
         count($_POST['filter_l7_used']) > 1 &&
         $_POST['filter_protocol_id'] != -1) {
            return _("Layer7 match can only be used with no ports select and no protocol definitions!");
      }

      if(isset($_POST['filter_ipt'])) {
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
      }

      if(isset($new)) {
         $db->db_query("
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
                          
         $_POST['filter_idx'] = $db->db_getid();

      }
      else {
         switch($ms->getOption("filter")) {
            case 'ipt':
               $db->db_query("
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
               $db->db_query("
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

      if(isset($_POST['used']) && $_POST['used']) {
         $db->db_query("
            DELETE FROM ". MYSQL_PREFIX ."assign_ports_to_filters
            WHERE
               afp_filter_idx='". $_POST['filter_idx'] ."'
         ");

         foreach($_POST['used'] as $use) {
            if($use != "") {
               $db->db_query("
                  INSERT INTO ". MYSQL_PREFIX ."assign_ports_to_filters (
                     afp_filter_idx, afp_port_idx
                  ) VALUES (
                     '". $_POST['filter_idx'] ."',
                     '". $use ."'
                  )
               ");
            }
         }

         if(isset($_POST['filter_l7_used']) && $_POST['filter_l7_used']) {
            $db->db_query("
               DELETE FROM ". MYSQL_PREFIX ."assign_l7_protocols_to_filters
               WHERE
                  afl7_filter_idx='". $_POST['filter_idx'] ."'
            ");
            foreach($_POST['filter_l7_used'] as $use) {
               if($use != "") {
                  $db->db_query("
                     INSERT INTO ". MYSQL_PREFIX ."assign_l7_protocols_to_filters (
                        afl7_filter_idx, afl7_l7proto_idx
                     ) VALUES (
                        '". $_POST['filter_idx'] ."',
                        '". $use ."'
                     )
                  ");
               }
            }
         }
      }

      return "ok";

   } // store()

   /**
    * delete filter
    */
   public function delete()
   {
      global $db;

      if(isset($_POST['idx']) && is_numeric($_POST['idx'])) {
         $idx = $_POST['idx'];
      
         $db->db_query("
            DELETE FROM ". MYSQL_PREFIX ."filters
            WHERE
               filter_idx='". $idx ."'
         ");
         $db->db_query("
            DELETE FROM ". MYSQL_PREFIX ."assign_ports_to_filters
            WHERE
               afp_filter_idx='". $idx ."'
         ");
         $db->db_query("
            DELETE FROM ". MYSQL_PREFIX ."assign_l7_protocols_to_filters
            WHERE
               afl7_filter_idx='". $idx ."'
         ");
         $db->db_query("
            DELETE FROM ". MYSQL_PREFIX ."assign_filters_to_pipes
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
      global $db;

      if(isset($_POST['idx']) && is_numeric($_POST['idx'])) {
         $idx = $_POST['idx'];

         if($_POST['to'] == 1)
            $new_status = 'Y';
         else
            $new_status = 'N';

         $db->db_query("
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
      global $db;

      if($db->db_fetchSingleRow("
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
      global $tmpl;

      $index = $smarty->get_template_vars('smarty.IB.filter_list.index');
      if(!$index) {
         $index = 0;
      }

      if($index < count($this->avail_filters)) {

        $filter_idx = $this->avail_filters[$index];
        $filter =  $this->filters[$filter_idx];

         $tmpl->assign('filter_idx', $filter_idx);
         $tmpl->assign('filter_name', $filter->filter_name);
         $tmpl->assign('filter_active', $filter->filter_active);

         $index++;
         $tmpl->assign('smarty.IB.filter_list.index', $index);
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
         $tmpl->trigger_error("getSLList: missing 'proto_idx' parameter", E_USER_WARNING);
         $repeat = false;
         return;
      } 

      global $db;

      $result = $db->db_query("
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
         $tmpl->trigger_error("getSLList: missing 'filter_idx' parameter", E_USER_WARNING);
         $repeat = false;
         return;
      } 
      if(!array_key_exists('mode', $params)) {
         $tmpl->trigger_error("getSLList: missing 'mode' parameter", E_USER_WARNING);
         $repeat = false;
         return;
      } 

      global $db;

      switch($params['mode']) {
         case 'unused':
            $ports = $db->db_query("
               SELECT port_idx, port_name, port_number
               FROM ". MYSQL_PREFIX ."ports
               LEFT JOIN ". MYSQL_PREFIX ."assign_ports_to_filters
                  ON port_idx=". MYSQL_PREFIX ."assign_ports_to_filters.afp_port_idx
               WHERE
                  ". MYSQL_PREFIX ."assign_ports_to_filters.afp_filter_idx <> '". $params['filter_idx'] ."'
               OR
                  ISNULL(". MYSQL_PREFIX ."assign_ports_to_filters.afp_filter_idx)
               ORDER BY port_name ASC
            ");
            break;
         case 'used':
            $ports = $db->db_query("
               SELECT p.port_idx, p.port_name, p.port_number
               FROM ". MYSQL_PREFIX ."assign_ports_to_filters
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
         $tmpl->trigger_error("getSLList: missing 'filter_idx' parameter", E_USER_WARNING);
         $repeat = false;
         return;
      } 
      if(!array_key_exists('mode', $params)) {
         $tmpl->trigger_error("getSLList: missing 'mode' parameter", E_USER_WARNING);
         $repeat = false;
         return;
      } 

      global $db;

      switch($params['mode']) {
         case 'unused':
            $l7protos = $db->db_query("
               SELECT l7proto_idx, l7proto_name
               FROM ". MYSQL_PREFIX ."l7_protocols
               LEFT JOIN ". MYSQL_PREFIX ."assign_l7_protocols_to_filters
                  ON l7proto_idx=afl7_l7proto_idx
                     AND afl7_filter_idx = '". $params['filter_idx'] ."'
               WHERE
                  afl7_filter_idx <> '". $params['filter_idx'] ."'
               OR ISNULL(afl7_filter_idx)
               ORDER BY l7proto_name ASC
            ");              
            break;
         case 'used':
            $l7protos = $db->db_query("
               SELECT l7proto_idx, l7proto_name
               FROM ". MYSQL_PREFIX ."assign_l7_protocols_to_filters
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
   
} // class MASTERSHAPER_FILTERS

$obj = new MASTERSHAPER_FILTERS;
$obj->handler();

?>
