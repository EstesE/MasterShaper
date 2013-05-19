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

      if(isset($_GET['clear']) && $_GET['clear'] == 'finished')
         $this->clear_finished_tasks();

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
	
      while($task = $res_tasks->fetch()) {
         $this->avail_tasks[$cnt_tasks] = $task->task_idx;
         $this->tasks[$task->task_idx] = $task;
         $cnt_tasks++;
      }

      $tmpl->registerPlugin("block", "task_list", array(&$this, "smarty_task_list"));

      return $tmpl->fetch("tasklist.tpl");
   
   } // showList() 

   /**
    * template function which will be called from the task listing template
    */
   public function smarty_task_list($params, $content, &$smarty, &$repeat)
   {
      global $ms;

      $index = $smarty->getTemplateVars('smarty.IB.task_list.index');
      if(!$index) {
         $index = 0;
      }

      if($index < count($this->avail_tasks)) {

        $task_idx = $this->avail_tasks[$index];
        $task =  $this->tasks[$task_idx];

         $smarty->assign('task_idx', $task_idx);
         $smarty->assign('task_job', $task->task_job);
         $smarty->assign('task_submit_time', strftime("%Y-%m-%d %H:%M:%S", $task->task_submit_time));
         if($task->task_run_time == -1)
            $smarty->assign('task_run_time', 'asap');
         else
            $smarty->assign('task_run_time', strftime("%Y-%m-%d %H:%M:%S", $task->task_run_time));
         switch($task->task_state) {
            case 'N': $smarty->assign('task_state', 'new'); break;
            case 'R': $smarty->assign('task_state', 'running'); break;
            case 'F': $smarty->assign('task_state', 'finish'); break;
            case 'E': $smarty->assign('task_state', 'error'); break;
            default:  $smarty->assign('task_state', 'unknown'); break;
         }
         $index++;
         $smarty->assign('smarty.IB.task_list.index', $index);
         $repeat = true;
      }
      else {
         $repeat =  false;
      }

      return $content;

   } // smarty_task_list()

   /**
    * clear finish tasks
    *
    * this function removes all finished tasks from tasklist
    */
   private function clear_finished_tasks()
   {
      global $ms, $db;

      $db->db_query("
         DELETE FROM
            ". MYSQL_PREFIX ."tasks
         WHERE
            task_host_idx LIKE '". $ms->get_current_host_profile() ."'
         AND
            task_state LIKE 'F'
      ");

   } // clear_finished_tasks()

} // class Page_Host_Tasklist

$obj = new Page_Host_Tasklist;
$obj->handler();

?>
