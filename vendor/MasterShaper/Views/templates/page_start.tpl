<!-- header cell -->
  <div style="height: 10px;"></div>
  <div style="width: 100%; height: 70px;">
   <a href="{$web_path}"><img src="{$web_path}/resources/images/ms_logo.png" /></a>
  </div>
  <!-- /header cell -->

  <!-- page title -->
  <div style="background-color: #aaaaaa; height: 2px;"></div>
  <div style="height: 30px; color: #FFFFFF; background-color: #174581; vertical-align: middle;" class="tablehead">
   <table style="height: 30px; width: 100%">
    <tr>
     <td style="width: 15px;">&nbsp;</td>
     <td style="vertical-align: middle;">
      {if !isset($user_name) || empty($user_name)}
       <div><img src="{$icon_home}" />&nbsp;MasterShaper Login</div>
      {else}
       <form action="{$rewriter->get_page_url('Logout')}" method="POST">
       <input type='hidden' name='action' value='do_logout' />
       <div>
        <img src="{$icon_home}" />&nbsp;MasterShaper Login - logged in as {$user_name}
         (<input type='submit' value='Logout' />)
       </div>
       </form>
      {/if}
     </td>
     <td style="text-align: right; vertical-align: middle;">
      {if isset($user_name) && !empty($user_name)}
       Host Profile:
       <select name="active_host_profile" onchange="set_host_profile()">
        {host_profile_select_list}
       </select>
       Agent:
       <a href="{$rewriter->get_page_url('Host Tasklist')}" title="Host Tasklist"><img src="{$icon_ready}" id="readybusyico" /></a>
      {/if}
     </td>
     <td style="width: 15px;">&nbsp;</td>
    </tr>
   </table>
  </div>
  <div style="background-color: #aaaaaa; height: 2px;"></div>
  <!-- /page title -->

  <div id="menubox">
   {include file="menu.tpl"}
  </div>

  <!-- grey border line -->
  <div style="background-color: #aaaaaa; height: 2px;"></div>
  <div style="height: 30px;"></div>
<!-- /header.tpl -->
