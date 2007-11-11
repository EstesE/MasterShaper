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

class MASTERSHAPER_PIPES {

   var $db;
   var $parent;
   var $tmpl;

   /* Class constructor */
   function MASTERSHAPER_PIPES($parent)
   {
      $this->parent = &$parent;
      $this->db = &$parent->db;
      $this->tmpl = &$parent->tmpl;

   } // MASTERSHAPER_PIPES()

   /* interface output */
   function show()
   {
      /* If authentication is enabled, check permissions */
      if($this->parent->getOption("authentication") == "Y" &&
         !$this->parent->checkPermissions("user_manage_pipes")) {

         $this->parent->printError(
            "<img src=\"". ICON_PIPES ."\" alt=\"pipe icon\" />&nbsp;". _("Manage Pipes"),
            _("You do not have enough permissions to access this module!")
         );

         return 0;

      }

      if(!isset($_GET['mode'])) {
         $_GET['mode'] = "show";
      }
      if(!isset($_GET['idx']) ||
         (isset($_GET['idx']) && !is_numeric($_GET['idx'])))
         $_GET['idx'] = 0;

      switch($_GET['mode']) {
         default:
         case 'show':
            $this->showList();
            break;
         case 'new':
         case 'edit':
            $this->showEdit($_GET['idx']);
            break;
      }

   } // show()

   /**
    * display all pipes
    */
   private function showList()
   {
      $this->avail_pipes = Array();
      $this->pipes = Array();

      $res_pipes = $this->db->db_query("
         SELECT *
         FROM ". MYSQL_PREFIX ."pipes p
         LEFT JOIN ". MYSQL_PREFIX ."chains c
            ON
               p.pipe_chain_idx=c.chain_idx
         ORDER BY
            p.pipe_chain_idx ASC, p.pipe_name ASC
      ");

      $cnt_pipes = 0;

      while($pipe = $res_pipes->fetchRow()) {
         $this->avail_pipes[$cnt_pipes] = $pipe->pipe_idx;
         $this->pipes[$pipe->pipe_idx] = $pipe;
         $cnt_pipes++;
      }

      $this->tmpl->register_block("pipe_list", array(&$this, "smarty_pipe_list"));
      $this->tmpl->show("pipes_list.tpl");

   } // showList()
   
   /**
    * pipe for handling
    */
   private function showEdit($idx)
   {
      if($idx != 0) {
         $pipe = $this->db->db_fetchSingleRow("
            SELECT *
            FROM ". MYSQL_PREFIX ."pipes
            WHERE
               pipe_idx='". $idx ."'
         ");
      }
      else {
         $pipe->pipe_active = 'Y';
         $pipe->pipe_direction = 2;
      }

      $this->tmpl->assign('pipe_idx', $idx);
      $this->tmpl->assign('pipe_name', $pipe->pipe_name);
      $this->tmpl->assign('pipe_active', $pipe->pipe_active);
      $this->tmpl->assign('pipe_direction', $pipe->pipe_direction);
      $this->tmpl->assign('pipe_sl_idx', $pipe->pipe_sl_idx);
      $this->tmpl->assign('pipe_src_target', $pipe->pipe_src_target);
      $this->tmpl->assign('pipe_dst_target', $pipe->pipe_dst_target);

      $this->tmpl->register_function("chain_select_list", array(&$this, "smarty_chain_select_list"), false);
      $this->tmpl->register_function("target_select_list", array(&$this, "smarty_target_select_list"), false);
      $this->tmpl->register_function("unused_filters_select_list", array(&$this, "smarty_unused_filters_select_list"), false);
      $this->tmpl->register_function("used_filters_select_list", array(&$this, "smarty_used_filters_select_list"), false);
      $this->tmpl->register_function("service_level_select_list", array(&$this, "smarty_service_level_select_list"), false);

      $this->tmpl->show("pipes_edit.tpl");

   } // showEdit()

   /**
    * template function which will be called from the pipe listing template
    */
   public function smarty_pipe_list($params, $content, &$smarty, &$repeat)
   {
      $index = $this->tmpl->get_template_vars('smarty.IB.pipe_list.index');
      if(!$index) {
         $index = 0;
      }

      if($index < count($this->avail_pipes)) {

         $pipe_idx = $this->avail_pipes[$index];
         $pipe =  $this->pipes[$pipe_idx];

         $filters = $this->db->db_query("
            SELECT
               filter_idx, filter_name
            FROM ". MYSQL_PREFIX ."filters f
            INNER JOIN ". MYSQL_PREFIX ."assign_filters apf
               ON
                  apf.apf_filter_idx=f.filter_idx
            WHERE
               apf.apf_pipe_idx='". $pipe->pipe_idx ."'
         ");

         while($filter = $filters->fetchRow()) {
            $pipe_filters.= "<a href=\"javascript:refreshContent('filters', '&mode=edit&idx=". $filter->filter_idx ."');\">". $filter->filter_name ."</a>,&nbsp;";
         }
         $pipe_filters = substr($pipe_filters, 0, strlen($pipe_filters)-7);
      
         $this->tmpl->assign('pipe_idx', $pipe_idx);
         $this->tmpl->assign('pipe_name', $pipe->pipe_name);
         $this->tmpl->assign('pipe_active', $pipe->pipe_active);
         $this->tmpl->assign('chain_name', $pipe->chain_name);
         $this->tmpl->assign('pipe_filters', $pipe_filters);

         $index++;
         $this->tmpl->assign('smarty.IB.pipe_list.index', $index);
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
         $this->tmpl->trigger_error("smarty_unused_filters_select_list: missing 'pipe_idx' parameter", E_USER_WARNING);
         $repeat = false;
         return;
      }

      $unused_filters = $this->db->db_query("
         SELECT DISTINCT f.filter_idx, f.filter_name
         FROM ". MYSQL_PREFIX ."filters f
         INNER JOIN (
            SELECT apf_filter_idx
            FROM ". MYSQL_PREFIX ."assign_filters
            WHERE
               apf_pipe_idx!='". $params['pipe_idx'] ."'
         ) apf
         ON
            apf.apf_filter_idx=f.filter_idx
      ");
         
      while($filter = $unused_filters->fetchrow()) {
         $string.= "<option value=\"". $filter->filter_idx ."\">". $filter->filter_name ."</option>\n";
      }

      return $string;

   } // smarty_unused_filters_select_list()

   public function smarty_used_filters_select_list($params, &$smarty)
   {
      if(!array_key_exists('pipe_idx', $params)) {
         $this->tmpl->trigger_error("smarty_used_filters_select_list: missing 'pipe_idx' parameter", E_USER_WARNING);
         $repeat = false;
         return;
      }

      $used_filters = $this->db->db_query("
         SELECT DISTINCT f.filter_idx, f.filter_name
         FROM ". MYSQL_PREFIX ."filters f
         INNER JOIN (
            SELECT apf_filter_idx
            FROM ". MYSQL_PREFIX ."assign_filters
            WHERE apf_pipe_idx='". $params['pipe_idx'] ."'
         ) apf
         ON
            apf.apf_filter_idx=f.filter_idx
      ");
         
      while($filter = $used_filters->fetchrow()) {
         $string.= "<option value=\"". $filter->filter_idx ."\">". $filter->filter_name ."</option>\n";
      }

      return $string;

   } // smarty_used_filters_select_list()

   public function smarty_chain_select_list($params, &$smarty) 
   {
      if(!array_key_exists('chain_idx', $params)) {
         $this->tmpl->trigger_error("smarty_chain_select_list: missing 'chain_idx' parameter", E_USER_WARNING);
         $repeat = false;
         return;
      }

      $result = $this->db->db_query("
         SELECT *
         FROM ". MYSQL_PREFIX ."chains
      ");

      while($row = $result->fetchrow()) {
         $string.= "<option value='". $row->chain_idx ."'";
         if($row->chain_idx == $params['chain_idx']) {
            $string.= " selected=\"selected\"";
         }
         $string.= ">". $row->chain_name ."</option>\n";
      }  

      return $string;

   } // smarty_chain_select_list()

   public function smarty_target_select_list($params, &$smarty)
   {
      if(!array_key_exists('target_idx', $params)) {
         $this->tmpl->trigger_error("smarty_target_select_list: missing 'target_idx' parameter", E_USER_WARNING);
         $repeat = false;
         return;
      }
   
      $result = $this->db->db_query("
         SELECT target_idx, target_name
         FROM ". MYSQL_PREFIX ."targets
         ORDER BY target_name
      ");

      while($row = $result->fetchRow()) {
         $string.= "<option value=\"". $row->target_idx ."\" ";
         if($row->target_idx == $params['target_idx']) {
            $string.= " selected=\"selected\"";
         }
         $string.= ">". $row->target_name ."</option>\n";
      } 

      return $string;

   } // smarty_target_select_list()

   public function smarty_service_level_select_list($params, &$smarty) 
   {
      if(!array_key_exists('pipe_sl_idx', $params)) {
         $this->tmpl->trigger_error("smarty_service_level_select_list: missing 'pipe_sl_idx' parameter", E_USER_WARNING);
         $repeat = false;
         return;
      }

      $result = $this->db->db_query("
         SELECT *
         FROM ". MYSQL_PREFIX ."service_levels
         ORDER BY sl_name ASC
      ");

      while($row = $result->fetchRow()) {
         $string.= "<option value=\"". $row->sl_idx ."\"";
         if($row->sl_idx == $params['pipe_sl_idx']) {
            $string.= " selected=\"selected\"";
         }
         $string.= ">". $row->sl_name ."</option>\n"; 
      }

      return $string;

   } // get_service_level_select_list()

   /**
    * handle updates
    */
   public function store()
   {
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
         $max_pos = $this->db->db_fetchSingleRow("
            SELECT MAX(pipe_position) as pos
            FROM ". MYSQL_PREFIX ."pipes 
            WHERE
               pipe_chain_idx='". $_POST['pipe_chain_idx'] ."'
         ");

         $this->db->db_query("
            INSERT INTO ". MYSQL_PREFIX ."pipes (
               pipe_name, pipe_chain_idx, pipe_sl_idx, pipe_position,
               pipe_src_target, pipe_dst_target, pipe_direction,
               pipe_active
            ) VALUES (
               '". $_POST['pipe_name'] ."', 
               '". $_POST['pipe_chain_idx'] ."', 
               '". $_POST['pipe_sl_idx'] ."', 
               '". ($max_pos->pos+1) ."', 
               '". $_POST['pipe_src_target'] ."', 
               '". $_POST['pipe_dst_target'] ."', 
               '". $_POST['pipe_direction'] ."', 
               '". $_POST['pipe_active'] ."')
         ");

         $_POST['idx'] = $this->db->db_getid();
      }
      else {
         $this->db->db_query("
            UPDATE ". MYSQL_PREFIX ."pipes
            SET 
               pipe_name='". $_POST['pipe_name'] ."', 
               pipe_chain_idx='". $_POST['pipe_chain_idx'] ."', 
               pipe_sl_idx='". $_POST['pipe_sl_idx'] ."', 
               pipe_src_target='". $_POST['pipe_src_target'] ."', 
               pipe_dst_target='". $_POST['pipe_dst_target'] ."', 
               pipe_direction='". $_POST['pipe_direction'] ."', 
               pipe_active='". $_POST['pipe_active'] ."' 
            WHERE
               pipe_idx='". $_POST['pipe_idx'] ."'
         ");

      }

      if($_POST['used']) {
         $this->db->db_query("
            DELETE FROM ". MYSQL_PREFIX ."assign_filters
            WHERE
               apf_pipe_idx='". $_POST['pipe_idx'] ."'
         ");
			
         foreach($_POST['used'] as $use) {
            if($use != "") {
               $this->db->db_query("
                  INSERT INTO ". MYSQL_PREFIX ."assign_filters (
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
      if(isset($_POST['pipe_idx']) && is_numeric($_POST['pipe_idx'])) {
         $idx = $_POST['pipe_idx'];

         $this->db->db_query("
            DELETE FROM ". MYSQL_PREFIX ."pipes
            WHERE
               pipe_idx='". $idx ."'
         ");
         $this->db->db_query("
            DELETE FROM ". MYSQL_PREFIX ."assign_filters
            WHERE
               apf_pipe_idx='". $idx ."'
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
      if(isset($_POST['pipe_idx']) && is_numeric($_POST['pipe_idx'])) {
         $idx = $_POST['pipe_idx'];

         if($_POST['to'] == 1)
            $new_status = 'Y';
         else
            $new_status = 'N';

         $this->db->db_query("
            UPDATE ". MYSQL_PREFIX ."pipes
            SET
               pipe_active='". $new_status ."'
            WHERE
               pipe_idx='". $idx ."'
         ");
      
         return "ok";
      }
   
      return "unkown error";

   } // togglePipeStatus()

   /**
    * return true if the provided pipe with the specified name is
    * already existing
    */
   private function checkPipeExists($pipe_name)
   {
      if($this->db->db_fetchSingleRow("
         SELECT pipe_idx
         FROM ". MYSQL_PREFIX ."pipes
         WHERE
            pipe_name LIKE BINARY '". $_POST['pipe_name'] ."'
         ")) {
         return true;
      }

      return false;

   } // checkPipeExists()

}

?>
