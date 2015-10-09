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

namespace MasterShaper\Views;

class AboutView extends DefaultView
{
   /**
    * Page_About constructor
    *
    * Initialize the Page_About class
    */
   public function __construct()
   {

   } // __construct()

   public function showList()
   {
      global $tmpl;

      $tmpl->assign('version', VERSION);
      return $tmpl->fetch("about.tpl");

   } // show()

} // class Page_About

$obj = new Page_About;
$obj->handler();

?>
