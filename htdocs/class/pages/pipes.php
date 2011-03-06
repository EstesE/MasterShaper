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

class Page_Pipes extends MASTERSHAPER_PAGE {

   /**
    * Page_Pipes constructor
    *
    * Initialize the Page_Pipes class
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
         SELECT DISTINCT
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

      if($page->id != 0)
         $pipe = new Pipe($page->id);
      else
         $pipe = new Pipe();

      /* get a list of chains that use this pipe */
      $sth = $db->db_prepare("
         SELECT
            c.chain_idx,
            c.chain_name
         FROM
            ". MYSQL_PREFIX ."chains c
         INNER JOIN
            ". MYSQL_PREFIX ."assign_pipes_to_chains apc
         ON
            apc.apc_chain_idx=c.chain_idx
         WHERE
            apc.apc_pipe_idx LIKE ?
         ORDER BY
            c.chain_name ASC
      ");

      $assigned_chains = $db->db_execute($sth, array(
         $page->id,
      ));

      if($assigned_chains->numRows() > 0) {
         $chain_use_pipes = array();
         while($chain = $assigned_chains->fetchRow()) {
            $chain_use_pipes[$chain->chain_idx] = $chain->chain_name;
         }
         $tmpl->assign('chain_use_pipes', $chain_use_pipes);
      }

      $tmpl->assign('pipe', $pipe);

      $tmpl->register_function("unused_filters_select_list", array(&$this, "smarty_unused_filters_select_list"), false);
      $tmpl->register_function("used_filters_select_list", array(&$this, "smarty_used_filters_select_list"), false);

      return $tmpl->fetch("pipes_edit.tpl");

   } // showEdit()

   /**
    * template function which will be called from the pipe listing template
    */
   public function smarty_pipe_list($params, $content, &$smarty, &$repeat)
   {
      global $ms, $db, $tmpl;

      $index = $smarty->get_template_vars('smarty.IB.pipe_list.index');
      if(!$index) {
         $index = 0;
      }

      if($index < count($this->avail_pipes)) {

         $pipe_idx = $this->avail_pipes[$index];
         $pipe =  $this->pipes[$pipe_idx];

         $filters = $ms->getFilters($pipe->pipe_idx, true);

         if($filters->numRows() > 0) {
            $pipe_use_filters = array();
            while($filter = $filters->fetchRow()) {
               $pipe_use_filters[$filter->apf_filter_idx] = $filter->filter_name;
            }
            $tmpl->assign('pipe_use_filters', $pipe_use_filters);
         }
         else
            $tmpl->assign('pipe_use_filters', '*none*');
      
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
         $sth = $db->db_prepare("
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
                  apf_pipe_idx LIKE ?
            ) apf
            ON
               apf.apf_filter_idx=f.filter_idx
            WHERE
               apf.apf_pipe_idx IS NULL
         ");

         $unused_filters = $db->db_execute($sth, array(
            $params['pipe_idx']
         ));

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

      $sth = $db->db_prepare("
         SELECT DISTINCT
            f.filter_idx,
            f.filter_name
         FROM
            ". MYSQL_PREFIX ."filters f
         INNER JOIN (
            SELECT
               apf_filter_idx
            FROM
               ". MYSQL_PREFIX ."assign_filters_to_pipes
            WHERE
               apf_pipe_idx LIKE ?
         ) apf
         ON
            apf.apf_filter_idx=f.filter_idx
      ");
         
      $used_filters = $db->db_execute($sth, array(
         $params['pipe_idx']
      ));

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
      global $ms, $db;

      if(isset($_POST['assign-pipe']) && $_POST['assign-pipe'] == 'true') {
         return $this->assign_pipe_to_chains();
      }

      /* load chain */
      if(isset($new))
         $pipe = new Pipe;
      else
         $pipe = new Pipe($_POST['pipe_idx']);

      isset($_POST['new']) && $_POST['new'] == 1 ? $new = 1 : $new = NULL;

      if(!isset($_POST['pipe_name']) || $_POST['pipe_name'] == "") {
         $ms->throwError(_("Please enter a pipe name!"));
      }
      if(isset($new) && $ms->check_object_exists('pipe', $_POST['pipe_name'])) {
         $ms->throwError(_("A pipe with that name already exists for that chain!"));
      }
      if(!isset($new) && $pipe->pipe_name != $_POST['pipe_name'] &&
         $ms->check_object_exists('pipe', $_POST['pipe_name'])) {
         $ms->throwError(_("A pipe with that name already exists for that chain!"));
      }

      $pipe_data = $ms->filter_form_data($_POST, 'pipe_');

      if(!$pipe->update($pipe_data))
         return false;

      if(!$pipe->save())
         return false;

      return true;

   } // store()

   private function assign_pipe_to_chains()
   {
      global $db, $page;

      if(!isset($_POST['chains']))
         return false;

      if(!is_array($_POST['chains']))
         return false;

      /* delete all connection between chains and this pipe */

      $sth = $db->db_prepare("
         DELETE FROM
            ". MYSQL_PREFIX ."assign_pipes_to_chains
         WHERE
            apc_pipe_idx LIKE ?
      ");

      $db->db_execute($sth, array(
         $page->id
      ));

      foreach($_POST['chains'] as $chain) {

         $sth = $db->db_prepare("
            INSERT INTO
               ". MYSQL_PREFIX ."assign_pipes_to_chains
            (
               apc_pipe_idx,
               apc_chain_idx,
               apc_sl_idx,
               apc_pipe_active,
               apc_pipe_pos
            ) VALUES (
               ?,
               ?,
               0,
               'Y',
               (
                  /* a workaround to trigger a temp-table
                     as MySQL do not allow query a to-be-
                     updated table
                  */
                  SELECT
                     MAX(apc_pipe_pos)+1
                  FROM (
                     SELECT
                        apc_pipe_pos,
                        apc_chain_idx
                     FROM
                        ". MYSQL_PREFIX ."assign_pipes_to_chains
                  ) as temp
                  WHERE
                     temp.apc_chain_idx LIKE ?
               )
            )
         ");

         $db->db_execute($sth, array(
            $page->id,
            $chain,
            $chain,
         ));
      }

      return true;

   } // assign_pipe_to_chains()

} // class Page_Pipes

$obj = new Page_Pipes;
$obj->handler();

?>
