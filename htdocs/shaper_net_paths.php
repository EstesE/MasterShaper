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

class MASTERSHAPER_NETPATHS {

   var $db;
   var $parent;
   var $tmpl;

   /* Class constructor */
   function MASTERSHAPER_NETPATHS($parent)
   {
      $this->parent = &$parent;
      $this->db = &$parent->db;
      $this->tmpl = &$parent->tmpl;

   } // MASTERSHAPER_NETPATHS()

   /* interface output */
   function show()
   {
      /* If authentication is enabled, check permissions */
      if($this->parent->getOption("authentication") == "Y" &&
         !$this->parent->checkPermissions("user_manage_options")) {
         $this->parent->printError("<img src=\"". ICON_INTERFACES ."\" alt=\"interface icon\" />&nbsp;". _("Manage Network Paths"), _("You do not have enough permissions to access this module!"));
         return 0;
      }

      if(!isset($_GET['mode'])) {
         $_GET['mode'] = "show";
      }
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

   /**
    * list all netpaths
    */
   private function showList()
   {
      $this->avail_netpaths = Array();
      $this->netpaths = Array();

      $res_netpaths = $this->db->db_query("
         SELECT *
         FROM ". MYSQL_PREFIX ."network_paths
         ORDER BY netpath_name ASC
      ");

      $cnt_netpaths = 0;
	
      while($np = $res_netpaths->fetchrow()) {
         $this->avail_netpaths[$cnt_netpaths] = $np->netpath_idx;
         $this->netpaths[$np->netpath_idx] = $np;
         $cnt_netpaths++;
      }

      $this->tmpl->register_block("netpath_list", array(&$this, "smarty_netpath_list"));
      $this->tmpl->show("net_paths_list.tpl");
   
   } // showList() 

   /**
    * interface for handling
    */
   private function showEdit($idx)
   {
      if($idx != 0) {
         $np = $this->db->db_fetchSingleRow("
            SELECT *
            FROM ". MYSQL_PREFIX ."network_paths
            WHERE
               netpath_idx='". $idx ."'
         ");

         $this->tmpl->assign('netpath_idx', $idx);
         $this->tmpl->assign('netpath_name', $np->netpath_name);
         $this->tmpl->assign('netpath_if1', $np->netpath_if1);
         $this->tmpl->assign('netpath_if2', $np->netpath_if2);
         $this->tmpl->assign('netpath_imq', $np->netpath_imq);
         $this->tmpl->assign('netpath_active', $np->netpath_active);
    
      }

      $this->tmpl->register_function("if_select_list", array(&$this, "smarty_if_select_list"), false);
      $this->tmpl->show("net_paths_edit.tpl");

   } // showEdit()

   /**
    * template function which will be called from the netpath listing template
    */
   public function smarty_netpath_list($params, $content, &$smarty, &$repeat)
   {
      $index = $this->tmpl->get_template_vars('smarty.IB.netpath_list.index');
      if(!$index) {
         $index = 0;
      }

      if($index < count($this->avail_netpaths)) {

        $netpath_idx = $this->avail_netpaths[$index];
        $netpath =  $this->netpaths[$netpath_idx];

         $this->tmpl->assign('netpath_idx', $netpath_idx);
         $this->tmpl->assign('netpath_name', $netpath->netpath_name);
         $this->tmpl->assign('netpath_active', $netpath->netpath_active);
         $this->tmpl->assign('netpath_if1', $this->parent->getInterfaceName($netpath->netpath_if1));
         $this->tmpl->assign('netpath_if2', $this->parent->getInterfaceName($netpath->netpath_if2));

         $index++;
         $this->tmpl->assign('smarty.IB.netpath_list.index', $index);
         $repeat = true;
      }
      else {
         $repeat =  false;
      }

      return $content;

   } // smarty_netpath_list()
   
   /**
    * handle updates
    */
   public function store()
   {
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
         $max_pos = $this->db->db_fetchSingleRow("
            SELECT MAX(netpath_position) as pos
            FROM ". MYSQL_PREFIX ."network_paths
         ");
         $this->db->db_query("
            INSERT INTO ". MYSQL_PREFIX ."network_paths (
               netpath_name, netpath_if1, netpath_if2, netpath_position,
               netpath_imq, netpath_active
            ) VALUES (
               '". $_POST['netpath_name'] ."',
               '". $_POST['netpath_if1'] ."',
               '". $_POST['netpath_if2'] ."',
               '". ($max_pos->pos+1) ."',
               '". $_POST['netpath_imq'] ."',
               '". $_POST['netpath_active'] ."'
            )
         ");
      }
      else {
         $this->db->db_query("
            UPDATE ". MYSQL_PREFIX ."network_paths
            SET
               netpath_name='". $_POST['netpath_name'] ."',
               netpath_if1='". $_POST['netpath_if1'] ."',
               netpath_if2='". $_POST['netpath_if2'] ."',
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
      if(isset($_POST['idx']) && is_numeric($_POST['idx'])) {
         $idx = $_POST['idx'];

         $this->db->db_query("
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
      if(isset($_POST['idx']) && is_numeric($_POST['idx'])) {
         $idx = $_POST['idx'];

         if($_POST['to'] == 1)
            $new_status = 'Y';
         else
            $new_status = 'N';

         $this->db->db_query("
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
    * this function will return a select list full of interfaces
    */
   public function smarty_if_select_list($params, &$smarty)
   {
      if(!array_key_exists('if_idx', $params)) {
         $this->tmpl->trigger_error("getSLList: missing 'if_idx' parameter", E_USER_WARNING);
         $repeat = false;
         return;
      }
      $result = $this->db->db_query("
         SELECT *
         FROM ". MYSQL_PREFIX ."interfaces
         ORDER BY if_name ASC
      ");

      while($row = $result->fetchRow()) {
         $string.= "<option value=\"". $row->if_idx ."\"";
         if($params['if_idx'] == $row->if_idx)
            $string.= " selected=\"selected\"";
         $string.= ">". $row->if_name ."</option>";
      }

      return $string;

   } // smarty_if_select_list()

   /**
    * checks if provided network path name already exists
    * and will return true if so.
    */
   private function checkNetworkPathExists($netpath_name)
   {
      if($this->db->db_fetchSingleRow("
         SELECT netpath_idx
         FROM ". MYSQL_PREFIX ."network_paths
         WHERE
            netpath_name LIKE BINARY '". $netpath_name ."'
         ")) {
         return true;
      } 
      return false;
   } // checkNetworkPathExists()




}

?>
