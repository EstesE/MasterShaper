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
 <a class="section" href="{get_url page="pipes"}"><img src="{$icon_pipes}" alt="pipes icon" />&nbsp;Pipes</a>
 <div class="divider"> / </div>
 <div class="active section">Edit {if $pipe->hasName()}{$pipe->getName()}{/if}</div>
</h1>
<div class="ui divider"></div>
<form class="ui form" data-id="{$pipe->getId()}" data-guid="{$pipe->getGuid()}" data-model="pipe">
 <h4 class="ui block header">General Setttings</h4>
 <div class="field">
  <label>Name</label>
  <div class="ui input">
   <input type="text" placeholder="enter a pipe name" name="pipe_name" value="{if $pipe->hasName()}{$pipe->getName()}{/if}" />
  </div>
  <div class="extra">
  </div>
 </div>
 <div class="field">
  <label>Active</label>
  <div class="ui radio checkbox">
   <input type="radio" name="pipe_active" value="Y" {if $pipe->isActive()} checked="checked" {/if} />
   <label>yes</label>
  </div>
  <div class="ui radio checkbox">
   <input type="radio" name="pipe_active" value="N" {if !$pipe->isActive()} checked="checked" {/if} />
   <label>no</label>
  </div>
 </div>
 <h4 class="ui block header">Parameters</h4>
 <div class="field">
  <label>Target</label>
  <table class="noborder">
   <tr>
    <td>Source
     <img class="change_to" src="{$icon_arrow_right}" value="Go" onclick="change_to('{get_url page='targets' mode='edit' id=0}', $('select[name=pipe_src_target]').val());" />
    </td>
    <td>&nbsp;</td>
    <td style="text-align: right;">Destination
     <img class="change_to" src="{$icon_arrow_right}" value="Go" onclick="change_to('{get_url page='targets' mode='edit' id=0}', $('select[name=pipe_dst_target]').val());" />
    </td>
   </tr>
   <tr>
    <td>
     <select name="pipe_src_target">
      <option value="0">any</option>
      {target_select_list target_idx=($pipe->hasSourceTarget()) ? $pipe->getSourceTarget() : null}
     </select>
    </td>
    <td>
     <select name="pipe_direction">
      <option value="1" {if $pipe->hasDirection() && $pipe->getDirection() == 1} selected="selected" {/if}>--&gt;</option>
      <option value="2" {if $pipe->hasDirection() && $pipe->getDirection() == 2} selected="selected" {/if}>&lt;-&gt;</option>
     </select>
    </td>
    <td>
     <select name="pipe_dst_target">
      <option value="0">any</option>
      {target_select_list target_idx=($pipe->hasDestinationTarget()) ? $pipe->getDestinationTarget() : null}
     </select>
    </td>
   </tr>
  </table>
  <div class="extra"> Match a source and destination targets.</div>
 </div>
 <div class="field">
  <label>Filters:</label>
  <table class="noborder">
   <tr>
    <td>
     <select size="10" name="avail[]" multiple="multiple">
      <option value="">********* Unused *********</option>
      {unused_filters_select_list pipe_idx=$pipe->getId()}
     </select>
    </td>
    <td>&nbsp;</td>
    <td>
     <input type="button" value="&gt;&gt;" onclick="moveOptions(document.forms['pipes'].elements['avail[]'], document.forms['pipes'].elements['used[]']);" /><br />
     <input type="button" value="&lt;&lt;" onclick="moveOptions(document.forms['pipes'].elements['used[]'], document.forms['pipes'].elements['avail[]']);" />
    </td>
    <td>&nbsp;</td>
    <td>
     <select size="10" name="used[]" multiple="multiple">
      <option value="">********* Used *********</option>
      {used_filters_select_list pipe_idx=$pipe->getId()}
     </select>
    </td>
   </tr>
  </table>
  <div class="extra">Select the filters this pipe will shape.<br />Remember that port matches will always be matched on "Destination" side!</div>
 </div>
 <h4 class="ui block header">Bandwidth defaults</h4>
 <div class="field">
  <label>Service-Level:</label>
  <select name="pipe_sl_idx">
  {service_level_select_list sl_idx=($pipe->hasServiceLevel()) ? $pipe->getServiceLevel() : null}
  </select>
  <img class="change_to" src="{$icon_arrow_right}" value="Go" onclick="change_to('{get_url page='service-levels' mode='edit' id=0}', $('select[name=pipe_sl_idx]').val());" />
  <div class="extra">Default bandwidth limit for this pipe. It can be overriden per chain as soon as you assigned this pipe to it.</div>
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
{if isset($chain_use_pipes) && !empty($chain_use_pipes)}
 This pipe is assigned to the following chains:<br />
 {foreach from=$chain_use_pipes key=chain_idx item=chain_name name=chains}
  <a href="{get_url page='chains' id=$chain->getSafeLink()}" title="Edit chain {$chain_name}"><img src="{$icon_chains}" alt="chain icon" />&nbsp;{$chain_name}</a>{if !isset($smarty.foreach.chains.last) || empty($smarty.foreach.chains.last)},{/if}
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
      location.href = '{get_url page='pipes'}';
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
      if (!(input = $(this).find('input[name^="pipe_"], textarea[name^="pipe_"]'))) {
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
         location.href = '{get_url page='pipes'}';
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
