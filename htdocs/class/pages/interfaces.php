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

class Page_Interfaces extends MASTERSHAPER_PAGE {

   /**
    * Page_Interfaces constructor
    *
    * Initialize the Page_Interfaces class
    */
   public function __construct()
   {
      $this->rights = 'user_manage_options';

   } // __construct()
  
   /**
    * display all interfaces
    */
   public function showList()
   {
      global $ms, $db, $tmpl;

      $this->avail_interfaces = Array();
      $this->interfaces = Array();

      $res_interfaces = $db->db_query("
         SELECT
            *
         FROM
            ". MYSQL_PREFIX ."interfaces
         WHERE
            if_host_idx LIKE '". $ms->get_current_host_profile() ."'
         ORDER BY
            if_name ASC
      ");
   
      $cnt_interfaces = 0;
	
      while($if = $res_interfaces->fetchrow()) {
         $this->avail_interfaces[$cnt_interfaces] = $if->if_idx;
         $this->interfaces[$if->if_idx] = $if;
         $cnt_interfaces++;
      }

      $tmpl->register_block("interface_list", array(&$this, "smarty_interface_list"));
      return $tmpl->fetch("interfaces_list.tpl");

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
         $if = new Network_Interface($page->id);
         $tmpl->assign('is_new', false);
      }
      else {
         $if = new Network_Interface;
         $tmpl->assign('is_new', true);
      }

      /* get a list of network paths that use this interface */
      $sth = $db->db_prepare("
         SELECT
            np.netpath_idx,
            np.netpath_name
         FROM
            ". MYSQL_PREFIX ."network_paths np
         WHERE
            np.netpath_if1 LIKE ?
         OR
            np.netpath_if2 LIKE ?
         ORDER BY
            np.netpath_name ASC
      ");

      $assigned_nps = $db->db_execute($sth, array(
         $page->id,
         $page->id,
      ));

      $db->db_sth_free($sth);

      if($assigned_nps->numRows() > 0) {
         $np_use_if = array();
         while($np = $assigned_nps->fetchRow()) {
            $np_use_if[$np->netpath_idx] = $np->netpath_name;
         }
         $tmpl->assign('np_use_if', $np_use_if);
      }


      $tmpl->assign('if', $if);
      return $tmpl->fetch("interfaces_edit.tpl");

   } // showEdit()

   /**
    * template function which will be called from the interface listing template
    */
   public function smarty_interface_list($params, $content, &$smarty, &$repeat)
   {
      global $ms, $tmpl;

      $index = $smarty->get_template_vars('smarty.IB.if_list.index');
      if(!$index) {
         $index = 0;
      }

      if($index < count($this->avail_interfaces)) {

        $if_idx = $this->avail_interfaces[$index];
        $if =  $this->interfaces[$if_idx];

         $tmpl->assign('if_idx', $if_idx);
         $tmpl->assign('if_name', $if->if_name);
         $tmpl->assign('if_speed', $if->if_speed);
         $tmpl->assign('if_fallback_idx', $if->if_fallback_idx);
         if($if->if_fallback_idx != 0)
            $tmpl->assign('if_fallback_name', $ms->getServiceLevelName($if->if_fallback_idx));
         $tmpl->assign('if_active', $if->if_active);

         $index++;
         $tmpl->assign('smarty.IB.if_list.index', $index);
         $repeat = true;
      }
      else {
         $repeat =  false;
      }

      return $content;

   } // smarty_interfaces_list()

   /**
    * handle updates
    */
   public function store()
   {
      global $ms, $db, $rewriter;

      isset($_POST['new']) && $_POST['new'] == 1 ? $new = 1 : $new = NULL;

      /* load interface */
      if(isset($new))
         $if = new Network_Interface;
      else
         $if = new Network_Interface($_POST['if_idx']);

      if(!isset($_POST['if_name']) || $_POST['if_name'] == "") {
         $ms->throwError(_("Please specify a interface!"));
      }
      if(isset($new) && $ms->check_object_exists('interface', $_POST['if_name'])) {
         $ms->throwError(_("A interface with that name already exists!"));
      }
      if(!isset($new) && $if->if_name != $_POST['if_name'] && 
         $ms->check_object_exists('interface', $_POST['if_name'])) {
         $ms->throwError(_("A interface with that name already exists!"));
      }
      if(!isset($_POST['if_speed']) || $_POST['if_speed'] == "")
         $_POST['if_speed'] = 0;
      else
         $_POST['if_speed'] = strtoupper($_POST['if_speed']);

      if(!$ms->validateBandwidth($_POST['if_speed'])) {
         $ms->throwError(_("Invalid bandwidth specified!"));
      }

      $if_data = $ms->filter_form_data($_POST, 'if_');

      if(!$if->update($if_data))
         return false;

      if(!$if->save())
         return false;

      if(isset($_POST['add_another']) && $_POST['add_another'] == 'Y')
         return true;

      $ms->set_header('Location', $rewriter->get_page_url('Interfaces List'));
      return true;
   
   } // store()

} // class Page_Interfaces

$obj = new Page_Interfaces;
$obj->handler();

?>
