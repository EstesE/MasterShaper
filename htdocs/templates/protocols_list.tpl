{start_table icon=$icon_protocols alt="protocol icon" title="Manage Protocols" }
<table style="width: 100%;" class="withborder">
 <tr>
  <td colspan="5">&nbsp;</td>
 </tr>
 <tr>
  <td style="text-align: center;" colspan="5">
   <img src="{ $icon_new }" alt="new icon" />
   <a href="javascript:refreshContent('protocols', '&mode=new');">Create a new Protocol</a>
  </td>
 </tr>
 <tr>
  <td colspan="3">&nbsp;</td>
 </tr>
 <tr>
  <td>
   <img src="{ $icon_protocols }" alt="protocol icon" />
   <a href="javascript:refreshContent('protocols', '&breaker={ $breaker }&orderby=proto_name&sortorder={ $sortorder }"><i>Name</i></a>
  </td>
  <td>
   <img src="{ $icon_protocols }" alt="protocol icon" />
     <a href="javascript:refreshContent('protocols', '&breaker={ $breaker }&orderby=proto_number&sortorder={ $sortorder }"><i>Protocol-Number</i></a>
  </td>
  <td style="text-align: center;"><i><?php print _("Options"); ?></i></td>
 </tr>
 { protocol_list }
 <tr onmouseover="setBackGrdColor(this, 'mouseover');" onmouseout="setBackGrdColor(this, 'mouseout');">
  <td>
   <img src="{ $icon_protocols }" alt="protocol icon" />
   <a href="javascript:refreshContent('protocols', '&mode=edit&idx={ $proto_idx }');">{ $proto_name }</a>
  { if $proto_user_defined == 'Y' }
    <img src="{ $icon_users }" alt="User defined protocol" />
  { /if }
   </a>
  </td>
  <td>{ if $proto_number != ""} { $proto_number} { else} &nbsp; { /if} </td>
  <td style="text-align: center;">
   <a href="javascript:deleteObj('protocol', 'protocols', '{ $proto_idx }');" title="Delete"><img src="{ $icon_delete }" alt="delete icon" /></a>
  </td>
 </tr>
{ /protocol_list }
