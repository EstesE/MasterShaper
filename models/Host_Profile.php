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

class Host_Profile extends MsObject {

   /**
    * Host_Profile constructor
    *
    * Initialize the Host_Profile class
    */
   public function __construct($id = null)
   {
      parent::__construct($id, Array(
         'table_name' => 'host_profiles',
         'col_name' => 'host',
         'fields' => Array(
            'host_idx' => 'integer',
            'host_name' => 'text',
            'host_active' => 'text',
            'host_heartbeat' => 'timestamp',
         ),
      ));

      if(!isset($id) || empty($id)) {
         parent::init_fields(Array(
            'host_active' => 'Y',
         ));
      }

   } // __construct()

   public function pre_delete()
   {
      global $db, $ms;

      if($this->id == 1) {
         $ms->throwError('You can not delete the default host profile!');
      }

   } // pre_delete()

} // class Host_Profile

?>
