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

require_once "shaper_page.php";
require_once "shaper_cfg.php";
require_once "shaper_db.php";

require_once "class/Rewriter.php";
require_once "class/Page.php";
require_once "class/MsObject.php";
require_once "class/MsObject.php";
require_once "class/Chain.php";
require_once "class/Filter.php";
require_once "class/Interface.php";
require_once "class/Network_Path.php";
require_once "class/Pipe.php";
require_once "class/Port.php";
require_once "class/Protocol.php";
require_once "class/Service_Level.php";
require_once "class/Target.php";
require_once "class/User.php";

define('DEBUG', 1);

class MASTERSHAPER {

   var $cfg;

   /**
    * class constructor
    *
    * this function will be called on class construct
    * and will check requirements, loads configuration,
    * open databases and start the user session
    */
   public function __construct($mode = null)
   {
      $this->cfg = new MASTERSHAPER_CFG($this, "config.dat");

      /* Check necessary requirements */
      if(!$this->check_requirements()) {
         exit(1);
      }

      $GLOBALS['db']       = new MASTERSHAPER_DB(&$this);
      $GLOBALS['rewriter'] = Rewriter::instance();

      global $db;
      global $rewriter;

      if($mode == 'install') {
         if($db->install_schema()) {
            $this->_print("Successfully installed database tables");
            exit(0);
         }
         $this->throwError("Failed installing database tables");
      }

      /* alert if meta table is missing */
      if(!$db->db_check_table_exists(MYSQL_PREFIX ."meta"))
         $this->throwError("You are missing table ". MYSQL_PREFIX ."meta! You may run install.php again.");

      if($db->getVersion() < SCHEMA_VERSION)
         $this->throwError("The local schema version is lower (". $db->getVersion() .") then the programs schema version (". SCHEMA_VERSION ."). You may run install.php again.");

      require_once "shaper_tmpl.php";
      $GLOBALS['tmpl'] = new MASTERSHAPER_TMPL($this);
      $GLOBALS['tmpl']->assign('rewriter', &$rewriter);

      if(session_id() == "")
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
      global $rewriter;

      $GLOBALS['page'] = Page::instance($rewriter->request);

      global $tmpl;
      global $page;

      $tmpl->assign("page_title", "MasterShaper v". VERSION);
      $tmpl->assign('page', $page);

      if($page->includefile == "[internal]")
         $this->handle_page_request();

      /* show login box, if not already logged in */
      if(!$this->is_logged_in()) {
         $this->load_main_title();
         $tmpl->assign('content', $tmpl->fetch("login_box.tpl"));
         $tmpl->show("index.tpl");
         return;
      }

      if($page->is_rpc_call()) {
         $this->rpc_handle();
         return;
      }

      if(!$page->includefile || $page->includefile == '[internal]') {
         $page->set_page($rewriter->default_page);
      }

      $fqpn = BASE_PATH ."/class/pages/". $page->includefile;
      if(!file_exists($fqpn))
         $this->throwError("Page not found. Unable to include ". $fqpn);
      if(!is_readable($fqpn))
         $this->throwError("Unable to read ". $fqpn);

      include $fqpn;

      $this->load_main_title();
      $this->load_main_menu();
      $this->load_sub_menu();

      $tmpl->show("index.tpl");

   } // show()

   /**
    * check if all requirements are met
    */
   private function check_requirements()
   {
      if(!function_exists("imagecreatefromjpeg")) {
         print "PHP GD library extension is missing<br />\n";
         $missing = true;
      }

      if(!function_exists("mysql_connect")) {
         print "PHP MySQL extension is missing<br />\n";
         $missing = true;
      }

      /* Check for HTML_AJAX PEAR package, lent from Horde project */
      ini_set('track_errors', 1);
      @include_once 'HTML/AJAX/Server.php';
      if(isset($php_errormsg) && preg_match('/Failed opening.*for inclusion/i', $php_errormsg)) {
         print "PEAR HTML_AJAX package is missing<br />\n";
         $missing = true;
         unset($php_errormsg);
      }
      @include_once 'MDB2.php';
      if(isset($php_errormsg) && preg_match('/Failed opening.*for inclusion/i', $php_errormsg)) {
         print "PEAR MDB2 package is missing<br />\n";
         $missing = true;
         unset($php_errormsg);
      }
      @include_once 'MDB2/Driver/mysql.php';
      if(isset($php_errormsg) && preg_match('/Failed opening.*for inclusion/i', $php_errormsg)) {
         print "PEAR MDB2 MySQL driver is missing<br />\n";
         $missing = true;
         unset($php_errormsg);
      }
      @include_once 'Net/IPv4.php';
      if(isset($php_errormsg) && preg_match('/Failed opening.*for inclusion/i', $php_errormsg)) {
         print "PEAR Net_IPv4 package is missing<br />\n";
         $missing = true;
         unset($php_errormsg);
      }
      @include_once 'smarty/libs/Smarty.class.php';
      if(isset($php_errormsg) && preg_match('/Failed opening.*for inclusion/i', $php_errormsg)) {
         print "Smarty template engine is missing<br />\n";
         $missing = true;
         unset($php_errormsg);
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

   } // check_requirements()

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
   public function load_main_title()
   {
      global $tmpl;

      if(!$this->is_logged_in()) {
         $tmpl->assign('main_title', "<img src=\"". WEB_PATH ."/icons/home.gif\" />&nbsp;MasterShaper Login");
         return;
      }

      $tmpl->assign('main_title', "<img src=\"". WEB_PATH ."/icons/home.gif\" />&nbsp;MasterShaper"
         ." - logged in as ". $_SESSION['user_name'] 
         ." (<a href=\"javascript:js_logout();\" style=\"color: #ffffff;\">logout</a>)");

   } // load_main_title()

   /**
    * loads the main menu template
    */
   public function load_main_menu()
   {
      global $tmpl;

      $tmpl->assign('main_menu', $tmpl->fetch('main_menu.tpl'));

   } // get_main_menu()

   /**
    * loads the sub menu template
    */
   public function load_sub_menu()
   {
      global $tmpl;
      global $page;
      global $rewriter;

      switch($page->name) {

         case 'Manage':
         case 'Chains List':
         case 'Chain New':
         case 'Chain Edit':
         case 'Pipes List':
         case 'Pipe New':
         case 'Pipe Edit':
         case 'Filters List':
         case 'Filter New':
         case 'Filter Edit':
            $string = "<table class=\"submenu\"><tr>\n";
            $string.= $this->addSubMenuItem($rewriter->get_page_url('Chains List'), WEB_PATH ."/icons/flag_blue.gif", _("Chains"));
            $string.= $this->addSubMenuItem($rewriter->get_page_url('Filters List'), WEB_PATH ."/icons/flag_green.gif", _("Filters"));
            $string.= $this->addSubMenuItem($rewriter->get_page_url('Pipes List'), WEB_PATH ."/icons/flag_pink.gif", _("Pipes"));
            $string.= "</tr></table>\n";
            break;

         case 'Settings':
         case 'Targets List':
         case 'Target New':
         case 'Target Edit':
         case 'Ports List':
         case 'Port New':
         case 'Port Edit':
         case 'Protocols List':
         case 'Protocol New':
         case 'Protocol Edit':
         case 'Service Levels List':
         case 'Service Level New':
         case 'Service Level Edit':
         case 'Options':
         case 'Users List':
         case 'User New':
         case 'User Edit':
         case 'Interfaces List':
         case 'Interface New':
         case 'Interface Edit':
         case 'Network Paths List':
         case 'Network Path New':
         case 'Network Path Edit':
            $string = "<table class=\"submenu\"><tr>\n";
            $string.= $this->addSubMenuItem($rewriter->get_page_url('Targets List'), WEB_PATH ."/icons/flag_purple.gif", _("Targets"));
            $string.= $this->addSubMenuItem($rewriter->get_page_url('Ports List'), WEB_PATH ."/icons/flag_orange.gif", _("Ports"));
            $string.= $this->addSubMenuItem($rewriter->get_page_url('Protocols List'), WEB_PATH ."/icons/flag_red.gif", _("Protocols"));
            $string.= $this->addSubMenuItem($rewriter->get_page_url('Service Levels List'), WEB_PATH ."/icons/flag_yellow.gif", _("Service Levels"));
            $string.= $this->addSubMenuItem($rewriter->get_page_url('Options'), WEB_PATH ."/icons/options.gif", _("Options"));
            $string.= $this->addSubMenuItem($rewriter->get_page_url('Users List'), WEB_PATH ."/icons/ms_users_14.gif", _("Users"));
            $string.= $this->addSubMenuItem($rewriter->get_page_url('Interfaces List'), WEB_PATH ."/icons/network_card.gif", _("Interfaces"));
            $string.= $this->addSubMenuItem($rewriter->get_page_url('Network Paths List'), WEB_PATH ."/icons/network_card.gif", _("Network Paths"));
            $string.= "</tr></table>\n";
            break;

         case 'Monitoring':
         case 'Monitoring Chains':
         case 'Monitoring Pipes':
         case 'Monitoring Bandwidth':
         case 'Monitoring Chains jqPlot':
         case 'Monitoring Pipes jqPlot':
         case 'Monitoring Bandwidth jqPlot':
            $string = "<table class=\"submenu\"><tr>\n";
            $string.= $this->addSubMenuItem($rewriter->get_page_url('Monitoring Chains'), WEB_PATH ."/icons/flag_blue.gif", _("Chains"));
            $string.= $this->addSubMenuItem($rewriter->get_page_url('Monitoring Pipes'), WEB_PATH ."/icons/flag_pink.gif", _("Pipes"));
            $string.= $this->addSubMenuItem($rewriter->get_page_url('Monitoring Bandwidth'), WEB_PATH ."/icons/bandwidth.gif", _("Bandwidth"));
            $string.= $this->addSubMenuItem($rewriter->get_page_url('Monitoring Chains jqPlot'), WEB_PATH ."/icons/flag_blue.gif", _("Chains jqPlot"));
            $string.= $this->addSubMenuItem($rewriter->get_page_url('Monitoring Pipes jqPlot'), WEB_PATH ."/icons/flag_pink.gif", _("Pipes jqPlot"));
            $string.= $this->addSubMenuItem($rewriter->get_page_url('Monitoring Bandwidth jqPlot'), WEB_PATH ."/icons/bandwidth.gif", _("Bandwidth jqPlot"));
            $string.= "</tr></table>\n";
            break;

         case 'Rules':
         case 'Rules Show':
         case 'Rules Load':
         case 'Rules Load Debug':
         case 'Rules Unload':
            $string = "<table class=\"submenu\"><tr>\n";
            $string.= $this->addSubMenuItem($rewriter->get_page_url('Rules Show'), WEB_PATH ."/icons/show.gif", _("Show"));
            $string.= $this->addSubMenuItem($rewriter->get_page_url('Rules Load'), WEB_PATH ."/icons/enable.gif", _("Load"));
            $string.= $this->addSubMenuItem($rewriter->get_page_url('Rules Load Debug'), WEB_PATH ."/icons/enable.gif", _("Load (debug)"));
            $string.= $this->addSubMenuItem($rewriter->get_page_url('Rules Unload'), WEB_PATH ."/icons/disable.gif", _("Unload"));
            $string.= "</tr></table>\n";
            break;

         case 'Others':
         case 'Others About':
            $string = "<table class=\"submenu\"><tr>\n";
            $string.= $this->addSubMenuItem("shaper_export.php", WEB_PATH ."/icons/disk.gif", _("Save Configuration"));
            $string.= $this->addSubMenuItem("javascript:config('restore')", WEB_PATH ."/icons/restore.gif", _("Restore Configuration"));
            $string.= $this->addSubMenuItem("javascript:config('reset')", WEB_PATH ."/icons/reset.gif", _("Reset Configuration"));
            $string.= $this->addSubMenuItem("javascript:config('updatel7')", WEB_PATH ."/icons/update.gif", _("Update L7 Protocols"));
            $string.= $this->addSubMenuItem("http://www.mastershaper.org/MasterShaper_documentation.pdf", WEB_PATH ."/icons/page_white_acrobat.gif", _("Documentation (PDF)"));
            $string.= $this->addSubMenuItem($rewriter->get_page_url('Others About'), WEB_PATH ."/icons/ms_users_14.gif", _("About"));
            $string.= "</tr></table>\n";
            break;

      }
   
      if(isset($string))
         $tmpl->assign('sub_menu', $string);

   } // load_sub_menu()

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
    * Generic RPC call handler
    *
    * @return string
    */
   private function rpc_handle()
   {
      global $page;

      if(!$this->is_logged_in()) {
         print "You need to login first!";
         return false;
      }

      if(!$this->is_valid_rpc_action()) {
         print "Invalid RPC action!";
         return false;
      }

      switch($page->action) {
         case 'delete':
            $this->rpc_delete_object();
            break;
         case 'toggle':
            $this->rpc_toggle_object_status();
            break;
         default:
            print "Unknown action";
            return false;
            break;
      }

      return true;

   } // rpc_handle()

   /**
    * RPC handler - delete object
    *
    * @return bool
    */
   private function rpc_delete_object()
   {
      global $page;

      if(!isset($_POST['id'])) {
         print "id is missing!";
         return false;
      }

      $id = $_POST['id'];

      if(preg_match('/(.*)-([0-9]+)/', $id, $parts) === false) {
         print "id in incorrect format!";
         return false;
      }

      $request_object = $parts[1];
      $id = $parts[2];

      if(!($obj = $this->load_class($request_object, $id))) {
         print "unable to locate class for ". $request_object;
         return false;
      }

      if($obj->delete()) {
         print "ok";
         return true;
      }

      print "unknown error";
      return false;

   } // rpc_delete_object()

   /**
    * RPC handler - toggle object status
    *
    * @return bool
    */
   private function rpc_toggle_object_status()
   {
      global $page;

      if(!isset($_POST['id'])) {
         print "id is missing!";
         return false;
      }
      if(!isset($_POST['to'])) {
         print "to is missing!";
         return false;
      }
      if(!in_array($_POST['to'], Array('on', 'off'))) {
         print "to in incorrect format!";
         return false;
      }

      $id = $_POST['id'];
      $to = $_POST['to'];

      if(preg_match('/(.*)-([0-9]+)/', $id, $parts) === false) {
         print "id in incorrect format!";
         return false;
      }

      $request_object = $parts[1];
      $id = $parts[2];

      if(!($obj = $this->load_class($request_object, $id))) {
         print "unable to locate class for ". $request_object;
         return false;
      }

      if($obj->toggle_status($to)) {
         print "ok";
         return true;
      }

      print "unknown error";
      return false;

   } // rpc_delete_object()


   private function load_class($object_name, $id = null)
   {
      switch($object_name) {
         case 'target':
            $obj = new Target($id);
            break;
         case 'port':
            $obj = new Port($id);
            break;
         case 'protocol':
            $obj = new Protocol($id);
            break;
         case 'servicelevel':
            $obj = new Service_Level($id);
            break;
         case 'user':
            $obj = new User($id);
            break;
         case 'interface':
            $obj = new Network_Interface($id);
            break;
         case 'networkpath':
            $obj = new Network_Path($id);
            break;
         case 'filter':
            $obj = new Filter($id);
            break;
         case 'pipe':
            $obj = new Pipe($id);
            break;
         case 'chain':
            $obj = new Chain($id);
            break;
      }

      if(isset($obj))
         return $obj;

      return false;

   } // load_class()

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
   public function login()
   {
      if(!isset($_POST['user_name']) || empty($_POST['user_name']))
         $this->throwError(_("Please enter Username and Password."));
      if(!isset($_POST['user_pass']) || empty($_POST['user_pass']))
         $this->throwError(_("Please enter Username and Password."));

      if(!$user = $this->getUserDetails($_POST['user_name']))
         $this->throwError(_("Invalid or inactive User."));

      if($user->user_pass != md5($_POST['user_pass']))
         $this->throwError(_("Invalid Password."));

      $_SESSION['user_name'] = $_POST['user_name'];
      $_SESSION['user_idx'] = $user->user_idx;

      return true;

   } // login()

   /**
    * return all user details for the provided user_name
    */
   private function getUserDetails($user_name)
   {
      global $db;

      if($user = $db->db_fetchSingleRow("
         SELECT
            user_idx,
            user_pass
         FROM
            ". MYSQL_PREFIX ."users
         WHERE
            user_name LIKE ". $db->quote($user_name) ."
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
      global $db;

      $result = $db->db_fetchSingleRow("
         SELECT setting_value
         FROM ". MYSQL_PREFIX ."settings
         WHERE setting_key like '". $object ."'
      ");

      if(isset($result->setting_value)) {
         return $result->setting_value;
      }

      return "unknown";

   } // getOption() 

   /**
    * set value of requested setting
    */
   public function setOption($key, $value)
   {
      global $db;

      $db->db_query("
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
      global $db;
      $user = $db->db_fetchSingleRow("
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
   public function getPriorityName($prio)
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
      global $db;

      if($if_idx == 0)
         return NULL;

      $if = $db->db_fetchSingleRow("
         SELECT if_name
         FROM ". MYSQL_PREFIX ."interfaces
         WHERE
            if_idx='". $if_idx ."'
      ");

      return $if->if_name;

   } // getInterfaceName() 

   public function getYearList($current = "")
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

   public function getMonthList($current = "")
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

   public function getDayList($current = "")
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

   public function getHourList($current = "")
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

   public function getMinuteList($current = "")
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
      global $db;

      if($proto = $db->db_fetchSingleRow("
         SELECT proto_number
         FROM ". MYSQL_PREFIX ."protocols
         WHERE
            proto_idx LIKE '". $proto_idx ."'"))
         return $proto->proto_number;

      return 0;

   } // getProtocolNumberById()

   /**
    * return IANA protocol name
    *
    * this function returns the IANA protocol name
    * for the specified database entry in the protocol table
    */
   public function getProtocolNameById($proto_idx)
   {
      global $db;

      if($proto = $db->db_fetchSingleRow("
         SELECT proto_name
         FROM ". MYSQL_PREFIX ."protocols
         WHERE
            proto_idx LIKE '". $proto_idx ."'"))
         return $proto->proto_name;

      return '';

   } // getProtocolNameById()

   /**
    * return kbit/s in integer value
    *
    * this function will transform user entered bandwidth
    * values (kilobit, megabit) into integer values).
    */
   public function getKbit($bw)
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
   public function getServiceLevel($sl_idx)
   {
      global $db;

      return $db->db_fetchSingleRow("
         SELECT *
         FROM ". MYSQL_PREFIX ."service_levels
         WHERE
            sl_idx='". $sl_idx ."'
      ");

   } // getServiceLevel()

   /** 
    * get service level name
    *
    * this function will return the name of the requested
    * service level.
    */
   public function getServiceLevelName($sl_idx)
   {
      global $db;

      if($sl = $db->db_fetchSingleRow("
         SELECT sl_name
         FROM ". MYSQL_PREFIX ."service_levels
         WHERE
            sl_idx='". $sl_idx ."'
      "))
         return $sl->sl_name;

   } // getServiceLevelName()

   /** 
    * get target name
    *
    * this function will return the name of the requested
    * target.
    */
   public function getTargetName($target_idx)
   {
      global $db;

      if($target = $db->db_fetchSingleRow("
         SELECT target_name
         FROM ". MYSQL_PREFIX ."targets
         WHERE
            target_idx='". $target_idx ."'
      "))
         return $target->target_name;

   } // getTargetName()

   /** 
    * get chain name
    *
    * this function will return the name of the requested
    * chain.
    */
   public function getChainName($chain_idx)
   {
      global $db;

      if($chain = $db->db_fetchSingleRow("
         SELECT chain_name
         FROM ". MYSQL_PREFIX ."chains
         WHERE
            chain_idx='". $chain_idx ."'
      "))
         return $chain->chain_name;

   } // getChainName()

   /** get filter information
    *
    * this function will return all details of the requested
    * filter
   */
   public function getFilter($filter_idx)
   {
      global $db;

      return $db->db_fetchSingleRow("
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
   public function getFilters($pipe_idx)
   {
      global $db;

      return $db->db_query("
         SELECT
            af.apf_filter_idx as apf_filter_idx
         FROM
            ". MYSQL_PREFIX ."assign_filters_to_pipes af
         INNER JOIN
            ". MYSQL_PREFIX ."filters f
         ON
            af.apf_filter_idx=f.filter_idx
         WHERE
            af.apf_pipe_idx='". $pipe_idx ."'
         AND
            f.filter_active='Y'
      ");

   } // getFilters()

   /**
    * get all ports for that filters
    *
    * this function will return all assigned ports
    * for the specified filter
    */
   public function getPorts($filter_idx)
   {
      global $db;

      $list = NULL;
      $numbers = "";

      /* first get all the port id's for that filter */
      $ports = $db->db_query("
         SELECT afp_port_idx
         FROM ". MYSQL_PREFIX ."assign_ports_to_filters
         WHERE
            afp_filter_idx='". $filter_idx ."'
      ");

      while($port = $ports->fetchRow()) {
         $numbers.= $port->afp_port_idx .",";
      }

      /* now look up the IANA port numbers for that ports */
      if($numbers != "") {
         $numbers = substr($numbers, 0, strlen($numbers)-1);
         $list = $db->db_query("
            SELECT port_name, port_number
            FROM ". MYSQL_PREFIX ."ports
            WHERE
               port_idx IN (". $numbers .")");
      }

      return $list;

   } // getPorts()

   /* extract all ports from a string */
   public function extractPorts($string)
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
   public function getConnmarkId($string1, $string2)
   {
      return "0x". dechex(crc32($string1 . str_replace(":", "", $string2))* -1);

   } // getConnmarkId()

   /**
    * return all assigned l7 protocols
    *
    * this function will return all assigned l7 protocol which
    * are assigned to the provided filter
    */
   public function getL7Protocols($filter_idx)
   {
      global $db;

      $list = NULL;
      $numbers = "";

      $protocols = $db->db_query("
         SELECT afl7_l7proto_idx
         FROM ". MYSQL_PREFIX ."assign_l7_protocols_to_filters
         WHERE
            afl7_filter_idx='". $filter_idx ."'");

      while($protocol = $protocols->fetchRow()) {
         $numbers.= $protocol->afl7_l7proto_idx .",";
      }

      if($numbers != "") {
         $numbers = substr($numbers, 0, strlen($numbers)-1);
         $list = $db->db_query("
            SELECT l7proto_name
            FROM ". MYSQL_PREFIX ."l7_protocols
            WHERE
               l7proto_idx IN (". $numbers .")
         ");
      }

      return $list;

   } // getL7Protocols

   /**
    * return content around monitor
    */
   public function monitor($mode)
   {
      $obj = new MASTERSHAPER_MONITOR($this);
      $obj->show($mode);
   
   } // monitor()

   /**
    * return JSON data for jqPlot
    *
    * @return string
    */
   public function get_jqplot_values()
   {
      if(!$this->is_logged_in()) {
         return _("not logged in");
      }

      $obj = new MASTERSHAPER_MONITOR($this);
      return $obj->get_jqplot_values($mode);
 
   } // get_jqplot_values()

   public function change_graph()
   {
      if(!isset($_POST['action']))
         return "missing action";
      if(!isset($_POST['value']))
         return "missing value";

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
      global $db;

      $result = $db->db_query("
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

   /**
    * throw error
    *
    * This function shows up error messages and afterwards outputs exceptions.
    *
    * @param string $string
    */
   public function throwError($string)
   {
      if(!defined('DB_NOERROR'))  {
         print "<br /><br />". $string ."<br /><br />\n";
         try {
            throw new MASTERSHAPER_EXCEPTION;
         }
         catch(MASTERSHAPER_EXCEPTION $e) {
            print "<br /><br />\n";
            $this->_print($e);
            die;
         }
      }

      $this->last_error = $string;

   } // throwError()

   /**
    * general output function
    *
    * @param string $text
    */
   public function _print($text)
   {
      switch($this->cfg->logging) {
         default:
         case 'display':
            print $text;
            if(!$this->is_cmdline())
               print "<br />";
            print "\n";
            break;
         case 'errorlog':
            error_log($text);
            break;
         case 'logfile':
            error_log($text, 3, $his->cfg->log_file);
            break;
      }

   } // _error()

   private function handle_page_request()
   {
      if(!isset($_POST))
         return;

      if(!isset($_POST['action']))
         return;

      $action = $_POST['action'];

      switch($action) {
         case 'do_login':
            if($this->login())
               Header("Location: ". WEB_PATH ."/");
            break;
         case 'do_logout':
            if($this->logout())
               Header("Location: ". WEB_PATH ."/");
            break;
      }
   }

   /**
    * check if called from command line
    *
    * this function will return true, if called from command line
    * otherwise false.
    * @return boolean
    */
   private function is_cmdline()
   {
      if(isset($_ENV['SHELL']) && !empty($_ENV['SHELL']))
         return true;

      return false;

   } // is_cmdline()

   /**
    * return if request RPC action is ok
    *
    * @return bool
    */
   private function is_valid_rpc_action()
   {
      global $page;

      $valid_actions = Array(
         'delete',
         'toggle',
      );

      if(in_array($page->action, $valid_actions))
         return true;

      return false;

   }  // is_valid_rpc_action()

   public function filter_form_data($data, $filter)
   {
      if(!is_array($data))
         return false;

      $filter_result = Array();

      foreach($data as $key => $value) {

         if(strstr($key, $filter) === false)
            continue;

         $filter_result[$key] = $value;
      }

      return $filter_result;

   }  // filter_form_data

   /**
    * return true if the provided chain name with the specified
    * name already exists
    *
    * @param string $object_type
    * @param string $object_name
    * @return bool
   */
   public function check_object_exists($object_type, $object_name)
   {
      global $ms, $db;

      switch($object_type) {
         case 'chain':
            $table = 'chains';
            $column = 'chain';
            break;
         case 'filter':
            $table = 'filters';
            $column = 'filter';
            break;
         case 'pipe':
            $table = 'pipes';
            $column = 'pipe';
            break;
         case 'target':
            $table = 'targets';
            $column = 'target';
            break;
         case 'port':
            $table = 'ports';
            $column = 'port';
            break;
         case 'protocol':
            $table = 'protocols';
            $column = 'proto';
            break;
         case 'service_level':
            $table = 'service_levels';
            $column = 'sl';
            break;
         case 'user':
            $table = 'users';
            $column = 'user';
            break;
         case 'interface':
            $table = 'interfaces';
            $column = 'if';
            break;
         case 'netpath':
            $table = 'network_paths';
            $column = 'netpath';
            break;
         default:
            $ms->throwError('Unknown object type');
            return;
      }

      if($db->db_fetchSingleRow("
         SELECT
            ". $column ."_idx
         FROM
            ". MYSQL_PREFIX . $table ."
         WHERE
            ". $column ."_name LIKE BINARY '". $object_name ."'
      ")) {
         return true;
      }

      return false;

   } // check_object_exist

} // class MASTERSHAPER

/***************************************************************************
 *
 * MASTERSHAPER_EXCEPTION class, inherits PHP's Exception class
 *
 ***************************************************************************/

class MASTERSHAPER_EXCEPTION extends Exception {

   // custom string representation of object
   public function __toString() {
      return "Backtrace:<br />\n". str_replace("\n", "<br />\n", parent::getTraceAsString());
   }

} // class MASTERSHAPER_EXCEPTION

// vim: set filetype=php expandtab softtabstop=3 tabstop=3 shiftwidth=3 autoindent smartindent:
?>
