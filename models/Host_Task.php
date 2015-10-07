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

class Host_Task extends MsObject {

   /**
    * Host_Task constructor
    *
    * Initialize the Host_Task class
    */
   public function __construct($id = null)
   {
      parent::__construct($id, Array(
         'table_name' => 'tasks',
         'col_name' => 'task',
         'fields' => Array(
            'task_idx' => 'integer',
            'task_job' => 'text',
            'task_submit_time' => 'timestamp',
            'task_run_time' => 'timestamp',
            'task_host_idx' => 'integer',
            'task_state' => 'text',
         ),
      ));

   } // __construct()

   public function pre_delete()
   {
      global $db, $ms;

      if($this->id == 1) {
         $ms->throwError('You can not delete the default host profile!');
      }

   } // pre_delete()

} // class Host_Task

?>
