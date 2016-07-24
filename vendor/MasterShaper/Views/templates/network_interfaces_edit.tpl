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
 <a class="section" href="{get_url page="network-interfaces"}"><img src="{$icon_interfaces}" alt="interfaces icon" />&nbsp;Network Interfaces</a>
 <div class="divider"> / </div>
 <div class="active section">Edit {if $if->hasName()}{$if->getName()}{/if}</div>
</h1>
<div class="ui divider"></div>
<form class="thallium ui form" method="POST" data-id="{$if->getIdx()}" data-guid="{$if->getGuid()}" data-model="network_interface" data-url-next="{get_url page='network-interfaces'}" data-url-discard="{get_url page='network-interfaces'}">
 <h4 class="ui block header">General Setttings</h4>
 <div class="field">
  <label>Name</label>
  <div class="ui input">
   <input type="text" placeholder="enter a interface name" name="if_name" value="{if $if->hasName()}{$if->getName()}{/if}" />
  </div>
  <div class="extra">
  </div>
 </div>
 <div class="field">
  <label>Active</label>
  <div class="ui radio checkbox">
   <input type="radio" name="if_active" value="Y" {if $if->isActive()} checked="checked" {/if} />
   <label>yes</label>
  </div>
  <div class="ui radio checkbox">
   <input type="radio" name="if_active" value="N" {if !$if->isActive()} checked="checked" {/if} />
   <label>no</label>
  </div>
 </div>
 <h4 class="ui block header">Details</h4>
 <div class="field">
  <label>Bandwidth</label>
  <input type="text" name="if_speed" value="{if $if->hasSpeed()}{$if->getSpeed()}{/if}" />
  <div class="extra">Specify the outbound bandwidth on this interface in bps (append K for kbps or M for Mbps).</div>
 </div>
 <div class="field">
  <label>Fallback</label>
  <select name="if_fallback_idx">
   <option value="0" {if !$if->hasFallback()} selected="selected" {/if} >--- No Fallback ---</option>
   {select_list name="servicelevels" what="ServiceLevelsModel" selected=($if->hasFallback()) ? $if->getFallback() : null}
   <option value="{$data->getIdx()}" {if $data->getIdx() == $selected}selected="selected"{/if}>{if $data->hasName()}{$data->getName()}{else}{$data->getIdx()}{/if}</option>
   {/select_list}
  </select>
  <img class="change_to" src="{$icon_arrow_right}" value="Go" onclick="change_to('{get_url page='service-levels' mode='edit' id=0}', $('select[name=if_fallback_idx]').val());" />
  <div class="extra">If none of the defined chains matches, you can define here a final fallback service level per interface.</div>
 </div>
 <div class="field">
  <label>IFB</label>
  <div class="ui radio checkbox">
   <label>enabled</label>
   <input type="radio" name="if_ifb" value="Y" {if $if->isIfb()} checked="checked"{/if} />
  </div>
  <div class="ui radio checkbox">
   <label>disabled</label>
   <input type="radio" name="if_ifb" value="N" {if !$if->isIfb()} checked="checked"{/if} />
  </div>
  <div class="extra">This option enables IFB support on this interface. Make sure that IFB is compiled into your kernel or the proper kernel module is loaded!</div>
 </div>
 <div class="ui divider"></div>
 {form_buttons submit=1 discard=1 reset=1}
</form>
<p class="footnote">
 {include file="link_list.tpl" link_source=$if}
</p>
