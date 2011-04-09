<pre id="target"></pre>
<form action="{$page->uri}" id="servicelevels" method="post">
<input type="hidden" name="module" value="servicelevel" />
<input type="hidden" name="action" value="store" />
{ if ! $sl->sl_idx }
 {start_table icon=$icon_servicelevels alt="service level icon" title="Create a new service level" }
 <input type="hidden" name="new" value="1" />
{ else }
 {start_table icon=$icon_servicelevels alt="service level icon" title="Modify service level `$sl->sl_name`" }
 <input type="hidden" name="new" value="0" />
 <input type="hidden" name="sl_idx" value="{ $sl->sl_idx }" />
{ /if }
<table style="width: 100%;" class="withborder2">
 <tr>
  <td colspan="3">
   <img src="{ $icon_servicelevels }" alt="servicelevel icon" />&nbsp;General
  </td>
 </tr>
 <tr>
  <td>Name:</td>
  <td><input type="text" name="sl_name" size="30" value="{ $sl->sl_name }" /></td>
  <td>Name of the service level.</td>
 </tr>
 <tr>
  <td>Classifier:</td>
  <td>
   <select name="classifier" onchange="refreshContent('servicelevels', '&mode=edit&idx={ $sl->sl_idx }&classifier='+(document.forms['servicelevels'].classifier.options[document.forms['servicelevels'].classifier.selectedIndex].value)+'&qdiscmode='+(document.forms['servicelevels'].sl_qdisc.options[document.forms['servicelevels'].sl_qdisc.selectedIndex].value));">
    <option value="HTB"  { if $classifier == "HTB"  } selected="selected" { /if }>HTB</option>
    <option value="HFSC" { if $classifier == "HFSC" } selected="selected" { /if }>HFSC</option>
    <option value="CBQ"  { if $classifier == "CBQ"  } selected="selected" { /if }>CBQ</option>
   </select>
  </td>
  <td>
   Save your service level settings first before you change the classifier.
  </td>
 </tr>
 { if $classifier == "HTB" }
 <tr>
  <td colspan="3">
   <img src="{ $icon_servicelevels }" alt="servicelevel icon" />&nbsp;Interface 1 -&gt; Interface 2
  </td>
 </tr>
 <tr>
  <td style="white-space: nowrap;">Bandwidth</td>
  <td style="white-space: nowrap;"><input type="text" name="sl_htb_bw_in_rate" size="25" value="{ $sl->sl_htb_bw_in_rate }" />&nbsp;kbit/s</td>
  <td>Bandwidth rate. This is the guaranteed bandwidth.</td>
 </tr>
 <tr>
  <td style="white-space: nowrap;">Bandwidth ceil:</td>
  <td style="white-space: nowrap;"><input type="text" name="sl_htb_bw_in_ceil" size="25" value="{ $sl->sl_htb_bw_in_ceil }" />&nbsp;kbit/s</td>
  <td>If the chain has bandwidth to spare, this is the maximum rate which can be lend to this service. The default value is the bandwidth rate which implies no borrowing from the chain.</td>
 </tr>
 <tr>
  <td style="white-space: nowrap;">Bandwidth burst:</td>
  <td style="white-space: nowrap;"><input type="text" name="sl_htb_bw_in_burst" size="25" value="{ $sl->sl_htb_bw_in_burst }" />&nbsp;kbit/s</td>
  <td>Amount of kbit/s that can be burst at ceil speed, in excess of the configured rate. Should be at least as high as the highest burst of all children. This is useful for interactive traffic.</td>
 </tr>
 <tr>
  <td colspan="3">
    <img src="{ $icon_servicelevels }" alt="servicelevel icon" />&nbsp;Interface 2 -&gt; Interface 1
  </td>
 </tr>
 <tr>
  <td style="white-space: nowrap;">Bandwidth:</td>
  <td style="white-space: nowrap;"><input type="text" name="sl_htb_bw_out_rate" size="25" value="{ $sl->sl_htb_bw_out_rate }" />&nbsp;kbit/s</td>
  <td>Bandwidth rate. This is the guaranteed bandwidth.</td>
 </tr>
 <tr>
  <td style="white-space: nowrap;">Bandwidth ceil:</td>
  <td style="white-space: nowrap;"><input type="text" name="sl_htb_bw_out_ceil" size="25" value="{ $sl->sl_htb_bw_out_ceil }" />&nbsp;kbit/s</td>
  <td>If the chain has bandwidth to spare, this is the maximum rate which can be lend to this service. The default value is the bandwidth rate which implies no borrowing from the chain.</td>
 </tr>
 <tr>
  <td style="white-space: nowrap;">Bandwidth burst:</td>
  <td style="white-space: nowrap;"><input type="text" name="sl_htb_bw_out_burst" size="25" value="{ $sl->sl_htb_bw_out_burst }" />&nbsp;kbit/s</td>
  <td>Amount of kbit/s that can be burst at ceil speed, in excess of the configured rate. Should be at least as high as the highest burst of all children. This is useful for interactive traffic.</td>
 </tr>
 <tr>
  <td colspan="3">
   <img src="{ $icon_servicelevels }" alt="servicelevel icon" />&nbsp;Parameters
  </td>
 </tr>
 <tr>
  <td>Priority:</td>
  <td>
   <select name="sl_htb_priority">
    <option value="1" { if $sl->sl_htb_priority == 1 } selected="selected" { /if }>Highest (1)</option>
    <option value="2" { if $sl->sl_htb_priority == 2 } selected="selected" { /if }>High (2)</option>
    <option value="3" { if $sl->sl_htb_priority == 3 } selected="selected" { /if }>Normal (3)</option>
    <option value="4" { if $sl->sl_htb_priority == 4 } selected="selected" { /if }>Low (4)</option>
    <option value="5" { if $sl->sl_htb_priority == 5 } selected="selected" { /if }>Lowest (5)</option>
    <option value="0" { if $sl->sl_htb_priority == 0 } selected="selected" { /if }>Ignore</option>
   </select>
  </td>
  <td>The service levels with a higher priority are favoured by the scheduler. Also pipes with service levels with a higher priority can lean more unused bandwidth from their chains. If priority is specified without in- or outbound rate, the maximum interface bandwidth can be used.</td>
 </tr>
 { elseif $classifier == "HFSC" }
 <tr>
  <td colspan="3">
   <img src="{ $icon_servicelevels }" alt="servicelevel icon" />&nbsp;Interface 1 -&gt; Interface 2
  </td>
 </tr>
 <tr>
  <td style="white-space: nowrap;">Work-Unit:</td>
  <td style="white-space: nowrap;"><input type="text" name="sl_hfsc_in_umax" size="25" value="{ $sl->sl_hfsc_in_umax }" />&nbsp;bytes</td>
  <td>Maximum unit of work. A value around your MTU (ex. 1500) is a good value.</td>
 </tr>
 <tr>
  <td style="white-space: nowrap;">Max-Delay:</td>
  <td style="white-space: nowrap;"><input type="text" name="sl_hfsc_in_dmax" size="25" value="{ $sl->sl_hfsc_in_dmax }" />&nbsp;ms</td>
  <td>Maximum delay of a packet within this Qdisc in milliseconds (ms)</td>
 </tr>
 <tr>
  <td style="white-space: nowrap;">Rate:</td>
  <td style="white-space: nowrap;"><input type="text" name="sl_hfsc_in_rate" size="25" value="{ $sl->sl_hfsc_in_rate }" />&nbsp;kbit/s</td>
  <td>Guaranteed rate of bandwidth in kbit/s</td>
 </tr>
 <tr>
  <td style="white-space: nowrap;">ul-Rate:</td>
  <td style="white-space: nowrap;"><input type="text" name="sl_hfsc_in_ulrate" size="25" value="{ $sl->sl_hfsc_in_ulrate }" />&nbsp;kbit/s</td>
  <td>Maximum rate of bandwidth in kbit/s</td>
 </tr>
 <tr>
  <td colspan="3">
   <img src="{ $icon_servicelevels }" alt="servicelevel icon" />&nbsp;Interface 2 -&gt; Interface 1
  </td>
 </tr>
 <tr>
  <td style="white-space: nowrap;">Work-Unit:</td>
  <td style="white-space: nowrap;"><input type="text" name="sl_hfsc_out_umax" size="25" value="{ $sl->sl_hfsc_out_umax }" />&nbsp;bytes</td>
  <td>Maximum unit of work. A value around your MTU (ex. 1500) is a good value.</td>
 </tr>
 <tr>
  <td style="white-space: nowrap;">Max-Delay:</td>
  <td style="white-space: nowrap;"><input type="text" name="sl_hfsc_out_dmax" size="25" value="{ $sl->sl_hfsc_out_dmax }" />&nbsp;ms</td>
  <td>Maximum delay of a packet within this Qdisc in milliseconds (ms)</td>
 </tr>
 <tr>
  <td style="white-space: nowrap;">Rate:</td>
  <td style="white-space: nowrap;"><input type="text" name="sl_hfsc_out_rate" size="25" value="{ $sl->sl_hfsc_out_rate }" />&nbsp;kbit/s</td>
  <td>Guaranteed rate of bandwidth in kbit/s</td>
 </tr>
 <tr>
  <td style="white-space: nowrap;">ul-Rate:</td>
  <td style="white-space: nowrap;"><input type="text" name="sl_hfsc_out_ulrate" size="25" value="{ $sl->sl_hfsc_out_ulrate }" />&nbsp;kbit/s</td>
  <td>Maximum rate of bandwidth in kbit/s</td>
 </tr>
 { elseif $classifier == "CBQ" }
 <tr>
  <td>Bounded:</td>
  <td>
   <input type="radio" name="sl_cbq_bounded" value="Y" { if $sl->sl_cbq_bounded == "Y" } checked="checked" { /if } />Yes
   <input type="radio" name="sl_cbq_bounded" value="N" { if $sl->sl_cbq_bounded != "Y" } checked="checked" { /if } />No
  </td>
  <td>
   If the CBQ class is bounded, it will not borrow unused bandwidth from it parent classes. If disabled the maximum rates are probably not enforced.
  </td>
 </tr> 
 <tr>
  <td colspan="3">
   <img src="{ $icon_servicelevels }" alt="servicelevel icon" />&nbsp;Interface 1 -&gt; Interface 2
  </td>
 </tr>
 <tr>
  <td style="white-space: nowrap;">Bandwidth:</td>
  <td style="white-space: nowrap;"><input type="text" name="sl_cbq_in_rate" size="25" value="{ $sl->sl_cbq_in_rate }" />&nbsp;kbit/s</td>
  <td>Maximum rate a chain or pipe can send at.</td>
 </tr>
 <tr>
  <td style="white-space: nowrap;">Priority:</td>
  <td style="white-space: nowrap;">
   <select name="sl_cbq_in_priority">
    <option value="1" { if $sl->sl_cbq_in_priority == 1 } selected="selected"; { /if }>Highest (1)</option>
    <option value="2" { if $sl->sl_cbq_in_priority == 2 } selected="selected"; { /if }>High (2)</option>
    <option value="3" { if $sl->sl_cbq_in_priority == 3 } selected="selected"; { /if }>Normal (3)</option>
    <option value="4" { if $sl->sl_cbq_in_priority == 4 } selected="selected"; { /if }>Low (4)</option>
    <option value="5" { if $sl->sl_cbq_in_priority == 5 } selected="selected"; { /if }>Lowest (5)</option>
   </select>
  </td>
  <td>In the round-robin process, classes with the lowest priority field are tried for packets first.</td>
 </tr>
 <tr>
  <td colspan="3">
   <img src="{ $icon_servicelevels }" alt="servicelevel icon" />&nbsp;Interface 2 -&gt; Interface 1
  </td>
 </tr>
 <tr>
  <td style="white-space: nowrap;">Bandwidth:</td>
  <td style="white-space: nowrap;"><input type="text" name="sl_cbq_out_rate" size="25" value="{ $sl->sl_cbq_out_rate }" />&nbsp;kbit/s</td>
  <td>Maximum rate a chain or pipe can send at.</td>
 </tr>
 <tr>
  <td style="white-space: nowrap;">Priority:</td>
  <td style="white-space: nowrap;">
   <select name="sl_cbq_out_priority">
    <option value="1" { if $sl->sl_cbq_out_priority == 1 } selected="selected"; { /if }>Highest (1)</option>
    <option value="2" { if $sl->sl_cbq_out_priority == 2 } selected="selected"; { /if }>High (2)</option>
    <option value="3" { if $sl->sl_cbq_out_priority == 3 } selected="selected"; { /if }>Normal (3)</option>
    <option value="4" { if $sl->sl_cbq_out_priority == 4 } selected="selected"; { /if }>Low (4)</option>
    <option value="5" { if $sl->sl_cbq_out_priority == 5 } selected="selected"; { /if }>Lowest (5)</option>
   </select>
  </td>
  <td>In the round-robin process, classes with the lowest priority field are tried for packets first.</td>
 </tr>
 { /if }
 <tr>
  <td colspan="3">
   <img src="{ $icon_servicelevels }" alt="servicelevel icon" />&nbsp;Queuing Discipline
  </td>
 </tr>
 <tr>
  <td>
   Queuing Discipline:
  </td>
  <td>
   <select name="sl_qdisc"  onchange="refreshContent('servicelevels', '&mode=edit&idx={ $sl->sl_idx }&qdiscmode='+(document.forms['servicelevels'].sl_qdisc.options[document.forms['servicelevels'].sl_qdisc.selectedIndex].value)+'&classifier='+(document.forms['servicelevels'].classifier.options[document.forms['servicelevels'].classifier.selectedIndex].value));">
    <option value="SFQ" { if $qdiscmode == "SFQ" } selected="selected" { /if }>SFQ</option>
    <option value="ESFQ" { if $qdiscmode == "ESFQ" } selected="selected" { /if }>ESFQ</option>
    <option value="HFSC" { if $qdiscmode == "HFSC" } selected="selected" { /if }>HFSC</option>
    <option value="NETEM" { if $qdiscmode == "NETEM" } selected="selected" { /if }>NETEM</option>
   </select>
  </td>
  <td>
   Select the to be used Queuing Discipline.
  </td>
 </tr>
 { if $qdiscmode == "ESFQ" }
 <tr>
  <td>
   Perturb:
  </td>
  <td>
   <input type="text" name="sl_esfq_perturb" size="25" value="{ $sl->sl_esfq_perturb }" />
  </td>
  <td>
   Causes the flows to be redistributed so there are no collosions on sharing a queue. Default is 0. Recommeded 10.
  </td> 
 </tr>
 <tr>
  <td>
   Limit:
  </td>
  <td>
   <input type="text" name="sl_esfq_limit" size="25" value="{ $sl->sl_esfq_limit }" />
  </td>
  <td>
   The total number of packets that will be queued by this ESFQ before packets start getting dropped.  Limit must be less than or equal to depth. Default is 128.
  </td>
 </tr>
 <tr>
  <td>
   Depth:
  </td>
  <td>
   <input type="text" name="sl_esfq_depth" size="25" value="{ $sl->sl_esfq_depth }" />
  </td>
  <td>
   No description available. Set like Limit.
  </td>
 </tr>
 <tr>
  <td>
   Divisor:
  </td>
  <td>
   <input type="text" name="sl_esfq_divisor" size="25" value="{ $sl->sl_esfq_divisor }" />
  </td>
  <td>
   Divisor sets the number of bits to use for the hash table. A larger hash table decreases the likelihood of collisions but will consume more memory.
  </td>
 </tr>
 <tr>
  <td>
   Hash:
  </td>
  <td>
   <select name="sl_esfq_hash">
    <option value="classic" { if $sl->sl_esfq_hash == "classic" } selected="selected"; { /if }>Classic</option>
    <option value="src" { if $sl->sl_esfq_hash == "src" } selected="selected"; { /if }>Src</option>
    <option value="dst" { if $sl->sl_esfq_hash == "dst" } selected="selected"; { /if }>Dst</option>
    <option value="fwmark" { if $sl->sl_esfq_hash == "fwmark" } selected="selected"; { /if }>Fwmark</option>
    <option value="src_direct" { if $sl->sl_esfq_hash == "src_direct" } selected="selected"; { /if }>Src_direct</option>
    <option value="dst_direct" { if $sl->sl_esfq_hash == "dst_direct" } selected="selected"; { /if }>Dst_direct</option>
    <option value="fwmark_direct" { if $sl->sl_esfq_hash == "fwmark_direct" } selected="selected"; { /if }>Fwmark_direct</option>
   </select>
  </td>
  <td>
   Howto seperate traffic into queues. Classisc equals to SFQ handling. Src and Dst per direction. Fwmark uses the connection mark which can be set by iptables. If less then 16384 (2^14) simultaneous connections occurs use one of the _direct sibling which uses an fast algorithm.
  </td>
 </tr>
 { elseif $qdiscmode == "NETEM" }
 <tr>
  <td colspan="3">
   <img src="{ $icon_servicelevels }" alt="servicelevel icon" />&nbsp;Network delays
  </td>
 </tr>
 <tr>
  <td style="white-space: nowrap;">Delay:</td>
  <td style="white-space: nowrap;"><input type="text" name="sl_netem_delay" size="25" value="{ $sl->sl_netem_delay }" />&nbsp;ms</td>
  <td>Fixed amount of delay to all packets.</td>
 </tr>
 <tr>
  <td style="white-space: nowrap;">Jitter:</td>
  <td style="white-space: nowrap;"><input type="text" name="sl_netem_jitter" size="25" value="{ $sl->sl_netem_jitter }" />&nbsp;ms</td>
  <td>Random variation around the delay value (= delay &#177; Jitter).
 </tr>
 <tr>
  <td style="white-space: nowrap;">Correlation:</td>
  <td style="white-space: nowrap;"><input type="text" name="sl_netem_random" size="25" value="{ $sl->sl_netem_random }" />&nbsp;&#37;</td>
  <td>Limits the randomness to simulate a real network. So the next packets delay will be within % of the delay of the packet before.</td>
 </tr>
 <tr>
  <td style="white-space: nowrap;">Distribution:</td>
  <td style="white-space: nowrap;">
   <select name="sl_netem_distribution">
    <option value="ignore" { if $sl->sl_netem_distribution == "ignore" } selected="selected"; { /if }>Ignore</option>
    <option value="normal" { if $sl->sl_netem_distribution == "normal" } selected="selected"; { /if }>normal</option>
    <option value="pareto" { if $sl->sl_netem_distribution == "pareto" } selected="selected"; { /if }>pareto</option>
    <option value="paretonormal" { if $sl->sl_netem_distribution == "paretonormal" } selected="selected"; { /if }>paretonormal</option>
   </select>
  </td>
  <td>How the delays are distributed over a longer delay periode.</td>
 </tr>
 <tr>
  <td colspan="3">
   <img src="{ $icon_servicelevels }" alt="servicelevel icon" />&nbsp;Others functions
  </td>
 </tr>
 <tr>
  <td style="white-space: nowrap;">Packetloss:</td>
  <td style="white-space: nowrap;"><input type="text" name="sl_netem_loss" size="25" value="{ $sl->sl_netem_loss }" />&nbsp;&#37;</td>
  <td>Packetloss in percent. Smallest value is .0000000232% ( = 1 / 2^32).
 </tr>
 <tr>
  <td style="white-space: nowrap;">Duplication:</td>
  <td style="white-space: nowrap;"><input type="text" name="sl_netem_duplication" size="25" value="{ $sl->sl_netem_duplication }" />&nbsp;&#37;</td>
  <td>Duplication in percent. Smallest value is .0000000232% ( = 1 / 2^32).
 </tr>
 <tr>
  <td colspan="3">
   <img src="{ $icon_servicelevels }" alt="servicelevel icon" />&nbsp;Re-Ordering
  </td>
 </tr>
 <tr>
  <td style="white-space: nowrap;">Gap:</td>
  <td style="white-space: nowrap;"><input type="text" name="sl_netem_gap" size="25" value="{ $sl->sl_netem_gap }" /></td>
  <td>Packet re-ordering causes 1 out of N packets to be delayed. For a value of 5 every 5th (10th, 15th, ...) packet will get delayed by 10ms and the others will pass straight out.</td>
 </tr>
 <tr>
 <tr>
  <td style="white-space: nowrap;">Reorder percentage:</td>
  <td style="white-space: nowrap;"><input type="text" name="sl_netem_reorder_percentage" size="25" value="{ $sl->sl_netem_reorder_percentage }" />&nbsp;&#37;</td>
  <td>Percentage of packets the get reordered.</td>
 </tr>
 <tr>
  <td style="white-space: nowrap;">Reorder correlation:</td>
  <td style="white-space: nowrap;"><input type="text" name="sl_netem_reorder_correlation" size="25" value="{ $sl->sl_netem_reorder_correlation }" />&nbsp;&#37;</td>
  <td>Percentage of packets the are correlate each others.</td>
 </tr>
 { /if }
 <tr>
  <td colspan="3">&nbsp;</td>
 </tr>
 <tr>
  <td style="text-align: center;"><a href="{$rewriter->get_page_url('Service Levels List')}" title="Back"><img src="{ $icon_arrow_left }" alt="arrow left icon" /></a></td>
  <td><input type="submit" value="Save" /></td>
  <td>Save settings</td>
 </tr>
</table> 
</form>
<p class="footnote">
This target is assigned to the following objects:<br />
{ foreach from=$obj_use_target key=obj_idx item=obj name=objects }
 { if $obj->type == 'pipe' }
  <a href="{$rewriter->get_page_url('Pipe Edit', $obj->idx)}" title="Edit pipe { $obj->name }"><img src="{$icon_pipes}" alt="pipe icon" />&nbsp;{ $obj->name }</a>{ if ! $smarty.foreach.filters.last},{/if}
 { /if }
 { if $obj->type == 'chain' }
  <a href="{$rewriter->get_page_url('Chain Edit', $obj->idx)}" title="Edit chain { $obj->name }"><img src="{$icon_chains}" alt="chain icon" />&nbsp;{ $obj->name }</a>{ if ! $smarty.foreach.filters.last},{/if}
 { /if }
 { if $obj->type == 'interface' }
  <a href="{$rewriter->get_page_url('Interface Edit', $obj->idx)}" title="Edit interface { $obj->name }"><img src="{$icon_interfaces}" alt="interface icon" />&nbsp;{ $obj->name }</a>{ if ! $smarty.foreach.filters.last},{/if}
 { /if }
{ foreachelse }
 none
{ /foreach }
</p>
{ page_end focus_to='sl_name' }
