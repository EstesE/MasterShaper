<pre id="target"></pre>
<form action="{$page->uri}" id="chains" onsubmit="selectAll('used[]');" method="POST">
<input type="hidden" name="module" value="chain" />
<input type="hidden" name="action" value="store" />
{ if !$chain->chain_idx }
 {start_table icon=$icon_chains alt="chain icon" title="Create a new Chain" }
 <input type="hidden" name="new" value="1" />
{ else }
 {start_table icon=$icon_chains alt="chain icon" title="Modify chain `$chain->chain_name`" }
 <input type="hidden" name="new" value="0" />
 <input type="hidden" name="chain_idx" value="{ $chain->chain_idx }" />
{ /if }
<table style="width: 100%;" class="withborder2">
 <tr>
  <td colspan="2">
   <img src="{ $icon_chains }" alt="chain icon" />&nbsp;General
  </td>
 </tr>
 <tr>
  <td style="white-space: nowrap;">Name:</td>
  <td style="white-space: nowrap;"><input type="text" name="chain_name" size="40" value="{ $chain->chain_name }" /></td>
 </tr>
 <tr>
  <td style="white-space: nowrap;">Status:</td>
  <td style="white-space: nowrap;">
   <input type="radio" name="chain_active" value="Y" { if $chain->chain_active == "Y" } checked="checked" { /if } />Active
   <input type="radio" name="chain_active" value="N" { if $chain->chain_active != "Y" } checked="checked" { /if } />Inactive
  </td>
 </tr>
 <tr>
  <td colspan="2">
   <img src="{ $icon_chains }" alt="chain icon" />&nbsp;Bandwidth
  </td>
 </tr>
 <tr>
  <td style="white-space: nowrap;">Service Level:</td>
  <td style="white-space: nowrap;">
   <select name="chain_sl_idx">
    { service_level_select_list sl_idx=$chain->chain_sl_idx }
    <option value="0" { if $chain->chain_sl_idx == 0 } selected="selected" { /if } >--- Ignore QoS ---</option>
   </select>
  </td>
 </tr>
 <tr>
  <td style="white-space: nowrap;">Fallback:</td>
  <td style="white-space: nowrap;">
   <select name="chain_fallback_idx">
    { service_level_select_list sl_idx=$chain->chain_fallback_idx }
    <option value="0" { if $chain->chain_fallback_idx == 0 } selected="selected" { /if } >--- No Fallback ---</option>
   </select>
  </td>
 </tr>
 <tr>
  <td colspan="2">
   <img src="{ $icon_chains }" alt="chain icon" />&nbsp;Targets
  </td>
 </tr>
 <tr>
  <td>Network Path:</td>
  <td>
   <select name="chain_netpath_idx">
    { network_path_select_list np_idx=$chain->chain_netpath_idx }
   </select>
  </td>
 </tr>
 <tr>
  <td style="white-space: nowrap;">Match targets:</td>
  <td style="white-space: nowrap;">
   <table class="noborder">
    <tr>
     <td>Target</td>
     <td>&nbsp;</td>
     <td style="text-align: right;">Target</td>
    </tr>
    <tr>
     <td>
      <select name="chain_src_target">
       <option value="0">any</option>
       { target_select_list target_idx=$chain->chain_src_target }
      </select>
     </td>
     <td>
      <select name="chain_direction">
       <option value="1" { if $chain->chain_direction == 1 } selected="selected" { /if } >--&gt;</option>
       <option value="2" { if $chain->chain_direction == 2 } selected="selected" { /if } >&lt;-&gt;</option>
      </select>
     </td>
     <td>
      <select name="chain_dst_target">
       <option value="0">any</option>
       { target_select_list target_idx=$chain->chain_dst_target }
      </select>
     </td>
    </tr>
   </table>
  </td>
 </tr>
 <tr>
  <td>Pipes:</td>
  <td>
   <table class="noborder">
    <tr>
     <td>
      <select size="10" name="avail[]" multiple="multiple">
       <option value="">********* Unused *********</option>
       { unused_pipes_select_list chain_idx=$chain->chain_idx }
      </select>
     </td>
     <td>&nbsp;</td>
     <td>
      <input type="button" value="&gt;&gt;" onclick="moveOptions(document.forms['chains'].elements['avail[]'], document.forms['chains'].elements['used[]']);" /><br />
      <input type="button" value="&lt;&lt;" onclick="moveOptions(document.forms['chains'].elements['used[]'], document.forms['chains'].elements['avail[]']);" />
     </td>
     <td>&nbsp;</td>
     <td>
      <select size="10" name="used[]" multiple="multiple">
       <option value="">********* Used *********</option>
       { used_pipes_select_list chain_idx=$chain->chain_idx }
      </select>
     </td>
    </tr>
   </table>
  </td>
 </tr>
 <tr>
  <td colspan="2">&nbsp;</td>
 </tr>
 <tr>
  <td style="text-align: center;"><a href="{$rewriter->get_page_url('Chains List')}" title="Back"><img src="{ $icon_arrow_left }" alt="arrow left icon" /></a></td>
  <td><input type="submit" value="Save" /></td>
 </tr>
</table>
</form>
{ page_end focus_to='chain_name' }
