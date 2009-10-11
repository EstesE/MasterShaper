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

class MASTERSHAPER_PIPES extends MASTERSHAPER_PAGE {

   /**
    * MASTERSHAPER_PIPES constructor
    *
    * Initialize the MASTERSHAPER_PIPES class
    */
   public function __construct()
   {
      $this->rights = 'user_manage_chains';

   } // __construct()

   /**
    * display all pipes
    */
   public function showList()
   {
      global $db, $tmpl;

      $this->avail_pipes = Array();
      $this->pipes = Array();

      $res_pipes = $db->db_query("
         SELECT
            p.*
         FROM
            ". MYSQL_PREFIX ."pipes p
         LEFT JOIN
            ". MYSQL_PREFIX ."assign_pipes_to_chains apc
         ON
            p.pipe_idx=apc.apc_pipe_idx
         LEFT JOIN
            ". MYSQL_PREFIX ."chains c
         ON
            apc.apc_chain_idx=c.chain_idx
         ORDER BY
            apc.apc_chain_idx ASC,
            p.pipe_name ASC
      ");

      $cnt_pipes = 0;

      while($pipe = $res_pipes->fetchRow()) {
         $this->avail_pipes[$cnt_pipes] = $pipe->pipe_idx;
         $this->pipes[$pipe->pipe_idx] = $pipe;
         $cnt_pipes++;
      }

      $tmpl->register_block("pipe_list", array(&$this, "smarty_pipe_list"));
      return $tmpl->fetch("pipes_list.tpl");

   } // showList()
   
   /**
    * pipe for handling
    */
   public function showEdit()
   {
      global $db, $page, $tmpl;

      if($this->is_storing())
         $this->store();

      if($page->id != 0) {
         $pipe = $db->db_fetchSingleRow("
            SELECT *
            FROM ". MYSQL_PREFIX ."pipes
            WHERE
               pipe_idx='". $page->id ."'
         ");
         $tmpl->assign('pipe_idx', $page->id);
         $tmpl->assign('pipe_name', $pipe->pipe_name);
         $tmpl->assign('pipe_active', $pipe->pipe_active);
         $tmpl->assign('pipe_direction', $pipe->pipe_direction);
         $tmpl->assign('pipe_sl_idx', $pipe->pipe_sl_idx);
         $tmpl->assign('pipe_src_target', $pipe->pipe_src_target);
         $tmpl->assign('pipe_dst_target', $pipe->pipe_dst_target);

      }
      else {
         $tmpl->assign('pipe_active', 'Y');
         $tmpl->assign('pipe_direction', 2);
      }

      $tmpl->register_function("unused_filters_select_list", array(&$this, "smarty_unused_filters_select_list"), false);
      $tmpl->register_function("used_filters_select_list", array(&$this, "smarty_used_filters_select_list"), false);

      return $tmpl->fetch("pipes_edit.tpl");

   } // showEdit()

   /**
    * template function which will be called from the pipe listing template
    */
   public function smarty_pipe_list($params, $content, &$smarty, &$repeat)
   {
      global $db, $tmpl;

      $index = $smarty->get_template_vars('smarty.IB.pipe_list.index');
      if(!$index) {
         $index = 0;
      }

      if($index < count($this->avail_pipes)) {

         $pipe_idx = $this->avail_pipes[$index];
         $pipe =  $this->pipes[$pipe_idx];

         $filters = $db->db_query("
            SELECT
               filter_idx, filter_name
            FROM ". MYSQL_PREFIX ."filters f
            INNER JOIN ". MYSQL_PREFIX ."assign_filters_to_pipes apf
               ON
                  apf.apf_filter_idx=f.filter_idx
            WHERE
               apf.apf_pipe_idx='". $pipe->pipe_idx ."'
         ");

         if($filters->numRows() > 0) {
            $pipe_use_filters = array();
            while($filter = $filters->fetchRow()) {
               $pipe_use_filters[$filter->filter_idx] = $filter->filter_name;
            }
            $tmpl->assign('pipe_use_filters', $pipe_use_filters);
         }
      
         $tmpl->assign('pipe_idx', $pipe_idx);
         $tmpl->assign('pipe_name', $pipe->pipe_name);
         $tmpl->assign('pipe_active', $pipe->pipe_active);
         $tmpl->assign('chain_name', $pipe->chain_name);

         $index++;
         $tmpl->assign('smarty.IB.pipe_list.index', $index);
         $repeat = true;
      }
      else {
         $repeat =  false;
      }

      return $content;

   } // smarty_pipe_list()

   public function smarty_unused_filters_select_list($params, &$smarty)
   {
      if(!array_key_exists('pipe_idx', $params)) {
         $tmpl->trigger_error("smarty_unused_filters_select_list: missing 'pipe_idx' parameter", E_USER_WARNING);
         $repeat = false;
         return;
      }

      global $db;

      if(!isset($params['pipe_idx'])) {
         $unused_filters = $db->db_query("
            SELECT
               filter_idx, filter_name
            FROM
               ". MYSQL_PREFIX ."filters
            ORDER BY
               filter_name
         ");
      }
      else {
         $unused_filters = $db->db_query("
            SELECT DISTINCT
               f.filter_idx, f.filter_name
            FROM
               ". MYSQL_PREFIX ."filters f
            LEFT OUTER JOIN (
               SELECT DISTINCT
                  apf_filter_idx, apf_pipe_idx
               FROM
                  ". MYSQL_PREFIX ."assign_filters_to_pipes
               WHERE
                  apf_pipe_idx=". $db->db_quote($params['pipe_idx']) ."
            ) apf
            ON
               apf.apf_filter_idx=f.filter_idx
            WHERE
               apf.apf_pipe_idx IS NULL
         ");
      }
         
      while($filter = $unused_filters->fetchrow()) {
         $string.= "<option value=\"". $filter->filter_idx ."\">". $filter->filter_name ."</option>\n";
      }

      return $string;

   } // smarty_unused_filters_select_list()

   public function smarty_used_filters_select_list($params, &$smarty)
   {
      if(!array_key_exists('pipe_idx', $params)) {
         $tmpl->trigger_error("smarty_used_filters_select_list: missing 'pipe_idx' parameter", E_USER_WARNING);
         $repeat = false;
         return;
      }

      global $db;

      $used_filters = $db->db_query("
         SELECT DISTINCT
            f.filter_idx, f.filter_name
         FROM
            ". MYSQL_PREFIX ."filters f
         INNER JOIN (
            SELECT
               apf_filter_idx
            FROM
               ". MYSQL_PREFIX ."assign_filters_to_pipes
            WHERE
               apf_pipe_idx='". $params['pipe_idx'] ."'
         ) apf
         ON
            apf.apf_filter_idx=f.filter_idx
      ");
         
      while($filter = $used_filters->fetchrow()) {
         $string.= "<option value=\"". $filter->filter_idx ."\">". $filter->filter_name ."</option>\n";
      }

      return $string;

   } // smarty_used_filters_select_list()

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

} // class MASTERSHAPER_PIPES

$obj = new MASTERSHAPER_PIPES;
$obj->handler();

?>
