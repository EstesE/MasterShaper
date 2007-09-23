<?php

/***************************************************************************
 *
 * Copyright (c) by Andreas Unterkircher
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

define('MANAGE_POS_CHAINS', 1);
define('MANAGE_POS_PIPES', 2);
define('MANAGE_POS_NETPATHS', 3);

class MASTERSHAPER_TARGETS {

   var $db;
   var $parent;
   var $tmpl;

   /* Class constructor */
   function MASTERSHAPER_TARGETS($parent)
   {
      $this->db = $parent->db;
      $this->parent = &$parent;
      $this->tmpl = &$this->parent->tmpl;

   } //MASTERSHAPER_TARGETS()

   /* interface output */
   function show()
   {
      /* If authentication is enabled, check permissions */
      if($this->parent->getOption("authentication") == "Y" &&
         !$this->parent->checkPermissions("user_show_rules")) {

         $this->parent->printError("<img src=\"". ICON_HOME ."\" alt=\"home icon\" />&nbsp;". _("MasterShaper Ruleset targets"), _("You do not have enough permissions to access this module!"));
         return 0;
      }

      if(!isset($this->parent->screen))
         $this->parent->screen = 0;

      $this->avail_targets = Array();
      $this->targets = Array(); 

      $res_targets = $this->db->db_query("
         SELECT target_idx, target_name, target_match
         FROM ". MYSQL_PREFIX ."targets
         ORDER BY target_name ASC
      ");

      $cnt_targets = 0;

      while($target = $res_targets->fetchrow()) {

         $this->avail_targets[$cnt_targets] = $target->target_idx;
         $this->targets[$target->target_idx] = $target;

         $cnt_targets++;

      }

      $this->tmpl->register_block("target_list", array(&$this, "smarty_target_list"));
      $this->tmpl->show("targets_list.tpl");

   } // show()

   function showEdit()
   {
      /* If authentication is enabled, check permissions */
      if($this->parent->getOption("authentication") == "Y" &&
         !$this->parent->checkPermissions("user_show_rules")) {

         $this->parent->printError("<img src=\"". ICON_HOME ."\" alt=\"home icon\" />&nbsp;". _("MasterShaper Ruleset targets"), _("You do not have enough permissions to access this module!"));
         return 0;
      }

      $target = $this->db->db_fetchSingleRow("
         SELECT *
         FROM ". MYSQL_PREFIX ."targets
         WHERE
            target_idx='". $_GET['idx'] ."'
      ");

      $this->tmpl->register_function("target_select_list", array(&$this, "smarty_target_select_list"), false);
      $this->tmpl->show("targets_edit.tpl");

   } // showEdit()

   /**
    * template function which will be called from the target listing template
    */
   public function smarty_target_list($params, $content, &$smarty, &$repeat) 
   {
      $index = $this->tmpl->get_template_vars('smarty.IB.target_list.index');
      if(!$index) {
         $index = 0;
      }

      if($index < count($this->avail_targets)) {

        $target_idx = $this->avail_targets[$index];
        $target =  $this->targets[$target_idx];
         
         $this->tmpl->assign('target_idx', $target_idx);
         $this->tmpl->assign('target_name', $target->target_name);
         switch($target->target_match) {
            case 'IP':    $this->tmpl->assign('target_type', _("IP match")); break;
            case 'MAC':   $this->tmpl->assign('target_type', _("MAC match")); break;
            case 'GROUP': $this->tmpl->assign('target_type', _("Target Group")); break;
         }

         $index++;
         $this->tmpl->assign('smarty.IB.target_list.index', $index);
         $repeat = true;
      }
      else {
         $repeat =  false;
      }  

      return $content;

   } // smarty_target_list()

   public function smarty_edit()
   {
   
      switch($bla) {
         case 'edit':

            if(!$saveit) {
            }
            else {
               $error = 0;

               if(!isset($_POST['target_name']) || $_POST['target_name'] == "") {
                  $this->parent->printError("<img src=\"". ICON_TARGETS ."\" alt=\"target icon\" />&nbsp;". _("Manage Target"), _("Please enter a name for this target!"));
                  $error = 1;
               }
               if(!$error && isset($_GET['new']) && $this->db->db_fetchSingleRow("SELECT target_idx FROM ". MYSQL_PREFIX ."targets WHERE target_name LIKE BINARY '". $_POST['target_name'] ."'")) {
                  $this->parent->printError("<img src=\"". ICON_TARGETS ."\" alt=\"target icon\" />&nbsp;". _("Manage Target"), _("A target with that name already exists!"));
                  $error = 1;
               }
               if(!$error && !isset($_GET['new']) && $_GET['namebefore'] != $_POST['target_name'] && $this->db->db_fetchSingleRow("SELECT target_idx FROM ". MYSQL_PREFIX ."targets WHERE target_name LIKE BINARY '". $_POST['target_name'] ."'")) {
                  $this->parent->printError("<img src=\"". ICON_TARGETS ."\" alt=\"target icon\" />&nbsp;". _("Manage Target"), _("A target with that name already exists!"));
                  $error = 1;
               }
               if(!$error && $_POST['target_match'] == "IP" && $_POST['target_ip'] == "") {
                  $this->parent->printError("<img src=\"". ICON_TARGETS ."\" alt=\"target icon\" />&nbsp;". _("Manage Target"), _("You have selected IP match but didn't entered a IP address!"));
                  $error = 1;
               }
               elseif(!$error && $_POST['target_match'] == "IP" && $_POST['target_ip'] != "") {
                  /* Is target_ip a ip range seperated by "-" */
                  if(strstr($_POST['target_ip'], "-") !== false) {
                     $hosts = split("-", $_POST['target_ip']);
                     foreach($hosts as $host) {
                        $ipv4 = new Net_IPv4;
                        if(!$error && !$ipv4->validateIP($host)) {
                           $this->parent->printError("<img src=\"". ICON_TARGETS ."\" alt=\"target icon\" />&nbsp;". _("Manage Target"), _("Incorrect IP address in IP range definition! Please enter a valid IP address!"));
                           $error = 1;
                        }
                     }
                  }

                  /* Is target_ip a network */
                  elseif(strstr($_POST['target_ip'], "/") !== false) {
                     $ipv4 = new Net_IPv4;
                     $net = $ipv4->parseAddress($_POST['target_ip']);
                     if($net->netmask == "" || $net->netmask == "0.0.0.0") {
                        $this->parent->printError("<img src=\"". ICON_TARGETS ."\" alt=\"target icon\" />&nbsp;". _("Manage Target"), _("Incorrect CIDR address! Please enter a valid network address!"));
                        $error = 1;
                     }
                  }

                  /* target_ip is a simple IP */
                  else {
                     $ipv4 = new Net_IPv4;
                     if(!$ipv4->validateIP($_POST['target_ip'])) {
                        $this->parent->printError("<img src=\"". ICON_TARGETS ."\" alt=\"target icon\" />&nbsp;". _("Manage Target"), _("Incorrect IP address! Please enter a valid IP address!"));
                        $error = 1;
                     }
                  }
               }

               /* MAC address specified? */
               if(!$error && $_POST['target_match'] == "MAC" && $_POST['target_mac'] == "") {
                  $this->parent->printError("<img src=\"". ICON_TARGETS ."\" alt=\"target icon\" />&nbsp;". _("Manage Target"), _("You have selected MAC match but didn't entered a MAC address!"));
                  $error = 1;
               }
               elseif(!$error && $_POST['target_match'] == "MAC" && $_POST['target_mac'] != "") {
                  if(!preg_match("/(.*):(.*):(.*):(.*):(.*):(.*)/", $_POST['target_mac']) && !preg_match("/(.*)-(.*)-(.*)-(.*)-(.*)-(.*)/", $_POST['target_mac'])) {
                     $this->parent->printError("<img src=\"". ICON_TARGETS ."\" alt=\"target icon\" />&nbsp;". _("Manage Target"), _("You have selected MAC match but specified a INVALID MAC address! Please specify a correct MAC address!"));
                     $error = 1;
                  }
               }
               if(!$error && $_POST['target_match'] == "GROUP" && count($_POST['used']) <= 1) {
                  $this->parent->printError("<img src=\"". ICON_TARGETS ."\" alt=\"target icon\" />&nbsp;". _("Manage Target"), _("You have selected Group match but didn't selected at least one target from the list!"));
                  $error = 1;
               }

               if(!$error) {
                  if(isset($_GET['new'])) {
                     $this->db->db_query("
                        INSERT INTO ". MYSQL_PREFIX ."targets
                           (target_name, target_match, target_ip, target_mac)
                        VALUES  (
                           '". $_POST['target_name'] ."',
                           '". $_POST['target_match'] ."',
                           '". $_POST['target_ip'] ."',
                           '". $_POST['target_mac'] ."'
                        )
                     ");
                     $_GET['idx'] = $this->db->db_getid();

                  }
                  else {
                     $this->db->db_query("
                        UPDATE ". MYSQL_PREFIX ."targets
                        SET 
                           target_name='". $_POST['target_name'] ."',
                           target_match='". $_POST['target_match'] ."',
                           target_ip='". $_POST['target_ip'] ."',
                           target_mac='". $_POST['target_mac'] ."'
                           WHERE target_idx='". $_GET['idx'] ."'
                     ");
                  }

                  if($_POST['used']) {
                     $this->db->db_query("DELETE FROM ". MYSQL_PREFIX ."assign_target_groups WHERE atg_group_idx='". $_GET['idx'] ."'");
		               foreach($_POST['used'] as $use) {
                        if($use != "") {
                           $this->db->db_query("INSERT INTO ". MYSQL_PREFIX ."assign_target_groups (atg_group_idx, atg_target_idx) "
                           ."VALUES ('". $_GET['idx'] ."', '". $use ."')");
			               }
                     }
                  }

                  $this->parent->goBack();
               }
            }
            break;

         case DELETE:

            //$this->parent->printYesNo("<img src=\"". ICON_TARGETS ."\" alt=\"target icon\" />&nbsp;". _("Delete Target"), _("Delete target") ." ". $_GET['name'] ."?");
            if(isset($_GET['idx'])) {
               $this->db->db_query("DELETE FROM ". MYSQL_PREFIX ."targets WHERE target_idx='". $_GET['idx'] ."'");
               $this->db->db_query("DELETE FROM ". MYSQL_PREFIX ."assign_target_groups WHERE atg_group_idx='". $_GET['idx'] ."'");
               $this->db->db_query("DELETE FROM ". MYSQL_PREFIX ."assign_target_groups WHERE atg_target_idx='". $_GET['idx'] ."'");
               $this->parent->goBack();
            }
            break;
      }



   } // edit()

   public function smarty_target_select_list($params, &$smarty)
   {
      if(!array_key_exists('group', $params)) {
         $this->tmpl->trigger_error("getSLList: missing 'group' parameter", E_USER_WARNING);
         $repeat = false;
         return;
      }
      $result = $this->db->db_query("
         SELECT target_idx, target_name
         FROM ". MYSQL_PREFIX ."targets 
         WHERE
            target_match<>'GROUP'
         AND
            target_idx<>'". $_GET['idx'] ."'
         ORDER BY target_name ASC
      ");

      while($row = $result->fetchRow()) {
         if(!$this->db->db_fetchSingleRow("
            SELECT atg_idx
            FROM ". MYSQL_PREFIX ."assign_target_groups
            WHERE 
               atg_group_idx='". $_GET['idx'] ."'
            AND
               atg_target_idx='". $row->target_idx ."'   
            ")) {

         $string = "<option value=\"". $row->target_idx ."\">". $row->target_name ."</option>";
        }
      }
   } // smarty_target_select_list()
}

?>
