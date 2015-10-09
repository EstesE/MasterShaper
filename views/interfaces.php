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

namespace MasterShaper\Views;

class InterfacesView extends DefaultView
{
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

      $res_interfaces = $db->query("
         SELECT
            *
         FROM
            TABLEPREFIXinterfaces
         WHERE
            if_host_idx LIKE '". $ms->get_current_host_profile() ."'
         ORDER BY
            if_name ASC
      ");
   
      $cnt_interfaces = 0;
	
      while($if = $res_interfaces->fetch()) {
         $this->avail_interfaces[$cnt_interfaces] = $if->if_idx;
         $this->interfaces[$if->if_idx] = $if;
         $cnt_interfaces++;
      }

      $tmpl->registerPlugin("block", "interface_list", array(&$this, "smarty_interface_list"));
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

      if(isset($page->id) && $page->id != 0) {
         $if = new Network_Interface($page->id);
         $tmpl->assign('is_new', false);
      }
      else {
         $if = new Network_Interface;
         $tmpl->assign('is_new', true);
         $page->id = NULL;
      }

      /* get a list of network paths that use this interface */
      $sth = $db->prepare("
         SELECT
            np.netpath_idx,
            np.netpath_name
         FROM
            TABLEPREFIXnetwork_paths np
         WHERE
            np.netpath_if1 LIKE ?
         OR
            np.netpath_if2 LIKE ?
         ORDER BY
            np.netpath_name ASC
      ");

      $db->execute($sth, array(
         $page->id,
         $page->id,
      ));

      if($sth->rowCount() > 0) {
         $np_use_if = array();
         while($np = $sth->fetch()) {
            $np_use_if[$np->netpath_idx] = $np->netpath_name;
         }
         $tmpl->assign('np_use_if', $np_use_if);
      }

      $db->db_sth_free($sth);

      $tmpl->assign('if', $if);
      return $tmpl->fetch("interfaces_edit.tpl");

   } // showEdit()

   /**
    * template function which will be called from the interface listing template
    */
   public function smarty_interface_list($params, $content, &$smarty, &$repeat)
   {
      global $ms;

      $index = $smarty->getTemplateVars('smarty.IB.if_list.index');
      if(!$index) {
         $index = 0;
      }

      if($index < count($this->avail_interfaces)) {

         $if_idx = $this->avail_interfaces[$index];
         $if =  $this->interfaces[$if_idx];

         $smarty->assign('if_idx', $if_idx);
         $smarty->assign('if_name', $if->if_name);
         $smarty->assign('if_speed', $if->if_speed);
         $smarty->assign('if_fallback_idx', $if->if_fallback_idx);
         if($if->if_fallback_idx != 0)
            $smarty->assign('if_fallback_name', $ms->getServiceLevelName($if->if_fallback_idx));
         $smarty->assign('if_active', $if->if_active);

         $index++;
         $smarty->assign('smarty.IB.if_list.index', $index);
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
         $ms->raiseError(_("Please specify a interface!"));
      }
      if(isset($new) && $ms->check_object_exists('interface', $_POST['if_name'])) {
         $ms->raiseError(_("A interface with that name already exists!"));
      }
      if(!isset($new) && $if->if_name != $_POST['if_name'] && 
         $ms->check_object_exists('interface', $_POST['if_name'])) {
         $ms->raiseError(_("A interface with that name already exists!"));
      }
      if(!isset($_POST['if_speed']) || $_POST['if_speed'] == "")
         $_POST['if_speed'] = 0;
      else
         $_POST['if_speed'] = strtoupper($_POST['if_speed']);

      if(!$ms->validateBandwidth($_POST['if_speed'])) {
         $ms->raiseError(_("Invalid bandwidth specified!"));
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
