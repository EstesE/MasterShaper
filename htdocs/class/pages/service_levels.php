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

      while($sl = $res_sl->fetch()) {
         $this->avail_service_levels[$cnt_sl] = $sl->sl_idx;
         $this->service_levels[$sl->sl_idx] = $sl;
         $cnt_sl++;
      }

      $tmpl->registerPlugin("block", "service_level_list", array(&$this, "smarty_sl_list"));
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

      if(isset($page->id) && $page->id != 0) {
         $sl = new Service_Level($page->id);
         $tmpl->assign('is_new', false);
      }
      else {
         $sl = new Service_Level;
         $tmpl->assign('is_new', true);
         $page->id = NULL;
      }

      $tmpl->assign('sl', $sl);

      if(isset($_GET['classifier']) && !empty($_GET['classifier']))
         $tmpl->assign('classifier', $_GET['classifier']);
      elseif($ms->getOption("classifier") != "unknown")
         $tmpl->assign('classifier', $ms->getOption("classifier"));
      else {
         /* if we still not know the classifier to use, assume HTB */
         $tmpl->assign('classifier', 'HTB');
      }

      if(!isset($_GET['qdiscmode'])) {
         if($page->id != 0)
            $tmpl->assign('qdiscmode', $sl->sl_qdisc);
         elseif($ms->getOption("qdisc") != "unknown")
            $tmpl->assign('qdiscmode', $ms->getOption("qdisc"));
         else {
            /* if we still not know the qdisc to use, assume SFQ */
            $tmpl->assign('qdiscmode', 'SFQ');
         }
      }
      else
         $tmpl->assign('qdiscmode', $_GET['qdiscmode']);

      /* get a list of objects that use this target */
      $sth = $db->db_prepare("
         (
            SELECT
               'pipe' as type,
               p.pipe_idx as idx,
               p.pipe_name as name
            FROM
               ". MYSQL_PREFIX ."pipes p
            INNER JOIN ". MYSQL_PREFIX ."assign_pipes_to_chains apc
               ON p.pipe_idx=apc.apc_pipe_idx
            WHERE
               apc.apc_sl_idx LIKE ?
            ORDER BY
               p.pipe_name
         )
         UNION
         (
            SELECT
               'chain' as type,
               c.chain_idx as idx,
               c.chain_name as name
            FROM
               ". MYSQL_PREFIX ."chains c
            WHERE
               c.chain_sl_idx LIKE ?
            ORDER BY
               c.chain_name
         )
         UNION
         (
            SELECT
               'pipe' as type,
               p.pipe_idx as idx,
               p.pipe_name as name
            FROM
               ". MYSQL_PREFIX ."pipes p
            WHERE
               p.pipe_sl_idx LIKE ?
            ORDER BY
               p.pipe_name
         )
         UNION
         (
            SELECT
               'interface' as type,
               iface.if_idx as idx,
               iface.if_name as name
            FROM
               ". MYSQL_PREFIX ."interfaces iface
            WHERE
               iface.if_fallback_idx LIKE ?
            ORDER BY
               iface.if_name
         )
      ");

      $db->db_execute($sth, array(
         $page->id,
         $page->id,
         $page->id,
         $page->id,
      ));

      $obj_used = array();
      if($sth->rowCount() > 0) {
         while($obj = $sth->fetch()) {
            array_push($obj_used, $obj);
         }
      }
      $tmpl->assign('obj_used', $obj_used);

      $db->db_sth_free($sth);

      return $tmpl->fetch("service_levels_edit.tpl");

   } // showEdit()

   /**
    * template function which will be called from the target listing template
    */
   public function smarty_sl_list($params, $content, &$smarty, &$repeat)
   {
      global $ms;

      $index = $smarty->getTemplateVars('smarty.IB.sl_list.index');
      if(!$index) {
         $index = 0;
      }

      if($index < count($this->avail_service_levels)) {

         $sl_idx = $this->avail_service_levels[$index];
         $sl =  $this->service_levels[$sl_idx];

         $smarty->assign('classifier', $ms->getOption("classifier"));
         $smarty->assign('sl_idx', $sl_idx);
         $smarty->assign('sl_name', $sl->sl_name);
         $smarty->assign('sl_htb_bw_in_rate', $sl->sl_htb_bw_in_rate);
         $smarty->assign('sl_htb_bw_out_rate', $sl->sl_htb_bw_out_rate);
         $smarty->assign('sl_htb_priority', $ms->getPriorityName($sl->sl_htb_priority));
         $smarty->assign('sl_hfsc_in_dmax', $sl->sl_hfsc_in_dmax);
         $smarty->assign('sl_hfsc_in_rate', $sl->sl_hfsc_in_rate);
         $smarty->assign('sl_hfsc_out_dmax', $sl->sl_hfsc_out_dmax);
         $smarty->assign('sl_hfsc_out_rate', $sl->sl_hfsc_out_rate);

         $index++;
         $smarty->assign('smarty.IB.sl_list.index', $index);
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
      global $ms, $db, $rewriter;

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

      if(!isset($_POST['classifiermode']) || !in_array($_POST['classifiermode'], Array('HTB', 'HFSC')))
         $_POST['classifiermode'] = 'HTB';

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
      }

      if(!$is_numeric) {
         $ms->throwError(_("Please enter only numerical values for bandwidth parameters!"));
      }

      $sl_data = $ms->filter_form_data($_POST, 'sl_');

      if(!$sl->update($sl_data))
         return false;

      if(!$sl->save())
         return false;

      if(isset($_POST['add_another']) && $_POST['add_another'] == 'Y')
         return true;

      $ms->set_header('Location', $rewriter->get_page_url('Service Levels List'));
      return true;

   } // store()

} // class Page_Service_Levels

$obj = new Page_Service_Levels;
$obj->handler();

?>
