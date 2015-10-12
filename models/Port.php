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

class Port extends MsObject {

   /**
    * Port constructor
    *
    * Initialize the Port class
    */
   public function __construct($id = null)
   {
      parent::__construct($id, Array(
         'table_name' => 'ports',
         'col_name' => 'port',
         'fields' => Array(
            'port_idx' => 'integer',
            'port_name' => 'text',
            'port_desc' => 'text',
            'port_number' => 'text',
            'port_user_defined' => 'text',
         ),
      ));

      return true;

   } // __construct()

   /**
    * delete port
    */
   public function post_delete()
   {
      global $db;

      $sth = $db->db_prepare("
         DELETE FROM
            ". MYSQL_PREFIX ."assign_ports_to_filters
         WHERE
            afp_port_idx LIKE ?
      ");

      $db->db_execute($sth, array(
         $this->id
      ));

      $db->db_sth_free($sth);
      return true;

   } // post_delete()

} // class Port

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
