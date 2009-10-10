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

class MASTERSHAPER_CHAINS {

   private $db;
   private $parent;
   private $tmpl;

   /**
    * MASTERSHAPER_CHAINS constructor
    *
    * Initialize the MASTERSHAPER_CHAINS class
    */
   public function __construct(&$parent)
   {
      $this->parent = $parent;
      $this->db = $parent->db;
      $this->tmpl = $parent->tmpl;

   } // __construct()

   /* interface output */
   public function show()
   {
      /* If authentication is enabled, check permissions */
      if($this->parent->getOption("authentication") == "Y" && 
         !$this->parent->checkPermissions("user_manage_chains")) {

         $this->parent->printError("<img src=\"". ICON_CHAINS ."\" alt=\"chain icon\" />&nbsp;". _("Manage Chains"), _("You do not have enough permissions to access this module!"));
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
    * display all chains
    */
   private function showList()
   {
      $this->avail_chains = Array();
      $this->chains = Array();

      $res_chains = $this->db->db_query("
         SELECT c.*, sl.sl_name as chain_sl_name,
            slfall.sl_name as chain_fallback_name
         FROM ". MYSQL_PREFIX ."chains c
         LEFT JOIN ". MYSQL_PREFIX ."service_levels sl
            ON
               c.chain_sl_idx=sl.sl_idx
         LEFT JOIN ". MYSQL_PREFIX ."service_levels slfall
            ON
               c.chain_fallback_idx=slfall.sl_idx
         ORDER BY
            c.chain_name ASC
      ");

      $cnt_chains = 0;

      while($chain = $res_chains->fetchRow()) {
         $this->avail_chains[$cnt_chains] = $chain->chain_idx;
         $this->chains[$chain->chain_idx] = $chain;
         $cnt_chains++;
      }

      $this->tmpl->register_block("chain_list", array(&$this, "smarty_chain_list"));
      $this->tmpl->show("chains_list.tpl");

   } // showList()

   /**
    * chains for handling
    */
   private function showEdit($idx)
   {
      if($idx != 0) {
         $chain = $this->db->db_fetchSingleRow("
            SELECT *
            FROM ". MYSQL_PREFIX ."chains
            WHERE
               chain_idx='". $idx ."'
         ");

         $this->tmpl->assign('chain_idx', $idx);
         $this->tmpl->assign('chain_name', $chain->chain_name);
         $this->tmpl->assign('chain_active', $chain->chain_active);
         $this->tmpl->assign('chain_direction', $chain->chain_direction);
         $this->tmpl->assign('chain_sl_idx', $chain->chain_sl_idx);
         $this->tmpl->assign('chain_fallback_idx', $chain->chain_fallback_idx);
         $this->tmpl->assign('chain_src_target', $chain->chain_src_target);
         $this->tmpl->assign('chain_dst_target', $chain->chain_dst_target);
         $this->tmpl->assign('chain_netpath_idx', $chain->chain_netpath_idx);
     }
      else {
         $this->tmpl->assign('chain_active', 'Y');
         $this->tmpl->assign('chain_fallback_idx', -1);
         $this->tmpl->assign('chain_direction', 2);
      }

      $this->tmpl->register_function("unused_pipes_select_list", array(&$this, "smarty_unused_pipes_select_list"), false);
      $this->tmpl->register_function("used_pipes_select_list", array(&$this, "smarty_used_pipes_select_list"), false);

      $this->tmpl->show("chains_edit.tpl");

   } // showEdit() 

   /**
    * template function which will be called from the chain listing template
    */
   public function smarty_chain_list($params, $content, &$smarty, &$repeat)
   {
      $index = $this->tmpl->get_template_vars('smarty.IB.chain_list.index');
      if(!$index) {
         $index = 0;
      }

      if($index < count($this->avail_chains)) {

         $chain_idx = $this->avail_chains[$index];
         $chain =  $this->chains[$chain_idx];

         $this->tmpl->assign('chain_idx', $chain_idx);
         $this->tmpl->assign('chain_name', $chain->chain_name);
         $this->tmpl->assign('chain_active', $chain->chain_active);
         $this->tmpl->assign('chain_name', $chain->chain_name);
         $this->tmpl->assign('chain_sl_idx', $chain->chain_sl_idx);
         $this->tmpl->assign('chain_fallback_idx', $chain->chain_fallback_idx);

         if($chain->chain_sl_idx != 0) {
            $this->tmpl->assign('chain_sl_name', $chain->chain_sl_name);
            if($chain->chain_fallback_idx != 0)
               $this->tmpl->assign('chain_fallback_name', $chain->chain_fallback_name);
            else
               $this->tmpl->assign('chain_fallback_name', _("No Fallback"));
         }
         else {
               $this->tmpl->assign('chain_sl_name', _("Ignore QoS"));
               $this->tmpl->assign('chain_fallback_name', _("Ignore QoS"));
         }

         $index++;
         $this->tmpl->assign('smarty.IB.chain_list.index', $index);
         $repeat = true;
      }
      else {
         $repeat =  false;
      }

      return $content;

   } // smarty_chain_list()

   /**
    * handle updates
    */
   public function store()
   {
      isset($_POST['chain_new']) && $_POST['chain_new'] == 1 ? $new = 1 : $new = NULL;

      if(!isset($_POST['chain_name']) || $_POST['chain_name'] == "") {
         return _("Please enter a chain name!");
      }
      if(isset($new) && $this->checkChainExists($_POST['chain_name'])) {
         return _("A chain with such a name already exists!");
      }
      if(!isset($new) && $_POST['chain_name'] != $_POST['namebefore'] && 
         $this->checkChainExists($_POST['chain_name'])) {
         return _("A chain with such a name already exists!");
      }

      if(isset($new)) {
						
         $max_pos = $this->db->db_fetchSingleRow("
            SELECT
               MAX(chain_position) as pos
            FROM
               ". MYSQL_PREFIX ."chains
            WHERE
               chain_netpath_idx='". $_POST['chain_netpath_idx'] ."'
         ");

         $this->db->db_query("
            INSERT INTO ". MYSQL_PREFIX ."chains (
               chain_name, chain_sl_idx, chain_src_target, chain_dst_target, 
               chain_position, chain_direction, chain_netpath_idx,
               chain_active, chain_fallback_idx
            ) VALUES (
               '". $_POST['chain_name'] ."',
               '". $_POST['chain_sl_idx'] ."',
               '". $_POST['chain_src_target'] ."',
               '". $_POST['chain_dst_target'] ."',
               '". ($max_pos->pos+1) ."',
               '". $_POST['chain_direction'] ."',
               '". $_POST['chain_netpath_idx'] ."',
               '". $_POST['chain_active'] ."',
               '". $_POST['chain_fallback_idx'] ."'
            )
         ");

         $_POST['chain_idx'] = $this->db->db_getid();

      }
      else {

         $this->db->db_query("
            UPDATE
               ". MYSQL_PREFIX ."chains
            SET
               chain_name='". $_POST['chain_name'] ."',
               chain_sl_idx='". $_POST['chain_sl_idx'] ."',
               chain_src_target='". $_POST['chain_src_target'] ."',
               chain_dst_target='". $_POST['chain_dst_target'] ."',
               chain_direction='". $_POST['chain_direction'] ."',
               chain_netpath_idx='". $_POST['chain_netpath_idx'] ."',
               chain_active='". $_POST['chain_active'] ."',
               chain_fallback_idx='". $_POST['chain_fallback_idx'] ."'
            WHERE
               chain_idx='". $_POST['chain_idx'] ."'");
      }

      if(isset($_POST['used']) && $_POST['used']) {
         $this->db->db_query("
            DELETE FROM
               ". MYSQL_PREFIX ."assign_pipes_to_chains
            WHERE
               apc_chain_idx='". $_POST['chain_idx'] ."'
         ");

         foreach($_POST['used'] as $use) {
            if($use != "") {
               $this->db->db_query("
                  INSERT INTO ". MYSQL_PREFIX ."assign_pipes_to_chains (
                     apc_pipe_idx, apc_chain_idx
                  ) VALUES (
                     '". $use ."',
                     '". $_POST['chain_idx'] ."'
                  )
               ");
            }
         }
      }

      return "ok";

   } // store()

   /**
    * delete chain
    */
   public function delete()
   {
      if(isset($_POST['idx']) && is_numeric($_POST['idx'])) {
         $idx = $_POST['idx'];

         $this->db->db_query("
            DELETE FROM ". MYSQL_PREFIX ."chains
            WHERE
               chain_idx='". $idx ."'
         ");
         $this->db->db_query("
            DELETE FROM
               ". MYSQL_PREFIX ."assign_pipes_to_chains
            WHERE
               apc_chain_idx='". $idx ."'
         ");
         return "ok";

      }

      return "unkown error";

   } // delete()

   /**
    * toggle chain status
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
            UPDATE ". MYSQL_PREFIX ."chains
            SET
               chain_active='". $new_status ."'
            WHERE
               chain_idx='". $idx ."'
         ");

         return "ok";
      }

      return "unkown error";

   } // toggleStatus()

   /**
    * return true if the provided chain name with the specified
    * name already exists
    */
   private function checkChainExists($chain_name)
   {

      if($this->db->db_fetchSingleRow("
         SELECT chain_idx
         FROM ". MYSQL_PREFIX ."chains
         WHERE
            chain_name LIKE BINARY '". $_POST['chain_name'] ."'
         ")) {
         return true;
      }

      return false;

   } // checkChainExists()

   public function smarty_unused_pipes_select_list($params, &$smarty)
   {
      if(!array_key_exists('chain_idx', $params)) {
         $this->tmpl->trigger_error("smarty_unused_pipes_select_list: missing 'chain_idx' parameter", E_USER_WARNING);
         $repeat = false;
         return;
      }

      if(!isset($params['chain_idx'])) {
         $unused_pipes = $this->db->db_query("
            SELECT
               pipe_idx,
               pipe_name
            FROM
               ". MYSQL_PREFIX ."pipes
            ORDER BY
               pipe_name ASC
         ");
      }
      else {
         $unused_pipes = $this->db->db_query("
            SELECT DISTINCT
               p.pipe_idx,
               p.pipe_name
            FROM
               ". MYSQL_PREFIX ."pipes p
            LEFT OUTER JOIN (
               SELECT DISTINCT
                  apc_pipe_idx, apc_chain_idx
               FROM
                  ". MYSQL_PREFIX ."assign_pipes_to_chains
               WHERE
                  apc_chain_idx=". $this->db->db_quote($params['chain_idx']) ."
            ) apc
            ON
               apc.apc_pipe_idx=p.pipe_idx
            WHERE
               apc.apc_chain_idx IS NULL
         ");
      }

      while($pipe = $unused_pipes->fetchrow()) {
         $string.= "<option value=\"". $pipe->pipe_idx ."\">". $pipe->pipe_name ."</option>\n";
      }

      return $string;

   } // smarty_unused_pipes_select_list()

   public function smarty_used_pipes_select_list($params, &$smarty)
   {
      if(!array_key_exists('chain_idx', $params)) {
         $this->tmpl->trigger_error("smarty_used_pipes_select_list: missing 'chain_idx' parameter", E_USER_WARNING);
         $repeat = false;
         return;
      }

      $used_pipes = $this->db->db_query("
         SELECT DISTINCT
            p.pipe_idx, p.pipe_name
         FROM
            ". MYSQL_PREFIX ."pipes p
         INNER JOIN (
            SELECT
               apc_pipe_idx
            FROM
               ". MYSQL_PREFIX ."assign_pipes_to_chains
            WHERE
               apc_chain_idx='". $params['chain_idx'] ."'
         ) apc
         ON
            apc.apc_pipe_idx=p.pipe_idx
         ");

      while($pipe = $used_pipes->fetchrow()) {
         $string.= "<option value=\"". $pipe->pipe_idx ."\">". $pipe->pipe_name ."</option>\n";
      }

      return $string;

   } // smarty_used_pipes_select_list()

}

?>
