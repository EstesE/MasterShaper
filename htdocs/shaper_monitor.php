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

class MASTERSHAPER_MONITOR {

   var $db;
   var $parent;
   var $tmpl;

   /* Class constructor */
   function MASTERSHAPER_MONITOR($parent)
   {
      $this->db = $parent->db;
      $this->parent = $parent;
      $this->tmpl = $parent->tmpl;

   } // MASTERSHAPER_MONITOR()

   /* interface output */
   function show($mode)
   {
      /* If authentication is enabled, check permissions */
      if($this->parent->getOption("authentication") == "Y" &&
         !$this->parent->checkPermissions("user_show_monitor")) {
         $this->parent->printError("<img src=\"". ICON_HOME ."\" alt=\"home icon\" />&nbsp;Monitoring", "You do not have enough permissions to access this module!");
         return 0;
      }

      $_SESSION['mode'] = $mode;

      // graph URL
      $image_loc = WEB_PATH ."/shaper_graph.php";

      switch($_SESSION['mode']) {
         case 'chains':
            $view = "Chains";
            break;
         case 'pipes':
            $view = "Pipes";
            if(!isset($_SESSION['showchain']) || $_SESSION['showchain'] == -1)
               $_SESSION['showchain'] = $this->getFirstChain();
            break;
         case 'bandwidth':
            $view = "Bandwidth";
            break;
      }

      /* pre-set some variables with default values, if not yet set.
         shaper_graph.php is designed to stop execution, if this vars
         are not set.
      */
      if(!isset($_SESSION['showif'])) 
         $_SESSION['showif'] = $this->getFirstInterface();
      if(!isset($_SESSION['mode']))
         $_SESSION['mode'] = 'bandwidth';
      if(!isset($_SESSION['graphmode']))
         $_SESSION['graphmode'] = 0;
      if(!isset($_SESSION['showchain']))
         $_SESSION['showchain'] = -1;
      if(!isset($_SESSION['scalemode']))
         $_SESSION['scalemode'] = "kbit";

      $image_loc.= "?uniqid=". mktime();
   
      $this->tmpl->assign('monitor', $_SESSION['mode']);
      $this->tmpl->assign('view', $view);
      $this->tmpl->assign('image_loc', $image_loc);
      if(isset($_SESSION['graphmode']))
         $this->tmpl->assign('graphmode', $_SESSION['graphmode']);
      if(isset($_SESSION['scalemode']))
         $this->tmpl->assign('scalemode', $_SESSION['scalemode']);

      $this->tmpl->register_function("interface_select_list", array(&$this, "smarty_interface_select_list"), false);
      $this->tmpl->register_function("chain_select_list", array(&$this, "smarty_chain_select_list"), false);

      $this->tmpl->show("monitor.tpl");

   } // show()

   function getFirstChain()
   {
      // Get only chains which do not Ignore QoS and are active
      $chain = $this->db->db_fetchSingleRow("
         SELECT chain_idx
         FROM ". MYSQL_PREFIX ."chains
         WHERE
            chain_sl_idx!=0
         AND chain_active='Y'
         ORDER BY chain_position ASC
         LIMIT 0,1
      ");
      return $chain->chain_idx;

   } // getFirstChain()

   function getFirstInterface()
   {
      $interfaces = $this->parent->getActiveInterfaces();
      $if = $interfaces->fetchRow();
      return $if->if_name;

   } // getFirstInterface()

 
   public function smarty_chain_select_list($params, &$smarty)
   {
      // list only chains which do not Ignore QoS and are active
      $chains = $this->db->db_query("
         SELECT chain_idx, chain_name
         FROM ". MYSQL_PREFIX ."chains
         WHERE 
            chain_sl_idx!='0'
         AND
            chain_active='Y'
         AND
            chain_fallback_idx<>'0'
         ORDER BY chain_position ASC
      ");

      while($chain = $chains->fetchRow()) {
         $string.= "<option value=\"". $chain->chain_idx ."\">". $chain->chain_name ."</option>\n";
      }

      return $string;

   } // smarty_chain_select_list

   public function smarty_interface_select_list($params, &$smarty)
   {
      $interfaces = $this->parent->getActiveInterfaces();
      $if_select = "";

      while($interface = $interfaces->fetchRow()) {

         $if_select.= "<option value=\"". $interface->if_name ."\"";
	 
         if($_SESSION['showif'] == $interface->if_name)
            $if_select.= " selected=\"selected\"";
	    
         $if_select.= ">". $interface->if_name ."</option>\n";

      }

      return $if_select;

   } // smarty_interface_select_list()

}

?>
