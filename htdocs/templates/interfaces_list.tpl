{start_table icon=$icon_interfaces alt="interface icon" title="Manage Interfaces" }
<table style="width: 100%;" class="withborder"> 
 <tr>
  <td style="text-align: center;" colspan="4">
   <img src="{ $icon_new }" alt="new icon" />
   <a href="javascript:refreshContent('interfaces', '&mode=new');">Add a new Interface</a>
  </td>
 </tr>
 <tr>
  <td colspan="4">&nbsp;</td>
 </tr>
 <tr>
  <td><img src="{ $icon_interfaces }" alt="interface icon" />&nbsp;<i>Interface</i></td>
  <td><img src="{ $icon_interfaces }" alt="interface icon" />&nbsp;<i>Bandwidth</i></td>
  <td style="text-align: center;"><i>Options</i></td>
 </tr>
 { interface_list }
 <tr onmouseover="setBackGrdColor(this, 'mouseover');" onmouseout="setBackGrdColor(this, 'mouseout');">
  <td>
   <img src="{ $icon_interfaces }" alt="interface icon" />
   <a href="javaScript:refreshContent('interfaces', '&mode=edit&idx={ $if_idx }');">{ $if_name }</a>
  </td>
  <td>
   { $if_speed }
  </td>
  <td style="text-align: center;">
   <a href="javascript:deleteObj('interface', 'interfaces', '{ $if_idx }');"><img src="{ $icon_delete }" alt="delete icon" /></a>
   { if $if_active == 'Y' }
   <a href="javascript:toggleInterfaceStatus('{ $if_idx }', '0');"><img src="{ $icon_active }" alt="active icon" /></a>
   { else }
   <a href="javascript:toggleInterfaceStatus('{ $if_idx }', '1');"><img src="{ $icon_inactive }" alt="active icon" /></a>
   { /if }
  </td>
 </tr>
 { /interface_list }
</table>
