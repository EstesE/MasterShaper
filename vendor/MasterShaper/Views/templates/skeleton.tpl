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
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
{include "header.tpl"}
</head>
<body>
{if !$zend_opcache_available}
<div class="ui inline cookie nag zendopcache missing">
 <span class="title">
  Your PHP installation does not seem to use PHP7 OPcache. This can speed up MasterShaper a lot!
 </span>
 <i class="close icon"></i>
</div>
{/if}
{include "menu.tpl"}
 <div class="ui main container">
{$page_content}
 </div>
{include "footer.tpl"}
{include "confirm_modal.tpl"}
{include "progress_modal.tpl"}
</body>
</html>
