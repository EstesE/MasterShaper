{start_table icon=$icon_chains alt="pipe icon" title="Manage Chains" }
<table style="width: 100%;" class="withborder">
 <tr>
  <td colspan="4" style="text-align: center;">
   <img src="{ $icon_new }" alt="new icon" />
   <a href="{$rewriter->get_page_url('Chain New')}" title="Create a new Chain">Create a new Chain</a>
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
   <a href="{$rewriter->get_page_url('Chain Edit', $chain_idx)}" title="Click to modify">{ $chain_name }</a>
  </td>
  <td>
   <img src="{ $icon_servicelevels }" alt="servicelevel icon" />
  { if $chain_sl_idx != 0 }
   <a href="{$rewriter->get_page_url('Service Level Edit', $chain_sl_idx)}">{ $chain_sl_name }</a>
  { else }
   { $chain_sl_name }
  { /if }
  </td>
  <td>
   <img src="{ $icon_servicelevels }" alt="servicelevel icon" />
  { if $chain_sl_idx != 0 && $chain_fallback_idx != 0 }
   <a href="{$rewriter->get_page_url('Service Level Edit', $chain_fallback_idx)}">{ $chain_fallback_name }</a>
  { else }
   { $chain_fallback_name }
  { /if }
  </td>
  <td style="text-align: center;">
   <!--<a href="javascript:deleteObj('chain', 'chains', '{ $chain_idx }');" title="Delete"><img src="{ $icon_delete }" alt="delete icon" /></a>-->
   <a title="Delete" class="delete" id="chain-{$chain_idx}"><img src="{ $icon_delete }" alt="delete icon" /></a>
   <div class="toggle" id="toggle-{$chain_idx}" style="display: inline;">
    <a class="toggle-off" id="chain-{$chain_idx}" to="off" title="Disable chain { $chain_name }" { if $chain_active == 'N' } style="display: none;" { /if }><img src="{ $icon_active }" alt="status icon" /></a>
    <a class="toggle-on" id="chain-{$chain_idx}" to="on" title="Enable chain { $chain_name }" { if $chain_active == 'Y'} style="display: none;" { /if }><img src="{ $icon_inactive }" alt="status icon" /></a>
   </div>
  </td>
 </tr>
 { /chain_list }
</table>
