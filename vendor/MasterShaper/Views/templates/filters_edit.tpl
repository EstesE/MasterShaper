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
<form class="thallium ui form" method="POST" data-id="{$filter->getIdx()}" data-guid="{$filter->getGuid()}" data-model="filter" data-url-next="{get_url page='filters'}" data-url-discard="{get_url page='filters'}">
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
      {port_select_list filter_idx=$filter->getIdx() mode=unused}
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
      {port_select_list filter_idx=$filter->getIdx() mode=used}
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
 <div class="field">
  <label>TCP flags</label>
  <table class="noborder">
   <tr>
    <td onclick="obj_toggle_checkbox('[name=filter_tcpflag_syn]')"><input type="checkbox" name="filter_tcpflag_syn" value="Y" {if $filter->isTcpFlagEnabled('SYN')} checked="checked" {/if} />SYN</td>
    <td onclick="obj_toggle_checkbox('[name=filter_tcpflag_ack]')"><input type="checkbox" name="filter_tcpflag_ack" value="Y" {if $filter->isTcpFlagEnabled('ACK')} checked="checked" {/if} />ACK</td>
    <td onclick="obj_toggle_checkbox('[name=filter_tcpflag_fin]')"><input type="checkbox" name="filter_tcpflag_fin" value="Y" {if $filter->isTcpFlagEnabled('FIN')} checked="checked" {/if} />FIN</td>
   </tr>
   <tr>
    <td onclick="obj_toggle_checkbox('[name=filter_tcpflag_rst]')"><input type="checkbox" name="filter_tcpflag_rst" value="Y" {if $filter->isTcpFlagEnabled('RST')} checked="checked" {/if} />RST</td>
    <td onclick="obj_toggle_checkbox('[name=filter_tcpflag_urg]')"><input type="checkbox" name="filter_tcpflag_urg" value="Y" {if $filter->isTcpFlagEnabled('URG')} checked="checked" {/if} />URG</td>
    <td onclick="obj_toggle_checkbox('[name=filter_tcpflag_psh]')"><input type="checkbox" name="filter_tcpflag_psh" value="Y" {if $filter->isTcpFlagEnabled('PSH')} checked="checked" {/if} />PSH</td>
   </tr>
  </table>
  <div class="extra">Match on specific TCP flags combinations.</div>
 </div>
 <div class="field">
  <label>Packet length</label>
  <input type="text" name="filter_packet_length" size="30" value="{if $filter->hasPacketLength()}{$filter->getPacketLength()}{/if}" />
  <div class="extra">Match a packet against a defined size. Enter a size \"64\" or a range \"64:128\".</div>
 </div>
 <div class="ui divider"></div>
 {form_buttons submit=1 discard=1 reset=1}
</form>
<p class="footnote">
 {include file="link_list.tpl" link_source=$filter}
</p>
