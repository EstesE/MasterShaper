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
require_once "class/Host_Profile.php";
require_once "class/Host_Task.php";

define('DEBUG', 1);

class MASTERSHAPER {

   var $cfg;
   var $headers;

   /**
    * class constructor
    *
    * this function will be called on class construct
    * and will check requirements, loads configuration,
    * open databases and start the user session
    */
   public function __construct($mode = null)
   {
      $GLOBALS['ms'] = $this;

      $this->cfg = new MASTERSHAPER_CFG($this, "config.dat");
      $this->headers = Array();

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
         $this->throwError("You are missing table ". MYSQL_PREFIX ."meta! You may run <a href=\"". WEB_PATH ."/install.php\">install.php</a> again.");

      if($db->getVersion() < SCHEMA_VERSION)
         $this->throwError("The local schema version is lower (". $db->getVersion() .") then the programs schema version (". SCHEMA_VERSION ."). You may run <a href=\"". WEB_PATH ."/install.php\">install.php</a> again.");

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
    * this function gets called after MASTERSHAPER constructor has
    * done its preparations. it will load the index.tpl smarty
    * template.
    */
   public function show()
   {
      global $rewriter;

      $GLOBALS['page'] = Page::instance($rewriter->request);

      global $tmpl;
      global $page;

      $tmpl->assign("page_title", "MasterShaper v". VERSION);
      $tmpl->assign('page', $page);

      /* page request handled by MS class itself */
      if(isset($page->includefile) && $page->includefile == "[internal]") {
         $this->handle_page_request();
      }

      /* show login box, if not already logged in */
      if(!$this->is_logged_in()) {

         /* do not return anything for a RPC call */
         if($page->is_rpc_call())
            return false;

         /* return login page */
         $tmpl->assign('content', $tmpl->fetch("login_box.tpl"));
         $tmpl->show("index.tpl");
         return;
      }

      /* if the request comes from rpc.html, handle it... */
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

      $tmpl->show("index.tpl");

   } // show()

   /**
    * load - load ruleset
    *
    * this function invokes the ruleset generator.
    */
   public function load()
   {
      $debug = 0;

      if(!$this->is_cmdline()) {
         die("This function must be called from command line!");
      }

      if(isset($_SERVER['argv']) && $_SERVER['argv'][2] == 'debug')
         $debug = 1;

      require_once "class/rules/ruleset.php";
      require_once "class/rules/interface.php";

      $ruleset = new Ruleset;
      $retval = $ruleset->load($debug);

      exit($retval);

   } // load()

   /**
    * unload - unload ruleset
    *
    * this function clears all loaded rules.
    */
   public function unload()
   {
      $debug = 0;

      if(!$this->is_cmdline()) {
         die("This function must be called from command line!");
      }

      require_once "class/rules/ruleset.php";
      require_once "class/rules/interface.php";

      $ruleset = new Ruleset;
      $retval = $ruleset->unload();

      exit($retval);

   } // unload()

   /**
    * check if all requirements are met
    */
   private function check_requirements()
   {
      if(!function_exists("mysql_connect")) {
         print "PHP MySQL extension is missing<br />\n";
         $missing = true;
      }

      ini_set('track_errors', 1);
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
      @include_once 'Pager.php';
      if(isset($php_errormsg) && preg_match('/Failed opening.*for inclusion/i', $php_errormsg)) {
         print "PEAR Pager package is missing<br />\n";
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

      if(isset($missing))
         return false;

      return true;

   } // check_requirements()

   /**
    * check login status
    *
    * return true if user is logged in
    * return false if user is not yet logged in
    *
    * @return bool
    */
   public function is_logged_in()
   {
      global $tmpl;

      /* if authentication is disabled, return true */
      if(!$this->getOption('authentication'))
         return true;

      if(isset($_SESSION['user_name'])) {
         $tmpl->assign('user_name', $_SESSION['user_name']);
         return true;
      }

      return false; 

   } // is_logged_in()

   /**
    * Generic RPC call handler
    *
    * @return string
    */
   private function rpc_handle()
   {
      global $page;

      if(!$this->is_logged_in()) {
         print "You need to login first";
         return false;
      }

      if(!$this->is_valid_rpc_action()) {
         print "Invalid RPC action";
         return false;
      }

      switch($page->action) {
         case 'delete':
            $this->rpc_delete_object();
            break;
         case 'toggle':
            $this->rpc_toggle_object_status();
            break;
         case 'alter-position':
            $this->rpc_alter_position();
            break;
         case 'graph-data':
            $this->rpc_graph_data();
            break;
         case 'graph-mode':
            $this->rpc_graph_mode();
            break;
         case 'get-chains-list':
            $this->rpc_get_chains_list();
            break;
         case 'get-sub-menu':
            $this->rpc_get_sub_menu();
            break;
         case 'set-host-profile':
            $this->rpc_set_host_profile();
            break;
         case 'get-host-state':
            $this->rpc_get_host_state();
            break;
         default:
            print "Unknown action\n";
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
         print "[id] is missing!";
         return false;
      }
      if(!isset($_POST['to'])) {
         print "[to] is missing!";
         return false;
      }
      if(!in_array($_POST['to'], Array('on', 'off'))) {
         print "[to] in incorrect format!";
         return false;
      }

      $id = $_POST['id'];
      $to = $_POST['to'];
      $parent = $_POST['parent'];

      if(preg_match('/(.*)-([0-9]+)/', $id, $parts) === false) {
         print "[id] in incorrect format!";
         return false;
      }

      $request_object = $parts[1];
      $id = $parts[2];

      /* if no parent has been specified, we can go one toggling
       * objects status.
       */
      if(empty($parent)) {

         if(!($obj = $this->load_class($request_object, $id))) {
            print "unable to locate class for ". $request_object;
            return false;
         }

         if($obj->toggle_status($to)) {
            print "ok";
            return true;
         }

         print "unknown error occured when trying to change status of ". $request_object ." ". $id;
         return false;
      }

      if(!empty($parent) && preg_match('/(.*)-([0-9]+)/', $parent, $parts_parent) === false) {
         print "[parent] in incorrect format!";
         return false;
      }

      $parent_request_object = $parts_parent[1];
      $parent_id = $parts_parent[2];

      if(!($obj = $this->load_class($parent_request_object, $parent_id))) {
         print "unable to locate class for ". $parent_request_object;
         return false;
      }

      if($obj->toggle_child_status($to, $request_object, $id)) {
         print "ok";
         return true;
      }

      print "unknown error";
      return false;

   } // rpc_toggle_object_status()

   /**
    * Generic class load function
    *
    * This function validates the requested class name
    * and then tries to load the corresponding class.
    */
   public function load_class($object_name, $id = null)
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
         case 'hostprofile':
            $obj = new Host_Profile($id);
            break;
         case 'hosttask':
            $obj = new Host_Task($id);
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
    * RPC handler
    *
    * change position of netpath, chains, pipes, ...
    */
   public function rpc_alter_position()
   {
      require_once "class/pages/overview.php";
      $obj = new Page_Overview;
      print $obj->alter_position();

   } // rpc_alter_position()

   /**
    * RPC handler
    *
    * return a list of chains used by floating-dialog
    * to assign pipes to chains.
    */
   public function rpc_get_chains_list()
   {
      require_once "class/pages/chains.php";
      $obj = new Page_Chains;
      print $obj->get_chains_list();

   } // rpc_get_chains_list()

   /**
    * RPC handler
    *
    * return the requested submenu
    */
   public function rpc_get_sub_menu()
   {
      require_once "class/pages/menu.php";
      $obj = new Page_Menu;
      print $obj->get_sub_menu();

   } // rpc_get_sub_menu()

   /**
    * check login
    */
   private function login()
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

      if(!isset($_SESSION['host_profile']) || empty($_SESSION['host_profile']) || !is_numeric($_SESSION['host_profile']))
         $_SESSION['host_profile'] = 1;

      return true;

   } // login()

   /**
    * general logout function
    *
    * this function will take care to destroy the active
    * user session to force a logout.
    *
    * @return bool
    */
   private function logout()
   {
      if(!$this->destroySession()) {
         print "failed to destroy user session!";
         return false;
      }

      return true;

   } // logout()

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
      /* is there really a session? */
      if(!isset($_SESSION) || !is_array($_SESSION))
         return false;

      /* unset all session variables */
      foreach($_SESSION as $k => $v) {
         unset($_SESSION[$k]);
      }

      /* finally destroy the active session */
      session_destroy();

      return true;

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

      $sth = $db->db_prepare("
         REPLACE INTO ". MYSQL_PREFIX ."settings (
            setting_key,
            setting_value
         ) VALUES (
            ?,
            ?
         )
      ");

      $db->db_execute($sth, array(
         $key,
         $value
      ));

      $db->db_sth_free($sth);

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
      /* we are going on to handle positive indexes */
      if($if_idx <= 0)
         return;

      if(!$if = new Network_Interface($if_idx))
         return false;

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
      if(!$proto = new Protocol($proto_idx))
         return false;

      return $proto->proto_number;

   } // getProtocolNumberById()

   /**
    * return IANA protocol name
    *
    * this function returns the IANA protocol name
    * for the specified database entry in the protocol table
    */
   public function getProtocolNameById($proto_idx)
   {
      if(!$proto = new Protocol($proto_idx))
         return false;

      return $proto->proto_name;

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
    *
    * @param int $sl_idx
    * @return Service_Level
    */
   public function get_service_level($sl_idx)
   {
      global $ms;

      if(!$sl = new Service_Level($sl_idx))
         return false;

      /* without IMQ we have to swap in & out */
      if($ms->getOption('msmode') == "router")
         $sl->swap_in_out();

      return $sl;

   } // getServiceLevel()

   /** 
    * get service level name
    *
    * this function will return the name of the requested
    * service level.
    */
   public function getServiceLevelName($sl_idx)
   {
      if(!$sl = new Service_Level($sl_idx))
         return false;

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
      if(!$target = new Target($target_idx))
         return false;

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
      if(!$chain = new Chain($chain_idx))
         return false;

      return $chain->chain_name;

   } // getChainName()

   /**
    * get all filters for that pipe
    *
    * this function will return all assigned filters
    * for the specified pipe
    *
    * @param int $pipe_idx
    * @param bool $with_name
    * @return MDB2_Result
    */
   public function getFilters($pipe_idx, $with_name = false)
   {
      global $db;

      $query = "
         SELECT
            af.apf_filter_idx as apf_filter_idx
      ";

      if($with_name)
         $query.= ",
            f.filter_name as filter_name
         ";

      $query.= "
         FROM
            ". MYSQL_PREFIX ."assign_filters_to_pipes af
         INNER JOIN
            ". MYSQL_PREFIX ."filters f
         ON
            af.apf_filter_idx=f.filter_idx
         WHERE
            af.apf_pipe_idx LIKE ?
         AND
            f.filter_active='Y'
      ";

      $sth = $db->db_prepare($query);

      $res = $db->db_execute($sth, array(
         $pipe_idx
      ));

      $db->db_sth_free($sth);

      return $res;

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
      $sth = $db->db_prepare("
         SELECT
            p.port_name as port_name,
            p.port_number as port_number
         FROM
            ". MYSQL_PREFIX ."assign_ports_to_filters afp
         INNER JOIN
            ". MYSQL_PREFIX ."ports p
         ON
            afp.afp_port_idx=p.port_idx
         WHERE
            afp_filter_idx LIKE ?
      ");

      $ports = $db->db_execute($sth, array(
         $filter_idx
      ));

      $numbers = Array();

      while($port = $ports->fetchRow()) {
         array_push($numbers, array(
            'name' => $port->port_name,
            'number' => $port->port_number
         ));
      }

      $db->db_sth_free($sth);

      /* now look up the IANA port numbers for that ports */
      if(empty($numbers))
         return NULL;

      return $numbers;

   } // getPorts()

   /* extract all ports from a string */
   public function extractPorts($string)
   {
      if(empty($string) || preg_match("/any/", $string))
         return NULL;

      $string = str_replace(" ", "", $string);
      $ports = preg_split("/,/", $string);

      $targets = Array();

      foreach($ports as $port) {

         $port = trim($port);

         if(!preg_match("/.*-.*/", $port)) {
            array_push($targets, $port);
            continue;
         }

         list($start, $end) = preg_split("/-/", $port);
         // if the user try to fool us...
         if($end < $start) {
            $tmp = $end;
            $end = $start;
            $start = $tmp;
         }
         for($i = $start*1; $i <= $end*1; $i++) {
            array_push($targets, $i);
         }
      }

      return $targets;

   } // extractPorts()

   /**
    * this function generates the value used for CONNMARK
    */
   public function getConnmarkId($string1, $string2)
   {
      // if dechex returned string longer than 8 chars,
      // we are running 64 kernel, so we have to shift
      // first 8 chars from left.

      $tmp =  dechex((float) crc32($string1 . str_replace(":", "", $string2))* -1);
      if(strlen($tmp)>8)
         $tmp = substr($tmp,8);

      return "0x".$tmp;

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

      $sth = $db->db_prepare("
         SELECT
            afl7_l7proto_idx
         FROM
            ". MYSQL_PREFIX ."assign_l7_protocols_to_filters
         WHERE
            afl7_filter_idx LIKE ?
      ");

      $protocols = $db->db_execute($sth, array(
         $filter_idx
      ));

      while($protocol = $protocols->fetchRow()) {
         $numbers.= $protocol->afl7_l7proto_idx .",";
      }

      $db->db_sth_free($sth);

      if(empty($numbers))
         return NULL;

      $numbers = substr($numbers, 0, strlen($numbers)-1);
      $sth = $db->db_prepare("
         SELECT
            l7proto_name
         FROM
            ". MYSQL_PREFIX ."l7_protocols
         WHERE
            l7proto_idx IN (?)
      ");

      $list = $db->db_execute($sth, array(
         $numbers
      ));

      $db->db_sth_free($sth);
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
   private function rpc_graph_data()
   {
      require_once "class/pages/monitor.php";
      $obj = new Page_Monitor;
      print $obj->get_jqplot_values();
 
   } // rpc_graph_data()

   /**
    * change settings which graph is going to be displayed
    */
   private function rpc_graph_mode()
   {
      if(isset($_POST['graphmode']) && $this->is_valid_graph_mode($_POST['graphmode']))
         $_SESSION['graphmode'] = $_POST['graphmode'];

      if(isset($_POST['scalemode']) && $this->is_valid_scale_mode($_POST['scalemode']))
         $_SESSION['scalemode'] = $_POST['scalemode'];

      if(isset($_POST['interface']) && $this->is_valid_interface($_POST['interface']))
         $_SESSION['showif'] = $_POST['interface'];

      if(isset($_POST['chain']) && $this->is_valid_chain($_POST['chain']))
         $_SESSION['showchain'] = $_POST['chain'];

      print "ok";

   } // rpc_change_graph()

   /**
    * change host profile
    */
   private function rpc_set_host_profile()
   {
      if(!isset($_POST['hostprofile']) || !is_numeric($_POST['hostprofile'])) {
         print "invalid host profile";
         return false;
      }

      if(!$this->is_valid_host_profile($_POST['hostprofile'])) {
         print "invalid host profile";
         return false;
      }

      $_SESSION['host_profile'] = $_POST['hostprofile'];
      print "ok";

   } // rpc_change_graph()

   /**
    * return current host state (task queue)
    *
    * @return bool
    */
   private function rpc_get_host_state()
   {
      if(!isset($_POST['idx']) || !is_numeric($_POST['idx'])) {
         print "invalid host profile";
         return false;
      }

      if(!$this->is_valid_host_profile($_POST['idx'])) {
         print "invalid host profile";
         return false;
      }

      if($this->is_running_task($_POST['idx']))
         print WEB_PATH .'/icons/busy.png';
      else
         print WEB_PATH .'/icons/ready.png';

      return true;

   } // rpc_get_host_state()

   /**
    * check if requested graph mode is valid
    *
    * @param int $mode
    * @return boolean
    */
   private function is_valid_graph_mode($mode)
   {
      if(!is_numeric($mode))
         return false;

      if(!in_array($mode, Array(0,1,2,3)))
         return false;

      return true;

   } // is_valid_graph_mode()

   /**
    * check if requested scale mode is valid
    *
    * @param string $mode
    * @return boolean
    */
   private function is_valid_scale_mode($mode)
   {
      if(in_array($mode, Array('bit', 'byte', 'kbit', 'kbyte', 'mbit', 'mbyte')))
         return true;

      return false;

   } // is_valid_scale_mode()

   /**
    * check if requested interface is valid
    *
    * @param string $if
    * @return boolean
    */
   private function is_valid_interface($if)
   {
      $interfaces = $this->getActiveInterfaces();

      while($interface = $interfaces->fetchRow()) {
         if($if === $interface->if_name)
            return true;
      }

      return false;

   } // is_valid_interface()

   /**
    * check if requested chain is valid
    *
    * @param int $chain_idx
    * @return boolean
    */
   private function is_valid_chain($chain_idx)
   {
      if(!is_numeric($chain_idx))
         return false;

      if(!($obj = new Chain($chain_idx)))
         return false;

      return true;

   } // is_valid_chain()

   /**
    * checks if provided host profile id is valid
    *
    * @return boolean
    */
   private function is_valid_host_profile($host_idx)
   {
      global $db;

      if($db->db_fetchSingleRow("
         SELECT
            host_idx
         FROM
            ". MYSQL_PREFIX ."host_profiles
         WHERE
            host_idx LIKE '". $host_idx ."'"))
         return true;

      return false;

   } // is_valid_host_profile()

   public function getActiveInterfaces()
   {
      global $db;

      $result = $db->db_query("
         SELECT
            DISTINCT if_name
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
            np.netpath_active LIKE 'Y'
         AND
            np.netpath_host_idx LIKE ". $this->get_current_host_profile() ."
         AND
            iface.if_host_idx LIKE ". $this->get_current_host_profile() ."
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
   public function _print($text, $override_output = NULL, $no_newline = NULL)
   {
      if(!isset($this->cfg->logging))
         $this->cfg->logging = 'display';

      if(isset($override_output))
         $this->cfg->logging = $override_output;

      switch($this->cfg->logging) {
         default:
         case 'display':
            print $text;
            if(!$this->is_cmdline())
               print "<br />";
            if(!isset($no_newline))
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
      if(!isset($_POST) || !is_array($_POST))
         return;

      if(!isset($_POST['action']))
         return;

      switch($_POST['action']) {

         case 'do_login':
            if($this->login()) {
               /* on successful login, redirect browser to start page */
               Header("Location: ". WEB_PATH ."/");
               exit(0);
            }
            break;

         case 'do_logout':
            if($this->logout()) {
               /* on successful logout, redirect browser to login page */
               Header("Location: ". WEB_PATH ."/");
               exit(0);
            }
            break;

      }

   } // handle_page_request()

   /**
    * check if called from command line
    *
    * this function will return true, if called from command line
    * otherwise false.
    * @return boolean
    */
   public function is_cmdline()
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
         'alter-position',
         'graph-data',
         'graph-mode',
         'get-chains-list',
         'get-sub-menu',
         'set-host-profile',
         'get-host-state',
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
         case 'hostprofile':
            $table = 'host_profiles';
            $column = 'host';
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


   /**
    * update position
    *
    */
   public function update_positions($obj_type, $ms_objects = NULL)
   {
      global $db;

      if($obj_type == "pipes") {

         // loop through all provided chain ids
         foreach($ms_objects as $chain) {

            // get all pipes used by chain
            $pipes = $db->db_query("
               SELECT
                  apc_pipe_idx as pipe_idx
               FROM
                  ". MYSQL_PREFIX ."assign_pipes_to_chains
               WHERE
                  apc_chain_idx LIKE '". $chain ."'
               ORDER BY
                  apc_pipe_pos ASC
            ");

            // update all pipes position assign to this chain
            $pos = 1;

            while($pipe = $pipes->fetchRow()) {

               $sth = $db->db_prepare("
                  UPDATE
                     ". MYSQL_PREFIX ."assign_pipes_to_chains
                  SET
                     apc_pipe_pos=?
                  WHERE
                     apc_pipe_idx=?
                  AND
                     apc_chain_idx=?
               ");

               $db->db_execute($sth, array(
                  $pos,
                  $pipe->pipe_idx,
                  $chain
               ));

               $db->db_sth_free($sth);
               $pos++;
            }
         }
      }

      if($obj_type == "chains") {

         // get all chains assign to this network-path
         $sth = $db->db_prepare("
            SELECT
               chain_idx
            FROM
               ". MYSQL_PREFIX ."chains
            WHERE
               chain_netpath_idx LIKE ?
            AND
               chain_host_idx LIKE ?
            ORDER BY
               chain_position ASC
         ");

         $chains = $db->db_execute($sth, array(
            $ms_objects,
            $this->get_current_host_profile(),
         ));

         $db->db_sth_free($sth);
         $pos = 1;

         while($chain = $chains->fetchRow()) {

            $sth = $db->db_prepare("
               UPDATE
                  ". MYSQL_PREFIX ."chains
               SET
                  chain_position=?
               WHERE
                 chain_idx LIKE ?
               AND
                 chain_netpath_idx LIKE ?
               AND
                 chain_host_idx LIKE ?
            ");

            $db->db_execute($sth, array(
               $pos,
               $chain->chain_idx,
               $ms_objects,
               $this->get_current_host_profile(),
            ));

            $db->db_sth_free($sth);
            $pos++;
         }
      }

      if($obj_type == "networkpaths") {

         $pos = 1;

         $sth = $db->db_prepare("
            SELECT
               netpath_idx
            FROM
               ". MYSQL_PREFIX ."network_paths
            WHERE
               netpath_host_idx LIKE ?
            ORDER BY
               netpath_position ASC
         ");

         $nps = $db->db_execute($sth, array(
            $this->get_current_host_profile(),
         ));

         $db->db_sth_free($sth);
         $pos = 1;

         while($np = $nps->fetchRow()) {

            $sth = $db->db_prepare("
               UPDATE
                  ". MYSQL_PREFIX ."network_paths
               SET
                  netpath_position=?
               WHERE
                  netpath_idx LIKE ?
               AND
                  netpath_host_idx LIKE ?
            ");

            $db->db_execute($sth, array(
               $pos,
               $np->netpath_idx,
               $this->get_current_host_profile(),
            ));

            $db->db_sth_free($sth);
            $pos++;
         }
      }

   } // update_positions()

   public function get_current_host_profile()
   {
      if(isset($_SESSION['host_profile']) && !empty($_SESSION['host_profile']))
         return $_SESSION['host_profile'];

      return 1;

   } // get_current_host_profile()

   /**
    * return global unique identifier
    *
    * original author
    * http://www.rodsdot.com/php/How-To-Obtain-A-GUID-Using-PHP-pseudo.php
    * @return string
    */
   public function create_guid()
   {
      mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
      $charid = strtoupper(md5(uniqid(rand(), true)));
      $hyphen = chr(45);// "-"
      $uuid = substr($charid, 0, 8).$hyphen
         .substr($charid, 8, 4).$hyphen
         .substr($charid,12, 4).$hyphen
         .substr($charid,16, 4).$hyphen
         .substr($charid,20,12);
      return $uuid;

   } // create_guid()

   public function add_task($job_cmd)
   {
      /* task_state's

         N = new
         R = running
         F = finished
         E = error/failed

      */

      global $db;

      $host_idx = $this->get_current_host_profile();

      if(!$this->is_valid_task($job_cmd))
         $ms->throwError('Invalid task '. $job_cmd .' submitted');


      /* if there is an RULES_UNLOAD request, we can remove
         any pending RULES_LOAD(_DEBUG) task that is not yet
         processed.
      */
      if($job_cmd == 'RULES_UNLOAD') {
         $db->db_query("
            DELETE FROM
               ". MYSQL_PREFIX ."tasks
            WHERE (
               task_job LIKE 'RULES_LOAD'
            OR
               task_job LIKE 'RULES_LOAD_DEBUG'
            ) AND (
               task_host_idx LIKE '". $host_idx ."'
            AND
               task_state LIKE 'N'
            )
         ");
      }

      $sth = $db->db_prepare("
         INSERT INTO ". MYSQL_PREFIX ."tasks (
            task_job,
            task_submit_time,
            task_run_time,
            task_host_idx,
            task_state
         ) VALUES (
            ?,
            ?,
            ?,
            ?,
            'N'
         ) ON DUPLICATE KEY UPDATE task_submit_time=UNIX_TIMESTAMP()
      ");

      $db->db_execute($sth, array(
         $job_cmd,
         mktime(),
         -1,
         $host_idx
      ));

   } // add_task()

   public function get_tasks()
   {
      global $db, $ms;

      $host_idx = $this->get_current_host_profile();

      if($this->is_running_task()) {
         $ms->_print("There is a running task");
         return false;
      }

      $sth = $db->db_prepare("
         SELECT
            task_idx,
            task_job,
            task_submit_time,
            task_run_time
         FROM
            ". MYSQL_PREFIX ."tasks
         WHERE
            task_state LIKE 'N'
         AND
            task_host_idx LIKE ?
         ORDER BY
            task_submit_time ASC
      ");

      $tasks = $db->db_execute($sth, array(
         $host_idx
      ));

      while($task = $tasks->fetchRow()) {
         $this->task_handler($task);
      }

   } // get_tasks()

   public function is_running_task($host_idx = NULL)
   {
      global $db;

      if(!isset($host_idx))
         $host_idx = $this->get_current_host_profile();

      $sth = $db->db_prepare("
         SELECT
            task_idx
         FROM
            ". MYSQL_PREFIX ."tasks
         WHERE
            task_state LIKE 'R'
         AND
            task_host_idx LIKE ?
         ORDER BY
            task_submit_time ASC
      ");

      $tasks = $db->db_execute($sth, array(
         $host_idx
      ));

      if($task = $tasks->fetchRow())
         return true;

      return false;

   } // is_running_task()

   private function task_handler($task)
   {
      global $ms;

      $this->set_task_state($task->task_idx, 'running');

      $ms->_print("Running task '". $task->task_job ."' submitted at ". strftime("%Y-%m-%d %H:%M:%S", $task->task_submit_time) .".", NULL, 1);

      switch($task->task_job) {
         case 'RULES_LOAD':
            require_once "class/rules/ruleset.php";
            require_once "class/rules/interface.php";
            $ruleset = new Ruleset;
            $retval = $ruleset->load(0);
            unset($ruleset);
            break;
         case 'RULES_LOAD_DEBUG':
            require_once "class/rules/ruleset.php";
            require_once "class/rules/interface.php";
            $ruleset = new Ruleset;
            $retval = $ruleset->load(1);
            unset($ruleset);
            break;
         case 'RULES_UNLOAD':
            require_once "class/rules/ruleset.php";
            require_once "class/rules/interface.php";
            $ruleset = new Ruleset;
            $retval = $ruleset->unload();
            unset($ruleset);
            break;
         default:
            $ms->throwError('Unknown task '. $task->task_job);
            break;
      }

      $this->set_task_state($task->task_idx, 'done', $retval);
      $this->_print(" Done. ". strftime("%Y-%m-%d %H:%M:%S", mktime()));

   } // task_handler()

   private function set_task_state($task_idx, $task_state, $retval = NULL)
   {
      global $db, $ms;

      if(!in_array($task_state, array('running', 'done')))
         $ms->throwError('Invalid task state '. $task_state);

      if(!is_numeric($task_idx) || $task_idx < 0)
         $ms->throwError('Invalid task index '. $task_idx);

      if($task_state == 'running')
         $task_state = 'R';
      if($task_state == 'done' && $retval == 0)
         $task_state = 'F';
      if($task_state == 'done' && $retval != 0)
         $task_state = 'E';

      $sth = $db->db_prepare("
         UPDATE
            ". MYSQL_PREFIX ."tasks
         SET
            task_state = ?,
            task_run_time = UNIX_TIMESTAMP()
         WHERE
            task_idx LIKE ?
      ");

      $db->db_execute($sth, array(
         $task_state,
         $task_idx
      ));

   } // set_task_state()

   public function is_valid_task($job_cmd)
   {
      switch($job_cmd) {
         case 'RULES_LOAD':
         case 'RULES_LOAD_DEBUG':
         case 'RULES_UNLOAD':
            return true;
      }

      return false;

   } // is_valid_task()

   /**
    * add a HTTP header to MasterShapers headers variable
    * that gets included when the template engine prints
    * out the document body.
    *
    * @return bool
    */
   public function set_header($key, $value)
   {
      $this->headers[$key] = $value;
      return true;

   } // set_header()

   /**
    * get a specific HTTP to be set by MasterShapers headers variable
    *
    * @return string
    */
   public function get_header($key)
   {
      if(!isset($this->headers[$key]))
         return NULL;

      return $this->headers[$key];

   } // get_header()

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
