{ if $monitor == "chains" || $monitor == "chains-jqPlot" }
 {start_table icon=$icon_chains alt="chain icon" title="Traffic Monitoring - $view" }
{ elseif $monitor == "pipes" || $monitor == "pipes-jqPlot" }
 {start_table icon=$icon_pipes alt="pipe icon" title="Traffic Monitoring - $view" }
{ elseif $monitor == "bandwidth" || $monitor == "bandwidth-jqPlot" }
 {start_table icon=$icon_bandwidth alt="bandwidth icon" title="Traffic Monitoring - $view" }
{ /if }
 <table style="width: 100%;" class="withborder">
  <tr>
   <td class="tablehead" style="width: 180px;">
    Graph Options
   </td>
   <td style="text-align: center; width: 900px; height: 350px" rowspan="10">
    { if $monitor == "chains-jqPlot" || $monitor == "pipes-jqPlot" || $monitor == "bandwidth-jqPlot" }
     <div id="jqp_monitor"></div>
    { else }
     <img src="{ $image_loc }" id="monitor_image" alt="monitor image" />
    { /if }
   </td>
   <td style="height: 350px" rowspan="10">
    { if $monitor == "chains-jqPlot" || $monitor == "pipes-jqPlot" || $monitor == "bandwidth-jqPlot" }
     <div id="jqp_legend"></div>
    { /if }
   </td>
  </tr>
  <tr>
   <td>&nbsp;</td>
  </tr>
  { if $monitor == "bandwidth" || $monitor == "bandwidth-jqPlot" } 
  <tr>
   <td>
    <table class="noborder" style="width: 100%; text-align: center;">
     <tr>
      <td>
       Traffic direction:
      </td>
     </tr>
     <tr>
      <td>
       <select name="showif" onchange="graph_set_interface(this);">
        { interface_select_list }
       </select>
      </td>
     </tr>
    </table>
   </td>
  </tr>
  { elseif $monitor == "pipes" || $monitor == "pipes-jqPlot" }
  <tr>
   <td style="text-align: center;">
    <table class="noborder" style="width: 100%; text-align: center;">
     <tr>
      <td>
       Chain:
      </td>
     </tr>
     <tr>
      <td>
        { chain_select_list }
       <select name="showchain" onchange="graph_set_chain(this);">
       </select>
      </td>
     </tr>
    </table>
   </td>
  </tr>
  { /if }
  { if $monitor == "pipes" || $monitor == "chains" || $monitor == "pipes-jqPlot" || $monitor == "chains-jqPlot" }
  <tr>
   <td style="text-align: center;">
    Interface:<br />
    <select name="showif" onchange="graph_set_interface(this);">
     { interface_select_list }
    </select>
   </td>
  </tr>
  <tr>
   <td>
    <table class="noborder" style="width: 100%; text-align: center">
     <tr>
      <td>
       Graph Mode:
      </td>
     </tr>
     <tr>
      <td>
       <input type="radio" name="graphmode" value="0" { if $graphmode == 0 } checked="checked" { /if } onclick="graph_set_mode(0); if(this.blur) this.blur();" class="radio" /><img src="{$web_path}/icons/graph_accu_lines.png" alt="accumulated lines" title="accumulated lines" /><br />
       <input type="radio" name="graphmode" value="1" { if $graphmode == 1 } checked="checked" { /if } onclick="graph_set_mode(1); if(this.blur) this.blur();" class="radio" /><img src="{$web_path}/icons/graph_lines.png" alt="lines" title="lines" /><br />
       <input type="radio" name="graphmode" value="2" { if $graphmode == 2 } checked="checked" { /if } onclick="graph_set_mode(2); if(this.blur) this.blur();" class="radio" /><img src="{$web_path}/icons/graph_bars.png" alt="bars" title="bars" /><br />
       <input type="radio" name="graphmode" value="3" { if $graphmode == 3 } checked="checked" { /if } onclick="graph_set_mode(3); if(this.blur) this.blur();" class="radio" /><img src="{$web_path}/icons/graph_pie_plot.png" alt="pie plot" title="pie plot" /><br />
      </td>
     </tr>
    </table>
   </td>
  </tr>
  { /if }
  <tr>
   <td style="text-align: center;">
    <table class="noborder" style="width: 100%; text-align: center">
     <tr>
      <td>Scale:</td>
     </tr>
     <tr>
      <td>
       <select name="scalemode" onchange="graph_set_scalemode(this);">
        <option value="bit" { if $scalemode == "bit" } selected="selected" { /if } >bit/s</option>
        <option value="byte" { if $scalemode == "byte" } selected="selected" { /if } >byte/s</option>
        <option value="kbit" { if $scalemode == "kbit" } selected="selected" { /if } >kbit/s</option>
        <option value="kbyte" { if $scalemode == "kbyte" } selected="selected" { /if } >kbyte/s</option>
        <option value="mbit" { if $scalemode == "mbit" } selected="selected" { /if } >mbit/s</option>
        <option value="mbyte" { if $scalemode == "mbyte" } selected="selected" { /if } >mbyte/s</option>
       </select>
      </td>
     </tr>
    </table>
   </td>
  </tr>
  <tr>
   <td style="text-align: center;">
    <input type="button" onclick="image_update();" value="Reload Graph" />
   </td>
  </tr>
  <tr>
   <td style="text-align: center;">
    <input type="checkbox" id="reload" value="Y" checked="checked" onclick="image_toggle_autoload(); if(this.blur) this.blur();" class="radio" />Auto reload
   </td>
  </tr>
 </table>
 <script>
  image_start_autoload();
 </script>
