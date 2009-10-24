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

class Filter extends MASTERSHAPER_PAGE {

   /**
    * Filter constructor
    *
    * Initialize the Filter class
    */
   public function __construct()
   {

   } // __constrcut()

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

} // class Filter

$obj = new Filter;
$obj->handler();

?>
