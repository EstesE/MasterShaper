{start_table icon=$icon_hosts alt="host profile icon" title="Manage Host Profiles"}
<table style="width: 100%;" class="withborder"> 
 <tr>
  <td style="text-align: center;" colspan="4">
   <img src="{$icon_new}" alt="new icon" />
   <a href="{$rewriter->get_page_url('Host Profile New')}">Define a new Host Profile</a>
  </td>
 </tr>
 <tr>
  <td colspan="2">&nbsp;</td>
 </tr>
 <tr>
  <td><img src="{$icon_hosts}" alt="host icon" />&nbsp;<i>Profile</i></td>
  <td style="text-align: center;"><i>Options</i></td>
 </tr>
 {host_list}
 <tr onmouseover="setBackGrdColor(this, 'mouseover');" onmouseout="setBackGrdColor(this, 'mouseout');">
  <td>
   <img src="{$icon_hosts}" alt="host icon" />
   <a href="{$rewriter->get_page_url('Host Profile Edit', $host_idx)}">{$host_name}</a>
  </td>
  <td style="text-align: center;">
   <a class="clone" id="hostprofile-{$host_idx}" title="Clone"><img src="{$icon_clone}" alt="clone icon" /></a>
   <a class="delete" id="hostprofile-{$host_idx}" title="Delete"><img src="{$icon_delete}" alt="delete icon" /></a>
   <div class="toggle" id="toggle-{$host_idx}" style="display: inline;">
    <a class="toggle-off" id="hostprofile-{$host_idx}" to="off" title="Disable host profile {$host_name}" {if $host_active == 'N'} style="display: none;" {/if}><img src="{$icon_active}" alt="active icon" /></a>
    <a class="toggle-on" id="hostprofile-{$host_idx}" to="on" title="Enable host profile {$host_name}" {if $host_active == 'Y'} style="display: none;" {/if}><img src="{$icon_inactive}" alt="inactive icon" /></a>
   </div>
  </td>
 </tr>
 {/host_list}
</table>
