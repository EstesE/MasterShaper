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

class HostTaskListView extends DefaultView
{
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

        $this->avail_tasks = array();
        $this->tasks = array();

        if (isset($_GET['clear']) && $_GET['clear'] == 'finished') {
            $this->clearFinishedTask();
        }

        $res_tasks = $db->query("
                SELECT
                *
                FROM
                TABLEPREFIXtasks
                WHERE
                task_host_idx LIKE '". $ms->get_current_host_profile() ."'
                ORDER BY
                task_submit_time DESC
                ");

        $cnt_tasks = 0;

        while ($task = $res_tasks->fetch()) {
            $this->avail_tasks[$cnt_tasks] = $task->task_idx;
            $this->tasks[$task->task_idx] = $task;
            $cnt_tasks++;
        }

        $tmpl->registerPlugin("block", "task_list", array(&$this, "smartyTaskList"));

        return $tmpl->fetch("tasklist.tpl");

    } // showList()

    /**
     * template function which will be called from the task listing template
     */
    public function smartyTaskList($params, $content, &$smarty, &$repeat)
    {
        global $ms;

        $index = $smarty->getTemplateVars('smarty.IB.task_list.index');
        if (!$index) {
            $index = 0;
        }

        if ($index < count($this->avail_tasks)) {

            $task_idx = $this->avail_tasks[$index];
            $task =  $this->tasks[$task_idx];

            $smarty->assign('task_idx', $task_idx);
            $smarty->assign('task_job', $task->task_job);
            $smarty->assign('task_submit_time', strftime("%Y-%m-%d %H:%M:%S", $task->task_submit_time));
            if ($task->task_run_time == -1) {
                $smarty->assign('task_run_time', 'asap');
            } else {
                $smarty->assign('task_run_time', strftime("%Y-%m-%d %H:%M:%S", $task->task_run_time));
            }
            switch ($task->task_state) {
                case 'N':
                    $smarty->assign('task_state', 'new');
                    break;
                case 'R':
                    $smarty->assign('task_state', 'running');
                    break;
                case 'F':
                    $smarty->assign('task_state', 'finish');
                    break;
                case 'E':
                    $smarty->assign('task_state', 'error');
                    break;
                default:
                    $smarty->assign('task_state', 'unknown');
                    break;
            }
            $index++;
            $smarty->assign('smarty.IB.task_list.index', $index);
            $repeat = true;
        } else {
            $repeat =  false;
        }

        return $content;

    } // smartyTaskList()

    /**
     * clear finish tasks
     *
     * this function removes all finished tasks from tasklist
     */
    private function clearFinishedTask()
    {
        global $ms, $db;

        $db->query("
                DELETE FROM
                TABLEPREFIXtasks
                WHERE
                task_host_idx LIKE '". $ms->get_current_host_profile() ."'
                AND
                task_state LIKE 'F'
                ");

    } // clearFinishedTask()
} // class Page_Host_Tasklist

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
