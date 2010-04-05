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
