<pre id="target"></pre>
<form action="{$page->uri}" id="ports" method="post">
<input type="hidden" name="module" value="port" />
<input type="hidden" name="action" value="store" />
{ if ! $port_idx }
 {start_table icon=$icon_ports alt="port icon" title="Create a new Port" }
 <input type="hidden" name="port_new" value="1" />
{ else }
 {start_table icon=$icon_ports alt="port icon" title="Modify Port $port_name" }
 <input type="hidden" name="port_new" value="0" />
 <input type="hidden" name="namebefore" value="{ $port_name }" />
 <input type="hidden" name="port_idx" value="{ $port_idx }" />
{ /if }
<table style="width: 100%" class="withborder">
 <tr>
  <td colspan="3">
   <img src="{ $icon_ports}" alt="port icon" />&nbsp;General
  </td>
 </tr>
 <tr>
  <td>Name:</td>
  <td><input type="text" name="port_name" size="30" value="{ $port_name }" /></td>
  <td>Name of the Port</td>
 </tr>
 <tr>
  <td>Description:</td>
  <td><input type="text" name="port_desc" size="30" value="{ $port_desc }" /></td>
  <td>Short description of the port.</td>
 </tr>
 <tr>
  <td colspan="3">
   <img src="{ $icon_ports }" alt="port icon" />&nbsp;Details
  </td>
 </tr>
 <tr>
  <td>Number:</td>
  <td>
   <input type="text" name="port_number" size="30" value="{ $port_number }" />
  </td>
  <td>Add multiple port splitted with ',' or lists like 22-25</td>
 </tr>
 <tr> 
  <td colspan="3">&nbsp;</td>
 </tr>
 <tr>
  <td style="text-align: center;"><a href="javascript:refreshContent('ports');" title="Back"><img src="{ $icon_arrow_left }" alt="arrow left icon" /></a></td>
  <td><input type="submit" value="Save" /></td>
  <td><?php _("Save settings."); ?></td>
 </tr>
</table>
</form>
{ page_end focus_to='port_name' }
