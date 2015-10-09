<!-- header.tpl -->
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
 <head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title>MasterShaper</title>
  <link href="{$web_path}/resources/shaper_style.css" type="text/css" rel="stylesheet" />
  <link rel="shortcut icon" href="{$web_path}/resources/icons/favicon.ico" type="image/png" />
  <link rel="icon" href="{$web_path}/resources/icons/favicon.ico" type="image/png" />
  <!-- jQuery -->
  <script language="javascript" type="text/javascript" src="{$web_path}/resources/jquery/jquery-1.8.0.min.js"></script>
  <script language="javascript" type="text/javascript" src="{$web_path}/resources/jquery/plugins/jquery.color.js"></script>
  <!-- jqPlot -->
  <!--[if IE]><script language="javascript" type="text/javascript" src="{$web_path}/jquery/jqplot/excanvas.js"></script><![endif]-->
  <link rel="stylesheet" type="text/css" href="{$web_path}/resources/jquery/jqplot/jquery.jqplot.css" />
  <script language="javascript" type="text/javascript" src="{$web_path}/resources/jquery/jqplot/jquery.jqplot.min.js"></script>
  <script language="javascript" type="text/javascript" src="{$web_path}/resources/jquery/jqplot/plugins/jqplot.cursor.min.js"></script>
  <script language="javascript" type="text/javascript" src="{$web_path}/resources/jquery/jqplot/plugins/jqplot.canvasTextRenderer.min.js"></script>
  <script language="javascript" type="text/javascript" src="{$web_path}/resources/jquery/jqplot/plugins/jqplot.canvasAxisLabelRenderer.min.js"></script>
  <script language="javascript" type="text/javascript" src="{$web_path}/resources/jquery/jqplot/plugins/jqplot.dateAxisRenderer.min.js"></script>
  <script language="javascript" type="text/javascript" src="{$web_path}/resources/jquery/jqplot/plugins/jqplot.categoryAxisRenderer.min.js"></script>
  <script language="javascript" type="text/javascript" src="{$web_path}/resources/jquery/jqplot/plugins/jqplot.pieRenderer.min.js"></script>
  <script language="javascript" type="text/javascript" src="{$web_path}/resources/jquery/jqplot/plugins/jqplot.barRenderer.min.js"></script>
  <script language="javascript" type="text/javascript" src="{$web_path}/resources/jquery/jqplot/plugins/jqplot.pointLabels.js"></script>
  <!-- jQuery UI -->
  <link type="text/css" href="{$web_path}/resources/jquery/ui/css/smoothness/jquery-ui-1.8.22.custom.css" rel="Stylesheet" />
  <script type="text/javascript" src="{$web_path}/resources/jquery/ui/js/jquery-ui-1.8.22.custom.min.js"></script>
  <!-- mb.menu -->
  <script type="text/javascript" src="{$web_path}/resources/jquery/menu/jquery.metadata.min.js"></script>
  <script type="text/javascript" src="{$web_path}/resources/jquery/menu/jquery.hoverIntent.min.js"></script>
  <script type="text/javascript" src="{$web_path}/resources/jquery/menu/mbMenu.min.js"></script>
  <!-- our own js stuff -->
  <script type="text/javascript" src="{$web_path}/resources/shaper.js"></script>
 </head>
 <body>
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
       <form action="{get_page_url page='Logout'}" method="POST">
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
       <a href="{get_page_url page='Host Tasklist'}" title="Host Tasklist"><img src="{$icon_ready}" id="readybusyico" /></a>
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
