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

class Pipe extends MASTERSHAPER_PAGE {

   /**
    * Pipe constructor
    *
    * Initialize the Pipe class
    */
   public function __construct()
   {

   } // __construct()

   /**
    * handle updates
    */
   public function store()
   {
      global $db;

      isset($_POST['pipe_new']) && $_POST['pipe_new'] == 1 ? $new = 1 : $new = NULL;

      if(!isset($_POST['pipe_name']) || $_POST['pipe_name'] == "") {
         return _("Please enter a pipe name!");
      }
      if(isset($new) && $this->checkPipeExists($_POST['pipe_name'])) {
         return _("A pipe with that name already exists for that chain!");
      }
      if(!isset($new) && $_POST['namebefore'] != $_POST['pipe_name'] &&
         $this->checkPipeExists($_POST['pipe_name'])) {
         return _("A pipe with that name already exists for that chain!");
      }
         
      if(isset($new)) {
         $max_pos = $db->db_fetchSingleRow("
            SELECT
               MAX(apc_pipe_pos) as pos
            FROM
               ". MYSQL_PREFIX ."assign_pipes_to_chains
            WHERE
               apc_chain_idx='". $_POST['chain_idx'] ."'
         ");

         $db->db_query("
            INSERT INTO ". MYSQL_PREFIX ."pipes (
               pipe_name, pipe_sl_idx, pipe_position,
               pipe_src_target, pipe_dst_target, pipe_direction,
               pipe_active
            ) VALUES (
               '". $_POST['pipe_name'] ."', 
               '". $_POST['pipe_sl_idx'] ."', 
               '". ($max_pos->pos+1) ."', 
               '". $_POST['pipe_src_target'] ."', 
               '". $_POST['pipe_dst_target'] ."', 
               '". $_POST['pipe_direction'] ."', 
               '". $_POST['pipe_active'] ."')
         ");

         $_POST['pipe_idx'] = $db->db_getid();
      }
      else {
         $db->db_query("
            UPDATE ". MYSQL_PREFIX ."pipes
            SET 
               pipe_name='". $_POST['pipe_name'] ."', 
               pipe_sl_idx='". $_POST['pipe_sl_idx'] ."', 
               pipe_src_target='". $_POST['pipe_src_target'] ."', 
               pipe_dst_target='". $_POST['pipe_dst_target'] ."', 
               pipe_direction='". $_POST['pipe_direction'] ."', 
               pipe_active='". $_POST['pipe_active'] ."' 
            WHERE
               pipe_idx='". $_POST['pipe_idx'] ."'
         ");

      }

      if(isset($_POST['used']) && $_POST['used']) {
         $db->db_query("
            DELETE FROM
               ". MYSQL_PREFIX ."assign_filters_to_pipes
            WHERE
               apf_pipe_idx='". $_POST['pipe_idx'] ."'
         ");
			
         foreach($_POST['used'] as $use) {
            if($use != "") {
               $db->db_query("
                  INSERT INTO ". MYSQL_PREFIX ."assign_filters_to_pipes (
                     apf_pipe_idx, apf_filter_idx
                  ) VALUES (
                     '". $_POST['pipe_idx'] ."',
                     '". $use ."'
                  )
               ");
            }
         }
      }

      return "ok";

   } // store()

   /** 
    * delete pipe
    */
   public function delete()
   {
      global $db;

      if(isset($_POST['idx']) && is_numeric($_POST['idx'])) {
         $idx = $_POST['idx'];

         $db->db_query("
            DELETE FROM ". MYSQL_PREFIX ."pipes
            WHERE
               pipe_idx='". $idx ."'
         ");
         $db->db_query("
            DELETE FROM ". MYSQL_PREFIX ."assign_filters_to_pipes
            WHERE
               apf_pipe_idx='". $idx ."'
         ");
         $db->db_query("
            DELETE FROM
               ". MYSQL_PREFIX ."assign_pipes_to_chains
            WHERE
               apc_pipe_idx='". $idx ."'
         ");
         return "ok";

      }

      return "unkown error";

   } // delete()

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

   /**
    * return true if the provided pipe with the specified name is
    * already existing
    */
   private function checkPipeExists($pipe_name)
   {
      global $db;

      if($db->db_fetchSingleRow("
         SELECT pipe_idx
         FROM ". MYSQL_PREFIX ."pipes
         WHERE
            pipe_name LIKE BINARY '". $_POST['pipe_name'] ."'
         ")) {
         return true;
      }

      return false;

   } // checkPipeExists()

} // class Pipe

$obj = new Pipe;
$obj->handler();

?>
