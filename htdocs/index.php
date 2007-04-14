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

require_once 'shaper_db.php';
require_once 'shaper.class.php';

if(isset($_SERVER['argv'][1])) {

   switch($_SERVER['argv'][1]) {
   
      case 'load':

	 $shape = new MS(VERSION, 1);
	 $retval = $shape->ms_setup->enableConfig(2);
	 break;

      case 'unload':

         $shape = new MS(VERSION, 1);
	 $retval = $shape->ms_setup->enableConfig(4);
	 break;

      default:
	 $shape = new MS(VERSION, 0);
	 break;

   }
}
else {

   $shape = new MS(VERSION, 0);

}

if(isset($shape)) 
   $shape->cleanup();

if(isset($retval))
   exit($retval);

?>
