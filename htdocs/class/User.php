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

class User extends MASTERSHAPER_PAGE {

   /**
    * User constructor
    *
    * Initialize the User class
    */
   public function __construct()
   {

   } // __construct()
  
   /** 
    * store user values
    */
   public function store()
   {
      global $db;

      isset($_POST['user_new']) && $_POST['user_new'] == 1 ? $new = 1 : $new = NULL;

      if(!isset($_POST['user_name']) || $_POST['user_name'] == "") {
         return _("Please enter a user name!");
      }
      if(isset($new) && $this->checkUserExists($_POST['user_name'])) {
         return _("A user with such a user name already exist!");
      }
      if($_POST['user_pass1'] == "") {
         return _("Empty passwords are not allowed!");
      }
      if($_POST['user_pass1'] != $_POST['user_pass2']) {
         return _("The two entered passwords do not match!");
      }	       

      if(isset($new)) {

         $db->db_query("
            INSERT INTO ". MYSQL_PREFIX ."users (
               user_name, user_pass, user_manage_chains,
               user_manage_pipes, user_manage_filters,
               user_manage_ports, user_manage_protocols, 
               user_manage_targets, user_manage_users,
               user_manage_options, user_manage_servicelevels,
               user_load_rules, user_show_rules, user_show_monitor,
               user_active
            ) VALUES (
               '". $_POST['user_name'] ."',
               '". md5($_POST['user_pass1']) ."',
               '". $_POST['user_manage_chains'] ."',
               '". $_POST['user_manage_pipes'] ."',
               '". $_POST['user_manage_filters'] ."',
               '". $_POST['user_manage_ports'] ."',
               '". $_POST['user_manage_protocols'] ."',
               '". $_POST['user_manage_targets'] ."',
               '". $_POST['user_manage_users'] ."',
               '". $_POST['user_manage_options'] ."',
               '". $_POST['user_manage_servicelevels'] ."',
               '". $_POST['user_load_rules'] ."',
               '". $_POST['user_show_rules'] ."',
               '". $_POST['user_show_monitor'] ."',
               '". $_POST['user_active'] ."'
            )
         ");
      }
      else {
         $db->db_query("
            UPDATE ". MYSQL_PREFIX ."users
            SET
               user_name='". $_POST['user_name'] ."',
               user_manage_chains='". $_POST['user_manage_chains'] ."',
               user_manage_pipes='". $_POST['user_manage_pipes'] ."',
               user_manage_filters='". $_POST['user_manage_filters'] ."',
               user_manage_ports='". $_POST['user_manage_ports'] ."',
               user_manage_protocols='". $_POST['user_manage_protocols'] ."',
               user_manage_targets='". $_POST['user_manage_targets'] ."',
               user_manage_users='". $_POST['user_manage_users'] ."',
               user_manage_options='". $_POST['user_manage_options'] ."',
               user_manage_servicelevels='". $_POST['user_manage_servicelevels'] ."',
               user_load_rules='". $_POST['user_load_rules'] ."',
               user_show_rules='". $_POST['user_show_rules'] ."',
               user_show_monitor='". $_POST['user_show_monitor'] ."',
               user_active='". $_POST['user_active'] ."'
            WHERE
               user_idx='". $_POST['user_idx'] ."'
         ");

         if($_POST['user_pass1'] != "nochangeMS") {
            $db->db_query("
               UPDATE ". MYSQL_PREFIX ."users
               SET
                  user_pass='". md5($_POST['user_pass1']) ."' 
               WHERE
                  user_idx='". $_POST['user_idx'] ."'
            ");
         }
      }
		  
      return "ok";

   } // store()

   /**
    * delete user
    */
   public function delete()
   {
      global $db;

      if(isset($_POST['idx']) && is_numeric($_POST['idx'])) {
         $idx = $_POST['idx'];

         $db->db_query("
            DELETE FROM ". MYSQL_PREFIX ."users
            WHERE
               user_idx='". $idx ."'
         ");

         return "ok";
	    }

      return "unkown error";
   
   } // delete()

   /**
    * toggle user active/inactive
    */
   public function toggleStatus()
   {
      if(isset($_POST['idx']) && is_numeric($_POST['idx'])) {
         if($_POST['to'] == 1)
            $new_status='Y';
         else
            $new_status='N';

         $db->db_query("
            UPDATE ". MYSQL_PREFIX ."users
            SET
               user_active='". $new_status ."'
            WHERE
               user_idx='". $_POST['idx'] ."'");

         return "ok";
      }
   
      return "unkown error";

   } // toggleStatus()

   /**
    * checks if provided user name already exists
    * and will return true if so.
    */
   private function checkUserExists($user_name)
   {
      global $db;

      if($db->db_fetchSingleRow("
         SELECT user_idx
         FROM ". MYSQL_PREFIX ."users
         WHERE
            user_name LIKE BINARY '". $user_name ."'
         ")) {
         return true;
      }

      return false;

   } // checkTargetExists()

} // class User

$obj = new User;
$obj->handler();

?>
