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
 <a class="section" href="{get_url page="users"}"><img src="{$icon_users}" alt="users icon" />&nbsp;Users</a>
 <div class="divider"> / </div>
 <div class="active section">Edit {if $user->hasName()}{$user->getName()}{/if}</div>
</h1>
<div class="ui divider"></div>
<form class="thallium ui form" method="POST" data-id="{$user->getIdx()}" data-guid="{$user->getGuid()}" data-model="user" data-url-next="{get_url page='users'}" data-url-discard="{get_url page='users'}">
 <h4 class="ui block header">General Setttings</h4>
 <div class="field">
  <label>Name</label>
  <div class="ui input">
   <input type="text" placeholder="enter a user name" name="user_name" value="{if $user->hasName()}{$user->getName()}{/if}" />
  </div>
  <div class="extra">
  </div>
 </div>
 <div class="field">
  <label>Active</label>
  <div class="ui radio checkbox">
   <input type="radio" name="user_active" value="Y" {if $user->isActive()} checked="checked" {/if} />
   <label>yes</label>
  </div>
  <div class="ui radio checkbox">
   <input type="radio" name="user_active" value="N" {if !$user->isActive()} checked="checked" {/if} />
   <label>no</label>
  </div>
 </div>
 <h4 class="ui block header">Password</h4>
 <div class="field">
  <label>Password</label>
  <input type="password" name="user_password" {if $user->hasPassword()} value="{$user->getGarbledPassword()}"{/if} />
  <div class="extra">Enter password of the user.</div>
 </div>
 <div class="field">
  <label>again</label>
  <input type="password" name="repeat_password" {if $user->hasPassword()} value="{$user->getGarbledPassword()}"{/if} />
 </div>
 <h4 class="ui block header">Permissions</h4>
 <div class="ui checkbox">
  <label>Manage Chains</label>
  <input type="checkbox" value="Y" name="user_manage_chains" {if $user->doesManage('manage_chains')} checked="checked" {/if} />
 </div>
 <div class="ui checkbox">
  <label>Manage Pipes</label>
  <input type="checkbox" value="Y" name="user_manage_pipes" {if $user->doesManage('manage_pipes')} checked="checked" {/if} />
 </div>
 <div class="ui checkbox">
  <label>Manage Filters</label>
  <input type="checkbox" value="Y" name="user_manage_filters" {if $user->doesManage('manage_filters')} checked="checked" {/if} />
 </div>
 <div class="ui checkbox">
  <label>Manage Ports</label>
  <input type="checkbox" value="Y" name="user_manage_ports" {if $user->doesManage('manage_ports')} checked="checked" {/if} />
 </div>
 <div class="ui checkbox">
  <label>Manage Protocols</label>
  <input type="checkbox" value="Y" name="user_manage_protocols" {if $user->doesManage('manage_protocols')} checked="checked" {/if} />
 </div>
 <div class="ui checkbox">
  <label>Manage Targets</label>
  <input type="checkbox" value="Y" name="user_manage_targets" {if $user->doesManage('manage_targets')} checked="checked" {/if} />
 </div>
 <div class="ui checkbox">
  <label>Manage Users</label>
  <input type="checkbox" value="Y" name="user_manage_users" {if $user->doesManage('manage_users')} checked="checked" {/if} />
 </div>
 <div class="ui checkbox">
  <label>Manage Options</label>
  <input type="checkbox" value="Y" name="user_manage_options" {if $user->doesManage('manage_options')} checked="checked" {/if} />
 </div>
 <div class="ui checkbox">
  <label>Manage Service Levels</label>
  <input type="checkbox" value="Y" name="user_manage_servicelevels" {if $user->doesManage('manage_servicelevels')} checked="checked" {/if} />
 </div>
 <div class="ui checkbox">
  <label>Load &amp; Unload Ruleset</label>
  <input type="checkbox" value="Y" name="user_load_rules" {if $user->doesManage('load_rules')} checked="checked" {/if} />
 </div>
 <div class="ui checkbox">
  <label>Show Ruleset &amp; Overview</label>
  <input type="checkbox" value="Y" name="user_show_rules" {if $user->doesManage('show_rules')} checked="checked" {/if} />
 </div>
 <div class="ui checkbox">
  <label>Show Monitor</label>
  <input type="checkbox" value="Y" name="user_show_monitor" {if $user->doesManage('show_monitor')} checked="checked" {/if} />
 </div>
 <div class="ui divider"></div>
 {form_buttons submit=1 discard=1 reset=1}
</form>
