<!-- header.tpl -->
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
 <head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title>{$page_title}</title>
  <link href="{$web_path}/shaper_style.css" type="text/css" rel="stylesheet" />
  <link rel="shortcut icon" href="{$web_path}/icons/favicon.ico" type="image/png" />
  <link rel="icon" href="{$web_path}/icons/favicon.ico" type="image/png" />
  <!-- jQuery -->
  <script language="javascript" type="text/javascript" src="{$web_path}/jquery/jquery-1.4.2.min.js"></script>
  <script language="javascript" type="text/javascript" src="{$web_path}/jquery/plugins/jquery.color.js"></script>
  <!-- jqPlot -->
  <!--[if IE]><script language="javascript" type="text/javascript" src="{$web_path}/jqplot/excanvas.js"></script><![endif]-->
  <link rel="stylesheet" type="text/css" href="{$web_path}/jquery/jqplot/jquery.jqplot.css" />
  <script language="javascript" type="text/javascript" src="{$web_path}/jquery/jqplot/jquery.jqplot.min.js"></script>
  <script language="javascript" type="text/javascript" src="{$web_path}/jquery/jqplot/plugins/jqplot.cursor.min.js"></script>
  <script language="javascript" type="text/javascript" src="{$web_path}/jquery/jqplot/plugins/jqplot.canvasTextRenderer.min.js"></script>
  <script language="javascript" type="text/javascript" src="{$web_path}/jquery/jqplot/plugins/jqplot.canvasAxisLabelRenderer.min.js"></script>
  <script language="javascript" type="text/javascript" src="{$web_path}/jquery/jqplot/plugins/jqplot.dateAxisRenderer.min.js"></script>
  <script language="javascript" type="text/javascript" src="{$web_path}/jquery/jqplot/plugins/jqplot.categoryAxisRenderer.min.js"></script>
  <script language="javascript" type="text/javascript" src="{$web_path}/jquery/jqplot/plugins/jqplot.pieRenderer.min.js"></script>
  <script language="javascript" type="text/javascript" src="{$web_path}/jquery/jqplot/plugins/jqplot.barRenderer.min.js"></script>
  <!-- jQuery UI -->
  <link type="text/css" href="{$web_path}/jquery/ui/css/smoothness/jquery-ui-1.8.4.custom.css" rel="Stylesheet" />
  <script type="text/javascript" src="{$web_path}/jquery/ui/js/jquery-ui-1.8.4.custom.min.js"></script>
  <!-- mb.menu -->
  <script type="text/javascript" src="{$web_path}/jquery/menu/jquery.metadata.js"></script>
  <script type="text/javascript" src="{$web_path}/jquery/menu/jquery.hoverIntent.js"></script>
  <script type="text/javascript" src="{$web_path}/jquery/menu/mbMenu.min.js"></script>
  <!-- our own js stuff -->
  <script type="text/javascript" src="{$web_path}/shaper.js"></script>
 </head>
 <body>
  <!-- header cell -->
  <div style="height: 10px;"></div>
  <div style="width: 100%; height: 70px;">
  <img src="{$web_path}/images/ms_logo.png"></div>
  <!-- /header cell -->   

  <!-- page title -->
  <div style="background-color: #aaaaaa; height: 2px;"></div>
  <div style="height: 30px; color: #FFFFFF; background-color: #174581; vertical-align: middle;" class="tablehead">
   <table style="height: 30px">
    <tr>
     <td style="width: 15px;"></td>
     <td style="vertical-align: middle;">
      { if ! $user_name }
       <div><img src="{ $icon_home }" />&nbsp;MasterShaper Login</div>
      { else }
       <form action="{ $rewriter->get_page_url('Logout') }" method="POST">
       <input type='hidden' name='action' value='do_logout' />
       <div>
        <img src="{ $icon_home }" />&nbsp;MasterShaper Login - logged in as { $user_name }
         (<input type='submit' value='Logout' />)
       </div>
       </form>
      { /if }
     </td>
    </tr>
   </table>
  </div>
  <div style="background-color: #aaaaaa; height: 2px;"></div>
  <!-- /page title -->

  <div id="menubox">
   { include file="menu.tpl" }
  </div>

  <!-- grey border line -->
  <div style="background-color: #aaaaaa; height: 2px;"></div>
  <div style="height: 30px;"></div>
<!-- /header.tpl -->
