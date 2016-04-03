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
<pre id="target"></pre>
<form action="{$page->uri}" id="options" method="post">
<input type="hidden" name="module" value="update-iana" />
<input type="hidden" name="action" value="store" />
{start_table icon=$icon_options alt="option icon" title="Update IANA Ports and Protocols"}
 <table style="width: 100%;" class="withborder2">
  <tr>
   <td colspan="2">&nbsp;</td>
  </tr>
  <tr>
   <td>&nbsp;</td>
   <td>
    This process will update the list of ports &amp; protocols you can select for Filters.<br />
    <br />
    <b>Ensure that you have put the IANA XML files into the _contrib_ directory first!</b><br />
    <br />
    You can grab the two files from IANA's homepage:<br />
    <ul><a href="http://lmgtfy.com/?q=iana+ports+assignment" target="_blank">service-names-port-numbers.xml</a></ul>
    <ul><a href="http://lmgtfy.com/?q=iana+protocol+assignment" target="_blank">protocol-numbers.xml</a></ul>
    <br />
    <b>
     It's safe to call this function multiple times.<br />
     Existing entries (user-defined or not) will not be modified!<br />
    </b>
   </td>
  </tr>
  <tr>
   <td colspan="2">
    <img src="{$icon_options}" alt="option icon" />&nbsp;Start update procedure
   </td>
  </tr>
  <tr>
   <td>&nbsp;</td>
   <td><input type="submit" value="Update" /></td>
  </tr>
 </table>
</form>
