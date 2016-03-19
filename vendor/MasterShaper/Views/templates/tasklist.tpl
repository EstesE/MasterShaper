{start_table icon=$icon_process alt="host profile icon" title="Manage Tasks"}
<table style="width: 100%;" class="withborder"> 
 <tr>
  <td colspan="6">&nbsp;</td>
 </tr>
 <tr>
  <td style="text-align: center;" colspan="6">
   <a href="{$page->self}?clear=finished" title="removes finished or failed tasks from list">Clear tasklist&nbsp;<img src="{$icon_delete}" alt="delete icon" /></a>
  </td>
 </tr>
 <tr>
  <td colspan="6">&nbsp;</td>
 </tr>
 <tr>
  <td><img src="{$icon_process}" alt="host icon" />&nbsp;<i>ID</i></td>
  <td><img src="{$icon_process}" alt="host icon" />&nbsp;<i>Job</i></td>
  <td><img src="{$icon_process}" alt="host icon" />&nbsp;<i>Submit Time</i></td>
  <td><img src="{$icon_process}" alt="host icon" />&nbsp;<i>Execute Time</i></td>
  <td><img src="{$icon_process}" alt="host icon" />&nbsp;<i>State</i></td>
  <td style="text-align: center;"><i>Options</i></td>
 </tr>
 {task_list}
 <tr onmouseover="setBackGrdColor(this, 'mouseover');" onmouseout="setBackGrdColor(this, 'mouseout');">
  <td {if $task_state == "finish"} style="text-decoration: line-through" {/if}>
   <img src="{$icon_process}" alt="host icon" />
   {$task_idx}
  </td>
  <td {if $task_state == "finish"} style="text-decoration: line-through" {/if}>
   <img src="{$icon_process}" alt="host icon" />
   {$task_job}
  </td>
  <td>{$task_submit_time}</td>
  <td>{$task_run_time}</td>
  <td>{$task_state}</td>
  <td style="text-align: center;">
   <a class="delete" id="hosttask-{$task_idx}"><img src="{$icon_delete}" alt="delete icon" /></a>
  </td>
 </tr>
 {/task_list}
</table>
