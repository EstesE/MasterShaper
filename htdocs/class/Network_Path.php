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

class Network_Path extends MASTERSHAPER_PAGE {

   /**
    * Network_Path constructor
    *
    * Initialize the Network_Path class
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

      isset($_POST['netpath_new']) && $_POST['netpath_new'] == 1 ? $new = 1 : $new = NULL;

      if(!isset($_POST['netpath_name']) || $_POST['netpath_name'] == "") {
         return _("Please specify a network path name!");
      }
      if(isset($new) && $this->checkNetworkPathExists($_POST['netpath_name'])) {
         return _("A network path with that name already exists!");
      }
      if(!isset($new) && $_POST['netpath_name'] != $_POST['namebefore'] &&
         $this->checkNetworkPathExists($_POST['netpath_name'])) {
         return _("A network path with that name already exists!");
      }
      if($_POST['netpath_if1'] == $_POST['netpath_if2']) {
         return _("A interface within a network path can not be used twice! Please select different interfaces");
      }

      if(isset($new)) {
         $max_pos = $db->db_fetchSingleRow("
            SELECT MAX(netpath_position) as pos
            FROM ". MYSQL_PREFIX ."network_paths
         ");
         $db->db_query("
            INSERT INTO ". MYSQL_PREFIX ."network_paths (
               netpath_name, netpath_if1, netpath_if1_inside_gre,
               netpath_if2, netpath_if2_inside_gre, netpath_position,
               netpath_imq, netpath_active
            ) VALUES (
               '". $_POST['netpath_name'] ."',
               '". $_POST['netpath_if1'] ."',
               '". $_POST['netpath_if1_inside_gre'] ."',
               '". $_POST['netpath_if2'] ."',
               '". $_POST['netpath_if2_inside_gre'] ."',
               '". ($max_pos->pos+1) ."',
               '". $_POST['netpath_imq'] ."',
               '". $_POST['netpath_active'] ."'
            )
         ");
      }
      else {
         $db->db_query("
            UPDATE ". MYSQL_PREFIX ."network_paths
            SET
               netpath_name='". $_POST['netpath_name'] ."',
               netpath_if1='". $_POST['netpath_if1'] ."',
               netpath_if1_inside_gre='". $_POST['netpath_if1_inside_gre'] ."',
               netpath_if2='". $_POST['netpath_if2'] ."',
               netpath_if2_inside_gre='". $_POST['netpath_if2_inside_gre'] ."',
               netpath_imq='". $_POST['netpath_imq'] ."',
               netpath_active='". $_POST['netpath_active'] ."'
            WHERE
               netpath_idx='". $_POST['netpath_idx'] ."'");
      }
		  
      return "ok";

   } // store()

   /**
    * delete network path
    */
   public function delete()
   {
      global $db;

      if(isset($_POST['idx']) && is_numeric($_POST['idx'])) {
         $idx = $_POST['idx'];

         $db->db_query("
            DELETE FROM ". MYSQL_PREFIX ."network_paths
            WHERE
               netpath_idx='". $idx ."'
         ");

         return "ok";
      
      }
      
      return "unkown error";

   } // delete()

   /**
    * toggle network path status
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
            UPDATE ". MYSQL_PREFIX ."network_paths
            SET
               netpath_active='". $new_status ."'
             WHERE
               netpath_idx='". $idx ."'
         ");
   
         return "ok";

	    }

      return "unkown error";

   } // toggleStatus()

   /**
    * checks if provided network path name already exists
    * and will return true if so.
    */
   private function checkNetworkPathExists($netpath_name)
   {
      global $db;

      if($db->db_fetchSingleRow("
         SELECT netpath_idx
         FROM ". MYSQL_PREFIX ."network_paths
         WHERE
            netpath_name LIKE BINARY '". $netpath_name ."'
         ")) {
         return true;
      } 
      return false;

   } // checkNetworkPathExists()

} // class Network_Path

$obj = new Network_Path;
$obj->handler();

?>
