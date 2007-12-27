<pre id="target"></pre>
<form action="rpc.php?action=store" id="pipes" onsubmit="selectAll('used[]'); saveForm(this, 'pipes'); return false;" method="post">
<input type="hidden" name="module" value="pipe" />
<input type="hidden" name="action" value="modify" />
{ if !$pipe_idx }
 {start_table icon=$icon_pipes alt="pipe icon" title="Create a new Pipe" }
 <input type="hidden" name="pipe_new" value="1" />
{ else }
 {start_table icon=$icon_pipes alt="pipe icon" title="Modify pipe $pipe_name" }
 <input type="hidden" name="pipe_new" value="0" />
 <input type="hidden" name="namebefore" value="{ $pipe_name }" />
 <input type="hidden" name="pipe_idx" value="{ $pipe_idx }" />
{ /if }
<table style="width: 100%;" class="withborder2">
 <tr>
  <td colspan="3">
   <img src="{ $icon_pipes }" alt="pipe icon" />&nbsp;General
  </td>
 </tr>
 <tr>
  <td>Name:</td>
  <td><input type="text" name="pipe_name" size="30" value="{ $pipe_name }" /></td>
  <td>Specify a name for the pipe.</td>
 </tr>
 <tr>
  <td>Status:</td>
  <td>
   <input type="radio" name="pipe_active" value="Y" { if $pipe_active == "Y" } checked="checked" { /if } />Active
   <input type="radio" name="pipe_active" value="N" { if $pipe_active != "Y" } checked="checked" { /if } />Inactive
  </td>
  <td>With this option the status of this chain is specified. Disabled pipes are ignored when reloading the ruleset.</td>
 </tr>
 <tr>
  <td colspan="3">
   <img src="{ $icon_pipes }" alt="pipe icon" />&nbsp;Parameters
  </td>
 </tr>
 <tr>
  <td>Chain:</td>
  <td>
   <select name="pipe_chain_idx">
    { chain_select_list chain_idx=$pipe_chain_idx }
   </select>
  </td>
  <td>Select a chain which the pipe will be assigned to. Only chains which use fallback service levels are able to contain pipes.</td>
 </tr>
 <tr>
  <td style="white-space: nowrap;">
   Target:
  </td>
  <td>
   <table class="noborder">
    <tr>
     <td>Source</td>
     <td>&nbsp;</td>
     <td style="text-align: right;">Destination</td>
    </tr>
    <tr>
     <td>
      <select name="pipe_src_target">
       <option value="0">any</option>
       { target_select_list target_idx=$pipe_src_target }
      </select>
     </td>
     <td>
      <select name="pipe_direction">
       <option value="1" { if $pipe_direction == 1 } selected="selected" { /if }>--&gt;</option>
       <option value="2" { if $pipe_direction == 2 } selected="selected" { /if }>&lt;-&gt;</option>
      </select>
     </td>
     <td>
      <select name="pipe_dst_target">
       <option value="0">any</option>
       { target_select_list target_idx=$pipe_dst_target }
      </select>
     </td>
    </tr>
   </table>
  </td>
  <td>
   Match a source and destination targets.
  </td>
 </tr>
 <tr>
  <td>Filters:</td>
  <td>
   <table class="noborder">
    <tr>
     <td>
      <select size="10" name="avail[]" multiple="multiple">
	    <option value="">********* Unused *********</option>
       { unused_filters_select_list pipe_idx=$pipe_idx }
      </select>
     </td>
     <td>&nbsp;</td>
     <td>
      <input type="button" value="&gt;&gt;" onclick="moveOptions(document.forms['pipes'].elements['avail[]'], document.forms['pipes'].elements['used[]']);" /><br />
      <input type="button" value="&lt;&lt;" onclick="moveOptions(document.forms['pipes'].elements['used[]'], document.forms['pipes'].elements['avail[]']);" />
     </td>
     <td>&nbsp;</td>
     <td>
      <select size="10" name="used[]" multiple="multiple">
       <option value="">********* Used *********</option>
       { used_filters_select_list pipe_idx=$pipe_idx }
      </select>
     </td>
    </tr>
   </table>
  </td>
  <td>Select the filters this pipe will shape.</td>
 </tr>
 <tr>
  <td colspan="3">
   <img src="{ $icon_pipes }" alt="pipe icon" />&nbsp;Bandwidth
  </td>
 </tr>
 <tr>
  <td>Service-Level:</td>
  <td>
   <select name="pipe_sl_idx">
   { service_level_select_list sl_idx=$pipe_sl_idx }
   </select>
  </td>
  <td>Bandwidth limit for this pipe.</td>
 </tr>
 <tr>
  <td colspan="3">&nbsp;</td>
 </tr>
 <tr>
  <td style="text-align: center;"><a href="javascript:refreshContent('pipes');" title="Back"><img src="{ $icon_arrow_left }" alt="arrow left icon" /></a></td>
  <td><input type="submit" value="Save" /></td>
  <td>Save settings.</td>
 </tr>
</table>
</form>
{ page_end focus_to='pipe_name' }
