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

class MASTERSHAPER_INTERFACES extends MASTERSHAPER_PAGE {

   /**
    * MASTERSHAPER_INTERFACES constructor
    *
    * Initialize the MASTERSHAPER_INTERFACES class
    */
   public function __construct()
   {
      $this->rights = 'user_manage_options';

   } // __construct()
  
   /**
    * display all interfaces
    */
   public function showList()
   {
      global $db, $tmpl;

      $this->avail_interfaces = Array();
      $this->interfaces = Array();

      $res_interfaces = $db->db_query("
         SELECT *
         FROM ". MYSQL_PREFIX ."interfaces
         ORDER BY if_name ASC
      ");
   
      $cnt_interfaces = 0;
	
      while($if = $res_interfaces->fetchrow()) {
         $this->avail_interfaces[$cnt_interfaces] = $if->if_idx;
         $this->interfaces[$if->if_idx] = $if;
         $cnt_interfaces++;
      }

      $tmpl->register_block("interface_list", array(&$this, "smarty_interface_list"));
      return $tmpl->fetch("interfaces_list.tpl");

   } // showList()

   /**
    * interface for handling
    */
   public function showEdit()
   {
      if($this->is_storing())
         $this->store();

      global $db, $tmpl, $page;

      if($page->id != 0) {
         $if = $db->db_fetchSingleRow("
            SELECT *
            FROM ". MYSQL_PREFIX ."interfaces
            WHERE 
               if_idx='". $page->id ."'");

         $tmpl->assign('if_idx', $page->id);
         $tmpl->assign('if_name', $if->if_name);
         $tmpl->assign('if_speed', $if->if_speed);
         $tmpl->assign('if_active', $if->if_active);
         $tmpl->assign('if_ifb', $if->if_ifb);
    
      }

      return $tmpl->fetch("interfaces_edit.tpl");

   } // showEdit()

   /**
    * template function which will be called from the interface listing template
    */
   public function smarty_interface_list($params, $content, &$smarty, &$repeat)
   {
      global $tmpl;

      $index = $smarty->get_template_vars('smarty.IB.if_list.index');
      if(!$index) {
         $index = 0;
      }

      if($index < count($this->avail_interfaces)) {

        $if_idx = $this->avail_interfaces[$index];
        $if =  $this->interfaces[$if_idx];

         $tmpl->assign('if_idx', $if_idx);
         $tmpl->assign('if_name', $if->if_name);
         $tmpl->assign('if_speed', $if->if_speed);
         $tmpl->assign('if_active', $if->if_active);

         $index++;
         $tmpl->assign('smarty.IB.if_list.index', $index);
         $repeat = true;
      }
      else {
         $repeat =  false;
      }

      return $content;

   } // smarty_interfaces_list()

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

} // class MASTERSHAPER_INTERFACES

$obj = new MASTERSHAPER_INTERFACES;
$obj->handler();

?>
