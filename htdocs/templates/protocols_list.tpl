{start_table icon=$icon_protocols alt="protocol icon" title="Manage Protocols" }
<table style="width: 100%;" class="withborder">
 <tr>
  <td colspan="5">&nbsp;</td>
 </tr>
 <tr>
  <td style="text-align: center;" colspan="5">
   <img src="{ $icon_new }" alt="new icon" />
   <a href="{$rewriter->get_page_url('Protocol New')}">Create a new Protocol</a>
  </td>
 </tr>
 <tr>
  <td colspan="3">&nbsp;</td>
 </tr>
 <tr>
  <td colspan="3" style="text-align: center;">
   { assign var=pager_ary value=$pager->getLinks() }
   { $pager_ary.all }
  </td>
 </tr>
 <tr>
  <td colspan="3">&nbsp;</td>
 </tr>
 <tr>
  <td>
   <img src="{ $icon_protocols }" alt="protocol icon" /><i>Name</i>
  </td>
  <td>
   <img src="{ $icon_protocols }" alt="protocol icon" /><i>Protocol-Number</i>
  </td>
  <td style="text-align: center;"><i>Options</i></td>
 </tr>
 { protocol_list }
 <tr onmouseover="setBackGrdColor(this, 'mouseover');" onmouseout="setBackGrdColor(this, 'mouseout');">
  <td>
   <img src="{ $icon_protocols }" alt="protocol icon" />
   <a href="{$rewriter->get_page_url('Protocol Edit', $protocol->proto_idx)}">{ $protocol->proto_name }</a>
  { if $protocol->proto_user_defined == 'Y' }
    <img src="{ $icon_users }" alt="User defined protocol" />
  { /if }
   </a>
  </td>
  <td>{ if $protocol->proto_number != ""} { $protocol->proto_number} { else} &nbsp; { /if} </td>
  <td style="text-align: center;">
   <a class="clone" id="protocol-{$protocol->proto_idx}');" title="Clone"><img src="{ $icon_clone }" alt="clone icon" /></a>
   <a class="delete" id="protocol-{$protocol->proto_idx}');" title="Delete"><img src="{ $icon_delete }" alt="delete icon" /></a>
  </td>
 </tr>
{ /protocol_list }
