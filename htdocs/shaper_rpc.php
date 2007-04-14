<?php

/* *************************************************************************
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
 * *************************************************************************/

require_once "shaper_config.php";
require_once "shaper_db.php";

class SHAPERRPC {

   function SHAPERRPC()
   {
      // Read config
      $this->ms_config = new MSCONFIG($this);
      $this->ms_config->readCfg("config.dat") or exit(1);
      $this->ms_config->readCfg("icons.dat");

      // initalize database communication 
      $this->db = new MSDB($this);

      // If not all definitions are available
      if(!defined('MYSQL_HOST') || !defined('MYSQL_DB') ||
         !defined('MYSQL_USER') || !defined('MYSQL_PASS') ||
         !defined('MYSQL_PREFIX') || !defined('SHAPER_PATH') ||
         !defined('SHAPER_WEB') || !defined('TC_BIN') ||
         !defined('IPT_BIN') || !defined('TEMP_PATH') ||
         !defined('SUDO_BIN')) {

         exit(1);

      }

      $this->startSession();

      // If no session is loaded, don't care about this request
      if(!$this->isSession()) {

         exit(1);

      }

      // if no new menu entry is selected, restore the current one from session data 
      if(!isset($_GET['navpoint']) && isset($_SESSION['navpoint'])) {

         $this->navpoint = $_SESSION['navpoint'];

      }

      // if a new menu entry is selected, set active and store it in session data 
      if(isset($_GET['navpoint'])) {

         $this->navpoint = $_GET['navpoint'];
         $_SESSION['navpoint'] = $this->navpoint;

      }

   } // SHAPERRPC()

   function process_ajax_request()
   {

      require_once 'HTML/AJAX/Server.php';

      $server = new HTML_AJAX_Server();
      $server->handleRequest();

      $navurl = "index.php?navpoint=". $this->navpoint;

      switch($this->navpoint) {

         default:

            break;

         case 'overview':

            break;

         case 'manage':

            print "<table class=\"submenu\"><tr>\n";
            $this->addSubMenuItem($navurl ."&amp;mode=1", "icons/flag_blue.gif", _("Chains"));
            $this->addSubMenuItem($navurl ."&amp;mode=8", "icons/flag_green.gif", _("Filters"));
            $this->addSubMenuItem($navurl ."&amp;mode=2", "icons/flag_pink.gif", _("Pipes"));
            print "</tr></table>\n";
            break;

         case 'settings':

            print "<table class=\"submenu\"><tr>\n";
            $this->addSubMenuItem($navurl ."&amp;mode=4", "icons/flag_purple.gif", _("Targets"));
            $this->addSubMenuItem($navurl ."&amp;mode=9", "icons/flag_orange.gif", _("Ports"));
            $this->addSubMenuItem($navurl ."&amp;mode=10", "icons/flag_red.gif", _("Protocols"));
            $this->addSubMenuItem($navurl ."&amp;mode=3", "icons/flag_yellow.gif", _("Service Levels"));
            $this->addSubMenuItem($navurl ."&amp;mode=6", "icons/options.gif", _("Options"));
            $this->addSubMenuItem($navurl ."&amp;mode=16", "icons/ms_users_14.gif", _("Users"));
            $this->addSubMenuItem($navurl ."&amp;mode=17", "icons/network_card.gif", _("Interfaces"));
            $this->addSubMenuItem($navurl ."&amp;mode=18", "icons/network_card.gif", _("Network Paths"));
            print "</tr></table>\n";
            break;

         case 'monitoring':

            print "<table class=\"submenu\"><tr>\n";
            $this->addSubMenuItem($navurl ."&amp;mode=5&amp;show=chains", "icons/flag_blue.gif", _("Chains"));
            $this->addSubMenuItem($navurl ."&amp;mode=5&amp;show=pipes", "icons/flag_pink.gif", _("Pipes"));
            $this->addSubMenuItem($navurl ."&amp;mode=5&amp;show=bandwidth", "icons/bandwidth.gif", _("Bandwidth"));
            print "</tr></table>\n";
            break;

         case 'rules':

            print "<table class=\"submenu\"><tr>\n";
            $this->addSubMenuItem($navurl ."&amp;mode=7&amp;screen=1", "icons/show.gif", _("Show"));
            $this->addSubMenuItem($navurl ."&amp;mode=7&amp;screen=2", "icons/enable.gif", _("Load"));
            $this->addSubMenuItem($navurl ."&amp;mode=7&amp;screen=3", "icons/enable.gif", _("Load (debug)"));
            $this->addSubMenuItem($navurl ."&amp;mode=7&amp;screen=4", "icons/disable.gif", _("Unload"));
            print "</tr></table>\n";
            break;

         case 'others':

            print "<table class=\"submenu\"><tr>\n";
            $this->addSubMenuItem($navurl ."&amp;mode=12", "icons/disk.gif", _("Save Configuration"));
            $this->addSubMenuItem($navurl ."&amp;mode=13", "icons/restore.gif", _("Restore Configuration"));
            $this->addSubMenuItem($navurl ."&amp;mode=14", "icons/reset.gif", _("Reset Configuration"));
            $this->addSubMenuItem($navurl ."&amp;mode=15", "icons/update.gif", _("Update L7 Protocols"));
            $this->addSubMenuItem("http://www.mastershaper.org/MasterShaper_documentation.pdf", "icons/page_white_acrobat.gif", _("Documentation (PDF)"));
            $this->addSubMenuItem("http://www.mastershaper.org/forum/", "icons/ms_users_14.gif", _("Support Forum"));
            $this->addSubMenuItem($navurl ."&amp;mode=11", "icons/ms_users_14.gif", _("About"));
            print "</tr></table>\n";
            break;

      }

   } // process_ajax_request();

   function addSubMenuItem($link, $image, $text)
   {
?>
     <td onmouseover="setBackGrdColor(this, 'mouseover');" onmouseout="setBackGrdColor(this, 'mouseout');">
      <a href="<?php print $link; ?>"><img src="<?php print $image; ?>" />&nbsp;<?php print $text; ?></a>
     </td>
<?php
   } // addSubMenuItem()

   function startSession()
   {

      session_name("MASTERSHAPER");
      session_start();

   } // startSession()

   function isSession()
   {

      if($this->getOption("authentication") == "Y") {

         if(isset($_SESSION['user_name']) && isset($_SESSION['user_idx']))
            return true;

         return false;

      }

      return true;

   } // isSession()

   function getOption($object)
   {

      $result = $this->db->db_fetchSingleRow("
         SELECT setting_value FROM ". MYSQL_PREFIX ."settings
         WHERE setting_key like '". $object ."'
      ");

      return $result->setting_value;

   } // getOption()
}

$rpc = new SHAPERRPC();
$rpc->process_ajax_request();

?>
