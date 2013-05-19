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

class Page_Protocols extends MASTERSHAPER_PAGE {

   /**
    * Page_Protocols constructor
    *
    * Initialize the Page_Protocols class
    */
   public function __construct()
   {
      $this->rights = 'user_manage_protocols';
      $this->items_per_page = 50;

   } // __construct()

   /**
    * display all protocols
    */
   public function showList()
   {
      global $db, $tmpl, $rewriter, $page;

      $this->avail_ports = Array();
      $this->ports = Array();

      if(empty($page->num))
         $page->num = 1;

      $limit = ($page->num-1) * $this->items_per_page;

      $num_protocols = $db->db_fetchSingleRow("SELECT COUNT(*) as count FROM ". MYSQL_PREFIX ."protocols");

      $this->avail_protocols = Array();
      $this->protocols = Array();

      $sth = $db->db_prepare("
         SELECT
            proto_idx
         FROM
            ". MYSQL_PREFIX ."protocols
         ORDER BY
            proto_name ASC
         LIMIT
            ?, ?
      ");

      $sth->bindParam(1, $limit, PDO::PARAM_INT);
      $sth->bindParam(2, $this->items_per_page, PDO::PARAM_INT);
      $db->db_execute($sth);

      while($protocol = $sth->fetch()) {
         $this->avail_protocols[] = $protocol->proto_idx;
      }

      $db->db_sth_free($sth);

      $pager_params = Array(
         'mode' => 'Sliding',
         'delta' => 3,
         'append' => true,
         'urlVar' => 'num',
         'totalItems' => $num_protocols->count,
         'perPage' => $this->items_per_page,
         'currentPage' => $page->num,
      );

      $pager = & Pager::factory($pager_params);
      $tmpl->assign('pager', $pager);

      $tmpl->registerPlugin("block", "protocol_list", array(&$this, "smarty_protocol_list"));
      return $tmpl->fetch("protocols_list.tpl");

   } // showList()

   /**
    * display interface to create or edit protocols
    */
   public function showEdit()
   {
      if($this->is_storing())
         $this->store();

      global $db, $tmpl, $page;

      if(isset($page->id) && $page->id != 0) {
         $protocol = new Protocol($page->id);
         $tmpl->assign('is_new', false);
      }
      else {
         $protocol = new Protocol;
         $tmpl->assign('is_new', true);
         $page->id = NULL;
      }

      /* get a list of filters that use this protocol */
      $sth = $db->db_prepare("
         SELECT
            f.filter_idx,
            f.filter_name
         FROM
            ". MYSQL_PREFIX ."filters f
         WHERE
            f.filter_protocol_id LIKE ?
         ORDER BY
            f.filter_name ASC
      ");

      $db->db_execute($sth, array(
         $page->id,
      ));

      if($sth->rowCount() > 0) {
         $filter_use_protocol = array();
         while($filter = $sth->fetch()) {
            $filter_use_protocol[$filter->filter_idx] = $filter->filter_name;
         }
         $tmpl->assign('filter_use_protocol', $filter_use_protocol);
      }

      $db->db_sth_free($sth);

      $tmpl->assign('protocol', $protocol);
      return $tmpl->fetch("protocols_edit.tpl");

   } // showEdit()

   /**
    * template function which will be called from the protocol listing template
    */
   public function smarty_protocol_list($params, $content, &$smarty, &$repeat)
   {
      $index = $smarty->getTemplateVars('smarty.IB.protocol_list.index');
      if(!$index) {
         $index = 0;
      }

      if($index < count($this->avail_protocols)) {

         $proto_idx = $this->avail_protocols[$index];

         $protocol = new Protocol($proto_idx);
         $smarty->assign('protocol', $protocol);

         $index++;
         $smarty->assign('smarty.IB.protocol_list.index', $index);
         $repeat = true;
      }
      else {
         $repeat =  false;
      }

      return $content;

   } // smarty_protocol_list()

   /**
    * handle updates
    */
   public function store()
   {
      global $ms, $db, $rewriter;

      isset($_POST['new']) && $_POST['new'] == 1 ? $new = 1 : $new = NULL;

      /* load protocol */
      if(isset($new))
         $protocol = new Protocol;
      else
         $protocol = new Protocol($_POST['proto_idx']);

      if(!isset($_POST['proto_name']) || $_POST['proto_name'] == "") {
         $ms->throwError(_("Please enter a protocol name!"));
      }
      if(isset($new) && $ms->check_object_exists('protocol', $_POST['proto_name'])) {
         $ms->throwError(_("A protocol with that name already exists!"));
      }
      if(!isset($new) && $protocol->proto_name != $_POST['proto_name']
         && $ms->check_object_exists('protocol', $_POST['proto_name'])) {
         $ms->throwError(_("A protocol with that name already exists!"));
      }
      if(!is_numeric($_POST['proto_number'])) {
         $ms->throwError(_("Protocol number needs to be an integer value!"));
      }

      $protocol_data = $ms->filter_form_data($_POST, 'proto_');

      if(!$protocol->update($protocol_data))
         return false;

      if(!$protocol->save())
         return false;

      if(isset($_POST['add_another']) && $_POST['add_another'] == 'Y')
         return true;

      $ms->set_header('Location', $rewriter->get_page_url('Protocols List'));
      return true;

   } // store()

} // class Page_Protocols

$obj = new Page_Protocols;
$obj->handler();

?>
