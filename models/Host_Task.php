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
