<!--
 ### main menu
-->
<div class="main_menu">
 <!-- the name rootVoice is hardcoded in mbMenu, it must be named like _this_ -->
 <table class="rootVoice">
  <tr>
   <td onmouseover="setBackGrdColor(this, 'mouseover');" onmouseout="setBackGrdColor(this, 'mouseout');" class="rootVoice {literal}{menu: 'empty'}{/literal}" onclick="location.href='{$rewriter->get_page_url('Overview')}';">
    <img src="{$icon_home}" />&nbsp;Overview
   </td>
   <td onmouseover="setBackGrdColor(this, 'mouseover');" onmouseout="setBackGrdColor(this, 'mouseout');" class="rootVoice {literal}{menu: 'menu_manage'}{/literal}">
    <img src="{$icon_arrow_left}" />&nbsp;Manage<img src="{$icon_menu_down}" />
   </td>
   <td onmouseover="setBackGrdColor(this, 'mouseover');" onmouseout="setBackGrdColor(this, 'mouseout');" class="rootVoice {literal}{menu: 'menu_settings'}{/literal}">
    <img src="{$icon_arrow_right}" />&nbsp;Settings<img src="{$icon_menu_down}" />
   </td>
   <td onmouseover="setBackGrdColor(this, 'mouseover');" onmouseout="setBackGrdColor(this, 'mouseout');" class="rootVoice {literal}{menu: 'menu_monitoring'}{/literal}">
    <img src="{$icon_monitor}" />&nbsp;Monitoring<img src="{$icon_menu_down}" />
   </td>
   <td onmouseover="setBackGrdColor(this, 'mouseover');" onmouseout="setBackGrdColor(this, 'mouseout');" class="rootVoice {literal}{menu: 'menu_rules'}{/literal}">
    <img src="{$icon_arrow_right}" />&nbsp;Rules<img src="{$icon_menu_down}" />
   </td>
   <td onmouseover="setBackGrdColor(this, 'mouseover');" onmouseout="setBackGrdColor(this, 'mouseout');" class="rootVoice {literal}{menu: 'menu_others'}{/literal}">
    <img src="{$icon_arrow_right}" />&nbsp;Others<img src="{$icon_menu_down}" />
   </td>
  </tr>
 </table>
</div>
<!--
 ### manage menu
-->
<div id="menu_manage" class="menu">
 <li>
  <a class="{ldelim}action: 'location.href=\'{$rewriter->get_page_url('Chains List')}\''{rdelim}">
   <img src="{$icon_chains}" />&nbsp;Chains<br />
   <div class="menu_help">add, modify and delete chains</div>
  </a>
 </li>
 <li>
  <a class="{ldelim}action: 'location.href=\'{$rewriter->get_page_url('Filters List')}\''{rdelim}">
   <img src="{$icon_filters}" />&nbsp;Filters<br />
   <div class="menu_help">add, modify and delete filters</div>
  </a>
 </li>
 <li>
  <a class="{ldelim}action: 'location.href=\'{$rewriter->get_page_url('Pipes List')}\''{rdelim}">
   <img src="{$icon_pipes}" />&nbsp;Pipes<br />
   <div class="menu_help">add, modify and delete filters,<br />mass-assign pipes to chains</div>
  </a>
 </li>
</div>
<!--
 ### monitoring menu
-->
<div id="menu_monitoring" class="menu">
 <li>
  <a class="{ldelim}action: 'location.href=\'{$rewriter->get_page_url('Monitoring Chains')}\''{rdelim}">
   <img src="{$icon_chains}" />&nbsp;Chains<br />
   <div class="menu_help">bandwidth usage by all chains</div>
  </a>
 </li>
 <li>
  <a class="{ldelim}action: 'location.href=\'{$rewriter->get_page_url('Monitoring Pipes')}\''{rdelim}">
   <img src="{$icon_pipes}" />&nbsp;Pipes<br />
   <div class="menu_help">bandwidth usage per chain</div>
  </a>
 </li>
 <li>
  <a class="{ldelim}action: 'location.href=\'{$rewriter->get_page_url('Monitoring Bandwidth')}\''{rdelim}">
   <img src="{$icon_bandwidth}" />&nbsp;Bandwidth<br />
   <div class="menu_help">bandwidth usage by all chains</div>
  </a>
 </li>
</div>
<!--
 ### others menu
-->
<div id="menu_others" class="menu">
 <li>
  <a class="{ldelim}action: 'location.href=\'{$rewriter->get_page_url('Others Update IANA')}\''{rdelim}">
   <img src="{$icon_rules_update}" />&nbsp;Update Ports &amp; Protocols<br />
   <div class="menu_help">update list of IANA-assigned ports and protocols</div>
  </a>
 </li>
 <li>
  <a class="{ldelim}action: 'location.href=\'{$rewriter->get_page_url('Others Update L7')}\''{rdelim}">
   <img src="{$icon_rules_update}" />&nbsp;Update L7 Protocols<br />
   <div class="menu_help">update internal list of known L7-filter patterns</div>
  </a>
 </li>
 <li>
  <a class="{ldelim}action: 'location.href=\'http://www.mastershaper.org/MasterShaper_documentation.pdf\''{rdelim}">
   <img src="{$icon_pdf}" />&nbsp;Documentation<br />
   <div class="menu_help">PDF document on mastershaper.org</div>
  </a>
 </li>
 <li>
  <a class="{ldelim}action: 'location.href=\'{$rewriter->get_page_url('Others About')}\''{rdelim}">
   <img src="{$icon_users}" />&nbsp;About<br />
   <div class="menu_help">general info and credits</div>
  </a>
 </li>
</div>
<!--
 ### rules menu
-->
<div id="menu_rules" class="menu">
 <li>
  <a class="{ldelim}action: 'location.href=\'{$rewriter->get_page_url('Rules Show')}\''{rdelim}">
   <img src="{$icon_rules_show}" />&nbsp;Show<br />
   <div class="menu_help">display result of generated ruleset commands</div>
  </a>
 </li>
 <li>
  <a class="{ldelim}action: 'location.href=\'{$rewriter->get_page_url('Rules Load')}\''{rdelim}">
   <img src="{$icon_rules_load}" />&nbsp;Load<br />
   <div class="menu_help">batch load ruleset into system (fast)</div>
  </a>
 </li>
 <li>
  <a class="{ldelim}action: 'location.href=\'{$rewriter->get_page_url('Rules Load Debug')}\''{rdelim}">
   <img src="{$icon_rules_load}" />&nbsp;Load (debug)<br />
   <div class="menu_help">load ruleset rule-by-rule into system (slow)</div>
  </a>
 </li>
 <li>
  <a class="{ldelim}action: 'location.href=\'{$rewriter->get_page_url('Rules Unload')}\''{rdelim}">
   <img src="{$icon_rules_unload}" />&nbsp;Unload<br />
   <div class="menu_help">stop shapping</div>
  </a>
 </li>
</div>
<!--
 ### settings menu
-->
<div id="menu_settings" class="menu">
 <li>
  <a class="{ldelim}action: 'location.href=\'{$rewriter->get_page_url('Targets List')}\''{rdelim}">
   <img src="{$icon_targets}" />&nbsp;Targets<br />
   <div class="menu_help">add matches for IP addresses, subnets, MAC, ...</div>
  </a>
 </li>
 <li>
  <a class="{ldelim}action: 'location.href=\'{$rewriter->get_page_url('Ports List')}\''{rdelim}">
   <img src="{$icon_ports}" />&nbsp;Ports<br />
   <div class="menu_help">add or modify TCP/UDP port list</div>
  </a>
 </li>
 <li>
  <a class="{ldelim}action: 'location.href=\'{$rewriter->get_page_url('Protocols List')}\''{rdelim}">
   <img src="{$icon_protocols}" />&nbsp;Protocols<br />
   <div class="menu_help">add or modify protocol list</div>
  </a>
 </li>
 <li>
  <a class="{ldelim}action: 'location.href=\'{$rewriter->get_page_url('Service Levels List')}\''{rdelim}">
   <img src="{$icon_servicelevels}" />&nbsp;Service Levels<br />
   <div class="menu_help">bandwidth control class</div>
  </a>
 </li>
 <li>
  <a class="{ldelim}action: 'location.href=\'{$rewriter->get_page_url('Options')}\''{rdelim}">
   <img src="{$icon_options}" />&nbsp;Options<br />
   <div class="menu_help">general MasterShaper options</div>
  </a>
 </li>
 <li>
  <a class="{ldelim}action: 'location.href=\'{$rewriter->get_page_url('Users List')}\''{rdelim}">
   <img src="{$icon_users}" />&nbsp;Users<br />
   <div class="menu_help">add or modify MasterShaper users</div>
  </a>
 </li>
 <li>
  <a class="{ldelim}action: 'location.href=\'{$rewriter->get_page_url('Interfaces List')}\''{rdelim}">
   <img src="{$icon_interfaces}" />&nbsp;Interfaces<br />
   <div class="menu_help">add or modify network interfaces</div>
  </a>
 </li>
 <li>
  <a class="{ldelim}action: 'location.href=\'{$rewriter->get_page_url('Network Paths List')}\''{rdelim}">
   <img src="{$icon_interfaces}" />&nbsp;Network Paths<br />
   <div class="menu_help">form interfaces into network-paths</div>
  </a>
 </li>
 <li>
  <a class="{ldelim}action: 'location.href=\'{$rewriter->get_page_url('Host Profiles List')}\''{rdelim}">
   <img src="{$icon_hosts}" />&nbsp;Host Profiles<br />
   <div class="menu_help">add or modify host profiles</div>
  </a>
 </li>
</div>
