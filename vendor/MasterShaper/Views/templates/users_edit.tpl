<h1 class="ui header breadcrumb">
 <a class="section" href="{get_url page="users"}"><img src="{$icon_users}" alt="users icon" />&nbsp;Users</a>
 <div class="divider"> / </div>
 <div class="active section">Edit {if $user->hasName()}{$user->getName()}{/if}</div>
</h1>
<div class="ui divider"></div>
<form class="ui form" data-id="{$user->getId()}" data-guid="{$user->getGuid()}" data-model="user" method="POST">
 <h4 class="ui block header">General Setttings</h4>
 <div class="field">
  <label>Name</label>
  <div class="ui input">
   <input type="text" placeholder="enter a user name" name="user_name" value="{if $user->hasName()}{$user->getName()}{/if}" />
  </div>
  <div class="extra">
  </div>
 </div>
 <div class="field">
  <label>Active</label>
  <div class="ui radio checkbox">
   <input type="radio" name="user_active" value="Y" {if $user->isActive()} checked="checked" {/if} />
   <label>yes</label>
  </div>
  <div class="ui radio checkbox">
   <input type="radio" name="user_active" value="N" {if !$user->isActive()} checked="checked" {/if} />
   <label>no</label>
  </div>
 </div>
 <h4 class="ui block header">Password</h4>
 <div class="field">
  <label>Password</label>
  <input type="password" name="user_password" {if $user->hasPassword()} value="{$user->getGarbledPassword()}"{/if} />
  <div class="extra">Enter password of the user.</div>
 </div>
 <div class="field">
  <label>again</label>
  <input type="password" name="repeat_password" {if $user->hasPassword()} value="{$user->getGarbledPassword()}"{/if} />
 </div>
 <h4 class="ui block header">Permissions</h4>
 <div class="ui checkbox">
  <label>Manage Chains</label>
  <input type="checkbox" value="Y" name="user_manage_chains" {if $user->doesManage('manage_chains')} checked="checked" {/if} />
 </div>
 <div class="ui checkbox">
  <label>Manage Pipes</label>
  <input type="checkbox" value="Y" name="user_manage_pipes" {if $user->doesManage('manage_pipes')} checked="checked" {/if} />
 </div>
 <div class="ui checkbox">
  <label>Manage Filters</label>
  <input type="checkbox" value="Y" name="user_manage_filters" {if $user->doesManage('manage_filters')} checked="checked" {/if} />
 </div>
 <div class="ui checkbox">
  <label>Manage Ports</label>
  <input type="checkbox" value="Y" name="user_manage_ports" {if $user->doesManage('manage_ports')} checked="checked" {/if} />
 </div>
 <div class="ui checkbox">
  <label>Manage Protocols</label>
  <input type="checkbox" value="Y" name="user_manage_protocols" {if $user->doesManage('manage_protocols')} checked="checked" {/if} />
 </div>
 <div class="ui checkbox">
  <label>Manage Targets</label>
  <input type="checkbox" value="Y" name="user_manage_targets" {if $user->doesManage('manage_targets')} checked="checked" {/if} />
 </div>
 <div class="ui checkbox">
  <label>Manage Users</label>
  <input type="checkbox" value="Y" name="user_manage_users" {if $user->doesManage('manage_users')} checked="checked" {/if} />
 </div>
 <div class="ui checkbox">
  <label>Manage Options</label>
  <input type="checkbox" value="Y" name="user_manage_options" {if $user->doesManage('manage_options')} checked="checked" {/if} />
 </div>
 <div class="ui checkbox">
  <label>Manage Service Levels</label>
  <input type="checkbox" value="Y" name="user_manage_servicelevels" {if $user->doesManage('manage_servicelevels')} checked="checked" {/if} />
 </div>
 <div class="ui checkbox">
  <label>Load &amp; Unload Ruleset</label>
  <input type="checkbox" value="Y" name="user_load_rules" {if $user->doesManage('load_rules')} checked="checked" {/if} />
 </div>
 <div class="ui checkbox">
  <label>Show Ruleset &amp; Overview</label>
  <input type="checkbox" value="Y" name="user_show_rules" {if $user->doesManage('show_rules')} checked="checked" {/if} />
 </div>
 <div class="ui checkbox">
  <label>Show Monitor</label>
  <input type="checkbox" value="Y" name="user_show_monitor" {if $user->doesManage('show_monitor')} checked="checked" {/if} />
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
      location.href = '{get_url page='users'}';
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
      if (!(input = $(this).find('input[name^="user_"], textarea[name^="user_"]'))) {
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
         location.href = '{get_url page='users'}';
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
