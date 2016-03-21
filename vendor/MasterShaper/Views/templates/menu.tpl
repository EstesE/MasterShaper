<div class="ui teal inverted secondary fixed main menu">
<!--<div class="ui teal inverted fixed menu">-->
 <div class="header brand item">
  <a href="{get_url page=overview}"><i class="gamepad icon"></i>MasterShaper</a>
 </div>
 <a href="{get_url page=overview}" class="item {get_menu_state page=overview}"><i class="database icon"></i>Overview</a>
 <div class="ui dropdown item">
  Manage<i class="dropdown icon"></i>
  <div class="menu">
   <a href="{get_url page='Chains List'}" class="item {get_menu_state page='Chains List'}"><img src="{$icon_chains}" />&nbsp;Chains</a>
   <a href="{get_url page='Filters List'}" class="item {get_menu_state page='Filters List'}"><img src="{$icon_filters}" />&nbsp;Filters</a>
   <a href="{get_url page='Pipes List'}" class="item {get_menu_state page='Pipes List'}"><img src="{$icon_pipes}" />&nbsp;Pipes</a>
  </div>
 </div>
 <div class="ui dropdown item">
  Settings<i class="dropdown icon"></i>
  <div class="menu">
   <a href="{get_url page='Targets List'}" class="item {get_menu_state page='Targets List'}"><img src="{$icon_targets}" />&nbsp;Targets</a>
   <!--<div class="menu_help">add matches for IP addresses, subnets, MAC, ...</div>-->
   <a href="{get_url page='Ports List'}" class="item {get_menu_state page='Ports List'}"><img src="{$icon_ports}" />&nbsp;Ports</a>
   <!--<div class="menu_help">add or modify TCP/UDP port list</div>-->
   <a href="{get_url page='Protocols List'}" class="item {get_menu_state page='Protocols List'}"><img src="{$icon_protocols}" />&nbsp;Protocols</a>
   <!--<div class="menu_help">add or modify protocol list</div>-->
   <a href="{get_url page='Service Levels List'}" class="item {get_menu_state page='Service Levels List'}"><img src="{$icon_servicelevels}" />&nbsp;Service Levels</a>
   <!--<div class="menu_help">bandwidth control class</div>-->
   <a href="{get_url page='Options'}" class="item {get_menu_state page='Options'}"><img src="{$icon_options}" />&nbsp;Options</a>
   <!--<div class="menu_help">general MasterShaper options</div>-->
   <a href="{get_url page='Users List'}" class="item {get_menu_state page='Users List'}"><img src="{$icon_users}" />&nbsp;Users</a>
   <!--<div class="menu_help">add or modify MasterShaper users</div>-->
   <a href="{get_url page='Interfaces List'}" class="item {get_menu_state page='Interfaces List'}"><img src="{$icon_interfaces}" />&nbsp;Interfaces</a>
   <!--<div class="menu_help">add or modify network interfaces</div>-->
   <a href="{get_url page='Network Paths List'}" class="item {get_menu_state page='Network Paths List'}"><img src="{$icon_interfaces}" />&nbsp;Network Paths</a>
   <!--<div class="menu_help">form interfaces into network-paths</div>-->
   <a href="{get_url page='Host Profiles List'}" class="item {get_menu_state page='Host Profiles List'}"><img src="{$icon_hosts}" />&nbsp;Host Profiles</a>
   <!--<div class="menu_help">add or modify host profiles</div>-->
  </div>
 </div>
 <div class="ui dropdown item">
  Monitoring<i class="dropdown icon"></i>
  <div class="menu">
   <a href="{get_url page='Monitoring Chains'}" class="item {get_menu_state page='Monitoring Chains'}"><img src="{$icon_chains}" />&nbsp;Chains</a>
   <!--<div class="menu_help">bandwidth usage by all chains</div>-->
   <a href="{get_url page='Monitoring Pipes'}" class="item {get_menu_state page='Monitoring Pipes'}"><img src="{$icon_pipes}" />&nbsp;Pipes</a>
   <!--<div class="menu_help">bandwidth usage per chain</div>-->
   <a href="{get_url page='Monitoring Bandwidth'}" class="item {get_menu_state page='Monitoring Bandwidth'}"><img src="{$icon_bandwidth}" />&nbsp;Bandwidth</a>
   <!--<div class="menu_help">bandwidth usage by all chains</div>-->
  </div>
 </div>
 <div class="ui dropdown item">
  Rules<i class="dropdown icon"></i>
  <div class="menu">
   <a href="{get_url page='Rules Show'}" class="item {get_menu_state page='Rules Show'}"><img src="{$icon_rules_show}" />&nbsp;Show</a>
   <!--<div class="menu_help">display result of generated ruleset commands</div>-->
   <a href="{get_url page='Rules Load'}" class="item {get_menu_state page='Rules Load'}"><img src="{$icon_rules_load}" />&nbsp;Load</a>
   <!--<div class="menu_help">batch load ruleset into system (fast)</div>-->
   <a href="{get_url page='Rules Load Debug'}" class="item {get_menu_state page='Rules Load Debug'}"><img src="{$icon_rules_load}" />&nbsp;Load (debug)</a>
   <!--<div class="menu_help">load ruleset rule-by-rule into system (slow)</div>-->
   <a href="{get_url page='Rules Unload'}" class="item {get_menu_state page='Rules Unload'}"><img src="{$icon_rules_unload}" />&nbsp;Unload</a>
   <!--<div class="menu_help">stop shapping</div>-->
  </div>
 </div>
 <div class="ui dropdown item">
  Others<i class="dropdown icon"></i>
  <div class="menu">
   <a href="{get_url page='Others Update IANA'}" class="item {get_menu_state page='Others Update IANA'}"><img src="{$icon_rules_update}" />&nbsp;Update Ports &amp; Protocols</a>
   <!--<div class="menu_help">update list of IANA-assigned ports and protocols</div>-->
   <a href="{get_url page='Others Update L7'}" class="item {get_menu_state page='Others Update L7'}"><img src="{$icon_rules_update}" />&nbsp;Update L7 Protocols</a>
   <!--<div class="menu_help">update internal list of known L7-filter patterns</div>-->
   <a href="http://www.mastershaper.org" class="item" target="_blank">mastershaper.org</a>
   <!--<div class="menu_help">PDF document on mastershaper.org</div>-->
   <a href="{get_url page='About'}" class="item {get_menu_state page='About'}"><img src="{$icon_users}" />&nbsp;About</a>
   <!--<div class="menu_help">general info and credits</div>-->
  </div>
 </div>
 <div class="right menu container">
  <div class="item">
   <a href="logout.html" class="item">Logout</a>
  </div>
  <div class="item">
   <form class="ui form search" method="POST" action="{get_url page=search}">
    <div class="ui icon input">
     <input type="text" name="search" placeholder="Search...">
     <i class="search link icon"></i>
    </div>
   </form>
  </div>
 </div>
</div>
<script type="text/javascript"><!--
$(document).ready(function () {
   $('.ui.main.menu .ui.dropdown.item').dropdown({
      on: 'hover'
   });
});
--></script>
