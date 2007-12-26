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

function selectAll(theSelForm)
{
	var lent = theSelForm.length ;
 
	for (var i=0; i<lent; i++) {
		theSelForm.options[i].selected = true;
	}
}

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
   refreshPageTitle();
   refreshMainMenu();
   refreshSubMenu();
 
   whattodo = AskServerWhatToDo();

   if(whattodo == "") {
     refreshContent();
   }

   if(whattodo == "show_overview")
      refreshContent("overview");

}

function refreshPageTitle()
{
   var page_title = document.getElementById("page_title");
   page_title.innerHTML = "Loading...";
   page_title.innerHTML = HTML_AJAX.grab(encodeURI('rpc.php?action=get_page_title'));
   
}

function refreshMainMenu()
{
   var main_menu = document.getElementById("main_menu");
   main_menu.innerHTML = "Loading...";
   main_menu.innerHTML = HTML_AJAX.grab(encodeURI('rpc.php?action=get_main_menu'));
}

function refreshSubMenu()
{
}

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
   refreshPageTitle();
   refreshMainMenu();
   refreshSubMenu();
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
   return (arrReturnElements)
}

function updateSubMenu(mode)
{
   var submenu = document.getElementById("submenu");
   var content = "";
   content = HTML_AJAX.grab('rpc.php?action=get_sub_menu&navpoint=' + mode);
   submenu.innerHTML = content;
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
   /* get the current image url */
   url = document.getElementById("monitor_image").src;
   /* remove the current uniq id from the string */
   url = url.replace(/\?uniqid=.*/, '');
   uniq = new Date();
   uniq = "?uniqid="+uniq.getTime();
   /* reload the image with a new uniq id */
   document.getElementById("monitor_image").src = url + uniq;

} // image_update()

function image_autoload()
{
   image_update();

   if(document.getElementById("reload").checked) {
      autoload = undefined;
      image_start_autoload();
   }

} // image_autoload

function image_start_autoload()
{
   if(autoload == undefined) {
      autoload = setTimeout("image_autoload()", 5000);
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

function setgraphmode(mode)
{
   // Create object with values of the form
   var objTemp = new Object();
   objTemp['action'] = 'graphmode';
   objTemp['value'] = mode;

   var retr = HTML_AJAX.post('rpc.php?action=changegraph', objTemp);
   image_update();

} // setgraphmode()

function setscalemode(obj)
{
   // Create object with values of the form
   var objTemp = new Object();
   objTemp['action'] = 'scalemode';
   objTemp['value'] = obj.options[obj.selectedIndex].value;

   var retr = HTML_AJAX.post('rpc.php?action=changegraph', objTemp);
   image_update();

} // setscalemode()
