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
 <a class="section" href="{get_url page="pipes"}"><img src="{$icon_pipes}" alt="pipes icon" />&nbsp;Pipes</a>
 <div class="divider"> / </div>
 <div class="active section">Edit {if $pipe->hasName()}{$pipe->getName()}{/if}</div>
</h1>
<div class="ui divider"></div>
<form class="thallium ui form" method="POST" data-id="{$pipe->getIdx()}" data-guid="{$pipe->getGuid()}" data-model="pipe" data-url-next="{get_url page='pipes'}" data-url-discard="{get_url page='pipes'}">
 <h4 class="ui block header">General Setttings</h4>
 <div class="field">
  <label>Name</label>
  <div class="ui input">
   <input type="text" placeholder="enter a pipe name" name="pipe_name" value="{if $pipe->hasName()}{$pipe->getName()}{/if}" />
  </div>
  <div class="extra">
  </div>
 </div>
 <div class="field">
  <label>Active</label>
  <div class="ui radio checkbox">
   <input type="radio" name="pipe_active" value="Y" {if $pipe->isActive()} checked="checked" {/if} />
   <label>yes</label>
  </div>
  <div class="ui radio checkbox">
   <input type="radio" name="pipe_active" value="N" {if !$pipe->isActive()} checked="checked" {/if} />
   <label>no</label>
  </div>
 </div>
 <h4 class="ui block header">Parameters</h4>
 <div class="field">
  <label>Target</label>
  <table class="noborder">
   <tr>
    <td>Source
     <img class="change_to" src="{$icon_arrow_right}" value="Go" onclick="change_to('{get_url page='targets' mode='edit' id=0}', $('select[name=pipe_src_target]').val());" />
    </td>
    <td>&nbsp;</td>
    <td style="text-align: right;">Destination
     <img class="change_to" src="{$icon_arrow_right}" value="Go" onclick="change_to('{get_url page='targets' mode='edit' id=0}', $('select[name=pipe_dst_target]').val());" />
    </td>
   </tr>
   <tr>
    <td>
     <select name="pipe_src_target">
      <option value="0">any</option>
      {select_list name="sourcetargets" what="TargetsModel" selected=($pipe->hasSourceTarget()) ? $pipe->getSourceTarget() : null}
      <option value="{$data->getIdx()}" {if $data->getIdx() == $selected}selected="selected"{/if}>{if $data->hasName()}{$data->getName()}{else}{$data->getIdx()}{/if}</option>
      {/select_list}
     </select>
    </td>
    <td>
     <select name="pipe_direction">
      <option value="1" {if $pipe->hasDirection() && $pipe->getDirection() == 1} selected="selected" {/if}>--&gt;</option>
      <option value="2" {if $pipe->hasDirection() && $pipe->getDirection() == 2} selected="selected" {/if}>&lt;-&gt;</option>
     </select>
    </td>
    <td>
     <select name="pipe_dst_target">
      <option value="0">any</option>
      {select_list name="sourcetargets" what="TargetsModel" selected=($pipe->hasDestinationTarget()) ? $pipe->getDestinationTarget() : null}
      <option value="{$data->getIdx()}" {if $data->getIdx() == $selected}selected="selected"{/if}>{if $data->hasName()}{$data->getName()}{else}{$data->getIdx()}{/if}</option>
      {/select_list}
     </select>
    </td>
   </tr>
  </table>
  <div class="extra"> Match a source and destination targets.</div>
 </div>
 <div class="field">
  <label>Filters:</label>
  <table class="noborder">
   <tr>
    <td>
     <select size="10" id="targets_avail" name="avail[]" multiple="multiple">
      <option value="">********* Unused *********</option>
      {unused_filters_select_list pipe_idx=$pipe->getIdx()}
     </select>
    </td>
    <td>&nbsp;</td>
    <td>
     <input type="button" value="&gt;&gt;" onclick="moveOptions('targets_avail', 'targets_used');" /><br />
     <input type="button" value="&lt;&lt;" onclick="moveOptions('targets_used', 'targets_avail');" />
    </td>
    <td>&nbsp;</td>
    <td>
     <select size="10" id="targets_used" name="used[]" multiple="multiple">
      <option value="">********* Used *********</option>
      {used_filters_select_list pipe_idx=$pipe->getIdx()}
     </select>
    </td>
   </tr>
  </table>
  <div class="extra">Select the filters this pipe will shape.<br />Remember that port matches will always be matched on "Destination" side!</div>
 </div>
 <h4 class="ui block header">Bandwidth defaults</h4>
 <div class="field">
  <label>Service-Level:</label>
  <select name="pipe_sl_idx">
  {service_level_select_list sl_idx=($pipe->hasServiceLevel()) ? $pipe->getServiceLevel() : null}
  {select_list name="servicelevel" what="ServiceLevelsModel" selected=($pipe->hasServiceLevel()) ? $pipe->getServiceLevel() : null}
   <option value="{$data->getIdx()}" {if $data->getIdx() == $selected}selected="selected"{/if}>{if $data->hasName()}{$data->getName()}{else}{$data->getIdx()}{/if}</option>
  {/select_list}
  </select>
  <img class="change_to" src="{$icon_arrow_right}" value="Go" onclick="change_to('{get_url page='service-levels' mode='edit' id=0}', $('select[name=pipe_sl_idx]').val());" />
  <div class="extra">Default bandwidth limit for this pipe. It can be overriden per chain as soon as you assigned this pipe to it.</div>
 </div>
 <div class="ui divider"></div>
 {form_buttons submit=1 discard=1 reset=1}
</form>
<p class="footnote">
 {include file="link_list.tpl" link_source=$pipe}
</p>
