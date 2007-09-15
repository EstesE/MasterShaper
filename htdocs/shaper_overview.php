<?php

/***************************************************************************
 *
 * Copyright (c) by Andreas Unterkircher
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

define('MANAGE_POS_CHAINS', 1);
define('MANAGE_POS_PIPES', 2);
define('MANAGE_POS_NETPATHS', 3);

class MASTERSHAPER_OVERVIEW {

   var $db;
   var $parent;

   /* Class constructor */
   function MASTERSHAPER_OVERVIEW($parent)
   {
      $this->db = $parent->db;
      $this->parent = $parent;

   } //MASTERSHAPER_OVERVIEW()

   /* interface output */
   function show()
   {
      /* If authentication is enabled, check permissions */
      if($this->parent->getOption("authentication") == "Y" &&
         !$this->parent->checkPermissions("user_show_rules")) {

         $this->parent->printError("<img src=\"". ICON_HOME ."\" alt=\"home icon\" />&nbsp;". _("MasterShaper Ruleset Overview"), _("You do not have enough permissions to access this module!"));
         return 0;
      }

      $this->parent->tmpl->register_function("start_table", array(&$this, "smarty_startTable"), false);
      $this->parent->tmpl->show("overview.tpl");
      return;

   } // show()

   public function smarty_startTable($params, &$smarty)
   {
      $this->parent->tmpl->assign('title', $params['title']);
      $this->parent->tmpl->assign('icon', $params['icon']);
      $this->parent->tmpl->assign('alt', $params['alt']);
      $this->parent->tmpl->show('start_table.tpl');

   } // smarty_function_startTable()

}

?>
