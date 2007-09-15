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

require_once "shaper_cfg.php";
require_once "shaper_db.php";
require_once "shaper_tmpl.php";

class MASTERSHAPER {

   var $cfg;
   var $db;

   /**
    * class constructor
    *
    * this function will be called on class construct
    * and will check requirements, loads configuration,
    * open databases and start the user session
    */
   public function __construct()
   {
      /* Check necessary requirements */
      if(!$this->checkRequirements()) {
         exit(1);
      }

      $this->cfg = new MASTERSHAPER_CFG("config.dat");
      $this->db  = new MASTERSHAPER_DB(&$this, $this->cfg->fspot_db);
      $this->tmpl = new MASTERSHAPER_TMPL($this);

      session_start();

   } // __construct()

   public function __destruct()
   {

   } // __destruct()

   /**
    * show - generate html output
    *
    * this function can be called after the constructor has
    * prepared everyhing. it will load the index.tpl smarty
    * template. if necessary it will registere pre-selects
    * (photo index, photo, tag search, date search) into
    * users session.
    */
   public function show()
   {
      $this->tmpl->assign('searchfor', $_SESSION['searchfor']);
      $this->tmpl->assign('page_title', $this->cfg->page_title);
      $this->tmpl->assign('current_condition', $_SESSION['tag_condition']);
      $this->tmpl->assign('template_path', 'themes/'. $this->cfg->theme_name);

      $_SESSION['start_action'] = $_GET['mode'];

      switch($_GET['mode']) {
         case 'showpi':
            if(isset($_GET['tags'])) {
               $_SESSION['selected_tags'] = $this->extractTags($_GET['tags']);
            }
            if(isset($_GET['from_date']) && $this->isValidDate($_GET['from_date'])) {
               $_SESSION['from_date'] = strtotime($_GET['from_date'] ." 00:00:00");
            }
            if(isset($_GET['to_date']) && $this->isValidDate($_GET['to_date'])) {
               $_SESSION['to_date'] = strtotime($_GET['to_date'] ." 23:59:59");
            }
            break;
         case 'showp':
            if(isset($_GET['tags'])) {
               $_SESSION['selected_tags'] = $this->extractTags($_GET['tags']);
               $_SESSION['start_action'] = 'showp';
            }
            if(isset($_GET['id']) && is_numeric($_GET['id'])) {
               $_SESSION['current_photo'] = $_GET['id'];
               $_SESSION['start_action'] = 'showp';
            }
            if(isset($_GET['from_date']) && $this->isValidDate($_GET['from_date'])) {
               $_SESSION['from_date'] = strtotime($_GET['from_date']);
            }
            if(isset($_GET['to_date']) && $this->isValidDate($_GET['to_date'])) {
               $_SESSION['to_date'] = strtotime($_GET['to_date']);
            }
            break;
         case 'export':
            $this->tmpl->show("export.tpl");
            return;
            break;
         case 'slideshow':
            $this->tmpl->show("slideshow.tpl");
            return;
            break;
      }

      $this->tmpl->show("index.tpl");

   } // show()

   /**
    * check if all requirements are met
    */
   private function checkRequirements()
   {
      if(!function_exists("imagecreatefromjpeg")) {
         print "PHP GD library extension is missing<br />\n";
         $missing = true;
      }

      if(!function_exists("sqlite3_open")) {
         print "PHP SQLite3 library extension is missing<br />\n";
         $missing = true;
      }

      /* Check for HTML_AJAX PEAR package, lent from Horde project */
      ini_set('track_errors', 1);
      @include_once 'HTML/AJAX/Server.php';
      if(isset($php_errormsg) && preg_match('/Failed opening.*for inclusion/i', $php_errormsg)) {
         print "PEAR HTML_AJAX package is missing<br />\n";
         $missing = true;
      }
      @include_once 'MDB2.php';
      if(isset($php_errormsg) && preg_match('/Failed opening.*for inclusion/i', $php_errormsg)) {
         print "PEAR MDB2 package is missing<br />\n";
         $missing = true;
      }
      ini_restore('track_errors');

      if(isset($missing))
         return false;

      return true;

   } // checkRequirements()

}

?>
