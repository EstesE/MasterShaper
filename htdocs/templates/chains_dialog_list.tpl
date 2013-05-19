<form action="{$rewriter->get_page_url('Pipe Edit', $pipe_idx)}" id="chains" method="POST">
<input type="hidden" name="module" value="chain" />
<input type="hidden" name="action" value="store" />
<input type="hidden" name="assign-pipe" value="true" />
<table style="width: 100%;" class="withborder">
 {chain_dialog_list}
 <tr onmouseover="setBackGrdColor(this, 'mouseover');" onmouseout="setBackGrdColor(this, 'mouseout');">
  <td>
   <input id="checkbox-{$chain_idx}" type="checkbox" name="chains[]" value="{$chain_idx}" {if isset($chain_used) && $chain_used} checked="checked" {/if} />
  </td>
  <td onclick="obj_toggle_checkbox('#checkbox-{$chain_idx}');">
   {$chain_name}&nbsp;{if $chain_active != 'Y'}(inactive){/if}
  </td>
 </tr>
 {/chain_dialog_list}
</table>
<input type="submit" value="Assign" />
<input type="button" value="Cancel" onclick="$('#dialog').dialog('close');" />
</form>
