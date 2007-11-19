{start_table icon=$icon_chains alt="pipe icon" title="Manage Chains" }
<table style="width: 100%;" class="withborder">
 <tr>
  <td colspan="4" style="text-align: center;">
   <img src="{ $icon_new }" alt="new icon" />
   <a href="javascript:refreshContent('chains', '&mode=new');" title="Create a new Chain">Create a new Chain</a>
  </td>
 </tr>
 <tr>
  <td colspan="4">&nbsp;</td>
 </tr>
 <tr>
  <td><img src="{ $icon_chains }" alt="chain icon" />&nbsp;<i>Chain-Name</i></td>
  <td><img src="{ $icon_servicelevels }" alt="servicelevel icon" />&nbsp;<i>Service Level</i></td>
  <td><img src="{ $icon_servicelevels }" alt="servicelevel icon" />&nbsp;<i>Fallback</i></td>
  <td style="text-align: center;"><i>Options</i></td>
 </tr>
 { chain_list }
 <tr onmouseover="setBackGrdColor(this, 'mouseover');" onmouseout="setBackGrdColor(this, 'mouseout');">
  <td>
   <img src="{ $icon_chains }" alt="chain icon" />
   <a href="javascript:refreshContent('chains', '&mode=edit&idx={ $chain_idx }');" title="Click to modify">{ $chain_name }</a>
  </td>
  <td>
   <img src="{ $icon_servicelevels }" alt="servicelevel icon" />
  { if $chain_sl_idx != 0 }
   <a href="javascript:refreshContent('servicelevels', '&mode=edit&idx={ $chain_sl_idx }');">{ $chain_sl_name }</a>
  { else }
   { $chain_sl_name }
  { /if }
  </td>
  <td>
   <img src="{ $icon_servicelevels }" alt="servicelevel icon" />
  { if $chain_sl_idx != 0 && $chain_fallback_idx != 0 }
   <a href="javascript:refreshContent('servicelevels', '&mode=edit&idx={ $chain_fallback_idx }');">{ $chain_fallback_name }</a>
  { else }
   { $chain_fallback_name }
  { /if }
  </td>
  <td style="text-align: center;">
   <a href="javascript:deleteObj('chain', 'chains', '{ $chain_idx }');" title="Delete"><img src="{ $icon_delete }" alt="delete icon" /></a>
   { if $chain_active == 'Y' }
   <a href="javascript:toggleChainStatus('{ $chain_idx }', '0');" title="Disable chain { $chain_name }"><img src="{ $icon_active }" alt="status icon" /></a>
   { else }
   <a href="javascript:toggleChainStatus('{ $chain_idx }', '1');" title="Enable chain { $chain_name }"><img src="{ $icon_inactive }" alt="status icon" /></a>
   { /if }
  </td>
 </tr>
 { /chain_list }
</table>
