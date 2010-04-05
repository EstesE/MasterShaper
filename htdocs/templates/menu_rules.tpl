<div id="menu_rules" class="menu">
 <li>
  <a class="{ldelim}action: 'location.href=\'{$rewriter->get_page_url('Rules Show')}\''{rdelim}">
   <img src="{$icon_rules_show}" />&nbsp;Show<br />
   <div class="menu_help">display result of generated ruleset commands</div>
  </a>
 <li>
  <a class="{ldelim}action: 'location.href=\'{$rewriter->get_page_url('Rules Load')}\''{rdelim}">
   <img src="{$icon_rules_load}" />&nbsp;Load<br />
   <div class="menu_help">batch load ruleset into system (fast)</div>
  </a>
 <li>
  <a class="{ldelim}action: 'location.href=\'{$rewriter->get_page_url('Rules Load Debug')}\''{rdelim}">
   <img src="{$icon_rules_load}" />&nbsp;Load (debug)<br />
   <div class="menu_help">load ruleset rule-by-rule into system (slow)</div>
  </a>
 <li>
  <a class="{ldelim}action: 'location.href=\'{$rewriter->get_page_url('Rules Unload')}\''{rdelim}">
   <img src="{$icon_rules_unload}" />&nbsp;Unload<br />
   <div class="menu_help">stop shapping</div>
  </a>
</div>
