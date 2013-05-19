{start_table icon=$icon_servicelevels alt="service level icon" title="Manage Service Levels"}
<table style="width: 100%;" class="withborder">
 <tr>
  <td colspan="3" style="text-align: center;">
   <img src="{$icon_new}" alt="new icon" />
   <a href="{$rewriter->get_page_url('Service Level New')}">Create a new Service Level</a>
  </td>
 </tr>
 <tr>
  <td colspan="3">&nbsp;</td>
 </tr>
 <tr>
  <td><img src="{$icon_servicelevels}" alt="servicelevel icon" />&nbsp;<i>Service Levels</i></td>
  <td><img src="{$icon_servicelevels}" alt="servicelevel icon" />&nbsp;<i>Qdisc Parameters</i></td>
  <td style="text-align: center;"><i>Options</i></td>
 </tr>
 {service_level_list}
 <tr onmouseover="setBackGrdColor(this, 'mouseover');" onmouseout="setBackGrdColor(this, 'mouseout');">
  <td>
   <img src="{$icon_servicelevels}" alt="servicelevel icon" />
   <a href="{$rewriter->get_page_url('Service Level Edit', $sl_idx)}">{$sl_name}</a>
  </td>
  <td>
   {$classifier}
   <img src="{$icon_servicelevels}" alt="servicelevel icon" />
   {if $classifier == "HTB"}
    {if $sl_htb_bw_in_rate != ""}
     In: {$sl_htb_bw_in_rate}kbps
    {/if}
    {if $sl_htb_bw_out_rate != ""}
     Out: {$sl_htb_bw_out_rate}kbps
    {/if}
    Prio: {$sl_htb_priority}
   {elseif $classifier == "HFSC"}
    {if $sl_hfsc_in_dmax != "" || $sl_hfsc_in_rate != ""}
     In: 
     {if $sl_hfsc_in_dmax != ""}
      {$sl_hfsc_in_dmax}ms,
     {/if}
     {if $sl_hfsc_in_rate != ""}
      {$sl_hfsc_in_rate}kbps
     {/if}
    {/if}
    {if $sl_hfsc_out_dmax != "" || $sl_hfsc_out_rate != ""}
	  Out:
     {if $sl_hfsc_out_dmax != ""}
      {$sl_hfsc_out_dmax}ms,
     {/if}
     {if $sl_hfsc_out_rate != ""}
      {$sl_hfsc_out_rate}kbps
     {/if}
    {/if}
   {elseif $classifier == "CBQ"}
    In: {$sl_cbq_in_rate}kbps, Prio: {$sl_cbq_in_priority},
    Out: {$sl_cbq_out_rate}kbps, Prio: {$sl_cbq_out_priority}
   {elseif $classifier == "NETEM"} 
    NETEM
   {/if}
  </td>
  <td style="text-align: center;">
   <a class="clone" id="servicelevel-{$sl_idx}" title="Clone"><img src="{$icon_clone}" alt="clone icon" /></a>
   <a class="delete" id="servicelevel-{$sl_idx}" title="Delete"><img src="{$icon_delete}" alt="delete icon" /></a>
  </td>
 </tr>
 {/service_level_list}
</table>
