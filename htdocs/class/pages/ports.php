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

class Page_Ports extends MASTERSHAPER_PAGE {

   /**
    * Page_Ports constructor
    *
    * Initialize the Page_Ports class
    */
   public function __construct()
   {
      $this->rights = 'user_manage_ports';
      $this->items_per_page = 50;

   } // __construct()

   /**
    * display all ports
    */
   public function showList()
   {
      global $db, $tmpl, $rewriter, $page;

      $this->avail_ports = Array();
      $this->ports = Array();

      if(empty($page->num))
         $page->num = 1;

      $limit = ($page->num-1) * $this->items_per_page;

      $num_ports = $db->db_fetchSingleRow("SELECT COUNT(*) as count FROM ". MYSQL_PREFIX ."ports");

      $sth = $db->db_prepare("
         SELECT
            port_idx
         FROM
            ". MYSQL_PREFIX ."ports
         ORDER BY
            port_name ASC
         LIMIT
            ?, ?
      ");

      $res_ports = $db->db_execute($sth, array(
         $limit,
         $this->items_per_page
      ));

      $cnt_ports = $res_ports->numRows();
	
      while($port = $res_ports->fetchrow()) {
         $this->avail_ports[] = $port->port_idx;
      }

      $pager_params = Array(
         'mode' => 'Sliding',
         'delta' => 3,
         'append' => true,
         'urlVar' => 'num',
         'totalItems' => $num_ports->count,
         'perPage' => $this->items_per_page,
         'currentPage' => $page->num,
      );

      $pager = & Pager::factory($pager_params);
      $tmpl->assign('pager', $pager);

      $tmpl->register_block("port_list", array(&$this, "smarty_port_list"));
      return $tmpl->fetch("ports_list.tpl");

   } // showList()

   /**
    * display interface to create or edit ports
    */
   public function showEdit()
   {
      if($this->is_storing())
         $this->store();

      global $db, $tmpl, $page;

      if($page->id != 0)
         $port = new Port($page->id);
      else
         $port = new Port;

      $tmpl->assign('port', $port);

     return $tmpl->fetch("ports_edit.tpl");

   } // showEdit()

   /**
    * template function which will be called from the port listing template
    */
   public function smarty_port_list($params, $content, &$smarty, &$repeat)
   {
      global $tmpl;

      $index = $smarty->get_template_vars('smarty.IB.port_list.index');
      if(!$index) {
         $index = 0;
      }

      if($index < count($this->avail_ports)) {

         $port_idx = $this->avail_ports[$index];
         $port = new Port($port_idx);

         $tmpl->assign('port', $port);

         $index++;
         $tmpl->assign('smarty.IB.port_list.index', $index);
         $repeat = true;
      }
      else {
         $repeat =  false;
      }

      return $content;

   } // smarty_port_list()

   /**
    * handle updates
    */
   public function store()
   {
      global $ms, $db;

      isset($_POST['new']) && $_POST['new'] == 1 ? $new = 1 : $new = NULL;

      /* load port */
      if(isset($new))
         $port = new Port;
      else
         $port = new Port($_POST['port_idx']);

      if(!isset($_POST['port_name']) || $_POST['port_name'] == "") {
         $ms->throwError(_("Please enter a port name!"));
      }

      if(isset($new) && $ms->check_object_exists('port', $_POST['port_name'])) {
         $ms->throwError(_("A port with that name already exists!"));
      }

      if(!isset($new) && $port->port_name != $_POST['port_name']
         && $ms->check_object_exists('port', $_POST['port_name'])) {
         $ms->throwError(_("A port with that name already exists!"));
      }

      // has to user provided one or multiple ports
      if(preg_match("/,/", $_POST['port_number']) || preg_match("/-/", $_POST['port_number'])) {
         $is_numeric = true;
         // split the port number string into an array
         $port_numbers = split(",", $_POST['port_number']);
         foreach($port_numbers as $port_number) {
            $port_number = trim($port_number);
            // if value contains a list, split the string
            if(preg_match("/-/", $port_number)) {
               list($lower, $higher) = split("-", $port_number);
               if(!is_numeric($lower) || $lower <= 0 || $lower >= 65536)
                  $is_numeric = false;
               if(!is_numeric($higher) || $higher <= 0 || $higher >= 65536)
                  $is_numeric = false;
            }
            else {
              if(!is_numeric($port_number) || $port_number <= 0 || $port_number >= 65536)
                  $is_numeric = false;
            }
         }
         if(!$is_numeric)
            $ms->throwError(_("Please enter a valid port number range as shown in the example!"));
      }
      elseif(!is_numeric($_POST['port_number']) ||
         $_POST['port_number'] <= 0 || $_POST['port_number'] >= 65536) {
         $ms->throwError(_("Please enter a decimal port number within 1 - 65535!"));
      }

      $port_data = $ms->filter_form_data($_POST, 'port_');

      if(!$port->update($port_data))
         return false;

      if(!$port->save())
         return false;

      return true;

   } // store()

} // class Page_Ports

$obj = new Page_Ports;
$obj->handler();

?>
