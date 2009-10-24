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

class Port extends MASTERSHAPER_PAGE {

   /**
    * Port constructor
    *
    * Initialize the Port class
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

      isset($_POST['port_new']) && $_POST['port_new'] == 1 ? $new = 1 : $new = NULL;

      if(!isset($_POST['port_name']) || $_POST['port_name'] == "") {
         return _("Please enter a port name!");
      }

      if(isset($new) && $this->checkPortExists($_POST['port_name'])) {
         return _("A port with that name already exists!");
      }

      if(!isset($new) && $_POST['namebefore'] != $_POST['port_name']
         && $this->checkPortExists($_POST['port_name'])) {
         return _("A port with that name already exists!");
      }

      /* only one or several ports */
      if(preg_match("/,/", $_POST['port_number']) || preg_match("/-/", $_POST['port_number'])) {
         $temp_ports = split(",", $_POST['port_number']);
         foreach($temp_ports as $port) {
            $port = trim($port); 
            if(preg_match("/-/", $port)) {
               list($lower, $higher) = split("-", $port);
               if(!is_numeric($lower) || $lower <= 0 || $lower >= 65536)
                  $is_numeric = 0;
               if(!is_numeric($higher) || $higher <= 0 || $higher >= 65536)
                  $is_numeric = 0;
            }
            else {
              if(!is_numeric($port) || $port <= 0 || $port >= 65536)
                  $is_numeric = 0;
            }
         }
      }
      elseif(!is_numeric($_POST['port_number']) ||
         $_POST['port_number'] <= 0 || $_POST['port_number'] >= 65536) {
         return _("Please enter a decimal port number within 1 - 65535!");
      }

      if(isset($new)) {

         $db->db_query("
            INSERT INTO ". MYSQL_PREFIX ."ports 
               (port_name, port_desc, port_number, port_user_defined)
            VALUES (
               '". $_POST['port_name'] ."',
               '". $_POST['port_desc'] ."',
               '". $_POST['port_number'] ."',
               'Y')
         ");
      }
      else {
		     $db->db_query("
               UPDATE ". MYSQL_PREFIX ."ports
               SET 
                  port_name='". $_POST['port_name'] ."',
                  port_desc='". $_POST['port_desc'] ."',
                  port_number='". $_POST['port_number'] ."',
                  port_user_defined='Y'
               WHERE
                  port_idx='". $_POST['port_idx'] ."'
            ");
      }

      return "ok";

   } // store()

   /**
    * checks if provided port name already exists
    * and will return true if so.
    */
   private function checkPortExists($port_name)
   {
      global $db;

      if($db->db_fetchSingleRow("
         SELECT port_idx
         FROM ". MYSQL_PREFIX ."ports
         WHERE
            port_name LIKE BINARY '". $port_name ."'
         ")) {
         return true;
      } 

      return false;

   } // checkPortExists()

   /**
    * delete port
    */
   public function delete()
   {
      global $db;

      if(isset($_POST['idx'])) {
         $idx = $_POST['idx'];

         $db->db_query("
            DELETE FROM ". MYSQL_PREFIX ."ports
            WHERE
               port_idx='". $idx ."'
         ");
         $db->db_query("
            DELETE FROM ". MYSQL_PREFIX ."assign_ports_to_filters
            WHERE
               afp_port_idx='". $idx ."'
         ");
   
         return "ok";
      }

      return "unkown error";

   } // delete()

} // class Port

$obj = new Port;
$obj->handler();

?>
