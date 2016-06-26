/**
 *
 * This file is part of MasterShaper.

 * MasterShaper, a web application to handle Linux's traffic shaping
 * Copyright (C) 2015 Andreas Unterkircher <unki@netshadow.net>

 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.

 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.

 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

'use strict'

var NetScape4 = (navigator.appName == "Netscape" && parseInt(navigator.appVersion) < 5);
var autoload = undefined;
var jqp = undefined;

function moveOptions(theSelFrom, theSelTo)
{
    $('#' + theSelFrom + ' option:selected')
        .remove()
        .appendTo('#' + theSelTo)
        .removeAttr('selected');

    return true;
}

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
         //window.alert(data);
         $.drawIt(data);
      }
   });

   $.drawIt = function(data) {

      if(data == undefined)
         window.alert("Something went wrong when fetching values from server!");

      if(data.error != undefined)
         window.alert(data.error);

      if(data.notice != undefined)
         document.getElementById("jqp_monitor").innerHTML = data.notice;

      var time_end         = data.time_end;
      var show_interface   = data.show_interface;
      var scalemode        = data.scalemode;
      var graphmode        = data.graphmode;

      if(data.names)
         var names_obj = parse_json(data.names);
      if(data.colors)
         var colors_obj = parse_json(data.colors);

      /* default values */
      var seriesStack = false;
      var seriesFill  = true;
      var seriesRenderer        = $.jqplot.LineRenderer;
      var seriesRendererOptions = {};

      if(!data.data) {
         //window.alert(values);
         return;
      }

      // enable for some debugging output
      // document.getElementById("debug").innerHTML = 'Debug: ' + data.data + '<br />' + data.names + '<br />' + data.colors;

      var plot_obj  = parse_json(data.data);
      var plot_arr  = new Array();
      var names_arr = new Array();
      var names_ary = new Array();
      /* a default color is a must, otherwise jqplot refuses to work */
      var colors_arr = new Array('#4444aa');

      var title = 'Current Bandwidth Usage - '+ time_end +" - Interface "+ show_interface;
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
         names_ary[j] = names_obj[i];
         j++;
      }
      j = 0;
      for (var i in colors_obj) {
         colors_arr[j] = colors_obj[i];
         j++;
      }

      if(plot_arr == undefined || plot_arr.length < 1) {
         document.getElementById("jqp_monitor").innerHTML = 'No data to display';
         return;
      }

      /* accumulated lines */
      if(graphmode == 0) {
         seriesStack = true;
         seriesPointLabels       = {};
         xaxis_opts = {
            autoscale:           true,
            label:               'Time',
            renderer:            $.jqplot.DateAxisRenderer,
            tickOptions:         {formatString:'%H:%M:%S'}
         }
         plot_values = plot_arr;
      }
      /* simple lines */
      if(graphmode == 1) {
         seriesFill = false;
         seriesPointLabels       = {};
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
         seriesRendererOptions   = { barPadding: 8, barMargin: 20, varyBarColor: true };
         seriesPointLabels       = { show: true, location: 'n', edgeTolerance: -15 };
         xaxis_opts = {
            renderer:            $.jqplot.CategoryAxisRenderer,
            ticks:               names_ary
         };
         plot_values = [plot_arr];
      }
      /* pie */
      if(graphmode == 3) {
         seriesRenderer          = $.jqplot.PieRenderer;
         seriesRendererOptions   = { sliceMargin:0, showDataLabels: true, dataLabels: 'label' };
         seriesPointLabels       = {};
         xaxis_opts = {};
         plot_values = [plot_arr];
      }
      // enable for some debugging output
      // document.getElementById("debug").innerHTML = 'Debug: ' + plot_values + '<br />' + names_ary + '<br />' + colors_arr;

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
               enableFontSupport:   true
            },
            xaxis:                  xaxis_opts
         },
         seriesDefaults: {
            fill:                   seriesFill,
            showMarker:             true,
            renderer:               seriesRenderer,
            rendererOptions:        seriesRendererOptions,
            pointLabels:            seriesPointLabels
         },
         cursor:{
            show:                   true,
            showVerticalLine:       true,
            showHorizontalLine:     false,
            showTooltip:            true,
            showCursorLegend:       false,
            useAxesFormatters:      true,
            zoom:                   true
         },
         stackSeries:               seriesStack,
         series:                    names_arr,
         seriesColors:              colors_arr,
         legend:{
            show:                   true,
            placement:              'outsideGrid'
         }
       }
      );
      /* replot
         jqp.series[0].data = seriesStack;
         jqp.series[0].color = colors_arr;
         jqp.replot({ resetAxes: true });
      }*/
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
         if(data == "ok") {
            clearTimeout(autoload);
            image_update();
            autoload = setTimeout("image_autoload()", 10000);
            return true;
         }
         alert('Server returned: ' + data + ', length ' + data.length);
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
         action    : 'set-host-profile',
         hostprofile : hostprofile
      }),
      error: function(XMLHttpRequest, textStatus, errorThrown) {
         alert('Failed to contact server! ' + textStatus);
      },
      success: function(data){
         if(data == "ok") {
            window.location.reload();
            return true;
         }
         alert('Server returned: ' + data + ', length ' + data.length);
         return false;
      }
   });

} // set_host_profile()

function get_host_state()
{
   var selectbox = document.getElementsByName("active_host_profile")[0];

   if(!selectbox) {
      // silently return...
      setTimeout("get_host_state()", 2000);
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
         type : 'rpc',
         action : 'get-host-state',
         idx: hostprofile
      }),
      error: function(XMLHttpRequest, textStatus, errorThrown) {
         //for now we silently ignore errors here
         //alert('Failed to contact server! ' + textStatus + ' ' + errorThrown);
      },
      success: function(data){
         $('#readybusyico').attr('src', data);
      }
   });

   setTimeout("get_host_state()", 2000);

} // get_host_state()

function obj_clone(element, target, idx)
{
   var clone_id = element.attr("id");

   if(clone_id == undefined || clone_id == "") {
      alert('no attribute "id" found!');
      return;
   }

   $.ajax({
      type: "POST",
      url: "rpc.html",
      data: ({
         type : 'rpc',
         action : 'clone',
         id : clone_id
      }),
      beforeSend: function() {
         // change row color to red
         element.parent().parent().animate({backgroundColor: "#fbc7c7" }, "fast");
      },
      error: function(XMLHttpRequest, textStatus, errorThrown) {
         alert('Failed to contact server! ' + textStatus);
      },
      success: function(data){
         if(data == "ok") {
            window.location.reload();
            return;
         }
         // change row color back to white
         element.parent().parent().animate({backgroundColor: "#ffffff" }, "fast");
         alert('Server returned: ' + data + ', length ' + data.length);
         return;
      }
   });

} // obj_clone()

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
         if(data == "ok") {
            // toggle all parent's children
            $('#' + element.parent().attr("id") + ' > *').toggle();
            return true;
         }
         alert('Server returned: ' + data + ', length ' + data.length);
         return false;
      }
   });

} // obj_toggle_status()

function obj_toggle_checkbox(element)
{
   $(element).attr('checked', !$(element).attr('checked'));

} // obj_toggle_checkbox

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
   if(element.attr("parent"))
      obj_parent = element.attr("parent");

   $.ajax({
      type: "POST",
      url: "rpc.html",
      data: ({
         type     : 'rpc',
         action   : 'alter-position',
         move_obj : obj_type,
         id       : obj_idx,
         to       : obj_to
      }),
      error: function(XMLHttpRequest, textStatus, errorThrown) {
         alert('Failed to contact server! ' + textStatus);
      },
      success: function(data){

         if(data != "ok") {
            alert('Server returned: ' + data + ', length ' + data.length);
            return;
         }

         // moving chains
         if(obj_type == 'chain') {
            tableRow = $('tr#' + obj_type + obj_idx);
            return obj_alter_position_chain(tableRow, obj_idx, obj_to);
         }
         else if (obj_type == 'pipe') {
            tableRow = $('tr#' + obj_type + obj_idx);
            return obj_alter_position_pipe(tableRow, obj_idx, obj_to);
         }
         else if (obj_type == 'netpath') {
            tableRow = $('table#' + obj_type + obj_idx);
            return obj_alter_position_netpath(tableRow, obj_idx, obj_to);
         }
      }
   });

} // obj_alter_position()

function obj_alter_position_chain(tableRow, obj_idx, obj_to)
{
   // get our childrens (pipes and filters)
   tableChld = $('tr[type=pipe][chain='+ obj_idx +'], tr[type=filter][chain='+ obj_idx +']');

   // if element has childs, detach them from DOM temporary
   if(tableChld)
      tableChld.detach();

   /**
    * move object up
    */
   if(obj_to == 'up') {

      /* if object is on the first position */
      if(tableRow.parent().children("tr[type=chain]").index(tableRow) == 0) {

         /* append after last object */
         tableRow.parent().children(":last-child").after(tableRow);
      }
      /* for any other object */
      else {

         /* get objects new position (current position - 1) */
         newpos = tableRow.parent().children("tr[type=chain]").index(tableRow) - 1;

         /* move object before (current) object at our new position (so we will be newpos) */
         tableRow.parent().children("tr[type=chain]").eq(newpos).before(tableRow);
      }
   }

   /**
    * move object down
    */
   if(obj_to == 'down') {

      /* if object is on the last position */
      if(tableRow.parent().children("tr[type=chain]").length-1 == tableRow.parent().children("tr[type=chain]").index(tableRow)) {

         /* insert before first object */
         tableRow.parent().children(":first-child").before(tableRow);
      }
      /* if object is two before end */
      else if(tableRow.parent().children("tr[type=chain]").length-2 == tableRow.parent().children("tr[type=chain]").index(tableRow)) {

         /* append after last object */
         tableRow.parent().children(":last-child").after(tableRow);
      }
      /* for any other object */
      else {

         /* get objects new position (current position + 2) */
         newpos = tableRow.parent().children("tr[type=chain]").index(tableRow) + 2;

         /* by selecting two objects ahead we can insert our object right between those two */
         tableRow.parent().children("tr[type=chain]").eq(newpos).before(tableRow);
         /* so we do not have to take care if the next object would be expanded/collapsed */
      }
   }

   // insert all childrens after new objects position
   if(tableChld)
      tableChld.insertAfter(tableRow);

   return true;

} // obj_alter_position_chain()

function obj_alter_position_pipe(tableRow, obj_idx, obj_to)
{
   // get our childrens (filters)
   tableChld = $('tr[type=filter][pipe='+ obj_idx +']');

   if((chain_id = tableRow.attr('chain')) == undefined) {
      window.alert("unable to locate chain_id");
      return false;
   }

   pipes = $('tr[type=pipe][chain='+ chain_id +']');

   // if element has childs, detach them from DOM temporary
   if(tableChld)
      tableChld.detach();

   if(obj_to == 'up') {

      /* if on the first position */
      if(pipes.first().is(tableRow)) {

         // append after last object
         pipes.last().after(tableRow);
      }
      else {

         newpos = pipes.index(tableRow)-1;
         pipes.eq(newpos).before(tableRow);
      }
   }
   if(obj_to == 'down') {

      if(pipes.last().is(tableRow)) {

         // insert before first object
         pipes.first().before(tableRow);
      }
      else if (pipes.eq(-2).is(tableRow)) {

         // insert before first object
         pipes.last().after(tableRow);
      }
      else {

         newpos = pipes.index(tableRow)+2;
         pipes.eq(newpos).before(tableRow);
      }
   }

   // insert all childrens after new objects position
   if(tableChld)
      tableChld.insertAfter(tableRow);

   return true;

} // obj_alter_position_pipe()

function obj_alter_position_netpath(tableRow, obj_idx, obj_to)
{
   netpaths = $('table[type=netpath]');

   if(obj_to == 'up') {

      /* if on the first position */
      if(netpaths.first().is(tableRow)) {

         // append after last object
         netpaths.last().after(tableRow);
      }
      else {

         newpos = netpaths.index(tableRow)-1;
         netpaths.eq(newpos).before(tableRow);
      }
   }
   if(obj_to == 'down') {

      if(netpaths.last().is(tableRow)) {

         // insert before first object
         netpaths.first().before(tableRow);
      }
      else {

         newpos = netpaths.index(tableRow)+1;
         netpaths.eq(newpos).after(tableRow);
      }
   }

   return true;

} // obj_alter_position_netpath()

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
            action : 'get-content',
            content : 'chains-list',
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

/**
 * change to link
 *
 * this function awaits as parameter a URL as
 * generated by get_page_url() method of rewriter.
 * ex. /shaper/network-paths/edit-[id].html
 *
 * it replaces then [id] by $id
 *
 * @param string link
 * @param int id
 * @return bool
 */
function change_to(link, id)
{
   if(link == undefined || link == "")
      return false;

   if(id == undefined || id == "")
      return false;

   link = link.replace('[id]', id);

   location.href = link;
   return true;

} // change_to()

function toggle_content(element, imgobj, imgshow, imghide, imgobjoth)
{
   var state = $(imgobj).attr('state');

   if(state != "hidden" && state != "shown") {
      window.alert('toggle_content(): unknown state - ' + state);
   }

   if(state == "hidden") {
      $(element).show('highlight', 500);
      if(imgshow && imghide) {
         $(imgobj).attr('state', 'shown');
         $(imgobj).attr('src', imgshow);
         if(imgobjoth) {
            $(imgobjoth).attr('state', 'shown');
            $(imgobjoth).attr('src', imgshow);
         }
      }
   }
   else {
      $(element).hide('highlight', 500);
      if(imgshow && imghide) {
         $(imgobj).attr('state', 'hidden');
         $(imgobj).attr('src', imghide);
         if(imgobjoth) {
            $(imgobjoth).attr('state', 'hidden');
            $(imgobjoth).attr('src', imghide);
         }
      }
   }

} // toggle_content()

function obj_save(form)
{
    var input, form_next_url;

    if (typeof (form_next_url = form.attr('data-url-next')) === 'undefined') {
        form_next_url = window.location.href;
        return false;
    }

    if (typeof mbus === 'undefined') {
        throw new Error('MessageBus is not available!');
        return false;
    }

    /*
     * if the <form> itself provides id, guid and model. take that one.
     */
    var values = new Object;
    var form_id = form.attr('data-id');
    var form_guid = form.attr('data-guid');
    var form_model = form.attr('data-model');
    if (typeof form_id !== 'undefined' &&
        typeof form_guid !== 'undefined' &&
        typeof form_model !== 'undefined'
    ) {
        var form_has_model = true;
    }

    if (!(input = form.find('.ui.checkbox, input[type="text"], textarea, select'))) {
        throw new Error('failed to locate any form elements!');
        return false;
    }

    input.each (function (index, input_element) {
        var id, guid, model, name, key, field, element;

        if (typeof form_has_model === 'undefined') {
            values = new Object;
        }

        if (typeof (element = $(input_element)) === 'undefined') {
            throw new Error('failed to retrieve jQuery object on element!');
            return false;
        }

        if (element.is('.ui.checkbox')) {
            if (typeof (name = element.find('input[type="radio"],input[type="checkbox"]').attr('name')) === 'undefined') {
                throw new Error('do not know how to identify element '+ input_element + '!');
                return false;
            }
        } else {
            if (typeof (name = element.attr('name')) === 'undefined') {
                throw new Error('do not know how to identify element '+ input_element + '!');
                return false;
            }
        }

        if (typeof form_has_model === 'undefined') {
            if (typeof (id = element.attr('data-id')) === 'undefined') {
                throw new Error('failed to locate data-id attribute!');
                return false;
            }

            if (typeof (guid = element.attr('data-guid')) === 'undefined') {
                throw new Error('failed to locate data-guid attribute!');
                return false;
            }

            if (typeof (model = element.attr('data-model')) === 'undefined') {
                throw new Error('failed to locate data-model attribute!');
                return false;
            }
        }

        if (typeof (key = element.attr('data-key')) !== 'undefined') {
            values[key] = name;
        }

        if (typeof (field = element.attr('data-field')) === 'undefined') {
            field = name;
        }

        /*
         * INPUT elements
         */
        if (element.prop('nodeName') === 'INPUT') {
            /*
             * text or password fields
             */
            if (element.attr('type') === 'text') {
                values[field] = element.val();
            } else if (field.attr('type') === 'password') {
                values[field] = element.val();
            /*
             * checkbox fields
             */
            } else if (element.attr('type') === 'checkbox') {
                if (element.is(':checked')) {
                    values[field] = element.val();
                }
            /*
             * radio checkbox fields
             */
            } else if (element.attr('type') === 'radio') {
                if (element.is(':checked')) {
                    values[field] = element.val();
                }
            } else {
                throw new Error('unsupported type! ' + element.attr('type'));
                return false;
            }
            /*
             * textarea elements
             */
        } else if (element.prop('nodeName') === 'TEXTAREA') {
            values[field] = element.text();
        /*
         * select/dropdown elements
         */
        } else if (element.prop('nodeName') === 'SELECT') {
            // document.forms['servicelevels'].classifier.options[document.forms['servicelevels'].classifier.selectedIndex].value
            values[field] = element.val();
        /*
         * general-purpose elements used as checkboxes
         */
        } else if (element.prop('nodeName') === 'DIV' && element.is('.ui.checkbox')) {
            // if it is a radio element, skip it, if it is not checked
            if (element.checkbox('is radio') && !element.checkbox('is checked')) {
                return true;
            } else if (!element.checkbox('is checked')) {
                values[field] = 'N';
            } else if (typeof (values[field] = element.find('input[type="radio"],input[type="checkbox"]').val()) === 'undefined') {
                throw new Error('failed to read radio element value!');
                return false;
            }
        } else {
            throw new Error('unsupported nodeName!');
            return false;
        }

        if (typeof values[field] === 'undefined') {
            values[field] = '';
        }

        if (typeof form_has_model === 'undefined') {
            values['id'] = id;
            values['guid'] = guid;
            values['model'] = model;

            var msg = new ThalliumMessage;
            msg.setCommand('save-request');
            msg.setMessage(values);
            if (!mbus.add(msg)) {
                throw new Error('ThalliumMessageBus.add() returned false!');
                return false;
            }

            return true;
        }
    });

    if (typeof form_has_model !== 'undefined') {
      values['id'] = form_id;
      values['guid'] = form_guid;
      values['model'] = form_model;

      var msg = new ThalliumMessage;
      msg.setCommand('save-request');
      msg.setMessage(values);
      if (!mbus.add(msg)) {
         throw new Error('ThalliumMessageBus.add() returned false!');
         return false;
      }
    }

    var save_timeout = setTimeout(function () {
        var save = $(this).find('.ui.button.save');
        // turn button red
        save.removeClass('positive').addClass('negative');
        // unsubscribe from MessageBus
        mbus.unsubscribe('save-replies-handler');
        // remove the loader
        save.find('.ui.inverted.dimmer').removeClass('active');
        // show a popup message
        save.popup({
            on          : 'manual',
            preserve    : true,
            exclusive   : true,
            lastResort  : true,
            content     : 'Saving failed - 10sec timeout reached! Click the save button to try again.',
            position    : 'top center',
            transition  : 'slide up'
        })
            .addClass('flowing red')
            .popup('show');
    }.bind(form), 10000);

    mbus.subscribe('save-replies-handler', 'save-reply', function (reply) {
        var newData, value;

        if (typeof reply === 'undefined' || !reply) {
            throw new Error('reply is empty!');
            return false;
        }
        newData = new Object;

        if (reply.value && (value = reply.value.match(/([0-9]+)%$/))) {
            newData.percent = value[1];
        }
        if (reply.body != 'Done') {
            return true;
        }
        clearTimeout(save_timeout);
        mbus.unsubscribe('save-replies-handler');
        $(this).find('.ui.button.save .ui.inverted.dimmer').removeClass('active');
        location.href = form_next_url;
        return true;
    }.bind(form));

    if (!mbus.send()) {
        throw 'ThalliumMessageBus.send() returned false!';
        return false;
    }
    return false;
}

$(document).ready(function() {
    $('.ui.checkbox').checkbox();
    $('.ui.accordion').accordion();
    $('.thallium.ui.form').form();
    $('.thallium.ui.form').submit(function () {
        return obj_save($(this));
    });
    $('.thallium.ui.form .ui.button.discard').click(function () {
        var form;
        if (typeof (form = $(this).closest('.thallium.ui.form')) === 'undefined') {
            window.location.href = window.location.href;
            return false;
        }
        var discard_url;
        if (typeof (discard_url = form.attr('data-url-discard')) === 'undefined') {
            window.location.href = window.location.href;
            return false;
        }
        if (new RegExp('^(?:[a-z]+:)?//', 'i').test(discard_url)) {
            throw new Error('data-url-discard attribute contains an URL outside of '+ window.location.hostname + '!');
            return false;
        }
        window.location.href = discard_url;
        return false;
    });
    $('.thallium.ui.form .ui.button.save').click(function () {
        $(this)
            .popup('hide')
            .find('.ui.inverted.dimmer')
            .addClass('active');
    });
    $('.thallium.ui.form .ui.button.reset').click(function () {
        var form;
        if (typeof (form = $(this).closest('.thallium.ui.form')) === 'undefined') {
            return false;
        }
        form.form('reset');
        return false;
    });
    $("table td a.clone").click(function(){
        obj_clone($(this));
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
    $('img.change_to').hover(
        function() {
            $(this).css('cursor','pointer');
        },
        function() {
            $(this).css('cursor','auto');
        }
    );
    // immediately update our host state
    setTimeout("get_host_state()", 250);
    //$.jqplot.config.enablePlugins = true;

    $('.item.state').click(function () {
        $(this).find('.ui.inverted.dimmer').addClass('active');
        return rpc_object_update($(this), function (element, data) {
            if (data != "ok") {
                $(this).find('.ui.inverted.dimmer').removeClass('active');
                $(this).checkbox('toggle');
                return true;
            }
            $(this).find('.ui.inverted.dimmer').removeClass('active');
            return true;
        }.bind(this));
        return true;
    });

    // Automatically shows on init if cookie isnt set
    $('.cookie.nag.zendopcache.missing').nag('show');
});

// vim: set filetype=javascript expandtab softtabstop=4 tabstop=4 shiftwidth=4:
