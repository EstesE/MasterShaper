<pre id="target"></pre>
<form action="{$page->uri}" id="ports" method="post">
<input type="hidden" name="module" value="port" />
<input type="hidden" name="action" value="store" />
{if !isset($port->port_idx) || empty($port->port_idx)}
 {start_table icon=$icon_ports alt="port icon" title="Create a new Port"}
 <input type="hidden" name="new" value="1" />
{else}
 {start_table icon=$icon_ports alt="port icon" title="Modify Port `$port->port_name`"}
 <input type="hidden" name="new" value="0" />
 <input type="hidden" name="port_idx" value="{$port->port_idx}" />
{/if}
<table style="width: 100%" class="withborder">
 <tr>
  <td colspan="3">
   <img src="{$icon_ports}" alt="port icon" />&nbsp;General
  </td>
 </tr>
 <tr>
  <td>Name:</td>
  <td><input type="text" name="port_name" size="30" value="{$port->port_name}" /></td>
  <td>Name of the Port</td>
 </tr>
 <tr>
  <td>Description:</td>
  <td><input type="text" name="port_desc" size="30" value="{$port->port_desc}" /></td>
  <td>Short description of the port.</td>
 </tr>
 <tr>
  <td colspan="3">
   <img src="{$icon_ports}" alt="port icon" />&nbsp;Details
  </td>
 </tr>
 <tr>
  <td>Number:</td>
  <td>
   <input type="text" name="port_number" size="30" value="{$port->port_number}" />
  </td>
  <td>Add multiple port splitted with ',' or lists like 22-25</td>
 </tr>
 <tr> 
  <td colspan="3">&nbsp;</td>
 </tr>
 <tr>
  <td style="text-align: center;"><a href="{get_url page='Ports List'}" title="Back"><img src="{$icon_arrow_left}" alt="arrow left icon" /></a></td>
  {include file="common_edit_save.tpl" newobj="Port"}
 </tr>
</table>
</form>
<p class="footnote">
{if isset($filter_use_port) && !empty($filter_use_port)}
 This port is assigned to the following filters:<br />
 {foreach from=$filter_use_port key=filter_idx item=filter_name name=filters}
  <a href="{get_url page='Filter Edit' id=$filter_idx}" title="Edit filter {$filter_name}"><img src="{$icon_filters}" alt="filter icon" />&nbsp;{$filter_name}</a>{if !isset($smarty.foreach.filters.last) || empty($smarty.foreach.filters.last)},{/if}
 {foreachelse}
  none
 {/foreach}
{/if}
</p>
{page_end focus_to='port_name'}
