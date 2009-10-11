<pre id="target"></pre>
<form action="{$page->uri}" id="filters" onsubmit="selectAll('used[]'); selectAll('filter_l7_used[]');" method="post">
<input type="hidden" name="module" value="filter" />
<input type="hidden" name="action" value="store" />
{ if !$filter_idx }
 {start_table icon=$icon_filters alt="filter icon" title="Create a new Filter" }
 <input type="hidden" name="filter_new" value="1" />
{ else }
 {start_table icon=$icon_filters alt="filter icon" title="Modify filter $filter_name" }
 <input type="hidden" name="filter_new" value="0" />
 <input type="hidden" name="namebefore" value="{ $filter_name }" />
 <input type="hidden" name="filter_idx" value="{ $filter_idx }" />
{ /if }
<table style="width: 100%;" class="withborder2"> 
 <tr>
  <td colspan="3">
   <img src="{ $icon_filters }" alt="filter icon" />&nbsp;General
  </td>
 </tr>
 <tr>
  <td style="white-space: nowrap;">Name:</td>
  <td><input type="text" name="filter_name" size="30" value="{ $filter_name }" /></td>
  <td>Name of the filter.</td>
 </tr>
 <tr>
  <td style="white-space: nowrap;">Status:</td>
  <td>
   <input type="radio" name="filter_active" value="Y" { if $filter_active == 'Y' } checked="checked" { /if } />Active
   <input type="radio" name="filter_active" value="N" { if $filter_active != 'Y' } checked="checked" { /if } />Inactive
  </td>
  <td>
   Will these filter be used or not.
  </td>
 </tr>
 <tr>
  <td colspan="3">
   <img src="{ $icon_filters }" alt="filter icon" />&nbsp;Match protocols
  </td>
 </tr>
 <tr>
  <td style="white-space: nowrap;">
   Protocols:
  </td>
  <td>
   <select name="filter_protocol_id">
    <option value="-1">--- Ignore ---</option>
    { protocol_select_list proto_idx=$filter_protocol_id }
   </select>
  </td>
  <td>
   Match on this protocol. Select TCP or UDP if you want to use port definitions! If you want to match both TCP &amp; UDP use IP as protocol. Be aware that tc-filter can not differ between TCP &amp; UDP. It will match both at the same time!
  </td>
 </tr>
 <tr>
  <td colspan="3">
   <img src="{ $icon_filters }" alt="filter icon" />&nbsp;Match ports
  </td>
 </tr>
 <tr>
  <td style="white-space: nowrap;">Ports:</td>
  <td>
   <table class="noborder">
    <tr>
     <td>
      <select size="10" name="avail[]" multiple="multiple">;
       <option value="">********* Unused *********</option>
       { port_select_list filter_idx=$filter_idx mode=unused }
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
       <option value="">********* Used *********</option>
       { port_select_list filter_idx=$filter_idx mode=used }
      </select>
     </td>
    </tr>
   </table>
  </td>
  <td>Match on specific ports. Be aware that this will only work for TCP/UDP protocols!</td>
 </tr>
 <tr>
  <td colspan="3">
   <img src="{ $icon_filters }" alt="filter icon" />&nbsp;Match protocol flags
  </td>
 </tr>
 <tr>
  <td style="white-space: nowrap;">
   TOS flags:
  </td>
  <td>
   <select name="filter_tos">
    <option value="-1"   { if $filter_tos == "-1"   } selected="selected" { /if }>Ignore</option>
    <option value="0x10" { if $filter_tos == "0x10" } selected="selected" { /if }>Minimize-Delay 16 (0x10)</option>
    <option value="0x08" { if $filter_tos == "0x08" } selected="selected" { /if }>Maximize-Throughput 8 (0x08)</option>
    <option value="0x04" { if $filter_tos == "0x04" } selected="selected" { /if }>Maximize-Reliability 4 (0x04)</option>
    <option value="0x02" { if $filter_tos == "0x02" } selected="selected" { /if }>Minimize-Cost 2 (0x02)</option>
    <option value="0x00" { if $filter_tos == "0x00" } selected="selected" { /if }>Normal-Service 0 (0x00)</option>
   </select>
  </td>
  <td>
   Match a specific TOS flag.
  </td>
 </tr>
 { if $filter_mode == "ipt" }
 <input type="hidden" name="filter_ipt" value="true" />
 <tr>
  <td style="white-space: nowrap;">
   TCP flags:
  </td>
  <td>
   <table class="noborder">
    <tr>
     <td><input type="checkbox" name="filter_tcpflag_syn" value="Y" { if $filter_tcpflag_syn == "Y" } checked="checked" { /if } />SYN</td>
     <td><input type="checkbox" name="filter_tcpflag_ack" value="Y" { if $filter_tcpflag_ack == "Y" } checked="checked" { /if } />ACK</td>
     <td><input type="checkbox" name="filter_tcpflag_fin" value="Y" { if $filter_tcpflag_fin == "Y" }  checked="checked" { /if } />FIN</td>
    </tr>
    <tr>
     <td><input type="checkbox" name="filter_tcpflag_rst" value="Y" { if $filter_tcpflag_rst == "Y" }  checked="checked" { /if } />RST</td>
     <td><input type="checkbox" name="filter_tcpflag_urg" value="Y" { if $filter_tcpflag_urg == "Y" }  checked="checked" { /if } />URG</td>
     <td><input type="checkbox" name="filter_tcpflag_psh" value="Y" { if $filter_tcpflag_psh == "Y" }  checked="checked" { /if } />PSH</td>
    </tr>
   </table>
  </td>
  <td>
   Match on specific TCP flags combinations.
  </td>
 </tr>
 <tr>
  <td style="white-space: nowrap;">
   Packet length:
  </td>
  <td>
   <input type="text" name="filter_packet_length" size="30" value="{ $filter_packet_length }" />
  </td>
  <td>
   Match a packet against a defined size. Enter a size \"64\" or a range \"64:128\".
  </td>
 </tr>
 <tr>
  <td colspan="3">
   <img src="{ $icon_filters }" alt="filter icon" />&nbsp;Other matches
  </td>
 </tr>
 <tr>
  <td style="white-space: nowrap;">
   IPP2P:
  </td>
  <td>
   <table class="noborder">
    <tr>
     <td><input type="checkbox" name="filter_p2p_edk" value="Y" { if $filter_p2p_edk == "Y" } checked="checked" { /if } />Edonkey</td>
<td><input type="checkbox" name="filter_p2p_kazaa" value="Y" { if $filter_p2p_kazaa == "Y" } checked="checked" { /if } />Kazaa</td>
     <td><input type="checkbox" name="filter_p2p_dc" value="Y" { if $filter_p2p_dc == "Y" } checked="checked" { /if } />Direct Connect (DC)</td>
    </tr>
    <tr>
<td><input type="checkbox" name="filter_p2p_gnu" value="Y" { if $filter_p2p_gnu == "Y" } checked="checked" { /if } />Gnutella</td>
<td><input type="checkbox" name="filter_p2p_bit" value="Y" { if $filter_p2p_bit == "Y" } checked="checked" { /if } />Bittorent</td>
<td><input type="checkbox" name="filter_p2p_apple" value="Y" { if $filter_p2p_apple == "Y" } checked="checked" { /if } />AppleJuice</td>
    </tr>
    <tr>
     <td><input type="checkbox" name="filter_p2p_soul" value="Y" { if $filter_p2p_soul == "Y" } checked="checked" { /if } />SoulSeek</td>
<td><input type="checkbox" name="filter_p2p_winmx" value="Y" { if $filter_p2p_winmx == "Y" } checked="checked" { /if } />WinMX</td>
     <td><input type="checkbox" name="filter_p2p_ares" value="Y" { if $filter_p2p_ares == "Y" } checked="checked" { /if } />Ares</td>
    </tr>
   </table>
  </td>
  <td>
   Match on specific filesharing protocols. This uses the ipp2p iptables module. It has to be available on your iptables installation. Refer <a href="http://www.ipp2p.org" onclick="window.open('http://www.ipp2p.org'); return false;">www.ipp2p.org</a> for more informations.
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
       <option value="">********* Unused *********</option>
       { l7_select_list filter_idx=$filter_idx mode=unused }
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
       <option value="">********* Used *********</option>
       { l7_select_list filter_idx=$filter_idx mode=used }
      </select>
     </td>
    </tr>
   </table>
  </td>
  <td>
   Match on specific protocols. This uses the layer7 iptables module. It has to be available on your iptables installation. Refer <a http="http://l7-filter.sourceforge.net" onclick="window.open('http://l7-filter.sourceforge.net'); return false;">l7-filter.sf.net</a> for more informations.<br /><br />Use Other-&gt;Update L7 Protocols to load current available l7 pat files.
  </td>
 </tr>
 <tr>
  <td style="white-space: nowrap;">
   Time:
  </td>
  <td>
   <table class="noborder">
    <tr>
     <td colspan="2">
 <input type="checkbox" name="filter_time_use_range" value="Y" { if $filter_time_use_range == "Y" } checked="checked" { /if } />Use time range:
</td>
    </tr>
    <tr>
     <td colspan="2">&nbsp;</td>
    </tr>
    <tr>
     <td>
      Start:
     </td>
     <td>
      <select name="filter_time_start_year">
       { year_select current=$filter_time_start }
      </select>
      -
      <select name="filter_time_start_month">
       { month_select current=$filter_time_start }
      </select>
      -
      <select name="filter_time_start_day">
       { day_select current=$filter_time_start }
      </select>
      &nbsp;
      <select name="filter_time_start_hour">
       { hour_select current=$filter_time_start }
      </select>
      :
      <select name="filter_time_start_minute">
       { minute_select current=$filter_time_start }
      </select>
     </td>
    </tr>
    <tr>
     <td>
      Stop:
     </td>
     <td>
      <select name="filter_time_stop_year">
       { year_select current=$filter_time_stop }
      </select>
      -
      <select name="filter_time_stop_month">
       { month_select current=$filte_time_stop }
      </select>
      -
      <select name="filter_time_stop_day">
       { day_select current=$filter_time_stop }
      </select>
      &nbsp;
      <select name="filter_time_stop_hour">
       { hour_select current=$filter_time_stop }
      </select>
      :
      <select name="filter_time_stop_minute">
       { minute_select current=$filter_time_stop }
      </select>
     </td>
    </tr>
    <tr>
     <td colspan="2">&nbsp;</td>
    </tr>
    <tr>
     <td>
      Days:
     </td>
     <td>
      <input type="checkbox" name="filter_time_day_mon" value="Y" { if $filter_time_day_mon == "Y" } chkeced="checked" { /if } />Mon
      <input type="checkbox" name="filter_time_day_tue" value="Y" { if $filter_time_day_tue == "Y" } chkeced="checked" { /if } />Tue
      <input type="checkbox" name="filter_time_day_wed" value="Y" { if $filter_time_day_wed == "Y" } chkeced="checked" { /if } />Wed
      <input type="checkbox" name="filter_time_day_thu" value="Y" { if $filter_time_day_thu == "Y" } chkeced="checked" { /if } />Thu
      <input type="checkbox" name="filter_time_day_fri" value="Y" { if $filter_time_day_fri == "Y" } chkeced="checked" { /if } />Fri
      <input type="checkbox" name="filter_time_day_sat" value="Y" { if $filter_time_day_sat == "Y" } chkeced="checked" { /if } />Sat
      <input type="checkbox" name="filter_time_day_sun" value="Y" { if $filter_time_day_sun == "Y" } chkeced="checked" { /if } />Sun
</td>
    </tr>
   </table>
  </td>
  <td>
   Match if the packet is within a defined timerange. Nice for file transfer operations, which you want to limit during the day, but have full bandwidth in the night for backup. This uses the time iptables match which has to be available on your iptables installation and supported by your running kernel.
  </td>
 </tr>
 <tr>
  <td>
   FTP data:
  </td>
  <td>
   <input type="checkbox" name="filter_match_ftp_data" value="Y" { if $filter_match_ftp_data == "Y" } checked="checked" { /if } />Match FTP data channel
  </td>
  <td>
   A FTP file transfer needs two connections: command channel (21/tcp) and a data channel. If you use active FTP the port for data channel is 20/tcp. If you use passive FTP, the port of the data channel is not predictable and is choosen by the ftp server (high port). But with the help of the iptables kernel module ip_conntrack_ftp you get the data channel which belongs to the command channel! Don't forget to load the ip_conntrack_ftp module!
  </td>
 </tr>
 <tr>
  <td>
   SIP:
  </td>
  <td>
   <input type="checkbox" name="filter_match_sip" value="Y" { if $filter_match_sip == "Y" } checked="checked" { /if } />Match SIP connections
  </td>
  <td>
   This match allows you to match of dynamic RTP/RTCP data streams of sip sessions as well as SIP request/responses. Don't forget to load the ip_conntrack_sip module!
  </td>
 </tr>
 { /if }
 <tr>
  <td colspan="3">&nbsp;</td>
 </tr>
 <tr>
  <td style="text-align: center;"><a href="javascript:refreshContent('filters');" title="Back"><img src="{ $icon_arrow_left }" alt="arrow left icon" /></a></td>
  <td><input type="submit" value="Save" /></td>
  <td>Save settings.</td>
 </tr>
</table> 
{ page_end focus_to='filter_name' }
