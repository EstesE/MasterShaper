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
  <td style="vertical-align: top;">
   <table class="withborder2" id="pipelist">
    <thead>
    <tr>
     <td><img src="{ $icon_pipes }" alt="pipe icon" />&nbsp;<i>Pipe</i></td>
     <td><i>Used</i></td>
     <td><img src="{ $icon_servicelevels }" alt="servicelevel icon" />&nbsp;<i>Service Level (override in this chain only)</i></td>
     <td><i>Status</i></td>
    </tr>
    </thead>
     <tbody id="pipes">
    { pipe_list }
     <tr id="pipe{$pipe->pipe_idx}" { if $pipe->apc_pipe_idx == 0 } style="opacity: 0.5;" { /if }>
      <td>
       <img src="{ $icon_pipes }" alt="pipe icon" />&nbsp;{ $pipe->pipe_name }
      </td>
      <td style="text-align: center;">
       <input type="checkbox" name="used[]" value="{$pipe->pipe_idx}" { if $pipe->apc_pipe_idx != 0 } checked="checked" { /if } onclick="if(this.checked == false) $('table#pipelist tbody#pipes tr#pipe{$pipe->pipe_idx}').fadeTo(500, 0.50); else $('table#pipelist tbody#pipes tr#pipe{$pipe->pipe_idx}').fadeTo(500, 1);" />
      </td>
      <td>
       <select name="pipe_sl_idx[{$pipe->pipe_idx}]">
        <option value="0">*** No override ***</option>
        { service_level_select_list sl_idx=$pipe->sl_in_use }
       </select>
      </td>
      <td style="text-align: center;">
       <input type="hidden" id="pipe-active-{$pipe->pipe_idx}" name="pipe_active[{$pipe->pipe_idx}]" value="{$pipe->apc_pipe_active}" />
       <div class="toggle" id="toggle-{$pipe->pipe_idx}" style="display: inline;">
        <a class="toggle-off" id="pipe-{$pipe->pipe_idx}" parent="chain-{$chain->chain_idx}" to="off" title="Disable pipe { $pipe->pipe_name }" { if $pipe->apc_pipe_active != "Y" } style="display: none;" { /if } onclick="$('#pipe-active-{$pipe->pipe_idx}').val('N');"><img src="{ $icon_active }" alt="active icon" /></a>
        <a class="toggle-on" id="pipe-{$pipe->pipe_idx}" parent="chain-{$chain->chain_idx}" to="on" title="Enable pipe { $pipe->pipe_name }" { if $pipe->apc_pipe_active == "Y" } style="display: none;" { /if } onclick="$('#pipe-active-{$pipe->pipe_idx}').val('Y');"><img src="{ $icon_inactive }" alt="inactive icon" /></a>
       </div>
      </td>
     </tr>
    { /pipe_list }
     </tbody>
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
{literal}
<script language="JavaScript">
   $(function(){
      $("table#pipelist tbody#pipes").sortable({
         accept:      'tbody#pipe',
         greedy:      true,
         cursor:      'crosshair',
         placeholder: 'ui-state-highlight',
         delay:       250
      });
      $("table#pipelist tbody#pipes").disableSelection();
   });
</script>
{/literal}
{ page_end focus_to='chain_name' }
