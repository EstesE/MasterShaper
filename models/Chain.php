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

class Chain extends MsObject {

   /**
    * Chain constructor
    *
    * Initialize the Chain class
    */
   public function __construct($id = null)
   {
      parent::__construct($id, Array(
         'table_name' => 'chains',
         'col_name' => 'chain',
         'child_names' => Array(
            'pipe' => 'apc',
         ),
         'fields' => Array(
            'chain_idx' => 'integer',
            'chain_name' => 'text',
            'chain_active' => 'text',
            'chain_sl_idx' => 'integer',
            'chain_src_target' => 'integer',
            'chain_dst_target' => 'integer',
            'chain_position' => 'integer',
            'chain_direction' => 'integer',
            'chain_fallback_idx' => 'integer',
            'chain_action' => 'text',
            'chain_tc_id' => 'text',
            'chain_netpath_idx' => 'integer',
            'chain_host_idx' => 'integer',
            'chain_guid' => 'text',
         ),
      ));

      /* it seems a new chain gets created, preset some values */
      if(!isset($id) || empty($id)) {
         parent::init_fields(Array(
            'chain_active' => 'Y',
            'chain_fallback_idx' => -1,
            'chain_direction' => 2,
         ));
      }

   } // __construct()

   /**
    * handle updates
    */
   public function pre_save()
   {
      global $ms, $db;

      /* no prework if chain already exists */
      if(isset($this->id))
         return true;

      if(!isset($_POST['chain_netpath_idx']) || empty($_POST['chain_netpath_idx']))
         return true;

      // get the last chain position in the current network path
      $max_pos = $db->db_fetchSingleRow("
         SELECT
            MAX(chain_position) as pos
         FROM
            ". MYSQL_PREFIX ."chains
         WHERE
            chain_netpath_idx LIKE '". $_POST['chain_netpath_idx'] ."'
         AND
            chain_host_idx LIKE '". $ms->get_current_host_profile() ."'
      ");

      $this->chain_position = ($max_pos->pos+1);

      $this->chain_host_idx = $ms->get_current_host_profile();

      return true;

   } // pre_save()

   public function post_save()
   {
      global $ms, $db;

      if(!isset($_POST['pipe_sl_idx']) || empty($_POST['pipe_sl_idx']))
         return true;

      if(!isset($_POST['pipe_active']) || empty($_POST['pipe_active']))
         return true;

      $sth = $db->db_prepare("
         DELETE FROM
            ". MYSQL_PREFIX ."assign_pipes_to_chains
         WHERE
            apc_chain_idx LIKE ?
      ");

      $db->db_execute($sth, array(
         $this->id
      ));

      $db->db_sth_free($sth);

      // nothing more to do for us?
      if(!isset($_POST['used']) || empty($_POST['used']))
         return true;

      $used = $_POST['used'];
      $pipe_sl_idx = $_POST['pipe_sl_idx'];
      $pipe_active = $_POST['pipe_active'];

      $pipe_position = 1;

      foreach($used as $use) {

         if(empty($use))
            continue;

         // skip if not a valid value
         if(!is_numeric($use))
            continue;

         // override of service level?
         if(isset($pipe_sl_idx[$use]) && is_numeric($pipe_sl_idx[$use]))
            $override_sl = $pipe_sl_idx[$use];
         else
            $override_sl = 0;

         // override of pipe state within this chain
         if(isset($pipe_active[$use]) && in_array($pipe_active[$use], Array('Y','N')))
            $override_active = $pipe_active[$use];
         else
            $override_active = 'Y';

         $sth = $db->db_prepare("
            INSERT INTO ". MYSQL_PREFIX ."assign_pipes_to_chains (
               apc_pipe_idx,
               apc_chain_idx,
               apc_sl_idx,
               apc_pipe_pos,
               apc_pipe_active,
               apc_guid
            ) VALUES (
               ?,
               ?,
               ?,
               ?,
               ?,
               ?
            )
         ");

         $db->db_execute($sth, array(
            $use,
            $this->id,
            $override_sl,
            $pipe_position,
            $override_active,
            $ms->create_guid(),
         ));

         $db->db_sth_free($sth);
         $pipe_position++;

      }

      return true;

   } // post_save()

   /**
    * post delete function
    *
    * this function will be called by MsObject::delete()
    *
    * @return bool
    */
   public function post_delete()
   {
      global $db, $ms;

      $sth = $db->db_prepare("
         DELETE FROM
            ". MYSQL_PREFIX ."assign_pipes_to_chains
         WHERE
            apc_chain_idx LIKE ?
      ");

      $db->db_execute($sth, array(
         $this->id
      ));

      $db->db_sth_free($sth);
      $ms->update_positions('chains', $this->chain_netpath_idx);

      return true;

   } // post_delete()

} // class Chain

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
