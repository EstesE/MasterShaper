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

class MASTERSHAPER_PROTOCOLS {

   var $db;
   var $parent;
   var $tmpl;

   /* Class constructor */
   function MASTERSHAPER_PROTOCOLS($parent)
   {
      $this->parent = &$parent;
      $this->db = &$parent->db;
      $this->tmpl = &$this->parent->tmpl;

   } // MASTERSHAPER_PROTOCOLS()

   /* interface output */
   function show()
   {
      /* If authentication is enabled, check permissions */
      if($this->parent->getOption("authentication") == "Y" &&
         !$this->parent->checkPermissions("user_manage_protocols")) {
         $this->parent->printError("<img src=\"". ICON_PROTOCOLS ."\" alt=\"protocol icon\" />&nbsp;". _("Manage Protocols"), _("You do not have enough permissions to access this module!"));
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
    * display all protocols
    */
   private function showList()
   {
      if(!isset($this->parent->screen))
         $this->parent->screen = 0;

      $this->avail_protocols = Array();
      $this->protocols = Array();

      $res_protocols = $this->db->db_query("
         SELECT *
         FROM ". MYSQL_PREFIX ."protocols
         ORDER BY proto_name ASC
      ");

      $cnt_protocols = 0;
	
      while($protocol = $res_protocols->fetchrow()) {
         $this->avail_protocols[$cnt_protocols] = $protocol->proto_idx;
         $this->protocols[$protocol->proto_idx] = $protocol;
         $cnt_protocols++;
      }

      $this->tmpl->register_block("protocol_list", array(&$this, "smarty_protocol_list"));
      $this->tmpl->show("protocols_list.tpl");

   } // showList()

   /**
    * display interface to create or edit protocols
    */
   function showEdit($idx)
   {
      /* If authentication is enabled, check permissions */
      if($this->parent->getOption("authentication") == "Y" &&
         !$this->parent->checkPermissions("user_manage_protocols")) {

         $this->parent->printError("<img src=\"". ICON_HOME ."\" alt=\"home icon\" />&nbsp;". _("MasterShaper Ruleset protocols"), _("You do not have enough permissions to access this module!"));
         return 0;
      }

      if($idx != 0) {
         $protocol = $this->db->db_fetchSingleRow("
            SELECT *
            FROM ". MYSQL_PREFIX ."protocols
            WHERE
               proto_idx='". $idx ."'
         ");

         $this->tmpl->assign('proto_idx', $idx);
         $this->tmpl->assign('proto_name', $protocol->proto_name);
         $this->tmpl->assign('proto_desc', $protocol->proto_desc);
         $this->tmpl->assign('proto_number', $protocol->proto_number);
 
      }
      else {
         /* preset values here */
      }

     $this->tmpl->show("protocols_edit.tpl");

   } // showEdit()


   /**
    * template function which will be called from the protocol listing template
    */
   public function smarty_protocol_list($params, $content, &$smarty, &$repeat)
   {
      $index = $this->tmpl->get_template_vars('smarty.IB.protocol_list.index');
      if(!$index) {
         $index = 0;
      }

      if($index < count($this->avail_protocols)) {

         $proto_idx = $this->avail_protocols[$index];
         $protocol =  $this->protocols[$proto_idx];

         $this->tmpl->assign('proto_idx', $proto_idx);
         $this->tmpl->assign('proto_name', $protocol->proto_name);
         $this->tmpl->assign('proto_desc', $protocol->proto_desc);
         $this->tmpl->assign('proto_number', $protocol->proto_number);

         $index++;
         $this->tmpl->assign('smarty.IB.protocol_list.index', $index);
         $repeat = true;
      }
      else {
         $repeat =  false;
      }

      return $content;

   } // smarty_protocol_list()

   /**
    * handle updates
    */
   public function store()
   {
      isset($_POST['proto_new']) && $_POST['proto_new'] == 1 ? $new = 1 : $new = NULL;

      if(!isset($_POST['proto_name']) || $_POST['proto_name'] == "") {
         return _("Please enter a protocol name!");
      }
      if(isset($new) && $this->checkProtocolExists($_POST['proto_name'])) {
         return _("A protocol with that name already exists!");
      }
      if(!isset($new) && $_POST['namebefore'] != $_POST['proto_name']
         && $this->checkProtocolExists($_POST['proto_name'])) {
         return _("A protocol with that name already exists!");
      }
      if(!is_numeric($_POST['proto_number'])) {
         return _("Protocol number needs to be an integer value!");
      }

      if(isset($new)) {

         $this->db->db_query("
            INSERT INTO ". MYSQL_PREFIX ."protocols 
               (proto_name, proto_desc, proto_number, proto_user_defined)
            VALUES (
               '". $_POST['proto_name'] ."',
               '". $_POST['proto_desc'] ."',
               '". $_POST['proto_number'] ."',
               'Y')
         ");
      }
      else {
		     $this->db->db_query("
               UPDATE ". MYSQL_PREFIX ."protocols
               SET 
                  proto_name='". $_POST['proto_name'] ."',
                  proto_desc='". $_POST['proto_desc'] ."',
                  proto_number='". $_POST['proto_number'] ."',
                  proto_user_defined='Y'
               WHERE
                  proto_idx='". $_POST['proto_idx'] ."'
            ");
      }

      return "ok";

   } // store()

   /**
    * checks if provided protocol name already exists
    * and will return true if so.
    */
   private function checkProtocolExists($proto_name)
   {
      if($this->db->db_fetchSingleRow("
         SELECT proto_idx
         FROM ". MYSQL_PREFIX ."protocols
         WHERE
            proto_name LIKE BINARY '". $proto_name ."'
         ")) {
         return true;
      } 
      return false;
   } // checkProtocolExists()

   /**
    * delete protocol
    */
   public function delete()
   {
      if(isset($_POST['idx'])) {
         $idx = $_POST['idx'];

         $this->db->db_query("
            DELETE FROM ". MYSQL_PREFIX ."protocols
            WHERE
               proto_idx='". $idx ."'
         ");
   
         return "ok";
      }

      return "unkown error";

   } // delete()
}

?>
