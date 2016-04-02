<h1 class="ui header breadcrumb">
 <a class="section" href="{get_url page="network-paths"}"><img src="{$icon_interfaces}" alt="interfaces icon" />&nbsp;Network Paths</a>
 <div class="divider"> / </div>
 <div class="active section">Edit {if $netpath->hasName()}{$netpath->getName()}{/if}</div>
</h1>
<div class="ui divider"></div>
<form class="ui form" data-id="{$netpath->getId()}" data-guid="{$netpath->getGuid()}" data-model="network_path" method="POST">
 <h4 class="ui block header">General Setttings</h4>
 <div class="field">
  <label>Name</label>
  <div class="ui input">
   <input type="text" placeholder="enter a network path name" name="netpath_name" value="{if $netpath->hasName()}{$netpath->getName()}{/if}" />
  </div>
  <div class="extra">
  </div>
 </div>
 <div class="field">
  <label>Active</label>
  <div class="ui radio checkbox">
   <input type="radio" name="netpath_active" value="Y" {if $netpath->isActive()} checked="checked" {/if} />
   <label>yes</label>
  </div>
  <div class="ui radio checkbox">
   <input type="radio" name="netpath_active" value="N" {if !$netpath->isActive()} checked="checked" {/if} />
   <label>no</label>
  </div>
 </div>

 <h4 class="ui block header">Interfaces</h4>
 <div class="inline fields">
  <div class="field">
   <label>Interface 1</label>
   <select name="netpath_if1">
    {if_select_list if_idx=$netpath->getInterface1()}
   </select>
  </div>
  <div class="field">
   <label>inside GRE-tunnel</label>
   <input type="checkbox" name="netpath_if1_inside_gre" value="Y" {if $netpath->isInterface1InsideGre()} checked="checked"{/if} />
  </div>
  <div class="extra">First interface of this network path.</div>
 </div>
 <div class="inline fields">
  <div class="field">
   <label>Interface 2</label>
   <select name="netpath_if2">
    {if_select_list if_idx=$netpath->getInterface2()}
    <option value="-1" {if $netpath->hasInterface2() && $netpath->getInterface2() == -1} selected="selected"{/if}>--- not used ---</option>
   </select>
  </div>
  <div class="field">
   <label>inside GRE-tunnel</label>
   <input type="checkbox" name="netpath_if2_inside_gre" value="Y" {if $netpath->isInterface2InsideGre()} checked="checked"{/if} />
  </div>
  <div class="extra">Second interface of this network path.</div>
 </div>

 <h4 class="ui block header">Options</h4>
 <div class="field">
  <label>IMQ</label>
  <div class="radio checkbox">
   <label>Yes</label>
   <input type="radio" name="netpath_imq" value="Y" {if $netpath->isImq()} checked="checked" {/if} />
  </div>
  <div class="radio checkbox">
   <label>No</label>
   <input type="radio" name="netpath_imq" value="N" {if !$netpath->isImq()} checked="checked" {/if} />
  </div>
  <div class="extra">Do you use IMQ (Intermediate Queuing Device) devices within this network path?</div>
 </div>

 <div class="field">
  <label>Chains</label>
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
      <a href="{get_url page='chains' mode='edit' id=$chain->getSafeLink()}" title="Edit chain {$chain->chain_name}"><img src="{$icon_chains}" alt="chain icon" />&nbsp;{$chain->chain_name}</a>
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
  <div class="extra">Select chains bound to this network path.</div>
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
      location.href = '{get_url page='network-paths'}';
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
      if (!(input = $(this).find('input[name^="netpath_"], textarea[name^="netpath_"]'))) {
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
         location.href = '{get_url page='network-paths'}';
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
