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
 <a class="section" href="{get_url page="chains"}"><img src="{$icon_chains}" alt="chains icon" />&nbsp;Chains</a>
 <div class="divider"> / </div>
 <div class="active section">Edit {if $chain->hasName()}{$chain->getName()}{/if}</div>
</h1>
<div class="ui divider"></div>
<form class="thallium ui form" data-id="{$chain->getId()}" data-guid="{$chain->getGuid()}" data-model="chain" data-url-next="{get_url page='chains'}" data-url-discard="{get_url page='chains'}">
 <h4 class="ui block header">General Setttings</h4>
 <div class="field">
  <label>Name</label>
  <div class="ui input">
   <input type="text" placeholder="enter a chain name" name="chain_name" value="{if $chain->hasName()}{$chain->getName()}{/if}" />
  </div>
  <div class="extra">
  </div>
 </div>
 <div class="field">
  <label>Active</label>
  <div class="ui radio checkbox">
   <input type="radio" name="chain_active" value="Y" {if $chain->isActive()} checked="checked" {/if} />
   <label>yes</label>
  </div>
  <div class="ui radio checkbox">
   <input type="radio" name="chain_active" value="N" {if !$chain->isActive()} checked="checked" {/if} />
   <label>no</label>
  </div>
 </div>
 <h4 class="ui block header">Bandwidth</h4>
 <div class="field">
  <label>Service Level:</label>
  <select name="chain_sl_idx">
   {service_level_select_list sl_idx=($chain->hasServiceLevel()) ? $chain->getServiceLevel() : null}
   <option value="0" {if !$chain->hasServiceLevel() || $chain->getServiceLevel() == 0} selected="selected" {/if} >--- Ignore QoS ---</option>
  </select>
  <img class="change_to" src="{$icon_arrow_right}" value="Go" onclick="change_to('{get_url page='service-levels' mode='edit' id=0}', $('select[name=chain_sl_idx]').val());" />
 </div>
 <div class="field">
  <label>Fallback:</label>
  <select name="chain_fallback_idx">
   {service_level_select_list sl_idx=($chain->hasFallbackServiceLevel()) ? $chain->getFallbackServiceLevel() : null}
   <option value="0" {if !$chain->hasFallbackServiceLevel() || $chain->getFallbackServiceLevel() == 0} selected="selected" {/if} >--- No Fallback ---</option>
  </select>
  <img class="change_to" src="{$icon_arrow_right}" value="Go" onclick="change_to('{get_url page='service-levels' mode='edit' id=0}', $('select[name=chain_fallback_idx]').val());" />
 </div>

 <h4 class="ui block header">Targets</h4>
 <div class="field">
  <label>Network Path:</label>
  <select name="chain_netpath_idx">
   {network_path_select_list np_idx=($chain->hasNetworkPath()) ? $chain->getNetworkPath() : null}
  </select>
  <img class="change_to" src="{$icon_arrow_right}" value="Go" onclick="change_to('{get_url page='network-paths' mode='edit' id=0}', $('select[name=chain_netpath_idx]').val());" />
 </div>
 <div class="field">
  <label>Match targets:</label>
  <table class="noborder">
   <tr>
    <td>Source {if isset($chain_netpath_if1)}({$chain_netpath_if1}){/if}
     <img class="change_to" src="{$icon_arrow_right}" value="Go" onclick="change_to('{get_url page='targets' mode='edit' id=0}', $('select[name=chain_src_target]').val());" />
    </td>
    <td>&nbsp;</td>
    <td style="text-align: right;">Destination {if isset($chain_netpath_if2)}({$chain_netpath_if2}){/if}
     <img class="change_to" src="{$icon_arrow_right}" value="Go" onclick="change_to('{get_url page='targets' mode='edit' id=0}', $('select[name=chain_dst_target]').val());" />
    </td>
   </tr>
   <tr>
    <td>
    <select name="chain_src_target">
      <option value="0">any</option>
      {target_select_list target_idx=($chain->hasSourceTarget()) ? $chain->getSourceTarget() : null}
     </select>
    </td>
    <td>
     <select name="chain_direction">
      <option value="1" {if $chain->hasDirection() && $chain->getDirection() == 1} selected="selected" {/if} >--&gt;</option>
      <option value="2" {if $chain->hasDirection() && $chain->getDirection() == 2} selected="selected" {/if} >&lt;-&gt;</option>
     </select>
    </td>
    <td>
     <select name="chain_dst_target">
      <option value="0">any</option>
      {target_select_list target_idx=($chain->hasDestinationTarget()) ? $chain->getDestinationTarget() : null}
     </select>
    </td>
   </tr>
  </table>
 </div>
 <div class="field">
  <label>Pipes</label>
  {if ( isset($chain_sl) && !empty($chain_sl) && $chain_sl->sl_htb_bw_in_rate < $chain_total_bw_in )}
   <b>More inbound bandwidth has been guaranteed ({$chain_total_bw_in}kbps) than available ({$chain_sl->sl_htb_bw_in_rate}kbps)!</b>
   <br />
  {else}
   Guaranteed inbound bandwidth: {if isset($chain_total_bw_in)}{$chain_total_bw_in}kbps{else}unknown{/if}<br />
  {/if}
  {if ( isset($chain_sl) && !empty($chain_sl) && $chain_sl->sl_htb_bw_out_rate < $chain_total_bw_out )}
   <b>More outbound bandwidth has been guaranteed ({$chain_total_bw_out}kbps) than available ({$chain_sl->sl_htb_bw_out_rate}kbps)!</b>
   <br />
  {else}
   Guaranteed outbound bandwidth: {if isset($chain_total_bw_out)}{$chain_total_bw_out}kbps{else}unknown{/if}<br />
  {/if}
  <br />
  <i>(Drag &amp; drop pipes to change order.)</i><br />
  <table class="withborder2" id="pipelist">
   <thead>
   <tr>
    <td><img src="{$icon_pipes}" alt="pipe icon" />&nbsp;<i>Pipe</i></td>
    <td><i>Used</i></td>
    <td><img src="{$icon_servicelevels}" alt="servicelevel icon" />&nbsp;<i>Service Level (override in this chain only)</i></td>
    <td><i>Status</i></td>
   </tr>
   </thead>
    <tbody id="pipes">
   {pipe_list}
    <tr id="pipe{$pipe->pipe_idx}" {if $pipe->apc_pipe_idx == 0} style="opacity: 0.5;" {/if} onmouseover="setBackGrdColor(this, 'mouseover');" onmouseout="setBackGrdColor(this, 'mouseout');">
     <td class="pipes_dragger">
      <a href="{get_url page='pipes' mode='edit' id=$pipe->getSafeLink()}" title="Edit pipe {$pipe->pipe_name}"><img src="{$icon_pipes}" alt="pipe icon" />&nbsp;{$pipe->pipe_name}</a>
     </td>
     <td style="text-align: center;">
      <input type="checkbox" name="used[]" value="{$pipe->pipe_idx}" {if $pipe->apc_pipe_idx != 0} checked="checked" {/if} onclick="if(this.checked == false) $('table#pipelist tbody#pipes tr#pipe{$pipe->pipe_idx}').fadeTo(500, 0.50); else $('table#pipelist tbody#pipes tr#pipe{$pipe->pipe_idx}').fadeTo(500, 1);" />
     </td>
     <td>
     <select name="pipe_sl_idx[{$pipe->pipe_idx}]" id="pipe_sl_idx{$pipe->pipe_idx}">
       {service_level_select_list sl_idx=$pipe->sl_in_use sl_default=$pipe->pipe_sl_idx }
      </select>
      <img class="change_to" src="{$icon_arrow_right}" value="Go" onclick="change_to('get_url page='service-levels' mode='edit' id=0}', $('#pipe_sl_idx{$pipe->pipe_idx}').val());" />
     </td>
     <td style="text-align: center;">
      <input type="hidden" id="pipe-active-{$pipe->pipe_idx}" name="pipe_active[{$pipe->pipe_idx}]" value="{$pipe->apc_pipe_active}" />
      <div class="toggle" id="toggle-{$pipe->pipe_idx}" style="display: inline;">
       <a class="toggle-off" id="pipe-{$pipe->pipe_idx}" parent="chain-{$chain->getId()}" to="off" title="Disable pipe {$pipe->pipe_name}" {if $pipe->apc_pipe_active != "Y"} style="display: none;" {/if} onclick="$('#pipe-active-{$pipe->pipe_idx}').val('N');"><img src="{$icon_active}" alt="active icon" /></a>
       <a class="toggle-on" id="pipe-{$pipe->pipe_idx}" parent="chain-{$chain->getId()}" to="on" title="Enable pipe {$pipe->pipe_name}" {if $pipe->apc_pipe_active == "Y"} style="display: none;" {/if} onclick="$('#pipe-active-{$pipe->pipe_idx}').val('Y');"><img src="{$icon_inactive}" alt="inactive icon" /></a>
      </div>
     </td>
    </tr>
   {/pipe_list}
    </tbody>
  </table>
 </div>
 <div class="ui divider"></div>
 <div class="ui buttons">
  <button class="ui labeled icon positive button save" type="submit">
   <div class="ui inverted dimmer">
    <div class="ui loader"></div>
   </div>
   <i class="save icon"></i>Save
  </button>
  <div class="or"></div>
  <button class="ui button discard">
   <i class="remove icon"></i>Discard
  </button>
 </div>
</form>
<script type="text/javascript">
'use strict';

$(document).ready(function () {
   /*$(function(){
      $("table#pipelist tbody#pipes").sortable({
         accept:      'tbody#pipe',
         greedy:      true,
         cursor:      'crosshair',
         placeholder: 'ui-state-highlight',
         delay:       250
      });
      $("table#pipelist tbody#pipes").disableSelection();
      $('td.pipes_dragger').hover(
         function() {
             $(this).css('cursor','crosshair');
         },
         function() {
             $(this).css('cursor','auto');
         }
      );
   });*/

});
</script>
