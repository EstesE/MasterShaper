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

class Protocol extends MsObject {

   /**
    * Protocol constructor
    *
    * Initialize the Protocol class
    */
   public function __construct($id = null)
   {
      parent::__construct($id, Array(
         'table_name' => 'protocols',
         'col_name' => 'proto',
         'fields' => Array(
            'proto_idx' => 'integer',
            'proto_number' => 'text',
            'proto_name' => 'text',
            'proto_desc' => 'text',
            'proto_user_defined' => 'text',
         ),
      ));

   } // __construct()

} // class Protocol

?>
