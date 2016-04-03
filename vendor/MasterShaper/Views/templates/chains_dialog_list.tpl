{*
 * This file is part of MasterShaper.

 * MasterShaper, a web application to handle Linux's traffic shaping
 * Copyright (C) 2007-2016 Andreas Unterkircher <unki@netshadow.net>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.

 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
*}
<form action="{get_url page='pipes' mode='edit' id=$item->getSafeLink()}" id="chains" method="POST">
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
