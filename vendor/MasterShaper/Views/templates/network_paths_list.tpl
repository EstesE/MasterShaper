{start_table icon=$icon_interfaces alt="network path icon" title="Manage Network Paths"}
<table style="width: 100%;" class="withborder"> 
 <tr>
  <td style="text-align: center;" colspan="4">
   <img src="{$icon_new}" alt="new icon" />
   <a href="{get_url page='Network Path New'}">Define a new Network Path</a>
  </td>
 </tr>
 <tr>
  <td colspan="4">&nbsp;</td>
 </tr>
 <tr>
  <td><img src="{$icon_interfaces}" alt="interface icon" />&nbsp;<i>Path</i></td>
  <td><img src="{$icon_interfaces}" alt="interface icon" />&nbsp;<i>Interface 1</i></td>
  <td><img src="{$icon_interfaces}" alt="interface icon" />&nbsp;<i>Interface 2</i></td>
  <td style="text-align: center;"><i>Options</i></td>
 </tr>
 {netpath_list}
 <tr onmouseover="setBackGrdColor(this, 'mouseover');" onmouseout="setBackGrdColor(this, 'mouseout');">
  <td>
   <img src="{$icon_interfaces}" alt="interface icon" />
   <a href="{get_url page='Network Path Edit' id=$netpath_idx}">{$netpath_name}</a>
  </td>
  <td>
   <a href="{get_url page='Interface Edit' id=$netpath_if1_idx}" title="Edit interface {$netpath_if1_name}"><img src="{$icon_interfaces}" alt="interface icon" />&nbsp;{$netpath_if1_name}</a> {if $netpath_if1_inside_gre == 'Y'}(inside GRE){/if}
  </td>
  <td>
   <a href="{get_url page='Interface Edit' id=$netpath_if2_idx}" title="Edit interface {$netpath_if2_name}"><img src="{$icon_interfaces}" alt="interface icon" />&nbsp;{$netpath_if2_name}</a> {if $netpath_if2_inside_gre == 'Y'}(inside GRE){/if}
  </td>
  <td style="text-align: center;">
   <a class="clone" id="networkpath-{$netpath_idx}" title="Clone"><img src="{$icon_clone}" alt="clone icon" /></a>
   <a class="delete" id="networkpath-{$netpath_idx}" title="Delete"><img src="{$icon_delete}" alt="delete icon" /></a>
   <div class="toggle" id="toggle-{$netpath_idx}" style="display: inline;">
    <a class="toggle-off" id="networkpath-{$netpath_idx}" to="off" title="Disable network path {$netpath_name}" {if $netpath_active == 'N'} style="display: none;" {/if}><img src="{$icon_active}" alt="active icon" /></a>
    <a class="toggle-on" id="networkpath-{$netpath_idx}" to="on" title="Enable network path {$netpath_name}" {if $netpath_active == 'Y'} style="display: none;" {/if}><img src="{$icon_inactive}" alt="inactive icon" /></a>
   </div>
  </td>
 </tr>
 {/netpath_list}
</table>
