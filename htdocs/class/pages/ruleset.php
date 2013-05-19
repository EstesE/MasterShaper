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

require_once "class/rules/ruleset.php";
require_once "class/rules/interface.php";

class Page_Ruleset extends MASTERSHAPER_PAGE {

   /**
    * Page_Ruleset constructor
    *
    * Initialize the Page_Ruleset class
    */
   public function __construct()
   {
      //$this->rights = 'user_manage_rules';

   } // __construct()

   public function handler()
   {
      global $tmpl, $page, $ms;

      if(isset($this->rights) && !$ms->is_cmdline()) {
         /* If authentication is enabled, check permissions */
         if($ms->getOption("authentication") == "Y" && !$ms->checkPermissions($this->rights)) {
            $ms->throwError("<img src=\"". ICON_CHAINS ."\" alt=\"chain icon\" />&nbsp;". _("Manage Chains"), _("You do not have enough permissions to access this module!"));
            return 0;
         }
      }

      switch($page->action) {
         default:
         case 'show':
            $content = $this->show();
            break;
         case 'load':
            $content = $this->load();
            break;
         case 'load-debug':
            $content = $this->load(DEBUG);
            break;
         case 'unload':
            $content = $this->unload();
            break;
      }

      if(isset($content))
         $tmpl->assign('content', $content);

   } // handler()

   /* This function prepares the rule setup according configuration and calls tc with a batchjob */
   public function show($state = 0)
   {
      global $ms, $tmpl;

      /* If authentication is enabled, check permissions */
      if($ms->getOption("authentication") == "Y" &&
         !$ms->checkPermissions("user_show_rules")) {

         $ms->printError("<img src=\"". ICON_HOME ."\" alt=\"home icon\" />&nbsp;MasterShaper Ruleset - ". _("Show rules"), _("You do not have enough permissions to access this module!"));
         return 0;

      }

      $tmpl->registerPlugin("function", "ruleset_output", array(&$this, "smarty_ruleset_output"), false);
      return $tmpl->fetch("ruleset_show.tpl");

   } // show

   /**
    * load MasterShaper ruleset
    */
   public function load($debug = null)
   {
      global $ms;

      /* If authentication is enabled, check permissions */
      if(!$ms->is_cmdline() && $ms->getOption("authentication") == "Y" &&
         !$ms->checkPermissions("user_load_rules")) {

         $ms->printError("<img src=\"". ICON_HOME ."\" alt=\"home icon\" />&nbsp;MasterShaper Ruleset - ". _("Load rules"), _("You do not have enough permissions to access this module!"));
         return 0;
      }

      if(!isset($debug))
         $ms->add_task('RULES_LOAD');
      else
         $ms->add_task('RULES_LOAD_DEBUG');

      return "Ruleset load task submitted to job queue.";

   } // load()

   /**
    * unload MasterShaper ruleset
    */
   public function unload()
   {
      global $ms;

      /* If authentication is enabled, check permissions */
      if($ms->getOption("authentication") == "Y" &&
         !$ms->checkPermissions("user_load_rules")) {

         $ms->printError("<img src=\"". ICON_HOME ."\" alt=\"home icon\" />&nbsp;MasterShaper Ruleset - Unload rules", "You do not have enough permissions to access this module!");
         return 0;

      }

      $retval = $ms->add_task('RULES_UNLOAD');

      return "Ruleset unload task submitted to job queue.";
      
   } // unload()

   public function smarty_ruleset_output($params, &$smarty)
   {
      $ruleset = new Ruleset;

      if($ruleset->initRules()) {
         return $ruleset->showIt();
      }

   } // smarty_ruleset_output()

} // class Page_Ruleset

$obj = new Page_Ruleset;
$obj->handler();

?>
