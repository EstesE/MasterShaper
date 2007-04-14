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

class MSPORTS {

   var $db;
   var $parent;

   /* Class constructor */
   function MSPORTS($parent)
   {
      $this->parent = $parent;
      $this->db = $parent->db;
   } // MSPORTS()

   /* interface output */
   function showHtml()
   {

      /* If authentication is enabled, check permissions */
      if($this->parent->getOption("authentication") == "Y" &&
	 !$this->parent->checkPermissions("user_manage_ports")) {

	 $this->parent->printError("<img src=\"". ICON_PORTS ."\" alt=\"port icon\" />&nbsp;". _("Manage Ports"), _("You do not have enough permissions to access this module!"));
	 return 0;

      }

      if(!isset($this->parent->screen))
         $this->parent->screen = 0;

      switch($this->parent->screen) {

         default:

	    if(!isset($_GET['orderby']))
	       $_GET['orderby'] = "port_name";
	    if(!isset($_GET['sortorder']))
	       $_GET['sortorder'] = "ASC";
	    if(!isset($_GET['breaker']))
	       $_GET['breaker'] = 'A';
	  
	    $this->parent->startTable("<img src=\"". ICON_PORTS ."\" alt=\"port icon\" />&nbsp;". _("Manage Ports"));
?>
  <table style="width: 100%;" class="withborder">
   <tr>
<?php
            if(isset($_GET['saved'])) {
?>
    <td colspan="5" style="text-align: center;" class="sysmessage"><?php print _("You have made changes to the ruleset. Don't forget to reload them."); ?></td>
<?php
            } else {
?>
    <td colspan="5">&nbsp;</td>
<?php
            }
?>
   </tr>
   <tr>
    <td style="text-align: center;" colspan="5">
     <img src="<?php print ICON_NEW; ?>" alt="new icon" />
     <a href="<?php print $this->parent->self ."?mode=". $this->parent->mode ."&amp;screen=". MANAGE ."&amp;new=1"; ?>"><? print _("Create a new Port"); ?></a>
    </td>
   </tr>
   <tr>
    <td colspan="5">&nbsp;</td>
   </tr>
   <tr>
    <td colspan="5" style="text-align: center;">
<?php
	    
	    /* Display alphabetical port select */
	    foreach(range('A', 'Z') as $letter)
	    {
	       if(isset($_GET['breaker']) && $letter == strtoupper($_GET['breaker'][0])) {
?>
     <a href="<?php print $this->parent->self ."?mode=". $this->parent->mode ."&amp;breaker=". $letter ."&amp;orderby=". $_GET['orderby'] ."&amp;sortorder=". $_GET['sortorder']; ?>" style="color: #AF0000;"><? print $letter ?></a>
<?php
               }
	       else {
?>
     <a href="<?php print $this->parent->self ."?mode=". $this->parent->mode ."&amp;breaker=". $letter ."&amp;orderby=". $_GET['orderby'] ."&amp;sortorder=". $_GET['sortorder']; ?>"><? print $letter ?></a>
<?php
               }
	    }

	    foreach(range('0', '9') as $letter)
	    {
	       if(isset($_GET['breaker']) && $letter == strtoupper($_GET['breaker'][0])) {
?>
     <a href="<?php print $this->parent->self ."?mode=". $this->parent->mode ."&amp;breaker=". $letter ."&amp;orderby=". $_GET['orderby'] ."&amp;sortorder=". $_GET['sortorder']; ?>" style="color: #AF0000;"><? print $letter ?></a>
<?php
               }
	       else {
?>
     <a href="<?php print $this->parent->self ."?mode=". $this->parent->mode ."&amp;breaker=". $letter ."&amp;orderby=". $_GET['orderby'] ."&amp;sortorder=". $_GET['sortorder']; ?>"><? print $letter ?></a>
<?php
               }
	    }

	    $letter = "#";

	    if(isset($_GET['breaker']) && $letter == urldecode(strtoupper($_GET['breaker'][0]))) {
?>
     <a href="<?php print $this->parent->self ."?mode=". $this->parent->mode ."&amp;breaker=". urlencode($letter) ."&amp;orderby=". $_GET['orderby'] ."&amp;sortorder=". $_GET['sortorder']; ?>" style="color: #AF0000;"><? print $letter ?></a>
<?php
	    }
	    else {
?>
     <a href="<?php print $this->parent->self ."?mode=". $this->parent->mode ."&amp;breaker=". urlencode($letter) ."&amp;orderby=". $_GET['orderby'] ."&amp;sortorder=". $_GET['sortorder']; ?>"><? print $letter ?></a>
<?php
	    }
?>
    </td>
   </tr>
   <tr>
    <td colspan="5">&nbsp;</td>
   </tr>
   <tr>
    <td>
     <img src="<?php print ICON_PORTS; ?>" alt="port icon" />
     <a href="<?php print $this->parent->self ."?mode=". $this->parent->mode ."&amp;breaker=". urlencode($_GET['breaker']) ."&amp;orderby=port_name&amp;sortorder="; if($_GET['sortorder'] == "ASC") print "DESC"; if($_GET['sortorder'] == 'DESC') print "ASC"; ?>">
      <i><?php print _("Name"); ?></i>
     </a>
    </td>
    <td>
     <img src="<?php print ICON_PORTS; ?>" alt="port icon" />
     <a href="<?php print $this->parent->self ."?mode=". $this->parent->mode ."&amp;breaker=". urlencode($_GET['breaker']) ."&amp;orderby=port_desc&amp;sortorder="; if($_GET['sortorder'] == "ASC") print "DESC"; if($_GET['sortorder'] == 'DESC') print "ASC"; ?>">
      <i><?php print _("Description"); ?></i>
     </a>
    </td>
    <td>
     <img src="<?php print ICON_PORTS; ?>" alt="port icon" />
     <a href="<?php print $this->parent->self ."?mode=". $this->parent->mode ."&amp;breaker=". urlencode($_GET['breaker']) ."&amp;orderby=port_number&amp;sortorder="; if($_GET['sortorder'] == "ASC") print "DESC"; if($_GET['sortorder'] == 'DESC') print "ASC"; ?>">
      <i><?php print _("Port-Number"); ?></i>
     </a>
    </td>
    <td style="text-align: center;"><i><?php print _("Options"); ?></i></td>
   </tr>
<?php

            if(isset($_GET['breaker']) && $_GET['breaker'] != "#") {

               $result = $this->db->db_query("SELECT * FROM ". MYSQL_PREFIX ."ports WHERE port_name REGEXP '^". $_GET['breaker'] ."'"
	          ."ORDER BY ". $_GET['orderby'] ." ". $_GET['sortorder']);
            }
	    else {

               $result = $this->db->db_query("SELECT * FROM ". MYSQL_PREFIX ."ports ORDER BY ". $_GET['orderby'] ." ". $_GET['sortorder']);
	    }
	
            while($row = $result->fetchrow()) {

?>
   <tr onmouseover="setBackGrdColor(this, 'mouseover');" onmouseout="setBackGrdColor(this, 'mouseout');">
    <td>
     <img src="<?php print ICON_PORTS; ?>" alt="port icon" />
     <a href="<?php print $this->parent->self ."?mode=". $this->parent->mode ."&amp;screen=". MANAGE ."&amp;idx=". $row->port_idx; ?>">
      <?php if($row->port_user_defined == 'Y') print "<img src=\"". ICON_USERS ."\" alt=\"User defined port\" />"; ?>
      <?php print htmlentities($row->port_name); ?>
     </a>
    </td>
    <td><?php if($row->port_desc != "") print htmlentities($row->port_desc); else print "&nbsp;"; ?></td>
    <td><?php if($row->port_number != "") print $row->port_number; else print "&nbsp;"; ?></td>
    <td style="text-align: center;">
     <a href="<?php print $this->parent->self ."?mode=". $this->parent->mode ."&amp;screen=". DELETE ."&amp;idx=". $row->port_idx ."&amp;name=". urlencode($row->port_name); ?>">
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

                  $this->parent->startTable("<img src=\"". ICON_PORTS ."\" alt=\"port icon\" />&nbsp;". _("Create a new Port"));
		  $form_url = $this->parent->self ."?mode=". $this->parent->mode ."&amp;screen=". $this->parent->screen ."&amp;saveit=1;&amp;new=1";
		  $current->port_name = "";

               }
	       else {

                  $current = $this->db->db_fetchSingleRow("SELECT * FROM ". MYSQL_PREFIX ."ports WHERE port_idx='". $_GET['idx'] ."'");
                  $this->parent->startTable("<img src=\"". ICON_PORTS ."\" alt=\"port icon\" />&nbsp;". _("Modify Port") ." ". $this->parent->getClassVar($current, 'port_name'));
		  $form_url = $this->parent->self ."?mode=". $this->parent->mode ."&amp;screen=". $this->parent->screen ."&amp;saveit=1&amp;namebefore=". urlencode($this->parent->getClassVar($current, 'port_name')) ."&amp;idx=". $_GET['idx'];

               }

?>
  <form action="<?php print $form_url; ?>" method="post">
   <table style="width: 100%" class="withborder2">
    <tr>
     <td colspan="3">
      <img src="<?php print ICON_PORTS; ?>" alt="port icon" />&nbsp;<? print _("General"); ?>
     </td>
    </tr>
    <tr>
     <td><?php print _("Name:"); ?></td>
     <td><input type="text" name="port_name" size="30" value="<?php print $this->parent->getClassVar($current, 'port_name'); ?>" /></td>
     <td><?php print _("Name of the Port."); ?></td>
    </tr>
    <tr>
     <td><?php print _("Description:"); ?></td>
     <td><input type="text" name="port_desc" size="30" value="<?php print $this->parent->getClassVar($current, 'port_desc'); ?>" /></td>
     <td><?php print _("Short description of the port."); ?></td>
    </tr>
    <tr>
     <td colspan="3">
      <img src="<?php print ICON_PORTS; ?>" alt="port icon" />&nbsp;<? print _("Details"); ?>
     </td>
    </tr>
    <tr>
     <td><?php print _("Number:"); ?></td>
     <td>
      <input type="text" name="port_number" size="30" value="<?php print $this->parent->getClassVar($current, 'port_number'); ?>" />
     </td>
     <td><?php print _("Add multiple port splitted with ',' or lists like 22-25"); ?></td>
    </tr>
    <tr> 
     <td colspan="3">&nbsp;</td>
    </tr>
    <tr>
     <td style="text-align: center;"><a href="<?php print $this->parent->self ."?mode=". $this->parent->mode; ?>" title="Back"><img src="<? print ICON_ARROW_LEFT; ?>" alt="arrow left icon" /></a></td>
     <td><input type="submit" value="<?php print _("Save"); ?>" /></td>
     <td><?php print _("Save settings."); ?></td>
    </tr>
   </table>
  </form>
<?php

               $this->parent->closeTable();

            }
            else {

	       $error = 0;
	       $is_numeric = 1;

               if(!isset($_POST['port_name']) || $_POST['port_name'] == "") {

                  $this->parent->printError("<img src=\"". ICON_PORTS ."\" alt=\"port icon\" />&nbsp;". _("Manage Port"), _("Please enter a port name!"));
		  $error = 1;

               }

	       if(!$error && isset($_GET['new']) && $this->db->db_fetchSingleRow("SELECT port_idx FROM ". MYSQL_PREFIX ."ports WHERE port_name LIKE BINARY '". $_POST['port_name'] ."'")) {

	          $this->parent->printError("<img src=\"". ICON_PORTS ."\" alt=\"port icon\" />&nbsp;". _("Manage Port"), _("A port with that name already exists!"));
		  $error = 1;

               }

	       if(!$error && !isset($_GET['new']) && $_GET['namebefore'] != $_POST['port_name'] && $this->db->db_fetchSingleRow("SELECT port_idx FROM ". MYSQL_PREFIX ."ports WHERE port_name LIKE BINARY '". $_POST['port_name'] ."'")) {

	          $this->parent->printError("<img src=\"". ICON_PORTS ."\" alt=\"port icon\" />&nbsp;". _("Manage Port"), _("A port with that name already exists!"));
		  $error = 1;

               }

	       /* only one or several ports */
	       if(!$error && (preg_match("/,/", $_POST['port_number']) || preg_match("/-/", $_POST['port_number']))) {

		  $temp_ports = split(",", $_POST['port_number']);
		  foreach($temp_ports as $port) {

		     $port = trim($port); 

		     if(preg_match("/-/", $port)) {

			list($lower, $higher) = split("-", $port);

			if(!is_numeric($lower) || $lower <= 0 || $lower >= 65536)
			   $is_numeric = 0;

			if(!is_numeric($higher) || $higher <= 0 || $higher >= 65536)
			   $is_numeric = 0;

		     }
		     else {

			if(!is_numeric($port) || $port <= 0 || $port >= 65536)
			   $is_numeric = 0;

		     }
		  }
               }
	       elseif(!$error && !is_numeric($_POST['port_number']) || $_POST['port_number'] <= 0 || $_POST['port_number'] >= 65536)
		  $is_numeric = 0;

	       if(!$error && !$is_numeric) {
	       
		  $this->parent->printError("<img src=\"". ICON_PORTS ."\" alt=\"port icon\" />&nbsp;". _("Modify Port"), _("Please enter a decimal port number within 1 - 65535!"));
		  $error = 1;

               }

	       if(!$error) {

	          if(isset($_GET['new'])) {

		     $this->db->db_query("INSERT INTO ". MYSQL_PREFIX ."ports (port_name, port_desc, port_number, "
			."port_user_defined) VALUES ("
			."'". $_POST['port_name'] ."', "
			."'". $_POST['port_desc'] ."', "
			."'". $_POST['port_number'] ."', "
			."'Y')");
		  }
		  else {

		     $this->db->db_query("UPDATE ". MYSQL_PREFIX ."ports SET "
		        ."port_name='". $_POST['port_name'] ."', "
			."port_desc='". $_POST['port_desc'] ."', "
			."port_number='". $_POST['port_number'] ."', "
			."port_user_defined='Y' "
			."WHERE port_idx='". $_GET['idx'] ."'");

                  }

		  $this->parent->goBack();
               }
            }
            break;

         case DELETE:

            if(!isset($_GET['doit']))
               $this->parent->printYesNo("<img src=\"". ICON_PORTS ."\" alt=\"port icon\" />&nbsp;". _("Delete Port"), _("Delete Port") ." ". $_GET['name'] ."?");

            else {

               if(isset($_GET['idx'])) {

                  $this->db->db_query("DELETE FROM ". MYSQL_PREFIX ."ports WHERE port_idx='". $_GET['idx'] ."'");
		  $this->db->db_query("DELETE FROM ". MYSQL_PREFIX ."assign_ports WHERE afp_port_idx='". $_GET['idx'] ."'");
                  $this->parent->goBack();

               }
            }
	    break;

      }

   } // showHtml()
}

?>
