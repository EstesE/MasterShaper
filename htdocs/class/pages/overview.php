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

class Page_Overview extends MASTERSHAPER_PAGE {

   private $db;
   private $parent;
   private $tmpl;

   private $sth_chains;
   private $sth_pipes;
   private $sth_filters;

   /**
    * Page_Overview constructor
    *
    * Initialize the Page_Overview class
    */
   public function __construct()
   {

   } // __construct()

   /* interface output */
   public function showList()
   {
      global $ms, $db, $tmpl;

      if($this->is_storing())
         $this->store();

      /* If authentication is enabled, check permissions */
      if($ms->getOption("authentication") == "Y" &&
         !$ms->checkPermissions("user_show_rules")) {

         $ms->throwError("You do not have enough permissions to access this module!");
         return 0;
      }

      $this->cnt_network_paths = 0;
      $this->cnt_chains = 0;
      $this->cnt_pipes = 0;
      $this->cnt_filters = 0;
      $this->avail_network_paths = Array();
      $this->avail_chains = Array();
      $this->avail_pipes = Array();
      $this->avail_filters = Array(); 
      $this->network_paths = Array();
      $this->chains = Array();
      $this->pipes = Array();
      $this->filters = Array();
      
      /* get a list of network paths */
      $res_network_paths = $db->db_query("
         SELECT
            *
         FROM
            ". MYSQL_PREFIX ."network_paths
         WHERE
            netpath_active LIKE 'Y'
         AND
            netpath_host_idx LIKE '". $ms->get_current_host_profile() ."'
         ORDER BY
            netpath_position ASC
      ");

      while($network_path = $res_network_paths->fetch()) {
   
         $this->cnt_chains = 0;
         $this->avail_chains[$network_path->netpath_idx] = Array();
         $this->chains[$network_path->netpath_idx] = Array();
         $this->pipes[$network_path->netpath_idx] = Array();
         $this->filters[$network_path->netpath_idx] = Array();

         $this->avail_network_paths[$this->cnt_network_paths] = $network_path->netpath_idx;
         $this->network_paths[$network_path->netpath_idx] = $network_path;

         /* get a list of chains for the current netpath */
         $this->sth_chains = $db->db_prepare("
            SELECT
               *
            FROM
               ". MYSQL_PREFIX ."chains
            WHERE 
               chain_netpath_idx LIKE ?
            AND 
               chain_active LIKE 'Y'
            AND
               chain_host_idx LIKE ?
            ORDER BY
               chain_position ASC
         ");

         $db->db_execute($this->sth_chains, array(
            $network_path->netpath_idx,
            $ms->get_current_host_profile(),
         ));

         while($chain = $this->sth_chains->fetch()) {

            $this->avail_chains[$network_path->netpath_idx][$this->cnt_chains] = $chain->chain_idx;
            $this->chains[$network_path->netpath_idx][$chain->chain_idx] = $chain;

            $this->cnt_pipes = 0;
            $this->avail_pipes[$network_path->netpath_idx][$chain->chain_idx] = Array();
            $this->pipes[$network_path->netpath_idx][$chain->chain_idx] = Array();
            $this->filters[$network_path->netpath_idx][$chain->chain_idx] = Array();

            /* pipes are only available if the chain DOES NOT ignore QoS or DOES NOT use fallback service level */
            if($chain->chain_sl_idx == 0 || $chain->chain_fallback_idx == 0) {
               $this->cnt_chains++;
               continue;
            }
    
            $this->sth_pipes = $db->db_prepare("
               SELECT
                  *
               FROM
                  ". MYSQL_PREFIX ."pipes p
               INNER JOIN
                  ". MYSQL_PREFIX ."assign_pipes_to_chains apc
               ON
                  p.pipe_idx=apc.apc_pipe_idx
               WHERE
                  apc.apc_chain_idx LIKE ?
               AND
                  p.pipe_active='Y'
               ORDER BY
                  apc.apc_pipe_pos ASC
            ");

            $db->db_execute($this->sth_pipes, array(
               $chain->chain_idx
            ));

            while($pipe = $this->sth_pipes->fetch()) {

               $this->avail_pipes[$network_path->netpath_idx][$chain->chain_idx][$this->cnt_pipes] = $pipe->pipe_idx;
               $this->pipes[$network_path->netpath_idx][$chain->chain_idx][$pipe->pipe_idx] = $pipe;

               $this->cnt_filters = 0;
               $this->avail_filters[$network_path->netpath_idx][$chain->chain_idx][$pipe->pipe_idx] = Array();
               $this->filters[$network_path->netpath_idx][$chain->chain_idx][$pipe->pipe_idx] = Array();

               $this->sth_filters = $db->db_prepare("
                  SELECT
                     a.filter_idx as filter_idx,
                     a.filter_name as filter_name
                  FROM
                     ". MYSQL_PREFIX ."filters a,
                     ". MYSQL_PREFIX ."assign_filters_to_pipes b
                  WHERE
                     b.apf_pipe_idx LIKE ?
                  AND
                     b.apf_filter_idx=a.filter_idx
                  AND
                     a.filter_active='Y'
               ");

               $db->db_execute($this->sth_filters, array(
                  $pipe->pipe_idx
               ));

               while($filter = $this->sth_filters->fetch()) {

                  $this->avail_filters[$network_path->netpath_idx][$chain->chain_idx][$pipe->pipe_idx][$this->cnt_filters] = $filter->filter_idx;
                  $this->filters[$network_path->netpath_idx][$chain->chain_idx][$pipe->pipe_idx][$filter->filter_idx] = $filter;

                  $this->cnt_filters++;
               }

               $db->db_sth_free($this->sth_filters);
               $this->cnt_pipes++;
            }

            $db->db_sth_free($this->sth_pipes);
            $this->cnt_chains++;
         }

         $db->db_sth_free($this->sth_chains);
         $this->cnt_network_paths++;
      }

      if(isset($_GET['mode']) && $_GET['mode'] == 'edit')
         $tmpl->assign('edit_mode', true);

      $tmpl->assign('cnt_network_paths', $this->cnt_network_paths);
      $tmpl->registerPlugin("block", "ov_netpath", array(&$this, "smarty_ov_netpath"));
      $tmpl->registerPlugin("block", "ov_chain", array(&$this, "smarty_ov_chain"));
      $tmpl->registerPlugin("block", "ov_pipe", array(&$this, "smarty_ov_pipe"));
      $tmpl->registerPlugin("block", "ov_filter", array(&$this, "smarty_ov_filter"));

      return $tmpl->fetch("overview.tpl");

   } // showList()

   public function smarty_ov_netpath($params, $content, &$smarty, &$repeat)
   {
      $index = $smarty->getTemplateVars('smarty.IB.ov_netpath.index');
      if(!$index) {
         $index = 0;
      }

      if($index < count($this->avail_network_paths)) {

         $np_idx = $this->avail_network_paths[$index];
         $np =  $this->network_paths[$np_idx];
         $smarty->assign('netpath', $np);

         $index++;
         $smarty->assign('smarty.IB.ov_netpath.index', $index);
         $repeat = true;
      }
      else {
         $repeat = false;
      }
      return $content;

   } // smart_ov_netpath()

   public function smarty_ov_chain($params, $content, &$smarty, &$repeat)
   {
      global $ms;

      if(!array_key_exists('np_idx', $params)) {
         $ms->trigger_error("ov_netpath: missing 'np_idx' parameter", E_USER_WARNING);
         $repeat = false;
         return;
      }
      
      $np_idx = $params['np_idx'];

      $index = $smarty->getTemplateVars('smarty.IB.ov_chain.index-'. $np_idx);
      if(!$index) {
         $index = 0;
      }

      if($index < count($this->avail_chains[$np_idx])) {

         $chain_idx = $this->avail_chains[$np_idx][$index];
         $chain =  $this->chains[$np_idx][$chain_idx];

         $smarty->assign('chain', $chain);

         if($chain->chain_sl_idx != 0)
            $smarty->assign('chain_has_sl', true);
         else
            $smarty->assign('chain_has_sl', false);

         $index++;
         $smarty->assign('smarty.IB.ov_chain.index-'. $np_idx, $index);

         $repeat = true;
      }
      else {
         $repeat = false;
      }

      return $content;

   } // smart_ov_chain()

   public function smarty_ov_pipe($params, $content, &$smarty, &$repeat)
   {
      global $db, $ms;

      if(!array_key_exists('np_idx', $params)) {
         $ms->trigger_error("ov_netpath: missing 'np_idx' parameter", E_USER_WARNING);
         $repeat = false;
         return;
      }
      if(!array_key_exists('chain_idx', $params)) {
         $ms->trigger_error("ov_netpath: missing 'chain_idx' parameter", E_USER_WARNING);
         $repeat = false;
         return;
      }
      
      $np_idx = $params['np_idx'];
      $chain_idx = $params['chain_idx'];

      $index = $smarty->getTemplateVars('smarty.IB.ov_pipe.index-'. $np_idx ."-". $chain_idx);
      if(!$index) {
         $index = 0;
      }

      if($index < count($this->avail_pipes[$np_idx][$chain_idx])) {

         $pipe_idx = $this->avail_pipes[$np_idx][$chain_idx][$index];
         $pipe = $this->pipes[$np_idx][$chain_idx][$pipe_idx];

         // check if pipes service level got overriden
         $ovrd_sl = $db->db_fetchSingleRow("
            SELECT
               apc_sl_idx
            FROM
               ". MYSQL_PREFIX ."assign_pipes_to_chains
            WHERE
               apc_chain_idx LIKE '". $chain_idx ."'
            AND
               apc_pipe_idx LIKE '". $pipe_idx ."'
         ");

         if(isset($ovrd_sl->apc_sl_idx) && !empty($ovrd_sl->apc_sl_idx))
            $pipe->pipe_sl_idx = $ovrd_sl->apc_sl_idx;

         $smarty->assign('pipe', $pipe);
         $smarty->assign('pipe_sl_name', $ms->getServiceLevelName($pipe->pipe_sl_idx));
         $smarty->assign('apc_idx', $pipe->apc_idx);
         $smarty->assign('apc_sl_idx', $pipe->apc_sl_idx);
         $smarty->assign('counter', $index+1);

         $index++;
         $smarty->assign('smarty.IB.ov_pipe.index-'. $np_idx ."-". $chain_idx, $index);

         $repeat = true;
      }
      else {
         $repeat = false;
      }

      return $content;

   } // smart_ov_pipe()

   public function smarty_ov_filter($params, $content, &$smarty, &$repeat)
   {
      global $db;

      if(!array_key_exists('np_idx', $params)) {
         $ms->trigger_error("ov_netpath: missing 'np_idx' parameter", E_USER_WARNING);
         $repeat = false;
         return;
      }
      if(!array_key_exists('chain_idx', $params)) {
         $ms->trigger_error("ov_netpath: missing 'chain_idx' parameter", E_USER_WARNING);
         $repeat = false;
         return;
      }
      if(!array_key_exists('pipe_idx', $params)) {
         $ms->trigger_error("ov_netpath: missing 'pipe_idx' parameter", E_USER_WARNING);
         $repeat = false;
         return;
      }
      
      $np_idx = $params['np_idx'];
      $chain_idx = $params['chain_idx'];
      $pipe_idx = $params['pipe_idx'];

      $index = $smarty->getTemplateVars('smarty.IB.ov_filter.index-'. $np_idx ."-". $chain_idx ."-". $pipe_idx);
      if(!$index) {
         $index = 0;
      }

      if($index < count($this->avail_filters[$np_idx][$chain_idx][$pipe_idx])) {

         $filter_idx = $this->avail_filters[$np_idx][$chain_idx][$pipe_idx][$index];
         $filter = $this->filters[$np_idx][$chain_idx][$pipe_idx][$filter_idx];

         $smarty->assign('filter', $filter);

         $index++;
         $smarty->assign('smarty.IB.ov_filter.index-'. $np_idx ."-". $chain_idx ."-". $pipe_idx, $index);

         $repeat = true;
      }
      else {
         $repeat = false;
      }

      return $content;

   } // smart_ov_filter()

   /**
    * alter position
    *
    * gather objects current position
    * move all other objects away
    * set objects new position
    *
    * @return string
    */
   public function alter_position()
   {
      global $ms, $db;

      if(!isset($_POST['move_obj'])) {
         print "Missing object-type to alter position off";
         return false;
      }

      switch($_POST['move_obj']) {

         case 'chain':
            $obj_table = "chains";
            $obj_col = "chain";
            $obj_parent = 'chain_netpath_idx';
            break;

         case 'netpath':
            $obj_table = "network_paths";
            $obj_col = "netpath";
            break;

         case 'pipe':
            $obj_table = "pipes";
            $obj_col = "pipe";
            $obj_parent = 'pipe_chain_idx';
            break;

         default:
            return "Unknown object-type";
            break;

      }

      if(!isset($_POST['id']) || !is_numeric($_POST['id']))
         $ms->throwError(_("Id to alter position is missing or not numeric!"));

      if(!isset($_POST['to']) || !in_array($_POST['to'], array('up','down')))
         $ms->throwError(_("Don't know in which direction we shall alter position!"));

      $idx = $_POST['id'];

      // get objects current position
      switch($_POST['move_obj']) {
         case 'chain':
            $query = "
               SELECT
                  chain_position as position,
                  chain_netpath_idx as parent_idx,
                  (
                     /* get colums max position */
                     SELECT
                        MAX(chain_position)
                     FROM
                        ". MYSQL_PREFIX ."chains
                     WHERE
                        /* but only for our parents objects */
                        chain_netpath_idx = (
                        SELECT
                           chain_netpath_idx
                        FROM
                           ". MYSQL_PREFIX ."chains
                        WHERE
                           chain_idx LIKE '". $idx ."'
                        AND
                           chain_host_idx LIKE '". $ms->get_current_host_profile() ."'
                     )
                     AND
                        chain_host_idx LIKE '". $ms->get_current_host_profile() ."'
                  ) as max
               FROM
                  ". MYSQL_PREFIX ."chains
               WHERE
                  chain_idx='". $idx ."'
               AND
                  chain_host_idx LIKE '". $ms->get_current_host_profile() ."'
            ";
            break;
         case 'pipe':
            $query = "
               SELECT
                  apc.apc_idx as idx,
                  apc.apc_pipe_pos as position,
                  apc.apc_chain_idx parent_idx,
                  (
                     /* get colums max position */
                     SELECT
                        MAX(apc_pipe_pos)
                     FROM
                        ". MYSQL_PREFIX . "assign_pipes_to_chains
                     WHERE
                        apc_chain_idx LIKE (
                           SELECT
                              apc_chain_idx
                           FROM
                              ". MYSQL_PREFIX . "assign_pipes_to_chains
                           WHERE
                              apc_idx LIKE '". $idx ."'
                        )
                  ) as max
               FROM
                  ". MYSQL_PREFIX . "pipes p
               INNER JOIN
                  ". MYSQL_PREFIX . "assign_pipes_to_chains apc
               ON
                  p.pipe_idx=apc.apc_pipe_idx
               WHERE
                  apc.apc_idx='". $idx ."'
            ";
            break;
         case 'netpath':
            $query = "
               SELECT
                  netpath_position as position,
                  (
                     /* get colums max position */
                     SELECT
                        MAX(netpath_position)
                     FROM
                        ". MYSQL_PREFIX ."network_paths
                     WHERE
                        netpath_host_idx LIKE '". $ms->get_current_host_profile() ."'
                  ) as max
               FROM
                  ". MYSQL_PREFIX ."network_paths
               WHERE
                  netpath_idx='". $idx ."'
               AND
                  netpath_host_idx LIKE '". $ms->get_current_host_profile() ."'
            ";
            break;
      }

      if(!isset($query))
         return;

      $my_pos = $db->db_fetchSingleRow($query);

      if($_POST['to'] == 'up') {
         /* if we are not at the top most position */
         if($my_pos->position > 1)
            $new_pos = $my_pos->position - 1;
         else
            $new_pos = -1;
      }
      elseif($_POST['to'] == 'down') {
         /* if we are not at the bottom most position */
         if($my_pos->position < $my_pos->max)
            $new_pos = $my_pos->position + 1;
         else
            $new_pos = -2;
      }
      else
         /* we make no change */
         $new_pos = $my_pos->position;

      //return $new_pos ." ". $my_pos->position ." ". $my_pos->max;

      /* if no position will be changed, return */
      if($new_pos == $my_pos->position)
         return "ok";

      /* new position can not be below null */
      if($new_pos == 0)
         $new_pos = 1;

      /* moving if new position is greater than 0 */
      if($new_pos > 0) {

         /* swap position with current position holder */
         switch($_POST['move_obj']) {
            case 'chain':
               $sth = $db->db_prepare("
                  UPDATE
                     ". MYSQL_PREFIX ."chains
                  SET
                     chain_position=?
                  WHERE
                     chain_position LIKE ?
                  AND
                     chain_netpath_idx LIKE ?
                  AND
                     chain_host_idx LIKE ?
               ");

               $db->db_execute($sth, array(
                  $my_pos->position,
                  $new_pos,
                  $my_pos->parent_idx,
                  $ms->get_current_host_profile(),
               ));
               $db->db_sth_free($sth);
               break;

            case 'pipe':
               $sth = $db->db_prepare("
                  UPDATE
                     ". MYSQL_PREFIX ."assign_pipes_to_chains
                  SET
                     apc_pipe_pos=?
                  WHERE
                     apc_pipe_pos LIKE ?
                  AND
                     apc_chain_idx LIKE ?
               ");

               $db->db_execute($sth, array(
                  $my_pos->position,
                  $new_pos,
                  $my_pos->parent_idx
               ));
               $db->db_sth_free($sth);
               break;

            case 'netpath':
               $sth = $db->db_prepare("
                  UPDATE
                     ". MYSQL_PREFIX ."network_paths
                  SET
                     netpath_position=?
                  WHERE
                     netpath_position LIKE ?
                  AND
                     netpath_host_idx LIKE ?
               ");

               $db->db_execute($sth, array(
                  $my_pos->position,
                  $new_pos,
                  $ms->get_current_host_profile(),
               ));
               $db->db_sth_free($sth);
               break;
         }
      }
      else {

         /* move all object one position up/down */
         if($_POST['to'] == 'up')
            $dir = "-1";
         elseif($_POST['to'] == 'down')
            $dir = "+1";

         switch($_POST['move_obj']) {

            case 'chain':
               $db->db_query("
                  UPDATE
                     ". MYSQL_PREFIX ."chains
                  SET
                     chain_position = chain_position". $dir ."
                  WHERE
                     chain_netpath_idx LIKE '". $my_pos->parent_idx ."'
                  AND
                     chain_host_idx LIKE '". $ms->get_current_host_profile() ."'
               ");
               break;

            case 'pipe':
               $db->db_query("
                  UPDATE
                     ". MYSQL_PREFIX ."assign_pipes_to_chains
                  SET
                     apc_pipe_pos = apc_pipe_pos". $dir ."
                  WHERE
                     apc_chain_idx LIKE '". $my_pos->parent_idx ."'
               ");
               break;

            case 'netpath':

               $sth = $db->db_prepare("
                  UPDATE
                     ". MYSQL_PREFIX ."network_paths
                  SET
                     netpath_position = netpath_position". $dir ."
                  WHERE
                     netpath_host_idx LIKE '". $ms->get_current_host_profile() ."'
               ");
               break;
         }
      }

      if($new_pos == -1)
         $new_pos = $my_pos->max;
      if($new_pos == -2)
         $new_pos = 1;

      /* finally set objects new position */
      switch($_POST['move_obj']) {

         case 'chain':
            $sth = $db->db_prepare("
               UPDATE
                  ". MYSQL_PREFIX ."chains
               SET
                  chain_position = ?
               WHERE
                  chain_idx LIKE ?
               AND
                  chain_host_idx LIKE ?
            ");

            $db->db_execute($sth, array(
               $new_pos,
               $idx,
               $ms->get_current_host_profile(),
            ));
            $db->db_sth_free($sth);
            break;

         case 'pipe':
            $sth = $db->db_prepare("
               UPDATE
                  ". MYSQL_PREFIX ."assign_pipes_to_chains
               SET
                  apc_pipe_pos = ?
               WHERE
                  apc_idx LIKE ?
            ");

            $db->db_execute($sth, array(
               $new_pos,
               $my_pos->idx
            ));
            $db->db_sth_free($sth);
            break;

         case 'netpath':
            $sth = $db->db_prepare("
               UPDATE
                  ". MYSQL_PREFIX ."network_paths
               SET
                  netpath_position = ?
               WHERE
                  netpath_idx LIKE ?
               AND
                  netpath_host_idx LIKE ?
            ");

            $db->db_execute($sth, array(
               $new_pos,
               $idx,
               $ms->get_current_host_profile(),
            ));
            $db->db_sth_free($sth);
            break;

      }

      return "ok";

   } // alter_position()

   /**
    * handle updates
    */
   public function store()
   {
      global $ms, $db;

      if(isset($_POST['chain_sl_idx']) && is_array($_POST['chain_sl_idx'])) {

         /* save all chain service levels */
         foreach($_POST['chain_sl_idx'] as $k => $v) {

            $sth = $db->db_prepare("
               UPDATE
                  ". MYSQL_PREFIX ."chains
               SET
                  chain_sl_idx=?
               WHERE
                  chain_idx LIKE ?
               AND
                  chain_host_idx LIKE ?
            ");

            $db->db_execute($sth, array(
               $v,
               $k,
               $ms->get_current_host_profile(),
            ));

            $db->db_sth_free($sth);
         }
      }

      if(isset($_POST['chain_fallback_idx']) && is_array($_POST['chain_fallback_idx'])) {

         /* save all chain fallback service levels */
         foreach($_POST['chain_fallback_idx'] as $k => $v) {

            $sth = $db->db_prepare("
               UPDATE
                  ". MYSQL_PREFIX ."chains
               SET
                  chain_fallback_idx = ?
               WHERE
                  chain_idx LIKE ?
               AND
                  chain_host_idx LIKE ?
            ");

            $db->db_execute($sth, array(
               $v,
               $k,
               $ms->get_current_host_profile(),
            ));

            $db->db_sth_free($sth);
         }
      }

      if(isset($_POST['chain_src_target']) && is_array($_POST['chain_src_target'])) {
         /* save all chain fallback service levels */
         foreach($_POST['chain_src_target'] as $k => $v) {

            $sth = $db->db_prepare("
               UPDATE
                  ". MYSQL_PREFIX ."chains
               SET
                  chain_src_target = ?
               WHERE
                  chain_idx LIKE ?
               AND
                  chain_host_idx LIKE ?
            ");

            $db->db_execute($sth, array(
               $v,
               $k,
               $ms->get_current_host_profile(),
            ));

            $db->db_sth_free($sth);
         }
      }

      if(isset($_POST['chain_dst_target']) && is_array($_POST['chain_dst_target'])) {
         /* save all chain fallback service levels */
         foreach($_POST['chain_dst_target'] as $k => $v) {

            $sth = $db->db_prepare("
               UPDATE
                  ". MYSQL_PREFIX ."chains
               SET
                  chain_dst_target = ?
               WHERE
                  chain_idx LIKE ?
               AND
                  chain_host_idx LIKE ?
            ");

            $db->db_execute($sth, array(
               $v,
               $k,
               $ms->get_current_host_profile(),
            ));

            $db->db_sth_free($sth);
         }
      }

      if(isset($_POST['chain_direction']) && is_array($_POST['chain_direction'])) {
         /* save all chain fallback service levels */
         foreach($_POST['chain_direction'] as $k => $v) {

            $sth = $db->db_prepare("
               UPDATE
                  ". MYSQL_PREFIX ."chains
               SET
                  chain_direction = ?
               WHERE
                  chain_idx LIKE ?
               AND
                  chain_host_idx LIKE ?
            ");

            $db->db_execute($sth, array(
               $v,
               $k,
               $ms->get_current_host_profile(),
            ));

            $db->db_sth_free($sth);
         }
      }

      if(isset($_POST['chain_action']) && is_array($_POST['chain_action'])) {
         /* save all chain fallback service levels */
         foreach($_POST['chain_action'] as $k => $v) {

            $sth = $db->db_prepare("
               UPDATE
                  ". MYSQL_PREFIX ."chains
               SET
                  chain_action = ?
               WHERE
                  chain_idx LIKE ?
               AND
                  chain_host_idx LIKE ?
            ");

            $db->db_execute($sth, array(
               $v,
               $k,
               $ms->get_current_host_profile(),
            ));

            $db->db_sth_free($sth);
         }
      }

      if(isset($_POST['pipe_sl_idx']) && is_array($_POST['pipe_sl_idx'])) {

         /* save all pipe service levels */
         foreach($_POST['pipe_sl_idx'] as $k => $v) {

            $sth = $db->db_prepare("
               UPDATE
                  ". MYSQL_PREFIX ."assign_pipes_to_chains
               SET
                  apc_sl_idx = ?
               WHERE
                  apc_idx LIKE ?
            ");

            $db->db_execute($sth, array(
               $v,
               $k
            ));

            $db->db_sth_free($sth);
         }
      }

      if(isset($_POST['pipe_src_target']) && is_array($_POST['pipe_src_target'])) {
         /* save all pipe fallback service levels */
         foreach($_POST['pipe_src_target'] as $k => $v) {

            $sth = $db->db_prepare("
               UPDATE
                  ". MYSQL_PREFIX ."pipes
               SET
                  pipe_src_target = ?
               WHERE
                  pipe_idx LIKE ?
            ");

            $db->db_execute($sth, array(
               $v,
               $k
            ));

            $db->db_sth_free($sth);
         }
      }

      if(isset($_POST['pipe_dst_target']) && is_array($_POST['pipe_dst_target'])) {
         /* save all pipe fallback service levels */
         foreach($_POST['pipe_dst_target'] as $k => $v) {

            $sth = $db->db_prepare("
               UPDATE
                  ". MYSQL_PREFIX ."pipes
               SET
                  pipe_dst_target = ?
               WHERE
                  pipe_idx LIKE ?
            ");

            $db->db_execute($sth, array(
               $v,
               $k
            ));

            $db->db_sth_free($sth);
         }
      }

      if(isset($_POST['pipe_direction']) && is_array($_POST['pipe_direction'])) {
         /* save all pipe fallback service levels */
         foreach($_POST['pipe_direction'] as $k => $v) {

            $sth = $db->db_prepare("
               UPDATE
                  ". MYSQL_PREFIX ."pipes
               SET
                  pipe_direction = ?
               WHERE
                  pipe_idx LIKE ?
            ");

            $db->db_execute($sth, array(
               $v,
               $k
            ));

            $db->db_sth_free($sth);
         }
      }

      if(isset($_POST['pipe_action']) && is_array($_POST['pipe_action'])) {
         /* save all pipe fallback service levels */
         foreach($_POST['pipe_action'] as $k => $v) {

            $sth = $db->db_prepare("
               UPDATE
                  ". MYSQL_PREFIX ."pipes
               SET
                  pipe_action = ?
               WHERE
                  pipe_idx LIKE ?
            ");

            $db->db_execute($sth, array(
               $v,
               $k
            ));

            $db->db_sth_free($sth);
         }
      }

      return true;

   } // store()

} // class Page_Overview

$obj = new Page_Overview;
$obj->handler();

?>
