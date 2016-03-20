<pre id="target"></pre>
<form action="{$page->uri}" id="hostprofiles" method="post">
<input type="hidden" name="module" value="hostprofile" />
<input type="hidden" name="action" value="store" />
{if !$host->host_idx}
 {start_table icon=$icon_hosts alt="host profile icon" title="Create a new Host Profile"}
 <input type="hidden" name="new" value="1" />
{else}
 {start_table icon=$icon_hosts alt="host profile icon" title="Modify host profile `$host->host_name`"}
 <input type="hidden" name="new" value="0" />
 <input type="hidden" name="host_idx" value="{$host->host_idx}" />
{/if}
<table style="width: 100%;" class="withborder2">
 <tr>
  <td colspan="3">
   <img src="{$icon_hosts}" alt="host icon" />
   General
  </td>
 </tr>
 <tr>
  <td>
   Name:
  </td>
  <td>
   <input type="text" name="host_name" size="30" value="{$host->host_name}" />
  </td>
  <td>
   Specify a host profile name (shaper, gw, ...).
  </td>
 </tr>
 <tr>
  <td>
   Status:
  </td>
  <td>
   <input type="radio" name="host_active" value="Y" {if $host->host_active == "Y"} checked="checked" {/if} />Enabled
   <input type="radio" name="host_active" value="N" {if $host->host_active != "Y"} checked="checked" {/if} />Disabled
  </td>
  <td>
   Enable or disable host.
  </td>
 </tr>
 <tr>
  <td colspan="3">
   &nbsp;
  </td>
 </tr>
 <tr>
  <td style="text-align: center;"><a href="{get_url page='Host Profiles List'}" title="Back"><img src="{$icon_arrow_left}" alt="arrow left icon" /></a></td>
  {include file="common_edit_save.tpl" newobj="Host"}
 </tr>
</table>
{page_end focus_to='host_name'}
