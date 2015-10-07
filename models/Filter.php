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
         'child_names' => Array(
            'port' => 'afp',
         ),
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
         parent::init_fields(Array(
            'filter_active' => 'Y',
            'filter_protocol_id' => NULL,
         ));
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

      if(isset($_POST['used']) && !empty($_POST['used'])) {

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
      }

      /* is our work done? */
      if(isset($_POST['filter_l7_used']) && !empty($_POST['filter_l7_used'])) {

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
