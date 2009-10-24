<?php

/***************************************************************************
 *
 * Copyright (c) by Andreas Unterkircher
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

class Target extends MASTERSHAPER_PAGE {

   /**
    * Target constructor
    *
    * Initialize the Target class
    */
   public function __construct()
   {

   } // __construct()

   /**
    * handle updates
    */
   public function store()
   {
      global $db;

      isset($_POST['target_new']) && $_POST['target_new'] == 1 ? $new = 1 : $new = NULL;

      if(!isset($_POST['target_name']) || $_POST['target_name'] == "") {
         return _("Please enter a name for this target!");
      }
      if(isset($new) && $this->checkTargetExists($_POST['target_name'])) { 
         return _("A target with that name already exists!");
      }
      if(!isset($new) && $_POST['namebefore'] != $_POST['target_name']
         && $this->checkTargetExists($_POST['target_name'] )) {
         return _("A target with that name already exists!");
      }
      if($_POST['target_match'] == "IP" && $_POST['target_ip'] == "") {
         return _("You have selected IP match but didn't entered a IP address!");
      }
      elseif($_POST['target_match'] == "IP" && $_POST['target_ip'] != "") {
         /* Is target_ip a ip range seperated by "-" */
         if(strstr($_POST['target_ip'], "-") !== false) {
            $hosts = split("-", $_POST['target_ip']);
            foreach($hosts as $host) {
               $ipv4 = new Net_IPv4;
               if(!$ipv4->validateIP($host)) {
                  return _("Incorrect IP address in IP range definition! Please enter a valid IP address!");
               }
            }
         }
         /* Is target_ip a network */
         elseif(strstr($_POST['target_ip'], "/") !== false) {
            $ipv4 = new Net_IPv4;
            $net = $ipv4->parseAddress($_POST['target_ip']);
            if($net->netmask == "" || $net->netmask == "0.0.0.0") {
               return _("Incorrect CIDR address! Please enter a valid network address!");
            }
         }
         /* target_ip is a simple IP */
         else {
            $ipv4 = new Net_IPv4;
            if(!$ipv4->validateIP($_POST['target_ip'])) {
               return _("Incorrect IP address! Please enter a valid IP address!");
            }
         }
      }
      /* MAC address specified? */
      if($_POST['target_match'] == "MAC" && $_POST['target_mac'] == "") {
         return _("You have selected MAC match but didn't entered a MAC address!");
      }
      elseif($_POST['target_match'] == "MAC" && $_POST['target_mac'] != "") {
         if(!preg_match("/(.*):(.*):(.*):(.*):(.*):(.*)/", $_POST['target_mac'])
            && !preg_match("/(.*)-(.*)-(.*)-(.*)-(.*)-(.*)/", $_POST['target_mac'])) {
            return _("You have selected MAC match but specified a INVALID MAC address! Please specify a correct MAC address!");
         }
      }
      if($_POST['target_match'] == "GROUP" && isset($_POST['used']) && count($_POST['used']) < 1) {
         return _("You have selected Group match but didn't selected at least one target from the list!");
      }

      if(isset($new)) {
         $db->db_query("
            INSERT INTO ". MYSQL_PREFIX ."targets
               (target_name, target_match, target_ip, target_mac)
            VALUES  (
               '". $_POST['target_name'] ."',
               '". $_POST['target_match'] ."',
               '". $_POST['target_ip'] ."',
               '". $_POST['target_mac'] ."'
            )
            ");

         $_POST['target_idx'] = $db->db_getId();

      }
      else {
         $db->db_query("
            UPDATE ". MYSQL_PREFIX ."targets
            SET 
               target_name='". $_POST['target_name'] ."',
               target_match='". $_POST['target_match'] ."',
               target_ip='". $_POST['target_ip'] ."',
               target_mac='". $_POST['target_mac'] ."'
               WHERE target_idx='". $_POST['target_idx'] ."'
         ");
      }

      if(isset($_POST['used']) && $_POST['used']) {
         $db->db_query("
            DELETE FROM ". MYSQL_PREFIX ."assign_target_groups
            WHERE
               atg_group_idx='". $_POST['target_idx'] ."'
         ");
         foreach($_POST['used'] as $use) {
            if($use != "") {
               $db->db_query("
                  INSERT INTO ". MYSQL_PREFIX ."assign_target_groups
                     (atg_group_idx, atg_target_idx) 
                  VALUES (
                     '". $_POST['target_idx'] ."',
                     '". $use ."'
                  )
               ");
            }
         }
      }
      return "ok";

   } // store()

   public function delete()
   {
      global $db;

      if(isset($_POST['idx'])) {
         $idx = $_POST['idx'];

         $db->db_query("
            DELETE FROM ". MYSQL_PREFIX ."targets
            WHERE
               target_idx='". $idx ."'
         ");
         $db->db_query("
            DELETE FROM ". MYSQL_PREFIX ."assign_target_groups
            WHERE
               atg_group_idx='". $idx ."'
         ");
         $db->db_query("
            DELETE FROM ". MYSQL_PREFIX ."assign_target_groups
            WHERE
               atg_target_idx='". $idx ."'
         ");
         
         return "ok";
      }

      return "unknown error";
   
   } // delete()

   /**
    * checks if provided target name already exists
    * and will return true if so.
    */
   private function checkTargetExists($target_name)
   {
      global $db;

      if($db->db_fetchSingleRow("
         SELECT target_idx
         FROM ". MYSQL_PREFIX ."targets
         WHERE
            target_name LIKE BINARY '". $target_name ."'
         ")) {
         return true;
      }

      return false;
   } // checkTargetExists()

} // class Target

$obj = new Target();
$obj->handler();

?>
