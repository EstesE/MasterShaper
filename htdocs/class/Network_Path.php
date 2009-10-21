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

class Network_Path extends MsObject {

   /**
    * Network_Path constructor
    *
    * Initialize the Network_Path class
    */
   public function __construct($id = null)
   {
      parent::__construct($id, Array(
         'table_name' => 'network_paths',
         'col_name' => 'netpath',
         'fields' => Array(
            'netpath_idx' => 'integer',
            'netpath_name' => 'text',
            'netpath_if1' => 'integer',
            'netpath_if1_inside_gre' => 'text',
            'netpath_if2' => 'integer',
            'netpath_if2_inside_gre' => 'text',
            'netpath_position' => 'integer',
            'netpath_imq' => 'text',
            'netpath_active' => 'text',
         ),
      ));

      if(!isset($id) || empty($id)) {
         $this->netpath_active = 'Y';
      }

   } // __construct()

   /**
    * handle updates
    */
   public function pre_save()
   {
      global $db;

      $max_pos = $db->db_fetchSingleRow("
         SELECT
            MAX(netpath_position) as pos
         FROM
            ". MYSQL_PREFIX ."network_paths
      ");

      $this->netpath_position = ($max_pos->pos+1);
      return true;

   } // pre_save()

   /**
    * toggle network path status
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
            UPDATE ". MYSQL_PREFIX ."network_paths
            SET
               netpath_active='". $new_status ."'
             WHERE
               netpath_idx='". $idx ."'
         ");
   
         return "ok";

	    }

      return "unkown error";

   } // toggleStatus()

} // class Network_Path

?>
