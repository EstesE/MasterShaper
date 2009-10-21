<pre id="target"></pre>
<form action="{$page->uri}" id="users" method="post">
<input type="hidden" name="module" value="user" />
<input type="hidden" name="action" value="store" />
{ if ! $user->user_idx }
 {start_table icon=$icon_users alt="user icon" title="Create a new User" }
 <input type="hidden" name="new" value="1" />
{ else }
 {start_table icon=$icon_users alt="user icon" title="Modify User `$user->user_name`" }
 <input type="hidden" name="new" value="0" />
 <input type="hidden" name="user_idx" value="{ $user->user_idx }" />
{ /if }
<table style="width: 100%;" class="withborder">
 <tr>
  <td colspan="3">
   <img src="{ $icon_users }" alt="user icon" />
   General
  </td>
 </tr>
 <tr>
  <td>
   Name:
  </td>
  <td>
   <input type="text" name="user_name" size="30" value="{ $user->user_name }" />
  </td>
  <td>
   Enter the user/login name.
  </td>
 </tr>
 <tr>
  <td>
   Password:
  </td>
  <td>
   <input type="password" name="user_pass1" size="30" value="{ if ! $new } nochangeMS { /if }" />
  </td>
  <td>
   Enter password of the user.
  </td>
 </tr>
 <tr>
  <td>
   again
  </td>
  <td>
   <input type="password" name="user_pass2" size="30" value="{ if ! $new } nochangeMS { /if }" />
  </td>
  <td>
   &nbsp;
  </td>
 </tr>
 <tr>
  <td>
   Status:
  </td>
  <td>
   <input type="radio" name="user_active" value="Y" { if $user->user_active == "Y" } checked="checked" { /if } />Enabled
   <input type="radio" name="user_active" value="N" { if $user->user_active != "Y" } checked="checked" { /if } />Disabled
  </td>
  <td>
   Enable or disable user account.
  </td>
 </tr>
 <tr>
  <td colspan="3">
   <img src="{ $icon_users }" alt="user icon" />
   Global Permissions:
  </td>
 </tr>
 <tr>
  <td>
   Rights:
  </td>
  <td>
   <table class="noborder">
    <tr>
     <td>
 <input type="checkbox" value="Y" name="user_manage_chains" { if $user->user_manage_chains == "Y" } checked="checked" { /if } />&nbsp;Manage Chains<br />
 <input type="checkbox" value="Y" name="user_manage_pipes" { if $user->user_manage_pipes == "Y" } checked="checked" { /if } />&nbsp;Manage Pipes<br />
 <input type="checkbox" value="Y" name="user_manage_filters" { if $user->user_manage_filters == "Y" } checked="checked" { /if } />&nbsp;Manage Filters<br />
 <input type="checkbox" value="Y" name="user_manage_ports" { if $user->user_manage_ports == "Y" } checked="checked" { /if } />&nbsp;Manage Ports<br />
 <input type="checkbox" value="Y" name="user_manage_protocols" { if $user->user_manage_protocols == "Y" } checked="checked" { /if } />&nbsp;Manage Protocols<br />
 <input type="checkbox" value="Y" name="user_manage_targets" { if $user->user_manage_targets == "Y" } checked="checked" { /if } />&nbsp;Manage Targets<br />
 <input type="checkbox" value="Y" name="user_manage_users" { if $user->user_manage_users == "Y" } checked="checked" { /if } />&nbsp;Manage User<br />
 <input type="checkbox" value="Y" name="user_manage_options" { if $user->user_manage_options == "Y" } checked="checked" { /if } />&nbsp;Manage Options<br />
 <input type="checkbox" value="Y" name="user_manage_servicelevels" { if $user->user_manage_servicelevels == "Y" } checked="checked" { /if } />&nbsp;Manage Service Levels<br />
 <input type="checkbox" value="Y" name="user_load_rules" { if $user->user_load_rules == "Y" } checked="checked" { /if } />&nbsp;Load &amp; Unload Ruleset<br />
 <input type="checkbox" value="Y" name="user_show_rules" { if $user->user_show_rules == "Y" } checked="checked" { /if } />&nbsp;Show Ruleset &amp; Overview<br />
 <input type="checkbox" value="Y" name="user_show_monitor" { if $user->user_show_monitor == "Y" } checked="checked" { /if } />&nbsp;Show Monitor<br />
</td>
    </tr>
   </table>
  <td>Permissions of the user.</td>
 </tr>
 <tr>
  <td colspan="3">
   &nbsp;
  </td>
 </tr>
 <tr>
  <td style="text-align: center;"><a href="javascript:refreshContent('users');" title="Back"><img src="{ $icon_arrow_left }" alt="arrow left icon" /></a></td>
  <td><input type="submit" value="Save" /></td>
  <td>Save your settings.</td>
 </tr>
</table>
</form>
{ page_end focus_to='user_name' }
