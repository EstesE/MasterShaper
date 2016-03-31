<h1 class="ui header breadcrumb">
 <a class="section" href="{get_url page="ports"}"><img src="{$icon_ports}" alt="ports icon" />&nbsp;Ports</a>
 <div class="divider"> / </div>
 <div class="active section">Edit {if $port->hasName()}{$port->getName()}{/if}</div>
</h1>
<div class="ui divider"></div>
<form class="ui form" data-id="{$port->getId()}" data-guid="{$port->getGuid()}" data-model="port">
 <h4 class="ui block header">General Setttings</h4>
 <div class="field">
  <label>Name</label>
  <div class="ui input">
   <input type="text" placeholder="enter a port name" name="port_name" value="{if $port->hasName()}{$port->getName()}{/if}" />
  </div>
  <div class="extra">
  </div>
 </div>
 <div class="field">
  <label>Active</label>
  <div class="ui radio checkbox">
   <input type="radio" name="port_active" value="Y" {if $port->isActive()} checked="checked" {/if} />
   <label>yes</label>
  </div>
  <div class="ui radio checkbox">
   <input type="radio" name="port_active" value="N" {if !$port->isActive()} checked="checked" {/if} />
   <label>no</label>
  </div>
 </div>
 <h4 class="ui block header">Port Parameters</h4>
 <div class="field">
  <label>Port Number</label>
  <div class="ui input">
   <input type="text" placeholder="enter a integer number" name="port_number" value="{if $port->hasNumber()}{$port->getNumber()}{/if}" />
  </div>
 </div>
 <div class="field">
  <label>Description</label>
  <div class="ui input">
   <input type="text" placeholder="enter a describing text" name="port_desc" value="{if $port->hasDescription()}{$port->getDescription()}{/if}" />
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
{if isset($obj_use_port) && !empty($obj_use_port)}
 This port is assigned to the following objects:<br />
 {foreach from=$obj_use_port key=obj_idx item=obj name=objects}
  {if $obj->type == 'group'}
   <a href="{get_url page='ports' mode='edit' id=$obj->getSafeLink()}" title="Edit port {$obj->name}"><img src="{$icon_ports}" alt="port icon" />&nbsp;{$obj->name}</a>{if !isset($smarty.foreach.objects.last) || empty($smarty.foreach.objects.last)},{/if}
  {/if}
  {if $obj->type == 'pipe'}
   <a href="{get_url page='pipes' mode='edit' id=$obj->getSafeLink()}" title="Edit pipe {$obj->name}"><img src="{$icon_pipes}" alt="pipe icon" />&nbsp;{$obj->name}</a>{if !isset($smarty.foreach.objects.last) || empty($smarty.foreach.objects.last)},{/if}
  {/if}
  {if $obj->type == 'chain'}
   <a href="{get_url page='chains' mode='edit' id=$obj->getSafeLink()}" title="Edit chain {$obj->name}"><img src="{$icon_chains}" alt="chain icon" />&nbsp;{$obj->name}</a>{if !isset($smarty.foreach.objects.last) || empty($smarty.foreach.objects.last)},{/if}
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
      location.href = '{get_url page='ports'}';
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
      if (!(input = $(this).find('input[name^="port_"], textarea[name^="port_"]'))) {
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
         location.href = '{get_url page='ports'}';
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
