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

class MSINTERFACES {

   var $db;
   var $parent;

   /* Class constructor */
   function MSINTERFACES($parent)
   {
      $this->parent = $parent;
      $this->db = $parent->db;
   } // MSINTERFACES()

  
   /* interface output */
   function showHtml()
   {

      /* If authentication is enabled, check permissions */
      if($this->parent->getOption("authentication") == "Y" &&
	 !$this->parent->checkPermissions("user_manage_options")) {

	 $this->parent->printError("<img src=\"". ICON_INTERFACES ."\" alt=\"interface icon\" />&nbsp;". _("Manage Interfaces"), _("You do not have enough permissions to access this module!"));
	 return 0;

      }

      if(!isset($this->parent->screen))
         $this->parent->screen = 0;

      switch($this->parent->screen) {

	 default:
	 case 0:
			   
            $this->parent->startTable("<img src=\"". ICON_INTERFACES ."\" alt=\"interface icon\" />&nbsp;". _("Manage Interfaces"));
?>
  <table style="width: 100%;" class="withborder"> 
   <tr>
    <td style="text-align: center;" colspan="4">
     <img src="<?php print ICON_NEW; ?>" alt="new icon" />
     <a href="<?php print $this->parent->self ."?mode=". $this->parent->mode; ?>&amp;screen=1&amp;new=1"><? print _("Add a new Interface"); ?></a>
    </td>
   </tr>
   <tr>
    <td colspan="4">&nbsp;</td>
   </tr>
   <tr>
    <td><img src="<?php print ICON_INTERFACES; ?>" alt="interface icon" />&nbsp;<i><? print _("Interface"); ?></i></td>
    <td><img src="<?php print ICON_INTERFACES; ?>" alt="interface icon" />&nbsp;<i><? print _("Bandwidth"); ?></i></td>
    <td style="text-align: center;"><i><?php print _("Options"); ?></i></td>
   </tr>
<?php

	    $result = $this->db->db_query("SELECT * FROM ". MYSQL_PREFIX ."interfaces ORDER BY if_name ASC");
	
	    while($row = $result->fetchrow()) {

?>
   <tr onmouseover="setBackGrdColor(this, 'mouseover');" onmouseout="setBackGrdColor(this, 'mouseout');">
    <td>
     <img src="<?php print ICON_INTERFACES; ?>" alt="interface icon" />
     <a href="<?php print $this->parent->self ."?mode=". $this->parent->mode ."&amp;screen=1&amp;idx=". $row->if_idx; ?>" onmouseover="staticTip.show('tipUser<? print $row->if_idx; ?>');" onmouseout="staticTip.hide();">
      <?php print $row->if_name; ?>
     </a>
    </td>
    <td>
     <?php print $row->if_speed; ?>
    </td>
    <td style="text-align: center;">
     <a href="<?php print $this->parent->self ."?mode=". $this->parent->mode ."&amp;screen=2&amp;idx=". $row->if_idx ."&amp;name=". urlencode($row->if_name); ?>">
      <img src="<?php print ICON_DELETE; ?>" alt="delete icon" />
     </a>
<?php
               if($row->if_active == 'Y') {
?>
     <a href="<?php print $this->parent->self ."?mode=". $this->parent->mode ."&amp;screen=3&amp;idx=". $row->if_idx ."&amp;to=0"; ?>">
      <img src="<?php print ICON_ACTIVE; ?>" alt="active icon" />
     </a>
<?php
               }
	       else {
?>
     <a href="<?php print $this->parent->self ."?mode=". $this->parent->mode ."&amp;screen=3&amp;idx=". $row->if_idx ."&amp;to=1"; ?>">
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

	 case 1:
	    
	    if(!isset($_GET['saveit'])) {

               if(isset($_GET['new'])) {

		  $this->parent->startTable("<img src=\"". ICON_INTERFACES ."\" alt=\"interface icon\" />&nbsp;". _("Add a new Interface"));
		  $form_url = $this->parent->self ."?mode=". $this->parent->mode ."&amp;screen=". $this->parent->screen ."&amp;saveit=1&amp;new=1";
		  $current->if_ifb = "N";
		  $current->if_active = "Y";

               }
	       else {

		  $current = $this->db->db_fetchSingleRow("SELECT * FROM ". MYSQL_PREFIX ."interfaces WHERE if_idx='". $_GET['idx'] ."'");
		  $this->parent->startTable("<img src=\"". ICON_INTERFACES ."\" alt=\"interface icon\" />&nbsp;". _("Modify Interface") ." ". $this->parent->getClassVar($current, 'if_name'));
		  $form_url = $this->parent->self ."?mode=". $this->parent->mode ."&amp;screen=". $this->parent->screen ."&amp;saveit=1&amp;idx=". $_GET['idx'];

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
      <input type="text" name="if_name" size="30" value="<?php print $this->parent->getClassVar($current, 'if_name'); ?>" />
     </td>
     <td>
      <?php print _("Specify the interface name (eth0, ppp0, imq0, ...)."); ?>
     </td>
    </tr>
    <tr>
     <td>
      <?php print _("Status:"); ?>
     </td>
     <td>
      <input type="radio" name="if_active" value="Y" <?php if($this->parent->getClassVar($current, 'if_active') == "Y") print "checked=\"checked\""; ?> /><? print _("Enabled"); ?>
      <input type="radio" name="if_active" value="N" <?php if($this->parent->getClassVar($current, 'if_active') != "Y") print "checked=\"checked\""; ?> /><? print _("Disabled"); ?>
     </td>
     <td>
      <?php print _("Enable or disable shaping on this interface (on next ruleset reload)."); ?>
     </td>
    </tr>
    <tr>
     <td colspan="3">
      <img src="<?php print ICON_INTERFACES; ?>" alt="interface icon" />
      <?php print _("Interface Details:"); ?>
     </td>
    </tr>
    <tr>
     <td>
      <?php print _("Bandwidth:"); ?>
     </td>
     <td>
      <input type="text" name="if_speed" size="30" value="<?php print $this->parent->getClassVar($current, 'if_speed'); ?>" />
     </td>
     <td>
      <?php print _("Specify the outbound bandwidth on this interface in bit/s (append K for kbit/s or M for Mbit/s)."); ?>
     </td>
    </tr>
    <tr>
     <td colspan="3">
      <img src="<?php print ICON_INTERFACES; ?>" alt="interface icon" />
      <?php print _("Options:"); ?>
     </td>
    </tr>
    <tr>
     <td>
      IFB:
     </td>
     <td>
      <input type="radio" name="if_ifb" value="Y" <?php if($current->if_ifb == "Y") print "checked=\"checked\""; ?> /><?php print _("Enabled"); ?>
      <input type="radio" name="if_ifb" value="N" <?php if($current->if_ifb != "Y") print "checked=\"checked\""; ?> /><?php print _("Disabled"); ?>
     </td>
     <td>
      <?php print _("This option enables IFB support on this interface. Make sure that IFB is compiled into your kernel or the proper kernel module is loaded!"); ?>
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

	       if(!isset($_POST['if_name']) || $_POST['if_name'] == "") {

		  $this->parent->printError("<img src=\"". ICON_INTERFACES ."\" alt=\"interface icon\" />&nbsp;". _("Manage Interfaces"), _("Please specify a interface!"));
		  $error = true;

               }

	       if(!$error && isset($_GET['new']) && $this->db->db_fetchSingleRow("SELECT if_idx FROM ". MYSQL_PREFIX ."interfaces WHERE if_name like '". $_POST['if_name'] ."'")) {

                  $this->parent->printError("<img src=\"". ICON_INTERFACES ."\" alt=\"interface icon\" />/&nbsp;". _("Manage Interfaces"), _("A interface with that name already exists!"));
		  $error = true;

	       }

	       if(!$_POST['if_speed'] || $_POST['if_speed'] == "")
	          $_POST['if_speed'] = 0;
	       else
	          $_POST['if_speed'] = strtoupper($_POST['if_speed']);

               if(!$error && !$this->parent->validateBandwidth($_POST['if_speed'])) {

	          $this->parent->printError("<img src=\"". ICON_INTERFACES ."\" alt=\"interface icon\" />&nbsp;". _("Interfaces"), _("Invalid bandwidth specified!"));
		  $error = true;

               }

	       if(!$error) {

                  if(isset($_GET['new'])) {

		     $this->db->db_query("INSERT INTO ". MYSQL_PREFIX ."interfaces (if_name, if_speed, if_ifb, if_active) "
		        ."VALUES ("
			."'". $_POST['if_name'] ."', "
			."'". $_POST['if_speed'] ."', "
			."'". $_POST['if_ifb'] ."', "
			."'". $_POST['if_active'] ."')");

                  }
		  else {

		     $this->db->db_query("UPDATE ". MYSQL_PREFIX ."interfaces SET "
			."if_name='". $_POST['if_name'] ."', "
			."if_speed='". $_POST['if_speed'] ."', "
			."if_ifb='". $_POST['if_ifb'] ."', "
			."if_active='". $_POST['if_active'] ."'"
			."WHERE if_idx='". $_GET['idx'] ."'");

		  }
		  
		  $this->parent->goBack();
		     
	       }

	    }
	    break;

	 case 2:
	    
	    if(!isset($_GET['doit']))
	       $this->parent->printYesNo("<img src=\"". ICON_INTERFACES ."\" alt=\"interface icon\" />&nbsp;". _("Delete Interface"), _("Delete Interface") ." ". $_GET['name'] ."?");
	    else {
	       
	       if($_GET['idx'])
		  $this->db->db_query("DELETE FROM ". MYSQL_PREFIX ."interfaces WHERE if_idx='". $_GET['idx'] ."'");
	       $this->parent->goBack();
	    }
	    break;

	 case 3:

	    if(isset($_GET['idx'])) {

	       if($_GET['to'] == 1) $this->db->db_query("UPDATE ". MYSQL_PREFIX ."interfaces SET if_active='Y' WHERE if_idx='". $_GET['idx'] ."'");
	       elseif($_GET['to'] == 0) $this->db->db_query("UPDATE ". MYSQL_PREFIX ."interfaces SET if_active='N' WHERE if_idx='". $_GET['idx'] ."'");

	    }

	    $this->parent->goBack();
	    break;

      }

   } // showHtml()

}

?>
