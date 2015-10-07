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

class Page_Users extends MASTERSHAPER_PAGE {

   /**
    * Page_Users constructor
    *
    * Initialize the Page_Users class
    */
   public function __construct()
   {
      $this->rights = 'user_manage_users';

   } // __construct()
  
   public function showList()
   {
      global $db, $tmpl;

      $this->avail_users = Array();
      $this->users = Array();

      $cnt_users = 0;

      $res_users = $db->db_query("
         SELECT
            *
         FROM
            ". MYSQL_PREFIX ."users
         ORDER BY
            user_name ASC
      ");
	
      while($user = $res_users->fetch()) {
         $this->avail_users[$cnt_users] = $user->user_idx;
         $this->users[$user->user_idx] = $user;
         $cnt_users++;
      }

      $tmpl->registerPlugin("block", "user_list", array(&$this, "smarty_user_list"));
      return $tmpl->fetch("users_list.tpl"); 

   } // showList()

   /**
    * display interface to create or edit users
    */
   public function showEdit()
   {
      if($this->is_storing())
         $this->store();

      global $db, $tmpl, $page;

      if(isset($page->id) && $page->id != 0) {
         $user = new User($page->id);
         $tmpl->assign('is_new', false);
      }
      else {
         $user = new User;
         $tmpl->assign('is_new', true);
         $page->id = NULL;
      }

      $tmpl->assign('user', $user);
      return $tmpl->fetch("users_edit.tpl");

   } // showEdit()
     
   /** 
    * store user values
    */
   public function store()
   {
      global $ms, $db, $rewriter;

      isset($_POST['new']) && $_POST['new'] == 1 ? $new = 1 : $new = NULL;

      /* load user */
      if(isset($new))
         $user = new User;
      else
         $user = new User($_POST['user_idx']);

      if(!isset($_POST['user_name']) || $_POST['user_name'] == "") {
         $ms->throwError(_("Please enter a user name!"));
      }
      if(isset($new) && $ms->check_object_exists('user', $_POST['user_name'])) {
         $ms->throwError(_("A user with such a user name already exist!"));
      }
      if($_POST['user_pass1'] == "") {
         $ms->throwError(_("Empty passwords are not allowed!"));
      }
      if($_POST['user_pass1'] != $_POST['user_pass2']) {
         $ms->throwError(_("The two entered passwords do not match!"));
      }	       

      if($_POST['user_pass1'] != "nochangeMS") {
         $_POST['user_pass'] = md5($_POST['user_pass1']);
         unset($_POST['user_pass1']);
         unset($_POST['user_pass2']);
      }

      $user_data = $ms->filter_form_data($_POST, 'user_');

      if(!$user->update($user_data))
         return false;

      if(!$user->save())
         return false;

      if(isset($_POST['add_another']) && $_POST['add_another'] == 'Y')
         return true;

      $ms->set_header('Location', $rewriter->get_page_url('Users List'));
      return true;

   } // store()

   /**
    * template function which will be called from the user listing template
    */
   public function smarty_user_list($params, $content, &$smarty, &$repeat)
   {
      $index = $smarty->getTemplateVars('smarty.IB.user_list.index');
      if(!$index) {
         $index = 0;
      }

      if($index < count($this->avail_users)) {

         $user_idx = $this->avail_users[$index];
         $user =  $this->users[$user_idx];

         $smarty->assign('user_idx', $user_idx);
         $smarty->assign('user_name', $user->user_name);
         $smarty->assign('user_active', $user->user_active);

         $index++;
         $smarty->assign('smarty.IB.user_list.index', $index);
         $repeat = true;
      }
      else {
         $repeat =  false;
      }

      return $content;

   } // smarty_user_list()


   private function getPermissions($user_idx)
   {
      global $db;

      $string = "";

      if($user = $db->db_fetchSingleRow("SELECT * FROM ". MYSQL_PREFIX ."users WHERE user_idx='". $user_idx ."'")) {

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

} // class Page_Users

$obj = new Page_Users;
$obj->handler();

?>
