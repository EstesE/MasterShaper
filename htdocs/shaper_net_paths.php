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

class MSNETPATHS {

   var $db;
   var $parent;

   /* Class constructor */
   function MSNETPATHS($parent)
   {
      $this->parent = $parent;
      $this->db = $parent->db;
   } // MSNETPATHS()

  
   /* interface output */
   function showHtml()
   {

      /* If authentication is enabled, check permissions */
      if($this->parent->getOption("authentication") == "Y" &&
	 !$this->parent->checkPermissions("user_manage_options")) {

	 $this->parent->printError("<img src=\"". ICON_INTERFACES ."\" alt=\"interface icon\" />&nbsp;". _("Manage Network Paths"), _("You do not have enough permissions to access this module!"));
	 return 0;

      }

      if(!isset($this->parent->screen))
         $this->parent->screen = 0;

      switch($this->parent->screen) {

	 default:
	 case 0:
			   
            $this->parent->startTable("<img src=\"". ICON_INTERFACES ."\" alt=\"interface icon\" />&nbsp;". _("Manage Network Paths"));
?>
  <table style="width: 100%;" class="withborder"> 
   <tr>
    <td style="text-align: center;" colspan="4">
     <img src="<?php print ICON_NEW; ?>" alt="new icon" />
     <a href="<?php print $this->parent->self ."?mode=". $this->parent->mode; ?>&amp;screen=1&amp;new=1"><? print _("Define a new Network Path"); ?></a>
    </td>
   </tr>
   <tr>
    <td colspan="4">&nbsp;</td>
   </tr>
   <tr>
    <td><img src="<?php print ICON_INTERFACES; ?>" alt="interface icon" />&nbsp;<i><? print _("Path"); ?></i></td>
    <td><img src="<?php print ICON_INTERFACES; ?>" alt="interface icon" />&nbsp;<i><? print _("Interface 1"); ?></i></td>
    <td><img src="<?php print ICON_INTERFACES; ?>" alt="interface icon" />&nbsp;<i><? print _("Interface 2"); ?></i></td>
    <td style="text-align: center;"><i><?php print _("Options"); ?></i></td>
   </tr>
<?php

	    $result = $this->db->db_query("SELECT * FROM ". MYSQL_PREFIX ."network_paths ORDER BY netpath_name ASC");
	
	    while($row = $result->fetchrow()) {

?>
   <tr onmouseover="setBackGrdColor(this, 'mouseover');" onmouseout="setBackGrdColor(this, 'mouseout');">
    <td>
     <img src="<?php print ICON_INTERFACES; ?>" alt="interface icon" />
     <a href="<?php print $this->parent->self ."?mode=". $this->parent->mode ."&amp;screen=1&amp;idx=". $row->netpath_idx; ?>"><? print $row->netpath_name; ?></a>
    </td>
    <td>
     <?php print $this->parent->getInterfaceName($row->netpath_if1); ?>
    </td>
    <td>
     <?php print $this->parent->getInterfaceName($row->netpath_if2); ?>
    </td>
    <td style="text-align: center;">
     <a href="<?php print $this->parent->self ."?mode=". $this->parent->mode ."&amp;screen=2&amp;idx=". $row->netpath_idx ."&amp;name=". urlencode($row->netpath_name); ?>">
      <img src="<?php print ICON_DELETE; ?>" alt="delete icon" />
     </a>
<?php
               if($row->netpath_active == 'Y') {
?>
     <a href="<?php print $this->parent->self ."?mode=". $this->parent->mode ."&amp;screen=3&amp;idx=". $row->netpath_idx ."&amp;to=0"; ?>">
      <img src="<?php print ICON_ACTIVE; ?>" alt="active icon" />
     </a>
<?php
               }
	       else {
?>
     <a href="<?php print $this->parent->self ."?mode=". $this->parent->mode ."&amp;screen=3&amp;idx=". $row->netpath_idx ."&amp;to=1"; ?>">
      <img src="<?php print ICON_INACTIVE; ?>" alt="inactive icon" />
     </a>
<?php
               }
?>
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

		  $this->parent->startTable("<img src=\"". ICON_INTERFACES ."\" alt=\"interface icon\" />&nbsp;". _("Define a new Network Path"));
		  $form_url = $this->parent->self ."?mode=". $this->parent->mode ."&amp;screen=". $this->parent->screen ."&amp;saveit=1&amp;new=1";
		  $current->netpath_active = "Y";
		  $current->netpath_imq = "Y";

               }
	       else {

		  $current = $this->db->db_fetchSingleRow("SELECT * FROM ". MYSQL_PREFIX ."network_paths WHERE netpath_idx='". $_GET['idx'] ."'");
		  $this->parent->startTable("<img src=\"". ICON_INTERFACES ."\" alt=\"interface icon\" />&nbsp;". _("Modify Network Path") ." ". $this->parent->getClassVar($current, 'netpath_name'));
		  $form_url = $this->parent->self ."?mode=". $this->parent->mode ."&amp;screen=". $this->parent->screen ."&amp;saveit=1&amp;idx=". $_GET['idx'] ."&amp;namebefore=". urlencode($this->parent->getClassVar($current, 'netpath_name'));

               }


?>
  <form action="<?php print $form_url; ?>" method="post">
   <table style="width: 100%;" class="withborder2">
    <tr>
     <td colspan="3">
      <img src="<?php print ICON_INTERFACES; ?>" alt="interface icon" />
      <?php print _("General"); ?>
     </td>
    </tr>
    <tr>
     <td>
      <?php print _("Name:"); ?>
     </td>
     <td>
      <input type="text" name="netpath_name" size="30" value="<?php print $this->parent->getClassVar($current, 'netpath_name'); ?>" />
     </td>
     <td>
      <?php print _("Specify a Network Path alias name (INET-LAN, INET-DMZ, ...)."); ?>
     </td>
    </tr>
    <tr>
     <td>
      <?php print _("Status:"); ?>
     </td>
     <td>
      <input type="radio" name="netpath_active" value="Y" <?php if($this->parent->getClassVar($current, 'netpath_active') == "Y") print "checked=\"checked\""; ?> /><? print _("Enabled"); ?>
      <input type="radio" name="netpath_active" value="N" <?php if($this->parent->getClassVar($current, 'netpath_active') != "Y") print "checked=\"checked\""; ?> /><? print _("Disabled"); ?>
     </td>
     <td>
      <?php print _("Enable or disable shaping on that Network path (on next ruleset reload)."); ?>
     </td>
    </tr>
    <tr>
     <td colspan="3">
      <img src="<?php print ICON_INTERFACES; ?>" alt="interface icon" />
      <?php print _("Interfaces:"); ?>
     </td>
    </tr>
    <tr>
     <td>
      <?php print _("Interface 1:"); ?>
     </td>
     <td>
      <select name="netpath_if1">
<?php

               $result = $this->db->db_query("SELECT * FROM ". MYSQL_PREFIX ."interfaces WHERE if_active='Y' ORDER BY if_name ASC");
	       while($row = $result->fetchRow()) {
?>
       <option value="<?php print $row->if_idx; ?>" <? if($this->parent->getClassVar($current, 'netpath_if1') == $row->if_idx) print "selected=\"selected\""; ?>><? print $row->if_name; ?></option>
<?php
	       }
?>
      </select>
     </td>
     <td>
      <?php print _("First interface of this network path."); ?>
     </td>
    </tr>
    <tr>
     <td>
      <?php print _("Interface 2:"); ?>
     </td>
     <td>
      <select name="netpath_if2">
<?php

               $result = $this->db->db_query("SELECT * FROM ". MYSQL_PREFIX ."interfaces WHERE if_active='Y' ORDER BY if_name ASC");
	       while($row = $result->fetchRow()) {
?>
       <option value="<?php print $row->if_idx; ?>" <? if($this->parent->getClassVar($current, 'netpath_if2') == $row->if_idx) print "selected=\"selected\""; ?>><? print $row->if_name; ?></option>
<?php
	       }
?>
       <option value="-1" <?php if($this->parent->getClassVar($current, 'netpath_if2') == -1) print "selected=\"selected\""; ?>>--- <? print _("not used"); ?>---</option>
      </select>
     </td>
     <td>
      <?php print _("Second interface of this network path."); ?>
     </td>
    </tr>
    <tr>
     <td colspan="3">
      <img src="<?php print ICON_INTERFACES; ?>" />&nbsp;<? print _("Options"); ?>
     </td>
    </tr>
    <tr>
     <td style="white-space: nowrap;">IMQ:</td>
     <td>
      <input type="radio" name="netpath_imq" value="Y" <?php if($this->parent->getClassVar($current, 'netpath_imq') == "Y") print "checked=\"checked\""; ?> /><? print _("Yes"); ?>
      <input type="radio" name="netpath_imq" value="N" <?php if($this->parent->getClassVar($current, 'netpath_imq') != "Y") print "checked=\"checked\""; ?> /><? print _("No"); ?>
     </td>
     <td>
      <?php print _("Do you use IMQ (Intermediate Queuing Device) devices within this network path?"); ?>
     </td>
    </tr>
    <tr>
     <td colspan="3">
      &nbsp;
     </td>
    </tr>
    <tr>
     <td style="text-align: center;"><a href="<?php print $this->parent->self ."?mode=". $this->parent->mode; ?>" title="Back"><img src="<? print ICON_ARROW_LEFT; ?>" alt="arrow left icon" /></a></td>
     <td><input type="submit" value="<?php print _("Save"); ?>" /></td>
     <td><?php print _("Save your settings."); ?></td>
    </tr>
   </table>
  </form>
<?php
               $this->parent->closeTable();

	    }
	    else {

	       $error = false;

	       if(!isset($_POST['netpath_name']) || $_POST['netpath_name'] == "") {

		  $this->parent->printError("<img src=\"". ICON_INTERFACES ."\" alt=\"interface icon\" />&nbsp;". _("Network Path"), _("Please specify a network path name!"));
		  $error = true;

               }

	       if(!$error && isset($_GET['new']) && $this->db->db_fetchSingleRow("SELECT netpath_idx FROM ". MYSQL_PREFIX ."network_paths WHERE netpath_name LIKE BINARY '". $_POST['netpath_name'] ."'")) {

                  $this->parent->printError("<img src=\"". ICON_INTERFACES ."\" alt=\"interface icon\" />&nbsp;". _("Network Path"), _("A network path with that name already exists!"));
		  $error = true;

	       }

	       if(!$error && !isset($_GET['new']) && $_POST['netpath_name'] != $_GET['namebefore'] && $this->db->db_fetchSingleRow("SELECT netpath_idx FROM ". MYSQL_PREFIX ."_network_paths WHERE netpath_name LIKE BINARY '". $_POST['netpath_name'] ."'")) {

		  $this->parent->printError("<img src=\"". ICON_INTERFACES ."\" alt=\"interface icon\" />&nbsp;". _("Network Path"), _("A network path with that name already exists!"));
		  $error = true;

               }

	       if(!$error && $_POST['netpath_if1'] == $_POST['netpath_if2']) {

	          $this->parent->printError("<img src=\"". ICON_INTERFACES ."\" alt=\"interface icon\" />&nbsp;". _("Network Path"), _("A interface within a network path can not be used twice! Please select different interfaces"));
		  $error = true;

               }

	       if(!$error) {

                  if(isset($_GET['new'])) {

		     $max_pos = $this->db->db_fetchSingleRow("SELECT MAX(netpath_position) as pos FROM ". MYSQL_PREFIX ."network_paths");

		     $this->db->db_query("INSERT INTO ". MYSQL_PREFIX ."network_paths (netpath_name, netpath_if1, netpath_if2, "
			."netpath_position, netpath_imq, netpath_active) VALUES ("
			."'". $_POST['netpath_name'] ."', "
			."'". $_POST['netpath_if1'] ."', "
			."'". $_POST['netpath_if2'] ."', "
			."'". ($max_pos->pos+1) ."', "
			."'". $_POST['netpath_imq'] ."', "
			."'". $_POST['netpath_active'] ."')");

                  }
		  else {

		     $this->db->db_query("UPDATE ". MYSQL_PREFIX ."network_paths SET "
			."netpath_name='". $_POST['netpath_name'] ."', "
			."netpath_if1='". $_POST['netpath_if1'] ."', "
			."netpath_if2='". $_POST['netpath_if2'] ."', "
			."netpath_imq='". $_POST['netpath_imq'] ."', "
			."netpath_active='". $_POST['netpath_active'] ."'"
			."WHERE netpath_idx='". $_GET['idx'] ."'");

		  }
		  
		  $this->parent->goBack();
		     
	       }

	    }
	    break;

	 case DELETE:
	    
	    if(!isset($_GET['doit']))
	       $this->parent->printYesNo("<img src=\"". ICON_INTERFACES ."\" alt=\"interface icon\" />&nbsp;". _("Delete Network Path"), _("Delete Network Path") ." ". $_GET['name'] ."?");
	    else {
	       
	       if($_GET['idx'])
		  $this->db->db_query("DELETE FROM ". MYSQL_PREFIX ."network_paths WHERE netpath_idx='". $_GET['idx'] ."'");
	       $this->parent->goBack();
	    }
	    break;

	 case CHGSTATUS:

	    if(isset($_GET['idx'])) {

	       if($_GET['to'] == 1) $this->db->db_query("UPDATE ". MYSQL_PREFIX ."network_paths SET netpath_active='Y' WHERE netpath_idx='". $_GET['idx'] ."'");
	       elseif($_GET['to'] == 0) $this->db->db_query("UPDATE ". MYSQL_PREFIX ."network_paths SET netpath_active='N' WHERE netpath_idx='". $_GET['idx'] ."'");

	    }

	    $this->parent->goBack();
	    break;

      }

   } // showHtml()

}

?>
