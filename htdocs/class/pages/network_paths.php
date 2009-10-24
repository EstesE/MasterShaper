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
      global $db, $tmpl;

      $this->avail_netpaths = Array();
      $this->netpaths = Array();

      $res_netpaths = $db->db_query("
         SELECT *
         FROM ". MYSQL_PREFIX ."network_paths
         ORDER BY netpath_name ASC
      ");

      $cnt_netpaths = 0;
	
      while($np = $res_netpaths->fetchrow()) {
         $this->avail_netpaths[$cnt_netpaths] = $np->netpath_idx;
         $this->netpaths[$np->netpath_idx] = $np;
         $cnt_netpaths++;
      }

      $tmpl->register_block("netpath_list", array(&$this, "smarty_netpath_list"));
      return $tmpl->fetch("network_paths_list.tpl");
   
   } // showList() 

   /**
    * interface for handling
    */
   public function showEdit()
   {
      if($this->is_storing())
         $this->store();

      global $db, $tmpl, $page;

      if($page->id != 0) {
         $np = $db->db_fetchSingleRow("
            SELECT *
            FROM ". MYSQL_PREFIX ."network_paths
            WHERE
               netpath_idx='". $page->id ."'
         ");

         $tmpl->assign('netpath_idx', $page->id);
         $tmpl->assign('netpath_name', $np->netpath_name);
         $tmpl->assign('netpath_if1', $np->netpath_if1);
         $tmpl->assign('netpath_if1_inside_gre', $np->netpath_if1_inside_gre);
         $tmpl->assign('netpath_if2', $np->netpath_if2);
         $tmpl->assign('netpath_if2_inside_gre', $np->netpath_if2_inside_gre);
         $tmpl->assign('netpath_imq', $np->netpath_imq);
         $tmpl->assign('netpath_active', $np->netpath_active);
    
      }

      $tmpl->register_function("if_select_list", array(&$this, "smarty_if_select_list"), false);
      return $tmpl->fetch("network_paths_edit.tpl");

   } // showEdit()

   /**
    * template function which will be called from the netpath listing template
    */
   public function smarty_netpath_list($params, $content, &$smarty, &$repeat)
   {
      global $tmpl, $ms;

      $index = $smarty->get_template_vars('smarty.IB.netpath_list.index');
      if(!$index) {
         $index = 0;
      }

      if($index < count($this->avail_netpaths)) {

        $netpath_idx = $this->avail_netpaths[$index];
        $netpath =  $this->netpaths[$netpath_idx];

         $tmpl->assign('netpath_idx', $netpath_idx);
         $tmpl->assign('netpath_name', $netpath->netpath_name);
         $tmpl->assign('netpath_active', $netpath->netpath_active);
         $tmpl->assign('netpath_if1', $ms->getInterfaceName($netpath->netpath_if1));
         $tmpl->assign('netpath_if1_inside_gre', $netpath->netpath_if1_inside_gre);
         $tmpl->assign('netpath_if2', $ms->getInterfaceName($netpath->netpath_if2));
         $tmpl->assign('netpath_if2_inside_gre', $netpath->netpath_if2_inside_gre);

         $index++;
         $tmpl->assign('smarty.IB.netpath_list.index', $index);
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
      global $db;

      isset($_POST['netpath_new']) && $_POST['netpath_new'] == 1 ? $new = 1 : $new = NULL;

      if(!isset($_POST['netpath_name']) || $_POST['netpath_name'] == "") {
         return _("Please specify a network path name!");
      }
      if(isset($new) && $this->checkNetworkPathExists($_POST['netpath_name'])) {
         return _("A network path with that name already exists!");
      }
      if(!isset($new) && $_POST['netpath_name'] != $_POST['namebefore'] &&
         $this->checkNetworkPathExists($_POST['netpath_name'])) {
         return _("A network path with that name already exists!");
      }
      if($_POST['netpath_if1'] == $_POST['netpath_if2']) {
         return _("A interface within a network path can not be used twice! Please select different interfaces");
      }

      if(isset($new)) {
         $max_pos = $db->db_fetchSingleRow("
            SELECT MAX(netpath_position) as pos
            FROM ". MYSQL_PREFIX ."network_paths
         ");
         $db->db_query("
            INSERT INTO ". MYSQL_PREFIX ."network_paths (
               netpath_name, netpath_if1, netpath_if1_inside_gre,
               netpath_if2, netpath_if2_inside_gre, netpath_position,
               netpath_imq, netpath_active
            ) VALUES (
               '". $_POST['netpath_name'] ."',
               '". $_POST['netpath_if1'] ."',
               '". $_POST['netpath_if1_inside_gre'] ."',
               '". $_POST['netpath_if2'] ."',
               '". $_POST['netpath_if2_inside_gre'] ."',
               '". ($max_pos->pos+1) ."',
               '". $_POST['netpath_imq'] ."',
               '". $_POST['netpath_active'] ."'
            )
         ");
      }
      else {
         $db->db_query("
            UPDATE ". MYSQL_PREFIX ."network_paths
            SET
               netpath_name='". $_POST['netpath_name'] ."',
               netpath_if1='". $_POST['netpath_if1'] ."',
               netpath_if1_inside_gre='". $_POST['netpath_if1_inside_gre'] ."',
               netpath_if2='". $_POST['netpath_if2'] ."',
               netpath_if2_inside_gre='". $_POST['netpath_if2_inside_gre'] ."',
               netpath_imq='". $_POST['netpath_imq'] ."',
               netpath_active='". $_POST['netpath_active'] ."'
            WHERE
               netpath_idx='". $_POST['netpath_idx'] ."'");
      }
		  
      return "ok";

   } // store()

   /**
    * this function will return a select list full of interfaces
    */
   public function smarty_if_select_list($params, &$smarty)
   {
      global $db;

      if(!array_key_exists('if_idx', $params)) {
         $smarty->trigger_error("getSLList: missing 'if_idx' parameter", E_USER_WARNING);
         $repeat = false;
         return;
      }

      $result = $db->db_query("
         SELECT *
         FROM ". MYSQL_PREFIX ."interfaces
         ORDER BY if_name ASC
      ");

      while($row = $result->fetchRow()) {
         $string.= "<option value=\"". $row->if_idx ."\"";
         if($params['if_idx'] == $row->if_idx)
            $string.= " selected=\"selected\"";
         $string.= ">". $row->if_name ."</option>";
      }

      return $string;

   } // smarty_if_select_list()

} // class Page_Network_Paths

$obj = new Page_Network_Paths;
$obj->handler();

?>
