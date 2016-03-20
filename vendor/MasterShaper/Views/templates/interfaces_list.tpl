{start_table icon=$icon_interfaces alt="interface icon" title="Manage Interfaces"}
<table style="width: 100%;" class="withborder"> 
 <tr>
  <td style="text-align: center;" colspan="4">
   <img src="{$icon_new}" alt="new icon" />
   <a href="{get_url page='Interface New'}">Add a new Interface</a>
  </td>
 </tr>
 <tr>
  <td colspan="4">&nbsp;</td>
 </tr>
 <tr>
  <td><img src="{$icon_interfaces}" alt="interface icon" />&nbsp;<i>Interface</i></td>
  <td><img src="{$icon_interfaces}" alt="interface icon" />&nbsp;<i>Bandwidth</i></td>
  <td><img src="{$icon_servicelevels}" alt="service level icon" />&nbsp;<i>Fallback Service Level</i></td>
  <td style="text-align: center;"><i>Options</i></td>
 </tr>
 {interface_list}
 <tr onmouseover="setBackGrdColor(this, 'mouseover');" onmouseout="setBackGrdColor(this, 'mouseout');">
  <td>
   <img src="{$icon_interfaces}" alt="interface icon" />
   <a href="{get_url page='Interface Edit' id=$if_idx}">{$if_name}</a>
  </td>
  <td>
   {$if_speed}
  </td>
  <td>
   {if $if_fallback_idx != 0}
    <a href="{get_url page='Service Level Edit' id=$if_fallback_idx}" title="Edit service level $if_fallback_name"><img src="{$icon_servicelevels}" alt="service level icon" />&nbsp;{$if_fallback_name}</a>
   {else}
    &nbsp;
   {/if}
  </td>
  <td style="text-align: center;">
   <a class="clone" id="interface-{$if_idx}" title="Clone"><img src="{$icon_clone}" alt="clone icon" /></a>
   <a class="delete" id="interface-{$if_idx}" title="Delete"><img src="{$icon_delete}" alt="delete icon" /></a>
   <div class="toggle" id="toggle-{$if_idx}" style="display: inline;">
    <a class="toggle-off" id="interface-{$if_idx}" to="off" title="Disable interface {$if_name}" {if $if_active == 'N'} style="display: none;" {/if}><img src="{$icon_active}" alt="active icon" /></a>
    <a class="toggle-on" id="interface-{$if_idx}" to="on" title="Enable interface {$if_name}" {if $if_active == 'Y'} style="display: none;" {/if}><img src="{$icon_inactive}" alt="inactive icon" /></a>
   </div>
  </td>
 </tr>
 {/interface_list}
</table>
