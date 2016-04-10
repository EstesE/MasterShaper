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
<h1 class="ui header"><img src="{$icon_options}" alt="options icon" />&nbsp;Options</a></h1>
<div class="ui divider"></div>
<form class="ui form">
 <h4 class="ui block header">General Setttings</h4>
 <div class="field">
  <label>Mode</label>
  <div class="ui radio checkbox" name="mode" data-id="{if $settings->hasSetting('mode')}{$settings->getSettingId('mode')}{else}new{/if}" data-guid="{if $settings->hasSetting('mode')}{$settings->getSettingGuid('mode')}{else}new{/if}" data-model="setting" data-key="setting_key" data-field="setting_value">
   <input type="radio" name="mode" value="router" {if !$settings->hasSetting('mode') || ($settings->hasSetting('mode') && $settings->hasSettingValue('mode') && $settings->getSettingValue('mode') == "router")} checked="checked" {/if}/>
   <label>Router</label>
  </div>
  <div class="ui radio checkbox" name="mode" data-id="{if $settings->hasSetting('mode')}{$settings->getSettingId('mode')}{else}new{/if}" data-guid="{if $settings->hasSetting('mode')}{$settings->getSettingGuid('mode')}{else}new{/if}" data-model="setting" data-key="setting_key" data-field="setting_value">
   <input type="radio" name="mode" value="bridge" {if $settings->hasSetting('mode') && $settings->hasSettingValue('mode') && $settings->getSettingValue('mode') == "bridge"} checked="checked" {/if}/>
   <label>Bridge</label>
  </div>
  <div class="extra">This option tells MasterShaper if it is used on a router (between networks) or on a bridge (transparent in the network). This setting is very important to match network packets on the correct network interfaces.</div>
 </div>
 <div class="field">
  <label>Authentication:</label>
  <div class="ui radio checkbox" name="authentication" data-id="{if $settings->hasSetting('authentication')}{$settings->getSettingId('authentication')}{else}new{/if}" data-guid="{if $settings->hasSetting('authentication')}{$settings->getSettingGuid('authentication')}{else}new{/if}" data-model="setting" data-key="setting_key" data-field="setting_value">
   <input type="radio" name="authentication" value="Y" {if $settings->hasSetting('authentication') && $settings->hasSettingValue('authentication') && $settings->getSettingValue('authentication') == "Y"} checked=checked" {/if} />
   <label>Yes</label>
  </div>
  <div class="ui radio checkbox" name="authentication" data-id="{if $settings->hasSetting('authentication')}{$settings->getSettingId('authentication')}{else}new{/if}" data-guid="{if $settings->hasSetting('authentication')}{$settings->getSettingGuid('authentication')}{else}new{/if}" data-model="setting" data-key="setting_key" data-field="setting_value">
   <input type="radio" name="authentication" value="N" {if !$settings->hasSetting('authentication') || ($settings->hasSetting('authentication') && $settings->hasSettingValue('authentication') && $settings->getSettingValue('authentication') != "Y")} checked=checked" {/if} />
   <label>No</label>
  </div>
  <div class="field">Enable or disable MasterShaper's authentication mechanism. If enabled you can configure user &amp; rights in the webinterface. If disabled, no permission management will be done per MasterShaper and everyone has full control in the webinterface.</div>
 </div>
 <h4 class="ui block header">Quality of Service Settings</h4>
 <div class="field">
  <label>ACK packets:</label>
  <select name="ack_sl" data-id="{if $settings->hasSetting('ack_sl')}{$settings->getSettingId('ack_sl')}{else}new{/if}" data-guid="{if $settings->hasSetting('ack_sl')}{$settings->getSettingGuid('ack_sl')}{else}new{/if}" data-model="setting" data-key="setting_key" data-field="setting_value">
   <option value="0">Ignore</option>
   {service_level_list}
    <option value="{$sl->getId()}" {if $settings->hasSetting('ack_sl') && $settings->hasSettingValue('ack_sl') && $settings->getSettingValue('ack_sl') == $sl->getId()} selected="selected" {/if}>{if $sl->hasName()}{$sl->getName()}{/if}</option>
   {/service_level_list}
  </select>
  <div class="extra">Should ACK- and other small packets (&lt;128byte) get a special service level? This is helpfull if you have a small upload bandwidth. There is no much needing for a high bandwidth for this (ex. 32kbps), but it should have a higher priority then other bulk traffic.<br />Be aware, that this may bypass some packets from later rules because smaller packets get matched here - so the traffic limits may not be strictly enforced.</div>
 </div>
 <div class="field">
  <label>Classifier:</label>
  <select name="classifier" data-id="{if $settings->hasSetting('classifier')}{$settings->getSettingId('classifier')}{else}new{/if}" data-guid="{if $settings->hasSetting('classifier')}{$settings->getSettingGuid('classifier')}{else}new{/if}" data-model="setting" data-key="setting_key" data-field="setting_value">
   <option value="HTB" {if $settings->hasSetting('classifier') && $settings->hasSettingValue('classifier') && $settings->getSettingValue('classifier') == "HTB"} selected="selected" {/if}>HTB</option>
   <option value="HFSC" {if $settings->hasSetting('classifier') && $settings->hasSettingValue('classifier') && $settings->getSettingValue('classifier') == "HFSC"} selected="selected" {/if}>HFSC</option>
  </select>
  <div class="extra">Choose HTB if you want to shape on base of maximum bandwidth rates, traffic bursts. Use HFSC for realtime application where network packets should not be delayed more such a specified value (VoIP).</div>
 </div>
 <div class="field">
  <label>Default Queuing Discipline:</label>
  <select name="qdisc" data-id="{if $settings->hasSetting('qdisc')}{$settings->getSettingId('qdisc')}{else}new{/if}" data-guid="{if $settings->hasSetting('qdisc')}{$settings->getSettingGuid('qdisc')}{else}new{/if}" data-model="setting" data-key="setting_key" data-field="setting_value">
   <option value="SFQ" {if !$settings->hasSetting('qdisc') || ($settings->hasSetting('qdisc') && $settings->hasSettingValue('qdisc') && $settings->getSettingValue('qdisc') == "SFQ") } selected="selected" {/if}>SFQ</option>
   <option value="ESFQ" {if $settings->hasSetting('qdisc') && $settings->hasSettingValue('qdisc') && $settings->getSettingValue('qdisc') == "ESFQ" } selected="selected" {/if}>ESFQ</option>
   <option value="HFSC" {if $settings->hasSetting('qdisc') && $settings->hasSettingValue('qdisc') && $settings->getSettingValue('qdisc') == "HFSC" } selected="selected" {/if}>HFSC</option>
  </select>
  <div class="extra">This specifies the default qdisc for pipes. It's generally not a good idea to mix between different qdiscs. However, MasterShaper supports to specify different qdiscs for pipes.</div>
 </div>

 <div class="ui accordion">
  <div class="title"><i class="dropdown icon"></i>ESFQ Advanced Settings</div>
  <div class="content">
   <div class="field">
    <label>ESFQ Perturb</label>
    <input type="text" name="esfq_default_perturb" data-id="{if $settings->hasSetting('esfq_default_perturb')}{$settings->getSettingId('esfq_default_perturb')}{else}new{/if}" data-guid="{if $settings->hasSetting('esfq_default_perturb')}{$settings->getSettingGuid('esfq_default_perturb')}{else}new{/if}" data-model="setting" data-key="setting_key" data-field="setting_value" value="{if $settings->hasSetting('esfq_default_perturb') && $settings->hasSettingValue('esfq_default_perturb')}{$settings->getSettingValue('esfq_default_perturb')}{else}10{/if}" size="28" />
    <div class="extra">Default ESFQ perturb value. See Service Level for more informations.</div>
   </div>
   <div class="field">
    <label>ESFQ Limit</label>
    <input type="text" name="esfq_default_limit" data-id="{if $settings->hasSetting('esfq_default_limit')}{$settings->getSettingId('esfq_default_limit')}{else}new{/if}" data-guid="{if $settings->hasSetting('esfq_default_limit')}{$settings->getSettingGuid('esfq_default_limit')}{else}new{/if}" data-model="setting" data-key="setting_key" data-field="setting_value" value="{if $settings->hasSetting('esfq_default_limit') && $settings->hasSettingValue('esfq_default_limit')}{$settings->getSettingValue('esfq_default_limit')}{else}128{/if}" size="28" />
    <div class="extra">Default ESFQ limit value. See Service Level for more informations.</div>
   </div>
   <div class="field">
    <label>ESFQ Depth:</label>
    <input type="text" name="esfq_default_depth" data-id="{if $settings->hasSetting('esfq_default_depth')}{$settings->getSettingId('esfq_default_depth')}{else}new{/if}" data-guid="{if $settings->hasSetting('esfq_default_depth')}{$settings->getSettingGuid('esfq_default_depth')}{else}new{/if}" data-model="setting" data-key="setting_key" data-field="setting_value" value="{if $settings->hasSetting('esfq_default_depth') && $settings->hasSettingValue('esfq_default_depth')}{$settings->getSettingValue('esfq_default_depth')}{else}128{/if}" size="28" />
    <div class="extra">Default ESFQ depth value. See Service Level for more informations.</div>
   </div>
   <div class="field">
    <label>ESFQ Divisor</label>
    <input type="text" name="esfq_default_divisor" data-id="{if $settings->hasSetting('esfq_default_divisor')}{$settings->getSettingId('esfq_default_divisor')}{else}new{/if}" data-guid="{if $settings->hasSetting('esfq_default_divisor')}{$settings->getSettingGuid('esfq_default_divisor')}{else}new{/if}" data-model="setting" data-key="setting_key" data-field="setting_value" value="{if $settings->hasSetting('esfq_default_divisor') && $settings->hasSettingValue('esfq_default_divisor')}{$settings->getSettingValue('esfq_default_divisor')}{else}10{/if}" size="28" />
    <div class="extra">Default ESFQ divisor value. See Service Level fore more informations.</div>
   </div>
   <div class="field">
    <label>ESFQ Hash</label>
    <select name="esfq_default_hash" data-id="{if $settings->hasSetting('esfq_default_hash')}{$settings->getSettingId('esfq_default_hash')}{else}new{/if}" data-guid="{if $settings->hasSetting('esfq_default_hash')}{$settings->getSettingGuid('esfq_default_hash')}{else}new{/if}" data-model="setting" data-key="setting_key" data-field="setting_value">
     <option value="classic" {if !$settings->hasSetting('esfq_default_hash') || ($settings->hasSetting('esfq_default_hash') && $settings->hasSettingValue('esfq_default_hash') && $settings->getSettingValue('esfq_default_hash') == "classic") } selected="selected" {/if}>Classic</option>
     <option value="src" {if $settings->hasSetting('esfq_default_hash') && $settings->hasSettingValue('esfq_default_hash') && $settings->getSettingValue('esfq_default_hash') == "src"} selected="selected" {/if}>Src</option>
     <option value="dst" {if $settings->hasSetting('esfq_default_hash') && $settings->hasSettingValue('esfq_default_hash') && $settings->getSettingValue('esfq_default_hash') == "dst"} selected="selected" {/if}>Dst</option>
     <option value="fwmark" {if $settings->hasSetting('esfq_default_hash') && $settings->hasSettingValue('esfq_default_hash') && $settings->getSettingValue('esfq_default_hash') == "fwmark"} selected="selected" {/if}>Fwmark</option>
     <option value="src_direct" {if $settings->hasSetting('esfq_default_hash') && $settings->hasSettingValue('esfq_default_hash') && $settings->getSettingValue('esfq_default_hash') == "src_direct"} selected="selected" {/if}>Src_direct</option>
     <option value="dst_direct" {if $settings->hasSetting('esfq_default_hash') && $settings->hasSettingValue('esfq_default_hash') && $settings->getSettingValue('esfq_default_hash') == "dst_direct"} selected="selected" {/if}>Dst_direct</option>
     <option value="fwmark_direct" {if $settings->hasSetting('esfq_default_hash') && $settings->hasSettingValue('esfq_default_hash') && $settings->getSettingValue('esfq_default_hash') == "fwmark_direct"} selected="selected" {/if}>Fwmark_direct</option>
    </select>
    <div class="extra">Default ESFQ hash. See Service Level fore more informations.</div>
   </div>
  </div>
 </div>

 <h4 class="ui block header">Quality of Service Settings</h4>
 <div class="field">
  <label>Traffic filter</label>
  <div class="ui radio checkbox" name="filter" data-id="{if $settings->hasSetting('filter')}{$settings->getSettingId('filter')}{else}new{/if}" data-guid="{if $settings->hasSetting('filter')}{$settings->getSettingGuid('filter')}{else}new{/if}" data-model="setting" data-key="setting_key" data-field="setting_value">
   <input type="radio" name="filter" value="tc" {if !$settings->hasSetting('filter') || ($settings->hasSetting('filter') && $settings->hasSettingValue('filter') && $settings->getSettingValue('filter') == "tc") } checked="checked" {/if} />
   <label>tc-filter</label>
  </div>
  <div class="ui radio checkbox" name="filter" data-id="{if $settings->hasSetting('filter')}{$settings->getSettingId('filter')}{else}new{/if}" data-guid="{if $settings->hasSetting('filter')}{$settings->getSettingGuid('filter')}{else}new{/if}" data-model="setting" data-key="setting_key" data-field="setting_value">
    <input type="radio" name="filter" value="ipt" {if $settings->hasSetting('filter') && $settings->hasSettingValue('filter') && $settings->getSettingValue('filter') == "ipt"} checked="checked" {/if} />
    <label>iptables</label>
  </div>
  <div class="extra">Mechanism which filters your traffic. tc-filter is the tc-builtin filter technic. Good performance, but less options. iptables has many options for matching traffic. But this will add a second needed subsystem for shaping. Make tests if your Linux machine is powerful enough for this.</div>
 </div>

 <div class="ui accordion">
  <div class="title"><i class="dropdown icon"></i>TC Advanced Settings</div>
  <div class="content">
   <div class="field">
    <label>Hashkey:</label>
    <div class="ui checkbox" name="use_hashkey" data-id="{if $settings->hasSetting('use_hashkey')}{$settings->getSettingId('use_hashkey')}{else}new{/if}" data-guid="{if $settings->hasSetting('use_hashkey')}{$settings->getSettingGuid('use_hashkey')}{else}new{/if}" data-model="setting" data-key="setting_key" data-field="setting_value">
     <input type="checkbox" name="use_hashkey" value="Y" {if $settings->hasSetting('use_hashkey') && $settings->hasSettingValue('use_hashkey') && $settings->getSettingValue('use_hashkey') == "Y"}checked="checked"{/if} />
     <label>Use Hashkey</label>
    </div>
   </div>
   <div class="field">
    <label>IP</label>
    <input type="text" name="hashkey_ip" data-id="{if $settings->hasSetting('hashkey_ip')}{$settings->getSettingId('hashkey_ip')}{else}new{/if}" data-guid="{if $settings->hasSetting('hashkey_ip')}{$settings->getSettingGuid('hashkey_ip')}{else}new{/if}" data-model="setting" data-key="setting_key" data-field="setting_value" value="{if $settings->hasSetting('hashkey_ip') && $settings->hasSettingValue('hashkey_ip')}{$settings->getSettingValue('hashkey_ip')}{else}10.0.0.0{/if}" />
   </div>
   <div class="field">
    <label>Mask</label>
    <select name="hashkey_mask" data-id="{if $settings->hasSetting('hashkey_mask')}{$settings->getSettingId('hashkey_mask')}{else}new{/if}" data-guid="{if $settings->hasSetting('hashkey_mask')}{$settings->getSettingGuid('hashkey_mask')}{else}new{/if}" data-model="setting" data-key="setting_key" data-field="setting_value">
     <option {if !$settings->hasSetting('hashkey_mask') || ($settings->hasSetting('hashkey_mask') && $settings->hasSettingValue('hashkey_mask') && $settings->getSettingValue('hashkey_mask') == "255.0.0.0") }selected="selected"{/if}>255.0.0.0</option>
     <option {if $settings->hasSetting('hashkey_mask') && $settings->hasSettingValue('hashkey_mask') && $settings->getSettingValue('hashkey_mask') == "0.255.0.0"}selected="selected"{/if}>0.255.0.0</option>
     <option {if $settings->hasSetting('hashkey_mask') && $settings->hasSettingValue('hashkey_mask') && $settings->getSettingValue('hashkey_mask') == "0.0.255.0"}selected="selected"{/if}>0.0.255.0</option>
     <option {if $settings->hasSetting('hashkey_mask') && $settings->hasSettingValue('hashkey_mask') && $settings->getSettingValue('hashkey_mask') == "0.0.0.255"}selected="selected"{/if}>0.0.0.255</option>
    </select>
   </div>
   <div class="field">
    <label>On</label>
    <select name="hashkey_matchon" data-id="{if $settings->hasSetting('hashkey_matchon')}{$settings->getSettingId('hashkey_matchon')}{else}new{/if}" data-guid="{if $settings->hasSetting('hashkey_matchon')}{$settings->getSettingGuid('hashkey_matchon')}{else}new{/if}" data-model="setting" data-key="setting_key" data-field="setting_value">
     <option value="src" {if !$settings->hasSetting('hashkey_matchon') || ($settings->hasSetting('hashkey_matchon') && $settings->hasSettingValue('hashkey_matchon') && $settings->getSettingValue('hashkey_matchon') == "src") }selected="selected"{/if}>IF1: src, IF2: dst</option>
     <option value="dst" {if $settings->hasSetting('hashkey_matchon') && $settings->hasSettingValue('hashkey_matchon') && $settings->getSettingValue('hashkey_matchon') == "dst"}selected="selected"{/if}>IF1: dst, IF2: src</option>
    </select>
    <div class="extra">10.0.0.0/8<br />00ff0000<br />Remember that "Targets" hashkey match on must only be ONE.</div>
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
      location.href = '{get_url page='overview'}';
   });
   $('.ui.button.save').click(function () {
      $(this).popup('hide')
         .find('.ui.inverted.dimmer').addClass('active');
   });
   $('.ui.form').submit(function () {
      var input;

      if (typeof mbus === 'undefined') {
         throw new Error('MessageBus is not available!');
         return false;
      }

      if (!(input = $(this).find('.ui.checkbox, input[type="text"], textarea, select'))) {
         throw new Error('failed to locate any form elements!');
         return false;
      }

      input.each (function (index, element) {
         var id, guid, model, name, key, field, values;

         values = new Object;

         if (typeof (element = $(element)) === 'undefined') {
            throw new Error('failed to retrieve jQuery object on element!');
            return false;
         }

         if (typeof (name = element.attr('name')) === 'undefined') {
            throw new Error('failed to locate name attribute!');
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
         if (!(key = $(this).attr('data-key'))) {
            throw new Error('failed to locate data-key attribute!');
            return false;
         }
         if (!(field = $(this).attr('data-field'))) {
            throw new Error('failed to locate data-field attribute!');
            return false;
         }

         values[key] = name;
         if (element.prop('nodeName') === 'INPUT') {
            if (element.attr('type') === 'text') {
               values[field] = element.val();
            } else if (element.attr('type') === 'checkbox') {
               if (element.is(':checked')) {
                  values[field] = element.val();
               }
            } else if (element.attr('type') === 'radio') {
               if (element.is(':checked')) {
                  values[field] = element.val();
               }
            } else {
               throw new Error('unsupported type! ' + element.attr('type'));
               return false;
            }
         } else if (element.prop('nodeName') === 'TEXTAREA') {
            values[field] = element.text();
         } else if (element.prop('nodeName') === 'SELECT') {
            // document.forms['servicelevels'].classifier.options[document.forms['servicelevels'].classifier.selectedIndex].value
            values[field] = element.val();
         } else if (element.prop('nodeName') === 'DIV' && element.is('.ui.checkbox')) {
            // if it is a radio element, skip it, if it is not checked
            if (element.checkbox('is radio') && !element.checkbox('is checked')) {
               return true;
            } else if (!element.checkbox('is checked')) {
               values[field] = 'N';
            } else if (typeof (values[field] = element.find('input[type="radio"],input[type="checkbox"]').val()) === 'undefined') {
               throw new Error('failed to read radio element value!');
               return false;
            }
         } else {
            throw new Error('unsupported nodeName!');
            return false;
         }

         if (typeof values[field] === 'undefined') {
            value[field] = '';
         }

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

         return true;
      });

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
         location.href = '{get_url page='options'}';
         return true;
      }.bind(this));


      if (!mbus.send()) {
         throw 'ThalliumMessageBus.send() returned false!';
         return false;
      }
      return false;
   });

   return true;
});
</script>
