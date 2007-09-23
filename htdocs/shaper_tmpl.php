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

require 'smarty/libs/Smarty.class.php';

class MASTERSHAPER_TMPL extends Smarty {

   var $parent;

   public function __construct($parent)
   {
      $this->Smarty();
      $this->template_dir = BASE_PATH .'/templates';
      $this->compile_dir  = BASE_PATH .'/templates_c';
      $this->config_dir   = BASE_PATH .'/smarty_config';
      $this->cache_dir    = BASE_PATH .'/smarty_cache';

      $this->assign('icon_chains', 'icons/flag_blue.gif');
      $this->assign('icon_options', 'icons/options.gif');
      $this->assign('icon_pipes', 'icons/flag_pink.gif');
      $this->assign('icon_ports', 'icons/flag_orange.gif');
      $this->assign('icon_protocols', 'icons/flag_red.gif');
      $this->assign('icon_servicelevels', 'icons/flag_yellow.gif');
      $this->assign('icon_filters', 'icons/flag_green.gif');
      $this->assign('icon_targets', 'icons/flag_purple.gif');
      $this->assign('icon_delete', 'icons/delete.gif');
      $this->assign('icon_active', 'icons/active.gif');
      $this->assign('icon_inactive', 'icons/inactive.gif');
      $this->assign('icon_arrow_left', 'icons/arrow_left.gif');
      $this->assign('icon_chains_arrow_up', 'icons/ms_chains_arrow_up_14.gif');
      $this->assign('icon_chains_arrow_down', 'icons/ms_chains_arrow_down_14.gif');
      $this->assign('icon_pipes_arrow_up', 'icons/ms_pipes_arrow_up_14.gif');
      $this->assign('icon_pipes_arrow_down', 'icons/ms_pipes_arrow_down_14.gif');
      $this->assign('icon_users', 'icons/ms_users_14.gif');
      $this->assign('icon_about', 'icons/home.gif');
      $this->assign('icon_home', 'icons/home.gif');
      $this->assign('icon_new', 'icons/page_white.gif');
      $this->assign('icon_monitor', 'icons/chart_pie.gif');
      $this->assign('icon_shaper_start', 'icons/enable.gif');
      $this->assign('icon_shaper_stop', 'icons/disable.gif');
      $this->assign('icon_bandwidth', 'icons/bandwidth.gif');
      $this->assign('icon_update', 'icons/update.gif');
      $this->assign('icon_interfaces', 'icons/network_card.gif');
      $this->assign('icon_treeend', 'icons/tree_end.gif');

      $this->register_function("start_table", array(&$this, "smarty_startTable"), false); 

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

}

?>
