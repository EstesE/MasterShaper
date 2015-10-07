<pre id="target"></pre>
<form action="{$page->uri}" id="interfaces" method="post">
<input type="hidden" name="module" value="interface" />
<input type="hidden" name="action" value="store" />
{if !isset($if->if_idx) || empty($if->if_idx)}
 {start_table icon=$icon_interfaces alt="interface icon" title="Create a new Interface"}
 <input type="hidden" name="new" value="1" />
{else}
 {start_table icon=$icon_interfaces alt="interface icon" title="Modify interface `$if->if_name`"}
 <input type="hidden" name="new" value="0" />
 <input type="hidden" name="if_idx" value="{$if->if_idx}" />
{/if} 
<table style="width: 100%;" class="withborder2">
 <tr>
  <td colspan="3">
   <img src="{$icon_interfaces}" alt="interface icon" />
   General
  </td>
 </tr>
 <tr>
  <td>
   Name:
  </td>
  <td>
   <input type="text" name="if_name" size="30" value="{$if->if_name}" />
  </td>
  <td>
   Specify the interface name (eth0, ppp0, imq0, ...).
  </td>
 </tr>
 <tr>
  <td>
   Status:
  </td>
  <td>
   <input type="radio" name="if_active" value="Y" {if $if->if_active == "Y"} checked="checked" {/if} />Enabled
   <input type="radio" name="if_active" value="N" {if $if->if_active != "Y"} checked="checked" {/if} />Disabled
  </td>
  <td>
   Enable or disable shaping on this interface.
  </td>
 </tr>
 <tr>
  <td colspan="3">
   <img src="{$icon_interfaces}" alt="interface icon" />
   Interface Details:
  </td>
 </tr>
 <tr>
  <td>
   Bandwidth:
  </td>
  <td>
   <input type="text" name="if_speed" size="30" value="{$if->if_speed}" />
  </td>
  <td>
   Specify the outbound bandwidth on this interface in bps (append K for kbps or M for Mbps).
  </td>
 </tr>
 <tr>
  <td colspan="3">
   <img src="{$icon_interfaces}" alt="interface icon" />
   Options:
  </td>
 </tr>
 <tr>
  <td style="white-space: nowrap;">Fallback:</td>
  <td style="white-space: nowrap;">
   <select name="if_fallback_idx">
    <option value="0" {if $if->if_fallback_idx == 0} selected="selected" {/if} >--- No Fallback ---</option>
    {service_level_select_list sl_idx=$if->if_fallback_idx}
   </select>
   <img class="change_to" src="{$icon_arrow_right}" value="Go" onclick="change_to('{$rewriter->get_page_url('Service Level Edit', 0)}', $('select[name=if_fallback_idx]').val());" />
  </td>
  <td>
   If none of the defined chains matches, you can define here a final fallback service level per interface.
  </td>
 </tr>
 <tr>
  <td>
   IFB:
  </td>
  <td>
   <input type="radio" name="if_ifb" value="Y" {if $if->if_ifb == "Y"} checked="checked" {/if} />Enabled
   <input type="radio" name="if_ifb" value="N" {if $if->if_ifb != "Y"} checked="checked" {/if} />Disabled
  </td>
  <td>
   This option enables IFB support on this interface. Make sure that IFB is compiled into your kernel or the proper kernel module is loaded!
  </td>
 </tr>
 <tr>
  <td colspan="3">
   &nbsp;
  </td>
 </tr>
 <tr>
  <td style="text-align: center;"><a href="{$rewriter->get_page_url('Interfaces List')}" title="Back"><img src="{$icon_arrow_left}" alt="arrow left icon" /></a></td>
  {include file="common_edit_save.tpl" newobj="Interface"}
 </tr>
</table>
<p class="footnote">
This interface is assigned to the following network paths:<br />
{if isset($np_use_if) && !empty($np_use_if)}
 {foreach from=$np_use_if key=np_idx item=np_name name=networkpaths}
  <a href="{$rewriter->get_page_url('Network Path Edit', $np_idx)}" title="Edit network path  $np_name}"><img src="{$icon_interfaces}" alt="interface icon" />&nbsp;{$np_name}</a>{if !isset($smarty.foreach.networkpaths.last) || empty($smarty.foreach.networkpaths.last)},{/if}
 {foreachelse}
  none
 {/foreach}
{/if}
</p>
{page_end focus_to='if_name'}
