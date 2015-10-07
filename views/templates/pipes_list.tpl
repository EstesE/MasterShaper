{start_table icon=$icon_pipes alt="pipe icon" title="Manage Pipes"}  
<table style="width: 100%;" class="withborder"> 
 <tr>
  <td style="text-align: center;" colspan="4">
   <img src="{$icon_new}" alt="new icon" />
   <a href="{$rewriter->get_page_url('Pipe New')}">Create a new Pipe</a>
  </td>
 </tr>
 <tr>
  <td colspan="4">&nbsp;</td>
 </tr>
 <tr>
  <td><img src="{$icon_pipes}" alt="pipe icon" />&nbsp;<i>Pipes</i></td>
  <td><img src="{$icon_filters}" alt="filter icon" />&nbsp;<i>Filters</i></td>
  <td style="text-align: center;"><i>Options</i></td>
 </tr>
 {pipe_list}
 <tr onmouseover="setBackGrdColor(this, 'mouseover');" onmouseout="setBackGrdColor(this, 'mouseout');">
  <td>
   <img src="{$icon_pipes}" alt="pipe icon" />
   <a href="{$rewriter->get_page_url('Pipe Edit', $pipe_idx)}">{$pipe_name}</a>
  </td>
  <td>
   <img src="{$icon_filters}" alt="filter icon" />
   {foreach from=$pipe_use_filters key=filter_idx item=filter_name name=filters}
    <a href="{$rewriter->get_page_url('Filter Edit', $filter_idx)}">{$filter_name}</a>{if !isset($smarty.foreach.filters.last) || empty($smarty.foreach.filters.last)},{/if}
   {foreachelse}
    &nbsp;
   {/foreach}
  </td>
  <td style="text-align: center;">
   <a class="clone" id="pipe-{$pipe_idx}" title="Clone"><img src="{$icon_clone}" alt="clone icon" /></a>
   <a class="delete" id="pipe-{$pipe_idx}" title="Delete"><img src="{$icon_delete}" alt="delete icon" /></a>
   <div class="toggle" id="toggle-{$pipe_idx}" style="display: inline;">
    <a class="toggle-off" id="pipe-{$pipe_idx}" to="off" title="Disable pipe {$pipe_name}" {if $pipe_active == 'N'} style="display: none;" {/if}><img src="{$icon_active}" alt="active icon" /></a>
    <a class="toggle-on" id="pipe-{$pipe_idx}" to="on" title="Enable pipe {$pipe_name}" {if $pipe_active == 'Y'} style="display: none;" {/if}><img src="{$icon_inactive}" alt="inactive icon" /></a>
   </div>
   <a class="assign-pipe-to-chains" id="pipe-{$pipe_idx}" title="Assign Pipe to one or more Chains"><img src="{$icon_chains_assign_pipe}" alt="Assign Pipe to Chain" /></a>
  </td>
 </tr>
 {/pipe_list}
</table>
<div id='dialog' style="visibility: hidden;"></div>
