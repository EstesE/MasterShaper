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

      $this->tmpl->assign('np_max', $this->avail_network_paths);
      $this->tmpl->assign('chain_max', $this->avail_chains);
      $this->tmpl->assign('pipes_max', $this->avail_pipes);
      $this->tmpl->assign('filters_max', $this->avail_filters);
      $this->tmpl->assign('network_paths', $this->network_paths);
      $this->tmpl->assign('chains', $this->chains);
      $this->tmpl->assign('pipes', $this->pipes);
      $this->tmpl->assign('filters', $this->filters);
      $this->tmpl->register_function("start_table", array(&$this, "smarty_startTable"), false);
      $this->tmpl->register_function("sl_list", array(&$this, "smarty_sl_list"), false);
      $this->tmpl->register_block("ov_netpath", array(&$this, "smarty_ov_netpath"));
      $this->tmpl->register_block("ov_chain", array(&$this, "smarty_ov_chain"));
      $this->tmpl->register_block("ov_pipe", array(&$this, "smarty_ov_pipe"));
      $this->tmpl->register_block("ov_filter", array(&$this, "smarty_ov_filter"));
      $this->tmpl->show("overview.tpl");
      return;

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
         if($sl->sl_idx == $idx)
            $string.= " selected=\"selected\"";
         $string.= ">". $sl->sl_name ."</option>\n";
      }

      return $string;

   } // smarty_sl_list()

   public function smarty_getTargetList($params, &$smarty)
   {
      $targets = $this->db->db_query("SELECT * FROM ". MYSQL_PREFIX ."targets");

      while($target = $targets->fetchRow()) {
         $string = "<option value=\"". $target->target_idx ."\"";
         if($target->target_idx == $chain->chain_src_target)
            $string = " selected=\"selected\"";
         $string = ">". $target->target_name ."</option>\n";
      }

      return $string;

   } // smarty_getTargetList()

   public function smarty_ov_netpath($params, $content, &$smarty, &$repeat) {
   
      $index = $this->tmpl->get_template_vars('smarty.IB.ov_netpath.index');
      if(!$index) {
         $index = 0;
      }

      if($index < count($this->avail_network_paths)) {

         $this->tmpl->assign('netpath_name', $this->network_paths[$index]->netpath_name);
         $this->tmpl->assign('netpath_idx', $this->avail_network_paths[$index]);

         $index++;
         $this->tmpl->assign('smarty.IB.ov_netpath.index', $index);
         $repeat = true;
         return;

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

      $index = $this->tmpl->get_template_vars('smarty.IB.ov_chain.index');
      if(!$index) {
         $index = 0;
      }

      if($index < count($this->avail_chains)) {

         $chain_idx = $this->avail_chains[$np_idx][$index];

         $this->tmpl->assign('chain_idx', $chain_idx);
         $this->tmpl->assign('chain_name', $this->chains[$np_idx][$chain_idx]->chain_name);
         
         if($this->chains[$np_idx][$chain_idx]->chain_sl_idx != 0) {
            $this->tmpl->assign('chain_has_sl', 'true');
         }

         $index++;
         $this->tmpl->assign('smarty.IB.ov_chain.index', $index);

         $repeat = true;
         return;

      }
      else {
         $repeat = false;
      }

      return $content;

   } // smart_ov_chain()
}

?>
