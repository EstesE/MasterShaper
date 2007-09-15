<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
 <head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title>{$page_title}</title>
  <link href="shaper_style.css" type="text/css" rel="stylesheet" />
  <script type="text/javascript" src="rpc.php?mode=init&amp;client=all"></script>
  <script type="text/javascript" src="shaper.js"></script>
  <link rel="shortcut icon" href="icons/favicon.ico" type="image/png" />
  <link rel="icon" href="icons/favicon.ico" type="image/png" />
 </head>
 <body onload="init_shaper();">
  <!-- header cell -->
  <div style="height: 10px;"></div>
  <div style="width: 100%; height: 90px;">
  <img src="images/ms_logo.png"></div>
  <!-- /header cell -->   

  <!-- page title -->
  <div style="background-color: #aaaaaa; height: 2px;"></div>
  <div style="height: 30px; color: #FFFFFF; background-color: #174581; vertical-align: middle;" class="tablehead">
   <table style="height: 30px">
    <tr>
     <td style="width: 15px;"></td>
     <td style="vertical-align: middle;">
      <?php print $title; ?>
     </td>
    </tr>
   </table>
  </div>
  <div style="background-color: #aaaaaa; height: 2px;"></div>
  <!-- /page title -->

  <div id="menubox">
   <table class="menu">
   </table>
  </div>

  <!-- grey border line below header cell -->
  <div style="background-color: #aaaaaa; height: 2px;"></div>

  <div id="submenubox">
   <table style="height: 30px;">
    <tr>
     <td>
      <div id="submenu"></div>
     </td>
    </tr>
   </table>
  </div>

  <!-- grey border line -->
  <div style="background-color: #aaaaaa; height: 2px;"></div>
  <div style="height: 30px;"></div>

  <!-- main cell -->
  <div id="main">
  
  <!-- module output -->

