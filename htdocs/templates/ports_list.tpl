{start_table icon=$icon_ports alt="port icon" title="Manage Ports" }	  
<table style="width: 100%;" class="withborder">
 <tr>
  <td colspan="5">&nbsp;</td>
 </tr>
 <tr>
  <td style="text-align: center;" colspan="5">
   <img src="{ $icon_new }" alt="new icon" />
   <a href="javascript:refreshContent('ports', '&mode=new');">Create a new Port</a>
  </td>
 </tr>
 <tr>
  <td colspan="5">&nbsp;</td>
 </tr>
 <tr>
  <td colspan="5" style="text-align: center;">
  { foreach from=$breakers item=letter }
   { if $cur_breaker == breaker }
    <a href="javascript:refreshContent('ports', '&breaker={ $letter }&orderby={ $orderby }&sortorder={ $sortorder }" style="color: #AF0000;">{ $letter }</a>
   { else }
    <a href="javascript:refreshContent('ports', '&breaker={ $letter }&orderby={ $orderby }&sortorder={ $sortorder }">{ $letter }</a>
   { /if }
  { /foreach }
  </td>
 </tr>
 <tr>
  <td colspan="5">&nbsp;</td>
 </tr>
 <tr>
  <td>
   <img src="{ $icon_ports }" alt="port icon" />
   <a href="javascript:refreshContent('ports', '&breaker={ $breaker }&orderby=port_name&sortorder={ $sortorder }"><i>Name</i></a>
  </td>
  <td>
   <img src="{ $icon_ports }" alt="port icon" />
   <a href="javascript:refreshContent('ports', '&breaker={ $breaker }&orderby=port_desc&sortorder={ $sortorder }"><i>Description</i></a>
  </td>
  <td>
   <img src="{ $icon_ports }" alt="port icon" />
     <a href="javascript:refreshContent('ports', '&breaker={ $breaker }&orderby=port_number&sortorder={ $sortorder }"><i>Port-Number</i></a>
  </td>
  <td style="text-align: center;"><i><?php print _("Options"); ?></i></td>
 </tr>
 { port_list }
 <tr onmouseover="setBackGrdColor(this, 'mouseover');" onmouseout="setBackGrdColor(this, 'mouseout');">
  <td>
   <img src="{ $icon_ports }" alt="port icon" />
   <a href="javascript:refreshContent('ports', '&mode=edit&idx={ $port_idx }');">{ $port_name }</a>
  { if $port_user_defined == 'Y' }
    <img src="{ $icon_users }" alt="User defined port" />
  { /if }
   </a>
  </td>
  <td>{ if $port_desc != "" } { $port_desc } { else } &nbsp; { /if }</td>
  <td>{ if $port_number != ""} { $port_number} { else} &nbsp; { /if} </td>
  <td style="text-align: center;">
   <a href="javascript:deletePort('{ $port_idx }');" title="Delete"><img src="{ $icon_delete }" alt="delete icon" /></a>
  </td>
 </tr>
{ /port_list }
