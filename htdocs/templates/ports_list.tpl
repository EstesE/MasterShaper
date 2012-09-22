{start_table icon=$icon_ports alt="port icon" title="Manage Ports" }	  
<table style="width: 100%;" class="withborder">
 <tr>
  <td colspan="5">&nbsp;</td>
 </tr>
 <tr>
  <td style="text-align: center;" colspan="5">
   <img src="{ $icon_new }" alt="new icon" />
   <a href="{$rewriter->get_page_url('Port New')}">Create a new Port</a>
  </td>
 </tr>
 <tr>
  <td colspan="5">&nbsp;</td>
 </tr>
 <tr>
  <td colspan="5" style="text-align: center;">
   { assign var=pager_ary value=$pager->getLinks() }
   { $pager_ary.all }
  </td>
 </tr>
 <tr>
  <td colspan="5">&nbsp;</td>
 </tr>
 <tr>
  <td>
   <img src="{ $icon_ports }" alt="port icon" /><i>Name</i>
  </td>
  <td>
   <img src="{ $icon_ports }" alt="port icon" /><i>Description</i>
  </td>
  <td>
   <img src="{ $icon_ports }" alt="port icon" /><i>Port-Number</i>
  </td>
  <td style="text-align: center;"><i>Options</i></td>
 </tr>
 { port_list }
 <tr onmouseover="setBackGrdColor(this, 'mouseover');" onmouseout="setBackGrdColor(this, 'mouseout');">
  <td>
   <img src="{ $icon_ports }" alt="port icon" />
   <a href="{$rewriter->get_page_url('Port Edit', $port->port_idx)}">{ $port->port_name }</a>
  { if $port->port_user_defined == 'Y' }
    <img src="{ $icon_users }" alt="User defined port" />
  { /if }
   </a>
  </td>
  <td>{ if $port->port_desc != "" } { $port->port_desc } { else } &nbsp; { /if }</td>
  <td>{ if $port->port_number != ""} { $port->port_number} { else} &nbsp; { /if} </td>
  <td style="text-align: center;">
   <a class="clone" id="port-{$port->port_idx}" title="Clone"><img src="{ $icon_clone }" alt="clone icon" /></a>
   <a class="delete" id="port-{$port->port_idx}" title="Delete"><img src="{ $icon_delete }" alt="delete icon" /></a>
  </td>
 </tr>
{ /port_list }
