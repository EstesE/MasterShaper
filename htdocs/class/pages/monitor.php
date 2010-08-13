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

class Page_Monitor extends MASTERSHAPER_PAGE {

   private $total;
   private $names;

   /**
    * Page_Monitor constructor
    *
    * Initialize the Page_Monitor class
    */
   public function __construct()
   {
      $this->rights = 'user_show_monitor';

      $this->total = Array();
      $this->names = Array();

   } // __construct()

   /* interface output */
   public function showList()
   {
      global $tmpl, $page;

      if(isset($_POST['view']))
         $_SESSION['mode'] = $_POST['view'];

      if(isset($page->action))
         $_SESSION['mode'] = $page->action;

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
         case 'chains-jqPlot':
            $view = "Chains (jqPlot)";
            break;
         case 'pipes-jqPlot':
            $view = "Pipes (jqPlot)";
            break;
         case 'bandwidth-jqPlot':
            $view = "Bandwidth (jqPlot)";
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
   
      $tmpl->assign('monitor', $_SESSION['mode']);
      $tmpl->assign('view', $view);
      $tmpl->assign('image_loc', $image_loc);
      if(isset($_SESSION['graphmode']))
         $tmpl->assign('graphmode', $_SESSION['graphmode']);
      if(isset($_SESSION['scalemode']))
         $tmpl->assign('scalemode', $_SESSION['scalemode']);

      $tmpl->register_function("interface_select_list", array(&$this, "smarty_interface_select_list"), false);
      $tmpl->register_function("chain_select_list", array(&$this, "smarty_chain_select_list"), false);

      return $tmpl->fetch("monitor.tpl");

   } // showList()

   private function getFirstChain()
   {
      global $db;

      // Get only chains which do not Ignore QoS and are active
      $chain = $db->db_fetchSingleRow("
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

   private function getFirstInterface()
   {
      global $ms;

      $interfaces = $ms->getActiveInterfaces();
      $if = $interfaces->fetchRow();
      return $if->if_name;

   } // getFirstInterface()

 
   public function smarty_chain_select_list($params, &$smarty)
   {
      global $db;

      // list only chains which do not Ignore QoS and are active
      $chains = $db->db_query("
         SELECT
            chain_idx,
            chain_name
         FROM
            ". MYSQL_PREFIX ."chains
         WHERE 
            chain_sl_idx!='0'
         AND
            chain_active='Y'
         AND
            chain_fallback_idx<>'0'
         ORDER BY
            chain_position ASC
      ");

      while($chain = $chains->fetchRow()) {
         $string.= "<option value=\"". $chain->chain_idx ."\">". $chain->chain_name ."</option>\n";
      }

      return $string;

   } // smarty_chain_select_list

   public function smarty_interface_select_list($params, &$smarty)
   {
      global $ms;

      $interfaces = $ms->getActiveInterfaces();
      $if_select = "";

      while($interface = $interfaces->fetchRow()) {

         $if_select.= "<option value=\"". $interface->if_name ."\"";
	 
         if($_SESSION['showif'] == $interface->if_name)
            $if_select.= " selected=\"selected\"";
	    
         $if_select.= ">". $interface->if_name ."</option>\n";

      }

      return $if_select;

   } // smarty_interface_select_list()

   public function get_jqplot_values()
   {
      global $db;

      /* ****************************** */
      /* graphmode                      */
      /*     0  Accumulated Lines       */
      /*     1  Lines                   */
      /*     2  Bars                    */
      /*     3  Pie plots               */
      /* ****************************** */

      if(!isset($_SESSION['mode']) ||
         !isset($_SESSION['graphmode']) ||
         !isset($_SESSION['showchain']) ||
         !isset($_SESSION['scalemode'])) {
         return _("some necessary variables are not set. stopping here.");
      }

      /* time settings */
      $time_now  = mktime();
      $time_past = mktime() - 120;

      $data = $db->db_query("
         SELECT
            stat_time,
            stat_data
         FROM
            ". MYSQL_PREFIX ."stats
         WHERE 
            stat_time>='". $time_past ."'
         AND
            stat_time<='". $time_now ."'
         ORDER BY
            stat_time ASC
      ");

      switch($_SESSION['mode']) {
         default:
            /* Chain & Pipe View */
            while($row = $data->fetchRow()) {
               if($stat = $this->extract_tc_stat($row->stat_data, $_SESSION['showif'] ."_")) {
                  $tc_ids = array_keys($stat);
                  foreach($tc_ids as $tc_id) {
                     if(!isset($bigdata[$row->stat_time]))
                        $bigdata[$row->stat_time] = Array();
                     $bigdata[$row->stat_time][$tc_id] = $stat[$tc_id];
                  }
               }
            }
            break;

         case 'bandwidth':
         case 'bandwidth-jqPlot':
            /* Bandwidth View */
            while($row = $data->fetchRow()) {
               if($stat = $this->extract_tc_stat($row->stat_data, "_1:1\$")) {
                  $tc_ids = array_keys($stat);
                  foreach($tc_ids as $tc_id) {
                     if(!isset($bigdata[$row->stat_time]))
                        $bigdata[$row->stat_time] = Array();
                     $bigdata[$row->stat_time][$tc_id] = $stat[$tc_id];
                  }
               }
            }
            break;
      }

      /* If we have no data here, maybe the tc_collector is not running. Stop here. */
      if(!isset($bigdata)) {
         return _("tc_collector.pl is inactive!");
      }
	
      /* prepare graph arrays and fill up with data */
      $timestamps = array_keys($bigdata);

      foreach($timestamps as $timestamp) {

         $tc_ids = array_keys($bigdata[$timestamp]);

         foreach($tc_ids as $tc_id) {

            if(!isset($plot_array[$tc_id]))
               $plot_array[$tc_id] = array();

            $bw = $bigdata[$timestamp][$tc_id];
            switch($_SESSION['scalemode']) {
               case 'bit':
                  break;
               case 'byte':
                  $bw = round($bw / 8, 1);
                  break;
               default:
               case 'kbit':
                  $bw = round($bw / 1024, 1);
                  break;
               case 'kbyte':
                  $bw = round($bw / (1024*8), 1);
                  break;
               case 'mbit':
                  $bw = round($bw / 1048576, 1);
                  break;
               case 'mbyte':
                  $bw = round($bw / (1048576*8), 1);
                  break;
            }
            array_push($plot_array[$tc_id], $bw);
         }
      }

      /* What shell we graph? */
      switch($_SESSION['mode']) {
         case 'pipes':
         case 'pipes-jqPlot':
            switch($_SESSION['graphmode']) {
               case 0:
               case 1:
                  foreach($tc_ids as $tc_id) {
                     if(array_sum($plot_array[$tc_id]) <= 0)
                        continue;
                     if(!$this->isPipe($tc_id, $_SESSION['showif'], $_SESSION['showchain']))
                        continue;
                     array_push($this->total, $plot_array[$tc_id]);
                  }
                  /* sort so the most bandwidth consuming is on first place */
                  array_multisort($this->total, SORT_DESC | SORT_NUMERIC);
                  break;

               case 2:
               case 3:
                  foreach($tc_ids as $tc_id) {
                     if(!$this->isPipe($tc_id, $_SESSION['showif'], $_SESSION['showchain']))
                        continue;
                     $bps = round(array_sum($plot_array[$tc_id])/count($plot_array[$tc_id]), 0);
                     /* skip if out-of-range */
                     if($bps <= 0)
                        continue;
                     if($_SESSION['graphmode'] == 3)
                        $name = $this->findname($tc_id, $_SESSION['showif']) ." (%d". $this->getScaleName($_SESSION['scalemode']) .")";
                     else 
                        $name = $this->findname($tc_id, $_SESSION['showif']);
                     array_push($this->total, $bps);
                  }
                  /* sort so the most bandwidth consuming is on first place */
                  array_multisort($this->total, SORT_DESC | SORT_NUMERIC, $this->names);
                  break;
            }
            break;

         case 'chains':
         case 'chains-jqPlot':

            switch($_SESSION['graphmode']) {
               case 0:
               case 1:
                  $counter = 0;
                  foreach($tc_ids as $tc_id) {
                     if(!$this->isChain($tc_id, $_SESSION['showif']) || preg_match("/1:.*00/", $tc_id))
                        continue;
                     if($counter > 15)
                        continue;
                     array_push($this->names, $this->findname($tc_id, $_SESSION['showif']));
                     array_push($this->total, $plot_array[$tc_id]);
                     $counter++;
                  }
                  /* sort so the most bandwidth consuming is on first place */
                  array_multisort($this->total, SORT_DESC | SORT_NUMERIC);
                  break;

               case 2:
               case 3:
                  $counter = 0;
                  foreach($tc_ids as $tc_id) {
                     if(!$this->isChain($tc_id, $_SESSION['showif']) || preg_match("/1:.*00/", $tc_id))
                        continue;
                     $bps = round(array_sum($plot_array[$tc_id])/count($plot_array[$tc_id]), 0);
                     if($bps <= 0 || preg_match("/1:.*00/", $tc_id))
                        continue;
                     if($counter > 15)
                        continue;
                     if($_SESSION['graphmode'] == 2) {
                        $name = $this->findname($tc_id, $_SESSION['showif']) . sprintf(" (%dkbit/s)", $bps);
                        array_push($this->total, $bps);
                     }
                     elseif($_SESSION['graphmode'] == 3) {
                        $name = $this->findname($tc_id, $_SESSION['showif']);
                        array_push($this->total, array($name, $bps));
                     }

                     $counter++;
                  }
                  /* sort so the most bandwidth consuming is on first place */
                  array_multisort($this->total, SORT_DESC | SORT_NUMERIC);
                  $this->total = array($this->total);
                  //return json_encode($this->total);
                  //return;
                  break;
            }

            if(!$this->total) {
               return _("No chain data available!\nMake sure tc_collector.pl is active and ruleset is loaded.");
            }
            break;
	 
         case "bandwidth":
         case "bandwidth-jqPlot":
	    
            foreach($tc_ids as $tc_id) {
               array_push($this->total, $p[$tc_id]);
            }
            break;
      }

      $json_obj = Array(
         'start_time' => strftime("%H:%M:%S", $time_past),
         'end_time'   => strftime("%H:%M:%S", $time_now),
         'interface'  => $_SESSION['showif'],
         'scalemode'  => $_SESSION['scalemode'],
         'graphmode'  => $_SESSION['graphmode'],
         'data'       => json_encode($this->total)
      );

      if(isset($this->names) && !empty($this->names))
         $json_obj['names'] = json_encode($this->names);

      return(json_encode($json_obj));

   } // get_jqplot_values()

   /* splitup tc_collector string */
   private function extract_tc_stat($line, $limit_to = "")
   {  
      $data  = Array();
      $pairs = Array();
      $pairs = split(',', $line);

      foreach($pairs as $pair) {

         list($key, $value) = split('=', $pair);
         if(preg_match("/". $limit_to ."/", $key)) {
            $key = preg_replace("/". $limit_to ."/", "", $key);
            //$key = "bla".str_replace(":", "_", $key);
            if($value >= 0)
               $data[$key] = $value;
            else
               $data[$key] = 0;
         }
      }

      return $data;

   } // extract_tc_stat()

   /* returns pipe/chain name according tc_id */
   private function findName($id, $interface)
   {
      global $db;

      if(preg_match("/1:.*00/", $id)) {
         return "Fallback";
      }

      if($tc_id = $db->db_fetchSingleRow("
         SELECT id_pipe_idx, id_chain_idx
         FROM ". MYSQL_PREFIX ."tc_ids
         WHERE
            id_tc_id='". $id ."'
         AND id_if='". $interface ."'
      ")) {
	 
         if($tc_id->id_pipe_idx != 0) {

            $pipe = $db->db_fetchSingleRow("
               SELECT pipe_name
               FROM ". MYSQL_PREFIX ."pipes
               WHERE pipe_idx='". $tc_id->id_pipe_idx ."'
            ");
            return $pipe->pipe_name;
         }

         if($tc_id->id_chain_idx != 0) {
            $chain = $db->db_fetchSingleRow("
               SELECT chain_name
               FROM ". MYSQL_PREFIX ."chains
               WHERE chain_idx='". $tc_id->id_chain_idx ."'
            ");
            return $chain->chain_name;
         }
      }

      return $id;

   } // findName()

   /* check if tc_id is a pipe */
   private function isPipe($tc_id, $if, $chain)
   {
      global $db;

      if($db->db_fetchSingleRow("
         SELECT
            id_tc_id
         FROM
            ". MYSQL_PREFIX ."tc_ids
         WHERE
            id_if='". $if ."'
         AND
            id_chain_idx='". $chain ."'
         AND
            id_pipe_idx<>0
         AND
            id_tc_id='". $tc_id ."'
      ")) {
         return true;
      }

      return false;

   } // isPipe() 

   /* check if tc_id is a chain */
   private function isChain($tc_id, $if)
   {
      global $db;

      if($db->db_fetchSingleRow("
         SELECT
            id_tc_id
         FROM
            ". MYSQL_PREFIX ."tc_ids
         WHERE
            id_if='". $if ."'
         AND 
            id_tc_id='". $tc_id ."'
         AND
            id_pipe_idx=0")) {

         return true;

      }

      return false;

   } // isChain()

   private function getScaleName($scalemode)
   {
      switch($scalemode) {
         case 'bit':
            return 'bit/s';
         case 'byte':
            return 'byte/s';
         case 'kbit':
            return 'kbit/s';
         case 'kbyte':
            return 'kbyte/s';
         case 'mbit':
            return 'mbit/s';
         case 'mbyte':
            return 'mbyte/s';

      }

   } // getScaleName()

} // class Page_Monitor

$obj = new Page_Monitor;
$obj->handler();

?>
