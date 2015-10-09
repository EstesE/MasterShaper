{start_table icon=$icon_filters alt="filter icon" title="Manage Filters"}
<table style="width: 100%;" class="withborder">
 <tr>
  <td colspan="2" style="text-align: center;">
   <img src="{$icon_new}" alt="new icon" />
   <a href="{get_page_url page='Filter New'}">Create a new Filter</a>
  </td>
 </tr>
 <tr>
  <td colspan="2">&nbsp;</td>
 </tr>
 <tr>
  <td><img src="{$icon_filters}" alt="filter icon" />&nbsp;<i>Filters</i></td> 
  <td style="text-align: center;"><i>Options</i></td>
 </tr>
 {filter_list}
 <tr onmouseover="setBackGrdColor(this, 'mouseover');" onmouseout="setBackGrdColor(this, 'mouseout');">
  <td>
   <img src="{$icon_filters}" alt="filter icon" />
   <a href="{get_page_url page='Filter Edit' id=$filter_idx}">{$filter_name}</a>
  </td>
  <td style="text-align: center;">
   <a class="clone" id="filter-{$filter_idx}" title="Clone"><img src="{$icon_clone}" alt="clone icon" /></a>
   <a class="delete" id="filter-{$filter_idx}" title="Delete"><img src="{$icon_delete}" alt="delete icon" /></a>
   <div class="toggle" id="toggle-{$filter_idx}" style="display: inline;">
    <a class="toggle-off" id="filter-{$filter_idx}" to="off" title="Disable filter {$filter_name}" {if $filter_active == 'N'} style="display: none;" {/if}><img src="{$icon_active}" alt="active icon" /></a>
    <a class="toggle-on" id="filter-{$filter_idx}" to="on" title="Enable filter {$filter_name}" {if $filter_active == 'Y'} style="display: none;" {/if}><img src="{$icon_inactive}" alt="inactive icon" /></a>
   </div>
  </td>
 </tr>
 {/filter_list}
</table>
