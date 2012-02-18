<pre id="target"></pre>
<form action="{$page->uri}" id="pipes" onsubmit="selectAll('used[]');" method="post">
<input type="hidden" name="module" value="pipe" />
<input type="hidden" name="action" value="store" />
{ if !$pipe->pipe_idx }
 {start_table icon=$icon_pipes alt="pipe icon" title="Create a new Pipe" }
 <input type="hidden" name="new" value="1" />
{ else }
 {start_table icon=$icon_pipes alt="pipe icon" title="Modify pipe `$pipe->pipe_name`" }
 <input type="hidden" name="new" value="0" />
 <input type="hidden" name="pipe_idx" value="{ $pipe->pipe_idx }" />
{ /if }
<table style="width: 100%;" class="withborder2">
 <tr>
  <td colspan="3">
   <img src="{ $icon_pipes }" alt="pipe icon" />&nbsp;General
  </td>
 </tr>
 <tr>
  <td>Name:</td>
  <td><input type="text" name="pipe_name" size="30" value="{ $pipe->pipe_name }" /></td>
  <td>Specify a name for the pipe.</td>
 </tr>
 <tr>
  <td>Status:</td>
  <td>
   <input type="radio" name="pipe_active" value="Y" { if $pipe->pipe_active == "Y" } checked="checked" { /if } />Active
   <input type="radio" name="pipe_active" value="N" { if $pipe->pipe_active != "Y" } checked="checked" { /if } />Inactive
  </td>
  <td>With this option the status of this chain is specified. Disabled pipes are ignored when reloading the ruleset.</td>
 </tr>
 <tr>
  <td colspan="3">
   <img src="{ $icon_pipes }" alt="pipe icon" />&nbsp;Parameters
  </td>
 </tr>
 <tr>
  <td style="white-space: nowrap;">
   Target:
  </td>
  <td>
   <table class="noborder">
    <tr>
     <td>Source
      <img class="change_to" src="{$icon_arrow_right}" value="Go" onclick="change_to('{$rewriter->get_page_url('Target Edit', 0)}', $('select[name=pipe_src_target]').val());" />
     </td>
     <td>&nbsp;</td>
     <td style="text-align: right;">Destination
      <img class="change_to" src="{$icon_arrow_right}" value="Go" onclick="change_to('{$rewriter->get_page_url('Target Edit', 0)}', $('select[name=pipe_dst_target]').val());" />
     </td>
    </tr>
    <tr>
     <td>
      <select name="pipe_src_target">
       <option value="0">any</option>
       { target_select_list target_idx=$pipe->pipe_src_target }
      </select>
     </td>
     <td>
      <select name="pipe_direction">
       <option value="1" { if $pipe->pipe_direction == 1 } selected="selected" { /if }>--&gt;</option>
       <option value="2" { if $pipe->pipe_direction == 2 } selected="selected" { /if }>&lt;-&gt;</option>
      </select>
     </td>
     <td>
      <select name="pipe_dst_target">
       <option value="0">any</option>
       { target_select_list target_idx=$pipe->pipe_dst_target }
      </select>
     </td>
    </tr>
   </table>
  </td>
  <td>
   Match a source and destination targets.
  </td>
 </tr>
 <tr>
  <td>Filters:</td>
  <td>
   <table class="noborder">
    <tr>
     <td>
      <select size="10" name="avail[]" multiple="multiple">
	    <option value="">********* Unused *********</option>
       { unused_filters_select_list pipe_idx=$pipe->pipe_idx }
      </select>
     </td>
     <td>&nbsp;</td>
     <td>
      <input type="button" value="&gt;&gt;" onclick="moveOptions(document.forms['pipes'].elements['avail[]'], document.forms['pipes'].elements['used[]']);" /><br />
      <input type="button" value="&lt;&lt;" onclick="moveOptions(document.forms['pipes'].elements['used[]'], document.forms['pipes'].elements['avail[]']);" />
     </td>
     <td>&nbsp;</td>
     <td>
      <select size="10" name="used[]" multiple="multiple">
       <option value="">********* Used *********</option>
       { used_filters_select_list pipe_idx=$pipe->pipe_idx }
      </select>
     </td>
    </tr>
   </table>
  </td>
  <td>Select the filters this pipe will shape.</td>
 </tr>
 <tr>
  <td colspan="3">
   <img src="{ $icon_pipes }" alt="pipe icon" />&nbsp;Bandwidth default
  </td>
 </tr>
 <tr>
  <td>Service-Level:</td>
  <td>
   <select name="pipe_sl_idx">
   { service_level_select_list sl_idx=$pipe->pipe_sl_idx }
   </select>
   <img class="change_to" src="{$icon_arrow_right}" value="Go" onclick="change_to('{$rewriter->get_page_url('Service Level Edit', 0)}', $('select[name=pipe_sl_idx]').val());" />
  </td>
  <td>Default bandwidth limit for this pipe. It can be overriden per chain as soon as you assigned this pipe to it.</td>
 </tr>
 <tr>
  <td colspan="3">&nbsp;</td>
 </tr>
 <tr>
  <td style="text-align: center;"><a href="{$rewriter->get_page_url('Pipes List')}" title="Back"><img src="{ $icon_arrow_left }" alt="arrow left icon" /></a></td>
  { include file=common_edit_save.tpl newobj=Pipe }
 </tr>
</table>
</form>
<p class="footnote">
This pipe is assigned to the following chains:<br />
{ foreach from=$chain_use_pipes key=chain_idx item=chain_name name=chains }
 <a href="{$rewriter->get_page_url('Chain Edit', $chain_idx)}" title="Edit chain { $chain_name }"><img src="{$icon_chains}" alt="chain icon" />&nbsp;{ $chain_name }</a>{ if ! $smarty.foreach.filters.last},{/if}
{ foreachelse }
 none
{ /foreach }
</p>
{ page_end focus_to='pipe_name' }
