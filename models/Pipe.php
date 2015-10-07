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

class Pipe extends MsObject {

   /**
    * Pipe constructor
    *
    * Initialize the Pipe class
    */
   public function __construct($id = null)
   {
      parent::__construct($id, Array(
         'table_name' => 'pipes',
         'col_name' => 'pipe',
         'child_names' => Array(
            'filter' => 'apf',
         ),
         'fields' => Array(
            'pipe_idx' => 'integer',
            'pipe_name' => 'text',
            'pipe_sl_idx' => 'integer',
            'pipe_src_target' => 'integer',
            'pipe_dst_target' => 'integer',
            'pipe_direction' => 'integer',
            'pipe_action' => 'text',
            'pipe_active' => 'text',
            'pipe_tc_id' => 'text',
         ),
      ));

      /* it seems a new pipe gets created, preset some values */
      if(!isset($id) || empty($id)) {
         parent::init_fields(Array(
            'pipe_active' => 'Y',
            'pipe_direction' => 2,
         ));
      }

   } // __construct()

   public function pre_save()
   {
      global $db;

      /* no prework if chain already exists */
      if(isset($this->id))
         return true;

      return true;

   } // pre_save();

   public function post_save()
   {
      global $db;

      $sth = $db->db_prepare("
         DELETE FROM
            ". MYSQL_PREFIX ."assign_filters_to_pipes
         WHERE
            apf_pipe_idx LIKE ?
      ");

      //  $_POST['pipe_idx'] ."'
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
            INSERT INTO ". MYSQL_PREFIX ."assign_filters_to_pipes (
               apf_pipe_idx,
               apf_filter_idx
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

   } // post_save()

   /** 
    * post delete pipe function
    *
    * perform several cleaning up tasks after a
    * pipe has been removed.
    *
    */
   public function post_delete()
   {
      global $db;

      // remove all filter associations
      $sth = $db->db_prepare("
         DELETE FROM
            ". MYSQL_PREFIX ."assign_filters_to_pipes
         WHERE
               apf_pipe_idx LIKE ?
      ");

      $db->db_execute($sth, array(
         $this->id
      ));

      // get all chains this pipe was associated with
      $result = $db->db_query("
         SELECT
            apc_chain_idx as chain_idx
         FROM
            ". MYSQL_PREFIX ."assign_pipes_to_chains
         WHERE
            apc_pipe_idx LIKE '". $this->id ."'
      ");

      $chains = Array();
      while($chain = $result->fetch()) {
         array_push($chains, $chain->chain_idx);
      }

      // remove all chains associations
      $sth = $db->db_prepare("
         DELETE FROM
            ". MYSQL_PREFIX ."assign_pipes_to_chains
         WHERE
            apc_pipe_idx LIKE ?
      ");

      $db->db_execute($sth, array(
         $this->id
      ));

      if(empty($chains))
         return true;

      global $ms;

      $ms->update_positions('pipes', $chains);

      return true;

   } // post_delete()

   /**
    * swap targets
    *
    * @return bool
    */
   public function swap_targets()
   {
      $tmp = $this->pipe_src_target;
      $this->pipe_src_target = $this->pipe_dst_target;
      $this->pipe_dst_target = $tmp;

      return true;

   } // swap_targets()

} // class Pipe

?>
