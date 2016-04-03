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
<form id="login" action="{get_url page='login'}" method="POST">
 <input type="hidden" name="action" value="do_login" />
 <table style="width: 100%;">
  <tr>
   <td>
    <table class="withborder2" style="margin-left:auto; margin-right:auto; text-align: center;">
     <tr>
      <td>
       User:
      </td>
      <td>
       <input type="text" name="user_name" size="15" />
      </td>
     </tr>
     <tr>
      <td>
       Password:
      </td>
      <td>
       <input type="password" name="user_pass" size="15" />
      </td>
     </tr>
     <tr>
      <td>
       &nbsp;
      </td>
      <td>
       <input type="submit" value="Login" />
      </td>
     </tr>
    </table>
   </td>
  </tr>
 </table>
</form>
{page_end focus_to='user_name'}
