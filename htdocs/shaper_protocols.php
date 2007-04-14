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

class MSPROTOCOLS {

   var $db;
   var $parent;

   /* Class constructor */
   function MSPROTOCOLS($parent)
   {
      $this->parent = $parent;
      $this->db = $parent->db;
   } // MSPROTOCOLS()

  
   /* interface output */
   function showHtml()
   {

      /* If authentication is enabled, check permissions */
      if($this->parent->getOption("authentication") == "Y" &&
	 !$this->parent->checkPermissions("user_manage_protocols")) {

	 $this->parent->printError("<img src=\"". ICON_PROTOCOLS ."\" alt=\"protocol icon\" />&nbsp;". _("Manage Protocols"), _("You do not have enough permissions to access this module!"));
	 return 0;

      }

      if(!isset($this->parent->screen))
         $this->parent->screen = 0;

      switch($this->parent->screen) {

	 default:
			   
            $this->parent->startTable("<img src=\"". ICON_PROTOCOLS ."\" alt=\"protocol icon\" />&nbsp;". _("Manage Protocols"));
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
    <td style="text-align: center;" colspan="3">
     <img src="<?php print ICON_NEW; ?>" alt="new icon" />
     <a href="<?php print $this->parent->self ."?mode=". $this->parent->mode ."&amp;screen=". MANAGE ."&amp;new=1"; ?>"><? print _("Create a new Protocol"); ?></a>
    </td>
   </tr>
   <tr>
    <td colspan="3">&nbsp;</td>
   </tr>
   <tr>
    <td><img src="<?php print ICON_PROTOCOLS; ?>" alt="protocol icon" />&nbsp;<i><? print _("Name"); ?></i></td>
    <td style="text-align: center;"><i><?php print _("Number"); ?></i></td>
    <td style="text-align: center;"><i><?php print _("Options"); ?></i></td>
   </tr>
<?php

	    $result = $this->db->db_query("SELECT * FROM ". MYSQL_PREFIX ."protocols ORDER BY proto_name ASC");
	
	    while($row = $result->fetchrow()) {

?>
   <tr onmouseover="setBackGrdColor(this, 'mouseover');" onmouseout="setBackGrdColor(this, 'mouseout');">
    <td>
     <img src="<?php print ICON_PROTOCOLS; ?>" alt="protocol icon" />
     <a href="<?php print $this->parent->self ."?mode=". $this->parent->mode ."&amp;screen=". MANAGE ."&amp;idx=". $row->proto_idx; ?>">
      <?php if($row->proto_user_defined == 'Y') print "<img src=\"". ICON_USERS ."\" alt=\"User defined protocol\" />"; ?>
      <?php print $row->proto_name; ?>
     </a>
    </td>
    <td style="text-align: center;"><?php print $row->proto_number; ?></td>
    <td style="text-align: center;">
     <a href="<?php print $this->parent->self ."?mode=". $this->parent->mode ."&amp;screen=". DELETE ."&amp;idx=". $row->proto_idx ."&amp;name=". urlencode($row->proto_name); ?>">
      <img src="<?php print ICON_DELETE; ?>" alt="delete icon" />
     </a>
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

		  $this->parent->startTable("<img src=\"". ICON_PROTOCOLS ."\" alt=\"protocol icon\" />&nbsp;". _("Create a new Protocol"));
		  $form_url = $this->parent->self ."?mode=". $this->parent->mode ."&amp;screen=". $this->parent->screen ."&amp;saveit=1&amp;new=1";
		  $current->proto_name = "";

               }
	       else {

		  $current = $this->db->db_fetchSingleRow("SELECT * FROM ". MYSQL_PREFIX ."protocols WHERE proto_idx='". $_GET['idx'] ."'");
		  $this->parent->startTable("<img src=\"". ICON_PROTOCOLS ."\" alt=\"protocol icon\" />&nbsp;". _("Manage Protocol "). $this->parent->getClassVar($current, 'proto_name'));
		  $form_url = $this->parent->self ."?mode=". $this->parent->mode ."&amp;screen=". $this->parent->screen ."&amp;saveit=1&amp;namebefore=". urlencode($this->parent->getClassVar($current, 'proto_name')) ."&amp;idx=". $_GET['idx'];

               }


?>
  <form action="<?php print $form_url; ?>" method="post">
   <table style="width: 100%;" class="withborder2">
    <tr>
     <td colspan="3">
      <img src="<?php print ICON_PROTOCOLS; ?>" alt="protocol icon" />
      <?php print _("General"); ?>
     </td>
    </tr>
    <tr>
     <td><?php print _("Name:"); ?></td>
     <td><input type="text" name="proto_name" size="30" value="<?php print $this->parent->getClassVar($current, 'proto_name'); ?>" /></td>
     <td><?php print _("The protocol name."); ?></td>
    </tr>
    <tr>
     <td colspan="3">
      <img src="<?php print ICON_PROTOCOLS; ?>" alt="protocol icon" />
      <?php print _("Details"); ?>
     </td>
    </tr>
    <tr>
     <td><?php print _("Number:"); ?></td>
     <td>
      <input type="text" name="proto_number" size="30" value="<?php print $this->parent->getClassVar($current, 'proto_number'); ?>" />
     </td>
     <td><?php print _("The IANA portocol number."); ?></td>
    </tr>
    <tr>
     <td colspan="3">
      &nbsp;
     </td>
    </tr>
    <tr>
     <td style="text-align: center;"><a href="<?php print $this->parent->self ."?mode=". $this->parent->mode; ?>" title="Back"><img src="<? print ICON_ARROW_LEFT; ?>" alt="protocol icon" /></a></td>
     <td><input type="submit" value="<?php print _("Save"); ?>" /></td>
     <td><?php print _("Save your settings."); ?></td>
    </tr>
   </table>
  </form>
<?php

               $this->parent->closeTable();
	    }
	    else {
	     
	       $error = 0;

	       if(!isset($_POST['proto_name']) || $_POST['proto_name'] == "") {

		  $this->parent->printError("<img src=\"". ICON_PROTOCOLS ."\" alt=\"protocol icon\" />&nbsp;". _("Manage Protocol"), _("Please enter a protocol name!"));
		  $error = 1;
               }

	       if(isset($_GET['new']) && $this->db->db_fetchSingleRow("SELECT proto_idx FROM ". MYSQL_PREFIX ."protocols WHERE proto_name LIKE BINARY '". $_POST['proto_name'] ."'")) {

	          $this->parent->printError("<img src=\"". ICON_PROTOCOLS ."\" alt=\"protocol icon\" />&nbsp;". _("Manage Protocol"), _("A protocol with that name already exists!"));
		  $error = 1;

               }

	       if(!isset($_GET['new']) && $_GET['namebefore'] != $_POST['proto_name'] && $this->db->db_fetchSingleRow("SELECT proto_idx FROM ". MYSQL_PREFIX ."protocols WHERE proto_name LIKE BINARY '". $_POST['proto_name'] ."'")) {

	          $this->parent->printError("<img src=\"". ICON_PROTOCOLS ."\" alt=\"protocol icon\" />&nbsp;". _("Manage Protocol"), _("A protocol with that name already exists!"));
		  $error = 1;

               }

	       if(!isset($_POST['proto_number']) || $_POST['proto_number'] == "" || !is_numeric($_POST['proto_number'])) {

		  $this->parent->printError("<img src=\"". ICON_PROTOCOLS ."\" alt=\"protocol icon\" />&nbsp;". _("Manage Protocol"), _("Please enter a numerical protocol number!"));
		  $error = 1;
               }

	       if(!$error) {

	          if(isset($_GET['new'])) {

		     $this->db->db_query("INSERT INTO ". MYSQL_PREFIX ."protocols (proto_name, proto_number, "
			."proto_user_defined) VALUES ("
			."'". $_POST['proto_name'] ."', "
			."'". $_POST['proto_number'] ."', "
			."'Y')");
 
                  }
		  else {

		     $this->db->db_query("UPDATE ". MYSQL_PREFIX ."protocols SET "
		        ."proto_name='". $_POST['proto_name'] ."', "
                        ."proto_number='". $_POST['proto_number'] ."', "
			."proto_user_defined='Y' "
			."WHERE proto_idx='". $_GET['idx'] ."'");

		  }

		  $this->parent->goBack();
	       }
	    }
	    break;

	 case DELETE:
	    
	    if(!isset($_GET['doit'])) {

	       $this->parent->printYesNo("<img src=\"". ICON_PROTOCOLS ."\" alt=\"protocol icon\" />&nbsp;". _("Delete Protocol"), _("Delete Protocol") ." ". $_GET['name']);

            }
	    else {
	       
	       if($_GET['idx'])
		  $this->db->db_query("DELETE FROM ". MYSQL_PREFIX ."protocols WHERE proto_idx='". $_GET['idx'] ."'");

	       $this->parent->goBack();

	    }
	    break;
      }

   } // showHtml()

}

?>
