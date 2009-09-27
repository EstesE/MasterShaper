{start_table icon=$icon_interfaces alt="network path icon" title="Manage Network Paths" }
<table style="width: 100%;" class="withborder"> 
 <tr>
  <td style="text-align: center;" colspan="4">
   <img src="{ $icon_new }" alt="new icon" />
   <a href="javascript:refreshContent('networkpaths', '&mode=new');">Define a new Network Path</a>
  </td>
 </tr>
 <tr>
  <td colspan="4">&nbsp;</td>
 </tr>
 <tr>
  <td><img src="{ $icon_interfaces }" alt="interface icon" />&nbsp;<i>Path</i></td>
  <td><img src="{ $icon_interfaces }" alt="interface icon" />&nbsp;<i>Interface 1</i></td>
  <td><img src="{ $icon_interfaces }" alt="interface icon" />&nbsp;<i>Interface 2</i></td>
  <td style="text-align: center;"><i>Options</i></td>
 </tr>
 { netpath_list }
 <tr onmouseover="setBackGrdColor(this, 'mouseover');" onmouseout="setBackGrdColor(this, 'mouseout');">
  <td>
   <img src="{ $icon_interfaces }" alt="interface icon" />
   <a href="javaScript:refreshContent('networkpaths', '&mode=edit&idx={ $netpath_idx }');">{ $netpath_name }</a>
  </td>
  <td>
   { $netpath_if1 } { if $netpath_if1_inside_gre == 'Y' }(inside GRE){ /if }
  </td>
  <td>
   { $netpath_if2 } { if $netpath_if2_inside_gre == 'Y' }(inside GRE){ /if }
  </td>
  <td style="text-align: center;">
   <a href="javascript:deleteObj('networkpath', 'networkpaths', '{ $netpath_idx }');"><img src="{ $icon_delete }" alt="delete icon" /></a>
   { if $netpath_active == 'Y' }
   <a href="javascript:toggleStatus('networkpath', 'networkpaths', '{ $netpath_idx }', '0');"><img src="{ $icon_active }" alt="active icon" /></a>
   { else }
   <a href="javascript:toggleStatus('networkpath', 'networkpaths', '{ $netpath_idx }', '1');"><img src="{ $icon_inactive }" alt="inactive icon" /></a>
   { /if }
  </td>
 </tr>
 { /netpath_list }
</table>
