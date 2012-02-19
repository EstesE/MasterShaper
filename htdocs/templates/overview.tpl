<form action="{$page->uri}" id="overview" method="post">
<input type="hidden" name="module" value="overview" />
<input type="hidden" name="action" value="store" />
{start_table icon=$icon_home alt="home icon" title="MasterShaper Ruleset Overview" }
<div>
{ if $cnt_network_paths == 0 }
<div style="padding-left: 20px; padding-top: 20px;">
There is no active <img src="{$icon_interfaces}" title="network path icon" />&nbsp;<a href="{$rewriter->get_page_url('Network Paths List')}" title="List Network Paths">Network Path</a> in this configuration!<br />
<br />
If you are entering <img src="{$icon_users}" alt="mastershaper icon" />&nbsp;<a href="{$rewriter->get_page_url('Others About')}" title="About MasterShaper">MasterShaper</a> for the first time then:<br />
<ul>1. Define <img src="{$icon_interfaces}" alt="interface icon" />&nbsp;<a href="{$rewriter->get_page_url('Interfaces List')}" title="List Interfaces">Interfaces</a></ul>
<ul>2. Declare a <img src="{$icon_interfaces}" alt="network path icon" />&nbsp;<a href="{$rewriter->get_page_url('Network Paths List')}" title="List Network Paths">Network Path</a></ul>
<ul>3. Define some <img src="{$icon_servicelevels}" alt="service level icon" />&nbsp;<a href="{$rewriter->get_page_url('Service Levels List')}" title="List Service Levels">Service Levels</a></ul>
<ul>4. Create a <img src="{$icon_filters}" alt="filter icon" />&nbsp;<a href="{$rewriter->get_page_url('Filters List')}" title="List Filters">Filter</a></ul>
<ul>5. Create a <img src="{$icon_pipes}" alt="pipe icon" />&nbsp;<a href="{$rewriter->get_page_url('Pipes List')}" title="List Pipes">Pipe</a> and assign <img src="{$icon_filters}" alt="filter icon" />&nbsp;<a href="{$rewriter->get_page_url('Filters List')}" title="List Filters">Filter</a> to it</ul>
<ul>6. Create a <img src="{$icon_chains}" alt="chain icon" />&nbsp;<a href="{$rewriter->get_page_url('Chains List')}" title="List Chains">Chain</a>, assign <img src="{$icon_pipes}" alt="pipe icon" />&nbsp;<a href="{$rewriter->get_page_url('Pipes List')}" title="List Pipes">Pipes</a> to it and attach it to a <img src="{$icon_interfaces}" alt="network path icon" />&nbsp;<a href="{$rewriter->get_page_url('Network Paths List')}" title="List Network Paths">Network Path</a></ul>
<ul>7. Go and <img src="{$icon_rules_load}" alt="rules icon" />&nbsp;<a href="{$rewriter->get_page_url('Rules Load')}" title="Load Ruleset">load</a> your ruleset!</ul>
</div>
{ /if }
{ ov_netpath }
<table style="width: 100%;" id="netpath{$netpath->netpath_idx}">
 <tr>
  <td style="height: 15px;" />
 </tr>
 <tr>
  <td>
   &nbsp;<a href="javascript:#" title="Collapse all chains within network path" onclick="toggle_content('tr[np={$netpath->netpath_idx}]', '#togglenp{$netpath->netpath_idx}', '{$icon_menu_down}', '{$icon_menu_right}', 'img[np={$netpath->netpath_idx}]'); return false;"><img src="{$icon_menu_right}" id="togglenp{$netpath->netpath_idx}" state=hidden /></a>
   <img src="{ $icon_interfaces }" alt="network path icon" />&nbsp;<a href="{$rewriter->get_page_url('Network Path Edit', $netpath->netpath_idx)}" title="Modify network path { $netpath->netpath_name }">Network Path { $netpath->netpath_name }</a>
   <a class="move-down" type="netpath" idx="{ $netpath->netpath_idx }"><img src="{ $icon_pipes_arrow_down }" alt="Move netpath down" /></a>
   <a class="move-up" type="netpath" idx="{ $netpath->netpath_idx }"><img src="{ $icon_pipes_arrow_up }" alt="Move netpath up" /></a>
  </td>
 </tr>
 <tr>
  <td style="height: 5px;" />
 </tr>
 <tr>
  <td>
   <table style="width: 100%;" class="withborder">
    <tr>
     <td class="colhead" colspan="2" style="width: 20%;">&nbsp;Name</td>
     <td class="colhead" style="text-align: center;">Service Level</td>
     <td class="colhead" style="text-align: center;">Fallback</td>
     <td class="colhead" style="text-align: center;">Source</td>
     <td class="colhead" style="text-align: center;">Direction</td>
     <td class="colhead" style="text-align: center;">Destination</td>
     <td class="colhead" style="text-align: center;">Action</td>
     <td class="colhead" style="text-align: center;">Position</td>
    </tr>

 { ov_chain np_idx=$netpath->netpath_idx }

    <tr onmouseover="setBackGrdColor(this, 'mouseover');" onmouseout="setBackGrdColor(this, 'mouseout');" id="chain{$chain->chain_idx}">
     <td colspan="2">
      <a href="javascript:#" title="Collapse chain" onclick="toggle_content('#chain{$chain->chain_idx} ~ [chain={$chain->chain_idx}]', '#togglechn{$chain->chain_idx}', '{$icon_menu_down}', '{$icon_menu_right}'); return false;"><img src="{$icon_menu_right}" id="togglechn{$chain->chain_idx}" np={$netpath->netpath_idx} state=hidden /></a>
      <img src="{ $icon_chains }" alt="chain icon" />&nbsp;
      <a href="{$rewriter->get_page_url('Chain Edit', $chain->chain_idx)}" title="Modify chain { $chain->chain_name }">{ $chain->chain_name }</a>
     </td>
     <td style="text-align: center;">
      <select name="chain_sl_idx[{ $chain->chain_idx }]">
       <option value="0">--- Ignore QoS ---</option>
       { service_level_select_list details=no sl_idx=$chain->chain_sl_idx }
      </select>
     </td> 

    { if $chain_has_sl }
     <td style="text-align: center;">
      <select name="chain_fallback_idx[{ $chain->chain_idx }]">
       <option value="0">--- No Fallback ---</option>
       { service_level_select_list details=no sl_idx=$chain->chain_fallback_idx }
      </select>
     </td>
    { else }
     <td>&nbsp;</td>
    { /if }

     <td style="text-align: center;">
      <select name="chain_src_target[{ $chain->chain_idx }]">
       <option value="0">any</option>
       { target_select_list target_idx=$chain->chain_src_target }
      </select>
     </td>
     <td style="text-align: center;">
      <select name="chain_direction[{ $chain->chain_idx }]">
       <option value="1" { if $chain->chain_direction == 1 } selected="selected" { /if }>--&gt;</option>
       <option value="2" { if $chain->chain_direction == 2 } selected="selected" { /if }>&lt;-&gt;</option>
      </select>
     </td>
     <td style="text-align: center;">
      <select name="chain_dst_target[{ $chain->chain_idx }]">
       <option value="0">any</option>
       { target_select_list target_idx=$chain->chain_dst_target }
      </select>
     </td>
     <td style="text-align: center;">
      <select name="chain_action[{ $chain->chain_idx }]">
       <option value="accept" { if $chain->chain_action == "accept" } selected="selected" { /if }>Accept</option>
       <option value="drop" { if $chain->chain_action == "drop" } selected="selected" { /if }>Drop</option>
       <option value="reject" { if $chain->chain_action == "reject" } selected="selected" { /if }>Reject</option>
      </select>
     </td>
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
    <tr onmouseover="setBackGrdColor(this, 'mouseover');" onmouseout="setBackGrdColor(this, 'mouseout');" id="pipe{$pipe->pipe_idx}" chain={$chain->chain_idx} np={$netpath->netpath_idx} style="display: none;">
     <td style="text-align: center;">{ $counter }</td>
     <td>
      <img src="{ $icon_pipes }" alt="pipes icon" />&nbsp;
      <a href="{$rewriter->get_page_url('Pipe Edit', $pipe->pipe_idx)}" title="Modify pipe { $pipe->pipe_name }">{ $pipe->pipe_name }</a>
     </td>
     <td style="text-align: center;">
      <select name="pipe_sl_idx[{ $apc_idx }]">
       <option value="0">*** { $pipe_sl_name } ***</option>
       { service_level_select_list details=no sl_idx=$apc_sl_idx }
      </select>
     </td>
     <td>&nbsp;</td>
     <td style="text-align: center;">
      <select name="pipe_src_target[{ $pipe->pipe_idx }]">
       <option value="0">any</option>
       { target_select_list target_idx=$pipe->pipe_src_target }
      </select>
     </td>
     <td style="text-align: center;">
      <select name="pipe_direction[{ $pipe->pipe_idx }]">
       <option value="1" { if $pipe->pipe_direction == 1 } selected="selected" { /if }>--&gt;</option>
       <option value="2" { if $pipe->pipe_direction == 2 } selected="selected" { /if }>&lt;-&gt;</option>
      </select>
     </td>
     <td style="text-align: center;">
      <select name="pipe_dst_target[{ $pipe->pipe_idx }]">
       <option value="0">any</option>
       { target_select_list target_idx=$pipe->pipe_dst_target }
      </select>
     </td>
     <td style="text-align: center;">
      <select name="pipe_action[{ $pipe->pipe_idx }]">
       <option value="accept" { if $pipe->pipe_action == "accept" } selected="selected" { /if}>Accept</option>
       <option value="drop" { if $pipe->pipe_action == "drop" } selected="selected" { /if }>Drop</option>
       <option value="reject" { if $pipe->pipe_action == "reject" } selected="selected" { /if }>Reject</option>
      </select>
     </td>
     <td style="text-align: center;">
      <a class="move-down" type="pipe" idx="{ $pipe->pipe_idx }"><img src="{ $icon_pipes_arrow_down }" alt="Move pipe down" /></a>
      <a class="move-up" type="pipe" idx="{ $pipe->pipe_idx }"><img src="{ $icon_pipes_arrow_up }" alt="Move pipe up" /></a>
     </td>
    </tr> 
    { ov_filter np_idx=$netpath->netpath_idx chain_idx=$chain->chain_idx pipe_idx=$pipe->pipe_idx }
    <tr onmouseover="setBackGrdColor(this, 'mouseover');" onmouseout="setBackGrdColor(this, 'mouseout');" chain={$chain->chain_idx} np={$netpath->netpath_idx} style="display: none;">
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
{/ov_netpath}
</div>
   </table>
  </td>
 </tr>
 <tr>
  <td>
   { include file=savebutton.tpl }
  </td>
 </tr>
</table>
</form>
