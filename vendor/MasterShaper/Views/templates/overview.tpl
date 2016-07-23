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
<form id="overview" method="post">
<input type="hidden" name="module" value="overview" />
<input type="hidden" name="action" value="store" />
{if !isset($edit_mode) || empty($edit_mode)}
 {start_table icon=$icon_home alt="home icon" title="MasterShaper Ruleset Overview (view)"}
{else}
 {start_table icon=$icon_home alt="home icon" title="MasterShaper Ruleset Overview (edit)"}
{/if}
<div>
{if isset($nps) && !empty($nps)}
{foreach from=$nps->getItems() item=netpath}
<table style="width: 100%;" type="netpath" id="netpath{$netpath->getIdx()}">
 <tr>
  <td style="height: 15px;" />
 </tr>
 <tr>
  <td style="width: 99%;">
   &nbsp;<a href="javascript:#" title="Collapse all chains within network path" onclick="toggle_content('tr[np={$netpath->getIdx()}]', '#togglenp{$netpath->getIdx()}', '{$icon_menu_down}', '{$icon_menu_right}', 'img[np={$netpath->getIdx()}]'); return false;"><img src="{$icon_menu_right}" id="togglenp{$netpath->getIdx()}" state="hidden" /></a>
   <img src="{$icon_interfaces}" alt="network path icon" />&nbsp;<a href="{get_url page='network-paths' mode='edit' id=$netpath->getSafeLink()}" title="Modify network path {$netpath->getName()}">Network Path {$netpath->getName()}</a>
   <a class="move-down" type="netpath" idx="{$netpath->getIdx()}"><img src="{$icon_pipes_arrow_down}" alt="Move netpath down" /></a>
   <a class="move-up" type="netpath" idx="{$netpath->getIdx()}"><img src="{$icon_pipes_arrow_up}" alt="Move netpath up" /></a>
  </td>
  <td style="width: 1%;">
{if !isset($edit_mode) || empty($edit_mode)}
{* <a href="{$page->self}?mode=edit" title="Switch to Edit-Mode">Edit-Mode</a>&nbsp;*}
{else}
{* <a href="{$page->self}?mode=view" title="Switch to View-Mode">View-Mode</a>&nbsp;*}
{/if}
  </td>
 </tr>
 <tr>
  <td style="height: 5px;" />
 </tr>
 <tr>
  <td colspan="2">
   <table style="width: 100%;" class="withborder">
    <thead>
    <tr>
     <td class="colhead" colspan="2" style="width: 18%;">
      &nbsp;Name
     </td>
     <td class="colhead" style="width: 18%; {if isset($edit_mode) && !empty($edit_mode)} text-align: center;{/if}">
      Service Level
     </td>
     <td class="colhead" style="width: 18%; {if isset($edit_mode) && !empty($edit_mode)} text-align: center;{/if}">
      Fallback
     </td>
     <td class="colhead" style="width: 18%; {if isset($edit_mode) && !empty($edit_mode)} text-align: center;{/if}">
      Source
     </td>
     <td class="colhead" style="text-align: center; width: 5%;">
      Direction
     </td>
     <td class="colhead" style="width: 18%; {if isset($edit_mode) && !empty($edit_mode)} text-align: center;{/if}">
      Destination
     </td>
     <!--<td class="colhead" style="text-align: center;">Action</td>-->
     <td class="colhead" style="text-align: center; width: 5%;">
      Position
     </td>
    </tr>
    </thead>

    <tbody>
 {foreach from=$netpath->getActiveChains() item=chain}

    <tr onmouseover="setBackGrdColor(this, 'mouseover');" onmouseout="setBackGrdColor(this, 'mouseout');" id="chain{$chain->getIdx()}" type="chain">
     <td colspan="2">
      <a href="javascript:#" title="Collapse chain" onclick="toggle_content('#chain{$chain->getIdx()} ~ [chain={$chain->getIdx()}]', '#togglechn{$chain->getIdx()}', '{$icon_menu_down}', '{$icon_menu_right}'); return false;"><img src="{$icon_menu_right}" id="togglechn{$chain->getIdx()}" np="{$netpath->getIdx()}" state="hidden" /></a>
      <img src="{$icon_chains}" alt="chain icon" />&nbsp;
      <a href="{get_url page='chains' mode='edit' id=$chain->getIdx()}" title="Modify chain {$chain->getName()}">{$chain->getName()}</a>
     </td>
     <td {if isset($edit_mode) && !empty($edit_mode)} style="text-align: center;" {/if}>
      {if isset($edit_mode) && !empty($edit_mode)}
      <select name="chain_sl_idx[{$chain->getIdx()}]">
       <option value="0">--- Ignore QoS ---</option>
       {service_level_select_list details=no sl_idx=$chain->getServiceLevel()}
      </select>
      {else}
       <img src="{$icon_servicelevels}" alt="service level icon" />&nbsp;<a href="{get_url page='service-levels' mode='edit' id=$chain->getServiceLevel()}" title="Modify servicel level {$chain->getServiceLevelName()}">{$chain->getServiceLevelName()}</a>
      {/if}
     </td>

    {if $chain->hasFallbackServiceLevel()}
     <td {if isset($edit_mode) && !empty($edit_mode)} style="text-align: center;" {/if}>
      {if isset($edit_mode) && !empty($edit_mode)}
      <select name="chain_fallback_idx[{$chain->getIdx()}]">
       <option value="0">--- No Fallback ---</option>
       {service_level_select_list details=no sl_idx=$chain->getFallbackServiceLevel()}
      </select>
      {else}
       <img src="{$icon_servicelevels}" alt="service level icon" />&nbsp;<a href="{get_url page='service-levels' mode='edit' id=$chain->getFallbackServiceLevel()}" title="Modify servicel level {$chain->getFallbackServiceLevelName()}">{$chain->getFallbackServiceLevelName()}</a>
      {/if}
     </td>
    {else}
     <td>&nbsp;</td>
    {/if}

     <td {if isset($edit_mode) && !empty($edit_mode)} style="text-align: center;" {/if}>
      {if isset($edit_mode) && !empty($edit_mode)}
      <select name="chain_src_target[{$chain->getIdx()}]">
       <option value="0">any</option>
       {target_select_list target_idx=$chain->getSourceTarget()}
      </select>
      {else}
       <img src="{$icon_targets}" alt="target icon" />&nbsp;<a href="{get_url page='Target Edit' id=$chain->getSourceTarget()}" title="Modify target {$chain->getSourceTargetName()}">{$chain->getSourceTargetName()}</a>

      {/if}
     </td>
     <td style="text-align: center;">
      {if isset($edit_mode) && !empty($edit_mode)}
      <select name="chain_direction[{$chain->getIdx()}]">
       <option value="1" {if $chain->getDirection() == 1} selected="selected" {/if}>--&gt;</option>
       <option value="2" {if $chain->getDirection() == 2} selected="selected" {/if}>&lt;-&gt;</option>
      </select>
      {else}
       {$chain->getDirection(true)}
      {/if}
     </td>
     <td {if isset($edit_mode) && !empty($edit_mode)} style="text-align: center;" {/if}>
      {if isset($edit_mode) && !empty($edit_mode)}
      <select name="chain_dst_target[{$chain->getIdx()}]">
       <option value="0">any</option>
       {target_select_list target_idx=$chain->getDestinationTarget()}
      </select>
      {else}
       <img src="{$icon_targets}" alt="target icon" />&nbsp;<a href="{get_url page='Target Edit' id=$chain->getDestinationTarget()}" title="Modify target {$chain->getDestinationTargetName()}">{$chain->getDestinationTargetName()}</a>
      {/if}
     </td>
     {* <!-- hide actions for now, not in use -->
     <td style="text-align: center;">
      <select name="chain_action[{$chain->getIdx()}]">
       <option value="accept" {if $chain->getAction() == "accept"} selected="selected" {/if}>Accept</option>
       <option value="drop" {if $chain->getAction() == "drop"} selected="selected" {/if}>Drop</option>
       <option value="reject" {if $chain->getAction() == "reject"} selected="selected" {/if}>Reject</option>
      </select>
     </td> *}
     <td style="text-align: center;">
      <a class="move-down" type="chain" idx="{$chain->getIdx()}"><img src="{$icon_chains_arrow_down}" alt="Move chain down" /></a>
      <a class="move-up" type="chain" idx="{$chain->getIdx()}"><img src="{$icon_chains_arrow_up}" alt="Move chain up" /></a>
     </td>
    </tr>

  <!-- pipes are only available if the chain DOES NOT ignore
       QoS or DOES NOT use fallback service level
  -->
  {if $chain->hasServiceLevel() && $chain->getServiceLevel() != 0 && $chain->hasFallbackServiceLevel() && $chain->getFallbackServiceLevel() != 0}
   {$counter = 0}
   {foreach $chain->getActivePipes() item=pipe}
    {counter start=1 print=false assign=counter}
    <input type="hidden" name="pipes[{$counter}]" value="{$pipe->getIdx()}" />
    <tr onmouseover="setBackGrdColor(this, 'mouseover');" onmouseout="setBackGrdColor(this, 'mouseout');" id="pipe{$pipe->apc_idx}" chain="{$chain->getIdx()}" np="{$netpath->getIdx()}" type="pipe" style="display: none;">
     <td style="text-align: center;">{$counter}</td>
     <td>
      <img src="{$icon_pipes}" alt="pipes icon" />&nbsp;
      <a href="{get_url page='Pipe Edit' id=$pipe->getIdx()}" title="Modify pipe {$pipe->getName()}">{$pipe->getName()}</a>
     </td>
     <td {if isset($edit_mode) && !empty($edit_mode)} style="text-align: center;" {/if}>
      {if isset($edit_mode) && !empty($edit_mode)}
      <select name="pipe_sl_idx[{$apc_idx}]">
       <option value="0">*** {$pipe_sl_name} ***</option>
       {service_level_select_list details=no sl_idx=$pipe->getServiceLevel()}
      </select>
      {else}
       <img src="{$icon_pipes}" alt="pipe icon" />&nbsp;<a href="{get_url page='Service Level Edit' id=$pipe->getServiceLevel()}" title="Modify service level {$pipe->getServiceLevel(true)->getName()}">{$pipe->getServiceLevel(true)->getName()}</a>
      {/if}
     </td>
     <td>&nbsp;</td>
     <td {if isset($edit_mode) && !empty($edit_mode)} style="text-align: center;" {/if}>
      {if isset($edit_mode) && !empty($edit_mode)}
      <select name="pipe_src_target[{$pipe->getIdx()}]">
       <option value="0">any</option>
       {target_select_list target_idx=$pipe->getSourceTarget()}
      </select>
      {else}
       <img src="{$icon_targets}" alt ="target icon" />&nbsp;<a href="{get_url page='Target Edit' id=$pipe->getSourceTarget()}" title="Modify target {$pipe->getSourceTargetName()}">{$pipe->getSourceTargetName()}</a>
      {/if}
     </td>
     <td style="text-align: center;">
      {if isset($edit_mode) && !empty($edit_mode)}
      <select name="pipe_direction[{$pipe->getIdx()}]">
       <option value="1" {if $pipe->getDirection() == 1} selected="selected" {/if}>--&gt;</option>
       <option value="2" {if $pipe->getDirection() == 2} selected="selected" {/if}>&lt;-&gt;</option>
      </select>
      {else}
       {$pipe->getDirection(true)}
      {/if}
     </td>
     <td {if isset($edit_mode) && !empty($edit_mode)} style="text-align: center;" {/if}>
      {if isset($edit_mode) && !empty($edit_mode)}
      <select name="pipe_dst_target[{$pipe->getIdx()}]">
       <option value="0">any</option>
       {target_select_list target_idx=$pipe->getDestinationTarget()}
      </select>
      {else}
       <img src="{$icon_targets}" alt ="target icon" />&nbsp;<a href="{get_url page='Target Edit' id=$pipe->getDestinationTarget()}" title="Modify target {$pipe->getDestinationTargetName()}">{$pipe->getDestinationTargetName()}</a>
      {/if}
     </td>
     {*
      <td style="text-align: center;">
      <select name="pipe_action[{$pipe->getIdx()}]">
       <option value="accept" {if $pipe->getAction() == "accept"} selected="selected" {/if}>Accept</option>
       <option value="drop" {if $pipe->getAction() == "drop"} selected="selected" {/if}>Drop</option>
       <option value="reject" {if $pipe->getAction() == "reject"} selected="selected" {/if}>Reject</option>
      </select>
     </td> *}
     <td style="text-align: center;">
      <a class="move-down" type="pipe" idx="{$pipe->apc_idx}"><img src="{$icon_pipes_arrow_down}" alt="Move pipe down" /></a>
      <a class="move-up" type="pipe" idx="{$pipe->apc_idx}"><img src="{$icon_pipes_arrow_up}" alt="Move pipe up" /></a>
     </td>
    </tr>
    {foreach from=$pipe->getActiveFilters() item=filter}
    <tr onmouseover="setBackGrdColor(this, 'mouseover');" onmouseout="setBackGrdColor(this, 'mouseout');" chain="{$chain->getIdx()}" np="{$netpath->getIdx()}" pipe="{$pipe->apc_idx}" type="filter" style="display: none;">
     <td>&nbsp;</td>
     <td colspan="7">
      <img src="{$icon_treeend}" alt="tree" />
      <img src="{$icon_filters}" alt="filter icon" />&nbsp;
      <a href="{get_url page='Filter Edit' id=$filter->getIdx()}" title="Modify filter {$filter->getName()}">{$filter->getName()}</a>
     </td>
     <td>&nbsp;</td>
    </tr>
    {/foreach}
   {/foreach}
  {/if}
 {/foreach}
   </tbody>
   </table>
  </td>
 </tr>
</table>
{if isset($edit_mode) && !empty($edit_mode)}
<table>
 <tr>
  <td>
   {include file="savebutton.tpl"}
  </td>
 </tr>
</table>
{/if}
{/foreach}
{else}
 {include file="welcome.tpl"}
{/if}
</div>
</form>
