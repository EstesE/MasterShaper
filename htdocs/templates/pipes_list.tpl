{start_table icon=$icon_pipes alt="pipe icon" title="Manage Pipes" }  
<table style="width: 100%;" class="withborder"> 
 <tr>
  <td style="text-align: center;" colspan="4">
   <img src="{ $icon_new }" alt="new icon" />
   <a href="javascript:refreshContent('pipes', '&mode=new');">Create a new Pipe</a>
  </td>
 </tr>
 <tr>
  <td colspan="4">&nbsp;</td>
 </tr>
 <tr>
  <td><img src="{ $icon_pipes }" alt="pipe icon" />&nbsp;<i>Pipes</i></td>
  <td><img src="{ $icon_chains }" alt="chain icon" />&nbsp;<i>Chains</i></td>
  <td><img src="{ $icon_filters }" alt="filter icon" />&nbsp;<i>Filters</i></td>
  <td style="text-align: center;"><i>Options</i></td>
 </tr>
 { pipe_list }
 <tr onmouseover="setBackGrdColor(this, 'mouseover');" onmouseout="setBackGrdColor(this, 'mouseout');">
  <td>
   <img src="{ $icon_pipes }" alt="pipe icon" />
   <a href="javascript:refreshContent('pipes', '&mode=edit&idx={ $pipe_idx }');">{ $pipe_name }</a>
  </td>
  <td>
   <img src="{ $icon_chains }" alt="chain icon" />
   <a href="javascript:refreshContent('chains', '&mode=edit&idx={ $pipe_chain_idx }');">{ $chain_name }</a>
  </td>
  <td>
   <img src="{ $icon_filters }" alt="filter icon" />
   { $pipe_filters }
  </td>
  <td style="text-align: center;">
   <a href="javascript:deleteObj('pipe', 'pipes', '{ $pipe_idx }');" title="Delete"><img src="{ $icon_delete }" alt="delete icon" /></a>
   { if $pipe_active == "Y" }
   <a href="javascript:toggleStatus('pipe', 'pipes', '{ $pipe_idx }', '0');" title="Disable pipe { $pipe_name }"><img src="{ $icon_active }" alt="active icon" /></a>
   { else }
   <a href="javascript:toggleStatus('pipe', 'pipes', '{ $pipe_idx }', '1');" title="Enable pipe { $pipe_name }"><img src="{ $icon_inactive }" alt="inactive icon" /></a>
   { /if }
  </td>
 </tr>
 { /pipe_list }
</table>
