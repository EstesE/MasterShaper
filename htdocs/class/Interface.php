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

class Interface extends MASTERSHAPER_PAGE {

   /**
    * Interface constructor
    *
    * Initialize the Interface class
    */
   public function __construct()
   {

   } // __construct()
  
   /**
    * delete interface
    */
   public function delete()
   {
      global $db;

      if(isset($_POST['idx']) && is_numeric($_POST['idx'])) {
         $idx = $_POST['idx'];
   
         $db->db_query("
            DELETE FROM ". MYSQL_PREFIX ."interfaces
            WHERE
               if_idx='". $idx ."'
         ");
         
         return "ok";
      }
   
      return "unkown error";

   } // delete() 

   /**
    * checks if provided interface name already exists
    * and will return true if so.
    */
   private function checkInterfaceExists($if_name)
   {
      global $db;

      if($db->db_fetchSingleRow("
         SELECT if_idx
         FROM ". MYSQL_PREFIX ."interfaces
         WHERE
            if_name LIKE BINARY '". $if_name ."'
         ")) {
         return true;
      }

      return false;

   } // checkInterfaceExists()

   /**
    * handle updates
    */
   public function store()
   {
      global $ms, $db;

      isset($_POST['if_new']) && $_POST['if_new'] == 1 ? $new = 1 : $new = NULL;

      if(!isset($_POST['if_name']) || $_POST['if_name'] == "") {
         return _("Please specify a interface!");
      }
      if(isset($new) && $this->checkInterfaceExists($_POST['if_name'])) {
         return _("A interface with that name already exists!");
      }
      if(!isset($new) && $_POST['namebefore'] != $_POST['if_name'] && 
         $this->checkInterfaceExists($_POST['if_name'])) {
         return _("A interface with that name already exists!");
      }
      if(!isset($_POST['if_speed']) || $_POST['if_speed'] == "")
         $_POST['if_speed'] = 0;
      else
         $_POST['if_speed'] = strtoupper($_POST['if_speed']);

      if(!$ms->validateBandwidth($_POST['if_speed'])) {
         return _("Invalid bandwidth specified!");
      }

      if(isset($new)) {
         $db->db_query("
            INSERT INTO ". MYSQL_PREFIX ."interfaces (
               if_name, if_speed, if_ifb, if_active
            ) VALUES (
               '". $_POST['if_name'] ."',
               '". $_POST['if_speed'] ."',
               '". $_POST['if_ifb'] ."',
               '". $_POST['if_active'] ."'
            )
         ");
      }
      else {
         $db->db_query("
            UPDATE ". MYSQL_PREFIX ."interfaces
            SET
               if_name='". $_POST['if_name'] ."',
               if_speed='". $_POST['if_speed'] ."',
               if_ifb='". $_POST['if_ifb'] ."',
               if_active='". $_POST['if_active'] ."'
            WHERE
               if_idx='". $_POST['if_idx'] ."'
         ");
      }
      
      return "ok";
   
   } // store()

   /**
    * toggle interface status
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
            UPDATE ". MYSQL_PREFIX ."interfaces
            SET
               if_active='". $new_status ."'
            WHERE
               if_idx='". $idx ."'
         ");

         return "ok";

      }

      return "unkown error";

   } // toggleStatus()

} // class Interface

$obj = new Interface;
$obj->handler();

?>
