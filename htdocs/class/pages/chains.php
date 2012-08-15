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
      global $ms, $db, $tmpl;

      $this->avail_chains = Array();
      $this->chains = Array();

      $res_chains = $db->db_query("
         SELECT
            c.*,
            sl.sl_name as chain_sl_name,
            slfall.sl_name as chain_fallback_name
         FROM
            ". MYSQL_PREFIX ."chains c
         LEFT JOIN
            ". MYSQL_PREFIX ."service_levels sl
         ON
            c.chain_sl_idx=sl.sl_idx
         LEFT JOIN
            ". MYSQL_PREFIX ."service_levels slfall
         ON
            c.chain_fallback_idx=slfall.sl_idx
         WHERE
            c.chain_host_idx LIKE '". $ms->get_current_host_profile() ."'
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

      $this->avail_pipes = Array();
      $this->pipes = Array();

      if($page->id != 0) {
         $chain = new Chain($page->id);
         $tmpl->assign('is_new', false);
      }
      else {
         $chain = new Chain;
         $tmpl->assign('is_new', true);
      }

      $sth = $db->db_prepare("
         SELECT DISTINCT
            p.pipe_idx,
            p.pipe_name,
            p.pipe_sl_idx,
            apc.apc_pipe_idx,
            apc.apc_sl_idx,
            apc.apc_pipe_active
         FROM
            ". MYSQL_PREFIX ."pipes p
         LEFT JOIN (
            SELECT
               apc_pipe_idx,
               apc_sl_idx,
               apc_pipe_active,
               apc_pipe_pos,
               /* just a trick to get the correct order of result */
               apc_pipe_pos IS NULL as pos_null
            FROM
               ". MYSQL_PREFIX ."assign_pipes_to_chains
            WHERE
               apc_chain_idx LIKE ?
         ) apc
         ON
            apc.apc_pipe_idx=p.pipe_idx
         ORDER BY
            pos_null DESC,
            apc_pipe_pos ASC
      ");

      $pipes = $db->db_execute($sth, array(
         $page->id
      ));

      $db->db_sth_free($sth);
      $cnt_pipes = 0;

      while($pipe = $pipes->fetchRow()) {
         $this->avail_pipes[$cnt_pipes] = $pipe->pipe_idx;
         $this->pipes[$pipe->pipe_idx] = $pipe;
         $cnt_pipes++;
      }

      $tmpl->assign('chain', $chain);

      $tmpl->register_block("pipe_list", array(&$this, "smarty_pipe_list"), false);

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
      global $ms, $db, $rewriter;

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

      // if chain gets moved to another network path, reset chain_position
      if(!isset($new) && $_POST['chain_netpath_idx'] != $chain->chain_netpath_idx) {
         $np = new Network_path($_POST['chain_netpath_idx']);
         $_POST['chain_position'] = $np->get_next_chain_position();
      }

      $chain_data = $ms->filter_form_data($_POST, 'chain_'); 

      if(!$chain->update($chain_data))
         return false;

      if(!$chain->save())
         return false;

      if(isset($_POST['add_another']) && $_POST['add_another'] == 'Y')
         return true;

      $ms->set_header('Location', $rewriter->get_page_url('Chains List'));
      return true;

   } // store()

   /**
    * template function which will be called from the chain editing template
    */
   public function smarty_pipe_list($params, $content, &$smarty, &$repeat)
   {
      global $tmpl;

      $index = $tmpl->get_template_vars('smarty.IB.pipe_list.index');
      if(!$index) {
         $index = 0;
      }

      if($index < count($this->avail_pipes)) {

         $pipe_idx = $this->avail_pipes[$index];
         $pipe =  $this->pipes[$pipe_idx];

         // check if pipes original service level got overruled
         if(isset($pipe->apc_sl_idx) && !empty($pipe->apc_sl_idx))
            $pipe->sl_in_use = $pipe->apc_sl_idx;
         else
            // no override
            $pipe->sl_in_use = -1;

         $tmpl->assign('pipe', $pipe);

         $index++;
         $tmpl->assign('smarty.IB.pipe_list.index', $index);
         $repeat = true;
      }
      else {
         $repeat =  false;
      }

      return $content;

   } // smarty_pipe_list()

   /**
    * return a list of chains used for the assign-pipe-to-chains feature
    *
    * @return object
    */
   public function get_chains_list()
   {
      global $ms, $db, $tmpl;

      $this->avail_chains = Array();
      $this->chains = Array();

      $id = $_POST['idx'];

      if(preg_match('/(.*)-([0-9]+)/', $id, $parts) === false) {
         print "id in incorrect format!";
         return false;
      }

      $request_object = $parts[1];
      $id = $parts[2];

      if($request_object != "pipe") {
         $ms->throwError("Unknown ID provided in get_chains_list()");
      }

      $sth = $db->db_prepare("
         SELECT
            c.chain_idx,
            c.chain_name,
            c.chain_active,
            apc.apc_chain_idx
         FROM
            ". MYSQL_PREFIX ."chains c
         LEFT OUTER JOIN
            ". MYSQL_PREFIX ."assign_pipes_to_chains apc
         ON (
               c.chain_idx=apc.apc_chain_idx
            AND
               apc.apc_pipe_idx LIKE ?
         )
         AND
            c.chain_host_idx LIKE ?
         ORDER BY
            c.chain_name ASC
      ");

      $res_chains = $db->db_execute($sth, array(
         $id,
         $ms->get_current_host_profile(),
      ));

      $db->db_sth_free($sth);
      $cnt_chains = 0;

      while($chain = $res_chains->fetchRow()) {
         $this->avail_chains[$cnt_chains] = $chain->chain_idx;
         $this->chains[$chain->chain_idx] = $chain;
         $cnt_chains++;
      }

      $tmpl->register_block("chain_dialog_list", array(&$this, "smarty_chain_dialog_list"));
      $tmpl->assign('pipe_idx', $id);

      $json_obj = Array(
         'content' =>  $tmpl->fetch("chains_dialog_list.tpl"),
      );

      return json_encode($json_obj);

   } // get_chains_list()

   /**
    * template function which will be called from the chain dialog listing template
    */
   public function smarty_chain_dialog_list($params, $content, &$smarty, &$repeat)
   {
      global $tmpl;

      $index = $tmpl->get_template_vars('smarty.IB.chain_dialog_list.index');
      if(!$index) {
         $index = 0;
      }

      if($index < count($this->avail_chains)) {

         $chain_idx = $this->avail_chains[$index];
         $chain =  $this->chains[$chain_idx];

         $tmpl->assign('chain_idx', $chain_idx);
         $tmpl->assign('chain_name', $chain->chain_name);
         $tmpl->assign('chain_active', $chain->chain_active);
         if(isset($chain->apc_chain_idx) && !is_null($chain->apc_chain_idx))
            $tmpl->assign('chain_used', $chain->apc_chain_idx);
         else
            $tmpl->clear_assign('chain_used');

         $index++;
         $tmpl->assign('smarty.IB.chain_dialog_list.index', $index);
         $repeat = true;
      }
      else {
         $repeat =  false;
      }

      return $content;

   } // smarty_chain_dialog_list()

} // Page_Chains

$obj = new Page_Chains;
$obj->handler();

?>
