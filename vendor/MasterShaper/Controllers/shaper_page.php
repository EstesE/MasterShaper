<?php

die("Don't call me!");

/**
 *
 * This file is part of MasterShaper.

 * MasterShaper, a web application to handle Linux's traffic shaping
 * Copyright (C) 2015 Andreas Unterkircher <unki@netshadow.net>

 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.

 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.

 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

die("Do not load me!");

class MASTERSHAPER_PAGE {

   public $rights;

   public function handler()
   {
      global $tmpl, $page, $ms;

      if(isset($this->rights)) {
         /* If authentication is enabled, check permissions */
         if($ms->getOption("authentication") == "Y" && !$ms->checkPermissions($this->rights)) {
            $ms->throwError("<img src=\"". ICON_CHAINS ."\" alt=\"chain icon\" />&nbsp;". _("Manage Chains"), _("You do not have enough permissions to access this module!"));
            return 0;
         }
      }

      switch($page->action) {
         case 'overview':
         case 'chains':
         case 'pipes':
         case 'bandwidth':
         case 'options':
         case 'about':
         case 'tasklist':
         case 'update-iana':
         case 'list':
            $content = $this->showList();
            break;
         case 'edit':
         case 'new':
            $content = $this->showEdit();
            break;
      }

      if($ms->get_header('Location')) {
         Header('Location: '. $ms->get_header('Location'));
         return false;
      }

      if(isset($content))
         $tmpl->assign('content', $content);

   } // handler()

   /**
    * returns true if storing is requested
    *
    * @return bool
    */
   public function is_storing()
   {
      if(!isset($_POST['action']) || empty($_POST['action']))
         return false;

      if($_POST['action'] == 'store')
         return true;

      return false;

   } // is_storing()

} // MASTERSHAPER_PAGE()
