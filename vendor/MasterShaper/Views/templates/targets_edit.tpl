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
 <a class="section" href="{get_url page="targets"}"><img src="{$icon_targets}" alt="targets icon" />&nbsp;Targets</a>
 <div class="divider"> / </div>
 <div class="active section">Edit {if $target->hasName()}{$target->getName()}{/if}</div>
</h1>
<div class="ui divider"></div>
<form class="thallium ui form" data-id="{$target->getId()}" data-guid="{$target->getGuid()}" data-model="target" data-url-discard="{get_url page='targets'}" data-url-next="{get_url page='targets'}">
 <h4 class="ui block header">General Setttings</h4>
 <div class="field">
  <label>Name</label>
  <div class="ui input">
   <input type="text" placeholder="enter a target name" name="target_name" value="{if $target->hasName()}{$target->getName()}{/if}" />
  </div>
  <div class="extra">
  </div>
 </div>
 <div class="field">
  <label>Active</label>
  <div class="ui radio checkbox">
   <input type="radio" name="target_active" value="Y" {if $target->isActive()} checked="checked" {/if} />
   <label>yes</label>
  </div>
  <div class="ui radio checkbox">
   <input type="radio" name="target_active" value="N" {if !$target->isActive()} checked="checked" {/if} />
   <label>no</label>
  </div>
 </div>
 <h4 class="ui block header">Match Parameters</h4>
 <div class="ui fluid accordion">
  <div class="title {if $target->hasMatch() && $target->getMatch() == "IP"}active{/if}">
   <i class="dropdown icon"></i>
   <div class="ui radio checkbox">
    <input type="radio" name="target_match" value="IP" {if $target->hasMatch() && $target->getMatch() == "IP"} checked="checked" {/if} />
    <label>IP address</label>
   </div>
  </div>
  <div class="content {if $target->hasMatch() && $target->getMatch() == "IP"}active{/if}">
   <div class="extra">Enter an IP address in the following forms: 1.1.1.1, 1.1.1.3-1.1.1.254, 1.1.1.0/24, 1.1.1.1/255.255.248.0</div>
   <div class="description field">
    <div class="ui input">
     <input type="text" placeholder="x.x.x.x" name="target_ip" value="{if $target->hasIP()}{$target->getIP()}{/if}" />
    </div>
   </div>
  </div>
  <div class="title {if $target->hasMatch() && $target->getMatch() == "MAC"}active{/if}">
   <i class="dropdown icon"></i>
   <div class="ui radio checkbox">
    <input type="radio" name="target_match" value="MAC" {if $target->hasMatch() && $target->getMatch() == "MAC"} checked="checked" {/if} />
    <label>MAC address</label>
   </div>
  </div>
  <div class="content {if $target->hasMatch() && $target->getMatch() == "MAC"}active{/if}">
   <div class="extra">Enter a MAC address in the following forms: 00:00:00:00:00:00 or 00-00-00-00-00-00</div>
   <div class="field">
    <label>MAC</label>
    <div class="ui input">
     <input type="text" placeholder="xx:xx:xx:xx:xx:xx" name="target_mac" value="{if $target->hasMAC()}{$target->getMAC()}{/if}" />
    </div>
   </div>
  </div>
  <div class="title {if $target->hasMatch() && $target->getMatch() == "GROUP"}active{/if}">
   <i class="dropdown icon"></i>
   <div class="ui radio checkbox">
    <input type="radio" name="target_match" value="GROUP" {if $target->hasMatch() && $target->getMatch() == "GROUP"} checked="checked" {/if} />
    <label>Group of targets:</label>
   </div>
  </div>
  <div class="content {if $target->hasMatch() && $target->getMatch() == "GROUP"}active{/if}">
   <div class="extra">Group targets together into a group.</div>
   <div class="ui three column grid">
    <div class="column">
     <select id="targets_avail" name="avail[]" multiple="multiple">
      {target_group_select_list group=avail}
       <option value="{$item->getId()}" data-id="{$item->getId()}" data-guid="{$item->getGuid()}">{$item->getName()}</option>
      {/target_group_select_list}
     </select>
    </div>
    <div class="three wide center aligned column">
     <input type="button" value="&lt;&lt;" onclick="moveOptions('targets_used', 'targets_avail');" />
     <input type="button" value="&gt;&gt;" onclick="moveOptions('targets_avail', 'targets_used');" />
    </div>
    <div class="column">
     <select id="targets_used" name="target_members" multiple="multiple">
      {target_group_select_list group=used}
       <option value="{$item->getId()}" data-id="{$item->getId()}" data-guid="{$item->getGuid()}">{$item->getName()}</option>
      {/target_group_select_list}
     </select>
    </div>
   </div>
  </div>
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
<p class="footnote">
{if isset($obj_use_target) && !empty($obj_use_target)}
 This target is assigned to the following objects:<br />
 {foreach from=$obj_use_target key=obj_idx item=obj name=objects}
  {if $obj->type == 'group'}
   <a href="{get_url page='targets' mode='edit' id=$obj->getSafeLink()}" title="Edit target {$obj->name}"><img src="{$icon_targets}" alt="target icon" />&nbsp;{$obj->name}</a>{if !isset($smarty.foreach.objects.last) || empty($smarty.foreach.objects.last)},{/if}
  {/if}
  {if $obj->type == 'pipe'}
   <a href="{get_url page='pipes' mode='edit' id=$obj->getSafeLink()}" title="Edit pipe {$obj->name}"><img src="{$icon_pipes}" alt="pipe icon" />&nbsp;{$obj->name}</a>{if !isset($smarty.foreach.objects.last) || empty($smarty.foreach.objects.last)},{/if}
  {/if}
  {if $obj->type == 'chain'}
   <a href="{get_url page='chains' mode='edit' id=$obj->getSafeLink()}" title="Edit chain {$obj->name}"><img src="{$icon_chains}" alt="chain icon" />&nbsp;{$obj->name}</a>{if !isset($smarty.foreach.objects.last) || empty($smarty.foreach.objects.last)},{/if}
  {/if}
 {foreachelse}
  none
 {/foreach}
{/if}
</p>
