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

class MASTERSHAPER_PORTS {

   private $db;
   private $parent;
   private $tmpl;

   /**
    * MASTERSHAPER_PORTS constructor
    *
    * Initialize the MASTERSHAPER_PORTS class
    */
   public function __construct(&$parent)
   {
      $this->parent = $parent;
      $this->db = $parent->db;
      $this->tmpl = $this->parent->tmpl;

   } // __construct()

   /* interface output */
   public function show()
   {
      /* If authentication is enabled, check permissions */
      if($this->parent->getOption("authentication") == "Y" &&
         !$this->parent->checkPermissions("user_manage_ports")) {
         $this->parent->printError("<img src=\"". ICON_PORTS ."\" alt=\"port icon\" />&nbsp;". _("Manage Ports"), _("You do not have enough permissions to access this module!"));
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
    * display all ports
    */
   private function showList()
   {
      if(!isset($this->parent->screen))
         $this->parent->screen = 0;

      $this->avail_ports = Array();
      $this->ports = Array();

      if(!isset($_GET['orderby']))
         $_GET['orderby'] = "port_name";
      if(!isset($_GET['sortorder']))
         $_GET['sortorder'] = "ASC";
      if(!isset($_GET['breaker']))
         $_GET['breaker'] = 'A';

      if(isset($_GET['breaker']) && $_GET['breaker'] != "#") {
         $res_ports = $this->db->db_query("
            SELECT *
            FROM ". MYSQL_PREFIX ."ports
            WHERE
               port_name REGEXP '^". $_GET['breaker'] ."'
            ORDER BY ". $_GET['orderby'] ." ". $_GET['sortorder']
         );
      }
      else {
         $res_ports = $this->db->db_query("
            SELECT *
            FROM ". MYSQL_PREFIX ."ports
            ORDER BY ". $_GET['orderby'] ." ". $_GET['sortorder']
         );
      }

      $cnt_ports = 0;
	
      while($port = $res_ports->fetchrow()) {
         $this->avail_ports[$cnt_ports] = $port->port_idx;
         $this->ports[$port->port_idx] = $port;
         $cnt_ports++;
      }

      $breakers = Array();
      $breakers = array_merge($breakers, range('A', 'Z'));
      $breakers = array_merge($breakers, range(0, 9));
      array_push($breakers, '#');

      $this->tmpl->assign('breakers', $breakers);
      $this->tmpl->register_block("port_list", array(&$this, "smarty_port_list"));
      $this->tmpl->show("ports_list.tpl");

   } // showList()

   /**
    * display interface to create or edit ports
    */
   public function showEdit($idx)
   {
      /* If authentication is enabled, check permissions */
      if($this->parent->getOption("authentication") == "Y" &&
         !$this->parent->checkPermissions("user_manage_ports")) {

         $this->parent->printError("<img src=\"". ICON_HOME ."\" alt=\"home icon\" />&nbsp;". _("MasterShaper Ruleset ports"), _("You do not have enough permissions to access this module!"));
         return 0;
      }

      if($idx != 0) {
         $port = $this->db->db_fetchSingleRow("
            SELECT *
            FROM ". MYSQL_PREFIX ."ports
            WHERE
               port_idx='". $idx ."'
         ");

         $this->tmpl->assign('port_idx', $idx);
         $this->tmpl->assign('port_name', $port->port_name);
         $this->tmpl->assign('port_desc', $port->port_desc);
         $this->tmpl->assign('port_number', $port->port_number);
 
      }
      else {
         /* preset values here */
      }

     $this->tmpl->show("ports_edit.tpl");

   } // showEdit()


   /**
    * template function which will be called from the port listing template
    */
   public function smarty_port_list($params, $content, &$smarty, &$repeat)
   {
      $index = $this->tmpl->get_template_vars('smarty.IB.port_list.index');
      if(!$index) {
         $index = 0;
      }

      if($index < count($this->avail_ports)) {

         $port_idx = $this->avail_ports[$index];
         $port =  $this->ports[$port_idx];

         $this->tmpl->assign('port_idx', $port_idx);
         $this->tmpl->assign('port_name', $port->port_name);
         $this->tmpl->assign('port_desc', $port->port_desc);
         $this->tmpl->assign('port_number', $port->port_number);

         $index++;
         $this->tmpl->assign('smarty.IB.port_list.index', $index);
         $repeat = true;
      }
      else {
         $repeat =  false;
      }

      return $content;

   } // smarty_port_list()

   /**
    * handle updates
    */
   public function store()
   {
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

         $this->db->db_query("
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
		     $this->db->db_query("
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
      if($this->db->db_fetchSingleRow("
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
      if(isset($_POST['idx'])) {
         $idx = $_POST['idx'];

         $this->db->db_query("
            DELETE FROM ". MYSQL_PREFIX ."ports
            WHERE
               port_idx='". $idx ."'
         ");
         $this->db->db_query("
            DELETE FROM ". MYSQL_PREFIX ."assign_ports_to_filters
            WHERE
               afp_port_idx='". $idx ."'
         ");
   
         return "ok";
      }

      return "unkown error";

   } // delete()

} // class MASTERSHAPER_PORTS

?>
