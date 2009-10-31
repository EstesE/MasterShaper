<pre id="target"></pre>
<form action="{$page->uri}" id="protocols" method="post">
<input type="hidden" name="module" value="protocol" />
<input type="hidden" name="action" value="store" />
{ if ! $protocol->proto_idx }
 {start_table icon=$icon_protocols alt="protocol icon" title="Create a new Protocol" }
 <input type="hidden" name="new" value="1" />
{ else }
 {start_table icon=$icon_protocols alt="protocol icon" title="Modify Protocol `$protocol->proto_name`" }
 <input type="hidden" name="new" value="0" />
 <input type="hidden" name="proto_idx" value="{ $protocol->proto_idx }" />
{ /if }
<table style="width: 100%" class="withborder">
 <tr>
  <td colspan="3">
   <img src="{ $icon_protocols}" alt="protocol icon" />&nbsp;General
  </td>
 </tr>
 <tr>
  <td>Name:</td>
  <td><input type="text" name="proto_name" size="30" value="{ $protocol->proto_name }" /></td>
  <td>Name of the Protocol</td>
 </tr>
 <tr>
  <td colspan="3">
   <img src="{ $icon_protocols }" alt="protocol icon" />&nbsp;Details
  </td>
 </tr>
 <tr>
  <td>Number:</td>
  <td>
   <input type="text" name="proto_number" size="30" value="{ $protocol->proto_number }" />
  </td>
  <td>The IANA protocol number.</td>
 </tr>
 <tr> 
  <td colspan="3">&nbsp;</td>
 </tr>
 <tr>
  <td style="text-align: center;"><a href="{$rewriter->get_page_url('Protocols List')}" title="Back"><img src="{ $icon_arrow_left }" alt="arrow left icon" /></a></td>
  <td><input type="submit" value="Save" /></td>
  <td><?php _("Save settings."); ?></td>
 </tr>
</table>
</form>
{ page_end focus_to='proto_name' }
