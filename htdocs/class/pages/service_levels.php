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

class Page_Service_Levels extends MASTERSHAPER_PAGE {

   /**
    * Page_Service_Levels constructor
    *
    * Initialize the Page_Service_Levels class
    */
   public function __construct()
   {
      $this->rights = 'user_manage_servicelevels';

   } // __construct()

   /**
    * display all service levels
    */
   public function showList()
   {
      global $db, $tmpl;

      $this->avail_service_levels = Array();
      $this->service_levels = Array();

      $res_sl = $db->db_query("
         SELECT
            *
         FROM
            ". MYSQL_PREFIX ."service_levels
         ORDER BY
            sl_name ASC
      ");

      $cnt_sl = 0; 

      while($sl = $res_sl->fetchrow()) {
         $this->avail_service_levels[$cnt_sl] = $sl->sl_idx;
         $this->service_levels[$sl->sl_idx] = $sl;
         $cnt_sl++;
      }

      $tmpl->register_block("service_level_list", array(&$this, "smarty_sl_list"));
      return $tmpl->fetch("service_levels_list.tpl");

   } // showList() 

   /**
    * display interface to create or edit service levels
    */
   public function showEdit()
   {
      if($this->is_storing())
         $this->store();

      global $ms, $db, $tmpl, $page;

      if($page->id != 0)
         $sl = new Service_Level($page->id);
      else
         $sl = new Service_Level;

      $tmpl->assign('sl', $sl);

      if(!isset($_GET['classifier']))
         $tmpl->assign('classifier', $ms->getOption("classifier"));
      else
         $tmpl->assign('classifier', $_GET['classifier']);

      if(!isset($_GET['qdiscmode'])) {
         if(isset($sl))
            $tmpl->assign('qdiscmode', $sl->sl_qdisc);
      }
      else
         $tmpl->assign('qdiscmode', $_GET['qdiscmode']);

      return $tmpl->fetch("service_levels_edit.tpl");

   } // showEdit()

   /**
    * template function which will be called from the target listing template
    */
   public function smarty_sl_list($params, $content, &$smarty, &$repeat)
   {
      global $ms, $tmpl;

      $index = $smarty->get_template_vars('smarty.IB.sl_list.index');
      if(!$index) {
         $index = 0;
      }

      if($index < count($this->avail_service_levels)) {

         $sl_idx = $this->avail_service_levels[$index];
         $sl =  $this->service_levels[$sl_idx];

         $tmpl->assign('classifier', $ms->getOption("classifier"));
         $tmpl->assign('sl_idx', $sl_idx);
         $tmpl->assign('sl_name', $sl->sl_name);
         $tmpl->assign('sl_htb_bw_in_rate', $sl->sl_htb_bw_in_rate);
         $tmpl->assign('sl_htb_bw_out_rate', $sl->sl_htb_bw_out_rate);
         $tmpl->assign('sl_htb_priority', $ms->getPriorityName($sl->sl_htb_priority));
         $tmpl->assign('sl_hfsc_in_dmax', $sl->sl_hfsc_in_dmax);
         $tmpl->assign('sl_hfsc_in_rate', $sl->sl_hfsc_in_rate);
         $tmpl->assign('sl_hfsc_out_dmax', $sl->sl_hfsc_out_dmax);
         $tmpl->assign('sl_hfsc_out_rate', $sl->sl_hfsc_out_rate);
         $tmpl->assign('sl_cbq_in_rate', $sl->sl_cbq_in_rate);
         $tmpl->assign('sl_cbq_out_rate', $sl->sl_cbq_out_rate);
         $tmpl->assign('sl_cbq_in_priority', $ms->getPriorityName($sl->sl_cbq_in_priority));
         $tmpl->assign('sl_cbq_out_priority', $ms->getPriorityName($sl->sl_cbq_out_priority));

         $index++;
         $tmpl->assign('smarty.IB.sl_list.index', $index);
         $repeat = true;
      }
      else {
         $repeat =  false;
      }

      return $content;

   } // smarty_sl_list

   /** 
    * save service level
    */
   public function store()
   {
      global $ms, $db;

      isset($_POST['new']) && $_POST['new'] == 1 ? $new = 1 : $new = NULL;

      /* load service level */
      if(isset($new))
         $sl = new Service_Level;
      else
         $sl = new Service_Level($_POST['sl_idx']);

      if(!isset($_POST['sl_name']) || $_POST['sl_name'] == "") {
         $ms->throwError(_("Please enter a service level name!"));
      }

      if(isset($new) && $ms->check_object_exists('service_level', $_POST['sl_name'])) {
         $ms->throwError(_("A service level with that name already exists!"));
      }

      if(!isset($new) && $sl->sl_name != $_POST['sl_name'] && $ms->check_object_exists('service_level', $_POST['sl_name'])) {
         $ms->throwError(_("A service level with that name already exists!"));
      }

      $is_numeric = 1;

      switch($_POST['classifiermode']) {
         case 'HTB':
            if($_POST['sl_htb_priority'] == 0 && $_POST['sl_htb_bw_in_rate'] == "" && $_POST['sl_htb_bw_out_rate'] == "") {
               $ms->throwError(_("A service level which ignores priority AND not specified inbound or outbound rate is not possible!"));
            }
            if($_POST['sl_htb_bw_in_rate'] != "" && !is_numeric($_POST['sl_htb_bw_in_rate']))
               $is_numeric = 0;
            if($_POST['sl_htb_bw_out_rate'] != "" && !is_numeric($_POST['sl_htb_bw_out_rate']))
               $is_numeric = 0;
            if($_POST['sl_htb_bw_in_ceil'] != "" && !is_numeric($_POST['sl_htb_bw_in_ceil']))
               $is_numeric = 0;
            if($_POST['sl_htb_bw_in_burst'] != "" && !is_numeric($_POST['sl_htb_bw_in_burst']))
               $is_numeric = 0;
            if($_POST['sl_htb_bw_out_ceil'] != "" && !is_numeric($_POST['sl_htb_bw_out_ceil']))
               $is_numeric = 0;
            if($_POST['sl_htb_bw_out_burst'] != "" && !is_numeric($_POST['sl_htb_bw_out_burst']))
               $is_numeric = 0;
            break;

         case 'HFSC':
            /* If umax is specifed, also umax is necessary */
            if(($_POST['sl_hfsc_in_umax'] != "" && $_POST['sl_hfsc_in_dmax'] == "") ||
               ($_POST['sl_hfsc_out_umax'] != "" && $_POST['sl_hfsc_out_dmax'] == "")) {
               $ms->throwError(_("Please enter a \"Max-Delay\" value if you have defined a \"Work-Unit\" value!"));
            }
            if($_POST['sl_hfsc_in_umax'] != "" && !is_numeric($_POST['sl_hfsc_in_umax']))
               $is_numeric = 0;
            if($_POST['sl_hfsc_in_dmax'] != "" && !is_numeric($_POST['sl_hfsc_in_dmax']))
               $is_numeric = 0;
            if($_POST['sl_hfsc_in_rate'] != "" && !is_numeric($_POST['sl_hfsc_in_rate']))
               $is_numeric = 0;
            if($_POST['sl_hfsc_in_ulrate'] != "" && !is_numeric($_POST['sl_hfsc_in_ulrate']))
               $is_numeric = 0;
            if($_POST['sl_hfsc_out_umax'] != "" && !is_numeric($_POST['sl_hfsc_out_umax']))
               $is_numeric = 0;
            if($_POST['sl_hfsc_out_dmax'] != "" && !is_numeric($_POST['sl_hfsc_out_dmax']))
               $is_numeric = 0;
            if($_POST['sl_hfsc_out_rate'] != "" && !is_numeric($_POST['sl_hfsc_out_rate']))
               $is_numeric = 0;
            if($_POST['sl_hfsc_out_ulrate'] != "" && !is_numeric($_POST['sl_hfsc_out_ulrate']))
               $is_numeric = 0;
            break;
         case 'CBQ':
            if($_POST['sl_cbq_in_rate'] == "" || $_POST['sl_cbq_out_rate'] == "") {
               $ms->throwError(_("Please enter a input and output rate!"));
            }
            if($_POST['sl_cbq_in_rate'] != "" && !is_numeric($_POST['sl_cbq_in_rate']))
               $is_numeric = 0;
            if($_POST['sl_cbq_out_rate'] != "" && !is_numeric($_POST['sl_cbq_out_rate']))
               $is_numeric = 0;
            break;
      }

      if(!$is_numeric) {
         $ms->throwError(_("Please enter only numerical values for bandwidth parameters!"));
      }

      $sl_data = $ms->filter_form_data($_POST, 'sl_');

      if(!$sl->update($sl_data))
         return false;

      if(!$sl->save())
         return false;

      return true;

   } // store()

} // class Page_Service_Levels

$obj = new Page_Service_Levels;
$obj->handler();

?>
