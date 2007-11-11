<pre id="target"></pre>
<form action="rpc.php?action=store" id="chains" onsubmit="saveChain(this); return false;" method="post">
<input type="hidden" name="module" value="chain" />
<input type="hidden" name="action" value="modify" />
{ if !$chain_idx }
 {start_table icon=$icon_chains alt="chain icon" title="Create a new Chain" }
 <input type="hidden" name="chain_new" value="1" />
{ else }
 {start_table icon=$icon_chains alt="chain icon" title="Modify chain $chain_name" }
 <input type="hidden" name="chain_new" value="0" />
 <input type="hidden" name="namebefore" value="{ $chain_name }" />
 <input type="hidden" name="chain_idx" value="{ $chain_idx }" />
{ /if }
<table style="width: 100%;" class="withborder2">
 <tr>
  <td colspan="2">
   <img src="{ $icon_chains }" alt="chain icon" />&nbsp;General
  </td>
 </tr>
 <tr>
  <td style="white-space: nowrap;">Name:</td>
  <td style="white-space: nowrap;"><input type="text" name="chain_name" size="40" value="{ $chain_name }" /></td>
 </tr>
 <tr>
  <td style="white-space: nowrap;">Status:</td>
  <td style="white-space: nowrap;">
   <input type="radio" name="chain_active" value="Y" { if $chain_active == "Y" } checked="checked" { /if } />Active
   <input type="radio" name="chain_active" value="N" { if $chain_active != "Y" } checked="checked" { /if } />Inactive
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
    { service_level_select_list sl_idx=$chain_sl_idx }
    <option value="0" { if $chain_sl_idx == 0 } selected="selected" { /if } >--- Ignore QoS ---</option>
   </select>
  </td>
 </tr>
 <tr>
  <td style="white-space: nowrap;">Fallback:</td>
  <td style="white-space: nowrap;">
   <select name="chain_fallback_idx">
    { service_level_select_list sl_idx=$chain_fallback_idx }
    <option value="0" { if $chain_fallback_idx == 0 } selected="selected" { /if } >--- No Fallback ---</option>
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
    { network_path_select_list np_idx=$chain_netpath_idx }
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
       { target_select_list target_idx=$chain_src_target }
      </select>
     </td>
     <td>
      <select name="chain_direction">
       <option value="1" { if $chain_direction == 1 } selected="selected" { /if } >--&gt;</option>
       <option value="2" { if $chain_direction == 2 } selected="selected" { /if } >&lt;-&gt;</option>
      </select>
     </td>
     <td>
      <select name="chain_dst_target">
       <option value="0">any</option>
       { target_select_list target_idx=$chain_dst_target }
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
  <td style="text-align: center;"><a href="javascript:refreshContent('chains');" title="Back"><img src="{ $icon_arrow_left }" alt="arrow left icon" /></a></td>
  <td><input type="submit" value="Save" /></td>
 </tr>
</table>
</form>
