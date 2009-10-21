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

class Page_Chains extends MASTERSHAPER_PAGE {

   /**
    * Page_Chains constructor
    *
    * Initialize the Page_Chains class
    */
   public function __construct()
   {
      $this->rights = 'user_manage_chains';

   } // __construct()

   /**
    * display all chains
    */
   public function showList()
   {
      global $db, $tmpl;

      $this->avail_chains = Array();
      $this->chains = Array();

      $res_chains = $db->db_query("
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

      $tmpl->register_block("chain_list", array(&$this, "smarty_chain_list"));

      return $tmpl->fetch("chains_list.tpl");

   } // showList()

   /**
    * chains for handling
    */
   public function showEdit()
   {
      if($this->is_storing())
         $this->store();

      global $db, $tmpl, $page;

      if($page->id != 0) {
         $chain = new Chain($page->id);
      }
      else {
         $chain = new Chain;
      }
      $tmpl->assign('chain', $chain);

      $tmpl->register_function("unused_pipes_select_list", array(&$this, "smarty_unused_pipes_select_list"), false);
      $tmpl->register_function("used_pipes_select_list", array(&$this, "smarty_used_pipes_select_list"), false);

      return $tmpl->fetch("chains_edit.tpl");

   } // showEdit() 

   /**
    * template function which will be called from the chain listing template
    */
   public function smarty_chain_list($params, $content, &$smarty, &$repeat)
   {
      global $tmpl;

      $index = $tmpl->get_template_vars('smarty.IB.chain_list.index');
      if(!$index) {
         $index = 0;
      }

      if($index < count($this->avail_chains)) {

         $chain_idx = $this->avail_chains[$index];
         $chain =  $this->chains[$chain_idx];

         $tmpl->assign('chain_idx', $chain_idx);
         $tmpl->assign('chain_name', $chain->chain_name);
         $tmpl->assign('chain_active', $chain->chain_active);
         $tmpl->assign('chain_name', $chain->chain_name);
         $tmpl->assign('chain_sl_idx', $chain->chain_sl_idx);
         $tmpl->assign('chain_fallback_idx', $chain->chain_fallback_idx);

         if($chain->chain_sl_idx != 0) {
            $tmpl->assign('chain_sl_name', $chain->chain_sl_name);
            if($chain->chain_fallback_idx != 0)
               $tmpl->assign('chain_fallback_name', $chain->chain_fallback_name);
            else
               $tmpl->assign('chain_fallback_name', _("No Fallback"));
         }
         else {
               $tmpl->assign('chain_sl_name', _("Ignore QoS"));
               $tmpl->assign('chain_fallback_name', _("Ignore QoS"));
         }

         $index++;
         $tmpl->assign('smarty.IB.chain_list.index', $index);
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
      global $ms, $db;

      isset($_POST['new']) && $_POST['new'] == 1 ? $new = 1 : $new = NULL;

      /* load chain */
      if(isset($new))
         $chain = new Chain;
      else
         $chain = new Chain($_POST['chain_idx']);

      if(!isset($new) && (!isset($_POST['chain_idx']) || !is_numeric($_POST['chain_idx'])))
         $ms->throwError(_("Missing id of chain to be handled!"));

      if(!isset($_POST['chain_name']) || empty($_POST['chain_name']))
         $ms->throwError(_("Please enter a chain name!"));

      if(isset($new) && $ms->check_object_exists('chain', $_POST['chain_name']))
         $ms->throwError(_("A chain with such a name already exists!"));

      if(!isset($new) && $_POST['chain_name'] != $chain->chain_name &&
         $ms->check_object_exists('chain', $_POST['chain_name']))
         $ms->throwError(_("A chain with such a name already exists!"));

      $chain_data = $ms->filter_form_data($_POST, 'chain_'); 

      if(!$chain->update($chain_data))
         return false;

      if(!$chain->save())
         return false;

      return true;

   } // store()

   public function smarty_unused_pipes_select_list($params, &$smarty)
   {
      global $db;

      if(!array_key_exists('chain_idx', $params)) {
         $smarty->trigger_error("smarty_unused_pipes_select_list: missing 'chain_idx' parameter", E_USER_WARNING);
         $repeat = false;
         return;
      }

      if(!isset($params['chain_idx'])) {
         $unused_pipes = $db->db_query("
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
         $unused_pipes = $db->db_query("
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
                  apc_chain_idx=". $db->db_quote($params['chain_idx']) ."
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
      global $db;

      if(!array_key_exists('chain_idx', $params)) {
         $smarty->trigger_error("smarty_used_pipes_select_list: missing 'chain_idx' parameter", E_USER_WARNING);
         $repeat = false;
         return;
      }

      $used_pipes = $db->db_query("
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

$obj = new Page_Chains;
$obj->handler();

?>
