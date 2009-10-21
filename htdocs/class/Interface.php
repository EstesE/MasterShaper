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
            'if_ifb' => 'text',
            'if_active' => 'text',
         ),
      ));

      if(!isset($id) || empty($id)) {
         $this->if_active = 'Y';
      }

   } // __construct()
  
   /**
    * toggle interface status
    */
   public function toggleStatus()
   {
      global $db;

      if(isset($_POST['idx']) && is_numeric($_POST['idx'])) {
         $idx = $_POST['idx'];

         if($_POST['to'] == 1)
            $new_status = 'Y';
         else
            $new_status = 'N';

         $db->db_query("
            UPDATE ". MYSQL_PREFIX ."interfaces
            SET
               if_active='". $new_status ."'
            WHERE
               if_idx='". $idx ."'
         ");

         return "ok";

      }

      return "unkown error";

   } // toggleStatus()

} // class Network_Interface

?>
