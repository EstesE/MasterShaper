<pre id="target"></pre>
<form action="{$page->uri}" id="protocols" method="post">
<input type="hidden" name="module" value="protocol" />
<input type="hidden" name="action" value="store" />
{if !isset($protocol->proto_idx) || empty($protocol->proto_idx)}
 {start_table icon=$icon_protocols alt="protocol icon" title="Create a new Protocol"}
 <input type="hidden" name="new" value="1" />
{else}
 {start_table icon=$icon_protocols alt="protocol icon" title="Modify Protocol `$protocol->proto_name`"}
 <input type="hidden" name="new" value="0" />
 <input type="hidden" name="proto_idx" value="{$protocol->proto_idx}" />
{/if}
<table style="width: 100%" class="withborder">
 <tr>
  <td colspan="3">
   <img src="{$icon_protocols}" alt="protocol icon" />&nbsp;General
  </td>
 </tr>
 <tr>
  <td>Name:</td>
  <td><input type="text" name="proto_name" size="30" value="{$protocol->proto_name}" /></td>
  <td>Name of the Protocol</td>
 </tr>
 <tr>
  <td colspan="3">
   <img src="{$icon_protocols}" alt="protocol icon" />&nbsp;Details
  </td>
 </tr>
 <tr>
  <td>Number:</td>
  <td>
   <input type="text" name="proto_number" size="30" value="{$protocol->proto_number}" />
  </td>
  <td>The IANA protocol number.</td>
 </tr>
 <tr> 
  <td colspan="3">&nbsp;</td>
 </tr>
 <tr>
  <td style="text-align: center;"><a href="{get_page_url page='Protocols List'}" title="Back"><img src="{$icon_arrow_left}" alt="arrow left icon" /></a></td>
  {include file="common_edit_save.tpl" newobj="Protocol"}
 </tr>
</table>
</form>
<p class="footnote">
{if isset($filter_use_protocol) && !empty($filter_use_protocol)}
 This protocol is assigned to the following filters:<br />
 {foreach from=$filter_use_protocol key=filter_idx item=filter_name name=filters}
  <a href="{get_page_url page='Filter Edit' id=$filter_idx}" title="Edit filter {$filter_name}"><img src="{$icon_filters}" alt="filter icon" />&nbsp;{$filter_name}</a>{if !isset($smarty.foreach.filters.last) || empty($smarty.foreach.filters.last)},{/if}
 {foreachelse}
  none
 {/foreach}
{/if}
</p>
{page_end focus_to='proto_name'}
