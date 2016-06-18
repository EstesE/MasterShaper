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
<div class="ui grid">
 <div class="ten wide column">
  <div class="left aligned container">
   <h1 class="ui header"><img src="{$icon_protocols}" alt="protocol icon" />Protocols</h1>
  </div>
 </div>
 <div class="six wide column">
  <div class="right aligned container">
   <form class="ui form add" onsubmit="return false;" data-target="protocol_add">
    <div class="fields">
     <div class="field">
      <input type="text" name="protocol_add" placeholder="Add protocols" data-action="add" data-model="protocol" data-key="proto_name" data-id="new" tabindex="0" />
     </div>
     <div class="field">
      <button class="circular ui button add" type="submit"><i class="save icon"></i>Add</button>
     </div>
    </div>
   </form>
  </div>
 </div>
</div>
<table class="ui striped single line selectable sortable celled table" id="datatable">
 <thead>
  <tr>
   <th class="no-sort one wide column center aligned">
    <div class="ui fitted checkbox item select" id="select_all">
     <input type="checkbox">
     <label></label>
    </div>
   </th>
   <th>Protocol</th>
   <th class="no-sort three wide column">
    <div class="two column ui grid">
     <div class="column">Actions</div>
     <div class="column right aligned">
      <a id="filterbutton"><i class="filter icon"></i></a>
     </div>
    </div>
   </th>
  </tr>
 </thead>
 <tbody>
{protocols_list}
 <tr>
  <td class="center aligned">
   <div class="ui fitted checkbox item select" id="select_{$item->getIdx()}">
    <input type="checkbox">
    <label></label>
   </div>
  </td>
  <td>
   <div name="protocol_{$item->getIdx()}" class="filterable inline editable content" data-current-value="{$item->getName()}" data-orig-value="{$item->getName()}" style="float: left;">{$item->getName()}</div>&nbsp;
   <a name="protocol_{$item->getIdx()}" class="inline editable edit link" data-inline-name="protocol_{$item->getIdx()}"><i class="tiny edit icon"></i></a>
   <div name="protocol_{$item->getIdx()}" class="inline editable formsrc" style="display: none;">
    <form class="ui form" onsubmit="return false;">
     <div class="fields">
      <div class="field small ui input">
       <input type="text" name="protocol_{$item->getIdx()}" value="{$item->getName()}" data-action="update" data-model="protocol" data-key="proto_name" data-id="{$item->getIdx()}" />
      </div>
      <div class="field">
       <button class="circular ui icon button inline editable save" type="submit"><i class="save icon"></i></button>
      </div>
      <div class="field">
       <button class="circular ui icon button inline editable cancel"><i class="cancel icon"></i></button>
      </div>
     </div>
    </form>
   </div>
  </td>
  <td>
   <div class="ui icon buttons">
    <a id="edit_link_{$item->getIdx()}" href="{get_url page='protocols' mode='edit' id=$item->getSafeLink()}" class="edit item ui icon button action link"><i class="edit icon"></i></a>
    <a id="delete_link_{$item->getIdx()}" class="delete item ui icon button action link" data-action-title="Deleting {$item->getName()|escape}" data-modal-title="Delete {$item->getName()|escape}" data-modal-text="Please confirm to delete {$item->getName()|escape}" data-id="{$item->getIdx()}" data-guid="{$item->getGuid()}" data-model="protocol" data-content="Delete {$item->getName()|escape}" data-variation="wide"><i class="remove circle icon"></i></a>
    <div class="ui icon button slider checkbox item state" data-target="protocol_state_{$item->getIdx()}">
     <div class="ui inverted dimmer">
      <div class="ui loader"></div>
     </div>
     <input name="protocol_state_{$item->getIdx()}" type="checkbox" data-action="update" data-model="protocol" data-key="proto_active" data-id="{$item->getIdx()}" data-guid="{$item->getGuid()}" value="Y" {if $item->isActive()}checked="checked"{/if}/>
     <label></label>
    </div>
   </div>
  </td>
 </tr>
{/protocols_list}
 </tbody>
 <tfoot>
  <tr>
   <th colspan="3">
    <div class="ui left floated borderless small menu">
     <a class="delete item" data-action-title="Deleting selected protocols" data-modal-title="Delete selected protocols" data-modal-text="Do you really want to delete selected protocols?" data-id="selected" data-guid="selected" data-model="protocols"><i class="remove circle icon"></i>Delete selected</a>
     <a class="delete item" data-action-title="Deleting all protocols" data-modal-title="Delete all protocols" data-modal-text="Do you really want to delete all protocols?" data-id="all" data-guid="all" data-model="protocols"><i class="remove circle icon"></i>Delete all</a>
    </div>
{if isset($pager)}
{include file='pager.tpl' pager=$pager view=protocols}
{/if}
   </th>
  </tr>
 </tfoot>
</table>
<script type="text/javascript"><!--

'use strict';

$(document).ready(function () {
   $('a.action.link').popup({
      exclusive: true,
      lastResort: true,
   });
});
--></script>
