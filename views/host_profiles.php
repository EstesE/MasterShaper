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

class Page_Host_Profiles extends MASTERSHAPER_PAGE {

   /**
    * Page_Host_Profiles constructor
    *
    * Initialize the Page_Host_Profiles class
    */
   public function __construct()
   {
      $this->rights = 'user_manage_options';

   } // __construct()

   /**
    * list all hosts
    */
   public function showList()
   {
      global $db, $tmpl;

      $this->avail_hosts = Array();
      $this->hosts = Array();

      $res_hosts = $db->db_query("
         SELECT
            *
         FROM
            ". MYSQL_PREFIX ."host_profiles
         ORDER BY
            host_name ASC
      ");

      $cnt_hosts = 0;
	
      while($hostprofile = $res_hosts->fetch()) {
         $this->avail_hosts[$cnt_hosts] = $hostprofile->host_idx;
         $this->hosts[$hostprofile->host_idx] = $hostprofile;
         $cnt_hosts++;
      }

      $tmpl->registerPlugin("block", "host_list", array(&$this, "smarty_host_list"));

      return $tmpl->fetch("host_profiles_list.tpl");
   
   } // showList() 

   /**
    * interface for handling
    */
   public function showEdit()
   {
      if($this->is_storing())
         $this->store();

      global $db, $tmpl, $page;

      $this->avail_chains = Array();
      $this->chains = Array();

      if(isset($page->id) && $page->id != 0) {
         $hostprofile = new Host_Profile($page->id);
         $tmpl->assign('is_new', false);
      }
      else {
         $hostprofile = new Host_Profile;
         $tmpl->assign('is_new', true);
         $page->id = NULL;
      }

      $tmpl->assign('host', $hostprofile);

      return $tmpl->fetch("host_profiles_edit.tpl");

   } // showEdit()

   /**
    * template function which will be called from the host listing template
    */
   public function smarty_host_list($params, $content, &$smarty, &$repeat)
   {
      global $ms;

      $index = $smarty->getTemplateVars('smarty.IB.host_list.index');
      if(!$index) {
         $index = 0;
      }

      if($index < count($this->avail_hosts)) {

        $host_idx = $this->avail_hosts[$index];
        $host =  $this->hosts[$host_idx];

         $smarty->assign('host_idx', $host_idx);
         $smarty->assign('host_name', $host->host_name);
         $smarty->assign('host_active', $host->host_active);

         $index++;
         $smarty->assign('smarty.IB.host_list.index', $index);
         $repeat = true;
      }
      else {
         $repeat =  false;
      }

      return $content;

   } // smarty_host_list()
   
   /**
    * handle updates
    */
   public function store()
   {
      global $ms, $db, $rewriter;

      isset($_POST['new']) && $_POST['new'] == 1 ? $new = 1 : $new = NULL;

      /* load host profile */
      if(isset($new))
         $hostprofile = new Host_Profile;
      else
         $hostprofile = new Host_Profile($_POST['host_idx']);

      if(!isset($_POST['host_name']) || $_POST['host_name'] == "") {
         $ms->throwError(_("Please specify a host profile name!"));
      }
      if(isset($new) && $ms->check_object_exists('hostprofile', $_POST['host_name'])) {
         $ms->throwError(_("A host profile with that name already exists!"));
      }
      if(!isset($new) && $hostprofile->host_name != $_POST['host_name'] &&
         $ms->check_object_exists('hostprofile', $_POST['host_name'])) {
         $ms->throwError(_("A host profile with that name already exists!"));
      }

      $hostprofile_data = $ms->filter_form_data($_POST, 'host_');

      if(!$hostprofile->update($hostprofile_data))
         return false;

      if(!$hostprofile->save())
         return false;

      if(isset($_POST['add_another']) && $_POST['add_another'] == 'Y')
         return true;

      $ms->set_header('Location', $rewriter->get_page_url('Host Profiles List'));
      return true;

   } // store()

} // class Page_Host_Profiles

$obj = new Page_Host_Profiles;
$obj->handler();

?>
