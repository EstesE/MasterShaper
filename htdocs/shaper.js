

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

function refreshContent(req_content)
{
   if(req_content == undefined)
      req_content = "";

   var content = document.getElementById("content");
   content.innerHTML = "Loading...";

   content.innerHTML = HTML_AJAX.grab(encodeURI('rpc.php?action=get_content&request=' + req_content));
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
      refreshPageTitle();
      refreshMainMenu();
      refreshSubMenu();
      refreshContent(content);
}

function js_logout()
{
   HTML_AJAX.grab(encodeURI('rpc.php?action=logout'));
   refreshPage();
}

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
