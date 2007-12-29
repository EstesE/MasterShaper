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

class MASTERSHAPER_ABOUT {

   private $parent;
   private $tmpl;
   
   /**
    * MASTERSHAPER_ABOUT constructor
    *
    * Initialize the MASTERSHAPER_ABOUT class
    */
   public function __construct(&$parent)
   {
      $this->parent = $parent;
      $this->tmpl = $parent->tmpl;

   } // __construct()

   public function show()
   {
      $this->tmpl->assign('version', VERSION);
      $this->tmpl->show("about.tpl");

   } // show()

} // class MASTERSHAPER_ABOUT

?>
