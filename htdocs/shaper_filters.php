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

class MSFILTERS {

   var $db;
   var $parent;

   /* Class constructor */
   function MSFILTERS($parent)
   {
      $this->db = $parent->db;
      $this->parent = $parent;
   } // MSFILTERS()

   /* interface output */
   function showHtml()
   {

      /* If authentication is enabled, check permissions */
      if($this->parent->getOption("authentication") == "Y" &&
         !$this->parent->checkPermissions("user_manage_filters")) {

         $this->parent->printError("<img src=\"". ICON_FILTERS ."\"
            alt=\"filter icon\" />&nbsp;". _("Manage Filters"),
            _("You do not have enough permissions to access this module!")
         );
         
         return 0;

      }

      if(!isset($this->parent->screen)) {

         $this->parent->screen = 0;

      }

      switch($this->parent->screen) {

         default:
         case 0:

            $this->parent->startTable("<img src=\"". ICON_FILTERS ."\"
               alt=\"filter icon\" />&nbsp;". _("Manage Filters")
            );
?>
  <table style="width: 100%;" class="withborder">
   <tr>
<?php

            if(isset($_GET['saved'])) {

?>
    <td colspan="2" style="text-align: center;" class="sysmessage"><?php print _("You have made changes to the ruleset. Don't forget to reload them."); ?></td>
<?php

            } else {

?>
    <td colspan="2">&nbsp;</td>
<?php
            }
?>
   </tr>
   <tr>
    <td colspan="2" style="text-align: center;">
     <img src="<?php print ICON_NEW; ?>" alt="new icon" />
     <a href="<?php print $this->parent->self ."?mode=". $this->parent->mode ."&amp;screen=". MANAGE ."&amp;new=1"; ?>"><? print _("Create a new Filter"); ?></a>
    </td>
   </tr>
   <tr>
    <td colspan="2">&nbsp;</td>
   </tr>
   <tr>
    <td><img src="<?php print ICON_FILTERS; ?>" alt="filter icon" />&nbsp;<i><? print _("Filters"); ?></i></td> 
    <td style="text-align: center;"><i><?php print _("Options"); ?></i></td>
   </tr>
<?php

            $result = $this->db->db_query("SELECT * FROM ". MYSQL_PREFIX ."filters ORDER BY filter_name ASC");

            while($row = $result->fetchrow()) {

?>
   <tr onmouseover="setBackGrdColor(this, 'mouseover');" onmouseout="setBackGrdColor(this, 'mouseout');">
    <td>
     <img src="<?php print ICON_FILTERS; ?>" alt="filter icon" />
     <a href="<?php print $this->parent->self ."?mode=". $this->parent->mode ."&amp;screen=". MANAGE ."&amp;idx=". $row->filter_idx; ?>">
      <?php print $row->filter_name; ?>
     </a>
    </td>
    <td style="text-align: center;">
     <a href="<?php print $this->parent->self ."?mode=". $this->parent->mode ."&amp;screen=". DELETE ."&amp;idx=". $row->filter_idx ."&amp;name=". urlencode($row->filter_name); ?>" title="Delete">
      <img src="<?php print ICON_DELETE; ?>" alt="filter icon" />
     </a>
<?php

               if($row->filter_active == "Y") {

?>
     <a href="<?php print $this->parent->self ."?mode=". $this->parent->mode ."&amp;screen=". CHGSTATUS ."&amp;idx=". $row->filter_idx ."&amp;to=0"; ?>" title="Disable filter <? print $row->filter_name; ?>">
      <img src="<?php print ICON_ACTIVE; ?>" alt="filter icon" />
     </a>
<?php

               }
               else {

?>
     <a href="<?php print $this->parent->self ."?mode=". $this->parent->mode ."&amp;screen=". CHGSTATUS ."&amp;idx=". $row->filter_idx ."&amp;to=1"; ?>" title="Enable filter <? print $row->filter_name; ?>">
      <img src="<?php print ICON_INACTIVE; ?>" alt="filter icon" />
     </a>
<?php

               }

?>
    </td>
   </tr>
<?php

            }

?>
  </table>
<?php

            $this->parent->closeTable();
            break;

         case MANAGE:

            if(!isset($_GET['saveit'])) {

               if(isset($_GET['new'])) {

                  $this->parent->startTable("<img src=\"". ICON_FILTERS ."\"
                     alt=\"filter icon\" />&nbsp;". _("Create a new Filter")
                  );

                  $form_url = $this->parent->self ."?mode=". $this->parent->mode
                     ."&amp;screen=". $this->parent->screen
                     ."&amp;saveit=1&amp;new=1";

                  $current->filter_active = 'Y';

               }
               else {

                  $current = $this->db->db_fetchSingleRow("
                     SELECT * FROM ". MYSQL_PREFIX ."filters
                     WHERE filter_idx='". $_GET['idx'] ."'
                  ");

                  $this->parent->startTable("<img src=\"". ICON_FILTERS ."\""
                     ."alt=\"filter icon\" />&nbsp;". _("Modify Filter")
                     ." ". $this->parent->getClassVar($current, 'filter_name')
                  );

                  $form_url = $this->parent->self ."?mode="
                     . $this->parent->mode ."&amp;screen="
                     . $this->parent->screen ."&amp;idx="
                     . $_GET['idx'] ."&amp;namebefore="
                     . urlencode($this->parent->getClassVar($current, 'filter_name')) ."&amp;saveit=1";

               }

?>
  <form action="<?php print $form_url; ?>" method="post" id="filters">
   <table style="width: 100%;" class="withborder2"> 
    <tr>
     <td colspan="3">
      <img src="<?php print ICON_FILTERS; ?>" alt="filter icon" />&nbsp;<? print _("General"); ?>
     </td>
    </tr>
    <tr>
     <td style="white-space: nowrap;"><?php print _("Name:"); ?></td>
     <td><input type="text" name="filter_name" size="30" value="<?php print $this->parent->getClassVar($current, 'filter_name'); ?>" /></td>
     <td><?php print _("Name of the filter."); ?></td>
    </tr>
    <tr>
     <td style="white-space: nowrap;"><?php print _("Status:"); ?></td>
     <td>
      <input type="radio" name="filter_active" value="Y" <?php if($this->parent->getClassVar($current, 'filter_active') == 'Y') print "checked=\"checked\""; ?> /><? print _("Active"); ?>
      <input type="radio" name="filter_active" value="N" <?php if($this->parent->getClassVar($current, 'filter_active') != 'Y') print "checked=\"checked\""; ?> /><? print _("Inactive"); ?>
     </td>
     <td>
      <?php print _("Will these filter be used or not."); ?>
     </td>
    </tr>
    <tr>
     <td colspan="3">
      <img src="<?php print ICON_FILTERS; ?>" alt="filter icon" />&nbsp;<? print _("Match protocols"); ?>
     </td>
    </tr>
    <tr>
     <td style="white-space: nowrap;">
      <?php print _("Protocols:"); ?>
     </td>
     <td>
      <select name="filter_protocol_id">
       <option value="-1">--- <?php print _("Ignore"); ?> ---</option>
<?php

               $result = $this->db->db_query("
                  SELECT * FROM ". MYSQL_PREFIX ."protocols
                  ORDER BY proto_name ASC
               ");

               while($row = $result->fetchRow()) {

?>
       <option value="<?php print $row->proto_idx; ?>" <? if($row->proto_idx == $this->parent->getClassVar($current, 'filter_protocol_id')) print "selected=\"selected\""; ?>><? print $row->proto_name; ?></option>
<?php

               }

?>
     </td>
     <td>
      <?php print _("Match on this protocol. Select TCP or UDP if you want to use port definitions! If you want to match both TCP &amp; UDP use IP as protocol. Be aware that tc-filter can not differ between TCP &amp; UDP. It will match both at the same time!"); ?>
     </td>
    </tr>
    <tr>
     <td colspan="3">
      <img src="<?php print ICON_FILTERS; ?>" alt="filter icon" />&nbsp;<? print _("Match ports"); ?>
     </td>
    </tr>
    <tr>
     <td style="white-space: nowrap;"><?php print _("Ports:"); ?></td>
     <td>
      <table class="noborder">
       <tr>
        <td>
	 <select size="10" name="avail[]" multiple="multiple">;
	  <option value="">********* <?php print _("Unused"); ?> *********</option>
<?php

               $ports = $this->db->db_query("
                  SELECT port_idx, port_name, port_number
                  FROM ". MYSQL_PREFIX ."ports
                  LEFT JOIN ". MYSQL_PREFIX ."assign_ports 
                     ON port_idx=". MYSQL_PREFIX ."assign_ports.afp_port_idx
                  WHERE ". MYSQL_PREFIX ."assign_ports.afp_filter_idx <> '". $this->parent->getHTTPVar('idx') ."' 
                  OR ISNULL(". MYSQL_PREFIX ."assign_ports.afp_filter_idx)
                  ORDER BY port_name ASC
               ");

               while($port = $ports->fetchRow()) {

                  print "<option value=\"". $port->port_idx
                     ."\">". $port->port_name
                     ." (". $port->port_number .")</option>\n";

               }
?>
         </select>
        </td>
        <td>&nbsp;</td>
        <td>
         <input type="button" value="&gt;&gt;" onclick="moveOptions(document.forms['filters'].elements['avail[]'], document.forms['filters'].elements['used[]']);" /><br />
         <input type="button" value="&lt;&lt;" onclick="moveOptions(document.forms['filters'].elements['used[]'], document.forms['filters'].elements['avail[]']);" />
	     </td>
	<td>&nbsp;</td>
	<td>
	 <select size="10" name="used[]" multiple="multiple">
	  <option value="">********* <?php print _("Used"); ?> *********</option>
<?php

               $ports = $this->db->db_query("
                  SELECT p.port_idx, p.port_name, p.port_number
                  FROM ". MYSQL_PREFIX ."assign_ports
                  LEFT JOIN ". MYSQL_PREFIX ."ports p 
                     ON p.port_idx = afp_port_idx
                  WHERE afp_filter_idx = '". $this->parent->getHTTPVar('idx') ."'
                  ORDER BY p.port_name ASC
               ");

               while($port = $ports->fetchRow()) {

                  print "<option value=\""
                     . $port->port_idx ."\">"
                     . $port->port_name 
                     ." (". $port->port_number .")</option>\n";

               }

?>
         </select>
        </td>
       </tr>
      </table>
     </td>
     <td><?php print _("Match on specific ports. Be aware that this will only work for TCP/UDP protocols!"); ?></td>
    </tr>
    <tr>
     <td colspan="3">
      <img src="<?php print ICON_FILTERS; ?>" alt="filter icon" />&nbsp;<? print _("Match protocol flags"); ?>
     </td>
    </tr>
    <tr>
     <td style="white-space: nowrap;">
      <?php print _("TOS flags:"); ?>
     </td>
     <td>
      <select name="filter_tos">
       <option value="-1"   <?php if($this->parent->getClassVar($current, 'filter_tos') == "-1")   print "selected=\"selected\"";?>>Ignore</option>
       <option value="0x10" <?php if($this->parent->getClassVar($current, 'filter_tos') == "0x10") print "selected=\"selected\"";?>>Minimize-Delay 16 (0x10)</option>
       <option value="0x08" <?php if($this->parent->getClassVar($current, 'filter_tos') == "0x08") print "selected=\"selected\"";?>>Maximize-Throughput 8 (0x08)</option>
       <option value="0x04" <?php if($this->parent->getClassVar($current, 'filter_tos') == "0x04") print "selected=\"selected\"";?>>Maximize-Reliability 4 (0x04)</option>
       <option value="0x02" <?php if($this->parent->getClassVar($current, 'filter_tos') == "0x02") print "selected=\"selected\"";?>>Minimize-Cost 2 (0x02)</option>
       <option value="0x00" <?php if($this->parent->getClassVar($current, 'filter_tos') == "0x00") print "selected=\"selected\"";?>>Normal-Service 0 (0x00)</option>
      </select>
     </td>
     <td>
      <?php print _("Match a specific TOS flag."); ?>
     </td>
    </tr>
<?php

               if($this->parent->getOption("filter") == "ipt") {

?>
    <tr>
     <td style="white-space: nowrap;">
      <?php print _("TCP flags:"); ?>
     </td>
     <td>
      <table class="noborder">
       <tr>
        <td><input type="checkbox" name="filter_tcpflag_syn" value="Y" <?php if($this->parent->getClassVar($current, 'filter_tcpflag_syn') =="Y") print "checked=\"checked\""; ?> />SYN</td>
	<td><input type="checkbox" name="filter_tcpflag_ack" value="Y" <?php if($this->parent->getClassVar($current, 'filter_tcpflag_ack') =="Y") print "checked=\"checked\""; ?> />ACK</td>
        <td><input type="checkbox" name="filter_tcpflag_fin" value="Y" <?php if($this->parent->getClassVar($current, 'filter_tcpflag_fin') =="Y") print "checked=\"checked\""; ?> />FIN</td>
       </tr>
       <tr>
        <td><input type="checkbox" name="filter_tcpflag_rst" value="Y" <?php if($this->parent->getClassVar($current, 'filter_tcpflag_rst') =="Y") print "checked=\"checked\""; ?> />RST</td>
        <td><input type="checkbox" name="filter_tcpflag_urg" value="Y" <?php if($this->parent->getClassVar($current, 'filter_tcpflag_urg') =="Y") print "checked=\"checked\""; ?> />URG</td>
        <td><input type="checkbox" name="filter_tcpflag_psh" value="Y" <?php if($this->parent->getClassVar($current, 'filter_tcpflag_psh') =="Y") print "checked=\"checked\""; ?> />PSH</td>
       </tr>
      </table>
     </td>
     <td>
      <?php print _("Match on specific TCP flags combinations."); ?>
     </td>
    </tr>
    <tr>
     <td style="white-space: nowrap;">
      <?php print _("Packet length:"); ?>
     </td>
     <td>
      <input type="text" name="filter_packet_length" size="30" value="<?php print $this->parent->getClassVar($current, 'filter_packet_length'); ?>" />
     </td>
     <td>
      <?php print _("Match a packet against a defined size. Enter a size \"64\" or a range \"64:128\"."); ?>
     </td>
    </tr>
    <tr>
     <td colspan="3">
      <img src="<?php print ICON_FILTERS; ?>" alt="filter icon" />&nbsp;<? print _("Other matches"); ?>
     </td>
    </tr>
    <tr>
     <td style="white-space: nowrap;">
      IPP2P:
     </td>
     <td>
      <table class="noborder">
       <tr>
        <td><input type="checkbox" name="filter_p2p_edk" value="Y" <?php if($this->parent->getClassVar($current, 'filter_p2p_edk') == "Y") print "checked=\"checked\""; ?> />Edonkey</td>
	<td><input type="checkbox" name="filter_p2p_kazaa" value="Y" <?php if($this->parent->getClassVar($current, 'filter_p2p_kazaa') == "Y") print "checked=\"checked\""; ?> />Kazaa</td>
        <td><input type="checkbox" name="filter_p2p_dc" value="Y" <?php if($this->parent->getClassVar($current, 'filter_p2p_dc') == "Y") print "checked=\"checked\""; ?> />Direct Connect (DC)</td>
       </tr>
       <tr>
	<td><input type="checkbox" name="filter_p2p_gnu" value="Y" <?php if($this->parent->getClassVar($current, 'filter_p2p_gnu') == "Y") print "checked=\"checked\""; ?> />Gnutella</td>
	<td><input type="checkbox" name="filter_p2p_bit" value="Y" <?php if($this->parent->getClassVar($current, 'filter_p2p_bit') == "Y") print "checked=\"checked\""; ?> />Bittorent</td>
	<td><input type="checkbox" name="filter_p2p_apple" value="Y" <?php if($this->parent->getClassVar($current, 'filter_p2p_apple') == "Y") print "checked=\"checked\""; ?> />AppleJuice</td>
       </tr>
       <tr>
        <td><input type="checkbox" name="filter_p2p_soul" value="Y" <?php if($this->parent->getClassVar($current, 'filter_p2p_soul') == "Y") print "checked=\"checked\""; ?> />SoulSeek</td>
	<td><input type="checkbox" name="filter_p2p_winmx" value="Y" <?php if($this->parent->getClassVar($current, 'filter_p2p_winmx') == "Y") print "checked=\"checked\""; ?> />WinMX</td>
        <td><input type="checkbox" name="filter_p2p_ares" value="Y" <?php if($this->parent->getClassVar($current, 'filter_p2p_ares') == "Y") print "checked=\"checked\""; ?> />Ares</td>
       </tr>
      </table>
     </td>
     <td>
      <?php print _("Match on specific filesharing protocols. This uses the ipp2p iptables module. It has to be available on your iptables installation. Refer <a href=\"http://www.ipp2p.org\" onclick=\"window.open('http://www.ipp2p.org'); return false;\">www.ipp2p.org</a> for more informations."); ?>
     </td>
    </tr>
    <tr>
     <td style="white-space: nowrap;">
      layer7:
     </td>
     <td>
      <table class="noborder">
       <tr>
        <td>
	 <select size="10" name="filter_l7_avail[]" multiple="multiple">
	  <option value="">********* <?php print _("Unused"); ?> *********</option>
<?php

               $l7protos = $this->db->db_query("
                  SELECT l7proto_idx, l7proto_name
                  FROM ". MYSQL_PREFIX ."l7_protocols 
                  LEFT JOIN ". MYSQL_PREFIX ."assign_l7_protocols
                     ON l7proto_idx=afl7_l7proto_idx
                     AND afl7_filter_idx = '".  $this->parent->getHTTPvar('idx') ."'
                  WHERE afl7_filter_idx <> '". $this->parent->getHTTPvar('idx') ."'
                  OR ISNULL(afl7_filter_idx)
                  ORDER BY l7proto_name ASC
               ");

               while($l7proto = $l7protos->fetchRow()) {

                  print "<option value=\""
                     . $l7proto->l7proto_idx ."\">"
                     . $l7proto->l7proto_name ."</option>\n";

               }
?>
         </select>
        </td>
        <td>&nbsp;</td>
        <td>
	 <input type="button" value="&gt;&gt;" onclick="moveOptions(document.forms['filters'].elements['filter_l7_avail[]'], document.forms['filters'].elements['filter_l7_used[]']);"/><br />
         <input type="button" value="&lt;&lt;" onclick="moveOptions(document.forms['filters'].elements['filter_l7_used[]'], document.forms['filters'].elements['filter_l7_avail[]']);"/>
	</td>
	<td>&nbsp;</td>
	<td>
	 <select size="10" name="filter_l7_used[]" multiple="multiple">
	  <option value="">********* <?php print _("Used"); ?> *********</option>
<?php

               $l7protos = $this->db->db_query("
                  SELECT l7proto_idx, l7proto_name
                  FROM ". MYSQL_PREFIX ."assign_l7_protocols 
                  LEFT JOIN ". MYSQL_PREFIX ."l7_protocols
                     ON l7proto_idx=afl7_l7proto_idx
                  WHERE afl7_filter_idx = '". $this->parent->getHTTPvar('idx') ."'
                  ORDER BY l7proto_name ASC
               ");
		  
               while($l7proto = $l7protos->fetchRow()) {

                  print "<option value=\""
                     . $l7proto->l7proto_idx ."\">"
                     . $l7proto->l7proto_name ."</option>\n";

               }

?>
         </select>
        </td>
       </tr>
      </table>
     </td>
     <td>
      <?php print _("Match on specific protocols. This uses the layer7 iptables module. It has to be available on your iptables installation. Refer <a http=\"http://l7-filter.sourceforge.net\" onclick=\"window.open('http://l7-filter.sourceforge.net'); return false;\">l7-filter.sf.net</a> for more informations.<br /><br />Use Other-&gt;Update L7 Protocols to load current available l7 pat files."); ?>
     </td>
    </tr>
<?php

               /* recalculate from timestamps */
               $current_time_start_year   = date("Y", (int) $this->parent->getClassVar($current, 'filter_time_start'));
               $current_time_start_month  = date("n", (int) $this->parent->getClassVar($current, 'filter_time_start'));
               $current_time_start_day    = date("d", (int)  $this->parent->getClassVar($current, 'filter_time_start'));
               $current_time_start_hour   = date("H", (int) $this->parent->getClassVar($current, 'filter_time_start'));
               $current_time_start_minute = date("i", (int) $this->parent->getClassVar($current, 'filter_time_start'));
               $current_time_stop_year    = date("Y", (int) $this->parent->getClassVar($current, 'filter_time_stop'));
               $current_time_stop_month   = date("n", (int) $this->parent->getClassVar($current, 'filter_time_stop'));
               $current_time_stop_day     = date("d", (int) $this->parent->getClassVar($current, 'filter_time_stop'));
               $current_time_stop_hour    = date("H", (int) $this->parent->getClassVar($current, 'filter_time_stop'));
               $current_time_stop_minute  = date("i", (int) $this->parent->getClassVar($current, 'filter_time_stop'));

?>
    <tr>
     <td style="white-space: nowrap;">
      Time:
     </td>
     <td>
      <table class="noborder">
       <tr>
        <td colspan="2">
	 <input type="checkbox" name="filter_time_use_range" value="Y" <?php if($this->parent->getClassVar($current, 'filter_time_use_range') == "Y") print "checked=\"checked\""; ?> /><? print _("Use time range:"); ?>
	</td>
       </tr>
       <tr>
        <td colspan="2">&nbsp;</td>
       </tr>
       <tr>
        <td>
	 <?php print _("Start:"); ?>
	</td>
	<td>
         <select name="filter_time_start_year">
          <?php print $this->parent->getYearList($current_time_start_year); ?>
         </select>
         -
         <select name="filter_time_start_month">
          <?php print $this->parent->getMonthList($current_time_start_month); ?>
         </select>
         -
         <select name="filter_time_start_day">
          <?php print $this->parent->getDayList($current_time_start_day); ?>
         </select>
         &nbsp;
         <select name="filter_time_start_hour">
          <?php print $this->parent->getHourList($current_time_start_hour); ?>
         </select>
         :
         <select name="filter_time_start_minute">
          <?php print $this->parent->getMinuteList($current_time_start_minute); ?>
         </select>
	</td>
       </tr>
       <tr>
        <td>
	 <?php print ("Stop:"); ?>
	</td>
        <td>
         <select name="filter_time_stop_year">
          <?php print $this->parent->getYearList($current_time_stop_year); ?>
         </select>
         -
         <select name="filter_time_stop_month">
          <?php print $this->parent->getMonthList($current_time_stop_month); ?>
         </select>
         -
         <select name="filter_time_stop_day">
          <?php print $this->parent->getDayList($current_time_stop_day); ?>
         </select>
         &nbsp;
         <select name="filter_time_stop_hour">
          <?php print $this->parent->getHourList($current_time_stop_hour); ?>
         </select>
         :
         <select name="filter_time_stop_minute">
          <?php print $this->parent->getMinuteList($current_time_stop_minute); ?>
         </select>
	</td>
       </tr>
       <tr>
        <td colspan="2">&nbsp;</td>
       </tr>
       <tr>
        <td>
	 <?php print _("Days:"); ?>
	</td>
	<td>
         <input type="checkbox" name="filter_time_day_mon" value="Y" <?php if($this->parent->getClassVar($current, 'filter_time_day_mon') == "Y") print "checked=\"checked\"";?> /><? print _("Mon"); ?>
         <input type="checkbox" name="filter_time_day_tue" value="Y" <?php if($this->parent->getClassVar($current, 'filter_time_day_tue') == "Y") print "checked=\"checked\"";?> /><? print _("Tue"); ?>
         <input type="checkbox" name="filter_time_day_wed" value="Y" <?php if($this->parent->getClassVar($current, 'filter_time_day_wed') == "Y") print "checked=\"checked\"";?> /><? print _("Wed"); ?>
         <input type="checkbox" name="filter_time_day_thu" value="Y" <?php if($this->parent->getClassVar($current, 'filter_time_day_thu') == "Y") print "checked=\"checked\"";?> /><? print _("Thu"); ?>
         <input type="checkbox" name="filter_time_day_fri" value="Y" <?php if($this->parent->getClassVar($current, 'filter_time_day_fri') == "Y") print "checked=\"checked\"";?> /><? print _("Fri"); ?>
         <input type="checkbox" name="filter_time_day_sat" value="Y" <?php if($this->parent->getClassVar($current, 'filter_time_day_sat') == "Y") print "checked=\"checked\"";?> /><? print _("Sat"); ?>
         <input type="checkbox" name="filter_time_day_sun" value="Y" <?php if($this->parent->getClassVar($current, 'filter_time_day_sun') == "Y") print "checked=\"checked\"";?> /><? print _("Sun"); ?>
	</td>
       </tr>
      </table>
     </td>
     <td>
      <?php print _("Match if the packet is within a defined timerange. Nice for file transfer operations, which you want to limit during the day, but have full bandwidth in the night for backup. This uses the time iptables match which has to be available on your iptables installation and supported by your running kernel."); ?>
     </td>
    </tr>
    <tr>
     <td>
      FTP data:
     </td>
     <td>
      <input type="checkbox" name="filter_match_ftp_data" value="Y" <?php if($this->parent->getClassVar($current, 'filter_match_ftp_data') == "Y") print "checked=\"checked\""; ?> /><? print _("Match FTP data channel"); ?>
     </td>
     <td>
      <?php print _("A FTP file transfer needs two connections: command channel (21/tcp) and a data channel. If you use active FTP the port for data channel is 20/tcp. If you use passive FTP, the port of the data channel is not predictable and is choosen by the ftp server (high port). But with the help of the iptables kernel module ip_conntrack_ftp you get the data channel which belongs to the command channel! Don't forget to load the ip_conntrack_ftp module!"); ?>
     </td>
    </tr>
    <tr>
     <td>
      SIP:
     </td>
     <td>
      <input type="checkbox" name="filter_match_sip" value="Y" <?php if($this->parent->getClassVar($current, 'filter_match_sip') == "Y") print "checked=\"checked\""; ?> /><? print _("Match SIP connections"); ?>
     </td>
     <td>
      <?php print _("This match allows you to match of dynamic RTP/RTCP data streams of sip sessions as well as SIP request/responses. Don't forget to load the ip_conntrack_sip module!"); ?>
     </td>
    </tr>
<?php
               }
?>
    <tr>
     <td colspan="3">&nbsp;</td>
    </tr>
    <tr>
     <td style="text-align: center;"><a href="<?php print $this->parent->self ."?mode=". $this->parent->mode; ?>" title="Back"><img src="<? print ICON_ARROW_LEFT; ?>" alt="arrow left icon" /></a></td>
     <td><input type="submit" value="<?php print _("Save"); ?>" onclick="selectAll(document.forms['filters'].elements['used[]']); selectAll(document.forms['filters'].elements['filter_l7_used[]']);" /></td>
     <td><?php print _("Save settings."); ?></td>
    </tr>
   </table> 
  </form>
<?php

               $this->parent->closeTable();

            }
            else {

               $error = false;

               if(!isset($_POST['filter_name']) ||
                  $_POST['filter_name'] == "") {

                  $this->parent->printError("<img src=\"". ICON_FILTERS ."\""
                     ." alt=\"filter icon\" />&nbsp;". _("Manage Filter"),
                      _("Please enter a filter name!"));

                  $error = true;

               }

               if(!$error &&
                  isset($_GET['new']) &&
                  $this->db->db_fetchSingleRow("
                     SELECT filter_idx 
                     FROM ". MYSQL_PREFIX ."filters
                     WHERE filter_name LIKE BINARY '". $_POST['filter_name'] ."'")) {

                  $this->parent->printError("<img src=\"". ICON_FILTERS ."\""
                     ." alt=\"filter icon\" />&nbsp;". _("Manage Filter"),
                     _("A filter with that name already exists!")
                  );

                  $error = true;

               }

               if(!$error &&
                  !isset($_GET['new']) &&
                  $_GET['namebefore'] != $_POST['filter_name'] &&
                  $this->db->db_fetchSingleRow("
                     SELECT filter_idx FROM ". MYSQL_PREFIX ."filters
                     WHERE filter_name LIKE BINARY '". $_POST['filter_name'] ."'")) {

                  $this->parent->printError("<img src=\"". ICON_FILTERS ."\""
                     ." alt=\"filter icon\" />&nbsp;". _("Manage Filter"),
                     _("A filter with that name already exists!")
                  );

                  $error = true;

               }

               if(!$error &&
                  $_POST['filter_protocol_id'] == -1 &&
                  count($_POST['used']) <= 1 &&
                  $_POST['filter_tos'] == -1 &&
                  !$_POST['filter_tcpflag_syn'] &&
                  !$_POST['filter_tcpflag_ack'] &&
                  !$_POST['filter_tcpflag_fin'] &&
                  !$_POST['filter_tcpflag_rst'] &&
                  !$_POST['filter_tcpflag_urg'] &&
                  !$_POST['filter_tcpflag_psh'] &&
                  !$_POST['filter_packet_length'] &&
                  !$_POST['filter_p2p_edk'] &&
                  !$_POST['filter_p2p_kazaa'] &&
                  !$_POST['filter_p2p_dc'] &&
                  !$_POST['filter_p2p_gnu'] &&
                  !$_POST['filter_p2p_bit'] &&
                  !$_POST['filter_p2p_apple'] &&
                  !$_POST['filter_p2p_soul'] &&
                  !$_POST['filter_p2p_winmx'] &&
                  !$_POST['filter_p2p_ares'] &&
                  !$_POST['filter_time_use_range'] &&
                  !$_POST['filter_time_day_mon'] &&
                  !$_POST['filter_time_day_tue'] &&
                  !$_POST['filter_time_day_wed'] &&
                  !$_POST['filter_time_day_thu'] &&
                  !$_POST['flter_time_day_fri'] &&
                  !$_POST['filter_time_day_sat'] &&
                  !$_POST['filter_time_day_sun'] &&
                  !$_POST['filter_match_sip'] &&
                  count($_POST['filter_l7_used']) <= 1) {

                  $this->parent->printError("<img src=\"". ICON_FILTERS ."\""
                     ." alt=\"filter icon\" />&nbsp;". _("Manage Filter"),
                     _("This filter has nothing to do. Please select at least one match!")
                  );

                  $error = true;
               }

               /* Ports can only be used with TCP, UDP or IP protocol */
               if(!$error &&
                  count($_POST['used']) > 1 &&
                  (
                     $this->parent->getProtocolNumberById($_POST['filter_protocol_id']) != 4 &&
                     $this->parent->getProtocolNumberById($_POST['filter_protocol_id']) != 17 &&
                     $this->parent->getProtocolNumberById($_POST['filter_protocol_id']) != 6
                  )) {

                  $this->parent->printError("<img src=\"". ICON_FILTERS ."\""
                     ." alt=\"filter icon\" />&nbsp;". _("Manage Filter"),
                     _("Ports can only be used in combination with IP, TCP or UDP protocol!")
                  );

                  $error = true;

               }

               /* TCP-flags can only be used with TCP protocol */
               if(!$error &&
                  (
                     $_POST['filter_tcpflag_syn'] ||
                     $_POST['filter_tcpflag_ack'] ||
                     $_POST['filter_tcpflag_fin'] ||
                     $_POST['filter_tcpflag_rst'] ||
                     $_POST['filter_tcpflag_urg'] ||
                     $_POST['filter_tcpflag_psh']
                  ) &&
                  $this->parent->getProtocolNumberById($_POST['filter_protocol_id']) != 6) {

                  $this->parent->printError("<img src=\"". ICON_FILTERS ."\""
                     ." alt=\"filter icon\" />&nbsp;". _("Manage Filter"),
                     _("TCP-Flags can only be used in combination with TCP protocol!")
                  );

                  $error = true;

               }

               /* ipp2p can only be used with no ports, no l7 filters and tcp &| udp protocol */
               if(!$error &&
                  (
                     $_POST['filter_p2p_edk'] ||
                     $_POST['filter_p2p_kazaa'] ||
                     $_POST['filter_p2p_dc'] ||
                     $_POST['filter_p2p_gnu'] || 
                     $_POST['filter_p2p_bit'] ||
                     $_POST['filter_p2p_apple'] ||
                     $_POST['filter_p2p_soul'] ||
                     $_POST['filter_p2p_winmx'] ||
                     $_POST['filter_p2p_ares']
                  ) &&
                  (
                     count($_POST['used']) > 1 || 
                     (
                        (
                           $this->parent->getProtocolNumberById(
                              $_POST['filter_protocol_id']) != 17 &&
                           $this->parent->getProtocolNumberById($_POST['filter_protocol_id']) != 6
                        ) &&
                        $_POST['filter_protocol_id'] != -1
                     ) ||
                     count($_POST['filter_l7_used']) > 1)) {

                  $this->parent->printError("<img src=\"". ICON_FILTERS ."\""
                     ." alt=\"filter icon\" />&nbsp;". _("Manage Filter"),
                     _("IPP2P match can only be used with no ports select and only with protocols TCP or UDP or completly ignoring protocols!<br />Also IPP2P can not be used in combination with layer7 filters.")
                  );

                  $error = true;

               }

               /* layer7 protocol match can only be used with no ports and no tcp &| udp protocols */
               if(!$error &&
                  count($_POST['filter_l7_used']) > 1 &&
                  $_POST['filter_protocol_id'] != -1) {

                  $this->parent->printError("<img src=\"". ICON_FILTERS ."\""
                     ." alt=\"filter icon\" />&nbsp;". _("Manage Filter"),
                     _("Layer7 match can only be used with no ports select and no protocol definitions!")
                  );
		  
                  $error = true;

               }

               if(!$error) {

                  $start_time = strtotime(sprintf("%04d-%02d-%02d %02d:%02d:00", 
                     $_POST['filter_time_start_year'],
                     $_POST['filter_time_start_month'],
                     $_POST['filter_time_start_day'],
                     $_POST['filter_time_start_hour'], 
                     $_POST['filter_time_start_minute']));
                  $stop_time = strtotime(sprintf("%04d-%02d-%02d %02d:%02d:00",
                     $_POST['filter_time_stop_year'],
                     $_POST['filter_time_stop_month'],
                     $_POST['filter_time_stop_day'],
                     $_POST['filter_time_stop_hour'],
                     $_POST['filter_time_stop_minute']));

                  if(isset($_GET['new'])) {

                     $this->db->db_query("
                        INSERT INTO ". MYSQL_PREFIX ."filters (
                           filter_name, filter_protocol_id, filter_TOS,
                           filter_tcpflag_syn, filter_tcpflag_ack,
                           filter_tcpflag_fin, filter_tcpflag_rst,
                           filter_tcpflag_urg, filter_tcpflag_psh, 
                           filter_packet_length, filter_p2p_edk,
                           filter_p2p_kazaa, filter_p2p_dc, 
                           filter_p2p_gnu, filter_p2p_bit, filter_p2p_apple,
                           filter_p2p_soul, filter_p2p_winmx, filter_p2p_ares,
                           filter_time_use_range, filter_time_start,
                           filter_time_stop, filter_time_day_mon, 
                           filter_time_day_tue, filter_time_day_wed,
                           filter_time_day_thu, filter_time_day_fri,
                           filter_time_day_sat, filter_time_day_sun, 
                           filter_match_ftp_data, filter_match_sip,
                           filter_active
                        ) 
                        VALUES (
                        '". $_POST['filter_name'] ."',
                        '". $_POST['filter_protocol_id'] ."',
                        '". $_POST['filter_tos'] ."', 
                        '". $_POST['filter_tcpflag_syn'] ."',
                        '". $_POST['filter_tcpflag_ack'] ."',
                        '". $_POST['filter_tcpflag_fin'] ."',
                        '". $_POST['filter_tcpflag_rst'] ."',
                        '". $_POST['filter_tcpflag_urg'] ."',
                        '". $_POST['filter_tcpflag_psh'] ."',
                        '". $_POST['filter_packet_length'] ."',
                        '". $_POST['filter_p2p_edk'] ."',
                        '". $_POST['filter_p2p_kazaa'] ."',
                        '". $_POST['filter_p2p_dc'] ."',
                        '". $_POST['filter_p2p_gnu'] ."', 
                        '". $_POST['filter_p2p_bit'] ."',
                        '". $_POST['filter_p2p_apple'] ."',
                        '". $_POST['filter_p2p_soul'] ."', 
                        '". $_POST['filter_p2p_winmx'] ."',
                        '". $_POST['filter_p2p_ares'] ."',
                        '". $_POST['filter_time_use_range'] ."',
                        '". $start_time ."',
                        '". $stop_time ."',
                        '". $_POST['filter_time_day_mon'] ."',
                        '". $_POST['filter_time_day_tue'] ."',
                        '". $_POST['filter_time_day_wed'] ."',
                        '". $_POST['filter_time_day_thu'] ."',
                        '". $_POST['filter_time_day_fri'] ."',
                        '". $_POST['filter_time_day_sat'] ."',
                        '". $_POST['filter_time_day_sun'] ."',
                        '". $_POST['filter_match_ftp_data'] ."', 
                        '". $_POST['filter_match_sip'] ."',
                        '". $_POST['filter_active'] ."')
                     ");
			     
                     $_GET['idx'] = $this->db->db_getid();

                  }
                  else {

                     switch($this->parent->getOption("filter")) {

                        case 'ipt':

                           $this->db->db_query("
                              UPDATE ". MYSQL_PREFIX ."filters 
                              SET
                              filter_name='". $_POST['filter_name'] ."', 
                              filter_protocol_id='". $_POST['filter_protocol_id'] ."', 
                              filter_tos='". $_POST['filter_tos'] ."', 
                              filter_tcpflag_syn='". $_POST['filter_tcpflag_syn'] ."', 
                              filter_tcpflag_ack='". $_POST['filter_tcpflag_ack'] ."', 
                              filter_tcpflag_fin='". $_POST['filter_tcpflag_fin'] ."', 
                              filter_tcpflag_rst='". $_POST['filter_tcpflag_rst'] ."', 
                              filter_tcpflag_urg='". $_POST['filter_tcpflag_urg'] ."', 
                              filter_tcpflag_psh='". $_POST['filter_tcpflag_psh'] ."', 
                              filter_packet_length='". $_POST['filter_packet_length'] ."', 
                              filter_p2p_edk='". $_POST['filter_p2p_edk'] ."', 
                              filter_p2p_kazaa='". $_POST['filter_p2p_kazaa'] ."', 
                              filter_p2p_dc='". $_POST['filter_p2p_dc'] ."', 
                              filter_p2p_gnu='". $_POST['filter_p2p_gnu'] ."', 
                              filter_p2p_bit='". $_POST['filter_p2p_bit'] ."', 
                              filter_p2p_apple='". $_POST['filter_p2p_apple'] ."', 
                              filter_p2p_soul='". $_POST['filter_p2p_soul'] ."', 
                              filter_p2p_winmx='". $_POST['filter_p2p_winmx'] ."', 
                              filter_p2p_ares='". $_POST['filter_p2p_ares'] ."', 
                              filter_time_use_range='". $_POST['filter_time_use_range'] ."', 
                              filter_time_start='". $start_time ."', 
                              filter_time_stop='". $stop_time ."', 
                              filter_time_day_mon='". $_POST['filter_time_day_mon'] ."', 
                              filter_time_day_tue='". $_POST['filter_time_day_tue'] ."', 
                              filter_time_day_wed='". $_POST['filter_time_day_wed'] ."', 
                              filter_time_day_thu='". $_POST['filter_time_day_thu'] ."',
                              filter_time_day_fri='". $_POST['filter_time_day_fri'] ."',
                              filter_time_day_sat='". $_POST['filter_time_day_sat'] ."',
                              filter_time_day_sun='". $_POST['filter_time_day_sun'] ."',
                              filter_match_ftp_data='". $_POST['filter_match_ftp_data'] ."', 
                              filter_match_sip='". $_POST['filter_match_sip'] ."', 
                              filter_active='". $_POST['filter_active'] ."' 
                              WHERE filter_idx='". $_GET['idx'] ."'
                           ");

                           break;

                        case 'tc':

                           $this->db->db_query("
                              UPDATE ". MYSQL_PREFIX ."filters
                              SET filter_name='". $_POST['filter_name'] ."',
                              filter_protocol_id='". $_POST['filter_protocol_id'] ."',
                              filter_tos='". $_POST['filter_tos'] ."', 
                              filter_active='". $_POST['filter_active'] ."' 
                              WHERE filter_idx='". $_GET['idx'] ."'
                           ");

                           break;
                     }

                  }

                  if($_POST['used']) {

                     $this->db->db_query("
                        DELETE FROM ". MYSQL_PREFIX ."assign_ports
                        WHERE afp_filter_idx='". $_GET['idx'] ."'
                     ");

                     foreach($_POST['used'] as $use) {

                        if($use != "") {

                           $this->db->db_query("
                              INSERT INTO ". MYSQL_PREFIX ."assign_ports
                              (afp_filter_idx, afp_port_idx) 
                              VALUES
                              ('". $_GET['idx'] ."', '". $use ."')
                           ");

                        }
                     }

                     if($_POST['filter_l7_used']) {

                        $this->db->db_query("
                           DELETE FROM ". MYSQL_PREFIX ."assign_l7_protocols
                           WHERE afl7_filter_idx='". $_GET['idx'] ."'
                        ");

                        foreach($_POST['filter_l7_used'] as $use) {

                           if($use != "") {

                              $this->db->db_query("
                                 INSERT INTO ". MYSQL_PREFIX ."assign_l7_protocols
                                 (afl7_filter_idx, afl7_l7proto_idx) 
                                 VALUES
                                 ('". $_GET['idx'] ."', '". $use ."')
                              ");

                           }
                        }

                     }
                  }

                  $this->parent->goBack();

               }
            }
            break;

         case DELETE:

            if(!isset($_GET['doit'])) {

               $this->parent->printYesNo("<img src=\"". ICON_FILTERS ."\""
                  ." alt=\"filter icon\" />&nbsp;". _("Delete Filter"),
                  _("Delete Filter") ." ". $_GET['name'] ."?");

            }
            else {

               if($_GET['idx']) {

                  $this->db->db_query("
                     DELETE FROM ". MYSQL_PREFIX ."filters
                     WHERE filter_idx='". $_GET['idx'] ."'
                  ");
                  $this->db->db_query("
                     DELETE FROM ". MYSQL_PREFIX ."assign_ports
                     WHERE afp_filter_idx='". $_GET['idx'] ."'
                  ");
                  $this->db->db_query("
                     DELETE FROM ". MYSQL_PREFIX ."assign_l7_protocols
                     WHERE afl7_filter_idx='". $_GET['idx'] ."'
                  ");
                  $this->db->db_query("
                     DELETE FROM ". MYSQL_PREFIX ."assign_filters
                     WHERE apf_filter_idx='". $_GET['idx'] ."'
                  ");
               }

               $this->parent->goBack();

            }
            break;

         case CHGSTATUS:

            if(isset($_GET['idx'])) {
	       
               if($_GET['to'] == 0) {

                  $this->db->db_query("
                     UPDATE ". MYSQL_PREFIX ."filters
                     SET filter_active='N'
                     WHERE filter_idx='". $_GET['idx'] ."'
                  ");
               }
               elseif($_GET['to'] == 1) {

                  $this->db->db_query("
                     UPDATE ". MYSQL_PREFIX ."filters
                     SET filter_active='Y'
                     WHERE filter_idx='". $_GET['idx'] ."'");
               }
            }

            $this->parent->goBack();
            break;

      }

   } // showHtml()

}

?>
