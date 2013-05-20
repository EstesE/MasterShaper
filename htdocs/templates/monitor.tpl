{if $monitor == "chains"}
 {start_table icon=$icon_chains alt="chain icon" title="Traffic Monitoring - $view"}
{elseif $monitor == "pipes"}
 {start_table icon=$icon_pipes alt="pipe icon" title="Traffic Monitoring - $view"}
{elseif $monitor == "bandwidth"}
 {start_table icon=$icon_bandwidth alt="bandwidth icon" title="Traffic Monitoring - $view"}
{/if}
 <table style="width: 100%;" class="withborder">
  <tr>
   <td class="tablehead" style="width: 180px;">
    Graph Options
   </td>
   <td style="text-align: center; width: 900px; height: 350px" rowspan="10">
    <div id="graph_messages"></div>
    <div id="jqp_monitor"></div>
    <div id="debug"></div>
   </td>
  </tr>
  <tr>
   <td>&nbsp;</td>
  </tr>
  {if $monitor == "bandwidth"}
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
       <select name="showif">
        {monitor_interface_select_list}
       </select>
      </td>
     </tr>
    </table>
   </td>
  </tr>
  {elseif $monitor == "pipes"}
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
       <select name="showchain">
        {monitor_chain_select_list}
       </select>
      </td>
     </tr>
    </table>
   </td>
  </tr>
  {/if}
  {if $monitor == "pipes" || $monitor == "chains"}
  <tr>
   <td style="text-align: center;">
    Interface:<br />
    <select name="showif">
     {monitor_interface_select_list}
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
       <input type="radio" name="graphmode" value="0" {if $graphmode == 0} checked="checked" {/if} onclick="set_graph_mode(0); if(this.blur) this.blur();" class="radio" /><img src="{$web_path}/icons/graph_accu_lines.png" alt="accumulated lines" title="accumulated lines" /><br />
       <input type="radio" name="graphmode" value="1" {if $graphmode == 1} checked="checked" {/if} onclick="set_graph_mode(1); if(this.blur) this.blur();" class="radio" /><img src="{$web_path}/icons/graph_lines.png" alt="lines" title="lines" /><br />
       <input type="radio" name="graphmode" value="2" {if $graphmode == 2} checked="checked" {/if} onclick="set_graph_mode(2); if(this.blur) this.blur();" class="radio" /><img src="{$web_path}/icons/graph_bars.png" alt="bars" title="bars" /><br />
       <input type="radio" name="graphmode" value="3" {if $graphmode == 3} checked="checked" {/if} onclick="set_graph_mode(3); if(this.blur) this.blur();" class="radio" /><img src="{$web_path}/icons/graph_pie_plot.png" alt="pie plot" title="pie plot" /><br />
      </td>
     </tr>
    </table>
   </td>
  </tr>
  {/if}
  <tr>
   <td style="text-align: center;">
    <table class="noborder" style="width: 100%; text-align: center">
     <tr>
      <td>Scale:</td>
     </tr>
     <tr>
      <td>
       <select name="scalemode">
        <option value="bit" {if $scalemode == "bit"} selected="selected" {/if} >bps</option>
        <option value="byte" {if $scalemode == "byte"} selected="selected" {/if} >Bps</option>
        <option value="kbit" {if $scalemode == "kbit"} selected="selected" {/if} >kbps</option>
        <option value="kbyte" {if $scalemode == "kbyte"} selected="selected" {/if} >kBps</option>
        <option value="mbit" {if $scalemode == "mbit"} selected="selected" {/if} >mbps</option>
        <option value="mbyte" {if $scalemode == "mbyte"} selected="selected" {/if} >mBps</option>
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
