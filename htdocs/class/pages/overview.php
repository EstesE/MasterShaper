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

         $ms->throwError("<img src=\"". ICON_HOME ."\" alt=\"home icon\" />&nbsp;". _("MasterShaper Ruleset Overview"), _("You do not have enough permissions to access this module!"));
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
         SELECT *
         FROM ". MYSQL_PREFIX ."network_paths
         WHERE
            netpath_active='Y'
         ORDER BY netpath_position
      ");

      while($network_path = $res_network_paths->fetchRow()) {
   
         $this->cnt_chains = 0;
         $this->avail_chains[$network_path->netpath_idx] = Array();
         $this->chains[$network_path->netpath_idx] = Array();
         $this->pipes[$network_path->netpath_idx] = Array();
         $this->filters[$network_path->netpath_idx] = Array();

         $this->avail_network_paths[$this->cnt_network_paths] = $network_path->netpath_idx;
         $this->network_paths[$network_path->netpath_idx] = $network_path;


         /* get a list of chains for the current netpath */
         $res_chains = $db->db_query("
            SELECT *
            FROM ". MYSQL_PREFIX ."chains
            WHERE 
               chain_netpath_idx='". $network_path->netpath_idx ."'
            AND 
               chain_active='Y'
            ORDER BY chain_position ASC
         ");

         while($chain = $res_chains->fetchRow()) {

            $this->avail_chains[$network_path->netpath_idx][$this->cnt_chains] = $chain->chain_idx;
            $this->chains[$network_path->netpath_idx][$chain->chain_idx] = $chain;

            $this->cnt_pipes = 0;
            $this->avail_pipes[$network_path->netpath_idx][$chain->chain_idx] = Array();
            $this->pipes[$network_path->netpath_idx][$chain->chain_idx] = Array();
            $this->filters[$network_path->netpath_idx][$chain->chain_idx] = Array();

            /* pipes are only available if the chain DOES NOT ignore QoS or DOES NOT use fallback service level */
            if($chain->chain_sl_idx != 0 && $chain->chain_fallback_idx != 0) {
    
               $res_pipes = $db->db_query("
                     SELECT
                        p.*
                     FROM
                        ". MYSQL_PREFIX ."pipes p
                     INNER JOIN
                        ". MYSQL_PREFIX ."assign_pipes_to_chains apc
                     ON
                        p.pipe_idx=apc.apc_pipe_idx
                     WHERE
                        apc.apc_chain_idx='". $chain->chain_idx ."'
                     AND
                        p.pipe_active='Y'
                     ORDER BY
                        p.pipe_position ASC
               ");

               while($pipe = $res_pipes->fetchRow()) {

                  $this->avail_pipes[$network_path->netpath_idx][$chain->chain_idx][$this->cnt_pipes] = $pipe->pipe_idx;
                  $this->pipes[$network_path->netpath_idx][$chain->chain_idx][$pipe->pipe_idx] = $pipe;
   
                  $this->cnt_filters = 0;
                  $this->avail_filters[$network_path->netpath_idx][$chain->chain_idx][$pipe->pipe_idx] = Array();
                  $this->filters[$network_path->netpath_idx][$chain->chain_idx][$pipe->pipe_idx] = Array();

                  $res_filters = $db->db_query("
                     SELECT a.filter_idx as filter_idx, a.filter_name as filter_name
                     FROM ". MYSQL_PREFIX ."filters a, ". MYSQL_PREFIX ."assign_filters_to_pipes b
                     WHERE
                        b.apf_pipe_idx='". $pipe->pipe_idx ."'
                     AND
                        b.apf_filter_idx=a.filter_idx
                     AND
                        a.filter_active='Y'
                  ");

                  while($filter = $res_filters->fetchRow()) {
   
                     $this->avail_filters[$network_path->netpath_idx][$chain->chain_idx][$pipe->pipe_idx][$this->cnt_filters] = $filter->filter_idx;
                     $this->filters[$network_path->netpath_idx][$chain->chain_idx][$pipe->pipe_idx][$filter->filter_idx] = $filter;

                     $this->cnt_filters++;
         
                  }
                  $this->cnt_pipes++;
               }
            }
            $this->cnt_chains++;
         }
         $this->cnt_network_paths++;
      }

      $tmpl->register_function("sl_list", array(&$this, "smarty_sl_list"), false);
      $tmpl->register_function("target_list", array(&$this, "smarty_target_list"), false);
      $tmpl->register_block("ov_netpath", array(&$this, "smarty_ov_netpath"));
      $tmpl->register_block("ov_chain", array(&$this, "smarty_ov_chain"));
      $tmpl->register_block("ov_pipe", array(&$this, "smarty_ov_pipe"));
      $tmpl->register_block("ov_filter", array(&$this, "smarty_ov_filter"));

      return $tmpl->fetch("overview.tpl");

   } // showList()

   public function smarty_sl_list($params, &$smarty)
   {
      global $db;

      if(!array_key_exists('idx', $params)) {
         $tmpl->trigger_error("getSLList: missing 'idx' parameter", E_USER_WARNING);
         $repeat = false;
         return;
      }

      $res_sl = $db->db_query("SELECT * FROM ". MYSQL_PREFIX ."service_levels");
      while($sl = $res_sl->fetchRow()) {
         $string.= "<option value=\"". $sl->sl_idx ."\"";
         if($sl->sl_idx == $params['idx'])
            $string.= " selected=\"selected\"";
         $string.= ">". $sl->sl_name ."</option>\n";
      }

      return $string;

   } // smarty_sl_list()

   public function smarty_target_list($params, &$smarty)
   {
      global $db;

      if(!array_key_exists('idx', $params)) {
         $tmpl->trigger_error("getSLList: missing 'idx' parameter", E_USER_WARNING);
         $repeat = false;
         return;
      }

      $res_targets = $db->db_query("SELECT * FROM ". MYSQL_PREFIX ."targets");

      while($target = $res_targets->fetchRow()) {
         $string.= "<option value=\"". $target->target_idx ."\"";
         if($target->target_idx == $params['idx'])
            $string.= " selected=\"selected\"";
         $string.= ">". $target->target_name ."</option>\n";
      }

      return $string;

   } // smarty_target_list()

   public function smarty_ov_netpath($params, $content, &$smarty, &$repeat)
   {
      global $db, $tmpl;

      $index = $tmpl->get_template_vars('smarty.IB.ov_netpath.index');
      if(!$index) {
         $index = 0;
      }

      if($index < count($this->avail_network_paths)) {

         $np_idx = $this->avail_network_paths[$index];
         $np =  $this->network_paths[$np_idx];
         $tmpl->assign('netpath_idx', $np_idx);
         $tmpl->assign('netpath_name', $np->netpath_name);

         $index++;
         $tmpl->assign('smarty.IB.ov_netpath.index', $index);
         $repeat = true;
      }
      else {
         $repeat = false;
      }
      return $content;

   } // smart_ov_netpath()

   public function smarty_ov_chain($params, $content, &$smarty, &$repeat)
   {
      global $db, $tmpl;

      if(!array_key_exists('np_idx', $params)) {
         $tmpl->trigger_error("ov_netpath: missing 'np_idx' parameter", E_USER_WARNING);
         $repeat = false;
         return;
      }
      
      $np_idx = $params['np_idx'];

      $index = $tmpl->get_template_vars('smarty.IB.ov_chain.index-'. $np_idx);
      if(!$index) {
         $index = 0;
      }

      if($index < count($this->avail_chains[$np_idx])) {

         $chain_idx = $this->avail_chains[$np_idx][$index];
         $chain =  $this->chains[$np_idx][$chain_idx];
         $tmpl->assign('chain_idx', $chain_idx);
         $tmpl->assign('chain_name', $chain->chain_name);
         $tmpl->assign('chain_sl_idx', $chain->chain_sl_idx);
         $tmpl->assign('chain_fallback_idx', $chain->chain_fallback_idx);
         $tmpl->assign('chain_src_target', $chain->chain_src_target);
         $tmpl->assign('chain_dst_target', $chain->chain_dst_target);
         $tmpl->assign('chain_direction', $chain->chain_direction);
         $tmpl->assign('chain_action', $chain->chain_action);

         if($chain->chain_sl_idx != 0)
            $tmpl->assign('chain_has_sl', true);
         else
            $tmpl->assign('chain_has_sl', false);

         $index++;
         $tmpl->assign('smarty.IB.ov_chain.index-'. $np_idx, $index);

         $repeat = true;
      }
      else {
         $repeat = false;
      }

      return $content;

   } // smart_ov_chain()

   public function smarty_ov_pipe($params, $content, &$smarty, &$repeat)
   {
      global $db, $tmpl;

      if(!array_key_exists('np_idx', $params)) {
         $tmpl->trigger_error("ov_netpath: missing 'np_idx' parameter", E_USER_WARNING);
         $repeat = false;
         return;
      }
      if(!array_key_exists('chain_idx', $params)) {
         $tmpl->trigger_error("ov_netpath: missing 'chain_idx' parameter", E_USER_WARNING);
         $repeat = false;
         return;
      }
      
      $np_idx = $params['np_idx'];
      $chain_idx = $params['chain_idx'];

      $index = $tmpl->get_template_vars('smarty.IB.ov_pipe.index-'. $np_idx ."-". $chain_idx);
      if(!$index) {
         $index = 0;
      }

      if($index < count($this->avail_pipes[$np_idx][$chain_idx])) {

         $pipe_idx = $this->avail_pipes[$np_idx][$chain_idx][$index];
         $pipe = $this->pipes[$np_idx][$chain_idx][$pipe_idx];

         $tmpl->assign('pipe_idx', $pipe_idx);
         $tmpl->assign('pipe_name', $pipe->pipe_name);
         $tmpl->assign('pipe_sl_idx', $pipe->pipe_sl_idx);
         $tmpl->assign('pipe_fallback_idx', $pipe->pipe_fallback_idx);
         $tmpl->assign('pipe_src_target', $pipe->pipe_src_target);
         $tmpl->assign('pipe_dst_target', $pipe->pipe_dst_target);
         $tmpl->assign('pipe_direction', $pipe->pipe_direction);
         $tmpl->assign('pipe_action', $pipe->pipe_action);
         $tmpl->assign('counter', $index+1);

         if($pipe->pipe_sl_idx != 0) {
            $tmpl->assign('pipe_has_sl', 'true');
         }

         $index++;
         $tmpl->assign('smarty.IB.ov_pipe.index-'. $np_idx ."-". $chain_idx, $index);

         $repeat = true;
      }
      else {
         $repeat = false;
      }

      return $content;

   } // smart_ov_pipe()

   public function smarty_ov_filter($params, $content, &$smarty, &$repeat)
   {
      global $db, $tmpl;

      if(!array_key_exists('np_idx', $params)) {
         $tmpl->trigger_error("ov_netpath: missing 'np_idx' parameter", E_USER_WARNING);
         $repeat = false;
         return;
      }
      if(!array_key_exists('chain_idx', $params)) {
         $tmpl->trigger_error("ov_netpath: missing 'chain_idx' parameter", E_USER_WARNING);
         $repeat = false;
         return;
      }
      if(!array_key_exists('pipe_idx', $params)) {
         $tmpl->trigger_error("ov_netpath: missing 'pipe_idx' parameter", E_USER_WARNING);
         $repeat = false;
         return;
      }
      
      $np_idx = $params['np_idx'];
      $chain_idx = $params['chain_idx'];
      $pipe_idx = $params['pipe_idx'];

      $index = $tmpl->get_template_vars('smarty.IB.ov_filter.index-'. $np_idx ."-". $chain_idx ."-". $pipe_idx);
      if(!$index) {
         $index = 0;
      }

      if($index < count($this->avail_filters[$np_idx][$chain_idx][$pipe_idx])) {

         $filter_idx = $this->avail_filters[$np_idx][$chain_idx][$pipe_idx][$index];
         $filter = $this->filters[$np_idx][$chain_idx][$pipe_idx][$filter_idx];

         $tmpl->assign('filter_idx', $filter_idx);
         $tmpl->assign('filter_name', $filter->filter_name);

         $index++;
         $tmpl->assign('smarty.IB.ov_filter.index-'. $np_idx ."-". $chain_idx ."-". $pipe_idx, $index);

         $repeat = true;
      }
      else {
         $repeat = false;
      }

      return $content;

   } // smart_ov_filter()

   public function alter_position()
   {
      global $ms, $db;

      if(!isset($_POST['type']))
         return "Missing object-type to alter position off";

      switch($_POST['type']) {

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
            return "Unkown object-type";
            break;

      }

      if(!isset($_POST['idx']) || !is_numeric($_POST['idx']))
         $ms->throwError(_("Id to alter position is missing or not numeric!"));

      if(!isset($_POST['to']) || !in_array($_POST['to'], array('up','down')))
         $ms->throwError(_("Don't know in which direction I shall alter position!"));

      $idx = $_POST['idx'];

      // get objects current position
      switch($_POST['type']) {
         case 'chain':
         case 'pipe':
            $query = "
               SELECT
                  ". $obj_col ."_position as position,
                  ". $obj_parent ." as parent_idx,
                  (
                     /* get colums max position */
                     SELECT
                        MAX(". $obj_col ."_position)
                     FROM
                        ". MYSQL_PREFIX . $obj_table ."
                     WHERE
                        /* but only for our parents objects */
                        ". $obj_parent ." = (
                        SELECT
                           ". $obj_parent ."
                        FROM
                           ". MYSQL_PREFIX . $obj_table ."
                        WHERE
                           ". $obj_col ."_idx='". $idx ."'
                     )
                  ) as max
               FROM
                  ". MYSQL_PREFIX . $obj_table ."
               WHERE
                  ". $obj_col ."_idx='". $idx ."'
            ";
            break;
         case 'netpath':
            $query = "
               SELECT
                  ". $obj_col ."_position as position,
                  (
                     /* get colums max position */
                     SELECT
                        MAX(". $obj_col ."_position)
                     FROM
                        ". MYSQL_PREFIX . $obj_table ."
                  ) as max
               FROM
                  ". MYSQL_PREFIX .  $obj_table ."
               WHERE
                  ". $obj_col ."_idx='". $idx ."'
            ";
            break;
      }

      if(!isset($query))
         return;

      $my_pos = $db->db_fetchSingleRow($query);

      if($_POST['to'] == 'up') {
         /* if we are not at the top most position */
         if($my_pos->position != 1)
            $new_pos = $my_pos->position - 1;
         else
            $new_pos = -1;
      }
      elseif($_POST['to'] == 'down') {
         /* if we are not at the bottom most position */
         if($my_pos->position != $my_pos->max)
            $new_pos = $my_pos->position + 1;
         else
            $new_pos = -2;
      }
      else
         $new_pos = $my_pos->position;

      /* if no position will be changed, return */
      if($new_pos == $my_pos->position)
         return "ok";

      //return $new_pos ." ". $my_pos->position ." ". $my_pos->max;
      /* new position can not be below null */
      if($new_pos == 0)
         $new_pos = 1;

      /* just moving ... */
      if($new_pos > 0) {
         /* move all other objects away */
         switch($_POST['type']) {
            case 'chain':
            case 'pipe':
               $db->db_query("
                  UPDATE
                     ". MYSQL_PREFIX . $obj_table ."
                  SET
                     ". $obj_col ."_position='". $my_pos->position ."'
                  WHERE
                     ". $obj_col ."_position='". $new_pos ."'
                  AND
                     ". $obj_parent ."=". $my_pos->parent_idx ."
               ");
               break;
            case 'netpath':
               $db->db_query("
                  UPDATE
                     ". MYSQL_PREFIX . $obj_table ."
                  SET
                     ". $obj_col ."_position='". $my_pos->position ."'
                  WHERE
                     ". $obj_col ."_position='". $new_pos ."'
               ");
               break;
         }
      }
      else {

         /* move all object one position up/down */
         if($_POST['to'] == 'up')
            $dir = "-1";
         elseif($_POST['to'] == 'down')
            $dir = "+1";

         switch($_POST['type']) {
            case 'chain':
            case 'pipe':
               $db->db_query("
                  UPDATE
                     ". MYSQL_PREFIX . $obj_table ."
                  SET
                     ". $obj_col ."_position=". $obj_col ."_position" . $dir ."
                  WHERE
                     ". $obj_parent ."=". $my_pos->parent_idx ."
               ");
               break;
            case 'netpath':
               $db->db_query("
                  UPDATE
                     ". MYSQL_PREFIX . $obj_table ."
                  SET
                     ". $obj_col ."_position=". $obj_col ."_position" . $dir ."
                  WHERE
                     ". $obj_col ."_position='". $new_pos ."'
               ");
               break;
         }

      }

      if($new_pos == -1)
         $new_pos = $my_pos->max;
      if($new_pos == -2)
         $new_pos = 1;

      /* set objects new position */
      $db->db_query("
         UPDATE ". MYSQL_PREFIX . $obj_table ."
         SET
            ". $obj_col ."_position='". $new_pos ."'
         WHERE
            ". $obj_col ."_idx='". $idx ."';
      ");

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
            $db->db_query("
               UPDATE ". MYSQL_PREFIX ."chains
               SET
                  chain_sl_idx='". $v ."'
               WHERE
                  chain_idx='". $k ."'
            ");
         }
      }

      if(isset($_POST['chain_fallback_idx']) && is_array($_POST['chain_fallback_idx'])) {
         /* save all chain fallback service levels */
         foreach($_POST['chain_fallback_idx'] as $k => $v) {
            $db->db_query("
               UPDATE ". MYSQL_PREFIX ."chains
               SET
                  chain_fallback_idx='". $v ."'
               WHERE
                  chain_idx='". $k ."'
            ");
         }
      }

      if(isset($_POST['chain_src_target']) && is_array($_POST['chain_src_target'])) {
         /* save all chain fallback service levels */
         foreach($_POST['chain_src_target'] as $k => $v) {
            $db->db_query("
               UPDATE ". MYSQL_PREFIX ."chains
               SET
                  chain_src_target='". $v ."'
               WHERE
                  chain_idx='". $k ."'
            ");
         }
      }

      if(isset($_POST['chain_dst_target']) && is_array($_POST['chain_dst_target'])) {
         /* save all chain fallback service levels */
         foreach($_POST['chain_dst_target'] as $k => $v) {
            $db->db_query("
               UPDATE ". MYSQL_PREFIX ."chains
               SET
                  chain_dst_target='". $v ."'
               WHERE
                  chain_idx='". $k ."'
            ");
         }
      }

      if(isset($_POST['chain_direction']) && is_array($_POST['chain_direction'])) {
         /* save all chain fallback service levels */
         foreach($_POST['chain_direction'] as $k => $v) {
            $db->db_query("
               UPDATE ". MYSQL_PREFIX ."chains
               SET
                  chain_direction='". $v ."'
               WHERE
                  chain_idx='". $k ."'
            ");
         }
      }

      if(isset($_POST['chain_action']) && is_array($_POST['chain_action'])) {
         /* save all chain fallback service levels */
         foreach($_POST['chain_action'] as $k => $v) {
            $db->db_query("
               UPDATE ". MYSQL_PREFIX ."chains
               SET
                  chain_action='". $v ."'
               WHERE
                  chain_idx='". $k ."'
            ");
         }
      }

      if(isset($_POST['pipe_sl_idx']) && is_array($_POST['pipe_sl_idx'])) {
         /* save all pipe service levels */
         foreach($_POST['pipe_sl_idx'] as $k => $v) {
            $db->db_query("
               UPDATE ". MYSQL_PREFIX ."pipes
               SET
                  pipe_sl_idx='". $v ."'
               WHERE
                  pipe_idx='". $k ."'
            ");
         }
      }

      if(isset($_POST['pipe_src_target']) && is_array($_POST['pipe_src_target'])) {
         /* save all pipe fallback service levels */
         foreach($_POST['pipe_src_target'] as $k => $v) {
            $db->db_query("
               UPDATE ". MYSQL_PREFIX ."pipes
               SET
                  pipe_src_target='". $v ."'
               WHERE
                  pipe_idx='". $k ."'
            ");
         }
      }

      if(isset($_POST['pipe_dst_target']) && is_array($_POST['pipe_dst_target'])) {
         /* save all pipe fallback service levels */
         foreach($_POST['pipe_dst_target'] as $k => $v) {
            $db->db_query("
               UPDATE ". MYSQL_PREFIX ."pipes
               SET
                  pipe_dst_target='". $v ."'
               WHERE
                  pipe_idx='". $k ."'
            ");
         }
      }

      if(isset($_POST['pipe_direction']) && is_array($_POST['pipe_direction'])) {
         /* save all pipe fallback service levels */
         foreach($_POST['pipe_direction'] as $k => $v) {
            $db->db_query("
               UPDATE ". MYSQL_PREFIX ."pipes
               SET
                  pipe_direction='". $v ."'
               WHERE
                  pipe_idx='". $k ."'
            ");
         }
      }

      if(isset($_POST['pipe_action']) && is_array($_POST['pipe_action'])) {
         /* save all pipe fallback service levels */
         foreach($_POST['pipe_action'] as $k => $v) {
            $db->db_query("
               UPDATE ". MYSQL_PREFIX ."pipes
               SET
                  pipe_action='". $v ."'
               WHERE
                  pipe_idx='". $k ."'
            ");
         }
      }

      return true;

   } // store()

} // class Page_Overview

$obj = new Page_Overview;
$obj->handler();

?>