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

class MASTERSHAPER_CFG {

   var $parent;

   /**
    * MASTERSHAPER_CFG constructor
    *
    * Initialize the MASTERSHAPER_CFG class
    */
   public function __construct($parent, $file)
   {
      $this->parent = $parent;

      if(!file_exists($file)) {
         $this->parent->throwError("Can not locate MasterShaper's config file at: ". getcwd() ."/". $file);
         return false;
      }

      if(!is_readable($file)) {
         $this->parent->throwError("Can not read MasterShaper's config file at: ". getcwd() ."/". $file);
         return false;
      }

      if($this->readCfg($file))
         return true;

      /* unknown reror */
      return false;

   } // __construct()

   /* reads key=value pairs from config file */
   private function readCfg($file)
   {
      if(file_exists($file) && ($xml = simplexml_load_file($file)) !== false) {
         $vars = get_object_vars($xml);
         $keys = array_keys($vars);
         foreach($keys as $key) {
            define(strtoupper($key), $vars[$key]);
         }
         return true;
      }
      return false;

   } // readCfg()

   /* split key=value pair */
   private function getParams($line)
   {
      return split("=", $line);
   } // getParams()

} // class MASTERSHAPER_CFG

?>
