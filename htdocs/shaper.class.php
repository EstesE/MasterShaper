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

define('MS_CHAINS', 1);
define('MS_PIPES', 2);
define('MS_SERVICE_LEVELS', 3);
define('MS_TARGETS', 4);
define('MS_MONITOR', 5);
define('MS_OPTIONS', 6);
define('MS_RULES', 7);
define('MS_FILTERS', 8);
define('MS_PORTS', 9);
define('MS_PROTOCOLS', 10);
define('MS_ABOUT', 11);
define('MS_CONFIG_SAVE', 12);
define('MS_CONFIG_RESTORE', 13);
define('MS_CONFIG_RESET', 14);
define('MS_UPDATE_L7', 15);
define('MS_USERS', 16);
define('MS_INTERFACES', 17);
define('MS_NET_PATHS', 18);
define('MS_LOGOUT', 97);
define('MS_LOGIN', 98);
define('MS_OVERVIEW', 99);

define('MANAGE', 1);
define('DELETE', 2);
define('CHGSTATUS', 3);

include "shaper_config.php";
include "shaper_chains.php";
include "shaper_pipes.php";
include "shaper_options.php";
include "shaper_setup.php";
include "shaper_filters.php";
include "shaper_targets.php";
include "shaper_monitor.php";
include "shaper_service_levels.php";
include "shaper_ports.php";
include "shaper_protocols.php";
include "shaper_overview.php";
include "shaper_users.php";
include "shaper_interfaces.php";
include "shaper_net_paths.php";
include "shaper_about.php";
include "shaper_interface.php";

require_once "Net/IPv4.php";

class MS {

   var $db;
   var $version;
   var $fromcmd;
   var $self;
   var $mode;
   var $screen;
   var $language;
   var $navpoint;

   /* Class constructor */
   function MS($version, $fromcmd)
   {
      $this->version  = $version;
      $this->fromcmd  = $fromcmd;
      $this->self     = $_SERVER['PHP_SELF'];
      $this->mode     = 0;
      $this->screen   = 0;

      if(isset($_GET['mode']) && is_numeric($_GET['mode']))
	 $this->mode    = $_GET['mode'];
      if(isset($_GET['screen']) && is_numeric($_GET['screen']))
	 $this->screen  = $_GET['screen'];

      if(!function_exists("simplexml_load_file")) {

	 print("no PHP XML support");
	 die;

      }


      /* Read config, if not exists, send browser to MasterShaper Installer */
      $this->ms_config = new MSCONFIG($this);
      $this->ms_config->readCfg("config.dat") or Header("Location: setup/");
      $this->ms_config->readCfg("icons.dat");

      /* initalize database communication */
      $this->db = new MSDB($this);

      /* If software version not equal database stored version */
      if($this->db->getVersion() != $this->version)
	 Header("Location: setup/");

      /* If not all definitions are available */
      if(!defined('MYSQL_HOST') || !defined('MYSQL_DB') || !defined('MYSQL_USER') || !defined('MYSQL_PASS') || !defined('MYSQL_PREFIX') ||
	 !defined('SHAPER_PATH') || !defined('SHAPER_WEB') || !defined('TC_BIN') || !defined('IPT_BIN') ||
	 !defined('TEMP_PATH') || !defined('SUDO_BIN')) {

	 Header("Location: setup/");

      }

      if(!class_exists("Net_IPv4")) {

	 print("PHP Pear Net_IPv4 class not found!");
	 die;

      }

      if(!function_exists("gettext")) {

	 print("Can't find gettext support!");
	 die;

      }

      /* Set locales */
      //$this->language = ($_REQUEST['language'] == "" ? 'en_US' : $_REQUEST['language']); // Find out what language to use. en_US is the default.
      $this->language = $this->getOption("language"); 

      putenv("LANG=". $this->language); 
      setlocale(LC_MESSAGES, $this->language);
      $domain = 'messages';
      bindtextdomain($domain, "./locales"); // this is the directory path to your locale message files. see below how to generate these.
      textdomain($domain);
      bind_textdomain_codeset($domain, 'UTF-8');

      $this->ms_chains         = new MSCHAINS($this);
      $this->ms_pipes          = new MSPIPES($this);
      $this->ms_options        = new MSOPTIONS($this);
      $this->ms_filters        = new MSFILTERS($this);
      $this->ms_targets        = new MSTARGETS($this);
      $this->ms_setup          = new MSSETUP($this);
      $this->ms_monitor        = new MSMONITOR($this);
      $this->ms_service_levels = new MSSERVICELEVELS($this);
      $this->ms_ports          = new MSPORTS($this);
      $this->ms_protocols      = new MSPROTOCOLS($this);
      $this->ms_overview       = new MSOVERVIEW($this);
      $this->ms_about	       = new MSABOUT($this);
      $this->ms_users          = new MSUSERS($this);
      $this->ms_interfaces     = new MSINTERFACES($this);
      $this->ms_net_paths      = new MSNETPATHS($this);

      $this->startSession();

      /* if no new menu entry is selected, restore the current one from session data */
      if(!isset($_GET['navpoint']) && isset($_SESSION['navpoint'])) {

         $this->navpoint = $_SESSION['navpoint'];

      }

      /* if a new menu entry is selected, set active and store it in session data */
      if(isset($_GET['navpoint'])) {

         $this->navpoint = $_GET['navpoint'];
         $_SESSION['navpoint'] = $this->navpoint;

      }

      /* if we are not called from command line */
      if(!$this->fromcmd)
         $this->showHtml();

   } // MS()
	
   /* interface output */
   function showHtml()
   {
      if(!isset($this->mode))
         $this->mode = MS_OVERVIEW;

      /* If authentication is enabled, we have to login first! */
      if($this->getOption("authentication") == "Y") {
         
	 /* If user requests the "About"-Site, it can be serverd without login */
	 if(!$this->isSession() && $this->mode != MS_ABOUT) {

	    $this->mode = MS_LOGIN;

	 }
      }

      /* If we are saving the current config we do not show the header */
      if($this->mode != MS_CONFIG_SAVE)
	 $this->showMSHeader();

      switch($this->mode) {
	
	 case MS_CHAINS:
	    $this->ms_chains->showHtml();
	    break;
	 case MS_PIPES:
	    $this->ms_pipes->showHtml();	
	    break;
	 case MS_SERVICE_LEVELS:
	    $this->ms_service_levels->showHtml();
	    break;
	 case MS_TARGETS:
	    $this->ms_targets->showHtml();
	    break;
	 case MS_MONITOR:
	    $this->ms_monitor->showHtml();
	    break;
	 case MS_OPTIONS:
	    $this->ms_options->showHtml();
	    break;
	 case MS_RULES:
	    $this->ms_setup->enableConfig();
	    break;
	 case MS_FILTERS:
	    $this->ms_filters->showHtml();
	    break;
	 case MS_PORTS:
	    $this->ms_ports->showHtml();
	    break;
	 case MS_PROTOCOLS:
	    $this->ms_protocols->showHtml();
	    break;
	 case MS_ABOUT:
	    $this->ms_about->showHtml();
	    break;
	 case MS_CONFIG_SAVE:
	    $this->ms_options->saveConfig();
	    break;
	 case MS_CONFIG_RESTORE:
	    $this->ms_options->restoreConfig();
	    break;
	 case MS_CONFIG_RESET:
	    $this->ms_options->resetConfig();
	    break;
	 case MS_UPDATE_L7:
	    $this->ms_options->updateL7Protocols();
	    break;
	 case MS_USERS:
	    $this->ms_users->showHtml();
	    break;
	 case MS_INTERFACES:
	    $this->ms_interfaces->showHtml();
	    break;
	 case MS_NET_PATHS:
	    $this->ms_net_paths->showHtml();
	    break;
	 case MS_LOGOUT:
	    $this->destroySession();
	    break;
	 case MS_LOGIN:
	    $this->showLogin();
	    break;
	 default:
	 case MS_OVERVIEW:
	    $this->ms_overview->showHtml();
	    break;
      }

      /* If we are saving the current config we do not show the footer */
      if($this->mode != MS_CONFIG_SAVE)
	 $this->showMSFooter();

   } // showHtml()

   function cleanup()
   {
      unset($this->db);

   } // cleanup()

   /* show header of webpages (incl. menu) */
   function showMSHeader()
   {

      if(!is_dir(SHAPER_PATH ."/jpgraph"))
        die("Can't locate jpgraph in &lt;". SHAPER_PATH ."/jpgraph&gt;<br />Read MasterShaper Documentation about System Requirements!");

      Header("Content-Type: text/html; charset=UTF-8");
      print "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
 <head>
  <!-- to block this stupid msnbot from fetching the whole mastershaper sites -->
  <meta name="msnbot" content="noindex,nofollow" />
  <meta http-equiv="Content-Type" content="text/html; charset=charset=utf-8" />
  <title>MasterShaper <?php print $this->version; ?> - Traffic Shaping and QoS</title>
  <link rel="stylesheet" href="shaper_style.css" type="text/css" />
  <link rel="shortcut icon" href="favicon.ico" />
  <script type="text/javascript" src="shaper_rpc.php?mode=init&amp;client=all"></script>
  <script type="text/javascript" src="<?php print SHAPER_WEB; ?>/shaper.js"></script>
 </head>
<?php

      /* If we are drawing the login dialog, set the focus on the user name field */
      if($this->mode != MS_LOGIN) {
?>
 <body onLoad="updateSubMenu(HTML_AJAX.grab('shaper_rpc.php?mode=get&navpoint=<?php print $this->navpoint; ?>'));">
<?php
         $header_text = "MasterShaper ". $this->version;

         if($this->isSession()) {

            $header_text.= " - logged in as ". $_SESSION['user_name'] ." (<a href=\"". $_SERVER['PHP_SELF'] ."?mode=". MS_LOGOUT ."\" style=\"color: #ffffff;\">logout</a>)";

	 }

      }
      else {
?>
 <body onload="document.forms['login'].elements['user_name'].focus();">
<?php
         $header_text = "MasterShaper Login";
	 $this->closeTable();
      }
?>
  <!-- header cell -->
  <div style="height: 10px;"></div>
  <div style="width: 100%; height: 90px;">
   <img src="images/ms_logo.png">
  </div>
  <!-- /header cell -->
 
<?php

         $this->showPageTitle("<img src=\"". ICON_HOME ."\" alt=\"home icon\" />&nbsp;". $header_text);

?>

  <div id="menubox">
   <table class="menu">
    <tr>
     <td onmouseover="setBackGrdColor(this, 'mouseover');" onmouseout="setBackGrdColor(this, 'mouseout');">
      <a href="javascript:void(0)" onClick="updateSubMenu(HTML_AJAX.grab('shaper_rpc.php?mode=get&navpoint=overview')); location.href='<?php print $_SERVER['PHP_SELF'] ."?mode=99&amp;navpoint=overview"; ?>';" /><img src="icons/home.gif" />&nbsp;<?php print _("Overview"); ?></a>
     </td>
     <td onmouseover="setBackGrdColor(this, 'mouseover');" onmouseout="setBackGrdColor(this, 'mouseout');">
      <a href="javascript:updateSubMenu(HTML_AJAX.grab('shaper_rpc.php?mode=get&navpoint=manage'));" /><img src="icons/arrow_right.gif" />&nbsp;<?php print _("Manage"); ?></a>
     </td>
     <td onmouseover="setBackGrdColor(this, 'mouseover');" onmouseout="setBackGrdColor(this, 'mouseout');">
      <a href="javascript:updateSubMenu(HTML_AJAX.grab('shaper_rpc.php?mode=get&navpoint=settings'));" /><img src="icons/arrow_right.gif" />&nbsp;<?php print _("Settings"); ?></a>
     </td>
     <td onmouseover="setBackGrdColor(this, 'mouseover');" onmouseout="setBackGrdColor(this, 'mouseout');">
      <a href="javascript:updateSubMenu(HTML_AJAX.grab('shaper_rpc.php?mode=get&navpoint=monitoring'));" /><img src="icons/chart_pie.gif" />&nbsp;<?php print _("Monitoring"); ?></a>
     </td>
     <td onmouseover="setBackGrdColor(this, 'mouseover');" onmouseout="setBackGrdColor(this, 'mouseout');">
      <a href="javascript:updateSubMenu(HTML_AJAX.grab('shaper_rpc.php?mode=get&navpoint=rules'));" /><img src="icons/arrow_right.gif" />&nbsp;<?php print _("Rules"); ?></a>
     </td>
     <td onmouseover="setBackGrdColor(this, 'mouseover');" onmouseout="setBackGrdColor(this, 'mouseout');">
      <a href="javascript:updateSubMenu(HTML_AJAX.grab('shaper_rpc.php?mode=get&navpoint=others'));" /><img src="icons/arrow_right.gif" />&nbsp;<?php print _("Others"); ?></a>
     </td>
    </tr>
   </table>
  </div>

  <!-- grey border line below header cell -->
  <div style="background-color: #aaaaaa; height: 2px;"></div>

  <div id="submenubox">
   <table style="height: 30px;">
    <tr>
     <td>
      <div id="submenu"></div>
     </td>
    </tr>
   </table>
  </div>

  <!-- grey border line -->
  <div style="background-color: #aaaaaa; height: 2px;"></div>

  <div style="height: 30px;"></div>

  <!-- main cell -->
  <div id="main">

    <!-- module output -->


<?php

   } // showMSHeader

   function showMSFooter()
   {
?>


      <!-- /module output -->

      </div>

     <!-- /main cell -->

  <br /><br />
 </body>
</html>
<?php
   } // showMSFooter()

   function goBack()
   {
?>
 <script>
 <!--
    location.href='<?php print $this->self ."?mode=". $this->mode ."&saved=1"; ?>';
 -->
 </script>
<?php
   } // goBack()

   function goStart()
   {
?>
 <script>
 <!--
    location.href='<?php print $this->self; ?>';
 -->
 </script>
<?php
   } // goStart()

   function printError($title, $text)
   {
      $this->startTable($title);
?>
  <table style="width: 100%;" class="withborder2">
   <tr>
    <td class="sysmessage">
     <?php print $text; ?>
    </td>
   </tr>
   <tr>
    <td style="text-align: center;">
     <a href="javascript:history.go(-1);"><?php print _("Back"); ?></a>
    </td>
   </tr>
  </table>
<?php
      $this->closeTable();

   } // printError()

   function printYesNo($title, $text)
   {
      $this->startTable($title);
?>
  <table style="width: 100%;" class="withborder2"> 
   <tr>
    <td class="sysmessage">
     <?php print $text; ?>
    </td>
   </tr>
   <tr>
    <td style="text-align: center;">
     <a href="<?php print htmlentities($_SERVER['REQUEST_URI']) ."&amp;doit=1"; ?>"><?php print _("Yes"); ?></a>
      &nbsp;
     <a href="<?php print $this->self ."?mode=". $this->mode; ?>"><?php print _("No"); ?></a>
    </td>
   </tr>
  </table>
<?php
      $this->closeTable();

   } // printYesNo()

   function getOption($object)
   {
      $result = $this->db->db_fetchSingleRow("SELECT setting_value FROM ". MYSQL_PREFIX ."settings WHERE setting_key like '". $object ."'");
      return $result->setting_value;
   } // getOption()

   function setOption($key, $value)
   {
      $this->db->db_query("REPLACE INTO ". MYSQL_PREFIX ."settings (setting_key, setting_value) VALUES ('". $key ."', '". $value ."')");
   } // setOption()	

   function getServiceLevelName($sl_idx)
   {
      $result = $this->db->db_fetchSingleRow("SELECT sl_name FROM ". MYSQL_PREFIX ."service_levels WHERE sl_idx='". $sl_idx ."'");
      return $result->sl_name;
   } // getServiceLevelName()

   /* returns all service level parameters for specific service level */
   function getServiceLevel($sl_idx)
   {

      return $this->db->db_fetchSingleRow("SELECT * FROM ". MYSQL_PREFIX ."service_levels WHERE sl_idx='". $sl_idx ."'");

   } // getServiceLevel()

   function getTargetName($target_idx)
   {
      if($target_idx != 0) {
	 $result = $this->db->db_fetchSingleRow("SELECT target_name FROM ". MYSQL_PREFIX ."targets WHERE target_idx='". $target_idx ."'");
	 return $result->target_name;
      }
      else
         return "any";

   } // getTargetName()

   function getPipeDirectionName($direction)
   {

      switch($direction) {

         case 1: return _("inbound"); break;
	 case 2: return _("outbound"); break;
	 case 3: return _("inbound &amp; outbound"); break;
	
      }
 
   } // getPipeDirectionName()

   function getChainDirectionName($direction)
   {

      switch($direction) {

         case 1: return _("Unidirectional"); break;
	 case 2: return _("Bidirectional"); break;
	
      }
 
   } // getChainDirectionName()

   function getChainName($chain_idx)
   {
      $result = $this->db->db_fetchSingleRow("SELECT chain_name FROM ". MYSQL_PREFIX ."chains WHERE chain_idx='". $chain_idx ."'");
      return $result->chain_name;
      
   } // getChainName()

   function getProtocolById($proto_idx)
   {
      if($proto = $this->db->db_fetchSingleRow("SELECT proto_name FROM ". MYSQL_PREFIX ."protocols WHERE proto_idx LIKE '". $proto_idx ."'"))
         return $proto->proto_name;

   } // getProtocolById()

   function getProtocolNumberById($proto_idx)
   {
      if($proto = $this->db->db_fetchSingleRow("SELECT proto_number FROM ". MYSQL_PREFIX ."protocols WHERE proto_idx LIKE '". $proto_idx ."'"))
         return $proto->proto_number;
      else
         return 0;

   } // getProtocolNumberById()
	 
   function getProtocolByName($proto_name)
   {
      if($proto = $this->db->db_fetchSingleRow("SELECT proto_idx FROM ". MYSQL_PREFIX ."protocols WHERE proto_name LIKE '". $proto_name ."'"))
	 return $proto->proto_idx;
      else
	 return false;

   } // getProtocolByName()

   function getPortByName($port_name)
   {
      if($port = $this->db->db_fetchSingleRow("SELECT port_idx FROM ". MYSQL_PREFIX ."ports WHERE port_name LIKE '". $port_name ."'"))
	 return $port->port_idx;
      else
	 return false;

   } // getPortByName()

   /* return a list for all ports in a filter definition */
   function getPorts($filter_idx)
   {
      $list = NULL;
      $numbers = "";

      $ports = $this->db->db_query("SELECT afp_port_idx FROM ". MYSQL_PREFIX ."assign_ports WHERE afp_filter_idx='". $filter_idx ."'");

      while($port = $ports->fetchRow()) {

	 $numbers.= $port->afp_port_idx .",";

      }

      if($numbers != "") {

	 $numbers = substr($numbers, 0, strlen($numbers)-1);
	 $list = $this->db->db_query("SELECT port_name, port_number FROM ". MYSQL_PREFIX ."ports WHERE port_idx IN (". $numbers .")");

      }

      return $list;

   } // getPorts()

   /* return a list for all layer7 protocols in a filter definition */
   function getL7Protocols($filter_idx)
   {

      $list = NULL;
      $numbers = "";

      $protocols = $this->db->db_query("SELECT afl7_l7proto_idx FROM ". MYSQL_PREFIX ."assign_l7_protocols WHERE afl7_filter_idx='". $filter_idx ."'");

      while($protocol = $protocols->fetchRow()) {

	 $numbers.= $protocol->afl7_l7proto_idx .",";

      }

      if($numbers != "") {

	 $numbers = substr($numbers, 0, strlen($numbers)-1);
	 $list = $this->db->db_query("SELECT l7proto_name FROM ". MYSQL_PREFIX ."l7_protocols WHERE l7proto_idx IN (". $numbers .")");

      }

      return $list;

   } // getL7Protocols

   function getServiceLevelByName($sl_name)
   {
      if($sl = $this->db->db_fetchSingleRow("SELECT sl_idx FROM ". MYSQL_PREFIX ."service_levels WHERE sl_name LIKE '". $sl_name ."'"))
	 return $sl->sl_idx;
      else
	 return false;

   } // getServiceLevelByName()

   function getTargetByName($target_name)
   {
      if($target = $this->db->db_fetchSingleRow("SELECT target_idx FROM ". MYSQL_PREFIX ."targets WHERE target_name LIKE '". $target_name ."'"))
	 return $target->target_idx;
      else
	 return false;

   } // getTargetByName()

   function getChainByName($chain_name)
   {
      if($chain = $this->db->db_fetchSingleRow("SELECT chain_idx FROM ". MYSQL_PREFIX ."chains WHERE chain_name LIKE '". $chain_name ."'"))
	 return $chain->chain_idx;
      else
	 return false;

   } // getChainByName()

   function getFilterByName($filter_name)
   {
      if($serv = $this->db->db_fetchSingleRow("SELECT filter_idx FROM ". MYSQL_PREFIX ."filters WHERE filter_name LIKE '". $filter_name ."'"))
	 return $serv->filter_idx;
      else
	 return false;

   } // getFilterByName()

   /* get all filters which are assigned to a pipe */
   function getFilters($pipe_idx)
   {

      return $this->db->db_query("SELECT a.apf_filter_idx as apf_filter_idx FROM ". MYSQL_PREFIX ."assign_filters a, ". MYSQL_PREFIX ."filters b WHERE "
	 ."a.apf_pipe_idx='". $pipe_idx ."' AND a.apf_filter_idx=b.filter_idx AND b.filter_active='Y'");

   } // getFilters()

   /* returns filter details */
   function getFilterDetails($filter_idx)
   {

      return $this->db->db_fetchSingleRow("SELECT * FROM ". MYSQL_PREFIX ."filters WHERE filter_idx='". $filter_idx ."'");

   } // getFilterDetails()

   function extract_tc_stat($line, $limit_to = "")
   {
      $pairs = Array();
      $pairs = split(',', $line);

      foreach($pairs as $pair) {
	 list($key, $value) = split('=', $pair);

	 if(preg_match("/". $limit_to ."/", $key)) {

	    $key = preg_replace("/". $limit_to ."/", "", $key);

	    if($key == "")
	       $key = 0;
				
	    if($value >= 0)
	       $data[$key] = $value;
	    else
	       $data[$key] = 0;
	 }
      }

      return $data;

   } // extract_tc_stat()

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

   function getNETEMParams($sl)
   {
      if($sl->sl_netem_delay != "" && is_numeric($sl->sl_netem_delay)) {

	 $params.= "delay ". $sl->sl_netem_delay ."ms ";

	 if($sl->sl_netem_jitter != "" && is_numeric($sl->sl_netem_jitter)) {

	    $params.= $sl->sl_netem_jitter ."ms ";

	    if($sl->sl_netem_random != "" && is_numeric($sl->sl_netem_random)) {

	       $params.= $sl->sl_netem_random ."% ";

	    }

	 }

	 if($sl->sl_netem_distribution != "ignore") {

	    $params.= "distribution ". $sl->sl_netem_distribution ." ";

	 }

      }

      if($sl->sl_netem_loss != "" && is_numeric($sl->sl_netem_loss)) {

	 $params.= "loss ". $sl->sl_netem_loss ."% ";

      }

      if($sl->sl_netem_duplication != "" && is_numeric($sl->sl_netem_duplication)) {

	 $params.= "duplicate ". $sl->sl_netem_duplication ."% ";

      }

      if($sl->sl_netem_gap != "" && is_numeric($sl->sl_netem_gap)) {

	 $params.= "gap ". $sl->sl_netem_gap ." ";

      }

      if($sl->sl_netem_reorder_percentage != "" && is_numeric($sl->sl_netem_reorder_percentage)) {

	 $params.= "reorder ". $sl->sl_netem_reorder_percentage ."% ";

	 if($sl->sl_netem_reorder_correlation  != "" && is_numeric($sl->sl_netem_reorder_correlation )) {

	    $params.= $sl->sl_netem_reorder_correlation ."% ";

	 }

      }

      return $params;

   } // getNETEMParams()

   function getESFQParams($sl)
   {

      $params = "";

      if($sl->sl_esfq_perturb != "" && is_numeric($sl->sl_esfq_perturb))
	 $params.= "perturb ". $sl->sl_esfq_perturb ." ";

      if($sl->sl_esfq_limit != "" && is_numeric($sl->sl_esfq_limit))
	 $params.= "limit ". $sl->sl_esfq_limit ." ";

      if($sl->sl_esfq_depth != "" && is_numeric($sl->sl_esfq_depth))
	 $params.= "depth ". $sl->sl_esfq_depth ." ";

      if($sl->sl_esfq_divisor != "" && is_numeric($sl->sl_esfq_divisor))
	 $params.= "divisor ". $sl->sl_esfq_divisor ." ";

      if($sl->sl_esfq_hash != "")
	 $params.= "hash ". $sl->sl_esfq_hash;

      return $params;

   } // getESFQParams()

   function getConnmarkId($string1, $string2)
   {

      return "0x". dechex(crc32($string1 . str_replace(":", "", $string2))* -1);

   } // getConnmarkId()

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

   function getYearList($current = "")
   {
      $string = "";
      for($i = date("Y"); $i <= date("Y")+2; $i++) {
	 $string.= "<option value=\"". $i ."\"";
         if($i == $current)
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
	 if($i == $current)
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
         if($i == $current)
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
	 if($i == $current)
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
	 if($i == $current)
	    $string.= " selected=\"selected\"";
	 if(date("i") == $i && $current == "")
	    $string.= " selected=\"selected\"";
	 $string.= ">". sprintf("%02d", $i)  ."</option>";
      }
      return $string;
      
   } // getMinuteList()

   function getL7ProtocolByName($l7proto_name)
   {

      if($l7proto = $this->db->db_fetchSingleRow("SELECT l7proto_idx FROM ". MYSQL_PREFIX ."l7_protocols WHERE l7proto_name LIKE '". $l7proto_name ."'"))
	 return $l7proto->l7proto_idx;
      else
	 return false;

   } // getL7ProtocolByName()

   function startTable($title)
   {
?>
  <div style="background-color: #aaaaaa; height: 2px;"></div>
  <div style="height: 20px; color: #FFFFFF; background-color: #174581; vertical-align: middle;" class="tablehead">
   <table style="height: 20px">
    <tr>
     <td style="width: 5px;"></td>
     <td class="tabletitle">
      <?php print $title; ?>
     </td>
    </tr>
   </table>
  </div>
  <div style="background-color: #aaaaaa; height: 2px;"></div>
<?php

   } // startTable()

   function closeTable()
   {
?>
<?php
   } // closeTable()

   function showPageTitle($title)
   {
?>
  <div style="background-color: #aaaaaa; height: 2px;"></div>
  <div style="height: 30px; color: #FFFFFF; background-color: #174581; vertical-align: middle;" class="tablehead">
   <table style="height: 30px">
    <tr>
     <td style="width: 15px;"></td>
     <td style="vertical-align: middle;">
      <?php print $title; ?>
     </td>
    </tr>
   </table>
  </div>
  <div style="background-color: #aaaaaa; height: 2px;"></div>
<?php
   } // showPageTitle()

   function isSession()
   {

      if(isset($_SESSION['user_name']) && isset($_SESSION['user_idx'])) 
         return true;
      else
         return false;

   } // isSession()

   function startSession()
   {

      session_name("MASTERSHAPER");
      session_start();

   } // startSession()

   function destroySession()
   {
      unset($_SESSION['user_name']);
      unset($_SESSION['user_idx']);
      unset($_SESSION['navpoint']);

      session_destroy();

      $this->goStart();

   } // destroySession()

   function showLogin()
   {

      if(!isset($_GET['proceed'])) {

?>
<form action="<?php print $this->self ."?proceed=1"; ?>" method="POST" id='login'>
 <table style="width: 100%;">
  <tr>
   <td>
    <table class="withborder2" style="margin-left:auto; margin-right:auto; text-align: center;">
     <tr>
      <td>
       <?php print _("User:"); ?>
      </td>
      <td>
       <input type="text" name="user_name" size="15" />
      </td>
     </tr>
     <tr>
      <td>
       <?php print _("Password:"); ?>
      </td>
      <td>
       <input type="password" name="user_pass" size="15" />
      </td>
     </tr>
     <tr>
      <td>
       &nbsp;
      </td>
      <td>
       <input type="submit" value="Login" />
      </td>
     </tr>
    </table>
   </td>
  </tr>
 </table>
</form>
<?php

      }
      else {

         if(isset($_POST['user_name']) && $_POST['user_name'] != "" &&
	    isset($_POST['user_pass']) && $_POST['user_pass'] != "") {

            if($user = $this->getUserDetails($_POST['user_name'])) {

	       if($user->user_pass == md5($_POST['user_pass'])) {

	          $_SESSION['user_name'] = $_POST['user_name'];
		  $_SESSION['user_idx'] = $user->user_idx;

		  $this->goStart();

	       }
	       else
	          $this->printError("<img src=\"". ICON_HOME ."\" alt=\"home icon\" />&nbsp;". _("MasterShaper Login"), _("Invalid Password."));

            }
	    else
	       $this->printError("<img src=\"". ICON_HOME ."\" alt=\"home icon\" />&nbsp;". _("MasterShaper Login"), _("Invalid or inactive User."));

	 }
	 else {

	    $this->printError("<img src=\"". ICON_HOME ."\" alt=\"home icon\" />&nbsp;". _("MasterShaper Login"), _("Please enter Username and Password."));

	 }
      
      }

   } // showLogin()

   function getUserDetails($user_name)
   {

      if($user = $this->db->db_fetchSingleRow("SELECT user_idx, user_pass FROM ". MYSQL_PREFIX ."users WHERE "
                    ."user_name LIKE '". $user_name ."' AND "
		    ."user_active='Y'"))

         return $user;
      else
         return NULL;

   } // getUserDetails()

   function checkPermissions($permission)
   {

      $user = $this->db->db_fetchSingleRow("SELECT ". $permission ." FROM ". MYSQL_PREFIX ."users WHERE "
         ."user_idx='". $_SESSION['user_idx'] ."'");

      if($user->$permission == "Y")
         return true;
      else
         return false;

   } // checkPermissions()

   function setShaperStatus($status)
   {

      $this->setOption("status", $status);

   } // setShaperStatus()

   function getShaperStatus()
   {

      return $this->getOption("status");

   } // getShaperStatus()

   function validateBandwidth($bw)
   {
      
      if(!is_numeric($bw)) {

	 if(preg_match("/^(\d+)(k|m)$/i", $bw))
	    return true;

      }
      else
         return true;

      return false;

   } // validateBandwidth()

   function getKbit($bw)
   {

      if(preg_match("/^(\d+)k$/i", $bw))
         return preg_replace("/k/i", "", $bw);
      if(preg_match("/^(\d+)m$/i", $bw))
         return (preg_replace("/m/i", "", $bw) * 1024);

      return $bw;

   }

   function getInterfaceName($if_idx)
   {

      if($if = $this->db->db_fetchSingleRow("SELECT if_name FROM ". MYSQL_PREFIX ."interfaces WHERE if_idx='". $if_idx ."'"))
         return $if->if_name;
      else
         return NULL;

   } // getInterfaceName()

   function getActiveInterfaces()
   {

      $result = $this->db->db_query("SELECT * FROM ". MYSQL_PREFIX ."interfaces WHERE if_active='Y'");

      return $result;

   } // getActiveInterfaces()

   function showSaveButton()
   {
?>
     <!-- save button -->
     <table>
      <tr>
       <td style="width: 25px;" />
       <td style="height: 50px;" />
        <table>
	 <tr>
	  <td style="height: 25px;" />
	 </tr>
         <tr>
	  <td>
	   <table style="border: 1px #AAAAAA solid;">
	    <tr>
	     <td style="padding: 15px;">
	      <input type="image" src="icons/disk.gif" />
	     </td>
             <td style="padding: 15px;">
              <input type="submit" value="<?php print _("Save"); ?>" />
             </td>
            </tr>
	   </table>
	  </td>
	 </tr>
  	 <tr>
	  <td style="height: 10px;" />
	 </tr>
        </table>
       </td>
      </tr>
     </table>
     <!-- /save button -->
<?php
   }

   function getHTTPVar($name)
   {

      if(isset($_GET[$name]))

         return $_GET[$name];

      else

         return '';

   } // getHTTPVar()

   function getClassVar($class, $name)
   {

      if(isset($class->$name))
      
         return $class->$name;

      else

         return '';

   } // getClassVar();

}

?>
