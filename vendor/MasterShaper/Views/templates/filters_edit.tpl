{*
 * This file is part of MasterShaper.

 * MasterShaper, a web application to handle Linux's traffic shaping
 * Copyright (C) 2007-2016 Andreas Unterkircher <unki@netshadow.net>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.

 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
*}
<h1 class="ui header breadcrumb">
 <a class="section" href="{get_url page="filters"}"><img src="{$icon_filters}" alt="filters icon" />&nbsp;Filters</a>
 <div class="divider"> / </div>
 <div class="active section">Edit {if $filter->hasName()}{$filter->getName()}{/if}</div>
</h1>
<div class="ui divider"></div>
<form class="thallium ui form" method="POST" data-id="{$filter->getId()}" data-guid="{$filter->getGuid()}" data-model="filter" data-url-next="{get_url page='filters'}" data-url-discard="{get_url page='filters'}">
 <h4 class="ui block header">General Setttings</h4>
 <div class="field">
  <label>Name</label>
  <div class="ui input">
   <input type="text" placeholder="enter a filter name" name="filter_name" value="{if $filter->hasName()}{$filter->getName()}{/if}" />
  </div>
  <div class="extra">
  </div>
 </div>
 <div class="field">
  <label>Active</label>
  <div class="ui radio checkbox">
   <input type="radio" name="filter_active" value="Y" {if $filter->isActive()} checked="checked" {/if} />
   <label>yes</label>
  </div>
  <div class="ui radio checkbox">
   <input type="radio" name="filter_active" value="N" {if !$filter->isActive()} checked="checked" {/if} />
   <label>no</label>
  </div>
 </div>
 <h4 class="ui block header">Match protocols</h4>
 <div class="field">
  <label>Protocols</label>
  <select name="filter_protocol_id">
   <option value="-1">--- Ignore ---</option>
   {protocol_select_list proto_idx=($filter->hasProtocol()) ? $filter->getProtocol() : null}
  </select>
  <img class="change_to" src="{$icon_arrow_right}" value="Go" onclick="if($('select[name=filter_protocol_id]').val() > 0) change_to('{get_url page='protocols' mode='edit' id=0}', $('select[name=filter_protocol_id]').val()); return false ;" title="Click to edit currently selected protocol" />
  <div class="extra">Match on this protocol. Select TCP or UDP if you want to use port definitions! If you want to match both TCP &amp; UDP use IP as protocol. Be aware that tc-filter can not differ between TCP &amp; UDP. It will match both at the same time!</div>
 </div>
 <h4 class="ui block header">Match ports</h4>
 <div class="field">
  <label>Ports:</label>
  <table class="noborder">
   <tr>
    <td>
     <select size="10" id="filters_avail" name="avail[]" multiple="multiple">;
      <option value="">********* Unused *********</option>
      {port_select_list filter_idx=$filter->getId() mode=unused}
     </select>
    </td>
    <td>&nbsp;</td>
    <td>
     <input type="button" value="&gt;&gt;" onclick="moveOptions('filters_avail', 'filters_used');" /><br />
     <input type="button" value="&lt;&lt;" onclick="moveOptions('filters_used', 'filters_avail');" />
    </td>
    <td>&nbsp;</td>
    <td>
     <select size="10" id="filters_used" name="used[]" multiple="multiple">
      <option value="">********* Used *********</option>
      {port_select_list filter_idx=$filter->getId() mode=used}
     </select>
    </td>
   </tr>
  </table>
  <div class="field">Match on specific ports. Be aware that this will only work for TCP/UDP protocols!</div>
 </div>
 <h4 class="ui block header">Match protocol flags</h4>
 <div class="field">
  <label>TOS flags:</label>
  <select name="filter_tos">
   <option value="-1"   {if $filter->hasTos() && $filter->getTos() == "-1"} selected="selected" {/if}>Ignore</option>
   <option value="0x10" {if $filter->hasTos() && $filter->getTos() == "0x10"} selected="selected" {/if}>Minimize-Delay 16 (0x10)</option>
   <option value="0x08" {if $filter->hasTos() && $filter->getTos() == "0x08"} selected="selected" {/if}>Maximize-Throughput 8 (0x08)</option>
   <option value="0x04" {if $filter->hasTos() && $filter->getTos() == "0x04"} selected="selected" {/if}>Maximize-Reliability 4 (0x04)</option>
   <option value="0x02" {if $filter->hasTos() && $filter->getTos() == "0x02"} selected="selected" {/if}>Minimize-Cost 2 (0x02)</option>
   <option value="0x00" {if $filter->hasTos() && $filter->getTos() == "0x00"} selected="selected" {/if}>Normal-Service 0 (0x00)</option>
  </select>
  <div class="extra">Match a specific TOS flag.</div>
 </div>
 <div class="field">
  <label>DSCP flags</label>
  <select name="filter_dscp">
   <option value="-1"   {if $filter->hasDscp() && $filter->getDscp() == "-1"} selected="selected" {/if}>Default</option>
   <option value="AF11" {if $filter->hasDscp() && $filter->getDscp() == "AF11"} selected="selected" {/if}>AF11</option>
   <option value="AF12" {if $filter->hasDscp() && $filter->getDscp() == "AF12"} selected="selected" {/if}>AF12</option>
   <option value="AF13" {if $filter->hasDscp() && $filter->getDscp() == "AF13"} selected="selected" {/if}>AF13</option>
   <option value="AF21" {if $filter->hasDscp() && $filter->getDscp() == "AF21"} selected="selected" {/if}>AF21</option>
   <option value="AF22" {if $filter->hasDscp() && $filter->getDscp() == "AF22"} selected="selected" {/if}>AF22</option>
   <option value="AF23" {if $filter->hasDscp() && $filter->getDscp() == "AF23"} selected="selected" {/if}>AF23</option>
   <option value="AF31" {if $filter->hasDscp() && $filter->getDscp() == "AF31"} selected="selected" {/if}>AF31</option>
   <option value="AF32" {if $filter->hasDscp() && $filter->getDscp() == "AF32"} selected="selected" {/if}>AF32</option>
   <option value="AF33" {if $filter->hasDscp() && $filter->getDscp() == "AF33"} selected="selected" {/if}>AF33</option>
   <option value="AF41" {if $filter->hasDscp() && $filter->getDscp() == "AF41"} selected="selected" {/if}>AF41</option>
   <option value="AF42" {if $filter->hasDscp() && $filter->getDscp() == "AF42"} selected="selected" {/if}>AF42</option>
   <option value="AF43" {if $filter->hasDscp() && $filter->getDscp() == "AF43"} selected="selected" {/if}>AF43</option>
   <option value="EF"   {if $filter->hasDscp() && $filter->getDscp() == "EF"} selected="selected" {/if}>EF</option>
  </select>
  <div class="extra">Match a specific DSCP flag. Expedited Forwarding (EF), Assured Forwarding (AF), Default is Best Effort (BE).</div>
 </div>
 {if isset($filter_mode) && $filter_mode == "ipt"}
 <div class="field">
  <label>TCP flags</label>
  <table class="noborder">
   <tr>
    <td onclick="obj_toggle_checkbox('[name=filter_tcpflag_syn]')"><input type="checkbox" name="filter_tcpflag_syn" value="Y" {if $filter->isTcpFlagSyn()} checked="checked" {/if} />SYN</td>
    <td onclick="obj_toggle_checkbox('[name=filter_tcpflag_ack]')"><input type="checkbox" name="filter_tcpflag_ack" value="Y" {if $filter->isTcpFlagAck()} checked="checked" {/if} />ACK</td>
    <td onclick="obj_toggle_checkbox('[name=filter_tcpflag_fin]')"><input type="checkbox" name="filter_tcpflag_fin" value="Y" {if $filter->isTcpFlagFin()} checked="checked" {/if} />FIN</td>
   </tr>
   <tr>
    <td onclick="obj_toggle_checkbox('[name=filter_tcpflag_rst]')"><input type="checkbox" name="filter_tcpflag_rst" value="Y" {if $filter->isTcpFlagRst()} checked="checked" {/if} />RST</td>
    <td onclick="obj_toggle_checkbox('[name=filter_tcpflag_urg]')"><input type="checkbox" name="filter_tcpflag_urg" value="Y" {if $filter->isTcpFlagUrg()} checked="checked" {/if} />URG</td>
    <td onclick="obj_toggle_checkbox('[name=filter_tcpflag_psh]')"><input type="checkbox" name="filter_tcpflag_psh" value="Y" {if $filter->isTcpFlagPsh()} checked="checked" {/if} />PSH</td>
   </tr>
  </table>
  <div class="extra">Match on specific TCP flags combinations.</div>
 </div>
 <div class="field">
  <label>Packet length</label>
  <input type="text" name="filter_packet_length" size="30" value="{if $filter->hasPacketLength()}{$filter->getPacketLength()}{/if}" />
  <div class="extra">Match a packet against a defined size. Enter a size \"64\" or a range \"64:128\".</div>
 </div>
 <h4 class="ui block header">Other matches</h4>
 <div class="field">
  <label>Time</label>
  <table class="noborder">
   <tr>
    <td colspan="2" onclick="obj_toggle_checkbox('[name=filter_time_use_range]')"><input type="checkbox" name="filter_time_use_range" value="Y" {if $filter->isTimeRange()} checked="checked" {/if} />Use time range:</td>
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
      {year_select current=($filter->hasTimeStart()) ? $filter->getTimeStart() : null}
     </select>
     -
     <select name="filter_time_start_month">
      {month_select current=($filter->hasTimeStart()) ? $filter->getTimeStart() : null}
     </select>
     -
     <select name="filter_time_start_day">
      {day_select current=($filter->hasTimeStart()) ? $filter->getTimeStart() : null}
     </select>
     &nbsp;
     <select name="filter_time_start_hour">
      {hour_select current=($filter->hasTimeStart()) ? $filter->getTimeStart() : null}
     </select>
     :
     <select name="filter_time_start_minute">
      {minute_select current=($filter->hasTimeStart()) ? $filter->getTimeStart() : null}
     </select>
    </td>
   </tr>
   <tr>
    <td>
     Stop:
    </td>
    <td>
     <select name="filter_time_stop_year">
      {year_select current=($filter->hasTimeStop()) ? $filter->getTimeStop() : null}
     </select>
     -
     <select name="filter_time_stop_month">
      {month_select current=($filter->hasTimeStop()) ? $filter->getTimeStop() : null}
     </select>
     -
     <select name="filter_time_stop_day">
      {day_select current=($filter->hasTimeStop()) ? $filter->getTimeStop() : null}
     </select>
     &nbsp;
     <select name="filter_time_stop_hour">
      {hour_select current=($filter->hasTimeStop()) ? $filter->getTimeStop() : null}
     </select>
     :
     <select name="filter_time_stop_minute">
      {minute_select current=($filter->hasTimeStop()) ? $filter->getTimeStop() : null}
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
     <input type="checkbox" name="filter_time_day_mon" value="Y" {if $filter->isTimeDayMon()} chkeced="checked" {/if} /><label onclick="obj_toggle_checkbox('[name=filter_time_day_mon]')">Mon</label>
     <input type="checkbox" name="filter_time_day_tue" value="Y" {if $filter->isTimeDayTue()} chkeced="checked" {/if} /><label onclick="obj_toggle_checkbox('[name=filter_time_day_tue]')">Tue</label>
     <input type="checkbox" name="filter_time_day_wed" value="Y" {if $filter->isTimeDayWed()} chkeced="checked" {/if} /><label onclick="obj_toggle_checkbox('[name=filter_time_day_wed]')">Wed</label>
     <input type="checkbox" name="filter_time_day_thu" value="Y" {if $filter->isTimeDayThu()} chkeced="checked" {/if} /><label onclick="obj_toggle_checkbox('[name=filter_time_day_thu]')">Thu</label>
     <input type="checkbox" name="filter_time_day_fri" value="Y" {if $filter->isTimeDayFri()} chkeced="checked" {/if} /><label onclick="obj_toggle_checkbox('[name=filter_time_day_fri]')">Fri</label>
     <input type="checkbox" name="filter_time_day_sat" value="Y" {if $filter->isTimeDaySat()} chkeced="checked" {/if} /><label onclick="obj_toggle_checkbox('[name=filter_time_day_sat]')">Sat</label>
     <input type="checkbox" name="filter_time_day_sun" value="Y" {if $filter->isTimeDaySun()} chkeced="checked" {/if} /><label onclick="obj_toggle_checkbox('[name=filter_time_day_sun]')">Sun</label>
    </td>
   </tr>
  </table>
  <div class="extra">Match if the packet is within a defined timerange. Nice for file transfer operations, which you want to limit during the day, but have full bandwidth in the night for backup. This uses the time iptables match which has to be available on your iptables installation and supported by your running kernel.</div>
 </div>
 <div class="field">
  <label>Match FTP data channel</label>
  <input type="checkbox" name="filter_match_ftp_data" value="Y" {if $filter->isMatchFtpData()} checked="checked" {/if} />
  <div class="extra">A FTP file transfer needs two connections: command channel (21/tcp) and a data channel. If you use active FTP the port for data channel is 20/tcp. If you use passive FTP, the port of the data channel is not predictable and is choosen by the ftp server (high port). But with the help of the iptables kernel module ip_conntrack_ftp you get the data channel which belongs to the command channel! Don't forget to load the ip_conntrack_ftp module!</div>
 </div>
 <div class="field">
  <label>Match SIP connections</label>
  <input type="checkbox" name="filter_match_sip" value="Y" {if $filter->isMatchSip()} checked="checked" {/if} />
  <div class="extra">This match allows you to match of dynamic RTP/RTCP data streams of sip sessions as well as SIP request/responses. Don't forget to load the ip_conntrack_sip module!</div>
 </div>
 {/if}
 <div class="ui divider"></div>
 {form_buttons submit=1 discard=1 reset=1}
</form>
<p class="footnote">
{if isset($pipe_use_filters) && !empty($pipe_use_filters)}
 This filter is assigned to the following pipes:<br />
 {foreach from=$pipe_use_filters key=pipe_idx item=pipe_name name=pipes}
  <a href="{get_url page='filters' mode='edit' id=$pipe->getSafeLink()}" title="Edit pipe {$pipe_name}"><img src="{$icon_pipes}" alt="pipe icon" />&nbsp;{$pipe_name}</a>{if !isset($smarty.foreach.pipes.last)},{/if}
 {foreachelse}
  none
 {/foreach}
{/if}
</p>
