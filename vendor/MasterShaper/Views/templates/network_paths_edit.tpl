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
 <a class="section" href="{get_url page="network-paths"}"><img src="{$icon_interfaces}" alt="interfaces icon" />&nbsp;Network Paths</a>
 <div class="divider"> / </div>
 <div class="active section">Edit {if $netpath->hasName()}{$netpath->getName()}{/if}</div>
</h1>
<div class="ui divider"></div>
<form class="thallium ui form" data-id="{$netpath->getId()}" data-guid="{$netpath->getGuid()}" data-model="network_path" method="POST" data-url-next="{get_url page='network-paths'}" data-url-discard="{get_url page='network-paths'}">
 <h4 class="ui block header">General Setttings</h4>
 <div class="field">
  <label>Name</label>
  <div class="ui input">
   <input type="text" placeholder="enter a network path name" name="netpath_name" value="{if $netpath->hasName()}{$netpath->getName()}{/if}" />
  </div>
  <div class="extra">
  </div>
 </div>
 <div class="field">
  <label>Active</label>
  <div class="ui radio checkbox">
   <input type="radio" name="netpath_active" value="Y" {if $netpath->isActive()} checked="checked" {/if} />
   <label>yes</label>
  </div>
  <div class="ui radio checkbox">
   <input type="radio" name="netpath_active" value="N" {if !$netpath->isActive()} checked="checked" {/if} />
   <label>no</label>
  </div>
 </div>

 <h4 class="ui block header">Interfaces</h4>
 <div class="inline fields">
  <div class="field">
   <label>Interface 1</label>
   <select name="netpath_if1">
    {if_select_list if_idx=$netpath->getInterface1()}
   </select>
  </div>
  <div class="field">
   <label>inside GRE-tunnel</label>
   <input type="checkbox" name="netpath_if1_inside_gre" value="Y" {if $netpath->isInterface1InsideGre()} checked="checked"{/if} />
  </div>
  <div class="extra">First interface of this network path.</div>
 </div>
 <div class="inline fields">
  <div class="field">
   <label>Interface 2</label>
   <select name="netpath_if2">
    {if_select_list if_idx=$netpath->getInterface2()}
    <option value="-1" {if $netpath->hasInterface2() && $netpath->getInterface2() == -1} selected="selected"{/if}>--- not used ---</option>
   </select>
  </div>
  <div class="field">
   <label>inside GRE-tunnel</label>
   <input type="checkbox" name="netpath_if2_inside_gre" value="Y" {if $netpath->isInterface2InsideGre()} checked="checked"{/if} />
  </div>
  <div class="extra">Second interface of this network path.</div>
 </div>

 <h4 class="ui block header">Options</h4>
 <div class="field">
  <label>IMQ</label>
  <div class="radio checkbox">
   <label>Yes</label>
   <input type="radio" name="netpath_imq" value="Y" {if $netpath->isImq()} checked="checked" {/if} />
  </div>
  <div class="radio checkbox">
   <label>No</label>
   <input type="radio" name="netpath_imq" value="N" {if !$netpath->isImq()} checked="checked" {/if} />
  </div>
  <div class="extra">Do you use IMQ (Intermediate Queuing Device) devices within this network path?</div>
 </div>

 <div class="field">
  <label>Chains</label>
  <i>(Drag &amp; drop chains to change order.)</i><br />
  <table class="withborder2" id="chainlist">
   <thead>
    <tr>
     <td><img src="{$icon_chains}" alt="chain icon" />&nbsp;<i>Chain</i></td>
     <td><i>Status</i></td>
    </tr>
   </thead>
   <tbody id="chains">
   {chain_list}
    <tr id="chain{$chain->chain_idx}" {if $chain->chain_active != 'Y'} style="opacity: 0.5;" {/if} onmouseover="setBackGrdColor(this, 'mouseover');" onmouseout="setBackGrdColor(this, 'mouseout');">
     <td class="chain_dragger">
      <a href="{get_url page='chains' mode='edit' id=$chain->getSafeLink()}" title="Edit chain {$chain->chain_name}"><img src="{$icon_chains}" alt="chain icon" />&nbsp;{$chain->chain_name}</a>
     </td>
     <td style="text-align: center;">
      <input type="hidden" name="used[]" value="{$chain->chain_idx}" />
      <input type="hidden" id="chain-active-{$chain->chain_idx}" name="chain_active[{$chain->chain_idx}]" value="{$chain->apc_chain_idx}" />
      <div class="toggle" id="toggle-{$chain->chain_idx}" style="display: inline;">
       <a class="toggle-off" id="chain-{$chain->chain_idx}" to="off" title="Disable chain {$chain->chain_name}" {if $chain->chain_active != "Y"} style="display: none;" {/if} onclick="$('#chain-active-{$chain->chain_idx}').val('N'); $('table#chainlist tbody#chains tr#chain{$chain->chain_idx}').fadeTo(500, 0.50);"><img src="{$icon_active}" alt="active icon" /></a>
       <a class="toggle-on" id="chain-{$chain->chain_idx}" to="on" title="Enable chain {$chain->chain_name}" {if $chain->chain_active == "Y"} style="display: none;" {/if} onclick="$('#chain-active-{$chain->chain_idx}').val('Y'); $('table#chainlist tbody#chains tr#chain{$chain->chain_idx}').fadeTo(500, 1);"><img src="{$icon_inactive}" alt="inactive icon" /></a>
      </div>
     </td>
    </tr>
   {/chain_list}
    </tbody>
  </table>
  <div class="extra">Select chains bound to this network path.</div>
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
