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
