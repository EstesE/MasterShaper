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
   $.ajax({
      type: 'POST',
      url: 'rpc.html',
      data: ({
         type : 'rpc',
         action : 'jqplot-data',
         view : 'chains-jqPlot'
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

      var start_time  = data.start_time;
      var end_time    = data.end_time;
      var interface   = data.interface;
      var scalemode   = data.scalemode;
      var graphmode   = data.graphmode;

      if(data.names)
         var names_obj= parse_json(data.names);

      /* default values */
      var seriesStack = false;
      var seriesFill  = true;
      var seriesRenderer        = $.jqplot.LineRenderer;
      var seriesRendererOptions = {};

      if(!data.data) {
         window.alert(values);
         return;
      }

      var plot_obj  = parse_json(data.data);
      var plot_arr  = new Array();
      var names_arr = new Array();

      var title = 'Current Bandwidth Usage - '+ end_time +" - Interface "+ interface;

      if(scalemode == "kbit")
         ylabel = "Bandwidth kbits per second";
      if(scalemode == "kbyte")
         ylabel = "Bandwidth kbytes per second";
      if(scalemode == "Mbit")
         ylabel = "Bandwidth Mbits per second";
      if(scalemode == "Mbyte")
         ylabel = "Bandwidth Mbytes per second";
      if(scalemode == undefined)
         ylabel = "Bandwidth per second";

      /* transform object to array */
      var j = 0;
      for (var i in plot_obj) {
         plot_arr[j] = plot_obj[i];
         j++;
      }
      j = 0;
      for (var i in names_obj) {
         names_arr[j] = { label: names_obj[i] };
         j++;
      }

      if(plot_arr == undefined || plot_arr.length < 1) {
         document.getElementById("jqp_monitor").innerHTML = 'No data to display';
      }

      /* accumulated lines */
      if(graphmode == 0) {
         seriesStack = true;
      }
      /* simple lines */
      if(graphmode == 1) {
         seriesFill = false;
      }
      /* bars */
      if(graphmode == 2) {
         seriesRenderer          = $.jqplot.BarRenderer;
         seriesRendererOptions   = { barPadding: 8, barMargin: 20 };
      }
      /* pie */
      if(graphmode == 3) {
         seriesRenderer          = $.jqplot.PieRenderer;
         seriesRendererOptions   = { sliceMargin:8 };
      }

      $('#jqp_monitor').empty();
      $.jqplot('jqp_monitor', plot_arr, {
         title: title,
         stackSeries: seriesStack,
         axes:{
            yaxis: {
               labelRenderer:     $.jqplot.CanvasAxisLabelRenderer,
               label:             ylabel,
               autoscale:         true,
               min:               0,
               angel:             90,
               enableFontSupport: true
            },
            xaxis: {
               autoscale:  false,
            }
         },
         seriesDefaults: {
            fill:       seriesFill,
            showMarker: false,
            renderer:   seriesRenderer,
            rendererOptions: seriesRendererOptions
         },
         series: names_arr,
         cursor:{
            zoom:        true,
            showTooltip: true
         },
         /*legend:{
            show:       true,
            location:   'ne',
            xoffset:    -70
         }*/
       }
      );

      var legend = document.getElementById('jqp_legend');

      legend.innerHTML = '';

      for(var arrkey in names_arr) {
         legend.innerHTML+= "<br />" + names_arr[arrkey].label;
      }
   }
}

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
         element.parent().parent().animate({backgroundColor: "#fbc7c7" }, "fast");
      },
      error: function(XMLHttpRequest, textStatus, errorThrown) {
         alert('Failed to contact server! ' + textStatus);
      },
      success: function(data){
         element.parent().parent().animate({ opacity: "hide" }, "fast");
         if(data == "ok\n")
            return;
         alert('Server returned: ' + data);
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

function currentSelect(obj)
{
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

   if(toggle_id == undefined || toggle_id == "") {
      alert('no attribute "id" found!');
      return;
   }
   if(toggle_to == undefined || toggle_to == "") {
      alert('no attribute "to" found!');
      return;
   }

   $.ajax({
      type: "POST",
      url: "rpc.html",
      data: ({type : 'rpc', action : 'toggle', id : toggle_id, to : toggle_to }),
      error: function(XMLHttpRequest, textStatus, errorThrown) {
         alert('Failed to contact server! ' + textStatus);
      },
      success: function(data){
         // toggle all parent's children
         $('#' + element.parent().attr("id") + ' > *').toggle();
         if(data == "ok\n")
            return;
         alert('Server returned: ' + data + data.length);
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

function image_update()
{
   if(document.getElementById("monitor_image")) {
      /* get the current image url */
      url = document.getElementById("monitor_image").src;
      /* remove the current uniq id from the string */
      url = url.replace(/\?uniqid=.*/, '');
      uniq = new Date();
      uniq = "?uniqid="+uniq.getTime();
      /* reload the image with a new uniq id */
      document.getElementById("monitor_image").src = url + uniq;
   }
   if(document.getElementById("jqp_monitor")) {
      draw_jqplot();
   }

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
      autoload = setTimeout("image_autoload()", 5000);
   }

   /* load jqplot for first time */
   if(document.getElementById("jqp_monitor")) {
      draw_jqplot();
   }

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
});
