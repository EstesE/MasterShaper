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

function AskServerWhatToDo()
{
   return HTML_AJAX.grab(encodeURI('rpc.php?action=what_to_do'));
}

function init_shaper()
{
   /*whattodo = AskServerWhatToDo();

   if(whattodo == "") {
     refreshContent();
   }

   if(whattodo == "show_overview")
      refreshContent("overview");
   */

} // init_shaper()

function refreshContent(req_content, options)
{
   if(req_content == undefined)
      req_content = "";

   var content = document.getElementById("content");
   content.innerHTML = "Loading...";
   var url = 'rpc.php?action=get_content&request=' + req_content;
   if(options != undefined) {
      url = url+options;
   }

   content.innerHTML = HTML_AJAX.grab(encodeURI(url));
}

function ruleset(mode)
{
   if(mode == undefined)
      mode = "";

   var content = document.getElementById("content");
   content.innerHTML = "Loading...";
   var url = 'rpc.php?action=ruleset&mode=' + mode;
   content.innerHTML = HTML_AJAX.grab(encodeURI(url));
} // ruleset()

function monitor(mode)
{
   if(mode == undefined)
      mode = "";

   var content = document.getElementById("content");
   content.innerHTML = "Loading...";
   var url = 'rpc.php?action=monitor&mode=' + mode;
   content.innerHTML = HTML_AJAX.grab(encodeURI(url));

   /* now start auto image reloading */
   image_start_autoload();

} // monitor()

function draw_jqplot()
{
   var url        = 'rpc.php?action=jqplot';
   var values     = HTML_AJAX.grab(encodeURI(url));
   var data       = parse_json(values);

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

   if(plot_arr.length < 1) {
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
      title:       title,
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

function check_login()
{
   if(document.forms['login'].user_name.value == "") {
      window.alert("Please enter a username");
      return;
   }
   if(document.forms['login'].user_pass.value == "") {
      window.alert("Please enter a password");
      return;
   }

   // Create object with values of the form
   var objTemp = new Object();
   objTemp['user_name'] = document.forms['login'].user_name.value;
   objTemp['user_pass'] = document.forms['login'].user_pass.value;

   var retr = HTML_AJAX.post('rpc.php?action=check_login', objTemp);

   if(retr == "ok") {
      refreshPage("overview");
   }
   else {
      window.alert(retr);
   }

}

function refreshPage(content)
{
   refreshContent(content);

} // refreshPage()

function js_logout()
{
   HTML_AJAX.grab(encodeURI('rpc.php?action=logout'));
   refreshPage();
} // js_logout()

function WSR_getElementsByClassName(oElm, strTagName, oClassNames){
   var arrElements = (strTagName == "*" && oElm.all)? oElm.all : oElm.getElementsByTagName(strTagName);
   var arrReturnElements = new Array();
   var arrRegExpClassNames = new Array();
   if(typeof oClassNames == "object"){
      for(var i=0; i<oClassNames.length; i++){
         arrRegExpClassNames.push(new RegExp("(^|\s)" + oClassNames[i].replace(/-/g, "\-") + "(\s|$)"));
      }
   }
   else{
      arrRegExpClassNames.push(new RegExp("(^|\s)" + oClassNames.replace(/-/g, "\-") + "(\s|$)"));
   }
   var oElement;
   var bMatchesAll;
   for(var j=0; j<arrElements.length; j++){
      oElement = arrElements[j];
      bMatchesAll = true;
      for(var k=0; k<arrRegExpClassNames.length; k++){
         if(!arrRegExpClassNames[k].test(oElement.className)){
            bMatchesAll = false;
            break;
         }
      }
      if(bMatchesAll){
         arrReturnElements.push(oElement);
      }
   }

   return (arrReturnElements);
}

function deleteObj(module, target, idx)
{
   // Create object with values of the form
   var objTemp = new Object();
   objTemp['module'] = module;
   objTemp['action'] = 'delete';
   objTemp['idx'] = idx;
   var retr = HTML_AJAX.post('rpc.php?action=store', objTemp);
   if(retr == "ok") {
      refreshPage(target);
   }
   else {
      window.alert(retr);
   }

} // delete()

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

function toggleStatus(module, target, idx, to)
{
   // Create object with values of the form
   var objTemp = new Object();
   objTemp['module'] = module;
   objTemp['action'] = 'toggle';
   objTemp['idx'] = idx;
   objTemp['to'] = to;
   var retr = HTML_AJAX.post('rpc.php?action=store', objTemp);
   if(retr == "ok") {
      refreshPage(target);
   }
   else {
      window.alert(retr);
   }
} // toggleStatus()

function saveForm(obj, target)
{
   var retval = formSubmit(obj, null, {isAsync: false});
   if(retval == "ok") {
      refreshPage(target);
   }
   else {
      window.alert(retval);
   }
} // saveForm()

/**
 * stolen from HTM_AJAX, since it seems to have a bug as 
 * it always returns true.
 * see http://pear.php.net/bugs/bug.php?id=12415
 */
function formSubmit(form, target, options)
{
   form = HTML_AJAX_Util.getElement(form);
   if (!form) {
      // let the submit be processed normally
      return false;
   }

   var out = HTML_AJAX.formEncode(form);
   target = HTML_AJAX_Util.getElement(target);
   if (!target) {
      target = form;
   }
   try
   {
      var action = form.attributes['action'].value;
   }
   catch(e){}
   if(action == undefined)
   {
      action = form.getAttribute('action');
   }
   var callback = false;
   if (HTML_AJAX_Util.getType(target) == 'function') {
      callback = target;
   }
   else {
      callback = function(result) {
         // result will be undefined if HA_Action is returned, so skip the replace
         if (typeof result != 'undefined') {
            HTML_AJAX_Util.setInnerHTML(target,result);
         }
      }
   }
   var serializer = HTML_AJAX.serializerForEncoding('Null');
   var request = new HTML_AJAX_Request(serializer);
   request.isAsync = true;
   request.callback = callback;

   switch (form.getAttribute('method').toLowerCase()) {
      case 'post':
         var headers = {};
         headers['Content-Type'] = 'application/x-www-form-urlencoded';
         request.customHeaders = headers;
         request.requestType = 'POST';
         request.requestUrl = action;
         request.args = out;
         break;
      default:
         if (action.indexOf('?') == -1) {
            out = '?' + out.substr(0, out.length - 1);
         }
         request.requestUrl = action+out;
         request.requestType = 'GET';
   }

   if(options) {
      for(var i in options) {
         request[i] = options[i];
      }
   }

   if(request.isAsync == true) {
      HTML_AJAX.makeRequest(request);
      return true;
   }
   else {
      return HTML_AJAX.makeRequest(request);
   }
}

function alterPosition(type, idx, to)
{
   // Create object with values of the form
   var objTemp = new Object();
   objTemp['type'] = type;
   objTemp['idx'] = idx;
   objTemp['to'] = to;

   var retr = HTML_AJAX.post('rpc.php?action=alter_position', objTemp);

   if(retr == "ok") {
      refreshPage("overview");
   }
   else {
      window.alert(retr);
   }

} // alterPosition()

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

function graph_set_mode(mode)
{
   // Create object with values of the form
   var objTemp = new Object();
   objTemp['action'] = 'graphmode';
   objTemp['value'] = mode;

   var retr = HTML_AJAX.post('rpc.php?action=changegraph', objTemp);
   image_update();

} // graph_set_mode()

function graph_set_scalemode(obj)
{
   // Create object with values of the form
   var objTemp = new Object();
   objTemp['action'] = 'scalemode';
   objTemp['value'] = obj.options[obj.selectedIndex].value;

   var retr = HTML_AJAX.post('rpc.php?action=changegraph', objTemp);
   image_update();

} // graph_set_scalemode()

function graph_set_interface(obj)
{
   // Create object with values of the form
   var objTemp = new Object();
   objTemp['action'] = 'interface';
   objTemp['value'] = obj.options[obj.selectedIndex].value;

   var retr = HTML_AJAX.post('rpc.php?action=changegraph', objTemp);
   image_update();

} // graph_set_interface()

function graph_set_chain(obj)
{
   // Create object with values of the form
   var objTemp = new Object();
   objTemp['action'] = 'chain';
   objTemp['value'] = obj.options[obj.selectedIndex].value;

   var retr = HTML_AJAX.post('rpc.php?action=changegraph', objTemp);
   image_update();

} // graph_set_chain()

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
