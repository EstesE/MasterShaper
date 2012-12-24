<?php

/**
 * Handler for mod_rewrite
 *
 * @package MasterShaper
 * @subpackage Rewriter
 */

class Rewriter {

   static public $request;
   static public $default_page;
   static private $instance;

   public function __construct()
   {
      $this->default_page = '/overview.html';

      if(isset($_SERVER['REQUEST_URI'])) {
         $parts = explode('?', $_SERVER['REQUEST_URI']);
         $this->request = $parts[0];
      }
      else
         $this->request = "";

      if(defined('WEB_PATH'))
         $this->request = str_replace(WEB_PATH, '', $this->request);

      if(empty($this->request) || $this->request == "/")
         $this->request = $this->default_page;

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
