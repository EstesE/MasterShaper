<pre id="target"></pre>
<form action="rpc.php?action=store" id="targets" onsubmit="selectAll('used[]'); saveForm(this, 'targets'); return false;" method="post">
<input type="hidden" name="module" value="target" />
<input type="hidden" name="action" value="modify" />
{ if ! $target_idx }
 {start_table icon=$icon_targets alt="target icon" title="Create a new Target" }
 <input type="hidden" name="target_new" value="1">
{ else }
 {start_table icon=$icon_targets alt="target icon" title="Modify Target $target_name" }
 <input type="hidden" name="target_new" value="0" />
 <input type="hidden" name="namebefore" value="{ $target_name }" />
 <input type="hidden" name="target_idx" value="{ $target_idx }" />
{ /if }
<table style="width: 100%;" class="withborder">
 <tr>
  <td colspan="3">&nbsp;</td>
 </tr>
 <tr>
  <td colspan="3">
   <img src="{ $icon_targets}" alt="target icon" />&nbsp;General
  </td>
 </tr>
 <tr>
  <td>Name:</td>
  <td><input type="text" name="target_name" size="30" value="{ $target_name }" /></td>
  <td>Name of the target.</td>
 </tr>
 <tr>
  <td colspan="3">
   <img src="{ $icon_targets }" alt="target icon" />&nbsp;Parameters
  </td>
 </tr>
 <tr>
  <td style="white-space: nowrap;">Match:</td>
  <td>
   <table class="noborder">
    <tr>
     <td style="white-space: nowrap;">
      <input type="radio" name="target_match" value="IP" { if $target_match == "IP" } checked="checked" { /if } />IP
     </td>
	  <td>&nbsp;</td>
     <td>
	   <input type="text" name="target_ip" size="30" value="{ $target_ip }" />
	  </td>
    </tr>
    <tr>
     <td style="white-space: nowrap;">
	   <input type="radio" name="target_match" value="MAC" { if $target_match == "MAC" } checked="checked" { /if } />MAC
     </td>
	  <td>&nbsp;</td>
	  <td>
	   <input type="text" name="target_mac" size="30" value="{ $target_mac }" />
	  </td>
    </tr>
    <tr>
     <td style="white-space: nowrap;">
	   <input type="radio" name="target_match" value="GROUP" { if $target_match == "GROUP" } checked="checked" { /if } />Group
     </td>
	  <td>&nbsp;</td>
	  <td>
	   <table>
	    <tr>
	     <td>
	      <select name="avail[]" size="5" multiple="multiple">
	       <option value="">********* Unused *********</option>
          { target_select_list group=unused idx=$target_idx }
         </select>
	     </td>
	     <td>&nbsp;</td>
	     <td>
         <input type="button" value="&gt;&gt;" onclick="moveOptions(document.forms['targets'].elements['avail[]'], document.forms['targets'].elements['used[]']);" /><br />
         <input type="button" value="&lt;&lt;" onclick="moveOptions(document.forms['targets'].elements['used[]'], document.forms['targets'].elements['avail[]']);" />
        </td>
	     <td>&nbsp;</td>
	     <td>
	      <select name="used[]" size="5" multiple="multiple">
	       <option value="">********* Used *********</option>
          { target_select_list group=used idx=$target_idx }
	      </select>
        </td>
       </tr>
      </table>
     </td>
    </tr>
   </table>
  </td>
  <td>
   Specify the target matchting method.<br /><br />IP: Enter a host (1.1.1.1), host list (1.1.1.1-1.1.1.254) or a network address (1.1.1.0/24).<br /><br />MAC: Specify the MAC address in format 00:00:00:00:00:00 or 00-00-00-00-00-00.<br /><br />Group: Group already defined targets as groups together. Group in group is not supported.<br /><br /><b>Be aware, that MAC match can NOT be used in combination with tc-filter.</b>
  </td>
 </tr>
 <tr>
  <td colspan="3">&nbsp;</td>
 </tr>
 <tr>
  <td style="text-align: center;"><a href="javascript:refreshContent('targets');" title="Back"><img src="{ $icon_arrow_left }" alt="arrow left icon" /></a></td>
  <td><input type="submit" value="Save" /></td>
  <td>Save settings.</td>
 </tr>
</table> 
</form>
{ page_end focus_to='target_name' }
