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

class MASTERSHAPER_OVERVIEW {

   var $db;
   var $parent;
   var $tmpl;

   /* Class constructor */
   function MASTERSHAPER_OVERVIEW($parent)
   {
      $this->db = $parent->db;
      $this->parent = &$parent;
      $this->tmpl = &$this->parent->tmpl;

   } //MASTERSHAPER_OVERVIEW()

   /* interface output */
   function show()
   {
      /* If authentication is enabled, check permissions */
      if($this->parent->getOption("authentication") == "Y" &&
         !$this->parent->checkPermissions("user_show_rules")) {

         $this->parent->printError("<img src=\"". ICON_HOME ."\" alt=\"home icon\" />&nbsp;". _("MasterShaper Ruleset Overview"), _("You do not have enough permissions to access this module!"));
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
      $res_network_paths = $this->db->db_query("
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
         $res_chains = $this->db->db_query("
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
    
               $res_pipes = $this->db->db_query("
                     SELECT *
                     FROM ". MYSQL_PREFIX ."pipes
                     WHERE
                        pipe_chain_idx='". $chain->chain_idx ."' 
                     AND
                        pipe_active='Y'
                     ORDER BY pipe_position ASC
               ");

               while($pipe = $res_pipes->fetchRow()) {

                  $this->avail_pipes[$network_path->netpath_idx][$chain->chain_idx][$this->cnt_pipes] = $pipe->pipe_idx;
                  $this->pipes[$network_path->netpath_idx][$chain->chain_idx][$pipe->pipe_idx] = $pipe;
   
                  $this->cnt_filters = 0;
                  $this->avail_filters[$network_path->netpath_idx][$chain->chain_idx][$pipe->pipe_idx] = Array();
                  $this->filters[$network_path->netpath_idx][$chain->chain_idx][$pipe->pipe_idx] = Array();

                  $res_filters = $this->db->db_query("
                     SELECT a.filter_idx as filter_idx, a.filter_name as filter_name
                     FROM ". MYSQL_PREFIX ."filters a, ". MYSQL_PREFIX ."assign_filters b
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

      $this->tmpl->register_function("start_table", array(&$this, "smarty_startTable"), false);
      $this->tmpl->register_function("sl_list", array(&$this, "smarty_sl_list"), false);
      $this->tmpl->register_function("target_list", array(&$this, "smarty_target_list"), false);
      $this->tmpl->register_block("ov_netpath", array(&$this, "smarty_ov_netpath"));
      $this->tmpl->register_block("ov_chain", array(&$this, "smarty_ov_chain"));
      $this->tmpl->register_block("ov_pipe", array(&$this, "smarty_ov_pipe"));
      $this->tmpl->register_block("ov_filter", array(&$this, "smarty_ov_filter"));
      $this->tmpl->show("overview.tpl");

   } // show()

   public function smarty_startTable($params, &$smarty)
   {
      $this->tmpl->assign('title', $params['title']);
      $this->tmpl->assign('icon', $params['icon']);
      $this->tmpl->assign('alt', $params['alt']);
      $this->tmpl->show('start_table.tpl');

   } // smarty_function_startTable()


   public function smarty_sl_list($params, &$smarty)
   {
      if(!array_key_exists('idx', $params)) {
         $this->tmpl->trigger_error("getSLList: missing 'idx' parameter", E_USER_WARNING);
         $repeat = false;
         return;
      }

      $res_sl = $this->db->db_query("SELECT * FROM ". MYSQL_PREFIX ."service_levels");
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
      if(!array_key_exists('idx', $params)) {
         $this->tmpl->trigger_error("getSLList: missing 'idx' parameter", E_USER_WARNING);
         $repeat = false;
         return;
      }

      $res_targets = $this->db->db_query("SELECT * FROM ". MYSQL_PREFIX ."targets");

      while($target = $res_targets->fetchRow()) {
         $string.= "<option value=\"". $target->target_idx ."\"";
         if($target->target_idx == $params['idx'])
            $string.= " selected=\"selected\"";
         $string.= ">". $target->target_name ."</option>\n";
      }

      return $string;

   } // smarty_target_list()

   public function smarty_ov_netpath($params, $content, &$smarty, &$repeat) {
   
      $index = $this->tmpl->get_template_vars('smarty.IB.ov_netpath.index');
      if(!$index) {
         $index = 0;
      }

      if($index < count($this->avail_network_paths)) {

         $np_idx = $this->avail_network_paths[$index];
         $np =  $this->network_paths[$np_idx];
         $this->tmpl->assign('netpath_idx', $np_idx);
         $this->tmpl->assign('netpath_name', $np->netpath_name);

         $index++;
         $this->tmpl->assign('smarty.IB.ov_netpath.index', $index);
         $repeat = true;
      }
      else {
         $repeat = false;
      }
      return $content;

   } // smart_ov_netpath()

   public function smarty_ov_chain($params, $content, &$smarty, &$repeat) {

      if(!array_key_exists('np_idx', $params)) {
         $this->tmpl->trigger_error("ov_netpath: missing 'np_idx' parameter", E_USER_WARNING);
         $repeat = false;
         return;
      }
      
      $np_idx = $params['np_idx'];

      $index = $this->tmpl->get_template_vars('smarty.IB.ov_chain.index-'. $np_idx);
      if(!$index) {
         $index = 0;
      }

      if($index < count($this->avail_chains[$np_idx])) {

         $chain_idx = $this->avail_chains[$np_idx][$index];
         $chain =  $this->chains[$np_idx][$chain_idx];
         $this->tmpl->assign('chain_idx', $chain_idx);
         $this->tmpl->assign('chain_name', $chain->chain_name);
         $this->tmpl->assign('chain_sl_idx', $chain->chain_sl_idx);
         $this->tmpl->assign('chain_fallback_idx', $chain->chain_fallback_idx);
         $this->tmpl->assign('chain_src_target', $chain->chain_src_target);
         $this->tmpl->assign('chain_dst_target', $chain->chain_dst_target);
         $this->tmpl->assign('chain_direction', $chain->chain_direction);
         $this->tmpl->assign('chain_action', $chain->chain_action);

         if($chain->chain_sl_idx != 0) {
            $this->tmpl->assign('chain_has_sl', 'true');
         }

         $index++;
         $this->tmpl->assign('smarty.IB.ov_chain.index-'. $np_idx, $index);

         $repeat = true;
      }
      else {
         $repeat = false;
      }

      return $content;

   } // smart_ov_chain()

   public function smarty_ov_pipe($params, $content, &$smarty, &$repeat) {

      if(!array_key_exists('np_idx', $params)) {
         $this->tmpl->trigger_error("ov_netpath: missing 'np_idx' parameter", E_USER_WARNING);
         $repeat = false;
         return;
      }
      if(!array_key_exists('chain_idx', $params)) {
         $this->tmpl->trigger_error("ov_netpath: missing 'chain_idx' parameter", E_USER_WARNING);
         $repeat = false;
         return;
      }
      
      $np_idx = $params['np_idx'];
      $chain_idx = $params['chain_idx'];

      $index = $this->tmpl->get_template_vars('smarty.IB.ov_pipe.index-'. $np_idx ."-". $chain_idx);
      if(!$index) {
         $index = 0;
      }

      if($index < count($this->avail_pipes[$np_idx][$chain_idx])) {

         $pipe_idx = $this->avail_pipes[$np_idx][$chain_idx][$index];
         $pipe = $this->pipes[$np_idx][$chain_idx][$pipe_idx];

         $this->tmpl->assign('pipe_idx', $pipe_idx);
         $this->tmpl->assign('pipe_name', $pipe->pipe_name);
         $this->tmpl->assign('pipe_sl_idx', $pipe->pipe_sl_idx);
         $this->tmpl->assign('pipe_fallback_idx', $pipe->pipe_fallback_idx);
         $this->tmpl->assign('pipe_src_target', $pipe->pipe_src_target);
         $this->tmpl->assign('pipe_dst_target', $pipe->pipe_dst_target);
         $this->tmpl->assign('pipe_direction', $pipe->pipe_direction);
         $this->tmpl->assign('pipe_action', $pipe->pipe_action);
         $this->tmpl->assign('counter', $index+1);

         if($pipe->pipe_sl_idx != 0) {
            $this->tmpl->assign('pipe_has_sl', 'true');
         }

         $index++;
         $this->tmpl->assign('smarty.IB.ov_pipe.index-'. $np_idx ."-". $chain_idx, $index);

         $repeat = true;
      }
      else {
         $repeat = false;
      }

      return $content;

   } // smart_ov_pipe()

   public function smarty_ov_filter($params, $content, &$smarty, &$repeat) {

      if(!array_key_exists('np_idx', $params)) {
         $this->tmpl->trigger_error("ov_netpath: missing 'np_idx' parameter", E_USER_WARNING);
         $repeat = false;
         return;
      }
      if(!array_key_exists('chain_idx', $params)) {
         $this->tmpl->trigger_error("ov_netpath: missing 'chain_idx' parameter", E_USER_WARNING);
         $repeat = false;
         return;
      }
      if(!array_key_exists('pipe_idx', $params)) {
         $this->tmpl->trigger_error("ov_netpath: missing 'pipe_idx' parameter", E_USER_WARNING);
         $repeat = false;
         return;
      }
      
      $np_idx = $params['np_idx'];
      $chain_idx = $params['chain_idx'];
      $pipe_idx = $params['pipe_idx'];

      $index = $this->tmpl->get_template_vars('smarty.IB.ov_filter.index-'. $np_idx ."-". $chain_idx ."-". $pipe_idx);
      if(!$index) {
         $index = 0;
      }

      if($index < count($this->avail_filters[$np_idx][$chain_idx][$pipe_idx])) {

         $filter_idx = $this->avail_filters[$np_idx][$chain_idx][$pipe_idx][$index];
         $filter = $this->filters[$np_idx][$chain_idx][$pipe_idx][$filter_idx];

         $this->tmpl->assign('filter_idx', $filter_idx);
         $this->tmpl->assign('filter_name', $filter->filter_name);

         $index++;
         $this->tmpl->assign('smarty.IB.ov_filter.index-'. $np_idx ."-". $chain_idx ."-". $pipe_idx, $index);

         $repeat = true;
      }
      else {
         $repeat = false;
      }

      return $content;

   } // smart_ov_filter()


}

?>