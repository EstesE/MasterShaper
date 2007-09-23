{start_table icon=$icon_targets alt="target icon" title="Manage Targets" }
<table style="width: 100%;" class="withborder">
 <tr>
  <td colspan="3">&nbsp;</td>
 </tr>
 <tr>
  <td colspan="3" style="text-align: center;">
   <img src="{ $icon_new }" alt="new icon" />
   <a href="javascript:refreshContent('targets', '&mode=new');">Create a new Target</a>
  </td>
 </tr>
 <tr>
  <td colspan="3">&nbsp;</td>
 </tr>
 <tr>
  <td><img src="{ $icon_targets }" alt="target icon" />&nbsp;<i>Targets</i></td>
  <td><img src="{ $icon_targets }" alt="target icon" />&nbsp;<i>Details</i></td>
  <td style="text-align: center;"><i>Options</i></td>
 </tr>
 { target_list }
 <tr onmouseover="setBackGrdColor(this, 'mouseover');" onmouseout="setBackGrdColor(this, 'mouseout');">
  <td>
   <img src="{ $icon_targets }" alt="target icon" />
   <a href="javascript:refreshContent('targets', '&mode=edit&idx={ $target_idx }');">{ $target_name }</a>
  </td>
  <td>
   <img src="{ $icon_targets }" alt="target icon" />
   { $target_type }
  </td>
  <td style="text-align: center;">
   <a href="javascript:refreshContent('deletetarget', '{ $target_idx }');" title="Delete"><img src="{ $icon_delete }" alt="delete icon" /></a>
  </td>
 </tr>
{ /target_list }
