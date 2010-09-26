<?php

/***************************************************************************
 *
 * Copyright (c) by Andreas Unterkircher
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

class Target extends MsObject {

   /**
    * Target constructor
    *
    * Initialize the Target class
    */
   public function __construct($id = null)
   {
      parent::__construct($id, Array(
         'table_name' => 'targets',
         'col_name' => 'target',
         'fields' => Array(
            'target_idx' => 'integer',
            'target_name' => 'text',
            'target_match' => 'text',
            'target_ip' => 'text',
            'target_mac' => 'text',
         ),
      ));

      if(!isset($id) || empty($id)) {
         $this->target_match = 'IP';
      }

   } // __construct()

   /**
    * handle updates
    */
   public function post_save()
   {
      global $db;

      $sth = $db->db_prepare("
         DELETE FROM
            ". MYSQL_PREFIX ."assign_target_groups
         WHERE
            atg_group_idx LIKE ?
      ");

      $db->db_execute($sth, array(
         $this->id
      ));

      foreach($_POST['used'] as $use) {

         if(empty($use))
            continue;

         $sth = $db->db_prepare("
            INSERT INTO ". MYSQL_PREFIX ."assign_target_groups (
               atg_group_idx,
               atg_target_idx
            ) VALUES (
               ?,
               ?
            )
         ");

         $db->db_execute($sth, array(
            $this->id,
            $use
         ));
      }

      return true;

   } // store()

   public function post_delete()
   {
      global $db;

      $sth = $db->db_prepare("
         DELETE FROM
            ". MYSQL_PREFIX ."assign_target_groups
         WHERE
            atg_group_idx LIKE ?
      ");

      $db->db_execute($sth, array(
         $this->id
      ));

      $sth = $db->db_prepare("
         DELETE FROM
            ". MYSQL_PREFIX ."assign_target_groups
         WHERE
            atg_target_idx LIKE ?
      ");
         
      $db->db_execute($sth, array(
         $this->id
      ));

      return true;
   
   } // delete()

} // class Target

?>
