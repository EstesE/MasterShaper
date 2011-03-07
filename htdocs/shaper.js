/***************************************************************************
 *
 * Copyright (c) by Andreas Unterkircher
 * All rights reserved
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
 *
 ***************************************************************************/

var NetScape4 = (navigator.appName == "Netscape" && parseInt(navigator.appVersion) < 5);
var autoload = undefined;
var jqp = undefined;

function addOption(theSel, theText, theValue)
{		
	var newOpt = new Option(theText, theValue);
	var selLength = theSel.length;
	theSel.options[selLength] = newOpt;
}	
	
function deleteOption(theSel, theIndex)
{		
	var selLength = theSel.length;
	if(selLength>0) {	
		theSel.options[theIndex] = null;
	}	
}	
	
function moveOptions(theSelFrom, theSelTo)
{	
	var selLength = theSelFrom.length;
	var selectedText = new Array();
	var selectedValues = new Array();
	var selectedCount = 0;
	
	var i;
	
	// Find the selected Options in reverse order
	// and delete them from the 'from' Select.
	for(i=selLength-1; i>=0; i--) {	
		if(theSelFrom.options[i].selected) {	

		        if(theSelFrom.options[i].value != "") {
				selectedText[selectedCount] = theSelFrom.options[i].text;
				selectedValues[selectedCount] = theSelFrom.options[i].value;
				deleteOption(theSelFrom, i);
				selectedCount++;
			}
		}	
	}	
		
	// Add the selected text/values in reverse order.
	// This will add the Options to the 'to' Select
	// in the same order as they were in the 'from' Select.
	for(i=selectedCount-1; i>=0; i--) {	
		addOption(theSelTo, selectedText[i], selectedValues[i]);
	}	
		
	if(NetScape4) history.go(0);
}	

/**
 * this function will select all available
 * options within a select-form
 */
function selectAll(obj)
{
   if(el = document.getElementsByName(obj)) {
      if(el.item(0)) {
         var lent = el.item(0).length ;
         for (var i=0; i<lent; i++) {
            el.item(0).options[i].selected = true;
         }
      }
   }
} // selectAll

function setBackGrdColor(item, color)
{
	if(color == 'mouseover')
		item.style.backgroundColor='#c6e9ff';
	if(color == 'mouseout')
		item.style.backgroundColor='transparent';
	if(color == 'mouseclick')
		item.style.backgroundColor='#93A8CA';
}

function click(object)
{
   if(object.blur)
      object.blur();

}

function init_shaper()
{

} // init_shaper()

function draw_jqplot()
{
   var showif = get_selected_interface();
   var showchain = get_selected_chain();
   var scalemode = get_selected_scalemode();

   $.ajax({
      type: 'POST',
      url: 'rpc.html',
      data: ({
         type      : 'rpc',
         action    : 'graph-data',
         showif    : showif,
         scalemode : scalemode,
         showchain : showchain
      }),
      dataType: 'json',
      error: function(XMLHttpRequest, textStatus, errorThrown) {
         alert('Failed to contact server! ' + textStatus + ' ' + errorThrown);
      },
      success: function(data){
         $.drawIt(data);
      }
   });

   $.drawIt = function(data) {

      if(data == undefined)
         return "Something went wrong when fetching values from server!";

      var time_end    = data.time_end;
      var interface   = data.interface;
      var scalemode   = data.scalemode;
      var graphmode   = data.graphmode;

      if(data.names)
         var names_obj= parse_json(data.names);
      if(data.colors)
         var colors_obj = parse_json(data.colors);

      /* default values */
      var seriesStack = false;
      var seriesFill  = true;
      var seriesRenderer        = $.jqplot.LineRenderer;
      var seriesRendererOptions = {};

      if(!data.data) {
         window.alert(values);
         return;
      }

      // enable for some debugging output
      //document.getElementById("debug").innerHTML = 'Debug: ' + data.data;
      //document.getElementById("debug").innerHTML = 'Debug: ' + data.names;
      //document.getElementById("debug").innerHTML = 'Debug: ' + data.colors;

      var plot_obj  = parse_json(data.data);
      var plot_arr  = new Array();
      var names_arr = new Array();
      /* a default color is a must, otherwise jqplot refuses to work */
      var colors_arr = new Array('#4444aa');

      var title = 'Current Bandwidth Usage - '+ time_end +" - Interface "+ interface;
      ylabel = "Bandwidth " + scalemode;

      /* transform object to array */
      var j = 0;
      for (var i in plot_obj) {
         plot_arr[j] = plot_obj[i];
         j++;
      }
      j = 0;
      for (var i in names_obj) {
         names_arr[j] = {
            label: names_obj[i]
         };
         j++;
      }
      j = 0;
      for (var i in colors_obj) {
         colors_arr[j] = colors_obj[i];
         j++;
      }

      if(plot_arr == undefined || plot_arr.length < 1) {
         document.getElementById("jqp_monitor").innerHTML = 'No data to display';
      }

      /* accumulated lines */
      if(graphmode == 0) {
         seriesStack = true;
         xaxis_opts = {
            autoscale:           true,
            label:               'Time',
            renderer:            $.jqplot.DateAxisRenderer,
            tickOptions:         {formatString:'%H:%M:%S'},
            tickInterval:        '10 seconds'
         }
         plot_values = plot_arr;
      }
      /* simple lines */
      if(graphmode == 1) {
         seriesFill = false;
         xaxis_opts = {
            autoscale:           true,
            label:               'Time',
            renderer:            $.jqplot.DateAxisRenderer
         }
         plot_values = plot_arr;
      }
      /* bars */
      if(graphmode == 2) {
         seriesRenderer          = $.jqplot.BarRenderer;
         seriesRendererOptions   = { barPadding: 8, barMargin: 20 };
         xaxis_opts = {};
      }
      /* pie */
      if(graphmode == 3) {
         seriesRenderer          = $.jqplot.PieRenderer;
         seriesRendererOptions   = { sliceMargin:8 };
         xaxis_opts = {};
      }

      // clear view
      //$('#jqp_monitor').empty();
      //jqplot.replot({resetAxes:true});

      //if(jqp == undefined) {

         // new plot
         jqp = $.jqplot('jqp_monitor', plot_values, {
         /* title */
         title:                     title,
         /* axes styling */
         axes:{
            yaxis: {
               labelRenderer:       $.jqplot.CanvasAxisLabelRenderer,
               label:               ylabel,
               autoscale:           true,
               min:                 0,
               angel:               90,
               enableFontSupport:   true
            },
            xaxis:                  xaxis_opts
         },
         seriesDefaults: {
            fill:                   seriesFill,
            showMarker:             true,
            renderer:               seriesRenderer,
            rendererOptions:        seriesRendererOptions
         },
         cursor:{
            show:                   false,
            showVerticalLine:       true,
            showHorizontalLine:     false,
            showTooltip:            true,
            showCursorLegend:       false,
            useAxesFormatters:      true,
            zoom:                   true
         },
         stackSeries:               seriesStack,
         series:                    names_arr,
         seriesColors:              colors_arr
         /*legend:{
            show:       true,
            location:   'ne',
            xoffset:    -70
         }*/
       }
      );
      /*}
      else {
         jqp.series[0].data = seriesStack;
         jqp.series[0].color = colors_arr;
         jqp.replot({ resetAxes: true });
      }*/

      var legend = document.getElementById('jqp_legend');

      legend.innerHTML = '';

      for(var arrkey in names_arr) {
         legend.innerHTML+= "<br /><font color='" + colors_arr[arrkey] + "'>" + names_arr[arrkey].label + "</font>";
      }
   }

} // draw_jqplot()

function set_graph_mode(to)
{
   var showif = get_selected_interface();
   var showchain = get_selected_chain();
   var scalemode = get_selected_scalemode();

   $.ajax({
      type: "POST",
      url: "rpc.html",
      data: ({
         type      : 'rpc',
         action    : 'graph-mode',
         graphmode : to,
         scalemode : scalemode,
         interface : showif,
         chain     : showchain
      }),
      error: function(XMLHttpRequest, textStatus, errorThrown) {
         alert('Failed to contact server! ' + textStatus);
      },
      success: function(data){
         if(data == "ok\n") {
            return true;
         }
         alert('Server returned: ' + data + data.length);
         return false;
      }
   });

} // set_graph_mode()

function set_host_profile()
{
   var selectbox = document.getElementsByName("active_host_profile")[0];

   if(!selectbox) {
      alert('Unable to locate element active_host_profile');
      return false;
   }

   var hostprofile = selectbox.options[selectbox.selectedIndex].value;

   if(!hostprofile) {
      alert('Unable to get selected host_profile');
      return false;
   }

   $.ajax({
      type: "POST",
      url: "rpc.html",
      data: ({
         type      : 'rpc',
         action    : 'host-profile',
         hostprofile : hostprofile
      }),
      error: function(XMLHttpRequest, textStatus, errorThrown) {
         alert('Failed to contact server! ' + textStatus);
      },
      success: function(data){
         if(data == "ok\n") {
            window.location.reload();
            return true;
         }
         alert('Server returned: ' + data + data.length);
         return false;
      }
   });

} // set_host_profile()

function obj_delete(element, target, idx)
{
   var del_id = element.attr("id");

   if(del_id == undefined || del_id == "") {
      alert('no attribute "id" found!');
      return;
   }

   if(!confirm("Are you sure you want to delete this object? There is NO undo!")) {
      return false;
   }

   $.ajax({
      type: "POST",
      url: "rpc.html",
      data: ({type : 'rpc', action : 'delete', id : del_id }),
      beforeSend: function() {
         // change row color to red
         element.parent().parent().animate({backgroundColor: "#fbc7c7" }, "fast");
      },
      error: function(XMLHttpRequest, textStatus, errorThrown) {
         alert('Failed to contact server! ' + textStatus);
      },
      success: function(data){
         if(data == "ok\n") {
            element.parent().parent().animate({ opacity: "hide" }, "fast");
            return;
         }
         // change row color back to white
         element.parent().parent().animate({backgroundColor: "#ffffff" }, "fast");
         alert('Server returned: ' + data);
         return;
      }
   });

} // obj_delete()

function currentRadio(obj)
{
   for(cnt = 0; cnt < obj.length; cnt++) {
      if(obj[cnt].checked)
         return obj[cnt].value;
   }
}

/**
 * get current selected value from a HTML select item
 *
 * @param obj object
 * @return string
 */
function currentSelect(obj)
{
   if(!obj)
      return;

   for(cnt = 0; cnt < obj.length; cnt++) {
      if(obj[cnt].selected)
         return obj[cnt].value;
   }
}

function currentCheckbox(obj)
{
   if(obj.checked == true) {
      return obj.value;
   }
   
   return;
}

function obj_toggle_status(element)
{
   var toggle_id = element.attr("id");
   var toggle_to = element.attr("to");
   var toggle_parent = element.attr("parent");

   if(toggle_id == undefined || toggle_id == "") {
      alert('no attribute "id" found!');
      return;
   }
   if(toggle_to == undefined || toggle_to == "") {
      alert('no attribute "to" found!');
      return;
   }
   // no parent, set null value
   if(toggle_parent == undefined || toggle_parent == "") {
      toglgle_parent = '';
   }

   $.ajax({
      type: "POST",
      url: "rpc.html",
      data: ({
         type : 'rpc',
         action : 'toggle',
         id : toggle_id,
         to : toggle_to,
         parent : toggle_parent
      }),
      error: function(XMLHttpRequest, textStatus, errorThrown) {
         alert('Failed to contact server! ' + textStatus);
      },
      success: function(data){
         if(data == "ok\n") {
            // toggle all parent's children
            $('#' + element.parent().attr("id") + ' > *').toggle();
            return true;
         }
         alert('Server returned: ' + data + data.length);
         return false;
      }
   });

} // obj_toggle_status()

function obj_alter_position(element)
{
   if(!(obj_type = element.attr("type")) == undefined)
      window.alert("missing type for " + element);
   if(!(obj_idx = element.attr("idx")) == undefined)
      window.alert("missing idx for " + element);

   if(element.attr("class") == "move-up")
      obj_to = "up";
   if(element.attr("class") == "move-down")
      obj_to = "down";

   $.ajax({
      type: "POST",
      url: "rpc.html",
      data: ({type : 'rpc', action : 'alter-position', move_obj : obj_type, id : obj_idx, to : obj_to }),
      error: function(XMLHttpRequest, textStatus, errorThrown) {
         alert('Failed to contact server! ' + textStatus);
      },
      success: function(data){
         tableRow = $('#' + obj_type + obj_idx);
         if(obj_to == 'up') {
            /* the first tableRow is the second child */
            if(tableRow.is(":nth-child(2)")) {
               if($children = tableRow.parent().children())
                  $($children[$children.length-1]).after(tableRow);
            }
            else {
               if(prev = tableRow.prev())
                  prev.before(tableRow);
            }
         }
         if(obj_to == 'down') {
            if(tableRow.is(":last-child")) {
               if($children = tableRow.parent().children())
                  $($children[1]).before(tableRow);
            }
            else {
               if(next = tableRow.next())
                  next.after(tableRow);
            }
         }
         if(data == "ok\n")
            return;
         alert('Server returned: ' + data + ' ' + data.length);
      }
   });

} // alter_position()

function obj_assign_pipe_to_chains(element)
{
   var pipe_idx = element.attr("id");

   if(pipe_idx == undefined || pipe_idx == "") {
      alert('no attribute "id" found!');
      return;
   }

   $.loadDialogContent = function() {

      $.ajax({
         type: 'POST',
         url: 'rpc.html',
         data: ({
            type : 'rpc',
            action : 'get-chains-list',
            idx: pipe_idx
         }),
         dataType: 'json',
         error: function(XMLHttpRequest, textStatus, errorThrown) {
            alert('Failed to contact server! ' + textStatus + ' ' + errorThrown);
         },
         success: function(data){
            $('#dialog').css('visibility', 'visible');
            if(data.content)
               $('#dialog').html(data.content);
            else
               $('#dialog').html('unable to fetch chains list!');
         }
      });
   }

   $('#dialog').attr('title', 'Apply Pipe to the following chains...');
   $('#dialog').html('Loading Chains-List...');

   $('#dialog').dialog({
      autoOpen: false,
      open: $.loadDialogContent(),
      close: $('#dialog').css('visibility', 'hidden')
   });

   if(!$('#dialog').dialog('isOpen')) {
      $('#dialog').dialog('open');
   }

} // obj_assign_pipe_to_chains()

function image_update()
{
   $('#jqp_monitor').empty();
   draw_jqplot();

} // image_update()

function image_autoload()
{
   image_update();

   if(document.getElementById("reload")) {
      if(document.getElementById("reload").checked) {
         autoload = undefined;
         image_start_autoload();
      }
   }

} // image_autoload

function image_start_autoload()
{
   if(autoload == undefined) {
      autoload = setTimeout("image_autoload()", 10000);
   }

   /* load jqplot for first time */
   draw_jqplot();

} // image_start_autoload()

function image_stop_autoload()
{
   clearTimeout(autoload);
   autoload = undefined;

} // image_stop_autoload()

function image_toggle_autoload()
{
   if(document.getElementById("reload").checked) {
         image_start_autoload();
   }
   else {
      image_stop_autoload();
   }
}

function get_selected_interface()
{
   var showif =  document.getElementsByName('showif');
   return currentSelect(showif[0]);

} // get_selected_interface()

function get_selected_scalemode()
{
   var scalemode = document.getElementsByName('scalemode');
   return currentSelect(scalemode[0]);

} // get_selected_scalemode()

function get_selected_chain()
{
   var showchain = document.getElementsByName('showchain');

   if(showchain == undefined)
      return false;

   return currentSelect(showchain[0]);
}

/**
 * set focus to specified object
 *
 * this function will search for the first matching
 * object and if possible, set the focus to it.
 */
function setFocus(obj)
{
   if(el = document.getElementsByName(obj)) {
      if(el.item(0)) {
         if(el.item(0).focus) {
            el.item(0).focus();
         }
      }
   }
} // setFocus()

function parse_json(values)
{
   if(!values)
      return;

   // use browser-built in function if it supports it
   if(typeof JSON === "object" && JSON.parse) {
      var data = JSON.parse(values);
   }
   else {
      // sanitize string and eval it
      var data = !(
            /[^,:{}\[\]0-9.\-+Eaeflnr-u \n\r\t]/.test(
               values.replace(/"(\\.|[^"\\])*"/g, '')
            )
         ) && eval('(' + values + ')');
   }
   
   return data;
}

function load_menu()
{
   if(!$('.main_menu')) {
      window.alert('unable to build menu on not-existing object .main_menu');
   }

   $('.main_menu').buildMenu({
      template:         'rpc.html',
      additionalData:   'type=rpc&action=get-sub-menu',
      menuWidth:        200,
      openOnRight:      false,
      openOnClick:      true,
      menuSelector:     '.menuSelector',
      iconPath:         'jquery/menu/ico/',
      hasImages:        false,
      fadeInTime:       200,
      fadeOutTime:      150,
      adjustLeft:       2,
      minZindex:        'auto',
      adjustTop:        10,
      opacity:          1.00,
      shadow:           true,
      shadowColor:      '#cccccc',
      hoverIntent:      1,
      closeOnMouseOut:  true,
      closeAfter:       1000
   });

} // load_menu()

$(document).ready(function() {
   $("table td a.delete").click(function(){
      obj_delete($(this));
   });
   $("table td div a.toggle-off, table td div a.toggle-on").click(function(){
      obj_toggle_status($(this));
   });
   $("table td a.move-up, table td a.move-down").click(function(){
      obj_alter_position($(this));
   });
   $("table td a.assign-pipe-to-chains").click(function(){
      obj_assign_pipe_to_chains($(this));
   });
   load_menu();
   //$.jqplot.config.enablePlugins = true;
});
