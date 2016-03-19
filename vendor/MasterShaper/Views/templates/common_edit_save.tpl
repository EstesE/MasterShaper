  <td>
   <input type="submit" value="Save" />
{if isset($is_new) && $is_new}
   <input type="checkbox" value="Y" name="add_another" checked="checked" /><label onclick="obj_toggle_checkbox('[name=add_another]');">&nbsp;Add another {$newobj}</label>
{/if}
  </td>
  <td>Save settings.</td>
