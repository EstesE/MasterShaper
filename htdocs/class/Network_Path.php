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
         'child_names' => Array(
            'chain' => 'chain',
         ),
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
    * get next chain position
    *
    * this function returns the next free chain position
    * available for the actual network path.
    *
    */
   public function get_next_chain_position()
   {
      global $db;

      $max_pos = $db->db_fetchSingleRow("
         SELECT
            MAX(chain_position) as pos
         FROM
            ". MYSQL_PREFIX ."chains
         WHERE
            chain_netpath_idx LIKE '". $this->id ."'
      ");

      if(!empty($max_pos->pos))
         return ($max_pos->pos+1);

      return 0;

   } // get_next_chain_position()

   public function post_save()
   {
      global $db;

      if(!isset($_POST['chain_active']) || empty($_POST['chain_active']))
         return true;

      $used = $_POST['used'];
      $chain_active = $_POST['chain_active'];

      $chain_position = 1;

      foreach($used as $use) {

         if(empty($use))
            continue;

         // skip if not a valid value
         if(!is_numeric($use))
            continue;

         $sth = $db->db_prepare("
            UPDATE
               ". MYSQL_PREFIX ."chains
            SET
               chain_position = ?
            WHERE
               chain_idx LIKE ?
         ");

         $db->db_execute($sth, array(
            $chain_position,
            $use,
         ));

         $chain_position++;

      }

      return true;

   } // post_save()

   public function post_delete()
   {
      global $ms;

      $ms->update_positions('networkpaths');

   } // post_delete()

} // class Network_Path

?>
