<form action="rpc.php?action=store" id="overview" onsubmit="saveForm(this, 'overview'); return false;" method="post">
<input type="hidden" name="module" value="overview" />
<input type="hidden" name="action" value="modify" />
{start_table icon=$icon_home alt="home icon" title="MasterShaper Ruleset Overview" }
{ ov_netpath }
<table style="width: 100%;">
 <tr>
  <td style="height: 15px;" />
 </tr>
 <tr>
  <td>
   &nbsp;
   Network Path '{ $netpath_name }'
   <a href="javascript:alterPosition('netpath', '{ $netpath_idx }', 'down');"><img src="{ $icon_pipes_arrow_down }" alt="Move netpath down" /></a>
   <a href="javascript:alterPosition('netpath', '{ $netpath_idx }', 'up');"><img src="{ $icon_pipes_arrow_up }" alt="Move netpath up" /></a>
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

 { ov_chain np_idx=$netpath_idx }

    <tr onmouseover="setBackGrdColor(this, 'mouseover');" onmouseout="setBackGrdColor(this, 'mouseout');">
     <td colspan="2">
      <input type="hidden" name="chains[{ $chain_cnt }]" value="{ $chain_idx }" />
      <img src="{ $icon_chains }" alt="chain icon" />&nbsp;
      <a href="javascript:refreshContent('chains', '&mode=edit&idx={ $chain_idx }');" title="Modify chain { $chain_name }">{ $chain_name }</a>
     </td>
     <td style="text-align: center;">
      <select name="chain_sl_idx[{ $chain_idx }]">
       <option value="0">--- Ignore QoS ---</option>
       { sl_list idx=$chain_sl_idx }
      </select>
     </td> 

    { if $chain_has_sl }
     <td style="text-align: center;">
      <select name="chain_fallback_idx[{ $chain_idx }]">
       <option value="0">--- No Fallback ---</option>
       { sl_list idx=$chain_fallback_idx }
      </select>
     </td>
    { else }
     <td>&nbsp;</td>
    { /if }

     <td style="text-align: center;">
      <select name="chain_src_target[{ $chain_idx }]">
       <option value="0">any</option>
       { target_list idx=$chain_src_target }
      </select>
     </td>
     <td style="text-align: center;">
      <select name="chain_direction[{ $chain_idx }]">
       <option value="1" { if $chain_direction == 1 } selected="selected" { /if }>--&gt;</option>
       <option value="2" { if $chain_direction == 2 } selected="selected" { /if }>&lt;-&gt;</option>
      </select>
     </td>
     <td style="text-align: center;">
      <select name="chain_dst_target[{ $chain_idx }]">
       <option value="0">any</option>
       { target_list idx=$chain_dst_target }
      </select>
     </td>
     <td style="text-align: center;">
      <select name="chain_action[{ $chain_idx }]">
       <option value="accept" { if $chain_action == "accept" } selected="selected" { /if }>Accept</option>
       <option value="drop" { if $chain_action == "drop" } selected="selected" { /if }>Drop</option>
       <option value="reject" { if $chain_action == "reject" } selected="selected" { /if }>Reject</option>
      </select>
     </td>
     <td style="text-align: center;">
      <a href="javascript:alterPosition('chain', '{ $chain_idx }', 'down');"><img src="{ $icon_chains_arrow_down }" alt="Move chain down" /></a>
      <a href="javascript:alterPosition('chain', '{ $chain_idx }', 'up');"><img src="{ $icon_chains_arrow_up }" alt="Move chain up" /></a>
     </td>
    </tr> 

  <!-- pipes are only available if the chain DOES NOT ignore
       QoS or DOES NOT use fallback service level
  -->
  { if $chain_sl_idx != 0 && $chain_fallback_idx != 0 }
   { ov_pipe np_idx=$netpath_idx chain_idx=$chain_idx }
    <input type="hidden" name="pipes[{ $pipe_counter }]" value="{ $pipe_idx }" />
    <tr onmouseover="setBackGrdColor(this, 'mouseover');" onmouseout="setBackGrdColor(this, 'mouseout');">
     <td style="text-align: center;">{ $counter }</td>
     <td>
      <img src="{ $icon_pipes }" alt="pipes icon" />&nbsp;
      <a href="javascript:refreshContent('pipes', '&mode=edit&idx={ $pipe_idx }');" title="Modify pipe { $pipe_name }">{ $pipe_name }</a>
     </td>
     <td style="text-align: center;">
      <select name="pipe_sl_idx[{ $pipe_idx }]"> 
      { sl_list idx=$pipe_sl_idx }
      </select>
     </td>
     <td>&nbsp;</td>
     <td style="text-align: center;">
      <select name="pipe_src_target[{ $pipe_idx }]">
       <option value="0">any</option>
       { target_list idx=$pipe_src_target }
      </select>
     </td>
     <td style="text-align: center;">
      <select name="pipe_direction[{ $pipe_idx }]">
       <option value="1" { if $pipe_direction == 1 } selected="selected" { /if }>--&gt;</option>
       <option value="2" { if $pipe_direction == 2 } selected="selected" { /if }>&lt;-&gt;</option>
      </select>
     </td>
     <td style="text-align: center;">
      <select name="pipe_dst_target[{ $pipe_idx }]">
       <option value="0">any</option>
       { target_list idx=$pipe_dst_target }
      </select>
     </td>
     <td style="text-align: center;">
      <select name="pipe_action[{ $pipe_idx }]">
       <option value="accept" { if $pipe_action == "accept" } selected="selected" { /if}>Accept</option>
       <option value="drop" { if $pipe_action == "drop" } selected="selected" { /if }>Drop</option>
       <option value="reject" { if $pipe_action == "reject" } selected="selected" { /if }>Reject</option>
      </select>
     </td>
     <td style="text-align: center;">
      <a href="javascript:alterPosition('pipe', '{ $pipe_idx }', 'down');"><img src="{ $icon_pipes_arrow_down }" alt="Move pipe down" /></a>
      <a href="javascript:alterPosition('pipe', '{ $pipe_idx }', 'up');"><img src="{ $icon_pipes_arrow_up }" alt="Move pipe up" /></a>
     </td>
    </tr> 
    { ov_filter np_idx=$netpath_idx chain_idx=$chain_idx pipe_idx=$pipe_idx }
    <tr onmouseover="setBackGrdColor(this, 'mouseover');" onmouseout="setBackGrdColor(this, 'mouseout');">
     <td>&nbsp;</td>
     <td colspan="7">
      <img src="{ $icon_treeend }" alt="tree" />
      <img src="{ $icon_filters }" alt="filter icon" />&nbsp;
      <a href="javascript:refreshContent('filters', '&mode=edit&idx={ $filter_idx }');" title="Modify filter { $filter_name }">{ $filter_name }</a>
     </td>
     <td>&nbsp;</td>
    </tr> 


    {/ov_filter}
   {/ov_pipe}
  {/if}
 {/ov_chain}
{/ov_netpath}
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
