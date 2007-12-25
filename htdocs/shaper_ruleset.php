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

define("UNIDIRECTIONAL", 1);
define("BIDIRECTIONAL", 2);
define("TRUE", 1);
define("FALSE", 0);
define("IF_NOT_USED", -1);

define("MS_PRE", 10);
define("MS_POST", 13);

class MASTERSHAPER_RULESET {

   var $db;
   var $parent;
   var $tmpl;
   var $ms_pre;
   var $ms_post;
   var $classes;
   var $filters;
   var $interfaces;

   /* Class constructor */
   function MASTERSHAPER_RULESET($parent)
   {
      $this->db = $parent->db;
      $this->tmpl = $parent->tmpl;
      $this->parent = $parent;

      $this->ms_pre = Array();
      $this->ms_post = Array();
      $this->error  = Array();

      $this->classes = Array();
      $this->filters = Array();
      $this->interfaces = Array();

   } // MASTERSHAPER_RULESET()

   /* This function prepares the rule setup according configuration and calls tc with a batchjob */
   function show($state = 0)
   {
      /* If authentication is enabled, check permissions */
      if($this->parent->getOption("authentication") == "Y" &&
         !$this->parent->checkPermissions("user_show_rules")) {

         $this->parent->printError("<img src=\"". ICON_HOME ."\" alt=\"home icon\" />&nbsp;MasterShaper Ruleset - ". _("Show rules"), _("You do not have enough permissions to access this module!"));
         return 0;

      }

      $this->tmpl->register_function("ruleset_output", array(&$this, "smarty_ruleset_output"), false);
      $this->tmpl->show("ruleset_show.tpl");

   } // show

   /**
    * load MasterShaper ruleset
    */
   public function load($debug = null)
   {
      /* If authentication is enabled, check permissions */
      if($this->parent->getOption("authentication") == "Y" &&
         !$this->parent->checkPermissions("user_load_rules")) {

         $this->parent->printError("<img src=\"". ICON_HOME ."\" alt=\"home icon\" />&nbsp;MasterShaper Ruleset - ". _("Load rules"), _("You do not have enough permissions to access this module!"));
         return 0;
      }

      print _("Loading MasterShaper Ruleset");
      print _("Please wait...");

      $this->initRules();
      $retval = $debug ? $this->doItLineByLine() : $this->doIt();
      if(!$retval)
         $this->parent->setOption("reload_timestamp", mktime());

      return $retval;

   } // load()

   /**
    * unload MasterShaper ruleset
    */
   public function unload()
   {
      /* If authentication is enabled, check permissions */
      if($this->parent->getOption("authentication") == "Y" &&
         !$this->parent->checkPermissions("user_load_rules")) {

         $this->parent->printError("<img src=\"". ICON_HOME ."\" alt=\"home icon\" />&nbsp;MasterShaper Ruleset - Unload rules", "You do not have enough permissions to access this module!");
         return 0;

      }

      $this->delActiveInterfaceQdiscs();
      $this->delIptablesRules();

      print "Unloading MasterShaper Ruleset";
      $this->parent->setShaperStatus(false);
      
      return $retval;

   } // show()

   function iptInitRules()
   {
      $this->addRule(MS_PRE, IPT_BIN ." -t mangle -N ms-all");
      $this->addRule(MS_PRE, IPT_BIN ." -t mangle -N ms-all-chains");
      $this->addRule(MS_PRE, IPT_BIN ." -t mangle -N ms-prerouting");
      $this->addRule(MS_PRE, IPT_BIN ." -t mangle -A PREROUTING -j ms-prerouting");

      /* We must restore the connection mark in PREROUTING table first! */
      $this->addRule(MS_PRE, IPT_BIN ." -t mangle -A ms-prerouting -j CONNMARK --restore-mark");
      $this->addRule(MS_POST, IPT_BIN ." -t mangle -A ms-prerouting -j CONNMARK --save-mark");

   } // iptInitRules()

   function addRuleComment($ruleset, $text)
   {
      $this->addRule($ruleset, "######### ". $text);

   } // addRuleComment()

   function addRule($rule, $cmd)
   {

      switch($rule) {

         case MS_PRE:
	    array_push($this->ms_pre, $cmd);
	    break;

	 case MS_POST:
	    array_push($this->ms_post, $cmd);
	    break;

      }

   } // addRule()

   function getRules($rules)
   {

      switch($rules) {

         case MS_PRE:

	    return $this->ms_pre;
	    break;

	 case MS_POST:

	    return $this->ms_post;
	    break;

      }
      
   } // getRules()

   function initRules()
   {
      /* The most tc_ids will change, so we delete the current known tc_ids */
      $this->db->db_query("DELETE FROM ". MYSQL_PREFIX ."tc_ids");

      /* Initial iptables rules */
      if($this->parent->getOption("filter") == "ipt") 
         $this->iptInitRules();

      $netpaths = $this->getActiveNetpaths(); 

      while($netpath = $netpaths->fetchRow()) {

         $have_if2 = true;
         $do_nothing = false;

         if(!isset($this->interfaces[$netpath->netpath_if1])) 
            $this->interfaces[$netpath->netpath_if1] = new MASTERSHAPER_INTERFACE($netpath->netpath_if1, $this->db, $this->parent);
         if(!isset($this->interfaces[$netpath->netpath_if2])) 
            $this->interfaces[$netpath->netpath_if2] = new MASTERSHAPER_INTERFACE($netpath->netpath_if2, $this->db, $this->parent);

         /* get interface 2 parameters (if available) */
         if($netpath->netpath_if2 == IF_NOT_USED)
            $have_if2 = false;

         /* If a interface on this network path is inactive, ignore it completely */
         if($this->interfaces[$netpath->netpath_if1]->isActive() != "Y")
            $do_nothing = true;  
         if($have_if2 && $this->interfaces[$netpath->netpath_if2]->isActive() != "Y") 
            $do_nothing = true;  

         if(!$do_nothing) {

            $this->addRuleComment(MS_PRE, "Rules for Network Path ". $netpath->netpath_name);

            /* tc structure
               1: root qdisc
                1:1 root class (dev. bandwidth limit)
                 1:2
                 1:3
                 1:4
            */

            /* only initialize the interface if it isn't already */
            if(!$this->interfaces[$netpath->netpath_if1]->getStatus()) {
               $this->interfaces[$netpath->netpath_if1]->Initialize("in");
            }

            /* only initialize the interface if it isn't already */
            if($have_if2 && !$this->interfaces[$netpath->netpath_if2]->getStatus()) {
               $this->interfaces[$netpath->netpath_if2]->Initialize("out");
            }
		  
            if($netpath->netpath_imq == "Y") {
               $this->interfaces[$netpath->netpath_if1]->buildChains($netpath->netpath_idx, "in");
               if($have_if2)
                  $this->interfaces[$netpath->netpath_if2]->buildChains($netpath->netpath_idx, "out");
            }
            else {
               $this->interfaces[$netpath->netpath_if1]->buildChains($netpath->netpath_idx, "in");
               if($have_if2)
                  $this->interfaces[$netpath->netpath_if2]->buildChains($netpath->netpath_idx, "out");
            }
         }
      }

      return true;

   } // initRules()

   /* Delete parent qdiscs */
   function delQdisc($interface)
   {
      $this->runProc("tc", TC_BIN . " qdisc del dev ". $interface ." root", TRUE);

   } // delQdisc()

   function delIptablesRules()
   {
      $this->runProc("cleanup");

   } // delIptablesRules

   function doIt()
   {

      $error = Array();
      $found_error = 0;

      /* Delete current root qdiscs */
      $this->delActiveInterfaceQdiscs();

      $this->delIptablesRules();

      /* Prepare the tc batch file */
      $temp_tc  = tempnam (TEMP_PATH, "FOOTC");
      $output_tc  = fopen($temp_tc, "w");

      /* If necessary prepare iptables batch files */
      if($this->parent->getOption("filter") == "ipt") {
         $temp_ipt = tempnam (TEMP_PATH, "FOOIPT");
         $output_ipt = fopen($temp_ipt, "w");
      }

      foreach($this->getCompleteRuleset() as $line) {
         $line = trim($line);
         if(!preg_match("/^#/", $line)) {
            /* tc filter task */
            if(strstr($line, TC_BIN) !== false && $line != "") {
               $line = str_replace(TC_BIN ." ", "", $line);
               fputs($output_tc, $line ."\n");
	         }
            /* iptables task */
            if(strstr($line, IPT_BIN) !== false && $this->parent->getOption("filter") == "ipt") {
               fputs($output_ipt, $line ."\n");
            }
         }
      }

      /* flush batch files */
      fclose($output_tc);

      if($this->parent->getOption("filter") == "ipt")
         fclose($output_ipt);

      /* load tc filter rules */
      if(($error = $this->runProc("tc", TC_BIN . " -b ". $temp_tc)) != TRUE) {
         print _("Error on mass loading tc rules. Try load ruleset in debug mode to figure incorrect or not supported rule."); 
         $found_error = 1;
      }

      /* load iptables rules */
      if($this->parent->getOption("filter") == "ipt" && !$found_error) {
         if(($error = $this->runProc("iptables", $temp_ipt)) != TRUE) {
            print _("Error on mass loading iptables rule. Try load ruleset in debug mode to figure incorrect or not supported rule.");
            $found_error = 1;
         }
      }

      if(!$found_error) {
         print _("Shaping enabled");
      }

      unlink($temp_tc);
      if($this->parent->getOption("filter") == "ipt")
         unlink($temp_ipt);

      if(!$found_error)
         $this->parent->setShaperStatus(true);
      else
         $this->parent->setShaperStatus(false);

      return $found_error;

   } // doIt()

   function doItLineByLine()
   {
      /* Delete current root qdiscs */
      $this->delActiveInterfaceQdiscs();
      $this->delIptablesRules();

      $ipt_lines = array();

      foreach($this->getCompleteRuleset() as $line) {
         if(!preg_match("/^#/", $line)) {
            if(strstr($line, TC_BIN) !== false) {
            print $line."<br />\n";
               if(($tc = $this->runProc("tc", $line)) !== TRUE)
                  print $tc."<br />\n";
            }
            if(strstr($line, IPT_BIN) !== false) 
               array_push($ipt_lines, $line);
         }
         else {
            print $line."<br />\n";
         }
      }

      foreach($ipt_lines as $line) {
         print $line."<br />\n";
         if(($tc = $this->runProc("iptables", $line)) !== TRUE)
            print $tc."<br />\n";
      }

   } // doItLineByLine()

   function output($text)
   {
      if($_GET['output'] == "noisy")
         print $text ."\n";

   } // output()

   function getCompleteRuleset()
   {
      $ruleset = Array();
      foreach($this->ms_pre as $tmp) {
         array_push($ruleset, $tmp);
      }
      foreach($this->interfaces as $interface) {
         foreach($interface->getRules() as $rule) {
            array_push($ruleset, $rule);
         }
      }
      foreach($this->ms_post as $tmp) {
         array_push($ruleset, $tmp);
      }
      return $ruleset;
   
   } // getCompleteRuleset()

   function showIt()
   {
      $string = "";
      foreach($this->getCompleteRuleset() as $tmp) {
         foreach(split("\n", $tmp) as $line) {
         $line = trim($line);
         if($line != "")
            $string.= "<font style='color: ". $this->getColor($line) .";'>". $line ."</font><br />\n";
         }
      }
      
      return $string;

   } // showIt()

   function getColor($text)
   {
      if(strstr($text, "########"))
	 return "#666666";
      if(strstr($text, TC_BIN))
	 return "#AF0000";
      if(strstr($text, IPT_BIN))
	 return "#0000AF";

      return "#000000";

   } // getColor()

   function runProc($option, $cmd = "", $ignore_err = FALSE)
   {
      $desc = array(
         0 => array('pipe','r'), /* STDIN */
         1 => array('pipe','w'), /* STDOUT */
         2 => array('pipe','w'), /* STDERR */ 
      );

      $process = proc_open(SUDO_BIN ." ". BASE_PATH ."/shaper_loader.sh ". $option ." \"". $cmd ."\"", $desc, $pipes);

      if(is_resource($process)) {
   
         $stdin = $pipes[0];
         $stdout = $pipes[1];
         $stderr = $pipes[2];

         while(!feof($stdout)) {
            $retval.= trim(fgets($stdout));
         }
         while(!feof($stderr)) {
            $error.= trim(fgets($stderr));
         }

         fclose($pipes[0]);
         fclose($pipes[1]);
         fclose($pipes[2]);

         $exit_code = proc_close($process);

      }
   
      if(!empty($error) || $stdout != "OK" && !$ignore_err)
         throw new Exception($error);
      else
         print $retval;

   } // runProc()

   function delActiveInterfaceQdiscs()
   {
      $result = $this->parent->getActiveInterfaces();
      while($row = $result->fetchRow()) {
         $this->delQdisc($row->if_name);
      }

   } // delActiveInterfaceQdiscs()

   function getActiveNetpaths()
   {
      return $this->db->db_query("SELECT * FROM ". MYSQL_PREFIX ."network_paths WHERE netpath_active='Y' ORDER BY netpath_position");

   } // getActiveNetpaths()

   public function smarty_ruleset_output($params, &$smarty)
   {
      if($this->initRules()) {
         return $this->showIt();
      }

   } // smarty_ruleset_output()

}

?>
