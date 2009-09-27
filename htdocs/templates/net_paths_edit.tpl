<pre id="target"></pre>
<form action="rpc.php?action=store" id="netpaths" onsubmit="saveForm(this, 'networkpaths'); return false;" method="post">
<input type="hidden" name="module" value="networkpath" />
<input type="hidden" name="action" value="modify" />
{ if !$netpath_idx }
 {start_table icon=$icon_interfaces alt="network path icon" title="Create a new Network Path" }
 <input type="hidden" name="netpath_new" value="1" />
{ else }
 {start_table icon=$icon_interfaces alt="network path icon" title="Modify network path $netpath_name" }
 <input type="hidden" name="netpath_new" value="0" />
 <input type="hidden" name="namebefore" value="{ $netpath_name }" />
 <input type="hidden" name="netpath_idx" value="{ $netpath_idx }" />
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
   <input type="text" name="netpath_name" size="30" value="{ $netpath_name }" />
  </td>
  <td>
   Specify a Network Path alias name (INET-LAN, INET-DMZ, ...).
  </td>
 </tr>
 <tr>
  <td>
   Status:
  </td>
  <td>
   <input type="radio" name="netpath_active" value="Y" { if $netpath_active == "Y" } checked="checked" { /if } />Enabled
   <input type="radio" name="netpath_active" value="N" { if $netpath_active != "Y" } checked="checked" { /if } />Disabled
  </td>
  <td>
   Enable or disable shaping on that Network path (on next ruleset reload).
  </td>
 </tr>
 <tr>
  <td colspan="3">
   <img src="{ $icon_interfaces }" alt="interface icon" />
   Interfaces:
  </td>
 </tr>
 <tr>
  <td>
   Interface 1:
  </td>
  <td>
   <select name="netpath_if1">
   { if_select_list if_idx=$netpath_if1 }
   </select>
   &nbsp;<input type="checkbox" name="netpath_if1_inside_gre" value="Y" { if $netpath_if1_inside_gre == "Y" } checked="checked" { /if } />&nbsp;іnside GRE-tunnel
  </td>
  <td>
   First interface of this network path.
  </td>
 </tr>
 <tr>
  <td>
   Interface 2:
  </td>
  <td>
   <select name="netpath_if2">
   { if_select_list if_idx=$netpath_if2 }
    <option value="-1" { if $netpath_if2 == -1 } selected="selected" { /if }>--- not used ---</option>
   </select>
   &nbsp;<input type="checkbox" name="netpath_if2_inside_gre" value="Y" { if $netpath_if2_inside_gre == "Y" } checked="checked" { /if } />&nbsp;іnside GRE-tunnel
  </td>
  <td>
   Second interface of this network path.
  </td>
 </tr>
 <tr>
  <td colspan="3">
   <img src="{ $icon_interfaces }" />&nbsp;Options
  </td>
 </tr>
 <tr>
  <td style="white-space: nowrap;">IMQ:</td>
  <td>
   <input type="radio" name="netpath_imq" value="Y" { if $netpath_imq == "Y" } checked="checked" { /if } />Yes
   <input type="radio" name="netpath_imq" value="N" { if $netpath_imq != "Y" } checked="checked" { /if } />No
  </td>
  <td>
   Do you use IMQ (Intermediate Queuing Device) devices within this network path?
  </td>
 </tr>
 <tr>
  <td colspan="3">
   &nbsp;
  </td>
 </tr>
 <tr>
  <td style="text-align: center;"><a href="javascript:refreshContent('networkpaths');" title="Back"><img src="{ $icon_arrow_left }" alt="arrow left icon" /></a></td>
  <td><input type="submit" value="Save" /></td>
  <td>Save your settings.</td>
 </tr>
</table>
{ page_end focus_to='netpath_name' }
