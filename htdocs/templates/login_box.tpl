<form id="login" action="{$rewriter->get_page_url('Login')}" method="POST">
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
