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

/**
 * Handler for mod_rewrite
 *
 * @package MasterShaper
 * @subpackage Rewriter
 */

class Rewriter {

   public $request;
   static public $default_page = '/overview.html';
   static private $instance;

   public function __construct()
   {

      if(isset($_SERVER['REQUEST_URI'])) {
         $parts = explode('?', $_SERVER['REQUEST_URI']);
         $this->request = $parts[0];
      }
      else
         $this->request = "";

      if(defined('WEB_PATH'))
         $this->request = str_replace(WEB_PATH, '', $this->request);

      if(empty($this->request) || $this->request == "/")
         $this->request = self::$default_page;

   } // __construct()

   /**
    * return requested page
    *
    * @param string
    */
   public function get_page_url($page_name, $id = null)
   {
      global $db;

      $sth = $db->db_prepare("
         SELECT
            page_uri
         FROM
            ". MYSQL_PREFIX ."pages
         WHERE
            page_name LIKE ?
      ");

      $db->db_execute($sth, array(
         $page_name,
      ));

      if($sth->rowCount() <= 0) {
         $db->db_sth_free($sth);
         return false;
      }

      if(($row = $sth->fetch()) === false) {
         $db->db_sth_free($sth);
         return false;
      }

      if(!isset($row->page_uri)) {
         $db->db_sth_free($sth);
         return false;
      }

      if(isset($id) && !empty($id))
         $row->page_uri = str_replace("[id]", (int) $id, $row->page_uri);

      $db->db_sth_free($sth);
      return WEB_PATH ."/". $row->page_uri;

   } // get_page_url()

   /**
    * returns instance
    *
    * @return Rewriter
    */
   static function instance()
   {
      if(!self::$instance) {
         self::$instance = new Rewriter();
      }
      return self::$instance;

   } // instance()

} // class Rewriter
