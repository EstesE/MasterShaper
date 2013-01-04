<form action="{$page->uri}" id="overview" method="post">
<input type="hidden" name="module" value="overview" />
<input type="hidden" name="action" value="store" />
{ if ! $edit_mode }
 {start_table icon=$icon_home alt="home icon" title="MasterShaper Ruleset Overview (View-Mode)" }
{ else }
 {start_table icon=$icon_home alt="home icon" title="MasterShaper Ruleset Overview (Edit-Mode)" }
{ /if }
<div>
{ if $cnt_network_paths == 0 }
 { include file="welcome.tpl" }
{ /if }
{ ov_netpath }
<table style="width: 100%;" type="netpath" id="netpath{$netpath->netpath_idx}">
 <tr>
  <td style="height: 15px;" />
 </tr>
 <tr>
  <td style="width: 99%;">
   &nbsp;<a href="javascript:#" title="Collapse all chains within network path" onclick="toggle_content('tr[np={$netpath->netpath_idx}]', '#togglenp{$netpath->netpath_idx}', '{$icon_menu_down}', '{$icon_menu_right}', 'img[np={$netpath->netpath_idx}]'); return false;"><img src="{$icon_menu_right}" id="togglenp{$netpath->netpath_idx}" state="hidden" /></a>
   <img src="{ $icon_interfaces }" alt="network path icon" />&nbsp;<a href="{$rewriter->get_page_url('Network Path Edit', $netpath->netpath_idx)}" title="Modify network path { $netpath->netpath_name }">Network Path { $netpath->netpath_name }</a>
   <a class="move-down" type="netpath" idx="{ $netpath->netpath_idx }"><img src="{ $icon_pipes_arrow_down }" alt="Move netpath down" /></a>
   <a class="move-up" type="netpath" idx="{ $netpath->netpath_idx }"><img src="{ $icon_pipes_arrow_up }" alt="Move netpath up" /></a>
  </td>
  <td style="width: 1%;">
{ if ! $edit_mode }
 <a href="{ $page->self }?mode=edit" title="Switch to Edit-Mode">Edit-Mode</a>&nbsp;
{ else }
 <a href="{ $page->self }?mode=view" title="Switch to View-Mode">View-Mode</a>&nbsp;
{ /if }
  </td>
 </tr>
 <tr>
  <td style="height: 5px;" />
 </tr>
 <tr>
  <td colspan="2">
   <table style="width: 100%;" class="withborder">
    <thead>
    <tr>
     <td class="colhead" colspan="2" style="width: 18%;">
      &nbsp;Name
     </td>
     <td class="colhead" style="width: 18%; { if $edit_mode } text-align: center;{ /if }">
      Service Level
     </td>
     <td class="colhead" style="width: 18%; { if $edit_mode } text-align: center;{ /if }">
      Fallback
     </td>
     <td class="colhead" style="width: 18%; { if $edit_mode } text-align: center;{ /if }">
      Source
     </td>
     <td class="colhead" style="text-align: center; width: 5%;">
      Direction
     </td>
     <td class="colhead" style="width: 18%; { if $edit_mode } text-align: center;{ /if }">
      Destination
     </td>
     <!--<td class="colhead" style="text-align: center;">Action</td>-->
     <td class="colhead" style="text-align: center; width: 5%;">
      Position
     </td>
    </tr>
    </thead>

    <tbody>
 { ov_chain np_idx=$netpath->netpath_idx }

    <tr onmouseover="setBackGrdColor(this, 'mouseover');" onmouseout="setBackGrdColor(this, 'mouseout');" id="chain{$chain->chain_idx}" type="chain">
     <td colspan="2">
      <a href="javascript:#" title="Collapse chain" onclick="toggle_content('#chain{$chain->chain_idx} ~ [chain={$chain->chain_idx}]', '#togglechn{$chain->chain_idx}', '{$icon_menu_down}', '{$icon_menu_right}'); return false;"><img src="{$icon_menu_right}" id="togglechn{$chain->chain_idx}" np="{$netpath->netpath_idx}" state="hidden" /></a>
      <img src="{ $icon_chains }" alt="chain icon" />&nbsp;
      <a href="{$rewriter->get_page_url('Chain Edit', $chain->chain_idx)}" title="Modify chain { $chain->chain_name }">{ $chain->chain_name }</a>
     </td>
     <td { if $edit_mode } style="text-align: center;" { /if }>
      { if $edit_mode }
      <select name="chain_sl_idx[{ $chain->chain_idx }]">
       <option value="0">--- Ignore QoS ---</option>
       { service_level_select_list details=no sl_idx=$chain->chain_sl_idx }
      </select>
      { else }
       <img src="{ $icon_servicelevels }" alt="service level icon" />&nbsp;<a href="{$rewriter->get_page_url('Service Level Edit', $chain->chain_sl_idx)}" title="Modify servicel level { get_item_name type=sl idx=$chain->chain_sl_idx }">{ get_item_name type=sl idx=$chain->chain_sl_idx }</a>
      { /if }
     </td>

    { if $chain_has_sl }
     <td { if $edit_mode } style="text-align: center;" { /if }>
      { if $edit_mode }
      <select name="chain_fallback_idx[{ $chain->chain_idx }]">
       <option value="0">--- No Fallback ---</option>
       { service_level_select_list details=no sl_idx=$chain->chain_fallback_idx }
      </select>
      { else }
       <img src="{ $icon_servicelevels }" alt="service level icon" />&nbsp;<a href="{$rewriter->get_page_url('Service Level Edit', $chain->chain_sl_idx)}" title="Modify servicel level { get_item_name type=fallsl idx=$chain->chain_fallback_idx }">{ get_item_name type=fallsl idx=$chain->chain_fallback_idx }</a>
      { /if }
     </td>
    { else }
     <td>&nbsp;</td>
    { /if }

     <td { if $edit_mode } style="text-align: center;" { /if }>
      { if $edit_mode }
      <select name="chain_src_target[{ $chain->chain_idx }]">
       <option value="0">any</option>
       { target_select_list target_idx=$chain->chain_src_target }
      </select>
      { else }
       <img src="{ $icon_targets }" alt="target icon" />&nbsp;<a href="{$rewriter->get_page_url('Target Edit', $chain->chain_src_target)}" title="Modify target { get_item_name type=target idx=$chain->chain_src_target }">{ get_item_name type=target idx=$chain->chain_src_target }</a>

      { /if }
     </td>
     <td style="text-align: center;">
      { if $edit_mode }
      <select name="chain_direction[{ $chain->chain_idx }]">
       <option value="1" { if $chain->chain_direction == 1 } selected="selected" { /if }>--&gt;</option>
       <option value="2" { if $chain->chain_direction == 2 } selected="selected" { /if }>&lt;-&gt;</option>
      </select>
      { else }
       { get_item_name type=direction idx=$chain->chain_direction }
      { /if }
     </td>
     <td { if $edit_mode } style="text-align: center;" { /if }>
      { if $edit_mode }
      <select name="chain_dst_target[{ $chain->chain_idx }]">
       <option value="0">any</option>
       { target_select_list target_idx=$chain->chain_dst_target }
      </select>
      { else }
       <img src="{ $icon_targets }" alt="target icon" />&nbsp;<a href="{$rewriter->get_page_url('Target Edit', $chain->chain_dst_target)}" title="Modify target { get_item_name type=target idx=$chain->chain_dst_target }">{ get_item_name type=target idx=$chain->chain_dst_target }</a>
      { /if }
     </td>
     {* <!-- hide actions for now, not in use -->
     <td style="text-align: center;">
      <select name="chain_action[{ $chain->chain_idx }]">
       <option value="accept" { if $chain->chain_action == "accept" } selected="selected" { /if }>Accept</option>
       <option value="drop" { if $chain->chain_action == "drop" } selected="selected" { /if }>Drop</option>
       <option value="reject" { if $chain->chain_action == "reject" } selected="selected" { /if }>Reject</option>
      </select>
     </td> *}
     <td style="text-align: center;">
      <a class="move-down" type="chain" idx="{ $chain->chain_idx }"><img src="{ $icon_chains_arrow_down }" alt="Move chain down" /></a>
      <a class="move-up" type="chain" idx="{ $chain->chain_idx }"><img src="{ $icon_chains_arrow_up }" alt="Move chain up" /></a>
     </td>
    </tr>

  <!-- pipes are only available if the chain DOES NOT ignore
       QoS or DOES NOT use fallback service level
  -->
  { if $chain->chain_sl_idx != 0 && $chain->chain_fallback_idx != 0 }
   { ov_pipe np_idx=$netpath->netpath_idx chain_idx=$chain->chain_idx }
    <input type="hidden" name="pipes[{ $pipe_counter }]" value="{ $pipe->pipe_idx }" />
    <tr onmouseover="setBackGrdColor(this, 'mouseover');" onmouseout="setBackGrdColor(this, 'mouseout');" id="pipe{$pipe->apc_idx}" chain="{$chain->chain_idx}" np="{$netpath->netpath_idx}" type="pipe" style="display: none;">
     <td style="text-align: center;">{ $counter }</td>
     <td>
      <img src="{ $icon_pipes }" alt="pipes icon" />&nbsp;
      <a href="{$rewriter->get_page_url('Pipe Edit', $pipe->pipe_idx)}" title="Modify pipe { $pipe->pipe_name }">{ $pipe->pipe_name }</a>
     </td>
     <td { if $edit_mode } style="text-align: center;" { /if }>
      { if $edit_mode }
      <select name="pipe_sl_idx[{ $apc_idx }]">
       <option value="0">*** { $pipe_sl_name } ***</option>
       { service_level_select_list details=no sl_idx=$apc_sl_idx }
      </select>
      { else }
       <img src="{ $icon_pipes }" alt="pipe icon" />&nbsp;<a href="{$rewriter->get_page_url('Service Level Edit', $apc_sl_idx)}" title="Modify service level { get_item_name type=sl idx=$apc_sl_idx }">{ get_item_name type=sl idx=$apc_sl_idx }</a>
      { /if }
     </td>
     <td>&nbsp;</td>
     <td { if $edit_mode } style="text-align: center;" { /if }>
      { if $edit_mode }
      <select name="pipe_src_target[{ $pipe->pipe_idx }]">
       <option value="0">any</option>
       { target_select_list target_idx=$pipe->pipe_src_target }
      </select>
      { else }
       <img src="{ $icon_targets }" alt ="target icon" />&nbsp;<a href="{$rewriter->get_page_url('Target Edit', $pipe->pipe_src_target)}" title="Modify target { get_item_name type=target idx=$pipe->pipe_src_target }">{ get_item_name type=target idx=$pipe->pipe_src_target }</a>
      { /if }
     </td>
     <td style="text-align: center;">
      { if $edit_mode }
      <select name="pipe_direction[{ $pipe->pipe_idx }]">
       <option value="1" { if $pipe->pipe_direction == 1 } selected="selected" { /if }>--&gt;</option>
       <option value="2" { if $pipe->pipe_direction == 2 } selected="selected" { /if }>&lt;-&gt;</option>
      </select>
      { else }
       { get_item_name type=direction idx=$pipe->pipe_direction }
      { /if }
     </td>
     <td { if $edit_mode } style="text-align: center;" { /if }>
      { if $edit_mode }
      <select name="pipe_dst_target[{ $pipe->pipe_idx }]">
       <option value="0">any</option>
       { target_select_list target_idx=$pipe->pipe_dst_target }
      </select>
      { else }
       <img src="{ $icon_targets }" alt ="target icon" />&nbsp;<a href="{$rewriter->get_page_url('Target Edit', $pipe->pipe_dst_target)}" title="Modify target { get_item_name type=target idx=$pipe->pipe_dst_target }">{ get_item_name type=target idx=$pipe->pipe_dst_target }</a>
      { /if }
     </td>
     {*
      <td style="text-align: center;">
      <select name="pipe_action[{ $pipe->pipe_idx }]">
       <option value="accept" { if $pipe->pipe_action == "accept" } selected="selected" { /if}>Accept</option>
       <option value="drop" { if $pipe->pipe_action == "drop" } selected="selected" { /if }>Drop</option>
       <option value="reject" { if $pipe->pipe_action == "reject" } selected="selected" { /if }>Reject</option>
      </select>
     </td> *}
     <td style="text-align: center;">
      <a class="move-down" type="pipe" idx="{ $pipe->apc_idx }"><img src="{ $icon_pipes_arrow_down }" alt="Move pipe down" /></a>
      <a class="move-up" type="pipe" idx="{ $pipe->apc_idx }"><img src="{ $icon_pipes_arrow_up }" alt="Move pipe up" /></a>
     </td>
    </tr>
    { ov_filter np_idx=$netpath->netpath_idx chain_idx=$chain->chain_idx pipe_idx=$pipe->pipe_idx }
    <tr onmouseover="setBackGrdColor(this, 'mouseover');" onmouseout="setBackGrdColor(this, 'mouseout');" chain="{$chain->chain_idx}" np="{$netpath->netpath_idx}" pipe="{ $pipe->apc_idx }" type="filter" style="display: none;">
     <td>&nbsp;</td>
     <td colspan="7">
      <img src="{ $icon_treeend }" alt="tree" />
      <img src="{ $icon_filters }" alt="filter icon" />&nbsp;
      <a href="{$rewriter->get_page_url('Filter Edit', $filter->filter_idx)}" title="Modify filter { $filter->filter_name }">{ $filter->filter_name }</a>
     </td>
     <td>&nbsp;</td>
    </tr>
    {/ov_filter}
   {/ov_pipe}
  {/if}
 {/ov_chain}
   </tbody>
   </table>
  </td>
 </tr>
</table>
{/ov_netpath}
</div>
{ if $cnt_network_paths > 0 }
 { if $edit_mode }
 <table>
 <tr>
  <td>
   { include file=savebutton.tpl }
  </td>
 </tr>
 </table>
 { /if }
{ /if }
</form>
