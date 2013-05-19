{start_table icon=$icon_users alt="user icon" title="Manage Users"}  
<table style="width: 100%;" class="withborder"> 
 <tr>
  <td style="text-align: center;" colspan="2">
   <img src="{$icon_new}" alt="new icon" />
   <a href="{$rewriter->get_page_url('User New')}">Create a new User</a>
  </td>
 </tr>
 <tr>
  <td colspan="2">&nbsp;</td>
 </tr>
 <tr>
  <td><img src="{$icon_users}" alt="user icon" />&nbsp;<i>Name</i></td>
  <td style="text-align: center;"><i>Options</i></td>
 </tr>
 {user_list}
 <tr onmouseover="setBackGrdColor(this, 'mouseover');" onmouseout="setBackGrdColor(this, 'mouseout');">
  <td>
   <img src="{$icon_users}" alt="user icon" />
   <a href="{$rewriter->get_page_url('User Edit', $user_idx)}">{$user_name}</a>
  </td>
  <td style="text-align: center;">
   <a class="delete" id="user-{$user_idx}" title="Delete"><img src="{$icon_delete}" alt="delete icon" /></a>
   <div class="toggle" id="toggle-{$user_idx}" style="display: inline;">
    <a class="toggle-off" id="user-{$user_idx}" to="off" title="Disable user {$user_name}" {if $user_active == 'N'} style="display: none;" {/if}><img src="{$icon_active}" alt="active icon" /></a>
    <a class="toggle-on" id="user-{$user_idx}" to="on" title="Enable user {$user_name}" {if $user_active == 'Y'} style="display: none;" {/if}><img src="{$icon_inactive}" alt="inactive icon" /></a>
   </div>
  </td>
 </tr>
 {/user_list}
</table>
