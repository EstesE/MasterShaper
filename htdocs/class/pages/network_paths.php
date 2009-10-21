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

      if($page->id != 0)
         $np = new Network_Path($page->id);
      else
         $np = new Network_Path;

      $tmpl->assign('np', $np);
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
      global $ms, $db;

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
      if(!isset($new) && $np->netpath_name != $_POST['namebefore'] &&
         $ms->check_object_exists('netpath', $_POST['netpath_name'])) {
         $ms->throwError(_("A network path with that name already exists!"));
      }
      if($_POST['netpath_if1'] == $_POST['netpath_if2']) {
         $ms->throwError(_("A interface within a network path can not be used twice! Please select different interfaces"));
      }

      $np_data = $ms->filter_form_data($_POST, 'netpath_');

      if(!$np->update($np_data))
         return false;

      if(!$np->save())
         return false;

      return true;

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
