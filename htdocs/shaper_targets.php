<?php

/***************************************************************************
 *
 * Copyright (c) by Andreas Unterkircher, unki@netshadow.at
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

class MSTARGETS {

   var $db;
   var $parent;

   /* Class constructor */
   function MSTARGETS($parent)
   {
      $this->db = $parent->db;
      $this->parent = $parent;
   } // MSTARGETS()

   /* interface output */
   function showHtml()
   {

      /* If authentication is enabled, check permissions */
      if($this->parent->getOption("authentication") == "Y" &&
	 !$this->parent->checkPermissions("user_manage_targets")) {

	 $this->parent->printError("<img src=\"". ICON_TARGETS ."\" alt=\"target icon\" />&nbsp;". _("Manage Targets"), _("You do not have enough permissions to access this module!"));
	 return 0;

      }

      if(!isset($this->parent->screen))
         $this->parent->screen = 0;

      switch($this->parent->screen) {

	 default:
	 case 0:

	    $this->parent->startTable("<img src=\"". ICON_TARGETS ."\" alt=\"target icon\" />&nbsp;". _("Manage Targets"));
?>
  <table style="width: 100%;" class="withborder">
   <tr>
<?php
	    if(isset($_GET['saved'])) {
?>
    <td colspan="3" style="text-align: center;" class="sysmessage"><?php print _("You have made changes to the ruleset. Don't forget to reload them."); ?></td>
<?php
	    } else {
?>
    <td colspan="3">&nbsp;</td>
<?php
	    }
?>
   </tr>
   <tr>
    <td colspan="3" style="text-align: center;">
     <img src="<?php print ICON_NEW; ?>" alt="new icon" />
     <a href="<?php print $this->parent->self ."?mode=". $this->parent->mode ."&amp;screen=". MANAGE ."&amp;new=1"; ?>"><? print _("Create a new Target"); ?></a>
    </td>
   </tr>
   <tr>
    <td colspan="3">&nbsp;</td>
   </tr>
   <tr>
    <td><img src="<?php print ICON_TARGETS; ?>" alt="target icon" />&nbsp;<i><? print _("Targets"); ?></i></td>
    <td><img src="<?php print ICON_TARGETS; ?>" alt="target icon" />&nbsp;<i><? print _("Details"); ?></i></td>
    <td style="text-align: center;"><i><?php print _("Options"); ?></i></td>
   </tr>
<?php

	    $result = $this->db->db_query("SELECT target_idx, target_name, target_match FROM ". MYSQL_PREFIX ."targets ORDER BY target_name ASC");

	    while($row = $result->fetchrow()) {
?>
   <tr onmouseover="setBackGrdColor(this, 'mouseover');" onmouseout="setBackGrdColor(this, 'mouseout');">
    <td>
     <img src="<?php print ICON_TARGETS; ?>" alt="target icon" />
     <a href="<?php print $this->parent->self ."?mode=". $this->parent->mode ."&amp;screen=". MANAGE ."&amp;namebefore=". urlencode($row->target_name) ."&amp;idx=". $row->target_idx; ?>">
      <?php print $row->target_name; ?>
     </a>
    </td>
    <td>
     <img src="<?php print ICON_TARGETS; ?>" alt="target icon" />
<?php
	       switch($row->target_match) {

		  case 'IP':
		     print _("IP match");
		     break;
		  case 'MAC':
		     print _("MAC match");
		     break;
		  case 'GROUP':
		     print _("Target Group");
		     break;
	       }
?>
    </td>
    <td style="text-align: center;">
     <a href="<?php print $this->parent->self ."?mode=". $this->parent->mode ."&amp;screen=". DELETE ."&amp;idx=". $row->target_idx ."&amp;name=". urlencode($row->target_name); ?>" title="Delete"><img src="<? print ICON_DELETE; ?>" alt="delete icon" /></a>
    </td>
   </tr>
<?php
	    }
?>
  </table>
<?php
            $this->parent->closeTable();
	    break;

	 case MANAGE:

	    if(!isset($_GET['saveit'])) {

               if(isset($_GET['new'])) {

		  $this->parent->startTable("<img src=\"". ICON_TARGETS ."\" alt=\"target icon\" />&nbsp;". _("Create a new Target"));
		  $form_url = $this->parent->self ."?mode=". $this->parent->mode ."&amp;screen=". $this->parent->screen ."&amp;saveit=1&amp;new=1";

		  $current->target_match = "IP";

               }
	       else {

	          $current = $this->db->db_fetchSingleRow("SELECT * FROM ". MYSQL_PREFIX ."targets WHERE target_idx='". $_GET['idx'] ."'");
		  $this->parent->startTable("<img src=\"". ICON_TARGETS ."\" alt=\"target icon\" />&nbsp;". _("Modify Target") ." ". $this->parent->getClassVar($current, 'target_name'));
		  $form_url = $this->parent->self ."?mode=". $this->parent->mode ."&amp;screen=". $this->parent->screen ."&amp;saveit=1&amp;namebefore=". urlencode($this->parent->getClassVar($current, 'target_name')) ."&amp;idx=". $_GET['idx'];

               }
?>
  <form action="<?php print $form_url; ?>" method="post" id="targets">
   <table style="width: 100%;" class="withborder2"> 
    <tr>
     <td colspan="3">
      <img src="<?php print ICON_TARGETS; ?>" alt="target icon" />&nbsp;<? print _("General"); ?>
     </td>
    </tr>
    <tr>
     <td><?php print _("Name:"); ?></td>
     <td><input type="text" name="target_name" size="30" value="<?php print $this->parent->getClassVar($current, 'target_name'); ?>" /></td>
     <td><?php print _("Name of the target."); ?></td>
    </tr>
    <tr>
     <td colspan="3">
      <img src="<?php print ICON_TARGETS; ?>" alt="target icon" />&nbsp;<? print _("Parameters"); ?>
     </td>
    </tr>
    <tr>
     <td style="white-space: nowrap;"><?php print _("Match:"); ?></td>
     <td>
      <table class="noborder">
       <tr>
        <td style="white-space: nowrap;">
	 <input type="radio" name="target_match" value="IP" <?php if($this->parent->getClassVar($current, 'target_match') == "IP") print "checked=\"checked\""; ?> />IP
	</td>
	<td>&nbsp;</td>
	<td>
	 <input type="text" name="target_ip" size="30" value="<?php print $this->parent->getClassVar($current, 'target_ip'); ?>" />
	</td>
       </tr>
       <tr>
        <td style="white-space: nowrap;">
	 <input type="radio" name="target_match" value="MAC" <?php if($this->parent->getClassVar($current, 'target_match') == "MAC") print  "checked=\"checked\""; ?> />MAC
	</td>
	<td>&nbsp;</td>
	<td>
	 <input type="text" name="target_mac" size="30" value="<?php print $this->parent->getClassVar($current, 'target_mac'); ?>" />
	</td>
       </tr>
       <tr>
        <td style="white-space: nowrap;">
	 <input type="radio" name="target_match" value="GROUP" <?php if($this->parent->getClassVar($current, 'target_match') == "GROUP") print "checked=\"checked\""; ?> /><? print _("Group"); ?>
	</td>
	<td>&nbsp;</td>
	<td>
	 <table>
	  <tr>
	   <td>
	    <select name="avail[]" size="5" multiple="multiple">
	     <option value="">********* <?php print _("Unused"); ?> *********</option>
<?php
	       $result = $this->db->db_query("SELECT target_idx, target_name FROM ". MYSQL_PREFIX ."targets "
		                               ."WHERE target_match<>'GROUP' AND target_idx<>'". $_GET['idx'] ."' ORDER BY target_name ASC");
	       while($row = $result->fetchRow()) {
		  
		  if(!$this->db->db_fetchSingleRow("SELECT atg_idx FROM ". MYSQL_PREFIX ."assign_target_groups WHERE "
						  ."atg_group_idx='". $_GET['idx'] ."' AND "
						  ."atg_target_idx='". $row->target_idx ."'")) {
?>
             <option value="<?php print $row->target_idx; ?>"><? print $row->target_name; ?></option>
<?php
		  }
	       }
?>	
            </select>
	   </td>
	   <td>&nbsp;</td>
	   <td>
            <input type="button" value="&gt;&gt;" onclick="moveOptions(document.forms['targets'].elements['avail[]'], document.forms['targets'].elements['used[]']);" /><br />
            <input type="button" value="&lt;&lt;" onclick="moveOptions(document.forms['targets'].elements['used[]'], document.forms['targets'].elements['avail[]']);" />
           </td>
	   <td>&nbsp;</td>
	   <td>
	    <select name="used[]" size="5" multiple="multiple">
	     <option value="">********* <?php print _("Used"); ?> *********</option>
<?php
	       $result = $this->db->db_query("SELECT target_idx, target_name FROM ". MYSQL_PREFIX ."targets "
					    ."WHERE target_match<>'GROUP' AND target_idx<>'". $_GET['idx'] ."' ORDER BY target_name ASC");
	       while($row = $result->fetchRow()) {

		  if($this->db->db_fetchSingleRow("SELECT atg_idx FROM ". MYSQL_PREFIX ."assign_target_groups WHERE "
						 ."atg_group_idx='". $_GET['idx'] ."' AND "
						 ."atg_target_idx='". $row->target_idx ."'")) {
?>
             <option value="<?php print $row->target_idx; ?>"><? print $row->target_name; ?></option>
<?php
		  }
	       }
?>
	    </select>
	   </td>
	  </tr>
	 </table>
	</td>
       </tr>
      </table>
     </td>
     <td>
      <?php print _("Specify the target matchting method.<br /><br />IP: Enter a host (1.1.1.1), host list (1.1.1.1-1.1.1.254) or a network address (1.1.1.0/24).<br /><br />MAC: Specify the MAC address in format 00:00:00:00:00:00 or 00-00-00-00-00-00.<br /><br />Group: Group already defined targets as groups together. Group in group is not supported.<br /><br /><b>Be aware, that MAC match can NOT be used in combination with tc-filter.</b>"); ?>
     </td>
    </tr>
    <tr>
     <td colspan="3">&nbsp;</td>
    </tr>
    <tr>
     <td style="text-align: center;"><a href="<?php print $this->parent->self ."?mode=". $this->parent->mode; ?>" title="Back"><img src="<? print ICON_ARROW_LEFT; ?>" alt="arrow left icon" /></a></td>
     <td><input type="submit" value="<?php print _("Save"); ?>" onclick="selectAll(document.forms['targets'].elements['used[]']);" /></td>
     <td><?php _("Save settings."); ?></td>
    </tr>
   </table> 
  </form>
<?php

               $this->parent->closeTable();

	    }
	    else {

	       $error = 0;

	       if(!isset($_POST['target_name']) || $_POST['target_name'] == "") {

		  $this->parent->printError("<img src=\"". ICON_TARGETS ."\" alt=\"target icon\" />&nbsp;". _("Manage Target"), _("Please enter a name for this target!"));
		  $error = 1;

	       }

	       if(!$error && isset($_GET['new']) && $this->db->db_fetchSingleRow("SELECT target_idx FROM ". MYSQL_PREFIX ."targets WHERE target_name LIKE BINARY '". $_POST['target_name'] ."'")) {
		  
		  $this->parent->printError("<img src=\"". ICON_TARGETS ."\" alt=\"target icon\" />&nbsp;". _("Manage Target"), _("A target with that name already exists!"));
		  $error = 1;

	       }
	       
	       if(!$error && !isset($_GET['new']) && $_GET['namebefore'] != $_POST['target_name'] && $this->db->db_fetchSingleRow("SELECT target_idx FROM ". MYSQL_PREFIX ."targets WHERE target_name LIKE BINARY '". $_POST['target_name'] ."'")) {
		  
		  $this->parent->printError("<img src=\"". ICON_TARGETS ."\" alt=\"target icon\" />&nbsp;". _("Manage Target"), _("A target with that name already exists!"));
		  $error = 1;

	       }

	       if(!$error && $_POST['target_match'] == "IP" && $_POST['target_ip'] == "") {

		  $this->parent->printError("<img src=\"". ICON_TARGETS ."\" alt=\"target icon\" />&nbsp;". _("Manage Target"), _("You have selected IP match but didn't entered a IP address!"));
		  $error = 1;

	       }
	       elseif(!$error && $_POST['target_match'] == "IP" && $_POST['target_ip'] != "") {
	       
		  /* Is target_ip a ip range seperated by "-" */
		  if(strstr($_POST['target_ip'], "-") !== false) {

		     $hosts = split("-", $_POST['target_ip']);

		     foreach($hosts as $host) {

			$ipv4 = new Net_IPv4;

			if(!$error && !$ipv4->validateIP($host)) {

			   $this->parent->printError("<img src=\"". ICON_TARGETS ."\" alt=\"target icon\" />&nbsp;". _("Manage Target"), _("Incorrect IP address in IP range definition! Please enter a valid IP address!"));
			   $error = 1;
			}

		     }
		  }

		  /* Is target_ip a network */
		  elseif(strstr($_POST['target_ip'], "/") !== false) {

		     $ipv4 = new Net_IPv4;
		     $net = $ipv4->parseAddress($_POST['target_ip']);

		     if($net->netmask == "" || $net->netmask == "0.0.0.0") {

			$this->parent->printError("<img src=\"". ICON_TARGETS ."\" alt=\"target icon\" />&nbsp;". _("Manage Target"), _("Incorrect CIDR address! Please enter a valid network address!"));
			$error = 1;
		     }
		  }

		  /* target_ip is a simple IP */
		  else {

		     $ipv4 = new Net_IPv4;

		     if(!$ipv4->validateIP($_POST['target_ip'])) {

			$this->parent->printError("<img src=\"". ICON_TARGETS ."\" alt=\"target icon\" />&nbsp;". _("Manage Target"), _("Incorrect IP address! Please enter a valid IP address!"));
			$error = 1;
		     }
		  }
	       }

	       /* MAC address specified? */
	       if(!$error && $_POST['target_match'] == "MAC" && $_POST['target_mac'] == "") {

		  $this->parent->printError("<img src=\"". ICON_TARGETS ."\" alt=\"target icon\" />&nbsp;". _("Manage Target"), _("You have selected MAC match but didn't entered a MAC address!"));
		  $error = 1;

	       }
	       elseif(!$error && $_POST['target_match'] == "MAC" && $_POST['target_mac'] != "") {

		  if(!preg_match("/(.*):(.*):(.*):(.*):(.*):(.*)/", $_POST['target_mac']) && !preg_match("/(.*)-(.*)-(.*)-(.*)-(.*)-(.*)/", $_POST['target_mac'])) {
		     $this->parent->printError("<img src=\"". ICON_TARGETS ."\" alt=\"target icon\" />&nbsp;". _("Manage Target"), _("You have selected MAC match but specified a INVALID MAC address! Please specify a correct MAC address!"));
		     $error = 1;
		  }
	       }

	       if(!$error && $_POST['target_match'] == "GROUP" && count($_POST['used']) <= 1) {

		  $this->parent->printError("<img src=\"". ICON_TARGETS ."\" alt=\"target icon\" />&nbsp;". _("Manage Target"), _("You have selected Group match but didn't selected at least one target from the list!"));
		  $error = 1;

	       }

	       if(!$error) {

	          if(isset($_GET['new'])) {

		     $this->db->db_query("INSERT INTO ". MYSQL_PREFIX ."targets (target_name, target_match, target_ip, target_mac) "
			."VALUES ("
			."'". $_POST['target_name'] ."', "
			."'". $_POST['target_match'] ."', "
			."'". $_POST['target_ip'] ."', "
			."'". $_POST['target_mac'] ."')");

		     $_GET['idx'] = $this->db->db_getid();

		  }
		  else {

		     $this->db->db_query("UPDATE ". MYSQL_PREFIX ."targets SET "
		        ."target_name='". $_POST['target_name'] ."', "
			."target_match='". $_POST['target_match'] ."', "
			."target_ip='". $_POST['target_ip'] ."', "
			."target_mac='". $_POST['target_mac'] ."' "
			."WHERE target_idx='". $_GET['idx'] ."'");

                  }

		  if($_POST['used']) {

		     $this->db->db_query("DELETE FROM ". MYSQL_PREFIX ."assign_target_groups WHERE atg_group_idx='". $_GET['idx'] ."'");

		     foreach($_POST['used'] as $use) {

			if($use != "") {

			   $this->db->db_query("INSERT INTO ". MYSQL_PREFIX ."assign_target_groups (atg_group_idx, atg_target_idx) "
			      ."VALUES ('". $_GET['idx'] ."', '". $use ."')");

			}
		     }
		  }

		  $this->parent->goBack();

	       }
	    }
	    break;

	 case DELETE:

	    if(!isset($_GET['doit']))
	       $this->parent->printYesNo("<img src=\"". ICON_TARGETS ."\" alt=\"target icon\" />&nbsp;". _("Delete Target"), _("Delete target") ." ". $_GET['name'] ."?");
	    else {

	       if(isset($_GET['idx'])) {

		  $this->db->db_query("DELETE FROM ". MYSQL_PREFIX ."targets WHERE target_idx='". $_GET['idx'] ."'");
		  $this->db->db_query("DELETE FROM ". MYSQL_PREFIX ."assign_target_groups WHERE atg_group_idx='". $_GET['idx'] ."'");
		  $this->db->db_query("DELETE FROM ". MYSQL_PREFIX ."assign_target_groups WHERE atg_target_idx='". $_GET['idx'] ."'");
		  $this->parent->goBack();
	       }
	    }
	    break;
      }

   } // showHtml();

}

?>
