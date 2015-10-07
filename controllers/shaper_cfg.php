<?php

/**
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
      return preg_split("/=/", $line);
   } // getParams()

} // class MASTERSHAPER_CFG

?>
