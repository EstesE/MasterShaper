<h1 class="ui header breadcrumb">
 <a class="section" href="{get_url page="network-interfaces"}"><img src="{$icon_interfaces}" alt="interfaces icon" />&nbsp;Network Interfaces</a>
 <div class="divider"> / </div>
 <div class="active section">Edit {if $if->hasName()}{$if->getName()}{/if}</div>
</h1>
<div class="ui divider"></div>
<form class="ui form" data-id="{$if->getId()}" data-guid="{$if->getGuid()}" data-model="network_interface" method="POST">
 <h4 class="ui block header">General Setttings</h4>
 <div class="field">
  <label>Name</label>
  <div class="ui input">
   <input type="text" placeholder="enter a interface name" name="if_name" value="{if $if->hasName()}{$if->getName()}{/if}" />
  </div>
  <div class="extra">
  </div>
 </div>
 <div class="field">
  <label>Active</label>
  <div class="ui radio checkbox">
   <input type="radio" name="if_active" value="Y" {if $if->isActive()} checked="checked" {/if} />
   <label>yes</label>
  </div>
  <div class="ui radio checkbox">
   <input type="radio" name="if_active" value="N" {if !$if->isActive()} checked="checked" {/if} />
   <label>no</label>
  </div>
 </div>
 <h4 class="ui block header">Details</h4>
 <div class="field">
  <label>Bandwidth</label>
  <input type="text" name="if_speed" value="{if $if->hasSpeed()}{$if->getSpeed()}{/if}" />
  <div class="extra">Specify the outbound bandwidth on this interface in bps (append K for kbps or M for Mbps).</div>
 </div>
 <div class="field">
  <label>Fallback</label>
  <select name="if_fallback_idx">
   <option value="0" {if $if->if_fallback_idx == 0} selected="selected" {/if} >--- No Fallback ---</option>
    {service_level_select_list sl_idx=$if->if_fallback_idx}
  </select>
  <img class="change_to" src="{$icon_arrow_right}" value="Go" onclick="change_to('{get_url page='service-levels' mode='edit' id=0}', $('select[name=if_fallback_idx]').val());" />
  <div class="extra">If none of the defined chains matches, you can define here a final fallback service level per interface.</div>
 </div>
 <div class="field">
  <label>IFB</label>
  <div class="ui radio checkbox">
   <label>enabled</label>
   <input type="radio" name="if_ifb" value="Y" {if $if->isIfb()} checked="checked"{/if} />
  </div>
  <div class="ui radio checkbox">
   <label>disabled</label>
   <input type="radio" name="if_ifb" value="N" {if !$if->isIfb()} checked="checked"{/if} />
  </div>
  <div class="extra">This option enables IFB support on this interface. Make sure that IFB is compiled into your kernel or the proper kernel module is loaded!</div>
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
This interface is assigned to the following network paths:<br />
{if isset($np_use_if) && !empty($np_use_if)}
 {foreach from=$np_use_if key=np_idx item=np_name name=networkpaths}
  <a href="{get_url page='network-paths' mode='edit' id=$netpath->getSafeLink()}" title="Edit network path  $np_name}"><img src="{$icon_interfaces}" alt="interface icon" />&nbsp;{$np_name}</a>{if !isset($smarty.foreach.networkpaths.last) || empty($smarty.foreach.networkpaths.last)},{/if}
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
      location.href = '{get_url page='network-interfaces'}';
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
      if (!(input = $(this).find('input[name^="if_"], textarea[name^="if_"]'))) {
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
            } else if (element.attr('type') === 'password') {
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
         location.href = '{get_url page='network-interfaces'}';
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
