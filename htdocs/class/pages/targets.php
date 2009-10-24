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

class Page_Targets extends MASTERSHAPER_PAGE {

   /**
    * Page_Targets constructor
    *
    * Initialize the Page_Targets class
    */
   public function __construct()
   {
      $this->rights = 'user_manage_targets';

   } // __construct()

   /**
    * handle updates
    */
   public function store()
   {
      global $db;

      isset($_POST['target_new']) && $_POST['target_new'] == 1 ? $new = 1 : $new = NULL;

      if(!isset($_POST['target_name']) || $_POST['target_name'] == "") {
         return _("Please enter a name for this target!");
      }
      if(isset($new) && $this->checkTargetExists($_POST['target_name'])) { 
         return _("A target with that name already exists!");
      }
      if(!isset($new) && $_POST['namebefore'] != $_POST['target_name']
         && $this->checkTargetExists($_POST['target_name'] )) {
         return _("A target with that name already exists!");
      }
      if($_POST['target_match'] == "IP" && $_POST['target_ip'] == "") {
         return _("You have selected IP match but didn't entered a IP address!");
      }
      elseif($_POST['target_match'] == "IP" && $_POST['target_ip'] != "") {
         /* Is target_ip a ip range seperated by "-" */
         if(strstr($_POST['target_ip'], "-") !== false) {
            $hosts = split("-", $_POST['target_ip']);
            foreach($hosts as $host) {
               $ipv4 = new Net_IPv4;
               if(!$ipv4->validateIP($host)) {
                  return _("Incorrect IP address in IP range definition! Please enter a valid IP address!");
               }
            }
         }
         /* Is target_ip a network */
         elseif(strstr($_POST['target_ip'], "/") !== false) {
            $ipv4 = new Net_IPv4;
            $net = $ipv4->parseAddress($_POST['target_ip']);
            if($net->netmask == "" || $net->netmask == "0.0.0.0") {
               return _("Incorrect CIDR address! Please enter a valid network address!");
            }
         }
         /* target_ip is a simple IP */
         else {
            $ipv4 = new Net_IPv4;
            if(!$ipv4->validateIP($_POST['target_ip'])) {
               return _("Incorrect IP address! Please enter a valid IP address!");
            }
         }
      }
      /* MAC address specified? */
      if($_POST['target_match'] == "MAC" && $_POST['target_mac'] == "") {
         return _("You have selected MAC match but didn't entered a MAC address!");
      }
      elseif($_POST['target_match'] == "MAC" && $_POST['target_mac'] != "") {
         if(!preg_match("/(.*):(.*):(.*):(.*):(.*):(.*)/", $_POST['target_mac'])
            && !preg_match("/(.*)-(.*)-(.*)-(.*)-(.*)-(.*)/", $_POST['target_mac'])) {
            return _("You have selected MAC match but specified a INVALID MAC address! Please specify a correct MAC address!");
         }
      }
      if($_POST['target_match'] == "GROUP" && isset($_POST['used']) && count($_POST['used']) < 1) {
         return _("You have selected Group match but didn't selected at least one target from the list!");
      }

      if(isset($new)) {
         $db->db_query("
            INSERT INTO ". MYSQL_PREFIX ."targets
               (target_name, target_match, target_ip, target_mac)
            VALUES  (
               '". $_POST['target_name'] ."',
               '". $_POST['target_match'] ."',
               '". $_POST['target_ip'] ."',
               '". $_POST['target_mac'] ."'
            )
            ");

         $_POST['target_idx'] = $db->db_getId();

      }
      else {
         $db->db_query("
            UPDATE ". MYSQL_PREFIX ."targets
            SET 
               target_name='". $_POST['target_name'] ."',
               target_match='". $_POST['target_match'] ."',
               target_ip='". $_POST['target_ip'] ."',
               target_mac='". $_POST['target_mac'] ."'
               WHERE target_idx='". $_POST['target_idx'] ."'
         ");
      }

      if(isset($_POST['used']) && $_POST['used']) {
         $db->db_query("
            DELETE FROM ". MYSQL_PREFIX ."assign_target_groups
            WHERE
               atg_group_idx='". $_POST['target_idx'] ."'
         ");
         foreach($_POST['used'] as $use) {
            if($use != "") {
               $db->db_query("
                  INSERT INTO ". MYSQL_PREFIX ."assign_target_groups
                     (atg_group_idx, atg_target_idx) 
                  VALUES (
                     '". $_POST['target_idx'] ."',
                     '". $use ."'
                  )
               ");
            }
         }
      }
      return "ok";

   } // store()

   /**
    * display all targets
    */
   public function showList()
   {
      global $db, $tmpl;

      if(!isset($this->parent->screen))
         $this->parent->screen = 0;

      $this->avail_targets = Array();
      $this->targets = Array(); 

      $res_targets = $db->db_query("
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

      $tmpl->register_block("target_list", array(&$this, "smarty_target_list"));
      return $tmpl->fetch("targets_list.tpl");

   } // showList()

   /**
    * display interface to create or edit targets
    */
   public function showEdit()
   {
      if($this->is_storing())
         $this->store();

      global $db, $tmpl, $page;
      
      if($page->id != 0) {
         $target = $db->db_fetchSingleRow("
            SELECT *
            FROM ". MYSQL_PREFIX ."targets
            WHERE
               target_idx='". $page->id ."'
         ");

         $tmpl->assign('target_idx', $page->id);
         $tmpl->assign('target_name', $target->target_name);
         $tmpl->assign('target_match', $target->target_match);
         $tmpl->assign('target_ip', $target->target_ip);
         $tmpl->assign('target_mac', $target->target_mac);
      }
      else {
         $tmpl->assign('target_match', 'IP');
      }

      $tmpl->register_function("target_select_list", array(&$this, "smarty_target_select_list"), false);
      return $tmpl->fetch("targets_edit.tpl");

   } // showEdit()

   /**
    * template function which will be called from the target listing template
    */
   public function smarty_target_list($params, $content, &$smarty, &$repeat) 
   {
      global $tmpl;

      $index = $smarty->get_template_vars('smarty.IB.target_list.index');
      if(!$index) {
         $index = 0;
      }

      if($index < count($this->avail_targets)) {

        $target_idx = $this->avail_targets[$index];
        $target =  $this->targets[$target_idx];
         
         $tmpl->assign('target_idx', $target_idx);
         $tmpl->assign('target_name', $target->target_name);
         switch($target->target_match) {
            case 'IP':    $tmpl->assign('target_type', _("IP match")); break;
            case 'MAC':   $tmpl->assign('target_type', _("MAC match")); break;
            case 'GROUP': $tmpl->assign('target_type', _("Target Group")); break;
         }

         $index++;
         $tmpl->assign('smarty.IB.target_list.index', $index);
         $repeat = true;
      }
      else {
         $repeat =  false;
      }  

      return $content;

   } // smarty_target_list()

   /**
    * return select-list of available or used targets assigned to a target-group
    */
   public function smarty_target_select_list($params, &$smarty)
   {
      if(!array_key_exists('group', $params)) {
         $tmpl->trigger_error("getSLList: missing 'group' parameter", E_USER_WARNING);
         $repeat = false;
         return;
      }

      global $db;

      /* either "used" or "unused" */
      $group = $params['group'];

      if(isset($params['idx']) && is_numeric($params['idx']))
         $idx = $params['idx'];

      switch($group) {

         case 'unused':
            $result = $db->db_query("
               SELECT t.target_idx, t.target_name
               FROM ". MYSQL_PREFIX ."targets t
               LEFT JOIN ". MYSQL_PREFIX ."assign_target_groups atg
                  ON t.target_idx=atg.atg_target_idx
               WHERE
                  atg.atg_group_idx <> '". $idx ."'
               OR
                  ISNULL(atg.atg_group_idx)
               ORDER BY t.target_name ASC
            ");
            break;
         case 'used':
            $result = $db->db_query("
               SELECT t.target_idx, t.target_name
               FROM ". MYSQL_PREFIX ."assign_target_groups atg
               LEFT JOIN ". MYSQL_PREFIX ."targets t
                  ON t.target_idx = atg.atg_target_idx
               WHERE
                  atg_group_idx = '". $idx ."'
               ORDER BY t.target_name ASC
            ");
            break;
      }

      while($row = $result->fetchRow()) {
         $string.= "<option value=\"". $row->target_idx ."\">". $row->target_name ."</option>";
      }

      return $string;

   } // smarty_target_select_list()

} // class Page_Targets

$obj = new Page_Targets();
$obj->handler();

?>
