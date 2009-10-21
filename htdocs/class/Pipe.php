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
         'fields' => Array(
            'pipe_idx' => 'integer',
            'pipe_name' => 'text',
            'pipe_sl_idx' => 'integer',
            'pipe_position' => 'integer',
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
         $this->pipe_active = 'Y';
         $this->pipe_direction = 2;
      }

   } // __construct()

   public function pre_save()
   {
      global $db;

      /* no prework if chain already exists */
      if(isset($this->id))
         return true;

      $max_pos = $db->db_fetchSingleRow("
         SELECT
            MAX(apc_pipe_pos) as pos
         FROM
            ". MYSQL_PREFIX ."assign_pipes_to_chains
         WHERE
            apc_chain_idx='". $_POST['chain_idx'] ."'
      ");

      $this->pipe_position = ($max_pos->pos+1);

      return true;

   } // pre_save();

   public function post_save()
   {
      global $db;

      $db->db_query("
         DELETE FROM
            ". MYSQL_PREFIX ."assign_filters_to_pipes
         WHERE
            apf_pipe_idx='". $_POST['pipe_idx'] ."'
      ");

      foreach($_POST['used'] as $use) {

         if(empty($use))
            continue;

         $db->db_query("
            INSERT INTO ". MYSQL_PREFIX ."assign_filters_to_pipes (
               apf_pipe_idx,
               apf_filter_idx
            ) VALUES (
               '". $this->id ."',
               '". $use ."'
            )
         ");
      }

      return true;

   } // post_save()

   /** 
    * delete pipe
    */
   public function post_delete()
   {
      global $db;

      $db->db_query("
         DELETE FROM
            ". MYSQL_PREFIX ."assign_filters_to_pipes
         WHERE
               apf_pipe_idx='". $this->id ."'
      ");
      $db->db_query("
         DELETE FROM
            ". MYSQL_PREFIX ."assign_pipes_to_chains
         WHERE
            apc_pipe_idx='". $this->id ."'
      ");

      return true;

   } // post_delete()

   /**
    * toggle pipe status
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
            UPDATE ". MYSQL_PREFIX ."pipes
            SET
               pipe_active='". $new_status ."'
            WHERE
               pipe_idx='". $idx ."'
         ");
      
         return "ok";
      }
   
      return "unkown error";

   } // toggleStatus()

} // class Pipe

?>
