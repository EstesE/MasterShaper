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
<div class="ui right floated pagination borderless small menu">
{if isset($pager) && $pager->getNumberOfPages() > 1}
{if $pager->getFirstPageNumber()}
 <a class="pager icon item" href="{get_url page=$view number=$pager->getFirstPageNumber()}" data-content="Go to first page"><i class="angle double left icon"></i></a>
{/if}
{if $pager->getPreviousPageNumber()}
 <a class="pager icon item" href="{get_url page=$view number=$pager->getPreviousPageNumber()}" data-content="Go to previous page"><i class="angle left icon"></i></a>
{/if}
{foreach $pager->getDeltaPageNumbers() as $pageno}
 <a class="pager item {if $pager->isCurrentPage($pageno)}active{/if}" href="{get_url page=$view number=$pageno}" data-content="Go to page {$pageno}">{$pageno}</a>
{/foreach}
{if $pager->getNextPageNumber()}
 <a class="pager icon item" href="{get_url page=$view number=$pager->getNextPageNumber()}" data-content="Go to next page"><i class="angle right icon"></i></a>
{/if}
{if $pager->getLastPageNumber()}
 <a class="pager icon item" href="{get_url page=$view number=$pager->getLastPageNumber()}" data-content="Go to last page"><i class="angle double right icon"></i></a>
{/if}
 <div class="item inactive">Page:</div>
 <div class="item">
  <div class="ui compact search selection dropdown" id="pager_page_select">
   <input type="hidden" name="pagergoto" value="{$pager->getCurrentPage()}">
   <i class="dropdown icon"></i>
   <div class="default text">Goto page:</div>
   <div class="menu">
{foreach $pager->getPageNumbers() as $pageno}
    <div class="item {if $pager->isCurrentPage($pageno)}active{/if}" data-value="{get_url page=$view number=$pageno}">{$pageno}</div>
{/foreach}
   </div>
  </div>
 </div>
{/if}
 <div class="item inactive">Items:</div>
 <div class="item">
  <div class="ui compact search selection dropdown" id="pager_items_select">
   <input type="hidden" name="itemsgoto" value="{$pager->getCurrentItemsLimit()}">
   <i class="dropdown icon"></i>
   <div class="default text">Items per page:</div>
   <div class="menu">
{foreach $pager->getItemsLimits() as $item_cnt}
    <div class="item{if $pager->isCurrentItemsLimit($item_cnt)} active{/if}" data-value="{get_url page=$view items_per_page=$item_cnt}">{if $item_cnt == 0}all{else}{$item_cnt}{/if}</div>
{/foreach}
   </div>
  </div>
 </div>
</div>
<script type="text/javascript"><!--

'use strict';

$(document).ready(function () {
   $('#pager_page_select, #pager_items_select').dropdown({
      match: 'text',
      onChange : function(value, text, choice) {
         if (typeof value === 'undefined' || value == "") {
            return false;
         }
         window.location = value;
      }
   });
   $('a.pager').popup({
      exclusive: true,
      lastResort: true,
   });
});
--></script>
