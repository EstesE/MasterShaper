<?php

/**
 *
 * This file is part of MasterShaper.

 * MasterShaper, a web application to handle Linux's traffic shaping
 * Copyright (C) 2015 Andreas Unterkircher <unki@netshadow.net>

 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.

 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.

 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

class Page_Network_Paths extends MASTERSHAPER_PAGE {

   /**
    * Page_Network_Paths constructor
    *
    * Initialize the Page_Network_Paths class
    */
   public function __construct()
   {
      $this->rights = 'user_manage_options';

   } // __construct()

   /**
    * list all netpaths
    */
   public function showList()
   {
      global $ms, $db, $tmpl;

      $this->avail_netpaths = Array();
      $this->netpaths = Array();

      $res_netpaths = $db->db_query("
         SELECT
            *
         FROM
            ". MYSQL_PREFIX ."network_paths
         WHERE
            netpath_host_idx LIKE '". $ms->get_current_host_profile() ."'
         ORDER BY
            netpath_name ASC
      ");

      $cnt_netpaths = 0;
	
      while($np = $res_netpaths->fetch()) {
         $this->avail_netpaths[$cnt_netpaths] = $np->netpath_idx;
         $this->netpaths[$np->netpath_idx] = $np;
         $cnt_netpaths++;
      }

      $tmpl->registerPlugin("block", "netpath_list", array(&$this, "smarty_netpath_list"));

      return $tmpl->fetch("network_paths_list.tpl");
   
   } // showList() 

   /**
    * interface for handling
    */
   public function showEdit()
   {
      if($this->is_storing())
         $this->store();

      global $ms, $db, $tmpl, $page;

      $this->avail_chains = Array();
      $this->chains = Array();

      if(isset($page->id) && $page->id != 0) {
         $np = new Network_Path($page->id);
         $tmpl->assign('is_new', false);
      }
      else {
         $np = new Network_Path;
         $tmpl->assign('is_new', true);
         $page->id = NULL;
      }

      $sth = $db->db_prepare("
         SELECT DISTINCT
            c.chain_idx,
            c.chain_name,
            c.chain_active,
            c.chain_position IS NULL as pos_null
         FROM
            ". MYSQL_PREFIX ."chains c
         WHERE
            c.chain_netpath_idx LIKE ?
         AND
            c.chain_host_idx LIKE ?
         ORDER BY
            pos_null DESC,
            chain_position ASC
      ");

      $db->db_execute($sth, array(
         $page->id,
         $ms->get_current_host_profile(),
      ));

      $cnt_chains = 0;

      while($chain = $sth->fetch()) {
         $this->avail_chains[$cnt_chains] = $chain->chain_idx;
         $this->chains[$chain->chain_idx] = $chain;
         $cnt_chains++;
      }

      $db->db_sth_free($sth);

      $tmpl->assign('np', $np);
      $tmpl->registerPlugin("function", "if_select_list", array(&$this, "smarty_if_select_list"), false);

      $tmpl->registerPlugin("block", "chain_list", array(&$this, "smarty_chain_list"), false);

      return $tmpl->fetch("network_paths_edit.tpl");

   } // showEdit()

   /**
    * template function which will be called from the netpath listing template
    */
   public function smarty_netpath_list($params, $content, &$smarty, &$repeat)
   {
      global $ms;

      $index = $smarty->getTemplateVars('smarty.IB.netpath_list.index');
      if(!$index) {
         $index = 0;
      }

      if($index < count($this->avail_netpaths)) {

        $netpath_idx = $this->avail_netpaths[$index];
        $netpath =  $this->netpaths[$netpath_idx];

         $smarty->assign('netpath_idx', $netpath_idx);
         $smarty->assign('netpath_name', $netpath->netpath_name);
         $smarty->assign('netpath_active', $netpath->netpath_active);
         $smarty->assign('netpath_if1_idx', $netpath->netpath_if1);
         $smarty->assign('netpath_if1_name', $ms->getInterfaceName($netpath->netpath_if1));
         $smarty->assign('netpath_if1_inside_gre', $netpath->netpath_if1_inside_gre);
         $smarty->assign('netpath_if2_idx', $netpath->netpath_if2);
         $smarty->assign('netpath_if2_name', $ms->getInterfaceName($netpath->netpath_if2));
         $smarty->assign('netpath_if2_inside_gre', $netpath->netpath_if2_inside_gre);

         $index++;
         $smarty->assign('smarty.IB.netpath_list.index', $index);
         $repeat = true;
      }
      else {
         $repeat =  false;
      }

      return $content;

   } // smarty_netpath_list()
   
   /**
    * handle updates
    */
   public function store()
   {
      global $ms, $db, $rewriter;

      isset($_POST['new']) && $_POST['new'] == 1 ? $new = 1 : $new = NULL;

      /* load network path */
      if(isset($new))
         $np = new Network_Path;
      else
         $np = new Network_Path($_POST['netpath_idx']);

      if(!isset($_POST['netpath_name']) || $_POST['netpath_name'] == "") {
         $ms->throwError(_("Please specify a network path name!"));
      }
      if(isset($new) && $ms->check_object_exists('netpath', $_POST['netpath_name'])) {
         $ms->throwError(_("A network path with that name already exists!"));
      }
      if(!isset($new) && $np->netpath_name != $_POST['netpath_name'] &&
         $ms->check_object_exists('netpath', $_POST['netpath_name'])) {
         $ms->throwError(_("A network path with that name already exists!"));
      }
      if($_POST['netpath_if1'] == $_POST['netpath_if2']) {
         $ms->throwError(_("An interface within a network path can not be used twice! Please select different interfaces"));
      }

      if(!isset($_POST['netpath_if1_inside_gre']) || empty($_POST['netpath_if1_inside_gre']))
         $_POST['netpath_if1_inside_gre'] = 'N';

      if(!isset($_POST['netpath_if2_inside_gre']) || empty($_POST['netpath_if2_inside_gre']))
         $_POST['netpath_if2_inside_gre'] = 'N';

      $np_data = $ms->filter_form_data($_POST, 'netpath_');

      if(!$np->update($np_data))
         return false;

      if(!$np->save())
         return false;

      if(isset($_POST['add_another']) && $_POST['add_another'] == 'Y')
         return true;

      $ms->set_header('Location',  $rewriter->get_page_url('Network Paths List'));
      return true;

   } // store()

   /**
    * this function will return a select list full of interfaces
    */
   public function smarty_if_select_list($params, &$smarty)
   {
      global $ms, $db;

      if(!array_key_exists('if_idx', $params)) {
         $smarty->trigger_error("getSLList: missing 'if_idx' parameter", E_USER_WARNING);
         $repeat = false;
         return;
      }

      $result = $db->db_query("
         SELECT
            *
         FROM
            ". MYSQL_PREFIX ."interfaces
         WHERE
            if_host_idx LIKE '". $ms->get_current_host_profile() ."'
         ORDER BY
            if_name ASC
      ");

      $string = "";
      while($row = $result->fetch()) {
         $string.= "<option value=\"". $row->if_idx ."\"";
         if($params['if_idx'] == $row->if_idx)
            $string.= " selected=\"selected\"";
         $string.= ">". $row->if_name ."</option>";
      }

      return $string;

   } // smarty_if_select_list()

   /**
    * template function which will be called from the network path editing template
    */
   public function smarty_chain_list($params, $content, &$smarty, &$repeat)
   {
      $index = $smarty->getTemplateVars('smarty.IB.chain_list.index');
      if(!$index) {
         $index = 0;
      }

      if($index < count($this->avail_chains)) {

         $chain_idx = $this->avail_chains[$index];
         $chain =  $this->chains[$chain_idx];

         $smarty->assign('chain', $chain);

         $index++;
         $smarty->assign('smarty.IB.chain_list.index', $index);
         $repeat = true;
      }
      else {
         $repeat =  false;
      }

      return $content;

   } // smarty_chain_list()

} // class Page_Network_Paths

$obj = new Page_Network_Paths;
$obj->handler();

?>
