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

class Network_Interface extends MsObject {

   /**
    * Network_Interface constructor
    *
    * Initialize the Network_Interface class
    */
   public function __construct($id = null)
   {
      parent::__construct($id, Array(
         'table_name' => 'interfaces',
         'col_name' => 'if',
         'fields' => Array(
            'if_idx' => 'integer',
            'if_name' => 'text',
            'if_speed' => 'text',
            'if_fallback_idx' => 'integer',
            'if_ifb' => 'text',
            'if_active' => 'text',
            'if_host_idx' => 'integer',
         ),
      ));

      if(!isset($id) || empty($id)) {
         parent::init_fields(Array(
            'if_active' => 'Y',
            'if_fallback_idx' => 0,
         ));
      }

   } // __construct()

   public function pre_save()
   {
      global $ms;

      $this->if_host_idx = $ms->get_current_host_profile();

   } // pre_save()

} // class Network_Interface

?>
