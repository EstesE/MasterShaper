{start_table icon=$icon_filters alt="filter icon" title="Manage Filters" }
<table style="width: 100%;" class="withborder">
 <tr>
  <td colspan="2" style="text-align: center;">
   <img src="{ $icon_new }" alt="new icon" />
   <a href="javascript:refreshContent('filters', '&mode=new');">Create a new Filter</a>
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
   <a href="javascript:refreshContent('filters', '&mode=edit&idx={ $filter_idx }');">{ $filter_name }</a>
  </td>
  <td style="text-align: center;">
   <a href="javascript:deleteFilter('{ $filter_idx }');" title="Delete"><img src="{ $icon_delete }" alt="filter icon" /></a>
   { if $filter_active == "Y" }
   <a href="javascript:toggleFilterStatus('{ $filter_idx }', '0');" title="Disable filter { $filter_name }"><img src="{ $icon_active }" alt="filter icon" /></a>
   { else }
   <a href="javascript:toggleFilterStatus('{ $filter_idx }', '1');" title="Enable filter { $filter_name }"><img src="{ $icon_inactive }" alt="filter icon" /></a>
   { /if }
  </td>
 </tr>
 { /filter_list }
</table>
