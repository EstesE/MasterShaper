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

class Page_Update_IANA extends MASTERSHAPER_PAGE {

   /**
    * Page_Update_IANA constructor
    *
    * Initialize the Page_Update_IANA class
    */
   public function __construct()
   {
      $this->page = 'user_manage_rights';

   } // __construct()

   /* interface output */
   public function showList()
   {
      if($this->is_storing())
         $this->store();

      global $ms, $db, $tmpl;

      return $tmpl->fetch("update-iana.tpl");

   } // show()

   public function store()
   {
      global $ms, $db;
      $protocols = array();
      $ports = array();

      $db->db_query("TRUNCATE TABLE shaper2_protocols");
      $db->db_query("TRUNCATE TABLE shaper2_ports");

      /**
       * Update Protocols
       */

      if(!file_exists(BASE_PATH ."/contrib/protocol-numbers.xml")) {
         $ms->throwError("Can not locate protocol-numbers.xml file at: ". BASE_PATH ."/contrib/protocol-numbers.xml");
         return false;
      }

      if(!is_readable(BASE_PATH ."/contrib/protocol-numbers.xml")) {
         $ms->throwError("Can not read protocol-numbers.xml file at: ". BASE_PATH ."/contrib/protocol-numbers.xml");
         return false;
      }

      $xml = simplexml_load_file(BASE_PATH ."/contrib/protocol-numbers.xml");

      $xml_reg = $xml->registry;

      foreach($xml_reg->record as $xml_rec) {

         if(!isset($xml_rec->name) || !is_string((string)$xml_rec->name))
            continue;
         if(!isset($xml_rec->value) || !is_numeric((int)$xml_rec->value))
            continue;
         if(!isset($xml_rc->description) || !is_string((string)$xml_rec->description))
            $xml_rec->description = "";

         array_push($protocols, array(
            $xml_rec->name,
            $xml_rec->description,
            $xml_rec->value,
         ));
      }

      $sth = $db->db_prepare("
         INSERT IGNORE INTO ". MYSQL_PREFIX ."protocols (
            proto_name,
            proto_desc,
            proto_number
         ) VALUES (
            ?, ?, ?
         )
      ");

      foreach($protocols as $proto) {

         $db->db_execute($sth, array(
            $proto[0],
            $proto[1],
            $proto[2],
         ));
      }

      $db->db_sth_free($sth);

      /**
       * Update Ports
       */

      if(!file_exists(BASE_PATH ."/contrib/service-names-port-numbers.xml")) {
         $ms->throwError("Can not locate protocol-numbers.xml file at: ". BASE_PATH ."/contrib/service-names-port-numbers.xml");
         return false;
      }

      if(!is_readable(BASE_PATH ."/contrib/service-names-port-numbers.xml")) {
         $ms->throwError("Can not read protocol-numbers.xml file at: ". BASE_PATH ."/contrib/service-names-port-numbers.xml");
         return false;
      }

      $xml = simplexml_load_file(BASE_PATH ."/contrib/service-names-port-numbers.xml");

      foreach($xml->record as $xml_rec) {

         if(!isset($xml_rec->name) || !is_string((string)$xml_rec->name))
            continue;
         if(!isset($xml_rec->number) || !is_numeric((int)$xml_rec->number))
            continue;
         if(!isset($xml_rc->description) || !is_string((string)$xml_rec->description))
            $xml_rec->description = "";

         array_push($ports, array(
            $xml_rec->name,
            $xml_rec->description,
            $xml_rec->number,
         ));
      }

      $sth = $db->db_prepare("
         INSERT IGNORE INTO ". MYSQL_PREFIX ."ports (
            port_name,
            port_desc,
            port_number
         ) VALUES (
            ?, ?, ?
         )
      ");

      foreach($ports as $port) {

         $db->db_execute($sth, array(
            $port[0],
            $port[1],
            $port[2]
         ));
      }

      $db->db_sth_free($sth);

      printf("Looks like this was successful!<br />\n");

      return;

   } // store()

} // class Page_Update_IANA

$obj = new Page_Update_IANA;
$obj->handler();

?>
