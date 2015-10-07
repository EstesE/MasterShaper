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

?>
