<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
 <head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title>{$page_title}</title>
  <link href="{$web_path}/shaper_style.css" type="text/css" rel="stylesheet" />
  <link rel="stylesheet" type="text/css" href="{$web_path}/jqplot/jquery.jqplot.css" />
  <link rel="shortcut icon" href="{$web_path}/icons/favicon.ico" type="image/png" />
  <link rel="icon" href="{$web_path}/icons/favicon.ico" type="image/png" />
  <!--<script type="text/javascript" src="{$web_path}/rpc.php?mode=init&amp;client=all"></script>-->
  <!--[if IE]><script language="javascript" type="text/javascript" src="{$web_path}/excanvas.js"></script><![endif]-->
  <script language="javascript" type="text/javascript" src="{$web_path}/jqplot/jquery-1.3.2.min.js"></script>
  <script language="javascript" type="text/javascript" src="{$web_path}/jqplot/jquery.jqplot.min.js"></script>
  <script language="javascript" type="text/javascript" src="{$web_path}/jqplot/plugins/jqplot.cursor.min.js"></script>
  <script language="javascript" type="text/javascript" src="{$web_path}/jqplot/plugins/jqplot.canvasTextRenderer.min.js"></script>
  <script language="javascript" type="text/javascript" src="{$web_path}/jqplot/plugins/jqplot.canvasAxisLabelRenderer.min.js"></script>
  <script language="javascript" type="text/javascript" src="{$web_path}/jqplot/plugins/jqplot.categoryAxisRenderer.min.js"></script>
  <!--<script language="javascript" type="text/javascript" src="{$web_path}/jqplot/plugins/jqplot.dateAxisRenderer.min.js"></script>-->
  <script language="javascript" type="text/javascript" src="{$web_path}/jqplot/plugins/jqplot.pieRenderer.min.js"></script>
  <script language="javascript" type="text/javascript" src="{$web_path}/jqplot/plugins/jqplot.barRenderer.min.js"></script>
  <script language="javascript" type="text/javascript" src="{$web_path}/jquery/plugins/jquery.color.js"></script>
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
      <div id="main_title">{$main_title}</div>
     </td>
    </tr>
   </table>
  </div>
  <div style="background-color: #aaaaaa; height: 2px;"></div>
  <!-- /page title -->

  <div id="menubox">
   <div id="main_menu">{$main_menu}</div>
  </div>

  <!-- grey border line below header cell -->
  <div style="background-color: #aaaaaa; height: 2px;"></div>

  <div id="submenubox">
   <table style="height: 30px;">
    <tr>
     <td>
      <div id="sub_menu">{$sub_menu}</div>
     </td>
    </tr>
   </table>
  </div>

  <!-- grey border line -->
  <div style="background-color: #aaaaaa; height: 2px;"></div>
  <div style="height: 30px;"></div>

