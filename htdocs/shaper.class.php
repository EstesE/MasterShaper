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
require_once "shaper_overview.php";
require_once "shaper_targets.php";
require_once "shaper_ports.php";
require_once "shaper_protocols.php";
require_once "shaper_service_levels.php";
require_once "shaper_options.php";
require_once "shaper_users.php";
require_once "shaper_interfaces.php";
require_once "shaper_net_paths.php";
require_once "shaper_filters.php";
require_once "shaper_pipes.php";
require_once "shaper_chains.php";
require_once "shaper_about.php";
require_once "shaper_ruleset.php";
require_once "shaper_interface.php";
require_once "shaper_monitor.php";

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
      @include_once 'Net/IPv4.php';
      if(isset($php_errormsg) && preg_match('/Failed opening.*for inclusion/i', $php_errormsg)) {
         print "PEAR Net_IPv4 package is missing<br />\n";
         $missing = true;
      }
      ini_restore('track_errors');

      if(!defined('BASE_PATH')) {
         define('BASE_PATH', getcwd());
      }

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
   public function is_logged_in()
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
      <a href="javascript:refreshContent('overview'); updateSubMenu();"><img src="icons/home.gif" />&nbsp;<?php print _("Overview"); ?></a>
     </td>
     <td onmouseover="setBackGrdColor(this, 'mouseover');" onmouseout="setBackGrdColor(this, 'mouseout');">
      <a href="javascript:updateSubMenu('manage');" /><img src="icons/arrow_right.gif" />&nbsp;<?php print _("Manage"); ?></a>
     </td>
     <td onmouseover="setBackGrdColor(this, 'mouseover');" onmouseout="setBackGrdColor(this, 'mouseout');">
      <a href="javascript:updateSubMenu('settings');" /><img src="icons/arrow_right.gif" />&nbsp;<?php print _("Settings"); ?></a>      
     </td>      
     <td onmouseover="setBackGrdColor(this, 'mouseover');" onmouseout="setBackGrdColor(this, 'mouseout');">
      <a href="javascript:updateSubMenu('monitoring');" /><img src="icons/chart_pie.gif" />&nbsp;<?php print _("Monitoring"); ?></a>     
     </td>      
     <td onmouseover="setBackGrdColor(this, 'mouseover');" onmouseout="setBackGrdColor(this, 'mouseout');">
      <a href="javascript:updateSubMenu('rules');" /><img src="icons/arrow_right.gif" />&nbsp;<?php print _("Rules"); ?></a>      
     </td>      
     <td onmouseover="setBackGrdColor(this, 'mouseover');" onmouseout="setBackGrdColor(this, 'mouseout');">
      <a href="javascript:updateSubMenu('others');" /><img src="icons/arrow_right.gif" />&nbsp;<?php print _("Others"); ?></a>      
     </td>
    </tr> 
   </table> 
<?php
   } // get_main_menu()

   /**
    * returns sub menus
    */
   public function get_sub_menu($navpoint)
   {
      switch($navpoint) {
         case 'manage':
            $string = "<table class=\"submenu\"><tr>\n";
            $string.= $this->addSubMenuItem("javascript:refreshContent('chains');", "icons/flag_blue.gif", _("Chains"));
            $string.= $this->addSubMenuItem("javascript:refreshContent('filters');", "icons/flag_green.gif", _("Filters"));
            $string.= $this->addSubMenuItem("javascript:refreshContent('pipes');", "icons/flag_pink.gif", _("Pipes"));
            $string.= "</tr></table>\n";
            break;

         case 'settings':
            $string = "<table class=\"submenu\"><tr>\n";
            $string.= $this->addSubMenuItem("javascript:refreshContent('targets');", "icons/flag_purple.gif", _("Targets"));
            $string.= $this->addSubMenuItem("javascript:refreshContent('ports');", "icons/flag_orange.gif", _("Ports"));
            $string.= $this->addSubMenuItem("javascript:refreshContent('protocols');", "icons/flag_red.gif", _("Protocols"));
            $string.= $this->addSubMenuItem("javascript:refreshContent('servicelevels');", "icons/flag_yellow.gif", _("Service Levels"));
            $string.= $this->addSubMenuItem("javascript:refreshContent('options');", "icons/options.gif", _("Options"));
            $string.= $this->addSubMenuItem("javascript:refreshContent('users');", "icons/ms_users_14.gif", _("Users"));
            $string.= $this->addSubMenuItem("javascript:refreshContent('interfaces');", "icons/network_card.gif", _("Interfaces"));
            $string.= $this->addSubMenuItem("javascript:refreshContent('networkpaths');", "icons/network_card.gif", _("Network Paths"));
            $string.= "</tr></table>\n";
            break;

         case 'monitoring':
            $string = "<table class=\"submenu\"><tr>\n";
            $string.= $this->addSubMenuItem("javascript:monitor('chains');", "icons/flag_blue.gif", _("Chains"));
            $string.= $this->addSubMenuItem("javascript:monitor('pipes');", "icons/flag_pink.gif", _("Pipes"));
            $string.= $this->addSubMenuItem("javascript:monitor('bandwidth');", "icons/bandwidth.gif", _("Bandwidth"));
            $string.= "</tr></table>\n";
            break;

         case 'rules':
            $string = "<table class=\"submenu\"><tr>\n";
            $string.= $this->addSubMenuItem("javascript:ruleset('show');", "icons/show.gif", _("Show"));
            $string.= $this->addSubMenuItem("javascript:ruleset('load');", "icons/enable.gif", _("Load"));
            $string.= $this->addSubMenuItem("javascript:ruleset('loaddebug');", "icons/enable.gif", _("Load"));
            $string.= $this->addSubMenuItem("javascript:ruleset('unload');", "icons/disable.gif", _("Unload"));
            $string.= "</tr></table>\n";
            break;

         case 'others':
            $string = "<table class=\"submenu\"><tr>\n";
            $string.= $this->addSubMenuItem($navurl ."&amp;mode=12", "icons/disk.gif", _("Save Configuration"));
            $string.= $this->addSubMenuItem($navurl ."&amp;mode=13", "icons/restore.gif", _("Restore Configuration"));
            $string.= $this->addSubMenuItem($navurl ."&amp;mode=14", "icons/reset.gif", _("Reset Configuration"));
            $string.= $this->addSubMenuItem($navurl ."&amp;mode=15", "icons/update.gif", _("Update L7 Protocols"));
            $string.= $this->addSubMenuItem("http://www.mastershaper.org/MasterShaper_documentation.pdf", "icons/page_white_acrobat.gif", _("Documentation (PDF)"));
            $string.= $this->addSubMenuItem("http://www.mastershaper.org/forum/", "icons/ms_users_14.gif", _("Support Forum"));
            $string.= $this->addSubMenuItem("javascript:refreshContent('about');", "icons/ms_users_14.gif", _("About"));
            $string.= "</tr></table>\n";
            break;

      }
   
      return $string;
   }

   /**
    * returns submenu item html code
    */
   private function addSubMenuItem($link, $image, $text)
   {
      $string = "
     <td onmouseover=\"setBackGrdColor(this, 'mouseover');\" onmouseout=\"setBackGrdColor(this, 'mouseout');\">
      <a href=\"". $link ."\"><img src=\"". $image ."\" />&nbsp;". $text ."</a>
     </td>";
      return $string;

   } // addSubMenuItem() 
            
   /**
    * return main content
    */
   public function get_content($request = "")
   {
      if(!$this->is_logged_in()) {
         return $this->tmpl->fetch("login_box.tpl");
      }

      switch($request) {
         case 'overview':
            $obj = new MASTERSHAPER_OVERVIEW($this);
            break;
         case 'targets':
            $obj = new MASTERSHAPER_TARGETS($this);
            break;
         case 'ports':
            $obj = new MASTERSHAPER_PORTS($this);
            break;
         case 'protocols':
            $obj = new MASTERSHAPER_PROTOCOLS($this);
            break;
         case 'servicelevels':
            $obj = new MASTERSHAPER_SERVICELEVELS($this);
            break;
         case 'options':
            $obj = new MASTERSHAPER_OPTIONS($this);
            break;
         case 'users':
            $obj = new MASTERSHAPER_USERS($this);
            break;
         case 'interfaces':
            $obj = new MASTERSHAPER_INTERFACES($this);
            break;
         case 'networkpaths':
            $obj = new MASTERSHAPER_NETPATHS($this);
            break;
         case 'filters':
            $obj = new MASTERSHAPER_FILTERS($this);
            break;
         case 'pipes':
            $obj = new MASTERSHAPER_PIPES($this);
            break;
         case 'chains':
            $obj = new MASTERSHAPER_CHAINS($this);
            break;
         case 'about':
            $obj = new MASTERSHAPER_ABOUT($this);
            break;
      }
      if(isset($obj))
         return $obj->show();

   } // get_content()

   /**
    * bla
    */
   public function store()
   {
      if(!$this->is_logged_in()) {
         return;
      }

      if(isset($_POST['module'])) {
         switch($_POST['module']) {
            case 'target':
               $obj = new MASTERSHAPER_TARGETS($this);
               break;
            case 'port':
               $obj = new MASTERSHAPER_PORTS($this);
               break;
            case 'protocol':
               $obj = new MASTERSHAPER_PROTOCOLS($this);
               break;
            case 'servicelevel':
               $obj = new MASTERSHAPER_SERVICELEVELS($this);
               break;
            case 'options':
               $obj = new MASTERSHAPER_OPTIONS($this);
               break;
            case 'user':
               $obj = new MASTERSHAPER_USERS($this);
               break;
            case 'interface':
               $obj = new MASTERSHAPER_INTERFACES($this);
               break;
            case 'networkpath':
               $obj = new MASTERSHAPER_NETPATHS($this);
               break;
            case 'filter':
               $obj = new MASTERSHAPER_FILTERS($this);
               break;
            case 'pipe':
               $obj = new MASTERSHAPER_PIPES($this);
               break;
            case 'chain':
               $obj = new MASTERSHAPER_CHAINS($this);
               break;
            case 'overview':
               $obj = new MASTERSHAPER_OVERVIEW($this);
               break;
         }

         if(isset($obj)) {
            switch($_POST['action']) {
               case 'modify': return $obj->store(); break;
               case 'delete': return $obj->delete(); break;
               case 'toggle': return $obj->toggleStatus(); break;
            }
         }
      }

   } // store()

   /**
    * change position
    */
   public function alter_position()
   {
      $obj = new MASTERSHAPER_OVERVIEW($this);
      return $obj->alter_position();

   } // alter_position()

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
      foreach($_SESSION as $k => $v) {
         unset($_SESSION[$k]);
      }

      session_destroy();

   } // destroySession()

   /**
    * return value of requested setting
    */
   public function getOption($object)
   {
      $result = $this->db->db_fetchSingleRow("
         SELECT setting_value
         FROM ". MYSQL_PREFIX ."settings
         WHERE setting_key like '". $object ."'
      ");

      return $result->setting_value;

   } // getOption() 

   /**
    * set value of requested setting
    */
   public function setOption($key, $value)
   {
      $this->db->db_query("
         REPLACE INTO ". MYSQL_PREFIX ."settings (
            setting_key, setting_value
         ) VALUES (
            '". $key ."',
            '". $value ."'
         )
      ");

   } // setOption()

   /**
    * return true if the current user has the requested
    * permission.
    */
   public function checkPermissions($permission)
   {
       $user = $this->db->db_fetchSingleRow("
         SELECT ". $permission ."
         FROM ". MYSQL_PREFIX ."users
         WHERE user_idx='". $_SESSION['user_idx'] ."'
      ");

      if($user->$permission == "Y")
         return true;

      return false; 

   } // checkPermissions()

   /**
    * return human readable priority name
    */
   function getPriorityName($prio)
   {
      switch($prio) {
         case 0: return _("Ignored"); break;
         case 1: return _("Highest"); break;
         case 2: return _("High");    break;
         case 3: return _("Normal");  break;
         case 4: return _("Low");     break;
         case 5: return _("Lowest");  break;
      }
   } // getPriorityName()
 
   /** 
    * this function validates the provided bandwidth
    * and will return true if correctly specified
    */
   public function validateBandwidth($bw)
   {
      if(!is_numeric($bw)) {
         if(preg_match("/^(\d+)(k|m)$/i", $bw))
            return true;
      }
      else
         return true;

   } // validateBandwidth()

   /**
    * this function will return the interface name
    * of the interface provided with its index number
    */
   public function getInterfaceName($if_idx)
   {
      if($if_idx != 0) {
         $if = $this->db->db_fetchSingleRow("
            SELECT if_name
            FROM ". MYSQL_PREFIX ."interfaces
            WHERE
               if_idx='". $if_idx ."'
         ");
         return $if->if_name;
      }
      
      return NULL;

   } // getInterfaceName() 

   function getYearList($current = "")
   {
      $string = "";
      for($i = date("Y"); $i <= date("Y")+2; $i++) {
         $string.= "<option value=\"". $i ."\"";
         if($i == date("Y", (int) $current))
            $string.= " selected=\"selected\"";
         $string.= ">". $i ."</option>";
      }
      return $string;

   } // getYearList()

   function getMonthList($current = "")
   {
      $string = "";
      for($i = 1; $i <= 12; $i++) {
         $string.= "<option value=\"". $i ."\"";
         if($i == date("n", (int) $current))
            $string.= " selected=\"selected\"";
         if(date("m") == $i && $current == "")
            $string.= " selected=\"selected\"";
         $string.= ">". $i ."</option>";
      }
      return $string;

   } // getMonthList()

   function getDayList($current = "")
   {
      $string = "";
      for($i = 1; $i <= 31; $i++) {
         $string.= "<option value=\"". $i ."\"";
         if($i == date("d", (int) $current))
            $string.= " selected=\"selected\"";
         if(date("d") == $i && $current == "")
            $string.= " selected=\"selected\"";
         $string.= ">". $i ."</option>";
      }
      return $string;

   } // getDayList()

   function getHourList($current = "")
   {
      $string = "";
      for($i = 0; $i <= 23; $i++) {
         $string.= "<option value=\"". $i ."\"";
         if($i == date("H", (int) $current))
            $string.= " selected=\"selected\"";
         if(date("H") == $i && $current == "")
            $string.= " selected=\"selected\"";
         $string.= ">". sprintf("%02d", $i) ."</option>";
      }
      return $string;

   } // getHourList()

   function getMinuteList($current = "")
   {
      $string = "";
      for($i = 0; $i <= 59; $i++) {
         $string.= "<option value=\"". $i ."\"";
         if($i == date("i", (int) $current))
            $string.= " selected=\"selected\"";
         if(date("i") == $i && $current == "")
            $string.= " selected=\"selected\"";
         $string.= ">". sprintf("%02d", $i)  ."</option>";
      }
      return $string;

   } // getMinuteList()

   /**
    * returns IANA protocol number
    *
    * this function returns the IANA protocol number
    * for the specified database entry in protocol table
    */
   public function getProtocolNumberById($proto_idx)
   {
      if($proto = $this->db->db_fetchSingleRow("
         SELECT proto_number
         FROM ". MYSQL_PREFIX ."protocols
         WHERE
            proto_idx LIKE '". $proto_idx ."'"))
         return $proto->proto_number;

      return 0;

   } // getProtocolNumberById()

   /**
    * return kbit/s in integer value
    *
    * this function will transform user entered bandwidth
    * values (kilobit, megabit) into integer values).
    */
   function getKbit($bw)
   {
      if(preg_match("/^(\d+)k$/i", $bw))
         return preg_replace("/k/i", "", $bw);
      if(preg_match("/^(\d+)m$/i", $bw))
         return (preg_replace("/m/i", "", $bw) * 1024);

      return $bw;

   } // getKbit

   /** 
    * get service level information
    *
    * this function will return all details of the requested
    * service level.
    */
   function getServiceLevel($sl_idx)
   {
      return $this->db->db_fetchSingleRow("
         SELECT *
         FROM ". MYSQL_PREFIX ."service_levels
         WHERE
            sl_idx='". $sl_idx ."'
      ");

   } // getServiceLevel()

   /** get filter information
    *
    * this function will return all details of the requested
    * filter
   */
   function getFilter($filter_idx)
   {
      return $this->db->db_fetchSingleRow("
         SELECT *
         FROM ". MYSQL_PREFIX ."filters
         WHERE
            filter_idx='". $filter_idx ."'");

   } // getFilter()

   /**
    * get all filters for that pipe
    *
    * this function will return all assigned filters
    * for the specified pipe
    */
   function getFilters($pipe_idx)
   {
      return $this->db->db_query("
         SELECT af.apf_filter_idx as apf_filter_idx
         FROM ". MYSQL_PREFIX ."assign_filters af
         INNER JOIN ". MYSQL_PREFIX ."filters f
            ON af.apf_filter_idx=f.filter_idx
         WHERE
            af.apf_pipe_idx='". $pipe_idx ."' AND 
            f.filter_active='Y'
      ");

   } // getFilters()

   /**
    * get all ports for that filters
    *
    * this function will return all assigned ports
    * for the specified filter
    */
   function getPorts($filter_idx)
   {
      $list = NULL;
      $numbers = "";

      /* first get all the port id's for that filter */
      $ports = $this->db->db_query("
         SELECT afp_port_idx
         FROM ". MYSQL_PREFIX ."assign_ports
         WHERE
            afp_filter_idx='". $filter_idx ."'
      ");

      while($port = $ports->fetchRow()) {
         $numbers.= $port->afp_port_idx .",";
      }

      /* now look up the IANA port numbers for that ports */
      if($numbers != "") {
         $numbers = substr($numbers, 0, strlen($numbers)-1);
         $list = $this->db->db_query("
            SELECT port_name, port_number
            FROM ". MYSQL_PREFIX ."ports
            WHERE
               port_idx IN (". $numbers .")");
      }

      return $list;

   } // getPorts()

   /* extract all ports from a string */
   function extractPorts($string)
   {
      if($string != "" && !preg_match("/any/", $string)) {
         $string = str_replace(" ", "", $string);
         $ports = split(",", $string);

         $targets = Array();
         foreach($ports as $port) {
            if(preg_match("/.*-.*/", $port)) {
               list($start, $end) = split("-", $port);
               for($i = $start*1; $i <= $end*1; $i++) {
                  array_push($targets, $i);
               }
            }
            else {
               array_push($targets, $port);
            }
         }
         return $targets;
      }
      else {
         return NULL;
      }

   } // extractPorts()

   /**
    * this function generates the value used for CONNMARK
    */
   function getConnmarkId($string1, $string2)
   {
      return "0x". dechex(crc32($string1 . str_replace(":", "", $string2))* -1);

   } // getConnmarkId()

   /**
    * return all assigned l7 protocols
    *
    * this function will return all assigned l7 protocol which
    * are assigned to the provided filter
    */
   function getL7Protocols($filter_idx)
   {
      $list = NULL;
      $numbers = "";

      $protocols = $this->db->db_query("  
         SELECT afl7_l7proto_idx
         FROM ". MYSQL_PREFIX ."assign_l7_protocols
         WHERE
            afl7_filter_idx='". $filter_idx ."'");

      while($protocol = $protocols->fetchRow()) {
         $numbers.= $protocol->afl7_l7proto_idx .",";
      }

      if($numbers != "") {
         $numbers = substr($numbers, 0, strlen($numbers)-1);
         $list = $this->db->db_query("
            SELECT l7proto_name
            FROM ". MYSQL_PREFIX ."l7_protocols
            WHERE
               l7proto_idx IN (". $numbers .")
         ");
      }

      return $list;

   } // getL7Protocols

   /**
    * return content around ruleset
    */
   public function ruleset($mode)
   {
      if(!$this->is_logged_in()) {
         return $this->tmpl->fetch("login_box.tpl");
      }

      $obj = new MASTERSHAPER_RULESET($this);
   
      switch($mode) {
         case 'show':
            return $obj->show();
            break;
         case 'load':
            return $obj->load();
            break;
         case 'loaddebug':
            return $obj->load(1);
            break;
         case 'unload':
            return $obj->unload();
            break;
      }

   } // ruleset()

   /**
    * return content around monitor
    */
   public function monitor($mode)
   {
      if(!$this->is_logged_in()) {
         return $this->tmpl->fetch("login_box.tpl");
      }

      $obj = new MASTERSHAPER_MONITOR($this);
      $obj->show($mode);
   
   } // monitor()

   public function change_graph()
   {
      switch($_POST['action']) {
         case 'graphmode':
            $_SESSION['graphmode'] = $_POST['value'];
            break;
         case 'scalemode':
            $_SESSION['scalemode'] = $_POST['value'];
            break;
         case 'interface':
            $_SESSION['showif'] = $_POST['value'];
            break;
         case 'chain':
            $_SESSION['showchain'] = $_POST['value'];
            break;
      }

   } // change_graph()

   public function getActiveInterfaces()
   {
      $result = $this->db->db_query("
         SELECT DISTINCT if_name
         FROM
            shaper2_interfaces iface
         INNER JOIN
            shaper2_network_paths np
         ON (
            np.netpath_if1=iface.if_idx
            OR
            np.netpath_if2=iface.if_idx
         )
         WHERE
            np.netpath_active='Y'
      ");
      return $result;

   } // getActiveInterfaces()

   public function setShaperStatus($status)
   {
      $this->setOption("status", $status);

   } // setShaperStatus()

   /**
    * return the current process-user name
    */
   public function getuid()
   {
      if($uid = posix_getuid()) {
         if($user = posix_getpwuid($uid)) {
            return $user['name'];
         }
      }

      return 'n/a';

   } // getuid()

} // class MASTERSHAPER()

?>
