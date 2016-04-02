<h1 class="ui header breadcrumb">
 <a class="section" href="{get_url page="chains"}"><img src="{$icon_chains}" alt="chains icon" />&nbsp;Chains</a>
 <div class="divider"> / </div>
 <div class="active section">Edit {if $chain->hasName()}{$chain->getName()}{/if}</div>
</h1>
<div class="ui divider"></div>
<form class="ui form" data-id="{$chain->getId()}" data-guid="{$chain->getGuid()}" data-model="chain">
 <h4 class="ui block header">General Setttings</h4>
 <div class="field">
  <label>Name</label>
  <div class="ui input">
   <input type="text" placeholder="enter a chain name" name="chain_name" value="{if $chain->hasName()}{$chain->getName()}{/if}" />
  </div>
  <div class="extra">
  </div>
 </div>
 <div class="field">
  <label>Active</label>
  <div class="ui radio checkbox">
   <input type="radio" name="chain_active" value="Y" {if $chain->isActive()} checked="checked" {/if} />
   <label>yes</label>
  </div>
  <div class="ui radio checkbox">
   <input type="radio" name="chain_active" value="N" {if !$chain->isActive()} checked="checked" {/if} />
   <label>no</label>
  </div>
 </div>
 <h4 class="ui block header">Bandwidth</h4>
 <div class="field">
  <label>Service Level:</label>
  <select name="chain_sl_idx">
   {service_level_select_list sl_idx=($chain->hasServiceLevel()) ? $chain->getServiceLevel() : null}
   <option value="0" {if !$chain->hasServiceLevel() || $chain->getServiceLevel() == 0} selected="selected" {/if} >--- Ignore QoS ---</option>
  </select>
  <img class="change_to" src="{$icon_arrow_right}" value="Go" onclick="change_to('{get_url page='service-levels' mode='edit' id=0}', $('select[name=chain_sl_idx]').val());" />
 </div>
 <div class="field">
  <label>Fallback:</label>
  <select name="chain_fallback_idx">
   {service_level_select_list sl_idx=($chain->hasFallbackServiceLevel()) ? $chain->getFallbackServiceLevel() : null}
   <option value="0" {if !$chain->hasFallbackServiceLevel() || $chain->getFallbackServiceLevel() == 0} selected="selected" {/if} >--- No Fallback ---</option>
  </select>
  <img class="change_to" src="{$icon_arrow_right}" value="Go" onclick="change_to('{get_url page='service-levels' mode='edit' id=0}', $('select[name=chain_fallback_idx]').val());" />
 </div>

 <h4 class="ui block header">Targets</h4>
 <div class="field">
  <label>Network Path:</label>
  <select name="chain_netpath_idx">
   {network_path_select_list np_idx=($chain->hasNetworkPath()) ? $chain->getNetworkPath() : null}
  </select>
  <img class="change_to" src="{$icon_arrow_right}" value="Go" onclick="change_to('{get_url page='network-paths' mode='edit' id=0}', $('select[name=chain_netpath_idx]').val());" />
 </div>
 <div class="field">
  <label>Match targets:</label>
  <table class="noborder">
   <tr>
    <td>Source {if isset($chain_netpath_if1)}({$chain_netpath_if1}){/if}
     <img class="change_to" src="{$icon_arrow_right}" value="Go" onclick="change_to('{get_url page='targets' mode='edit' id=0}', $('select[name=chain_src_target]').val());" />
    </td>
    <td>&nbsp;</td>
    <td style="text-align: right;">Destination {if isset($chain_netpath_if2)}({$chain_netpath_if2}){/if}
     <img class="change_to" src="{$icon_arrow_right}" value="Go" onclick="change_to('{get_url page='targets' mode='edit' id=0}', $('select[name=chain_dst_target]').val());" />
    </td>
   </tr>
   <tr>
    <td>
    <select name="chain_src_target">
      <option value="0">any</option>
      {target_select_list target_idx=($chain->hasSourceTarget()) ? $chain->getSourceTarget() : null}
     </select>
    </td>
    <td>
     <select name="chain_direction">
      <option value="1" {if $chain->hasDirection() && $chain->getDirection() == 1} selected="selected" {/if} >--&gt;</option>
      <option value="2" {if $chain->hasDirection() && $chain->getDirection() == 2} selected="selected" {/if} >&lt;-&gt;</option>
     </select>
    </td>
    <td>
     <select name="chain_dst_target">
      <option value="0">any</option>
      {target_select_list target_idx=($chain->hasDestinationTarget()) ? $chain->getDestinationTarget() : null}
     </select>
    </td>
   </tr>
  </table>
 </div>
 <div class="field">
  <label>Pipes</label>
  {if ( isset($chain_sl) && !empty($chain_sl) && $chain_sl->sl_htb_bw_in_rate < $chain_total_bw_in )}
   <b>More inbound bandwidth has been guaranteed ({$chain_total_bw_in}kbps) than available ({$chain_sl->sl_htb_bw_in_rate}kbps)!</b>
   <br />
  {else}
   Guaranteed inbound bandwidth: {if isset($chain_total_bw_in)}{$chain_total_bw_in}kbps{else}unknown{/if}<br />
  {/if}
  {if ( isset($chain_sl) && !empty($chain_sl) && $chain_sl->sl_htb_bw_out_rate < $chain_total_bw_out )}
   <b>More outbound bandwidth has been guaranteed ({$chain_total_bw_out}kbps) than available ({$chain_sl->sl_htb_bw_out_rate}kbps)!</b>
   <br />
  {else}
   Guaranteed outbound bandwidth: {if isset($chain_total_bw_out)}{$chain_total_bw_out}kbps{else}unknown{/if}<br />
  {/if}
  <br />
  <i>(Drag &amp; drop pipes to change order.)</i><br />
  <table class="withborder2" id="pipelist">
   <thead>
   <tr>
    <td><img src="{$icon_pipes}" alt="pipe icon" />&nbsp;<i>Pipe</i></td>
    <td><i>Used</i></td>
    <td><img src="{$icon_servicelevels}" alt="servicelevel icon" />&nbsp;<i>Service Level (override in this chain only)</i></td>
    <td><i>Status</i></td>
   </tr>
   </thead>
    <tbody id="pipes">
   {pipe_list}
    <tr id="pipe{$pipe->pipe_idx}" {if $pipe->apc_pipe_idx == 0} style="opacity: 0.5;" {/if} onmouseover="setBackGrdColor(this, 'mouseover');" onmouseout="setBackGrdColor(this, 'mouseout');">
     <td class="pipes_dragger">
      <a href="{page='pipes' mode='edit' id=$pipe->getSafeLink()}" title="Edit pipe {$pipe->pipe_name}"><img src="{$icon_pipes}" alt="pipe icon" />&nbsp;{$pipe->pipe_name}</a>
     </td>
     <td style="text-align: center;">
      <input type="checkbox" name="used[]" value="{$pipe->pipe_idx}" {if $pipe->apc_pipe_idx != 0} checked="checked" {/if} onclick="if(this.checked == false) $('table#pipelist tbody#pipes tr#pipe{$pipe->pipe_idx}').fadeTo(500, 0.50); else $('table#pipelist tbody#pipes tr#pipe{$pipe->pipe_idx}').fadeTo(500, 1);" />
     </td>
     <td>
     <select name="pipe_sl_idx[{$pipe->pipe_idx}]" id="pipe_sl_idx{$pipe->pipe_idx}">
       {service_level_select_list sl_idx=$pipe->sl_in_use sl_default=$pipe->pipe_sl_idx }
      </select>
      <img class="change_to" src="{$icon_arrow_right}" value="Go" onclick="change_to('get_url page='service-levels' mode='edit' id=0}', $('#pipe_sl_idx{$pipe->pipe_idx}').val());" />
     </td>
     <td style="text-align: center;">
      <input type="hidden" id="pipe-active-{$pipe->pipe_idx}" name="pipe_active[{$pipe->pipe_idx}]" value="{$pipe->apc_pipe_active}" />
      <div class="toggle" id="toggle-{$pipe->pipe_idx}" style="display: inline;">
       <a class="toggle-off" id="pipe-{$pipe->pipe_idx}" parent="chain-{$chain->getId()}" to="off" title="Disable pipe {$pipe->pipe_name}" {if $pipe->apc_pipe_active != "Y"} style="display: none;" {/if} onclick="$('#pipe-active-{$pipe->pipe_idx}').val('N');"><img src="{$icon_active}" alt="active icon" /></a>
       <a class="toggle-on" id="pipe-{$pipe->pipe_idx}" parent="chain-{$chain->getId()}" to="on" title="Enable pipe {$pipe->pipe_name}" {if $pipe->apc_pipe_active == "Y"} style="display: none;" {/if} onclick="$('#pipe-active-{$pipe->pipe_idx}').val('Y');"><img src="{$icon_inactive}" alt="inactive icon" /></a>
      </div>
     </td>
    </tr>
   {/pipe_list}
    </tbody>
  </table>
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
<script type="text/javascript">
'use strict';

$(document).ready(function () {
   $('.ui.checkbox').checkbox();
   $('.ui.accordion').accordion();
   $('.ui.button.discard').click(function () {
      location.href = '{get_url page='chains'}';
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
      if (!(input = $(this).find('input[name^="chain_"], textarea[name^="chain_"]'))) {
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
         location.href = '{get_url page='chains'}';
         return true;
      }.bind(this));


      if (!mbus.send()) {
         throw 'ThalliumMessageBus.send() returned false!';
         return false;
      }
      return false;
   });

   $(function(){
      $("table#pipelist tbody#pipes").sortable({
         accept:      'tbody#pipe',
         greedy:      true,
         cursor:      'crosshair',
         placeholder: 'ui-state-highlight',
         delay:       250
      });
      $("table#pipelist tbody#pipes").disableSelection();
      $('td.pipes_dragger').hover(
         function() {
             $(this).css('cursor','crosshair');
         },
         function() {
             $(this).css('cursor','auto');
         }
      );
   });

});
</script>
