<pre id="target"></pre>
<form action="{$page->uri}" id="netpaths" method="post">
<input type="hidden" name="module" value="networkpath" />
<input type="hidden" name="action" value="store" />
{if !$np->netpath_idx}
 {start_table icon=$icon_interfaces alt="network path icon" title="Create a new Network Path"}
 <input type="hidden" name="new" value="1" />
{else}
 {start_table icon=$icon_interfaces alt="network path icon" title="Modify network path `$np->netpath_name`"}
 <input type="hidden" name="new" value="0" />
 <input type="hidden" name="netpath_idx" value="{$np->netpath_idx}" />
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
   <input type="text" name="netpath_name" size="30" value="{$np->netpath_name}" />
  </td>
  <td>
   Specify a Network Path alias name (INET-LAN, INET-DMZ, ...).
  </td>
 </tr>
 <tr>
  <td>
   Status:
  </td>
  <td>
   <input type="radio" name="netpath_active" value="Y" {if $np->netpath_active == "Y"} checked="checked" {/if} />Enabled
   <input type="radio" name="netpath_active" value="N" {if $np->netpath_active != "Y"} checked="checked" {/if} />Disabled
  </td>
  <td>
   Enable or disable shaping on that Network path (on next ruleset reload).
  </td>
 </tr>
 <tr>
  <td colspan="3">
   <img src="{$icon_interfaces}" alt="interface icon" />
   Interfaces:
  </td>
 </tr>
 <tr>
  <td>
   Interface 1:
  </td>
  <td>
   <select name="netpath_if1">
   {if_select_list if_idx=$np->netpath_if1}
   </select>
   &nbsp;<input type="checkbox" name="netpath_if1_inside_gre" value="Y" {if $np->netpath_if1_inside_gre == "Y"} checked="checked" {/if} /><label onclick="obj_toggle_checkbox('[name=netpath_if1_inside_gre]');">&nbsp;іnside GRE-tunnel</label>
  </td>
  <td>
   First interface of this network path.
  </td>
 </tr>
 <tr>
  <td>
   Interface 2:
  </td>
  <td>
   <select name="netpath_if2">
   {if_select_list if_idx=$np->netpath_if2}
    <option value="-1" {if $np->netpath_if2 == -1} selected="selected" {/if}>--- not used ---</option>
   </select>
   &nbsp;<input type="checkbox" name="netpath_if2_inside_gre" value="Y" {if $np->netpath_if2_inside_gre == "Y"} checked="checked" {/if} /><label onclick="obj_toggle_checkbox('[name=netpath_if2_inside_gre]');">&nbsp;іnside GRE-tunnel</label>
  </td>
  <td>
   Second interface of this network path.
  </td>
 </tr>
 <tr>
  <td colspan="3">
   <img src="{$icon_interfaces}" />&nbsp;Options
  </td>
 </tr>
 <tr>
  <td style="white-space: nowrap;">IMQ:</td>
  <td>
   <input type="radio" name="netpath_imq" value="Y" {if $np->netpath_imq == "Y"} checked="checked" {/if} />Yes
   <input type="radio" name="netpath_imq" value="N" {if $np->netpath_imq != "Y"} checked="checked" {/if} />No
  </td>
  <td>
   Do you use IMQ (Intermediate Queuing Device) devices within this network path?
  </td>
 </tr>
 <tr>
  <td>Chains:</td>
  <td style="vertical-align: top;">
   <i>(Drag &amp; drop chains to change order.)</i><br />
   <table class="withborder2" id="chainlist">
    <thead>
     <tr>
      <td><img src="{$icon_chains}" alt="chain icon" />&nbsp;<i>Chain</i></td>
      <td><i>Status</i></td>
     </tr>
    </thead>
    <tbody id="chains">
    {chain_list}
     <tr id="chain{$chain->chain_idx}" {if $chain->chain_active != 'Y'} style="opacity: 0.5;" {/if} onmouseover="setBackGrdColor(this, 'mouseover');" onmouseout="setBackGrdColor(this, 'mouseout');">
      <td class="chain_dragger">
       <a href="{get_url page='Chain Edit' id=$chain->chain_idx}" title="Edit chain {$chain->chain_name}"><img src="{$icon_chains}" alt="chain icon" />&nbsp;{$chain->chain_name}</a>
      </td>
      <td style="text-align: center;">
       <input type="hidden" name="used[]" value="{$chain->chain_idx}" />
       <input type="hidden" id="chain-active-{$chain->chain_idx}" name="chain_active[{$chain->chain_idx}]" value="{$chain->apc_chain_idx}" />
       <div class="toggle" id="toggle-{$chain->chain_idx}" style="display: inline;">
        <a class="toggle-off" id="chain-{$chain->chain_idx}" to="off" title="Disable chain {$chain->chain_name}" {if $chain->chain_active != "Y"} style="display: none;" {/if} onclick="$('#chain-active-{$chain->chain_idx}').val('N'); $('table#chainlist tbody#chains tr#chain{$chain->chain_idx}').fadeTo(500, 0.50);"><img src="{$icon_active}" alt="active icon" /></a>
        <a class="toggle-on" id="chain-{$chain->chain_idx}" to="on" title="Enable chain {$chain->chain_name}" {if $chain->chain_active == "Y"} style="display: none;" {/if} onclick="$('#chain-active-{$chain->chain_idx}').val('Y'); $('table#chainlist tbody#chains tr#chain{$chain->chain_idx}').fadeTo(500, 1);"><img src="{$icon_inactive}" alt="inactive icon" /></a>
       </div>
      </td>
     </tr>
    {/chain_list}
     </tbody>
   </table>
  </td>
  <td>Select chains bound to this network path.</td>
 </tr>
 <tr>
  <td colspan="3">
   &nbsp;
  </td>
 </tr>
 <tr>
  <td style="text-align: center;"><a href="{get_url page='Network Paths List'}" title="Back"><img src="{$icon_arrow_left}" alt="arrow left icon" /></a></td>
  {include file="common_edit_save.tpl" newobj="Network Path"}
 </tr>
</table>
{literal}
<script language="JavaScript">
   $(function(){
      $("table#chainlist tbody#chains").sortable({
         accept:      'tbody#chain',
         greedy:      true,
         cursor:      'crosshair',
         placeholder: 'ui-state-highlight',
         delay:       250
      });
      $("table#chainlist tbody#chains").disableSelection();
      $('td.chain_dragger').hover(
         function() {
             $(this).css('cursor','crosshair');
         },
         function() {
             $(this).css('cursor','auto');
         }
      );
   });
</script>
{/literal}
{page_end focus_to='netpath_name'}
