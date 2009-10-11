<pre id="target"></pre>
<form action="{$page->uri}" id="interfaces" method="post">
<input type="hidden" name="module" value="interface" />
<input type="hidden" name="action" value="store" />
{ if !$if_idx }
 {start_table icon=$icon_interfaces alt="interface icon" title="Create a new Interface" }
 <input type="hidden" name="if_new" value="1" />
{ else }
 {start_table icon=$icon_interfaces alt="interface icon" title="Modify interface $if_name" }
 <input type="hidden" name="if_new" value="0" />
 <input type="hidden" name="namebefore" value="{ $if_name }" />
 <input type="hidden" name="if_idx" value="{ $if_idx }" />
{ /if } 
<table style="width: 100%;" class="withborder2">
 <tr>
  <td colspan="3">
   <img src="{ $icon_interfaces }" alt="interface icon" />
   General
  </td>
 </tr>
 <tr>
  <td>
   Name:
  </td>
  <td>
   <input type="text" name="if_name" size="30" value="{ $if_name }" />
  </td>
  <td>
   Specify the interface name (eth0, ppp0, imq0, ...).
  </td>
 </tr>
 <tr>
  <td>
   Status:
  </td>
  <td>
   <input type="radio" name="if_active" value="Y" { if $if_active == "Y" } checked="checked" { /if } />Enabled
   <input type="radio" name="if_active" value="N" { if $if_active != "Y" } checked="checked" { /if } />Disabled
  </td>
  <td>
   Enable or disable shaping on this interface (on next ruleset reload).
  </td>
 </tr>
 <tr>
  <td colspan="3">
   <img src="{ $icon_interfaces }" alt="interface icon" />
   Interface Details:
  </td>
 </tr>
 <tr>
  <td>
   Bandwidth:
  </td>
  <td>
   <input type="text" name="if_speed" size="30" value="{ $if_speed }" />
  </td>
  <td>
   Specify the outbound bandwidth on this interface in bit/s (append K for kbit/s or M for Mbit/s).
  </td>
 </tr>
 <tr>
  <td colspan="3">
   <img src="{ $icon_interfaces }" alt="interface icon" />
   Options:
  </td>
 </tr>
 <tr>
  <td>
   IFB:
  </td>
  <td>
   <input type="radio" name="if_ifb" value="Y" { if $if_ifb == "Y" } checked="checked" { /if } />Enabled
   <input type="radio" name="if_ifb" value="N" { if $if_ifb != "Y" } checked="checked" { /if } />Disabled
  </td>
  <td>
   This option enables IFB support on this interface. Make sure that IFB is compiled into your kernel or the proper kernel module is loaded!
  </td>
 </tr>
 <tr>
  <td colspan="3">
   &nbsp;
  </td>
 </tr>
 <tr>
  <td style="text-align: center;"><a href="javascript:refreshContent('interfaces');" title="Back"><img src="{ $icon_arrow_left }" alt="arrow left icon" /></a></td>
  <td><input type="submit" value="Save" /></td>
  <td>Save your settings.</td>
 </tr>
</table>
{ page_end focus_to='if_name' }
