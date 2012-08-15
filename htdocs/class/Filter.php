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

class Filter extends MsObject {

   /**
    * Filter constructor
    *
    * Initialize the Filter class
    */
   public function __construct($id = null)
   {
      parent::__construct($id, Array(
         'table_name' => 'filters',
         'col_name' => 'filter',
         'fields' => Array(
            'filter_idx' => 'integer',
            'filter_name' => 'text',
            'filter_protocol_id' => 'integer',
            'filter_tos' => 'text',
            'filter_dscp' => 'text',
            'filter_tcpflag_syn' => 'text',
            'filter_tcpflag_ack' => 'text',
            'filter_tcpflag_fin' => 'text',
            'filter_tcpflag_rst' => 'text',
            'filter_tcpflag_urg' => 'text',
            'filter_tcpflag_psh' => 'text',
            'filter_packet_length' => 'text',
            'filter_p2p_edk' => 'text',
            'filter_p2p_kazaa' => 'text',
            'filter_p2p_dc' => 'text',
            'filter_p2p_gnu' => 'text',
            'filter_p2p_bit' => 'text',
            'filter_p2p_apple' => 'text',
            'filter_p2p_soul' => 'text',
            'filter_p2p_winmx' => 'text',
            'filter_p2p_ares' => 'text',
            'filter_time_use_range' => 'text',
            'filter_time_start' => 'integer',
            'filter_time_stop' => 'integer',
            'filter_time_day_mon' => 'text',
            'filter_time_day_tue' => 'text',
            'filter_time_day_wed' => 'text',
            'filter_time_day_thu' => 'text',
            'filter_time_day_fri' => 'text',
            'filter_time_day_sat' => 'text',
            'filter_time_day_sun' => 'text',
            'filter_match_ftp_data' => 'text',
            'filter_match_sip' => 'text',
            'filter_active' => 'text',
         ),
      ));

      /* it seems a new filter gets created, preset some values */
      if(!isset($id) || empty($id)) {
         $this->filter_active = 'Y';
      }

   } // __construct()

   /**
    * handle updates
    */
   public function post_save()
   {
      global $db;

      $sth = $db->db_prepare("
         DELETE FROM
            ". MYSQL_PREFIX ."assign_ports_to_filters
         WHERE
            afp_filter_idx LIKE ?
      ");

      $db->db_execute($sth, array(
         $this->id
      ));

      $db->db_sth_free($sth);

      foreach($_POST['used'] as $use) {

         if(empty($use))
            continue;

         $sth = $db->db_prepare("
            INSERT INTO ". MYSQL_PREFIX ."assign_ports_to_filters (
               afp_filter_idx,
               afp_port_idx
            ) VALUES (
               ?,
               ?
            )
         ");

         $db->db_execute($sth, array(
            $this->id,
            $use
         ));

         $db->db_sth_free($sth);
      }

      /* is our work done? */
      if(!isset($_POST['filter_l7_used']) || empty($_POST['filter_l7_used']))
         return true;

      $sth = $db->db_prepare("
         DELETE FROM
            ". MYSQL_PREFIX ."assign_l7_protocols_to_filters
         WHERE
            afl7_filter_idx LIKE ?
      ");

      $db->db_execute($sth, array(
         $this->id
      ));

      $db->db_sth_free($sth);

      foreach($_POST['filter_l7_used'] as $use) {

         if(empty($use))
            continue;

         $sth = $db->db_prepare("
            INSERT INTO ". MYSQL_PREFIX ."assign_l7_protocols_to_filters (
               afl7_filter_idx,
               afl7_l7proto_idx
            ) VALUES (
               ?,
               ?
            )
         ");

         $db->db_execute($sth, array(
            $this->id,
            $use
         ));

         $db->db_sth_free($sth);
      }

      return true;

   } // post_save()

   /**
    * delete filter
    */
   public function post_delete()
   {
      global $db;

      $sth = $db->db_prepare("
         DELETE FROM
            ". MYSQL_PREFIX ."assign_ports_to_filters
         WHERE
            afp_filter_idx LIKE ?
      ");

      $db->db_execute($sth, array(
         $this->id
      ));

      $db->db_sth_free($sth);
      $sth = $db->db_prepare("
         DELETE FROM
            ". MYSQL_PREFIX ."assign_l7_protocols_to_filters
         WHERE
            afl7_filter_idx LIKE ?
      ");

      $db->db_execute($sth, array(
         $this->id
      ));

      $db->db_sth_free($sth);
      $sth = $db->db_prepare("
         DELETE FROM
            ". MYSQL_PREFIX ."assign_filters_to_pipes
         WHERE
            apf_filter_idx LIKE ?
      ");

      $db->db_execute($sth, array(
         $this->id
      ));

      $db->db_sth_free($sth);
      return true;
      
   } // post_delete()

} // class Filter

?>
