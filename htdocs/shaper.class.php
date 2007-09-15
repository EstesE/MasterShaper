<?php

/***************************************************************************
 *
 * Copyright (c) by Andreas Unterkircher, unki@netshadow.at
 * All rights reserved
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  any later version.
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

require_once "shaper_cfg.php";
require_once "shaper_db.php";
require_once "shaper_tmpl.php";

class MASTERSHAPER {

   var $cfg;
   var $db;

   /**
    * class constructor
    *
    * this function will be called on class construct
    * and will check requirements, loads configuration,
    * open databases and start the user session
    */
   public function __construct()
   {
      $this->cfg = new MASTERSHAPER_CFG("config.dat");

      /* Check necessary requirements */
      if(!$this->checkRequirements()) {
         exit(1);
      }

      $this->db  = new MASTERSHAPER_DB(&$this, $this->cfg->fspot_db);
      $this->tmpl = new MASTERSHAPER_TMPL($this);

      session_start();

   } // __construct()

   public function __destruct()
   {

   } // __destruct()

   /**
    * show - generate html output
    *
    * this function can be called after the constructor has
    * prepared everyhing. it will load the index.tpl smarty
    * template. if necessary it will registere pre-selects
    * (photo index, photo, tag search, date search) into
    * users session.
    */
   public function show()
   {
      $this->tmpl->assign("page_title", "MasterShaper v". VERSION);
      $this->tmpl->show("index.tpl");

   } // show()

   /**
    * check if all requirements are met
    */
   private function checkRequirements()
   {
      if(!function_exists("imagecreatefromjpeg")) {
         print "PHP GD library extension is missing<br />\n";
         $missing = true;
      }

      if(!function_exists("sqlite3_open")) {
         print "PHP SQLite3 library extension is missing<br />\n";
         $missing = true;
      }

      /* Check for HTML_AJAX PEAR package, lent from Horde project */
      ini_set('track_errors', 1);
      @include_once 'HTML/AJAX/Server.php';
      if(isset($php_errormsg) && preg_match('/Failed opening.*for inclusion/i', $php_errormsg)) {
         print "PEAR HTML_AJAX package is missing<br />\n";
         $missing = true;
      }
      @include_once 'MDB2.php';
      if(isset($php_errormsg) && preg_match('/Failed opening.*for inclusion/i', $php_errormsg)) {
         print "PEAR MDB2 package is missing<br />\n";
         $missing = true;
      }
      ini_restore('track_errors');

      if(!is_dir(BASE_PATH ."/jpgraph")) {
         print "Can't locate jpgraph in &lt;". BASE_PATH ."/jpgraph&gt;<br />\n";
         $missing = true;
      }

      if(isset($missing))
         return false;

      return true;

   } // checkRequirements()

   /**
    * check login status
    *
    * return true if user is logged in
    * return false if user is not yet logged in
    */
   private function is_logged_in()
   {
      if(isset($_SESSION['user_name']))
         return true;

      return false; 

   } // is_logged_in()

   /**
    * returns current page title
    */
   public function get_page_title()
   {
      if(!$this->is_logged_in()) {
         return "<img src=\"icons/home.gif\" />&nbsp;MasterShaper Login";
      }
      else {
         return "<img src=\"icons/home.gif\" />&nbsp;MasterShaper Login"
            ." - logged in as ". $_SESSION['user_name'] 
            ." (<a href=\"javascript:js_logout();\" style=\"color: #ffffff;\">logout</a>)";
      }

   } // get_page_title()

   /**
    * returns main menu
    */
   public function get_main_menu()
   {
?>
   <table class="menu">
    <tr>
     <td onmouseover="setBackGrdColor(this, 'mouseover');" onmouseout="setBackGrdColor(this, 'mouseout');">
      <a href="javascript:void(0)" onClick="updateSubMenu(HTML_AJAX.grab('shaper_rpc.php?mode=get&navpoint=overview')); location.href='<?php print $_SERVER['PHP_SELF'] ."?mode=99&amp;navpoint=overview"; ?>';" /><img src="icons/home.gif" />&nbsp;<?php print _("Overview"); ?></a>      </td>      <td onmouseover="setBackGrdColor(this, 'mouseover');" onmouseout="setBackGrdColor(this, 'mouseout');">       <a href="javascript:updateSubMenu(HTML_AJAX.grab('shaper_rpc.php?mode=get&navpoint=manage'));" /><img src="icons/arrow_right.gif" />&nbsp;<?php print _("Manage"); ?></a>      </td>      <td onmouseover="setBackGrdColor(this, 'mouseover');" onmouseout="setBackGrdColor(this, 'mouseout');">       <a href="javascript:updateSubMenu(HTML_AJAX.grab('shaper_rpc.php?mode=get&navpoint=settings'));" /><img src="icons/arrow_right.gif" />&nbsp;<?php print _("Settings"); ?></a>      </td>      <td onmouseover="setBackGrdColor(this, 'mouseover');" onmouseout="setBackGrdColor(this, 'mouseout');">       <a href="javascript:updateSubMenu(HTML_AJAX.grab('shaper_rpc.php?mode=get&navpoint=monitoring'));" /><img src="icons/chart_pie.gif" />&nbsp;<?php print _("Monitoring"); ?></a>      </td>      <td onmouseover="setBackGrdColor(this, 'mouseover');" onmouseout="setBackGrdColor(this, 'mouseout');">       <a href="javascript:updateSubMenu(HTML_AJAX.grab('shaper_rpc.php?mode=get&navpoint=rules'));" /><img src="icons/arrow_right.gif" />&nbsp;<?php print _("Rules"); ?></a>      </td>      <td onmouseover="setBackGrdColor(this, 'mouseover');" onmouseout="setBackGrdColor(this, 'mouseout');">       <a href="javascript:updateSubMenu(HTML_AJAX.grab('shaper_rpc.php?mode=get&navpoint=others'));" /><img src="icons/arrow_right.gif" />&nbsp;<?php print _("Others"); ?></a>      </td>     </tr>    </table> 
<?php
   } // get_main_menu()

   /**
    * return main content
    */
   public function get_content()
   {
      if(!$this->is_logged_in()) {
         return $this->tmpl->fetch("login_box.tpl");
      }

   } // get_content()

   /**
    * check login
    */
   public function check_login()
   {
      if(isset($_POST['user_name']) && $_POST['user_name'] != "" &&
         isset($_POST['user_pass']) && $_POST['user_pass'] != "") {

         if($user = $this->getUserDetails($_POST['user_name'])) {
            if($user->user_pass == md5($_POST['user_pass'])) {
               $_SESSION['user_name'] = $_POST['user_name'];
               $_SESSION['user_idx'] = $user->user_idx;

               return "ok";
            }
            else {
               return _("Invalid Password.");
            }
         }
         else {
            return _("Invalid or inactive User.");
         }
      }
      else {
         return _("Please enter Username and Password.");
      }

   } // check_login()

   /**
    * return all user details for the provided user_name
    */
   private function getUserDetails($user_name)
   {
      if($user = $this->db->db_fetchSingleRow("
         SELECT user_idx, user_pass
         FROM ". MYSQL_PREFIX ."users
         WHERE
            user_name LIKE '". $user_name ."'
         AND
            user_active='Y'")) {

         return $user;
      }

      return NULL;

   } // getUserDetails()

   /**
    * destroy the current user session to force logout
    */
   public function destroySession()
   {
      unset($_SESSION['user_name']);
      unset($_SESSION['user_idx']);
      unset($_SESSION['navpoint']);

      session_destroy();

   } // destroySession()

}

?>
