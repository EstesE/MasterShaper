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

/* Readin config & database support */
require_once 'shaper_db.php';
require_once 'shaper.class.php';

/* include jpgraph library */
require_once "jpgraph/src/jpgraph.php";
require_once "jpgraph/src/jpgraph_line.php";
require_once "jpgraph/src/jpgraph_pie.php";
require_once "jpgraph/src/jpgraph_pie3d.php";
require_once "jpgraph/src/jpgraph_bar.php";

class MASTERSHAPER_GRAPH{

   var $config;
   var $db;
   var $total;
   var $colorid;
   var $graph;

   /* Class constructor */
   function MASTERSHAPER_GRAPH()
   {
      $this->ms     = new MASTERSHAPER;
      $this->db     = $this->ms->db;
      $this->total  = Array();
      $this->names  = Array();
      $this->colors = Array();
      $this->colorid = -1;

      /* If authentication is enabled, check permissions */
      if($this->ms->getOption("authentication") == "Y" &&
         !$this->ms->checkPermissions("user_show_monitor")) {
         $this->showTextBox("Insufficient permissions!");
         exit(0);
      }

   } // MASTERSHAPER_GRAPH()

   /* create graph */
   function draw($mode, $graphmode, $chain)
   {
      /* ****************************** */
      /* Setup the graph                */
      /*     0  Accumulated Lines       */
      /*     1  Lines                   */
      /*     2  Bars                    */
      /*     3  Pie plots               */
      /* ****************************** */

      switch($graphmode) {
         case 0:
         case 1:
         case 2:
            $this->graph = new Graph(800,360);
            $this->graph->SetMargin(60,205,50,30);
            $this->graph->SetMarginColor('white');
            $this->graph->SetScale("textlin");
            $this->graph->SetShadow();
            $this->graph->tabtitle->Set('Current Bandwidth Usage - '. strftime("%Y-%m-%d %H:%M:%S") ." - Interface ". $_GET['showif']);
            $this->graph->tabtitle->SetWidth(TABTITLE_WIDTHFULL);
            $this->graph->xgrid->Show();
            $this->graph->xgrid->SetColor('gray@0.5');
            $this->graph->ygrid->SetColor('gray@0.5');
                   
            switch($_GET['scalemode']) {
               default:
               case 'kbit':
                  $this->graph->yaxis->title->Set("Bandwidth kbits per second");
                  break;
               case 'kbyte':
                  $this->graph->yaxis->title->Set("Bandwidth kbytes per second");
                  break;
               case 'mbit':
                  $this->graph->yaxis->title->Set("Bandwidth Mbits per second");
                  break;
               case 'mbyte':
                  $this->graph->yaxis->title->Set("Bandwidth Mbytes per second");
                  break;
            }
            $this->graph->yaxis->setTitleMargin(38);
            $this->graph->yaxis->HideZeroLabel();
            $this->graph->ygrid->SetFill(true,'#EFEFEF@0.9','#BBCCFF@0.9');
            break;
         case 3:
            $this->graph = new PieGraph(800,350);
            $this->graph->SetMargin(20,320,50,20);
            $this->graph->SetShadow();
            break;
      }

      /* common graph settings */
      $this->graph->tabtitle->Set("Current Bandwidth Usage - ". strftime("%Y-%m-%d %H:%M:%S"));
      $this->graph->tabtitle->SetWidth(TABTITLE_WIDTHFULL);
      $this->graph->SetMarginColor('white');

      // Hide the frame around the graph
      $this->graph->SetFrame(false);

      // Authors note
      $this->graph->footer->right->Set("(c) Andreas Unterkircher\nGraph by MasterShaper");

      // Format the legend box
      $this->graph->legend->SetColor('navy');
      $this->graph->legend->SetFillColor('white');
      $this->graph->legend->SetLineWeight(1);
      $this->graph->legend->SetFont(FF_FONT2,FS_BOLD,8);
      $this->graph->legend->SetShadow('gray@0.4',3);
      $this->graph->legend->SetAbsPos(20,35,'right','top');
		
      /* time settings */
      $time_now  = mktime();
      $time_past = mktime() - 120;

      $data = $this->db->db_query("
         SELECT stat_time, stat_data
         FROM ". MYSQL_PREFIX ."stats
         WHERE 
            stat_time>='". $time_past ."'
            AND
            stat_time<='". $time_now ."'
         ORDER BY stat_time ASC
      ");

      switch($_GET['show']) {
         default:
            /* Chain & Pipe View */
            while($row = $data->fetchRow()) {
               if($stat = $this->extract_tc_stat($row->stat_data, $_GET['showif'] ."_")) {
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
         $this->showTextBox("tc_collecotr.pl is inactive!");
         exit(1);
      }
		
      /* prepare graph arrays and fill up with data */
      $timestamps = array_keys($bigdata);
      foreach($timestamps as $timestamp) {
         $tc_ids = array_keys($bigdata[$timestamp]);
         foreach($tc_ids as $tc_id) {
            if(!isset($plot_array[$tc_id]))
               $plot_array[$tc_id] = array();
            $bw = $bigdata[$timestamp][$tc_id];
            switch($_GET['scalemode']) {
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
      switch($mode) {
         case 'pipes':
            switch($graphmode) {
               case 0:
               case 1:
                  foreach($tc_ids as $tc_id) {
                     if(array_sum($plot_array[$tc_id]) > 0) {
                        if($this->isPipe($tc_id, $_GET['showif'], $chain)) {
                           $p[$tc_id] = new LinePlot($plot_array[$tc_id]);
                           if($graphmode == 0) {
                              $p[$tc_id]->SetColor("black");
                              $p[$tc_id]->SetFillColor($this->getColor($tc_id, $_GET['showif']));
                              $p[$tc_id]->SetWeight(1);
                           }
                           else {
                              $p[$tc_id]->SetColor($this->getColor($tc_id, $_GET['showif']));
                              $p[$tc_id]->SetWeight(2);
                           }
                           $p[$tc_id]->SetLegend($this->findname($tc_id, $_GET['showif']));
                           array_push($this->total, $p[$tc_id]);
			               }
                     }
                  }
                  /* sort so the most bandwidth consuming is on first place */
                  array_multisort($this->total, SORT_DESC | SORT_NUMERIC);
                  break;

               case 2:
               case 3:
                  foreach($tc_ids as $tc_id) {
                     if($this->isPipe($tc_id, $_GET['showif'], $chain)) {
                        $bps = round(array_sum($plot_array[$tc_id])/count($plot_array[$tc_id]), 0);
                        if($bps > 0) {
                           if($graphmode == 3)
                              array_push($this->names, $this->findname($tc_id, $_GET['showif']) ." (%d". $this->getScaleName($_GET['scalemode']) .")");
                           else 
                              array_push($this->names, $this->findname($tc_id, $_GET['showif']));
                           array_push($this->colors, $this->getColor($tc_id, $_GET['showif']));
                           array_push($this->total, $bps);
                        }
                     }
                  }
                  /* sort so the most bandwidth consuming is on first place */
                  array_multisort($this->total, SORT_DESC | SORT_NUMERIC, $this->colors, $this->names);
                  break;
            }

            /* Avoid jpgraph errors if no data is available and display a empty graph */
            if(empty($this->total)) {
               $null_data = Array();
               $null_data[0] = 0;
               $null_data[1] = 0;

               switch($graphmode) {
                  case 0:
                  case 1:
                     $p[0] = new LinePlot($null_data);
                     array_push($this->total, $p[0]);
                     break;
                  case 2:
                     $this->total = Array(0);
                     break;
                  case 3:
                     $this->total = Array(0.1);
                     break;
               }
            }
            break;

         case 'chains':

            switch($graphmode) {
               case 0:
               case 1:
                  $counter = 0;
                  foreach($tc_ids as $tc_id) {
                     if($this->isChain($tc_id, $_GET['showif']) && !preg_match("/1:.*99/", $row->stat_id)) {
                        $p[$tc_id] = new LinePlot($plot_array[$tc_id]);
                        if($graphmode == 0) {
                           $p[$tc_id]->SetColor("black");
                           $p[$tc_id]->SetFillColor($this->getColor($tc_id, $_GET['showif']));
                           $p[$tc_id]->SetWeight(1);
                        }
                        else {
                           $p[$tc_id]->SetColor($this->getColor($tc_id, $_GET['showif']));
                           $p[$tc_id]->SetWeight(1);
                        }

                        if($counter < 15) {
                           $p[$tc_id]->SetLegend($this->findname($tc_id, $_GET['showif']));
                           array_push($this->total, $p[$tc_id]);
                        }
                        $counter++;
                     }
                  }
		  
                  /* sort so the most bandwidth consuming is on first place */
                  array_multisort($this->total, SORT_DESC | SORT_NUMERIC);
                  break;

               case 2:
               case 3:

                  foreach($tc_ids as $tc_id) {
                     if($this->isChain($tc_id, $_GET['showif']) && !preg_match("/1:.*99/", $row->stat_id)) {
                        $bps = round(array_sum($plot_array[$tc_id])/count($plot_array[$tc_id]), 0);
                        if($bps > 0 || preg_match("/1:.*99/", $tc_id)) {
                           if($counter < 15) {
                              if($graphmode == 3)
                                 array_push($this->names, $this->findname($tc_id, $_GET['showif']) ." (%dkbit/s)");
                              else
                                 array_push($this->names, $this->findname($tc_id, $_GET['showif']));

                              array_push($this->colors, $this->getColor($tc_id, $_GET['showif']));
                              array_push($this->total, $bps);
                           }
                           $counter++;
                        }
                     }
                  }
                  /* sort so the most bandwidth consuming is on first place */
                  array_multisort($this->total, SORT_DESC | SORT_NUMERIC, $this->colors, $this->names);
                  break;
            }

            if(!$this->total) {
               $this->showTextBox(_("No chain data available!\nMake sure tc_collector.pl is active and ruleset is loaded."));
               exit(1);
            }
            break;
	 
         case "bandwidth";
	    
            foreach($tc_ids as $tc_id) {
               $p[$tc_id] = new LinePlot($plot_array[$tc_id]);
               $p[$tc_id]->SetColor("black");
               $p[$tc_id]->SetFillColor($this->getColor("1:1", $tc_id));
               $p[$tc_id]->SetWeight(1);
               $p[$tc_id]->SetLegend($tc_id);
               array_push($this->total, $p[$tc_id]);
            }
            break;
      }

      switch($_GET['graphmode']) {

         default:
         case 0:
            $accumulated = new AccLinePlot($this->total);
            $this->graph->Add($accumulated);
            $xdata = Array();
            array_push($xdata, strftime("%H:%M:%S", mktime()-120));
            for($i = 1; $i <= 9; $i++) {
               if($i != 5)
                  array_push($xdata, "");
               else
                  array_push($xdata, strftime("%H:%M:%S", mktime()-60));
            }		
            array_push($xdata, strftime("%H:%M:%S", mktime()));
            $this->graph->xaxis->SetTickLabels($xdata);
            $this->graph->xaxis->SetTextLabelInterval(5); 
            break;

         case 1:
            $i = 0;
            foreach($this->total as $plot[$i]) {
               $this->graph->Add($plot[$i]);
               $i++;
            }
            $xdata = Array();
            array_push($xdata, strftime("%H:%M:%S", mktime()-120));
            for($i = 1; $i <= 9; $i++) {
               if($i != 5)
                  array_push($xdata, "");
               else
                  array_push($xdata, strftime("%H:%M:%S", mktime()-60));
            }		
            array_push($xdata, strftime("%H:%M:%S", mktime()));
            $this->graph->xaxis->SetTickLabels($xdata);
            $this->graph->xaxis->SetTextLabelInterval(5); 
            break;

         case 2:

            $p1 = new BarPlot($this->total); 
            $p1->setFillColor($this->colors);
            $p1->setShadow();
            $p1->value->Show();
            $this->graph->xaxis->SetTickLabels($this->names);
            $this->graph->Add($p1);
            break;

         case 3:

            $p1 = new PiePlot3D($this->total); 
            $p1->SetLegends($this->names);
            $p1->SetCenter("0.30", "0.55");
            $p1->SetLabelType(PIE_VALUE_ABS);
            $p1->SetSliceColors($this->colors);
            $p1->value->Show(false);
            $this->graph->Add($p1);
            break;

      }

      // Output the graph
      $this->graph->Stroke();

   } // draw()

   /* returns a new color for the graph */
   function getColor($id, $interface)
   {
      $colors = Array(
         "#CC2222",
         "#44FF44",
         "#4444FF",
         "#FFFF00",
         "#00FFFF",
         "#FF00FF",
         "#C0C0C0",
         "#00F000",
         "#8396A6",
         "#E4CBAB",
         "#7FFFD4",
         "#8A2BE2",
         "#A52A2A",
         "#002200
      ");

      /* The color should not change during graphs. So we save the currently used color to
         database and reuse it on the next reload.
      */
      $color = $this->db->db_fetchSingleRow("
         SELECT id_color
         FROM ". MYSQL_PREFIX ."tc_ids
         WHERE
            id_tc_id='". $id ."'
         AND 
            id_if='". $interface ."'
      ");
		
      if($color->id_color != "") {
         return $color->id_color;
      }
      else {
         /* Already used? */
         $this->colorid++;
         while(isset($colors[$this->colorid]) && $this->checkColor($colors[$this->colorid], $interface)) {
            if($this->colorid == count($colors)) {
               $this->colorid = 0;
               break;
            }
            $this->colorid++;
         }
         $this->setColor($colors[$this->colorid], $id, $interface);
         return $colors[$this->colorid];
      }

   } // getColor()

   /* remember the used color */
   function setColor($color, $id, $interface)
   {
      $this->db->db_query("
         UPDATE ". MYSQL_PREFIX ."tc_ids
         SET id_color='". $color ."'
         WHERE
            id_tc_id='". $id ."'
         AND
            id_if='". $interface ."'
      ");

   } // setColor()

   /* check if color is already used */
   function checkColor($color, $interface)
   {
      if($this->db->db_fetchSingleRow("
         SELECT id_color
         FROM ". MYSQL_PREFIX ."tc_ids
         WHERE
            id_color='". $color ."'
         AND
            id_if='". $interface ."'
      "))
         return 1;
      else
         return 0;

   } // checkColor()

   /* returns pipe/chain name according tc_id */
   function findName($id, $interface)
   {
      if(preg_match("/1:.*99/", $id)) {
         return "Fallback";
      }

      if($tc_id = $this->db->db_fetchSingleRow("
         SELECT id_pipe_idx, id_chain_idx
         FROM ". MYSQL_PREFIX ."tc_ids
         WHERE
            id_tc_id='". $id ."'
         AND id_if='". $interface ."'
      ")) {
	 
         if($tc_id->id_pipe_idx != 0) {

            $pipe = $this->db->db_fetchSingleRow("
               SELECT pipe_name
               FROM ". MYSQL_PREFIX ."pipes
               WHERE pipe_idx='". $tc_id->id_pipe_idx ."'
            ");
            return $pipe->pipe_name;
         }

         if($tc_id->id_chain_idx != 0) {
            $chain = $this->db->db_fetchSingleRow("
               SELECT chain_name
               FROM ". MYSQL_PREFIX ."chains
               WHERE chain_idx='". $tc_id->id_chain_idx ."'
            ");
            return $chain->chain_name;
         }
      }

      return $id;

   } // findName()

   /* splitup tc_collector string */
   function extract_tc_stat($line, $limit_to = "")
   {
      $data  = Array();
      $pairs = Array();
      $pairs = split(',', $line);
		
      foreach($pairs as $pair) {
	 
         list($key, $value) = split('=', $pair);
         if(preg_match("/". $limit_to ."/", $key)) {
            $key = preg_replace("/". $limit_to ."/", "", $key);
            if($value >= 0)
               $data[$key] = $value;
            else
               $data[$key] = 0;
         }
      }

      return $data;

   } // extract_tc_stat()

   /* check if tc_id is a pipe */
   function isPipe($tc_id, $if, $chain)
   {
      if($this->db->db_fetchSingleRow("
         SELECT id_tc_id
         FROM ". MYSQL_PREFIX ."tc_ids
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
   function isChain($tc_id, $if)
   {
      if($this->db->db_fetchSingleRow("
         SELECT id_tc_id
         FROM ". MYSQL_PREFIX ."tc_ids
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

   function getScaleName($scalemode)
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

   function showTextBox($txt, $color=000000, $space=4, $font=4, $w=300) 
   {
      if (strlen($color) != 6) {
         $color = 000000;
      }

      $int = hexdec($color);
      $h = imagefontheight($font);
      $fw = imagefontwidth($font);
      $txt = explode("\n", wordwrap($txt, ($w / $fw), "\n"));
      $lines = count($txt);
      $im = imagecreate($w, (($h * $lines) + ($lines * $space)));
      $bg = imagecolorallocate($im, 255, 255, 255);
      $color = imagecolorallocate($im, 0xFF & ($int >> 0x10), 0xFF & ($int >> 0x8), 0xFF & $int);
      $y = 0;

      foreach ($txt as $text) {
         $x = (($w - ($fw * strlen($text))) / 2);
         imagestring($im, $font, $x, $y, $text, $color);
         $y += ($h + $space);

      }

      Header("Content-type: image/png");
      ImagePng($im);

   } // showTextBox()
		
}

$stat = new MASTERSHAPER_GRAPH;

if($stat != 0) {
   
   if(!isset($_GET['show']))
      $_GET['show'] = 'bandwidth';
   if(!isset($_GET['graphmode']))
      $_GET['graphmode'] = 0;
   if(!isset($_GET['showchain']))
      $_GET['showchain'] = -1;

   $stat->draw($_GET['show'], $_GET['graphmode'], $_GET['showchain']);

}

?>
