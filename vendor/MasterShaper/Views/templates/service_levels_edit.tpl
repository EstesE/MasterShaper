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
<h1 class="ui header breadcrumb">
 <a class="section" href="{get_url page="service-levels"}"><img src="{$icon_servicelevels}" alt="servicelevels icon" />&nbsp;Service Levels</a>
 <div class="divider"> / </div>
 <div class="active section">Edit {if $servicelevel->hasName()}{$servicelevel->getName()}{/if}</div>
</h1>
<div class="ui divider"></div>
<form class="ui form" data-id="{$servicelevel->getId()}" data-guid="{$servicelevel->getGuid()}" data-model="service_level">
 <h4 class="ui block header">General Setttings</h4>
 <div class="field">
  <label>Name</label>
  <div class="ui input">
   <input type="text" placeholder="enter a servicelevel name" name="sl_name" value="{if $servicelevel->hasName()}{$servicelevel->getName()}{/if}" />
  </div>
  <div class="extra">Name of the service level.</div>
 </div>
 <div class="field">
  <label>Active</label>
  <div class="ui radio checkbox">
   <input type="radio" name="sl_active" value="Y" {if $servicelevel->isActive()} checked="checked" {/if} />
   <label>yes</label>
  </div>
  <div class="ui radio checkbox">
   <input type="radio" name="sl_active" value="N" {if !$servicelevel->isActive()} checked="checked" {/if} />
   <label>no</label>
  </div>
 </div>
 <h4 class="ui block header">Classifier Settings</h4>
 <div class="ui accordion">
  <div class="title"><i class="dropdown icon"></i>HTB Settings</div>
  <div class="content">
   <h5><img src="{$icon_servicelevels}" alt="servicelevel icon" />&nbsp;Interface 1 -&gt; Interface 2</h5>
   <div class="field">
    <label>Bandwidth</label>
    <div class="ui right labeled input">
     <input type="text" name="sl_htb_bw_in_rate" value="{if $servicelevel->hasHtbBandwidthInRate()}{$servicelevel->getHtbBandwidthInRate()}{/if}" />
     <div class="ui basic label">kbps</div>
    </div>
    <div class="extra">Bandwidth rate. This is the guaranteed bandwidth.</div>
   </div>
   <div class="field">
    <label>Bandwidth ceil</label>
    <div class="ui right labeled input">
     <input type="text" name="sl_htb_bw_in_ceil" value="{if $servicelevel->hasHtbBandwidthInCeil()}{$servicelevel->getHtbBandwidthInCeil()}{/if}" />
     <div class="ui basic label">kbps</div>
    </div>
    <div class="extra">If the chain has bandwidth to spare, this is the maximum rate which can be lend to this service. The default value is the bandwidth rate which implies no borrowing from the chain.</div>
   </div>
   <div class="field">
    <label>Bandwidth burst</label>
    <div class="ui right labeled input">
     <input type="text" name="sl_htb_bw_in_burst" value="{if $servicelevel->hasHtbBandwidthInBurst()}{$servicelevel->getHtbBandwidthInBurst()}{/if}" />
     <div class="ui basic label">bytes</div>
    </div>
    <div class="extra">Amount of bytes that can be burst at ceil speed, in excess of the configured rate. Should be at least as high as the highest burst of all children. This is useful for interactive traffic.</div>
   </div>
   <h5><img src="{$icon_servicelevels}" alt="servicelevel icon" />&nbsp;Interface 2 -&gt; Interface 1</h5>
   <div class="field">
    <label>Bandwidth</label>
    <div class="ui right labeled input">
     <input type="text" name="sl_htb_bw_out_rate" value="{if $servicelevel->hasHtbBandwidthOutRate()}{$servicelevel->getHtbBandwidthOutRate()}{/if}" />
     <div class="ui basic label">kbps</div>
    </div>
    <div class="extra">Bandwidth rate. This is the guaranteed bandwidth.</div>
   </div>
   <div class="field">
    <label>Bandwidth ceil</label>
    <div class="ui right labeled input">
     <input type="text" name="sl_htb_bw_out_ceil" value="{if $servicelevel->hasHtbBandwidthOutCeil()}{$servicelevel->getHtbBandwidthOutCeil()}{/if}" />
     <div class="ui basic label">kbps</div>
    </div>
    <div class="extra">If the chain has bandwidth to spare, this is the maximum rate which can be lend to this service. The default value is the bandwidth rate which implies no borrowing from the chain.</div>
   </div>
   <div class="field">
    <label>Bandwidth burst</label>
    <div class="ui right labeled input">
     <input type="text" name="sl_htb_bw_out_burst" value="{if $servicelevel->hasHtbBandwidthOutBurst()}{$servicelevel->getHtbBandwidthOutBurst()}{/if}" />
     <div class="ui basic label">bytes</div>
    </div>
    <div class="extra">Amount of bytes that can be burst at ceil speed, in excess of the configured rate. Should be at least as high as the highest burst of all children. This is useful for interactive traffic.</div>
   </div>
   <h5><img src="{$icon_servicelevels}" alt="servicelevel icon" />&nbsp;Parameters</h5>
   <div class="field">
    <label>Priority</label>
    <select name="sl_htb_priority">
     <option value="1" {if $servicelevel->hasHtbPriority() && $servicelevel->getHtbPriority() == 1} selected="selected" {/if}>Highest (1)</option>
     <option value="2" {if $servicelevel->hasHtbPriority() && $servicelevel->getHtbPriority() == 2} selected="selected" {/if}>High (2)</option>
     <option value="3" {if $servicelevel->hasHtbPriority() && $servicelevel->getHtbPriority() == 3} selected="selected" {/if}>Normal (3)</option>
     <option value="4" {if $servicelevel->hasHtbPriority() && $servicelevel->getHtbPriority() == 4} selected="selected" {/if}>Low (4)</option>
     <option value="5" {if $servicelevel->hasHtbPriority() && $servicelevel->getHtbPriority() == 5} selected="selected" {/if}>Lowest (5)</option>
     <option value="0" {if $servicelevel->hasHtbPriority() && $servicelevel->getHtbPriority() == 0} selected="selected" {/if}>Ignore</option>
    </select>
    <div class="extra">The service levels with a higher priority are favoured by the scheduler. Also pipes with service levels with a higher priority can lean more unused bandwidth from their chains. If priority is specified without in- or outbound rate, the maximum interface bandwidth can be used.</div>
   </div>
  </div>
  <div class="title"><i class="dropdown icon"></i>HFSC Settings</div>
  <div class="content">
   <h5><img src="{$icon_servicelevels}" alt="servicelevel icon" />&nbsp;Interface 1 -&gt; Interface 2</h5>
   <div class="field">
    <label>Work-Unit</label>
    <div class="ui right labeled input">
     <input type="text" name="sl_hfsc_in_umax" value="{if $servicelevel->hasHfscInUmax()}{$servicelevel->getHfscInUmax()}{/if}" />
     <div class="ui basic label">bytes</div>
    </div>
    <div class="extra">Maximum unit of work. A value around your MTU (ex. 1500) is a good value.</div>
   </div>
   <div class="field">
    <label>Max-Delay</label>
    <div class="ui right labeled input">
     <input type="text" name="sl_hfsc_in_dmax" value="{if $servicelevel->hasHfscInDmax()}{$servicelevel->getHfscInDmax()}{/if}" />
     <div class="ui basic label">ms</div>
    </div>
    <div class="extra">Maximum delay of a packet within this Qdisc in milliseconds (ms)</div>
   </div>
   <div class="field">
    <label>Rate</label>
    <div class="ui right labeled input">
     <input type="text" name="sl_hfsc_in_rate" value="{if $servicelevel->hasHfscInRate()}{$servicelevel->getHfscInRate()}{/if}" />
     <div class="ui basic label">kbps</div>
    </div>
    <div class="extra">Guaranteed rate of bandwidth in kbps</div>
   </div>
   <div class="field">
    <label>ul-Rate</label>
    <div class="ui right labeled input">
     <input type="text" name="sl_hfsc_in_ulrate" value="{if $servicelevel->hasHfscInUlrate()}{$servicelevel->getHfscInUlrate()}{/if}" />
     <div class="ui basic label">kbps</div>
    </div>
    <div class="extra">Maximum rate of bandwidth in kbps</div>
   </div>
   <h5><img src="{$icon_servicelevels}" alt="servicelevel icon" />&nbsp;Interface 2 -&gt; Interface 1</h5>
   <div class="field">
    <label>Work-Unit</label>
    <div class="ui right labeled input">
     <input type="text" name="sl_hfsc_out_umax" value="{if $servicelevel->hasHfscOutUmax()}{$servicelevel->getHfscOutUmax()}{/if}" />
     <div class="ui basic label">bytes</div>
    </div>
    <div class="extra">Maximum unit of work. A value around your MTU (ex. 1500) is a good value.</div>
   </div>
   <div class="field">
    <label>Max-Delay</label>
    <div class="ui right labeled input">
     <input type="text" name="sl_hfsc_out_dmax" value="{if $servicelevel->hasHfscOutDmax()}{$servicelevel->getHfscOutDmax()}{/if}" />
     <div class="ui basic label">ms</div>
    </div>
    <div class="extra">Maximum delay of a packet within this Qdisc in milliseconds (ms)</div>
   </div>
   <div class="field">
    <label>Rate</label>
    <div class="ui right labeled input">
     <input type="text" name="sl_hfsc_out_rate" value="{if $servicelevel->hasHfscOutRate()}{$servicelevel->getHfscOutRate()}{/if}" />
     <div class="ui basic label">kbps</div>
    </div>
    <div class="extra">Guaranteed rate of bandwidth in kbps</div>
   </div>
   <div class="field">
    <label>ul-Rate</label>
    <div class="ui right labeled input">
     <input type="text" name="sl_hfsc_out_ulrate" value="{if $servicelevel->hasHfscOutUlrate()}{$servicelevel->getHfscOutUlrate()}{/if}" />
     <div class="ui basic label">kbps</div>
    </div>
    <div class="extra">Maximum rate of bandwidth in kbps</div>
   </div>
  </div>
 </div>

 <h4 class="ui block header">Queuing Discipline</h4>
 <div class="field">
  <label>Queuing Discipline</label>
  <select name="sl_qdisc">
   <option value="SFQ" {if $servicelevel->hasQdisc() && $servicelevel->getQdisc() == "SFQ"} selected="selected" {/if}>SFQ</option>
   <option value="ESFQ" {if $servicelevel->hasQdisc() && $servicelevel->getQdisc() == "ESFQ"} selected="selected" {/if}>ESFQ</option>
   <option value="HFSC" {if $servicelevel->hasQdisc() && $servicelevel->getQdisc() == "HFSC"} selected="selected" {/if}>HFSC</option>
   <option value="NETEM" {if $servicelevel->hasQdisc() && $servicelevel->getQdisc() == "NETEM"} selected="selected" {/if}>NETEM</option>
  </select>
  <div class="extra">Select the to be used Queuing Discipline.</div>
 </div>
 <div class="ui accordion">
  <div class="title"><i class="dropdown icon"></i>SFQ Advanced</div>
  <div class="content">
   <div class="field">
     <label>Perturb</label>
    <input type="text" name="sl_sfq_perturb" value="{if $servicelevel->hasSfqPerturb()}{$servicelevel->getSfqPerturb()}{/if}" />
    <div class="extra">Reconfigure hashing once this many seconds. Default is 10. Change only if you know what you do!</div>
   </div>
   <div class="field">
    <label>Quantum</label>
    <div class="ui right labeled input">
     <input type="text" name="sl_sfq_quantum" value="{if $servicelevel->hasSfqQuantum()}{$servicelevel->getSfqQuantum()}{/if}" />
     <div class="ui basic label">bytes</div>
    </div>
    <div class="extra">Amount of bytes a stream is allowed to dequeue before the next queue gets a turn. Default is 1532. Do not set below the MTU. Change only if you know what you do!</div>
   </div>
  </div>
  <div class="title"><i class="dropdown icon"></i>ESFQ Advanced Settings</div>
  <div class="content">
   <div class="field">
    <label>Perturb</label>
    <input type="text" name="sl_esfq_perturb" value="{if $servicelevel->hasEsfqPerturb()}{$servicelevel->getEsfqPerturb()}{/if}" />
    <div class="extra">Causes the flows to be redistributed so there are no collosions on sharing a queue. Default is 0. Recommeded 10.</td>
   </div>
   <div class="field">
    <label>Limit</label>
    <input type="text" name="sl_esfq_limit" value="{if $servicelevel->hasEsfqLimit()}{$servicelevel->getEsfqLimit()}{/if}" />
    <div class="extra">The total number of packets that will be queued by this ESFQ before packets start getting dropped.  Limit must be less than or equal to depth. Default is 128.</div>
   </div>
   <div class="field">
    <label>Depth</label>
    <input type="text" name="sl_esfq_depth" value="{if $servicelevel->hasEsfqDepth()}{$servicelevel->getEsfqDepth()}{/if}" />
    <div class="extra">No description available. Set like Limit.</div>
   </div>
   <div class="field">
    <label>Divisor</div>
    <input type="text" name="sl_esfq_divisor" value="{if $servicelevel->hasEsfqDivisor()}{$servicelevel->getEsfqDivisor()}{/if}" />
    <div class="extra">Divisor sets the number of bits to use for the hash table. A larger hash table decreases the likelihood of collisions but will consume more memory.</div>
   </div>
   <div class="field">
    <label>Hash</label>
    <select name="sl_esfq_hash">
     <option value="classic" {if $servicelevel->hasEsfqHash() && $servicelevel->getEsfqHash() == "classic"} selected="selected"; {/if}>Classic</option>
     <option value="src" {if $servicelevel->hasEsfqHash() && $servicelevel->getEsfqHash() == "src"} selected="selected"; {/if}>Src</option>
     <option value="dst" {if $servicelevel->hasEsfqHash() && $servicelevel->getEsfqHash() == "dst"} selected="selected"; {/if}>Dst</option>
     <option value="fwmark" {if $servicelevel->hasEsfqHash() && $servicelevel->getEsfqHash() == "fwmark"} selected="selected"; {/if}>Fwmark</option>
     <option value="src_direct" {if $servicelevel->hasEsfqHash() && $servicelevel->getEsfqHash() == "src_direct"} selected="selected"; {/if}>Src_direct</option>
     <option value="dst_direct" {if $servicelevel->hasEsfqHash() && $servicelevel->getEsfqHash() == "dst_direct"} selected="selected"; {/if}>Dst_direct</option>
     <option value="fwmark_direct" {if $servicelevel->hasEsfqHash() && $servicelevel->getEsfqHash() == "fwmark_direct"} selected="selected"; {/if}>Fwmark_direct</option>
    </select>
    <div class="extra">Howto seperate traffic into queues. Classisc equals to SFQ handling. Src and Dst per direction. Fwmark uses the connection mark which can be set by iptables. If less then 16384 (2^14) simultaneous connections occurs use one of the _direct sibling which uses an fast algorithm.</div>
   </div>
  </div>
  <div class="title"><i class="dropdown icon"></i>Network Emulator (NETEM) Advanced Settings</div>
  <div class="content">
   <h5 class="ui block header">NETEM - Network Delays</h5>
   <div class="field">
    <label>Delay</label>
    <div class="ui right labeled input">
     <input type="text" name="sl_netem_delay" value="{if $servicelevel->hasNetemDelay()}{$servicelevel->getNetemDelay()}{/if}" />
     <div class="ui basic label">ms</div>
    </div>
    <div class="extra">Fixed amount of delay to all packets.</div>
   </div>
   <div class="field">
    <label>Jitter</label>
    <div class="ui right labeled input">
     <input type="text" name="sl_netem_jitter"  value="{if $servicelevel->hasNetemJitter()}{$servicelevel->getNetemJitter()}{/if}" />
     <div class="ui basic label">ms</div>
    </div>
    <div class="extra">Random variation around the delay value (= delay &#177; Jitter).</div>
   </div>

   <div class="field">
    <label>Correlation</label>
    <div class="ui right labeled input">
     <input type="text" name="sl_netem_random" value="{if $servicelevel->hasNetemRandom()}{$servicelevel->getNetemRandom()}{/if}" />
     <div class="ui basic label">&#37;</div>
    </div>
    <div class="extra">Limits the randomness to simulate a real network. So the next packets delay will be within % of the delay of the packet before.</div>
   </div>

   <div class="field">
    <label>Distribution</label>
    <select name="sl_netem_distribution">
       <option value="ignore" {if $servicelevel->hasNetemDistribution() && $servicelevel->getNetemDistribution() == "ignore"} selected="selected"; {/if}>Ignore</option>
     <option value="normal" {if $servicelevel->hasNetemDistribution() && $servicelevel->getNetemDistribution() == "normal"} selected="selected"; {/if}>normal</option>
     <option value="pareto" {if $servicelevel->hasNetemDistribution() && $servicelevel->getNetemDistribution() == "pareto"} selected="selected"; {/if}>pareto</option>
     <option value="paretonormal" {if $servicelevel->hasNetemDistribution() && $servicelevel->getNetemDistribution() == "paretonormal"} selected="selected"; {/if}>paretonormal</option>
    </select>
    <div class="extra">How the delays are distributed over a longer delay periode.</div>
   </div>

   <h5 class="ui block header">NETEM - More Settings</h5>
   <div class="field">
    <label>Packetloss</label>
    <div class="ui right labeled input">
     <input type="text" name="sl_netem_loss" value="{if $servicelevel->hasNetemLoss()}{$servicelevel->getNetemLoss()}{/if}" />
     <div class="ui basic label">&#37;</div>
    </div>
    <div class="extra">Packetloss in percent. Smallest value is .0000000232% ( = 1 / 2^32).</div>
   </div>

   <div class="field">
    <label>Duplication</label>
    <div class="ui right labeled input">
     <input type="text" name="sl_netem_duplication" value="{if $servicelevel->hasNetemDuplication()}{$servicelevel->getNetemDuplication()}{/if}" />
     <div class="ui basic label">&#37;</div>
    </div>
    <div class="extra">Duplication in percent. Smallest value is .0000000232% ( = 1 / 2^32).</div>
   </div>

   <h5 class="ui block header">NETEM - Re-Ordering</h5>

   <div class="field">
    <label>Gap</label>
    <input type="text" name="sl_netem_gap" value="{if $servicelevel->hasNetemGap()}{$servicelevel->getNetemGap()}{/if}" />
    <div class="extra">Packet re-ordering causes 1 out of N packets to be delayed. For a value of 5 every 5th (10th, 15th, ...) packet will get delayed by 10ms and the others will pass straight out.</div>
   </div>

   <div class="field">
    <label>Reorder percentage</label>
    <div class="ui right labeled input">
     <input type="text" name="sl_netem_reorder_percentage" value="{if $servicelevel->hasNetemReorderPercentage()}{$servicelevel->getNetemReorderPercentage()}{/if}" />
     <div class="ui basic label">&#37;</div>
    </div>
    <div class="extra">Percentage of packets the get reordered.</div>
   </div>

   <div class="field">
    <label>Reorder correlation</label>
    <div class="ui right labeled input">
     <input type="text" name="sl_netem_reorder_correlation" value="{if $servicelevel->hasNetemReorderCorrelation()}{$servicelevel->getNetemReorderCorrelation()}{/if}" />
     <div class="ui basic label">&#37;</div>
    </div>
    <div class="extra">Percentage of packets the are correlate each others.</div>
   </div>
  </div>
 </div>

 <div class="ui divider"></div>
 <div class="ui buttons">
  <button class="ui labeled icon positive button save" type="submit">
   <div class="ui inverted dimmer">
    <div class="ui loader"></div>
   </div>
   <i class="save icon"></i>Save
  </button>
  <div class="or"></div>
  <button class="ui button discard">
   <i class="remove icon"></i>Discard
  </button>
 </div>
</form>
<p class="footnote">
{if isset($obj_use_sl) && !empty($obj_use_sl)}
This target is assigned to the following objects<br />
{foreach from=$obj_used key=obj_idx item=obj name=objects}
 {if $obj->type == 'pipe'}
  <a href="{get_url page='pipes' mode='edit' id=$obj->getSafeLink()}" title="Edit pipe {$obj->name}"><img src="{$icon_pipes}" alt="pipe icon" />&nbsp;{$obj->name}</a>{if !isset($smarty.foreach.objects.last) || empty($smarty.foreach.objects.last)},{/if}
 {/if}
 {if $obj->type == 'chain'}
  <a href="{get_url page='chains' mode='edit' id=$obj->getSafeLink()}" title="Edit chain {$obj->name}"><img src="{$icon_chains}" alt="chain icon" />&nbsp;{$obj->name}</a>{if !isset($smarty.foreach.objects.last) || empty($smarty.foreach.objects.last)},{/if}
 {/if}
 {if $obj->type == 'interface'}
  <a href="{get_url page='interfaces' mode='edit' id=$obj->getSafeLink()}" title="Edit interface {$obj->name}"><img src="{$icon_interfaces}" alt="interface icon" />&nbsp;{$obj->name}</a>{if !isset($smarty.foreach.objects.last) || empty($smarty.foreach.objects.last)},{/if}
 {/if}
{foreachelse}
 none
{/foreach}
{/if}
</p>
<script type="text/javascript">
'use strict';

$(document).ready(function () {
   $('.ui.checkbox').checkbox();
   $('.ui.accordion').accordion();
   $('.ui.button.discard').click(function () {
      location.href = '{get_url page='service-levels'}';
   });
   $('.ui.button.save').click(function () {
      $(this).popup('hide')
         .find('.ui.inverted.dimmer').addClass('active');
   });
   $('.ui.form').submit(function () {
      var id, guid, model, input, values;

      if (typeof mbus === 'undefined') {
         throw new Error('MessageBus is not available!');
         return false;
      }

      if (!(id = $(this).attr('data-id'))) {
         throw new Error('failed to locate data-id attribute!');
         return false;
      }
      if (!(guid = $(this).attr('data-guid'))) {
         throw new Error('failed to locate data-guid attribute!');
         return false;
      }
      if (!(model = $(this).attr('data-model'))) {
         throw new Error('failed to locate data-model attribute!');
         return false;
      }
      if (!(input = $(this).find('input[name^="sl_"], textarea[name^="sl_"], select[name^="sl_"]'))) {
         throw new Error('failed to locate any form elements!');
         return false;
      }
      values = new Object;
      input.each (function (index, element) {
         var name;
         element = $(element);
         if (!(name = element.attr('name'))) {
            return;
         }
         if (element.prop('nodeName') === 'INPUT') {
            if (element.attr('type') === 'text') {
               values[name] = element.val();
               return;
            } else if (element.attr('type') === 'checkbox') {
               if (element.is(':checked')) {
                  values[name] = element.val();
               }
               return;
            } else if (element.attr('type') === 'radio') {
               if (element.is(':checked')) {
                  values[name] = element.val();
               }
               return;
            } else {
               throw new Error('unsupported type! ' + element.attr('type'));
               return;
            }
         } else if (element.prop('nodeName') === 'TEXTAREA') {
            values[name] = element.text();
            return;
         } else if (element.prop('nodeName') === 'SELECT') {
            // document.forms['servicelevels'].classifier.options[document.forms['servicelevels'].classifier.selectedIndex].value
            console.log(element.val());
            values[name] = element.val();
         } else {
            throw new Error('unsupported nodeName!');
            return false;
         }
      });

      values['id'] = id;
      values['guid'] = guid;
      values['model'] = model;

      var msg = new ThalliumMessage;
      msg.setCommand('save-request');
      msg.setMessage(values);
      if (!mbus.add(msg)) {
         throw new Error('ThalliumMessageBus.add() returned false!');
         return false;
      }

      var save_timeout = setTimeout(function () {
         var save = $(this).find('.ui.button.save');
         // turn button red
         save.removeClass('positive').addClass('negative');
         // unsubscribe from MessageBus
         mbus.unsubscribe('save-replies-handler');
         // remove the loader
         save.find('.ui.inverted.dimmer').removeClass('active');
         // show a popup message
         save.popup({
            on          : 'manual',
            preserve    : true,
            exclusive   : true,
            lastResort  : true,
            content     : 'Saving failed - 10sec timeout reached! Click the save button to try again.',
            position    : 'top center',
            transition  : 'slide up'
         })
            .addClass('flowing red')
            .popup('show');
      }.bind(this), 10000);

      mbus.subscribe('save-replies-handler', 'save-reply', function (reply) {
         var newData, value, del_wnd, progressbar;

         if (typeof reply === 'undefined' || !reply) {
            throw new Error('reply is empty!');
            return false;
         }
         newData = new Object;

         if (reply.value && (value = reply.value.match(/([0-9]+)%$/))) {
            newData.percent = value[1];
         }
         if (reply.body != 'Done') {
            return true;
         }
         clearTimeout(save_timeout);
         mbus.unsubscribe('save-replies-handler');
         $(this).find('.ui.button.save .ui.inverted.dimmer').removeClass('active');
         location.href = '{get_url page='service-levels'}';
         return true;
      }.bind(this));


      if (!mbus.send()) {
         throw 'ThalliumMessageBus.send() returned false!';
         return false;
      }
      return false;
   });
});
</script>
