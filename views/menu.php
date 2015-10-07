<?php

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

class Page_Menu extends MASTERSHAPER_PAGE {

   /**
    * Page_Menu constructor
    *
    * Initialize the Page_Menu class
    */
   public function __construct()
   {

   } // __construct()

   /* interface output */
   public function get_sub_menu()
   {
      global $page, $tmpl, $ms;

      if($page->call_type != 'rpc') {
         $ms->throwError('Invalid call to get_sub_menu()');
         return false;
      }
      if($page->action != 'get-sub-menu') {
         $ms->throwError('Invalid call to get_sub_menu()');
         return false;
      }
      if(!isset($_POST['menuId']) || empty($_POST['menuId'])) {
         $ms->throwError('POST parameter menuId is not set.');
         return false;
      }

      $valid_menuIds = Array(
         'menu_overview',
         'menu_manage',
         'menu_settings',
         'menu_monitoring',
         'menu_rules',
         'menu_others',
      );

      if(!in_array($_POST['menuId'], $valid_menuIds)) {
         $ms->throwError('Unknown sub-menu id '. $_POST['menuId'] .' requested.');
         return false;
      }

      return $tmpl->fetch($_POST['menuId'] .'.tpl');

   } // get_sub_menu()

} // class Page_Menu

?>
