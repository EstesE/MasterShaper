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

class Page_Host_Tasklist extends MASTERSHAPER_PAGE {

   /**
    * Page_Host_Tasklist constructor
    *
    * Initialize the Page_Host_Tasklist class
    */
   public function __construct()
   {
      $this->rights = 'user_manage_options';

   } // __construct()

   /**
    * list all tasks
    */
   public function showList()
   {
      global $ms, $db, $tmpl;

      $this->avail_tasks = Array();
      $this->tasks = Array();

      $res_tasks = $db->db_query("
         SELECT
            *
         FROM
            ". MYSQL_PREFIX ."tasks
         WHERE
            task_host_idx LIKE '". $ms->get_current_host_profile() ."'
         ORDER BY
            task_submit_time DESC
      ");

      $cnt_tasks = 0;
	
      while($task = $res_tasks->fetchrow()) {
         $this->avail_tasks[$cnt_tasks] = $task->task_idx;
         $this->tasks[$task->task_idx] = $task;
         $cnt_tasks++;
      }

      $tmpl->register_block("task_list", array(&$this, "smarty_task_list"));

      return $tmpl->fetch("tasklist.tpl");
   
   } // showList() 

   /**
    * interface for handling
    */
   /*public function showEdit()
   {
      if($this->is_storing())
         $this->store();

      global $db, $tmpl, $page;

      $this->avail_chains = Array();
      $this->chains = Array();

      if($page->id != 0) {
         $hostprofile = new Host_Profile($page->id);
         $tmpl->assign('is_new', false);
      }
      else {
         $hostprofile = new Host_Profile;
         $tmpl->assign('is_new', true);
      }

      $tmpl->assign('host', $hostprofile);

      return $tmpl->fetch("host_profiles_edit.tpl");

   } // showEdit()*/

   /**
    * template function which will be called from the task listing template
    */
   public function smarty_task_list($params, $content, &$smarty, &$repeat)
   {
      global $tmpl, $ms;

      $index = $smarty->get_template_vars('smarty.IB.task_list.index');
      if(!$index) {
         $index = 0;
      }

      if($index < count($this->avail_tasks)) {

        $task_idx = $this->avail_tasks[$index];
        $task =  $this->tasks[$task_idx];

         $tmpl->assign('task_idx', $task_idx);
         $tmpl->assign('task_job', $task->task_job);
         $tmpl->assign('task_submit_time', strftime("%Y-%m-%d %H:%M:%S", $task->task_submit_time));
         if($task->task_run_time == -1)
            $tmpl->assign('task_run_time', 'asap');
         else
            $tmpl->assign('task_run_time', strftime("%Y-%m-%d %H:%M:%S", $task->task_run_time));
         switch($task->task_state) {
            case 'N': $tmpl->assign('task_state', 'new'); break;
            case 'R': $tmpl->assign('task_state', 'running'); break;
            case 'F': $tmpl->assign('task_state', 'finish'); break;
            case 'E': $tmpl->assign('task_state', 'error'); break;
            default:  $tmpl->assign('task_state', 'unknown'); break;
         }
         $index++;
         $tmpl->assign('smarty.IB.task_list.index', $index);
         $repeat = true;
      }
      else {
         $repeat =  false;
      }

      return $content;

   } // smarty_task_list()
   
   /**
    * handle updates
    */
   public function store()
   {
      global $ms, $db, $rewriter;

      isset($_POST['new']) && $_POST['new'] == 1 ? $new = 1 : $new = NULL;

      /* load task profile */
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

} // class Page_Host_Tasklist

$obj = new Page_Host_Tasklist;
$obj->handler();

?>
