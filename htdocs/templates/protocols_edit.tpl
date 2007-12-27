<pre id="target"></pre>
<form action="rpc.php?action=store" id="protocols" onsubmit="saveForm(this, 'protocols'); return false;" method="post">
<input type="hidden" name="module" value="protocol" />
<input type="hidden" name="action" value="modify" />
{ if ! $proto_idx }
 {start_table icon=$icon_protocols alt="protocol icon" title="Create a new Protocol" }
 <input type="hidden" name="proto_new" value="1" />
{ else }
 {start_table icon=$icon_protocols alt="protocol icon" title="Modify Protocol $proto_name" }
 <input type="hidden" name="proto_new" value="0" />
 <input type="hidden" name="namebefore" value="{ $proto_name }" />
 <input type="hidden" name="proto_idx" value="{ $proto_idx }" />
{ /if }
<table style="width: 100%" class="withborder">
 <tr>
  <td colspan="3">
   <img src="{ $icon_protocols}" alt="protocol icon" />&nbsp;General
  </td>
 </tr>
 <tr>
  <td>Name:</td>
  <td><input type="text" name="proto_name" size="30" value="{ $proto_name }" /></td>
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
   <input type="text" name="proto_number" size="30" value="{ $proto_number }" />
  </td>
  <td>The IANA protocol number.</td>
 </tr>
 <tr> 
  <td colspan="3">&nbsp;</td>
 </tr>
 <tr>
  <td style="text-align: center;"><a href="javascript:refreshContent('protocols');" title="Back"><img src="{ $icon_arrow_left }" alt="arrow left icon" /></a></td>
  <td><input type="submit" value="Save" /></td>
  <td><?php _("Save settings."); ?></td>
 </tr>
</table>
</form>
{ page_end focus_to='proto_name' }
