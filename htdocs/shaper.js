

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

function saveTarget()
{
   // Create object with values of the form
   var objTemp = new Object();

   objTemp['module'] = 'target';
   objTemp['action'] = 'modify';
   objTemp['target_new'] = document.forms['targets'].target_new.value;
   if(document.forms['targets'].target_new.value == 0) {
      objTemp['target_idx'] = document.forms['targets'].target_idx.value;
      objTemp['namebefore'] = document.forms['targets'].namebefore.value;
   }
   objTemp['target_name'] = document.forms['targets'].target_name.value;
   objTemp['target_match'] = currentRadio(document.forms['targets'].target_match);
   objTemp['target_ip'] = document.forms['targets'].target_ip.value;
   objTemp['target_mac'] = document.forms['targets'].target_mac.value;
   objTemp['target_group'] = document.forms['targets'].used;

   var target_used = new Array();
   var used = document.forms['targets'].elements['used[]'];
   for(i = 1; i < used.length; i++) {
      target_used[i-1] = used.options[i].value;
   }

   objTemp['target_used'] = target_used;

   var retr = HTML_AJAX.post('rpc.php?action=store', objTemp);

   if(retr == "ok") {
      refreshPage("targets");
   }
   else {
      window.alert(retr);
   }

} // saveTarget()

function deleteTarget(idx)
{
   // Create object with values of the form
   var objTemp = new Object();

   objTemp['module'] = 'target';
   objTemp['action'] = 'delete';
   objTemp['target_idx'] = idx;

   var retr = HTML_AJAX.post('rpc.php?action=store', objTemp);

   if(retr == "ok") {
      refreshPage("targets");
   }
   else {
      window.alert(retr);
   }
} // deleteTarget()

function savePort()
{
   // Create object with values of the form
   var objTemp = new Object();

   objTemp['module'] = 'port';
   objTemp['action'] = 'modify';
   objTemp['port_new'] = document.forms['ports'].port_new.value;
   if(document.forms['ports'].port_new.value == 0) {
      objTemp['port_idx'] = document.forms['ports'].port_idx.value;
      objTemp['namebefore'] = document.forms['ports'].namebefore.value;
   }
   objTemp['port_name'] = document.forms['ports'].port_name.value;
   objTemp['port_desc'] = document.forms['ports'].port_desc.value;
   objTemp['port_number'] = document.forms['ports'].port_number.value;

   var retr = HTML_AJAX.post('rpc.php?action=store', objTemp);

   if(retr == "ok") {
      refreshPage("ports");
   }
   else {
      window.alert(retr);
   }

} // savePort()

function deletePort(idx)
{
   // Create object with values of the form
   var objTemp = new Object();

   objTemp['module'] = 'port';
   objTemp['action'] = 'delete';
   objTemp['port_idx'] = idx;

   var retr = HTML_AJAX.post('rpc.php?action=store', objTemp);

   if(retr == "ok") {
      refreshPage("ports");
   }
   else {
      window.alert(retr);
   }
} // deletePort()

function saveProtocol()
{
   // Create object with values of the form
   var objTemp = new Object();

   objTemp['module'] = 'protocol';
   objTemp['action'] = 'modify';
   objTemp['proto_new'] = document.forms['protocols'].proto_new.value;
   if(document.forms['protocols'].proto_new.value == 0) {
      objTemp['proto_idx'] = document.forms['protocols'].proto_idx.value;
      objTemp['namebefore'] = document.forms['protocols'].namebefore.value;
   }
   objTemp['proto_name'] = document.forms['protocols'].proto_name.value;
   objTemp['proto_number'] = document.forms['protocols'].proto_number.value;

   var retr = HTML_AJAX.post('rpc.php?action=store', objTemp);

   if(retr == "ok") {
      refreshPage("protocols");
   }
   else {
      window.alert(retr);
   }

} // saveProtocol()

function deleteProtocol(idx)
{
   // Create object with values of the form
   var objTemp = new Object();

   objTemp['module'] = 'protocol';
   objTemp['action'] = 'delete';
   objTemp['proto_idx'] = idx;

   var retr = HTML_AJAX.post('rpc.php?action=store', objTemp);

   if(retr == "ok") {
      refreshPage("protocols");
   }
   else {
      window.alert(retr);
   }
} // deletePort()

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

function saveServiceLevel()
{
   // Create object with values of the form
   var objTemp = new Object();

   objTemp['module'] = 'servicelevel';
   objTemp['action'] = 'modify';

   sl_form = document.forms['servicelevels'];
   objTemp['sl_new'] = sl_form.sl_new.value;
   if(sl_form.sl_new.value == 0) {
      objTemp['sl_idx'] = sl_form.sl_idx.value;
      objTemp['namebefore'] = sl_form.namebefore.value;
   }
   objTemp['sl_name'] = sl_form.sl_name.value;

   if(sl_form.sl_htb_bw_in_rate != undefined) {
      objTemp['sl_htb_bw_in_rate'] = sl_form.sl_htb_bw_in_rate.value;
      objTemp['sl_htb_bw_in_ceil'] = sl_form.sl_htb_bw_in_ceil.value;
      objTemp['sl_htb_bw_in_burst'] = sl_form.sl_htb_bw_in_burst.value;
      objTemp['sl_htb_bw_out_rate'] = sl_form.sl_htb_bw_out_rate.value;
      objTemp['sl_htb_bw_out_ceil'] = sl_form.sl_htb_bw_out_ceil.value;
      objTemp['sl_htb_bw_out_burst'] = sl_form.sl_htb_bw_out_burst.value;
      objTemp['sl_htb_priority'] = sl_form.sl_htb_priority.value;
   }
   if(sl_form.sl_hfsc_in_umax != undefined) {
      objTemp['sl_hfsc_in_umax'] = sl_form.sl_hfsc_in_umax.value;
      objTemp['sl_hfsc_in_dmax'] = sl_form.sl_hfsc_in_dmax.value;
      objTemp['sl_hfsc_in_rate'] = sl_form.sl_hfsc_in_rate.value;
      objTemp['sl_hfsc_in_ulrate'] = sl_form.sl_hfsc_in_ulrate.value;
      objTemp['sl_hfsc_out_umax'] = sl_form.sl_hfsc_out_umax.value;
      objTemp['sl_hfsc_out_dmax'] = sl_form.sl_hfsc_out_dmax.value;
      objTemp['sl_hfsc_out_rate'] = sl_form.sl_hfsc_out_rate.value;
      objTemp['sl_hfsc_out_ulrate'] = sl_form.sl_hfsc_out_ulrate.value;
   }
   if(sl_form.sl_cbq_in_rate != undefined) {
      objTemp['sl_cbq_in_rate'] = sl_form.sl_cbq_in_rate.value;
      objTemp['sl_cbq_in_priority'] = sl_form.sl_cbq_in_priority.value;
      objTemp['sl_cbq_out_rate'] = sl_form.sl_cbq_out_rate.value;
      objTemp['sl_cbq_out_priority'] = sl_form.sl_cbq_priority.value;
      objTemp['sl_cbq_bounded'] = sl_form.sl_cbq_bounded.value;
   }

   objTemp['sl_qdisc'] = sl_form.sl_qdisc.value;
   if(sl_form.sl_qdisc.value == "NETEM") {
      objTemp['sl_netem_delay'] = sl_form.sl_netem_delay.value;
      objTemp['sl_netem_jitter'] = sl_form.sl_netem_jitter.value;
      objTemp['sl_netem_random'] = sl_form.sl_netem_random.value;
      objTemp['sl_netem_distribution'] = sl_form.sl_netem_distribution.value;
      objTemp['sl_netem_loss'] = sl_form.sl_netem_loss.value;
      objTemp['sl_netem_duplication'] = sl_form.sl_netem_duplication.value;
      objTemp['sl_netem_gap'] = sl_form.sl_netem_gap.value;
      objTemp['sl_netem_reorder_percentage'] = sl_form.sl_netem_reorder_percentage.value;
      objTemp['sl_netem_reorder_correlation'] = sl_form.sl_netem_reorder_correlation.value;
   }
   else if(sl_form.sl_qdisc.value == "ESFQ") {
      objTemp['sl_esfq_perturb'] = sl_form.sl_esfq_perturb.value;
      objTemp['sl_esfq_limit'] = sl_form.sl_esfq_limit.value;
      objTemp['sl_esfq_depth'] = sl_form.sl_esfq_depth.value;
      objTemp['sl_esfq_divisor'] = sl_form.sl_esfq_divisor.value;
      objTemp['sl_esfq_hash'] = sl_form.sl_esfq_hash.value;
   }

   var retr = HTML_AJAX.post('rpc.php?action=store', objTemp);

   if(retr == "ok") {
      refreshPage("servicelevels");
   }
   else {
      window.alert(retr);
   }

} // saveServiceLevel()

function deleteServiceLevel(idx)
{
   // Create object with values of the form
   var objTemp = new Object();

   objTemp['module'] = 'servicelevel';
   objTemp['action'] = 'delete';
   objTemp['sl_idx'] = idx;

   var retr = HTML_AJAX.post('rpc.php?action=store', objTemp);

   if(retr == "ok") {
      refreshPage("servicelevels");
   }
   else {
      window.alert(retr);
   }
} // deleteServiceLevel()

function saveOptions()
{
   // Create object with values of the form
   var objTemp = new Object();

   objTemp['module'] = 'options';
   objTemp['action'] = 'modify';

   opt_form = document.forms['options'];

   objTemp['language'] = currentSelect(opt_form.language);
   objTemp['ack_sl'] = currentSelect(opt_form.ack_sl);
   objTemp['classifier'] = currentSelect(opt_form.classifier);
   objTemp['qdisc'] = currentSelect(opt_form.qdisc);
   if(objTemp['qdisc'] == "ESFQ") {
      objTemp['sl_default_esfq_perturb'] = opt_form.sl_default_esfq_perturb.value;
      objTemp['sl_default_esfq_limit'] = opt_form.sl_default_esfq_limit.value;
      objTemp['sl_default_esfq_depth'] = opt_form.sl_default_esfq_depth.value;
      objTemp['sl_default_esfq_divisor'] = opt_form.sl_default_esfq_divisor.value;
      objTemp['sl_default_esfq_hash'] = opt_form.sl_default_esfq_hash.value;
   }
   objTemp['filter'] = currentRadio(opt_form.filter);
   objTemp['msmode'] = currentRadio(opt_form.msmode);
   objTemp['authentication'] = currentRadio(opt_form.authentication);

   var retr = HTML_AJAX.post('rpc.php?action=store', objTemp);

   if(retr == "ok") {
      refreshPage("options");
   }
   else {
      window.alert(retr);
   }

} // saveServiceLevel()

function saveUser()
{
   // Create object with values of the form
   var objTemp = new Object();

   objTemp['module'] = 'user';
   objTemp['action'] = 'modify';

   user_form = document.forms['users'];

   objTemp['user_new'] = user_form.user_new.value;
   if(objTemp['user_new'] == 0) {
      objTemp['user_idx'] = user_form.user_idx.value;
      objTemp['namebefore'] = user_form.namebefore.value;
   }
 
   objTemp['user_name'] = user_form.user_name.value;
   objTemp['user_pass1'] = user_form.user_pass1.value;
   objTemp['user_pass2'] = user_form.user_pass2.value;
   objTemp['user_active'] = currentRadio(user_form.user_active);
   objTemp['user_manage_chains'] = currentCheckbox(user_form.user_manage_chains);
   objTemp['user_manage_pipes'] = currentCheckbox(user_form.user_manage_pipes);
   objTemp['user_manage_filters'] = currentCheckbox(user_form.user_manage_filters);
   objTemp['user_manage_ports'] = currentCheckbox(user_form.user_manage_ports);
   objTemp['user_manage_protocols'] = currentCheckbox(user_form.user_manage_protocols);
   objTemp['user_manage_targets'] = currentCheckbox(user_form.user_manage_targets);
   objTemp['user_manage_users'] = currentCheckbox(user_form.user_manage_users);
   objTemp['user_manage_options'] = currentCheckbox(user_form.user_manage_options);
   objTemp['user_manage_servicelevels'] = currentCheckbox(user_form.user_manage_servicelevels);
   objTemp['user_load_rules'] = currentCheckbox(user_form.user_load_rules);
   objTemp['user_show_rules'] = currentCheckbox(user_form.user_show_rules);
   objTemp['user_show_monitor'] = currentCheckbox(user_form.user_show_monitor);

   var retr = HTML_AJAX.post('rpc.php?action=store', objTemp);

   if(retr == "ok") {
      refreshPage("users");
   }
   else {
      window.alert(retr);
   }

} // saveUser()

function deleteUser(idx)
{
   // Create object with values of the form
   var objTemp = new Object();

   objTemp['module'] = 'user';
   objTemp['action'] = 'delete';
   objTemp['user_idx'] = idx;

   var retr = HTML_AJAX.post('rpc.php?action=store', objTemp);

   if(retr == "ok") {
      refreshPage("users");
   }
   else {
      window.alert(retr);
   }
} // deleteUser()

function toggleUserStatus(idx, to)
{
   // Create object with values of the form
   var objTemp = new Object();

   objTemp['module'] = 'user';
   objTemp['action'] = 'toggle';
   objTemp['user_idx'] = idx;
   objTemp['to'] = to;

   var retr = HTML_AJAX.post('rpc.php?action=store', objTemp);

   if(retr == "ok") {
      refreshPage("users");
   }
   else {
      window.alert(retr);
   }
} // toggleUserStatus()

function saveInterface()
{
   // Create object with values of the form
   var objTemp = new Object();

   objTemp['module'] = 'interface';
   objTemp['action'] = 'modify';

   if_form = document.forms['interfaces'];

   objTemp['if_new'] = if_form.if_new.value;
   if(objTemp['if_new'] == 0) {
      objTemp['if_idx'] = if_form.if_idx.value;
      objTemp['namebefore'] = if_form.namebefore.value;
   }
 
   objTemp['if_name'] = if_form.if_name.value;
   objTemp['if_active'] = currentRadio(if_form.if_active);
   objTemp['if_speed'] = if_form.if_speed.value;
   objTemp['if_ifb'] = currentRadio(if_form.if_ifb);

   var retr = HTML_AJAX.post('rpc.php?action=store', objTemp);

   if(retr == "ok") {
      refreshPage("interfaces");
   }
   else {
      window.alert(retr);
   }

} // saveInterface()

function deleteInterface(idx)
{
   // Create object with values of the form
   var objTemp = new Object();

   objTemp['module'] = 'interface';
   objTemp['action'] = 'delete';
   objTemp['if_idx'] = idx;

   var retr = HTML_AJAX.post('rpc.php?action=store', objTemp);

   if(retr == "ok") {
      refreshPage("interfaces");
   }
   else {
      window.alert(retr);
   }
} // deleteInterface()

function toggleInterfaceStatus(idx, to)
{
   // Create object with values of the form
   var objTemp = new Object();

   objTemp['module'] = 'interface';
   objTemp['action'] = 'toggle';
   objTemp['if_idx'] = idx;
   objTemp['to'] = to;

   var retr = HTML_AJAX.post('rpc.php?action=store', objTemp);

   if(retr == "ok") {
      refreshPage("interfaces");
   }
   else {
      window.alert(retr);
   }
} // toggleInterfaceStatus()

function saveNetworkPath(obj)
{
   var retval = formSubmit(obj, null, {isAsync: false});

   if(retval == "ok") {
      refreshPage("networkpaths");
   }
   else {
      window.alert(retval);
   }

} // saveNetworkPath()

function deleteNetworkPath(idx)
{
   // Create object with values of the form
   var objTemp = new Object();

   objTemp['module'] = 'networkpath';
   objTemp['action'] = 'delete';
   objTemp['netpath_idx'] = idx;

   var retr = HTML_AJAX.post('rpc.php?action=store', objTemp);

   if(retr == "ok") {
      refreshPage("networkpaths");
   }
   else {
      window.alert(retr);
   }
} // deleteNetworkPath()

function toggleNetworkPathStatus(idx, to)
{
   // Create object with values of the form
   var objTemp = new Object();

   objTemp['module'] = 'networkpath';
   objTemp['action'] = 'toggle';
   objTemp['netpath_idx'] = idx;
   objTemp['to'] = to;

   var retr = HTML_AJAX.post('rpc.php?action=store', objTemp);

   if(retr == "ok") {
      refreshPage("networkpaths");
   }
   else {
      window.alert(retr);
   }
} // toggleInterfaceStatus()

function deleteFilter(idx)
{
   // Create object with values of the form
   var objTemp = new Object();

   objTemp['module'] = 'filter';
   objTemp['action'] = 'delete';
   objTemp['filter_idx'] = idx;

   var retr = HTML_AJAX.post('rpc.php?action=store', objTemp);

   if(retr == "ok") {
      refreshPage("filters");
   }
   else {
      window.alert(retr);
   }
} // deleteFilter()

function toggleFilterStatus(idx, to)
{
   // Create object with values of the form
   var objTemp = new Object();

   objTemp['module'] = 'filter';
   objTemp['action'] = 'toggle';
   objTemp['filter_idx'] = idx;
   objTemp['to'] = to;

   var retr = HTML_AJAX.post('rpc.php?action=store', objTemp);

   if(retr == "ok") {
      refreshPage("filters");
   }
   else {
      window.alert(retr);
   }
} // toggleFilterStatus()

function saveFilter(obj)
{
   selectAll(document.forms['filters'].elements['used[]']);
   var retval = formSubmit(obj, null, {isAsync: false});

   if(retval == "ok") {
      refreshPage("filters");
   }
   else {
      window.alert(retval);
   }

} // saveFilter()

function savePipe(obj)
{
   selectAll(document.forms['pipes'].elements['used[]']);
   var retval = formSubmit(obj, null, {isAsync: false});

   if(retval == "ok") {
      refreshPage("pipes");
   }
   else {
      window.alert(retval);
   }

} // savePipe()

function deletePipe(idx)
{
   // Create object with values of the form
   var objTemp = new Object();

   objTemp['module'] = 'pipe';
   objTemp['action'] = 'delete';
   objTemp['pipe_idx'] = idx;

   var retr = HTML_AJAX.post('rpc.php?action=store', objTemp);

   if(retr == "ok") {
      refreshPage("pipes");
   }
   else {
      window.alert(retr);
   }
} // deletePipe()

function togglePipeStatus(idx, to)
{
   // Create object with values of the form
   var objTemp = new Object();

   objTemp['module'] = 'pipe';
   objTemp['action'] = 'toggle';
   objTemp['pipe_idx'] = idx;
   objTemp['to'] = to;

   var retr = HTML_AJAX.post('rpc.php?action=store', objTemp);

   if(retr == "ok") {
      refreshPage("pipes");
   }
   else {
      window.alert(retr);
   }
} // toggleFilterStatus()

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
