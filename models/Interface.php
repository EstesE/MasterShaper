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
