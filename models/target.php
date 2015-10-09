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

namespace MasterShaper\Models;

class TargetModel extends DefaultModel
{
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
         'child_names' => Array(
            'target' => 'atg',
         ),
         'fields' => Array(
            'target_idx' => 'integer',
            'target_name' => 'text',
            'target_match' => 'text',
            'target_ip' => 'text',
            'target_mac' => 'text',
         ),
      ));

      if(!isset($id) || empty($id)) {
         parent::init_fields(Array(
            'target_match' => 'IP',
         ));
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
            ". MYSQL_PREFIX ."assign_targets_to_targets
         WHERE
            atg_group_idx LIKE ?
      ");

      $db->db_execute($sth, array(
         $this->id
      ));

      $db->db_sth_free($sth);

      if(!isset($_POST['used']) || empty($_POST['used']))
         return true;

      foreach($_POST['used'] as $use) {

         if(empty($use))
            continue;

         $sth = $db->db_prepare("
            INSERT INTO ". MYSQL_PREFIX ."assign_targets_to_targets (
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

         $db->db_sth_free($sth);
      }

      return true;

   } // store()

   public function post_delete()
   {
      global $db;

      $sth = $db->db_prepare("
         DELETE FROM
            ". MYSQL_PREFIX ."assign_targets_to_targets
         WHERE
            atg_group_idx LIKE ?
      ");

      $db->db_execute($sth, array(
         $this->id
      ));

      $db->db_sth_free($sth);
      $sth = $db->db_prepare("
         DELETE FROM
            ". MYSQL_PREFIX ."assign_targets_to_targets
         WHERE
            atg_target_idx LIKE ?
      ");
         
      $db->db_execute($sth, array(
         $this->id
      ));

      $db->db_sth_free($sth);
      return true;
   
   } // post_delete()

} // class Target

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
