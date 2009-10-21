{start_table icon=$icon_filters alt="filter icon" title="Manage Filters" }
<table style="width: 100%;" class="withborder">
 <tr>
  <td colspan="2" style="text-align: center;">
   <img src="{ $icon_new }" alt="new icon" />
   <a href="{$rewriter->get_page_url('Filter New')}">Create a new Filter</a>
  </td>
 </tr>
 <tr>
  <td colspan="2">&nbsp;</td>
 </tr>
 <tr>
  <td><img src="{ $icon_filters }" alt="filter icon" />&nbsp;<i>Filters</i></td> 
  <td style="text-align: center;"><i>Options</i></td>
 </tr>
 { filter_list }
 <tr onmouseover="setBackGrdColor(this, 'mouseover');" onmouseout="setBackGrdColor(this, 'mouseout');">
  <td>
   <img src="{ $icon_filters }" alt="filter icon" />
   <a href="{$rewriter->get_page_url('Filter Edit', $filter_idx)}">{ $filter_name }</a>
  </td>
  <td style="text-align: center;">
   <a class="delete" id="filter-{$filter_idx}" title="Delete"><img src="{ $icon_delete }" alt="filter icon" /></a>
   { if $filter_active == "Y" }
   <a href="javascript:toggleStatus('filter', 'filters', '{ $filter_idx }', '0');" title="Disable filter { $filter_name }"><img src="{ $icon_active }" alt="active icon" /></a>
   { else }
   <a href="javascript:toggleStatus('filter', 'filters', '{ $filter_idx }', '1');" title="Enable filter { $filter_name }"><img src="{ $icon_inactive }" alt="inactive icon" /></a>
   { /if }
  </td>
 </tr>
 { /filter_list }
</table>
