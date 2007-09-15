<?php

/***************************************************************************
 *
 * Copyright (c) by Andreas Unterkircher, unki@netshadow.at
 * All rights reserved
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  any later version.
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

require 'smarty/libs/Smarty.class.php';

class MASTERSHAPER_TMPL extends Smarty {

   var $parent;

   public function __construct($parent)
   {
      $this->Smarty();
      $this->template_dir = BASE_PATH .'/templates';
      $this->compile_dir  = BASE_PATH .'/templates_c';
      $this->config_dir   = BASE_PATH .'/smarty_config';
      $this->cache_dir    = BASE_PATH .'/smarty_cache';

   } // __construct()

   public function show($template)
   {
      $this->display($template);

   } // show()

}

?>
