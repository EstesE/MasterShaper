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
 <a class="section" href="{get_url page="protocols"}"><img src="{$icon_protocols}" alt="protocols icon" />&nbsp;Protocols</a>
 <div class="divider"> / </div>
 <div class="active section">Edit {if $protocol->hasName()}{$protocol->getName()}{/if}</div>
</h1>
<div class="ui divider"></div>
<form class="thallium ui form" method="POST" data-id="{$protocol->getId()}" data-guid="{$protocol->getGuid()}" data-model="protocol" data-url-next="{get_url page='protocols'}" data-url-discard="{get_url page='protocols'}">
 <h4 class="ui block header">General Setttings</h4>
 <div class="field">
  <label>Name</label>
  <div class="ui input">
   <input type="text" placeholder="enter a protocol name" name="proto_name" value="{if $protocol->hasName()}{$protocol->getName()}{/if}" />
  </div>
  <div class="extra">
  </div>
 </div>
 <div class="field">
  <label>Active</label>
  <div class="ui radio checkbox">
   <input type="radio" name="proto_active" value="Y" {if $protocol->isActive()} checked="checked" {/if} />
   <label>yes</label>
  </div>
  <div class="ui radio checkbox">
   <input type="radio" name="proto_active" value="N" {if !$protocol->isActive()} checked="checked" {/if} />
   <label>no</label>
  </div>
 </div>
 <h4 class="ui block header">Protocol Parameters</h4>
 <div class="field">
  <label>Protocol Number</label>
  <div class="ui input">
   <input type="text" placeholder="enter a integer number" name="proto_number" value="{if $protocol->hasNumber()}{$protocol->getNumber()}{/if}" />
  </div>
 </div>
 <div class="field">
  <label>Description</label>
  <div class="ui input">
   <input type="text" placeholder="enter a describing text" name="proto_desc" value="{if $protocol->hasDescription()}{$protocol->getDescription()}{/if}" />
  </div>
 </div>
 <div class="ui divider"></div>
 {form_buttons submit=1 discard=1 reset=1}
</form>
<p class="footnote">
{if isset($obj_use_protocol) && !empty($obj_use_protocol)}
 This protocol is assigned to the following objects:<br />
 {foreach from=$obj_use_protocol key=obj_idx item=obj name=objects}
  {if $obj->type == 'group'}
   <a href="{get_url page='protocols' mode='edit' id=$obj->getSafeLink()}" title="Edit port {$obj->name}"><img src="{$icon_ports}" alt="port icon" />&nbsp;{$obj->name}</a>{if !isset($smarty.foreach.objects.last) || empty($smarty.foreach.objects.last)},{/if}
  {/if}
  {if $obj->type == 'pipe'}
   <a href="{get_url page='pipes' mode='edit' id=$obj->getSafeLink()}" title="Edit pipe {$obj->name}"><img src="{$icon_pipes}" alt="pipe icon" />&nbsp;{$obj->name}</a>{if !isset($smarty.foreach.objects.last) || empty($smarty.foreach.objects.last)},{/if}
  {/if}
  {if $obj->type == 'chain'}
   <a href="{get_url page='pipes' mode='edit' id=$obj->getSafeLink()}" title="Edit chain {$obj->name}"><img src="{$icon_chains}" alt="chain icon" />&nbsp;{$obj->name}</a>{if !isset($smarty.foreach.objects.last) || empty($smarty.foreach.objects.last)},{/if}
  {/if}
 {foreachelse}
  none
 {/foreach}
{/if}
</p>
