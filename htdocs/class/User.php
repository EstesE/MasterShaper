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

class User extends MsObject {

   /**
    * User constructor
    *
    * Initialize the User class
    */
   public function __construct($id = null)
   {
      parent::__construct($id, Array(
         'table_name' => 'users',
         'col_name' => 'user',
         'fields' => Array(
            'user_idx' => 'integer',
            'user_name' => 'text',
            'user_pass' => 'text',
            'user_manage_chains' => 'text',
            'user_manage_pipes' => 'text',
            'user_manage_filters' => 'text',
            'user_manage_ports' => 'text',
            'user_manage_protocols' => 'text',
            'user_manage_targets' => 'text',
            'user_manage_users' => 'text',
            'user_manage_options' => 'text',
            'user_manage_servicelevels' => 'text',
            'user_show_rules' => 'text',
            'user_load_rules' => 'text',
            'user_show_monitor' => 'text',
            'user_active' => 'text',
         ),
      ));

      if(!isset($id) || empty($id)) {
         $this->user_active = 'Y';
      }

   } // __construct()
  
} // class User

?>
