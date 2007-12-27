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

class MASTERSHAPER_USERS {

   var $db;
   var $parent;
   var $tmpl;

   /* Class constructor */
   function MASTERSHAPER_USERS($parent)
   {
      $this->parent = &$parent;
      $this->db = &$parent->db;
      $this->tmpl = &$parent->tmpl;

   } // MASTERSHAPER_USERS()
  
   /* interface output */
   function show()
   {
      /* If authentication is enabled, check permissions */
      if($this->parent->getOption("authentication") == "Y" &&
	 !$this->parent->checkPermissions("user_manage_users")) {

	 $this->parent->printError("<img src=\"". ICON_USERS ."\" alt=\"user icon\" />&nbsp;". _("Manage Users"), _("You do not have enough permissions to access this module!"));
	 return 0;

      }

      if(!isset($_GET['mode'])) 
         $_GET['mode'] = "show";
      if(!isset($_GET['idx']) ||
         (isset($_GET['idx']) && !is_numeric($_GET['idx'])))
         $_GET['idx'] = 0;

      switch($_GET['mode']) {
         default:
         case 'show':
            $this->showList();
            break;
         case 'new':
         case 'edit':
            $this->showEdit($_GET['idx']);
            break;
      }

   } // show()

   private function showList()
   {
      $this->avail_users = Array();
      $this->users = Array();

      $cnt_users = 0;

      $res_users = $this->db->db_query("
         SELECT *
         FROM ". MYSQL_PREFIX ."users
         ORDER BY user_name ASC
      ");
	
      while($user = $res_users->fetchrow()) {
         $this->avail_users[$cnt_users] = $user->user_idx;
         $this->users[$user->user_idx] = $user;
         $cnt_users++;
      }

      $this->tmpl->register_block("user_list", array(&$this, "smarty_user_list"));
      $this->tmpl->show("users_list.tpl"); 

   } // showList()

   /**
    * display interface to create or edit users
    */
   private function showEdit($idx)
   {
      /* If authentication is enabled, check permissions */
      if($this->parent->getOption("authentication") == "Y" &&
         !$this->parent->checkPermissions("user_manage_users")) {
         $this->parent->printError("<img src=\"". ICON_USERS ."\" alt=\"user icon\" />&nbsp;". _("Manage Users"), _("You do not have enough permissions to access this module!"));
         return 0;
      }

      if($idx != 0) {
         $user = $this->db->db_fetchSingleRow("
            SELECT *
            FROM ". MYSQL_PREFIX ."users
            WHERE
               user_idx='". $idx ."'
         ");

         $this->tmpl->assign('user_idx', $idx);
         $this->tmpl->assign('user_name', $user->user_name);
         $this->tmpl->assign('user_active', $user->user_active);
         $this->tmpl->assign('user_manage_chains', $user->user_manage_chains);
         $this->tmpl->assign('user_manage_pipes', $user->user_manage_pipes);
         $this->tmpl->assign('user_manage_filters', $user->user_manage_filters);
         $this->tmpl->assign('user_manage_ports', $user->user_manage_ports);
         $this->tmpl->assign('user_manage_protocols', $user->user_manage_protocols);
         $this->tmpl->assign('user_manage_targets', $user->user_manage_targets);
         $this->tmpl->assign('user_manage_users', $user->user_manage_users);
         $this->tmpl->assign('user_manage_options', $user->user_manage_options);
         $this->tmpl->assign('user_manage_servicelevels', $user->user_manage_servicelevels);
         $this->tmpl->assign('user_load_rules', $user->user_load_rules);
         $this->tmpl->assign('user_show_rules', $user->user_show_rules);
         $this->tmpl->assign('user_show_monitor', $user->user_show_monitor);

      }
   
      $this->tmpl->show("users_edit.tpl");

   } // showEdit()
     
   /** 
    * store user values
    */
   public function store()
   {

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

         $this->db->db_query("
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
         $this->db->db_query("
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
            $this->db->db_query("
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
      if(isset($_POST['idx']) && is_numeric($_POST['idx'])) {
         $idx = $_POST['idx'];

         $this->db->db_query("
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

         $this->db->db_query("
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
    * template function which will be called from the user listing template
    */
   public function smarty_user_list($params, $content, &$smarty, &$repeat)
   {
      $index = $this->tmpl->get_template_vars('smarty.IB.user_list.index');
      if(!$index) {
         $index = 0;
      }

      if($index < count($this->avail_users)) {

         $user_idx = $this->avail_users[$index];
         $user =  $this->users[$user_idx];

         $this->tmpl->assign('user_idx', $user_idx);
         $this->tmpl->assign('user_name', $user->user_name);
         $this->tmpl->assign('user_active', $user->user_active);

         $index++;
         $this->tmpl->assign('smarty.IB.user_list.index', $index);
         $repeat = true;
      }
      else {
         $repeat =  false;
      }

      return $content;

   } // smarty_user_list()


   function getPermissions($user_idx)
   {

      $string = "";

      if($user = $this->db->db_fetchSingleRow("SELECT * FROM ". MYSQL_PREFIX ."users WHERE user_idx='". $user_idx ."'")) {

         if($user->user_manage_chains == "Y")
	    $string.= "Chains, ";
         if($user->user_manage_pipes == "Y")
	    $string.= "Pipes, ";
         if($user->user_manage_filters == "Y")
	    $string.= "Filters, ";
         if($user->user_manage_ports == "Y")
	    $string.= "Ports, ";
         if($user->user_manage_protocols == "Y")
	    $string.= "Protocols, ";
         if($user->user_manage_targets == "Y")
	    $string.= "Targets, ";
         if($user->user_manage_users == "Y")
	    $string.= "Users, ";
         if($user->user_manage_options == "Y")
	    $string.= "Options, ";
         if($user->user_manage_servicelevels == "Y")
	    $string.= "Service Levels, ";
         if($user->user_load_rules == "Y")
	    $string.= "Load Rules, ";
         if($user->user_show_rules == "Y")
	    $string.= "Show Rules, ";
         if($user->user_show_monitor == "Y")
	    $string.= "Show Monitoring, ";

      }

      return substr($string, 0, strlen($string)-2);

   } // getPermissions()

   /**
    * checks if provided user name already exists
    * and will return true if so.
    */
   private function checkUserExists($user_name)
   {
      if($this->db->db_fetchSingleRow("
         SELECT user_idx
         FROM ". MYSQL_PREFIX ."users
         WHERE
            user_name LIKE BINARY '". $user_name ."'
         ")) {
         return true;
      }

      return false;
   } // checkTargetExists()

}

?>
