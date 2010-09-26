<?php

/***************************************************************************
 *
 * Copyright (c) by Andreas Unterkircher, unki@netshadow.at
 * All rights reserved
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  any later version.
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

class MASTERSHAPER_TMPL extends Smarty {

   public function __construct()
   {
      global $ms;

      $this->Smarty();
      $this->template_dir = BASE_PATH .'/templates';
      $this->compile_dir  = BASE_PATH .'/templates_c';
      $this->config_dir   = BASE_PATH .'/smarty_config';
      $this->cache_dir    = BASE_PATH .'/smarty_cache';

      if(!is_writeable($this->compile_dir)) {
         print "Smarty compile directory ". $this->compile_dir ." is not writeable for the current user (". $ms->getuid() .").<br />\n";
         print "Please check that the permissions are set correctly to this directory.<br />\n";
         exit(1);
      }

      $this->assign('icon_chains', WEB_PATH .'/icons/flag_blue.gif');
      $this->assign('icon_chains_assign_pipe', WEB_PATH .'/icons/flag_blue_with_purple_arrow.gif');
      $this->assign('icon_options', WEB_PATH .'/icons/options.gif');
      $this->assign('icon_pipes', WEB_PATH .'/icons/flag_pink.gif');
      $this->assign('icon_ports', WEB_PATH .'/icons/flag_orange.gif');
      $this->assign('icon_protocols', WEB_PATH .'/icons/flag_red.gif');
      $this->assign('icon_servicelevels', WEB_PATH .'/icons/flag_yellow.gif');
      $this->assign('icon_filters', WEB_PATH .'/icons/flag_green.gif');
      $this->assign('icon_targets', WEB_PATH .'/icons/flag_purple.gif');
      $this->assign('icon_delete', WEB_PATH .'/icons/delete.png');
      $this->assign('icon_active', WEB_PATH .'/icons/active.gif');
      $this->assign('icon_inactive', WEB_PATH .'/icons/inactive.gif');
      $this->assign('icon_arrow_left', WEB_PATH .'/icons/arrow_left.gif');
      $this->assign('icon_arrow_right', WEB_PATH .'/icons/arrow_right.gif');
      $this->assign('icon_chains_arrow_up', WEB_PATH .'/icons/ms_chains_arrow_up_14.gif');
      $this->assign('icon_chains_arrow_down', WEB_PATH .'/icons/ms_chains_arrow_down_14.gif');
      $this->assign('icon_pipes_arrow_up', WEB_PATH .'/icons/ms_pipes_arrow_up_14.gif');
      $this->assign('icon_pipes_arrow_down', WEB_PATH .'/icons/ms_pipes_arrow_down_14.gif');
      $this->assign('icon_users', WEB_PATH .'/icons/ms_users_14.gif');
      $this->assign('icon_about', WEB_PATH .'/icons/home.gif');
      $this->assign('icon_home', WEB_PATH .'/icons/home.gif');
      $this->assign('icon_new', WEB_PATH .'/icons/page_white.gif');
      $this->assign('icon_monitor', WEB_PATH .'/icons/chart_pie.gif');
      $this->assign('icon_shaper_start', WEB_PATH .'/icons/enable.gif');
      $this->assign('icon_shaper_stop', WEB_PATH .'/icons/disable.gif');
      $this->assign('icon_bandwidth', WEB_PATH .'/icons/bandwidth.gif');
      $this->assign('icon_update', WEB_PATH .'/icons/update.gif');
      $this->assign('icon_interfaces', WEB_PATH .'/icons/network_card.gif');
      $this->assign('icon_treeend', WEB_PATH .'/icons/tree_end.gif');
      $this->assign('icon_rules_show', WEB_PATH .'/icons/show.gif');
      $this->assign('icon_rules_load', WEB_PATH .'/icons/enable.gif');
      $this->assign('icon_rules_unload', WEB_PATH .'/icons/disable.gif');
      $this->assign('icon_rules_export', WEB_PATH .'/icons/disk.gif');
      $this->assign('icon_rules_restore', WEB_PATH .'/icons/restore.gif');
      $this->assign('icon_rules_reset', WEB_PATH .'/icons/reset.gif');
      $this->assign('icon_rules_update', WEB_PATH .'/icons/update.gif');
      $this->assign('icon_pdf', WEB_PATH .'/icons/page_white_acrobat.gif');
      $this->assign('icon_menu_down', WEB_PATH .'/icons/bullet_arrow_down.png');
      $this->assign('web_path', WEB_PATH);

      $this->register_function("start_table", array(&$this, "smarty_startTable"), false); 
      $this->register_function("page_end", array(&$this, "smarty_page_end"), false); 
      $this->register_function("year_select", array(&$this, "smarty_year_select"), false); 
      $this->register_function("month_select", array(&$this, "smarty_month_select"), false); 
      $this->register_function("day_select", array(&$this, "smarty_day_select"), false); 
      $this->register_function("hour_select", array(&$this, "smarty_hour_select"), false); 
      $this->register_function("minute_select", array(&$this, "smarty_minute_select"), false); 
      $this->register_function("chain_select_list", array(&$this, "smarty_chain_select_list"), false);
      $this->register_function("pipe_select_list", array(&$this, "smarty_pipe_select_list"), false);
      $this->register_function("target_select_list", array(&$this, "smarty_target_select_list"), false);
      $this->register_function("service_level_select_list", array(&$this, "smarty_service_level_select_list"), false);
      $this->register_function("network_path_select_list", array(&$this, "smarty_network_path_select_list"), false);

   } // __construct()

   public function show($template)
   {
      $this->display($template);

   } // show()

   public function smarty_startTable($params, &$smarty)
   {  
      $this->assign('title', $params['title']);
      $this->assign('icon', $params['icon']);
      $this->assign('alt', $params['alt']);
      $this->show('start_table.tpl');

   } // smarty_function_startTable() 

   public function smarty_page_end($params, &$smarty)
   {  
      if(isset($params['focus_to'])) {
         $this->assign('focus_to', $params['focus_to']);
      }

      $this->show('page_end.tpl');

   } // smarty_function_startTable() 


   public function smarty_year_select($params, &$smarty)
   {
      global $ms;
      print $ms->getYearList($params['current']); 
   } // smarty_year_select()

   public function smarty_month_select($params, &$smarty)
   {
      global $ms;
      print $ms->getMonthList($params['current']); 
   } // smarty_month_select()

   public function smarty_day_select($params, &$smarty)
   {
      global $ms;
      print $ms->getDayList($params['current']); 
   } // smarty_day_select()

   public function smarty_hour_select($params, &$smarty)
   {
      global $ms;
      print $ms->getHourList($params['current']); 
   } // smarty_hour_select()

   public function smarty_minute_select($params, &$smarty)
   {
      global $ms;
      print $ms->getMinuteList($params['current']); 
   } // smarty_minute_select()

   public function smarty_chain_select_list($params, &$smarty)
   {
      global $db;

      if(!array_key_exists('chain_idx', $params)) {
         $this->trigger_error("smarty_chain_select_list: missing 'chain_idx' parameter", E_USER_WARNING);
         $repeat = false;
         return;
      }

      $result = $db->db_query("
         SELECT
            *
         FROM
            ". MYSQL_PREFIX ."chains
      ");

      while($row = $result->fetchrow()) {
         $string.= "<option value='". $row->chain_idx ."'";
         if($row->chain_idx == $params['chain_idx']) {
            $string.= " selected=\"selected\"";
         }
         $string.= ">". $row->chain_name ."</option>\n";
      }

      return $string;

   } // smarty_chain_select_list()

   public function smarty_pipe_select_list($params, &$smarty)
   {
      global $db;

      if(!array_key_exists('pipe_idx', $params)) {
         $this->trigger_error("smarty_pipe_select_list: missing 'pipe_idx' parameter", E_USER_WARNING);
         $repeat = false;
         return;
      }

      $result = $db->db_query("
         SELECT
            *
         FROM
            ". MYSQL_PREFIX ."pipes
      ");

      while($row = $result->fetchrow()) {
         $string.= "<option value='". $row->pipe_idx ."'";
         if($row->pipe_idx == $params['pipe_idx']) {
            $string.= " selected=\"selected\"";
         }
         $string.= ">". $row->pipe_idx ."</option>\n";
      }

      return $string;

   } // smarty_pipe_select_list()

   public function smarty_target_select_list($params, &$smarty)
   {
      global $db;

      if(!array_key_exists('target_idx', $params)) {
         $this->trigger_error("smarty_target_select_list: missing 'target_idx' parameter", E_USER_WARNING);
         $repeat = false;
         return;
      }

      $result = $db->db_query("
         SELECT
            target_idx,
            target_name
         FROM
            ". MYSQL_PREFIX ."targets
         ORDER BY
            target_name
      ");

      while($row = $result->fetchRow()) {
         $string.= "<option value=\"". $row->target_idx ."\" ";
         if($row->target_idx == $params['target_idx']) {
            $string.= " selected=\"selected\"";
         }
         $string.= ">". $row->target_name ."</option>\n";
      }

      return $string;

   } // smarty_target_select_list()

   public function smarty_service_level_select_list($params, &$smarty)
   {
      global $ms, $db;

      if(!array_key_exists('sl_idx', $params)) {
         $this->trigger_error("smarty_service_level_select_list: missing 'sl_idx' parameter", E_USER_WARNING);
         $repeat = false;
         return;
      }

      $result = $db->db_query("
         SELECT
             *
         FROM
            ". MYSQL_PREFIX ."service_levels
         ORDER BY
            sl_name ASC
      ");

      while($row = $result->fetchRow()) {
         $string.= "<option value=\"". $row->sl_idx ."\"";
         if($row->sl_idx == $params['sl_idx']) {
            $string.= " selected=\"selected\"";
         }
         switch($ms->getOption("classifier")) {
            case 'HTB':
               $string.= ">". $row->sl_name ." (in: ". $row->sl_htb_bw_in_rate ."kbit/s, out: ". $row->sl_htb_bw_out_rate ."kbit/s)</option>\n";
               break;
            case 'HFSC':
               $string.= ">". $row->sl_name ." (in: ". $row->sl_hfsc_in_dmax ."ms,". $row->sl_hfsc_in_rate ."kbit/s, out: ". $row->sl_hfsc_out_dmax ."ms,". $row->sl_hfsc_bw_out_rate ."kbit/s)</option>\n";
               break;
            case 'CBQ':
               $string.= ">". $row->sl_name ." (in: ". $row->sl_cbq_in_rate ."kbit/s, out: ". $row->sl_cbq_out_rate ."kbit/s)</option>\n";
               break;
         }

      }

      return $string;

   } // smarty_service_level_select_list()

   public function smarty_network_path_select_list($params, &$smarty)
   {
      global $db;

      if(!array_key_exists('np_idx', $params)) {
         $this->trigger_error("smarty_network_path_select_list: missing 'np_idx' parameter", E_USER_WARNING);
         $repeat = false;
         return;
      }

      $result = $db->db_query("
         SELECT
            *
         FROM
            ". MYSQL_PREFIX ."network_paths
         ORDER BY
            netpath_name ASC
      ");

      while($row = $result->fetchRow()) {
         $string.= "<option value=\"". $row->netpath_idx ."\"";
         if($row->netpath_idx == $params['np_idx']) {
            $string.= " selected=\"selected\"";
         }
         $string.= ">". $row->netpath_name ."</option>\n";
      }

      return $string;

   } // smarty_network_path_select_list()

}

?>
