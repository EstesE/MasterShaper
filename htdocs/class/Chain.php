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

class Chain extends MASTERSHAPER_PAGE {

   /**
    * Chain constructor
    *
    * Initialize the Chain class
    */
   public function __construct()
   {

   } // __construct()

   /**
    * handle updates
    */
   public function store()
   {
      global $ms, $db;

      isset($_POST['chain_new']) && $_POST['chain_new'] == 1 ? $new = 1 : $new = NULL;

      if(!isset($_POST['chain_name']) || $_POST['chain_name'] == "") {
         $ms->throwError(_("Please enter a chain name!"));
      }
      if(isset($new) && $this->checkChainExists($_POST['chain_name'])) {
         $ms->throwError(_("A chain with such a name already exists!"));
      }
      if(!isset($new) && $_POST['chain_name'] != $_POST['namebefore'] && 
         $this->checkChainExists($_POST['chain_name'])) {
         $ms->throwError(_("A chain with such a name already exists!"));
      }

      if(isset($new)) {
						
         $max_pos = $db->db_fetchSingleRow("
            SELECT
               MAX(chain_position) as pos
            FROM
               ". MYSQL_PREFIX ."chains
            WHERE
               chain_netpath_idx='". $_POST['chain_netpath_idx'] ."'
         ");

         $db->db_query("
            INSERT INTO ". MYSQL_PREFIX ."chains (
               chain_name, chain_sl_idx, chain_src_target, chain_dst_target, 
               chain_position, chain_direction, chain_netpath_idx,
               chain_active, chain_fallback_idx
            ) VALUES (
               '". $_POST['chain_name'] ."',
               '". $_POST['chain_sl_idx'] ."',
               '". $_POST['chain_src_target'] ."',
               '". $_POST['chain_dst_target'] ."',
               '". ($max_pos->pos+1) ."',
               '". $_POST['chain_direction'] ."',
               '". $_POST['chain_netpath_idx'] ."',
               '". $_POST['chain_active'] ."',
               '". $_POST['chain_fallback_idx'] ."'
            )
         ");

         $_POST['chain_idx'] = $db->db_getid();

      }
      else {

         $db->db_query("
            UPDATE
               ". MYSQL_PREFIX ."chains
            SET
               chain_name='". $_POST['chain_name'] ."',
               chain_sl_idx='". $_POST['chain_sl_idx'] ."',
               chain_src_target='". $_POST['chain_src_target'] ."',
               chain_dst_target='". $_POST['chain_dst_target'] ."',
               chain_direction='". $_POST['chain_direction'] ."',
               chain_netpath_idx='". $_POST['chain_netpath_idx'] ."',
               chain_active='". $_POST['chain_active'] ."',
               chain_fallback_idx='". $_POST['chain_fallback_idx'] ."'
            WHERE
               chain_idx='". $_POST['chain_idx'] ."'");
      }

      if(isset($_POST['used']) && $_POST['used']) {
         $db->db_query("
            DELETE FROM
               ". MYSQL_PREFIX ."assign_pipes_to_chains
            WHERE
               apc_chain_idx='". $_POST['chain_idx'] ."'
         ");

         foreach($_POST['used'] as $use) {
            if($use != "") {
               $db->db_query("
                  INSERT INTO ". MYSQL_PREFIX ."assign_pipes_to_chains (
                     apc_pipe_idx, apc_chain_idx
                  ) VALUES (
                     '". $use ."',
                     '". $_POST['chain_idx'] ."'
                  )
               ");
            }
         }
      }

      return "ok";

   } // store()

   /**
    * delete chain
    */
   public function delete()
   {
      global $db;

      if(isset($_POST['idx']) && is_numeric($_POST['idx'])) {
         $idx = $_POST['idx'];

         $db->db_query("
            DELETE FROM ". MYSQL_PREFIX ."chains
            WHERE
               chain_idx='". $idx ."'
         ");
         $db->db_query("
            DELETE FROM
               ". MYSQL_PREFIX ."assign_pipes_to_chains
            WHERE
               apc_chain_idx='". $idx ."'
         ");
         return "ok";

      }

      return "unkown error";

   } // delete()

   /**
    * toggle chain status
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
            UPDATE ". MYSQL_PREFIX ."chains
            SET
               chain_active='". $new_status ."'
            WHERE
               chain_idx='". $idx ."'
         ");

         return "ok";
      }

      return "unkown error";

   } // toggleStatus()

   /**
    * return true if the provided chain name with the specified
    * name already exists
    */
   private function checkChainExists($chain_name)
   {
      global $db;

      if($db->db_fetchSingleRow("
         SELECT chain_idx
         FROM ". MYSQL_PREFIX ."chains
         WHERE
            chain_name LIKE BINARY '". $_POST['chain_name'] ."'
         ")) {
         return true;
      }

      return false;

   } // checkChainExists()

}

$obj = new Chain;
$obj->handler();

?>
