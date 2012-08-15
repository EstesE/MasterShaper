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

class Page_Filters extends MASTERSHAPER_PAGE {

   /**
    * Page_Filters constructor
    *
    * Initialize the Page_Filters class
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
         SELECT
            *
         FROM
            ". MYSQL_PREFIX ."filters
         ORDER BY
            filter_name ASC
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
         $filter = new Filter($page->id);
         $tmpl->assign('is_new', false);
      }
      else {
         $filter = new filter;
         $tmpl->assign('is_new', true);
      }

      /* get a list of pipes that use this filter */
      $sth = $db->db_prepare("
         SELECT
            p.pipe_idx,
            p.pipe_name
         FROM
            ". MYSQL_PREFIX ."pipes p
         INNER JOIN
            ". MYSQL_PREFIX ."assign_filters_to_pipes afp
         ON
            afp.apf_pipe_idx=p.pipe_idx
         WHERE
            afp.apf_filter_idx LIKE ?
         ORDER BY
            p.pipe_name ASC
      ");

      $assigned_pipes = $db->db_execute($sth, array(
         $page->id,
      ));

      $db->db_sth_free($sth);

      if($assigned_pipes->numRows() > 0) {
         $pipe_use_filters = array();
         while($pipe = $assigned_pipes->fetchRow()) {
            $pipe_use_filters[$pipe->pipe_idx] = $pipe->pipe_name;
         }
         $tmpl->assign('pipe_use_filters', $pipe_use_filters);
      }

      $tmpl->assign('filter', $filter);
      $tmpl->assign('filter_mode', $ms->getOption("filter"));

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
      global $ms, $db, $rewriter;

      isset($_POST['new']) && $_POST['new'] == 1 ? $new = 1 : $new = NULL;

      /* load filter */
      if(isset($new))
         $filter = new Filter;
      else
         $filter = new Filter($_POST['filter_idx']);

      if(!isset($new) && (!isset($_POST['filter_idx']) || !is_numeric($_POST['filter_idx'])))
         $ms->throwError(_("Missing id of filter to be handled!"));

      if(!isset($_POST['filter_name']) || $_POST['filter_name'] == "") {
         $ms->throwError(_("Please enter a filter name!"));
      }
      if(isset($new) && $ms->check_object_exists('filter', $_POST['filter_name'])) {
         $ms->throwError(_("A filter with that name already exists!"));
      }
      if(!isset($new) && $filter->filter_name != $_POST['filter_name'] &&
         $ms->check_object_exists('filter', $_POST['filter_name'])) {
         $ms->throwError(_("A filter with that name already exists!"));
      }
      if($_POST['filter_protocol_id'] == -1 &&
         count($_POST['used']) <= 1 &&
         $_POST['filter_tos'] == -1 &&
         $_POST['filter_dscp'] == -1 &&
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
         $ms->throwError(_("This filter has nothing to do. Please select at least one match!"));
      }
      /* Ports can only be used with TCP, UDP or IP protocol */
      if(isset($_POST['used']) && count($_POST['used']) > 1 &&
         (
            !isset($_POST['filter_protocol_id']) ||
            $_POST['filter_protocol_id'] == -1 || (
            $ms->getProtocolNumberById($_POST['filter_protocol_id']) != 4 &&
            $ms->getProtocolNumberById($_POST['filter_protocol_id']) != 17 &&
            $ms->getProtocolNumberById($_POST['filter_protocol_id']) != 6
         ))) {
         $ms->throwError(_("Ports can only be used in combination with IP, TCP or UDP protocol!"));
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
         $ms->throwError(_("TCP-Flags can only be used in combination with TCP protocol!"));
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
         $ms->throwError(_("IPP2P match can only be used with no ports select and only with protocols TCP or UDP or completly ignoring protocols!<br />Also IPP2P can not be used in combination with layer7 filters."));
      }
      /* layer7 protocol match can only be used with no ports and no tcp &| udp protocols */
      if(isset($_POST['filter_ipt']) &&
         count($_POST['filter_l7_used']) > 1 &&
         $_POST['filter_protocol_id'] != -1) {
            $ms->throwError(_("Layer7 match can only be used with no ports select and no protocol definitions!"));
      }

      if(isset($_POST['filter_ipt'])) {
         $_POST['filter_time_start'] = strtotime(sprintf("%04d-%02d-%02d %02d:%02d:00", 
            $_POST['filter_time_start_year'],
            $_POST['filter_time_start_month'],
            $_POST['filter_time_start_day'],
            $_POST['filter_time_start_hour'], 
            $_POST['filter_time_start_minute']));
         $_POST['filter_time_stop'] = strtotime(sprintf("%04d-%02d-%02d %02d:%02d:00",
            $_POST['filter_time_stop_year'],
            $_POST['filter_time_stop_month'],
            $_POST['filter_time_stop_day'],
            $_POST['filter_time_stop_hour'],
            $_POST['filter_time_stop_minute']));
      }

      /* unset filter_ipt variable, it will not be stored */
      if(isset($_POST['filter_ipt'])) {
         unset($_POST['filter_ipt']);
      }

      $filter_data = $ms->filter_form_data($_POST, 'filter_');

      if(!$filter->update($filter_data))
         return false;

      if(!$filter->save())
         return false;

      if(isset($_POST['add_another']) && $_POST['add_another'] == 'Y')
         return true;

      $ms->set_header('Location', $rewriter->get_page_url('Filters List'));
      return true;

   } // store()

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
         SELECT
            *
         FROM
            ". MYSQL_PREFIX ."protocols
         ORDER BY
            proto_name ASC
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

      global $ms, $db;

      switch($params['mode']) {

         case 'unused':

            $sth = $db->db_prepare("
               SELECT
                  port_idx,
                  port_name,
                  port_number
               FROM
                  ". MYSQL_PREFIX ."ports
               LEFT JOIN ". MYSQL_PREFIX ."assign_ports_to_filters
                  ON port_idx=". MYSQL_PREFIX ."assign_ports_to_filters.afp_port_idx
               WHERE
                  ". MYSQL_PREFIX ."assign_ports_to_filters.afp_filter_idx <> ?
               OR
                  ISNULL(". MYSQL_PREFIX ."assign_ports_to_filters.afp_filter_idx)
               ORDER BY
                  port_name ASC
            ");

            $ports = $db->db_execute($sth, array(
               $params['filter_idx']
            ));
            $db->db_sth_free($sth);
            break;

         case 'used':

            $sth = $db->db_prepare("
               SELECT
                  p.port_idx,
                  p.port_name,
                  p.port_number
               FROM
                  ". MYSQL_PREFIX ."assign_ports_to_filters
               LEFT JOIN
                  ". MYSQL_PREFIX ."ports p
                  ON p.port_idx = afp_port_idx
               WHERE
                  afp_filter_idx LIKE ?
               ORDER BY p.port_name ASC
            ");

            $ports = $db->db_execute($sth, array(
               $params['filter_idx']
            ));
            $db->db_sth_free($sth);
            break;

         default:
            $ms->throwError('unknown mode');
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

      global $ms, $db;

      switch($params['mode']) {
         case 'unused':

            $sth = $db->db_prepare("
               SELECT
                  l7proto_idx,
                  l7proto_name
               FROM
                  ". MYSQL_PREFIX ."l7_protocols
               LEFT JOIN ". MYSQL_PREFIX ."assign_l7_protocols_to_filters
                  ON l7proto_idx=afl7_l7proto_idx
                     AND afl7_filter_idx LIKE ?
               WHERE
                  afl7_filter_idx <> ?
               OR
                  ISNULL(afl7_filter_idx)
               ORDER BY
                  l7proto_name ASC
            ");              

            $l7protos = $db->db_execute($sth, array(
               $params['filter_idx'],
               $params['filter_idx']
            ));
            $db->db_sth_free($sth);
            break;

         case 'used':

            $sth = $db->db_prepare("
               SELECT
                  l7proto_idx,
                  l7proto_name
               FROM
                  ". MYSQL_PREFIX ."assign_l7_protocols_to_filters
               LEFT JOIN ". MYSQL_PREFIX ."l7_protocols
                  ON l7proto_idx=afl7_l7proto_idx
               WHERE
                  afl7_filter_idx LIKE ?
               ORDER BY
                  l7proto_name ASC
            "); 

            $l7protos = $db->db_execute($sth, array(
               $params['filter_idx']
            ));
            $db->db_sth_free($sth);
            break;

         default:
            $ms->throwError('unknown mode');
            break;
      }

      while($l7proto = $l7protos->fetchRow()) { 
         $string.= "<option value=\"" . $l7proto->l7proto_idx ."\">". $l7proto->l7proto_name ."</option>\n";
      }

      return $string;

   } // smarty_l7_select_list()
   
} // class Page_Filters

$obj = new Page_Filters;
$obj->handler();

?>
