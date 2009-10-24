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

class Service_Level extends MASTERSHAPER_PAGE {

   /**
    * Service_Level constructor
    *
    * Initialize the Service_Level class
    */
   public function __construct()
   {

   } // __construct()

   /** 
    * save service level
    */
   public function store()
   {
      global $db;

      isset($_POST['sl_new']) && $_POST['sl_new'] == 1 ? $new = 1 : $new = NULL;

      if(!isset($_POST['sl_name']) || $_POST['sl_name'] == "") {
         return _("Please enter a service level name!");
      }

      if(isset($new) && $this->checkServiceLevelExists($_POST['sl_name'])) {
         return _("A service level with that name already exists!");
      }

      if(!isset($new) && $_POST['namebefore'] != $_POST['sl_name'] && $this->checkServiceLevelExists($_POST['sl_name'])) {
         return _("A service level with that name already exists!");
      }

      $is_numeric = 1;

      switch($_POST['classifiermode']) {
         case 'HTB':
            if($_POST['sl_htb_priority'] == 0 && $_POST['sl_htb_bw_in_rate'] == "" && $_POST['sl_htb_bw_out_rate'] == "") {
               return _("A service level which ignores priority AND not specified inbound or outbound rate is not possible!");
            }
            if($_POST['sl_htb_bw_in_rate'] != "" && !is_numeric($_POST['sl_htb_bw_in_rate']))
               $is_numeric = 0;
            if($_POST['sl_htb_bw_out_rate'] != "" && !is_numeric($_POST['sl_htb_bw_out_rate']))
               $is_numeric = 0;
            if($_POST['sl_htb_bw_in_ceil'] != "" && !is_numeric($_POST['sl_htb_bw_in_ceil']))
               $is_numeric = 0;
            if($_POST['sl_htb_bw_in_burst'] != "" && !is_numeric($_POST['sl_htb_bw_in_burst']))
               $is_numeric = 0;
            if($_POST['sl_htb_bw_out_ceil'] != "" && !is_numeric($_POST['sl_htb_bw_out_ceil']))
               $is_numeric = 0;
            if($_POST['sl_htb_bw_out_burst'] != "" && !is_numeric($_POST['sl_htb_bw_out_burst']))
               $is_numeric = 0;
            break;

         case 'HFSC':
            /* If umax is specifed, also umax is necessary */
            if(($_POST['sl_hfsc_in_umax'] != "" && $_POST['sl_hfsc_in_dmax'] == "") ||
               ($_POST['sl_hfsc_out_umax'] != "" && $_POST['sl_hfsc_out_dmax'] == "")) {
               return _("Please enter a \"Max-Delay\" value if you have defined a \"Work-Unit\" value!");
            }
            if($_POST['sl_hfsc_in_umax'] != "" && !is_numeric($_POST['sl_hfsc_in_umax']))
               $is_numeric = 0;
            if($_POST['sl_hfsc_in_dmax'] != "" && !is_numeric($_POST['sl_hfsc_in_dmax']))
               $is_numeric = 0;
            if($_POST['sl_hfsc_in_rate'] != "" && !is_numeric($_POST['sl_hfsc_in_rate']))
               $is_numeric = 0;
            if($_POST['sl_hfsc_in_ulrate'] != "" && !is_numeric($_POST['sl_hfsc_in_ulrate']))
               $is_numeric = 0;
            if($_POST['sl_hfsc_out_umax'] != "" && !is_numeric($_POST['sl_hfsc_out_umax']))
               $is_numeric = 0;
            if($_POST['sl_hfsc_out_dmax'] != "" && !is_numeric($_POST['sl_hfsc_out_dmax']))
               $is_numeric = 0;
            if($_POST['sl_hfsc_out_rate'] != "" && !is_numeric($_POST['sl_hfsc_out_rate']))
               $is_numeric = 0;
            if($_POST['sl_hfsc_out_ulrate'] != "" && !is_numeric($_POST['sl_hfsc_out_ulrate']))
               $is_numeric = 0;
            break;
         case 'CBQ':
            if($_POST['sl_cbq_in_rate'] == "" || $_POST['sl_cbq_out_rate'] == "") {
               return _("Please enter a input and output rate!");
            }
            if($_POST['sl_cbq_in_rate'] != "" && !is_numeric($_POST['sl_cbq_in_rate']))
               $is_numeric = 0;
            if($_POST['sl_cbq_out_rate'] != "" && !is_numeric($_POST['sl_cbq_out_rate']))
               $is_numeric = 0;
            break;
      }

      if(!$is_numeric) {
         return _("Please enter only numerical values for bandwidth parameters!");
      }

      if(isset($new)) {

         $db->db_query("
            INSERT INTO ". MYSQL_PREFIX ."service_levels (
               sl_name, sl_htb_bw_in_rate, sl_htb_bw_in_ceil, 
               sl_htb_bw_in_burst, sl_htb_bw_out_rate, sl_htb_bw_out_ceil,
               sl_htb_bw_out_burst, sl_htb_priority, sl_hfsc_in_umax,
               sl_hfsc_in_dmax, sl_hfsc_in_rate, sl_hfsc_in_ulrate, 
               sl_hfsc_out_umax, sl_hfsc_out_dmax, sl_hfsc_out_rate,
               sl_hfsc_out_ulrate, sl_cbq_in_rate, sl_cbq_in_priority,
               sl_cbq_out_rate, sl_cbq_out_priority, sl_cbq_bounded,
               sl_qdisc, sl_netem_delay, sl_netem_jitter, sl_netem_random,
               sl_netem_distribution, sl_netem_loss, sl_netem_duplication,
               sl_netem_gap, sl_netem_reorder_percentage,
               sl_netem_reorder_correlation, sl_esfq_perturb, sl_esfq_limit,
               sl_esfq_depth, sl_esfq_divisor, sl_esfq_hash
            ) VALUES (
               '". $_POST['sl_name'] ."',
               '". $_POST['sl_htb_bw_in_rate'] ."',
               '". $_POST['sl_htb_bw_in_ceil'] ."',
               '". $_POST['sl_htb_bw_in_burst'] ."',
               '". $_POST['sl_htb_bw_out_rate'] ."',
               '". $_POST['sl_htb_bw_out_ceil'] ."',
               '". $_POST['sl_htb_bw_out_burst'] ."',
               '". $_POST['sl_htb_priority'] ."',
               '". $_POST['sl_hfsc_in_umax'] ."',
               '". $_POST['sl_hfsc_in_dmax'] ."',
               '". $_POST['sl_hfsc_in_rate'] ."',
               '". $_POST['sl_hfsc_in_ulrate'] ."',
               '". $_POST['sl_hfsc_out_umax'] ."',
               '". $_POST['sl_hfsc_out_dmax'] ."',
               '". $_POST['sl_hfsc_out_rate'] ."',
               '". $_POST['sl_hfsc_out_ulrate'] ."',
               '". $_POST['sl_cbq_in_rate'] ."',
               '". $_POST['sl_cbq_in_priority'] ."',
               '". $_POST['sl_cbq_out_rate'] ."',
               '". $_POST['sl_cbq_out_priority'] ."',
               '". $_POST['sl_cbq_bounded'] ."',
               '". $_POST['sl_qdisc'] ."',
               '". $_POST['sl_netem_delay'] ."', 
               '". $_POST['sl_netem_jitter'] ."',
               '". $_POST['sl_netem_random'] ."',
               '". $_POST['sl_netem_distribution'] ."',
               '". $_POST['sl_netem_loss'] ."',
               '". $_POST['sl_netem_duplication'] ."',
               '". $_POST['sl_netem_gap'] ."',
               '". $_POST['sl_netem_reorder_percentage']."',
               '". $_POST['sl_netem_reorder_correlation'] ."',
               '". $_POST['sl_esfq_perturb'] ."',
               '". $_POST['sl_esfq_limit'] ."',
               '". $_POST['sl_esfq_depth'] ."',
               '". $_POST['sl_esfq_divisor'] ."',
               '". $_POST['sl_esfq_hash'] ."'
            )
         ");
      }
      else {
         $db->db_query("
            UPDATE ". MYSQL_PREFIX ."service_levels 
            SET
               sl_name='". $_POST['sl_name'] ."',
               sl_htb_bw_in_rate='". $_POST['sl_htb_bw_in_rate'] ."',
               sl_htb_bw_in_ceil='". $_POST['sl_htb_bw_in_ceil'] ."',
               sl_htb_bw_in_burst='". $_POST['sl_htb_bw_in_burst'] ."',
               sl_htb_bw_out_rate='". $_POST['sl_htb_bw_out_rate'] ."',
               sl_htb_bw_out_ceil='". $_POST['sl_htb_bw_out_ceil'] ."',
               sl_htb_bw_out_burst='". $_POST['sl_htb_bw_out_burst'] ."',
               sl_htb_priority='". $_POST['sl_htb_priority'] ."',
               sl_hfsc_in_umax='". $_POST['sl_hfsc_in_umax'] ."',
               sl_hfsc_in_dmax='". $_POST['sl_hfsc_in_dmax'] ."',
               sl_hfsc_in_rate='". $_POST['sl_hfsc_in_rate'] ."',
               sl_hfsc_in_ulrate='". $_POST['sl_hfsc_in_ulrate'] ."',
               sl_hfsc_out_umax='". $_POST['sl_hfsc_out_umax'] ."',
               sl_hfsc_out_dmax='". $_POST['sl_hfsc_out_dmax'] ."',
               sl_hfsc_out_rate='". $_POST['sl_hfsc_out_rate'] ."',
               sl_hfsc_out_ulrate='". $_POST['sl_hfsc_out_ulrate'] ."',
               sl_cbq_in_rate='". $_POST['sl_cbq_in_rate'] ."',
               sl_cbq_in_priority='". $_POST['sl_cbq_in_priority'] ."',
               sl_cbq_out_rate='". $_POST['sl_cbq_out_rate'] ."',
               sl_cbq_out_priority='". $_POST['sl_cbq_out_priority'] ."',
               sl_cbq_bounded='". $_POST['sl_cbq_bounded'] ."',
               sl_qdisc='". $_POST['sl_qdisc'] ."',
               sl_netem_delay='". $_POST['sl_netem_delay'] ."',
               sl_netem_jitter='". $_POST['sl_netem_jitter'] ."',
               sl_netem_random='". $_POST['sl_netem_random'] ."',
               sl_netem_distribution='". $_POST['sl_netem_distribution'] ."',
               sl_netem_loss='". $_POST['sl_netem_loss'] ."',
               sl_netem_duplication='". $_POST['sl_netem_duplication'] ."',
               sl_netem_gap='". $_POST['sl_netem_gap'] ."',
               sl_netem_reorder_percentage='". $_POST['sl_netem_reorder_percentage']."',
               sl_netem_reorder_correlation='". $_POST['sl_netem_reorder_correlation'] ."',
               sl_esfq_perturb='". $_POST['sl_esfq_perturb'] ."',
               sl_esfq_limit='". $_POST['sl_esfq_limit'] ."',
               sl_esfq_depth='". $_POST['sl_esfq_depth'] ."',
               sl_esfq_divisor='". $_POST['sl_esfq_divisor'] ."',
               sl_esfq_hash='". $_POST['sl_esfq_hash'] ."'
            WHERE sl_idx='". $_POST['sl_idx'] ."'
         ");
      }

      return "ok";

   } // edit()

   public function delete()
   {
      global $db;

      if(isset($_POST['idx'])) {
         $idx = $_POST['idx'];

         $db->db_query("
            DELETE FROM ". MYSQL_PREFIX ."service_levels
            WHERE
               sl_idx='". $idx ."'
            ");
            return ok;
      }
   
      return "unkown error";

   } // delete()

   /**
    * checks if provided service level name already exists
    * and will return true if so.
    */
   private function checkServiceLevelExists($sl_name)
   {
      global $db;

      if($db->db_fetchSingleRow("
         SELECT sl_idx
         FROM ". MYSQL_PREFIX ."service_levels
         WHERE
            sl_name LIKE BINARY '". $sl_name ."'
         ")) {
         return true;
      }
      return false;
   } // checkServiceLevelExists()

} // class Service_Level

$obj = new Service_Level;
$obj->handler();

?>
