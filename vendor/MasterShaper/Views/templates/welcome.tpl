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
<div style="padding-left: 20px; padding-top: 20px;">
There is no active <img src="{$icon_interfaces}" title="network path icon" />&nbsp;<a href="{get_url page='Network Paths List'}" title="List Network Paths">Network Path</a> in this configuration!<br />
<br />
If you are entering <img src="{$icon_users}" alt="mastershaper icon" />&nbsp;<a href="{get_url page='Others About'}" title="About MasterShaper">MasterShaper</a> for the first time:<br />
<ul>1. Define <img src="{$icon_interfaces}" alt="interface icon" />&nbsp;<a href="{get_url page='Interfaces List'}" title="List Interfaces">Interfaces</a></ul>
<ul>2. Define a <img src="{$icon_interfaces}" alt="network path icon" />&nbsp;<a href="{get_url page='Network Paths List'}" title="List Network Paths">Network Path</a>&nbsp;between your <img src="{$icon_interfaces}" alt="interface icon" />&nbsp;<a href="{get_url page='Interfaces List'}" title="List Interfaces">Interfaces</a></ul>
<ul>3. Define <img src="{$icon_servicelevels}" alt="service level icon" />&nbsp;<a href="{get_url page='Service Levels List'}" title="List Service Levels">Service Levels</a> to guarantee, limit or priorize your traffic.</ul>
<ul>4. Create <img src="{$icon_filters}" alt="filter icon" />&nbsp;<a href="{get_url page='Filters List'}" title="List Filters">Filters</a> to match on ports, protocols, DSCP bits, ...</ul>
<ul>5. Create <img src="{$icon_pipes}" alt="pipe icon" />&nbsp;<a href="{get_url page='Pipes List'}" title="List Pipes">Pipes</a> and assign <img src="{$icon_filters}" alt="filter icon" />&nbsp;<a href="{get_url page='Filters List'}" title="List Filters">Filters</a> to it to channelize your traffic.</ul>
<ul>6. Create <img src="{$icon_chains}" alt="chain icon" />&nbsp;<a href="{get_url page='Chains List'}" title="List Chains">Chains</a>, assign <img src="{$icon_pipes}" alt="pipe icon" />&nbsp;<a href="{get_url page='Pipes List'}" title="List Pipes">Pipes</a> to it and attach it to a <img src="{$icon_interfaces}" alt="network path icon" />&nbsp;<a href="{get_url page='Network Paths List'}" title="List Network Paths">Network Path</a></ul>
<ul>7. Go and <img src="{$icon_rules_load}" alt="rules icon" />&nbsp;<a href="{get_url page='Rules Load'}" title="Load Ruleset">load</a> your ruleset!</ul>
</div>
