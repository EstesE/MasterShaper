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
         SELECT
            target_idx,
            target_name,
            target_match
         FROM
            ". MYSQL_PREFIX ."targets
         ORDER BY
            target_name ASC
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

      if($page->id != 0)
         $target = new Target($page->id);
      else
         $target = new Target;

      /* get a list of objects that use this target */
      $sth = $db->db_prepare("
         (
            SELECT
               'group' as type,
               t.target_idx as idx,
               t.target_name as name
            FROM
               ". MYSQL_PREFIX ."targets t
            INNER JOIN ". MYSQL_PREFIX ."assign_target_groups atg
               ON t.target_idx=atg.atg_group_idx
            WHERE
               atg.atg_target_idx LIKE ?
            ORDER BY
               t.target_name
         )
         UNION
         (
            SELECT
               'chain' as type,
               c.chain_idx as idx,
               c.chain_name as name
            FROM
               ". MYSQL_PREFIX ."chains c
            WHERE
               c.chain_src_target LIKE ?
            OR
               c.chain_dst_target LIKE ?
            ORDER BY
               c.chain_name
         )
         UNION
         (
            SELECT
               'pipe' as type,
               p.pipe_idx as idx,
               p.pipe_name as name
            FROM
               ". MYSQL_PREFIX ."pipes p
            WHERE
               p.pipe_src_target LIKE ?
            OR
               p.pipe_dst_target LIKE ?
            ORDER BY
               p.pipe_name
         )
      ");

      $assigned_obj = $db->db_execute($sth, array(
         $page->id,
         $page->id,
         $page->id,
         $page->id,
         $page->id,
      ));

      if($assigned_obj->numRows() > 0) {
         $obj_use_target = array();
         while($obj = $assigned_obj->fetchRow()) {
            array_push($obj_use_target, $obj);
         }
         $tmpl->assign('obj_use_target', $obj_use_target);
      }

      $tmpl->assign('target', $target);

      $tmpl->register_function("target_select_list", array(&$this, "smarty_target_select_list"), false);
      return $tmpl->fetch("targets_edit.tpl");

   } // showEdit()

   /**
    * handle updates
    */
   public function store()
   {
      global $ms, $db;

      isset($_POST['new']) && $_POST['new'] == 1 ? $new = 1 : $new = NULL;

      /* load target */
      if(isset($new))
         $target = new Target;
      else
         $target = new Target($_POST['target_idx']);

      if(!isset($_POST['target_name']) || $_POST['target_name'] == "") {
         $ms->throwError(_("Please enter a name for this target!"));
      }
      if(isset($new) && $ms->check_object_exists('target', $_POST['target_name'])) { 
         $ms->throwError(_("A target with that name already exists!"));
      }
      if(!isset($new) && $target->target_name != $_POST['target_name']
         && $ms->check_object_exists('target', $_POST['target_name'] )) {
         $ms->throwError(_("A target with that name already exists!"));
      }
      if($_POST['target_match'] == "IP" && $_POST['target_ip'] == "") {
         $ms->throwError(_("You have selected IP match but didn't entered a IP address!"));
      }
      elseif($_POST['target_match'] == "IP" && $_POST['target_ip'] != "") {
         /* Is target_ip a ip range seperated by "-" */
         if(strstr($_POST['target_ip'], "-") !== false) {
            $hosts = split("-", $_POST['target_ip']);
            foreach($hosts as $host) {
               $ipv4 = new Net_IPv4;
               if(!$ipv4->validateIP($host)) {
                  $ms->throwError(_("Incorrect IP address in IP range definition! Please enter a valid IP address!"));
               }
            }
         }
         /* Is target_ip a network */
         elseif(strstr($_POST['target_ip'], "/") !== false) {
            $ipv4 = new Net_IPv4;
            $net = $ipv4->parseAddress($_POST['target_ip']);
            if($net->netmask == "" || $net->netmask == "0.0.0.0") {
               $ms->throwError(_("Incorrect CIDR address! Please enter a valid network address!"));
            }
         }
         /* target_ip is a simple IP */
         else {
            $ipv4 = new Net_IPv4;
            if(!$ipv4->validateIP($_POST['target_ip'])) {
               $ms->throwError(_("Incorrect IP address! Please enter a valid IP address!"));
            }
         }
      }
      /* MAC address specified? */
      if($_POST['target_match'] == "MAC" && $_POST['target_mac'] == "") {
         $ms->throwError(_("You have selected MAC match but didn't entered a MAC address!"));
      }
      elseif($_POST['target_match'] == "MAC" && $_POST['target_mac'] != "") {
         if(!preg_match("/(.*):(.*):(.*):(.*):(.*):(.*)/", $_POST['target_mac'])
            && !preg_match("/(.*)-(.*)-(.*)-(.*)-(.*)-(.*)/", $_POST['target_mac'])) {
            $ms->throwError(_("You have selected MAC match but specified an INVALID MAC address! Please specify a correct MAC address!"));
         }
      }
      if($_POST['target_match'] == "GROUP" && isset($_POST['used']) && count($_POST['used']) < 1) {
         $ms->throwError(_("You have selected Group match but didn't selected at least one target from the list!"));
      }

      $target_data = $ms->filter_form_data($_POST, 'target_');

      if(!$target->update($target_data))
         return false;

      if(!$target->save())
         return false;

      return true;

   } // store()

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
            $sth = $db->db_prepare("
               SELECT
                  t.target_idx,
                  t.target_name
               FROM
                  ". MYSQL_PREFIX ."targets t
               LEFT JOIN
                  ". MYSQL_PREFIX ."assign_target_groups atg
               ON
                  t.target_idx=atg.atg_target_idx
               WHERE
                  atg.atg_group_idx NOT LIKE ?
               OR
                  ISNULL(atg.atg_group_idx)
               ORDER BY
                  t.target_name ASC
            ");
            $result = $db->db_execute($sth, array(
               $idx
            ));
            break;

         case 'used':

            $sth = $db->db_prepare("
               SELECT
                  t.target_idx,
                  t.target_name
               FROM
                  ". MYSQL_PREFIX ."assign_target_groups atg
               LEFT JOIN
                  ". MYSQL_PREFIX ."targets t
               ON
                  t.target_idx = atg.atg_target_idx
               WHERE
                  atg_group_idx LIKE ?
               ORDER BY
                  t.target_name ASC
            ");
            $result = $db->db_execute($sth, array(
               $idx
            ));
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
