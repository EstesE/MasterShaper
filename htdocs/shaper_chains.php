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

class MSCHAINS {

   var $db;
   var $parent;

   /* Class constructor */
   function MSCHAINS($parent)
   {
      $this->parent = $parent;
      $this->db = $parent->db;
   } // MSCHAIN()

   /* interface output */
   function showHtml()
   {

      /* If authentication is enabled, check permissions */
      if($this->parent->getOption("authentication") == "Y" && 
         !$this->parent->checkPermissions("user_manage_chains")) {

         $this->parent->printError("<img src=\"". ICON_CHAINS ."\" alt=\"chain icon\" />&nbsp;". _("Manage Chains"), _("You do not have enough permissions to access this module!"));
	 return 0;

      }
   
      if(!isset($this->parent->screen))
         $this->parent->screen = 0;

      switch($this->parent->screen) {

	 default:
	 case 0:
	    $this->parent->startTable("<img src=\"". ICON_CHAINS ."\" alt=\"chain icon\" />&nbsp;". _("Manage Chains"));

?>
     <table style="width: 100%;" class="withborder">
      <tr>
<?php
	    if(isset($_GET['saved']) && $_GET['saved']) {
?>
       <td colspan="4" style="text-align: center;" class="sysmessage"><?php print _("You have made changes to the ruleset. Don't forget to reload them."); ?></td>
<?php
	    } else {
?>
       <td colspan="4">&nbsp;</td>
<?php
	    }
?>
      </tr>
      <tr>
       <td colspan="4" style="text-align: center;">
        <img src="<?php print ICON_NEW; ?>" alt="new icon" />
        <a href="<?php print $this->parent->self ."?mode=". $this->parent->mode ."&amp;screen=". MANAGE ."&amp;new=1"; ?>" title="<? print _("Create a new Chain"); ?>"><? print _("Create a new Chain"); ?></a>
       </td>
      </tr>
      <tr>
       <td colspan="4">&nbsp;</td>
      </tr>
      <tr>
       <td><img src="<?php print ICON_CHAINS; ?>" alt="chain icon" />&nbsp;<i><? print _("Chain-Name"); ?></i></td>
       <td><img src="<?php print ICON_SERVICELEVELS; ?>" alt="servicelevel icon" />&nbsp;<i><? print _("Service Level"); ?></i></td>
       <td><img src="<?php print ICON_SERVICELEVELS; ?>" alt="servicelevel icon" />&nbsp;<i><? print _("Fallback"); ?></i></td>
       <td style="text-align: center;"><i><?php print _("Options"); ?></i></td>
      </tr>
<?php
	    $result = $this->db->db_query("SELECT * FROM ". MYSQL_PREFIX ."chains ORDER BY chain_name ASC");

	    while($row = $result->fetchrow()) {

	       if($row->chain_sl_idx != 0) {

		  $sl = $this->db->db_fetchSingleRow("SELECT sl_name FROM ". MYSQL_PREFIX ."service_levels WHERE sl_idx='". $row->chain_sl_idx ."'");

                  if($row->chain_fallback_idx != 0) 
		     $fallback = $this->db->db_fetchSingleRow("SELECT sl_name FROM ". MYSQL_PREFIX ."service_levels WHERE sl_idx='". $row->chain_fallback_idx ."'");
		  else
		     $fallback->sl_name = _("No Fallback");

	       }
	       else {

		  $sl->sl_name = _("Ignore QoS");
		  $fallback->sl_name = _("Ignore QoS");

	       }
?>
      <tr onmouseover="setBackGrdColor(this, 'mouseover');" onmouseout="setBackGrdColor(this, 'mouseout');">
       <td>
        <img src="<?php print ICON_CHAINS; ?>" alt="chain icon" />
        <a href="<?php print $this->parent->self ."?mode=". $this->parent->mode ."&amp;screen=". MANAGE ."&amp;idx=". $row->chain_idx; ?>" title="Click to modify">
         <?php print $row->chain_name; ?>
        </a>
       </td>
       <td>
<?php
	       if($row->chain_sl_idx != 0) {
?>
        <img src="<?php print ICON_SERVICELEVELS; ?>" alt="servicelevel icon" />
        <a href="<?php print $this->parent->self ."?mode=3&amp;screen=". MANAGE ."&amp;idx=". $row->chain_sl_idx; ?>">
         <?php print $sl->sl_name; ?>
        </a>
<?php
	       } else {
?>
        <img src="<?php print ICON_SERVICELEVELS; ?>" alt="servicelevel icon" />
        <?php print $sl->sl_name; ?>
<?php 
	       }
?>
       </td>
       <td>
<?php
	       if($row->chain_sl_idx != 0 && $row->chain_fallback_idx != 0) {
?>
        <img src="<?php print ICON_SERVICELEVELS; ?>" alt="servicelevel icon" />
        <a href="<?php print $this->parent->self ."?mode=3&amp;screen=". MANAGE ."&amp;idx=". $row->chain_fallback_idx; ?>">
         <?php print $fallback->sl_name; ?>
        </a>
<?php
	       } else {
?>
        <img src="<?php print ICON_SERVICELEVELS; ?>" alt="servicelevel icon" />
        <?php print $fallback->sl_name; ?>
<?php
	       }
?>
       </td>
       <td style="text-align: center;">
        <a href="<?php print $this->parent->self ."?mode=". $this->parent->mode ."&amp;screen=". DELETE ."&amp;idx=". $row->chain_idx ."&amp;name=". urlencode($row->chain_name); ?>" title="Delete">
         <img src="<?php print ICON_DELETE; ?>" alt="delete icon" />
        </a>
<?php
	       if($row->chain_active == 'Y') {
?>
        <a href="<?php print $this->parent->self ."?mode=". $this->parent->mode ."&amp;screen=". CHGSTATUS ."&amp;idx=". $row->chain_idx ."&amp;to=0"; ?>" title="Disable chain <? print $row->chain_name; ?>">
         <img src="<?php print ICON_ACTIVE; ?>" alt="status icon" />
        </a>
<?php
	       } else {
?>
        <a href="<?php print $this->parent->self ."?mode=". $this->parent->mode ."&amp;screen=". CHGSTATUS ."&amp;idx=". $row->chain_idx ."&amp;to=1"; ?>" title="Enable chain <? print $row->chain_name; ?>">
         <img src="<?php print ICON_INACTIVE; ?>" alt="status icon"  />
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

	       $form_url = $this->parent->self ."?mode=". $this->parent->mode ."&amp;screen=". $this->parent->screen ."&amp;saveit=1";

               if(isset($_GET['new'])) {

	          $this->parent->startTable("<img src=\"". ICON_CHAINS ."\" alt=\"chain icon\" />&nbsp;". _("Create a new Chain"));
		  $form_url.= "&amp;new=1";

		  /* set defaults */
		  $current->chain_active = "Y";
		  $current->chain_fallback_idx = -1;
		  $current->chain_direction = 2;

	       }
	       else {

	          $current = $this->db->db_fetchSingleRow("SELECT * FROM ". MYSQL_PREFIX ."chains WHERE chain_idx='". $_GET['idx'] ."'");
	          $this->parent->startTable("<img src=\"". ICON_CHAINS ."\" alt=\"chain icon\" />&nbsp;". _("Modify Chain") ." ". $this->parent->getClassVar($current, 'chain_name'));
		  $form_url.= "&amp;idx=". $_GET['idx'] ."&amp;namebefore=". urlencode($this->parent->getClassVar($current, 'chain_name'));

	       }
	       
?>
  <form action="<?php print $form_url; ?>" method="post" id="chains">
  <table style="width: 100%;">
   <tr>
    <td style="width: 50%;">
     <table style="width: 100%;" class="withborder2">
      <tr>
       <td colspan="2">
        <img src="<?php print ICON_CHAINS; ?>" alt="chain icon" />&nbsp;<? print _("General"); ?>
       </td>
      </tr>
      <tr>
       <td style="white-space: nowrap;"><?php print _("Name:"); ?></td>
       <td style="white-space: nowrap;"><input type="text" name="chain_name" size="40" value="<?php print $this->parent->getClassVar($current, 'chain_name'); ?>" /></td>
      </tr>
      <tr>
       <td style="white-space: nowrap;"><?php print _("Status:"); ?></td>
       <td style="white-space: nowrap;">
        <input type="radio" name="chain_active" value="Y" <?php if($this->parent->getClassVar($current, 'chain_active') == "Y") print "checked=\"checked\""; ?> /><? print _("Active"); ?>
        <input type="radio" name="chain_active" value="N" <?php if($this->parent->getClassVar($current, 'chain_active') != "Y") print "checked=\"checked\""; ?> /><? print _("Inactive"); ?>
       </td>
      </tr>
      <tr>
       <td colspan="2">
        <img src="<?php print ICON_CHAINS; ?>" alt="chain icon" />&nbsp;<? print _("Bandwidth"); ?>
       </td>
      </tr>
      <tr>
       <td style="white-space: nowrap;"><?php print _("Service Level:"); ?></td>
       <td style="white-space: nowrap;">
        <select name="chain_sl_idx">
<?php
	       $result = $this->db->db_query("SELECT * FROM ". MYSQL_PREFIX ."service_levels ORDER BY sl_name ASC");
	       
	       while($row = $result->fetchRow()) {

		  print "<option value=\"". $row->sl_idx ."\"";

		  if($row->sl_idx == $this->parent->getClassVar($current, 'chain_sl_idx'))
		     print " selected=\"selected\"";

		  switch($this->parent->getOption("classifier")) {
		     case 'HTB':
			print ">". $row->sl_name ." (in: ". $row->sl_htb_bw_in_rate ."kbit/s, out: ". $row->sl_htb_bw_out_rate ."kbit/s)</option>\n";
			break;
		     case 'HFSC':
			print ">". $row->sl_name ." (in: ". $row->sl_hfsc_in_dmax ."ms,". $row->sl_hfsc_in_rate ."kbit/s, out: ". $row->sl_hfsc_out_dmax ."ms,". $row->sl_hfsc_bw_out_rate ."kbit/s)</option>\n";
			break;
		     case 'CBQ':
			print ">". $row->sl_name ." (in: ". $row->sl_cbq_in_rate ."kbit/s, out: ". $row->sl_cbq_out_rate ."kbit/s)</option>\n";
			break;

		  }
	       }
?>
         <option value="0" <?php if($this->parent->getClassVar($current, 'chain_sl_idx') == 0) print "selected=\"selected\""; ?>>--- <? print _("Ignore QoS"); ?> ---</option>
        </select>
       </td>
      </tr>
      <tr>
       <td style="white-space: nowrap;"><?php print _("Fallback:"); ?></td>
       <td style="white-space: nowrap;">
        <select name="chain_fallback_idx">
<?php
	       $result = $this->db->db_query("SELECT * FROM ". MYSQL_PREFIX ."service_levels ORDER BY sl_name ASC");

	       while($row = $result->fetchRow()) {

		  print "<option value=\"". $row->sl_idx ."\"";

		  if($row->sl_idx == $this->parent->getClassVar($current, 'chain_fallback_idx'))
		     print " selected=\"selected\"";

		  switch($this->parent->getOption("classifier")) {

		     case 'HTB':
			print ">". $row->sl_name ." (in: ". $row->sl_htb_bw_in_rate ."kbit/s, out: ". $row->sl_htb_bw_out_rate ."kbit/s)</option>\n";
			break;
		     case 'HFSC':
			print ">". $row->sl_name ." (in: ". $row->sl_hfsc_in_dmax ."ms,". $row->sl_hfsc_in_rate ."kbit/s, out: ". $row->sl_hfsc_out_dmax ."ms,". $row->sl_hfsc_bw_out_rate ."kbit/s)</option>\n";
			break;
		     case 'CBQ':
			print ">". $row->sl_name ." (in: ". $row->sl_cbq_in_rate ."kbit/s, out: ". $row->sl_cbq_out_rate ."kbit/s)</option>\n";
			break;
		  }
	       }
?>
         <option value="0" <?php if($this->parent->getClassVar($current, 'chain_fallback_idx') == 0) print "selected=\"selected\""; ?>>--- <? print _("No Fallback"); ?> ---</option>
        </select>
       </td>
      </tr>
      <tr>
       <td colspan="2">
        <img src="<?php print ICON_CHAINS; ?>" alt="chain icon" />&nbsp;<? print _("Targets"); ?>
       </td>
      </tr>
      <tr>
       <td><?php print _("Network Path:"); ?></td>
       <td>
        <select name="chain_netpath_idx">
<?php

               $result = $this->db->db_query("SELECT * FROM ". MYSQL_PREFIX ."network_paths ORDER BY netpath_name ASC");
	       while($row = $result->fetchRow()) {
?>
         <option value="<?php print $row->netpath_idx; ?>" <? if($this->parent->getClassVar($current, 'chain_netpath_idx') == $row->netpath_idx) print "selected=\"selected\""; ?>><? print $row->netpath_name; ?></option>
<?php
	       }
?>
        </select>
       </td>
      </tr>
      <tr>
       <td style="white-space: nowrap;"><?php print _("Match targets:"); ?></td>
       <td style="white-space: nowrap;">
        <table class="noborder">
         <tr>
          <td><?php print _("Target"); ?></td>
          <td>&nbsp;</td>
          <td style="text-align: right;"><?php print _("Target"); ?></td>
         </tr>
         <tr>
          <td>
           <select name="chain_src_target">
            <option value="0">any</option>
<?php
	       $result = $this->db->db_query("SELECT * FROM ". MYSQL_PREFIX ."targets ORDER BY target_name ASC");
	       while($row = $result->fetchRow()) {
		  
		  print "<option value=\"". $row->target_idx ."\"";

		  if($row->target_idx == $this->parent->getClassVar($current, 'chain_src_target'))
		     print " selected=\"selected\"";

		  print ">". $row->target_name ."</option>\n";
	       }
?>
           </select>
          </td>
          <td>
           <select name="chain_direction">
            <option value="1" <?php if($this->parent->getClassVar($current, 'chain_direction') == 1) print "selected=\"selected\""; ?>>--&gt;</option>
            <option value="2" <?php if($this->parent->getClassVar($current, 'chain_direction') == 2) print "selected=\"selected\""; ?>>&lt;-&gt;</option>
           </select>
          </td>
          <td>
           <select name="chain_dst_target">
            <option value="0">any</option>
<?php
	       $result = $this->db->db_query("SELECT * FROM ". MYSQL_PREFIX ."targets ORDER BY target_name ASC");
	       while($row = $result->fetchRow()) {

		  print "<option value=\"". $row->target_idx ."\"";

		  if($row->target_idx == $this->parent->getClassVar($current, 'chain_dst_target'))
		     print " selected=\"selected\"";

		  print ">". $row->target_name ."</option>\n";
	       }
?>
           </select>
          </td>
         </tr>
        </table>
       </td>
      </tr>
      <tr>
       <td colspan="2">&nbsp;</td>
      </tr>
      <tr>
       <td style="text-align: center;"><a href="<?php print $this->parent->self ."?mode=". $this->parent->mode; ?>" title="Back"><img src="<? print ICON_ARROW_LEFT; ?>" alt="arrow left icon" /></a></td>
       <td><input type="submit" value="<?php print _("Save"); ?>" onclick="selectAll(document.forms['chains'].elements['used[]']);" /></td>
      </tr>
     </table>
    </td>
    <td style="width: 10px;" />
    <td style="width: 49%; height: 100%;">
     <table style="width: 100%; height: 100%;" class="withborder2">
      <tr>
       <td>
        <?php print _("With <b>Name</b> it is possible to enter a identification for this chain. It is useful to enter a self-explanatory name here (OFFICE_LAN, DMZ, INTERNET, WLAN, ...) here.<br /><br /><b>Status</b> will enable or disable the chain. This option has only impact after the ruleset is reloaded. Disabled chains are ignored when reloading the ruleset and do not show up in the overview list.<br /><br /><b>Service Level</b> Specify the maximum bandwidth this chain provides. A chain contain one or more pipes which shape available chain bandwidth.<br /><br />Every traffic which get not matched against a chain's pipe can only use the <b>fallback</b> bandwidth. If no fallback service level is defined the chain is unable to contain pipes. Every traffic which will then get into this chain will be able use the chain's available bandwidth.<br /><br /><b>Network Path</b> assign this chain to a specific interface combination.<br /><br /><b>Affecting</b> limits the traffic which get into this chain.") ."\n"; ?>
       </td>
      </tr>
     </table>
    </td>
   </tr>
  </table>
  </form>
<?php
	       $this->parent->closeTable();
	    }
	    else {

	       $error = 0;

               /* Chain name specified? */
	       if(!isset($_POST['chain_name']) || $_POST['chain_name'] == "") {

	          $this->parent->printError("<img src=\"". ICON_CHAINS ."\" alt=\"chain icon\" />&nbsp;". _("Manage Chains"), _("Please enter a chain name!"));
		  $error = 1;

               }

               /* When we create a new chain, a chain with such a name already exists? */
	       if(!$error && isset($_GET['new']) && $this->db->db_fetchSingleRow("SELECT chain_idx FROM ". MYSQL_PREFIX ."chains WHERE "
	          ."chain_name LIKE BINARY '". $_POST['chain_name'] ."'")) {

		  $this->parent->printError("<img src=\"". ICON_CHAINS ."\" alt=\"chain icon\" />&nbsp;". _("Manage Chains"), _("A chain with such a name already exists!"));
		  $error = 1;

               }

	       /* When we modify a chain, does a chain with such a name already exists? (if the name has changed) */
	       if(!$error && !isset($_GET['new']) && $_POST['chain_name'] != $_GET['namebefore'] && $this->db->db_fetchSingleRow("SELECT "
	          ."chain_idx FROM ". MYSQL_PREFIX ."chains WHERE chain_name LIKE BINARY '". $_POST['chain_name'] ."'")) {

		  $this->parent->printError("<img src=\"". ICON_CHAINS ."\" alt=\"chain icon\" />&nbsp;". _("Manage Chains"), _("A chain with such a name already exists!"));
		  $error = 1;

               }

               if(!$error) {

                  if(isset($_GET['new'])) {
						
		     $max_pos = $this->db->db_fetchSingleRow("SELECT MAX(chain_position) as pos FROM ". MYSQL_PREFIX ."chains");

		     $this->db->db_query("INSERT INTO ". MYSQL_PREFIX ."chains (chain_name, chain_sl_idx, chain_src_target, "
			."chain_dst_target, chain_position, chain_direction, chain_netpath_idx, chain_active, "
			."chain_fallback_idx) VALUES ('". $_POST['chain_name'] ."', "
			."'". $_POST['chain_sl_idx'] ."', "
			."'". $_POST['chain_src_target'] ."', "
			."'". $_POST['chain_dst_target'] ."', "
			."'". ($max_pos->pos+1) ."', "
			."'". $_POST['chain_direction'] ."', "
			."'". $_POST['chain_netpath_idx'] ."', "
			."'". $_POST['chain_active'] ."', "
			."'". $_POST['chain_fallback_idx'] ."')");

                     $_GET['idx'] = $this->db->db_getid();

                  }
		  else {

		     $this->db->db_query("UPDATE ". MYSQL_PREFIX ."chains SET chain_name='". $_POST['chain_name'] ."', "
			."chain_sl_idx='". $_POST['chain_sl_idx'] ."', "
			."chain_src_target='". $_POST['chain_src_target'] ."', "
			."chain_dst_target='". $_POST['chain_dst_target'] ."', "
			."chain_direction='". $_POST['chain_direction'] ."', "
			."chain_netpath_idx='". $_POST['chain_netpath_idx'] ."', "
			."chain_active='". $_POST['chain_active'] ."', "
			."chain_fallback_idx='". $_POST['chain_fallback_idx'] ."' "
			."WHERE chain_idx='". $_GET['idx'] ."'");

                  }

		  $this->parent->goBack();

	       }
	    }
	    break;

	 case DELETE:

	    if(!isset($_GET['doit']))
	       $this->parent->printYesNo("<img src=\"". ICON_CHAINS ."\" alt=\"chain icon\" />&nbsp;". _("Delete Chain"), _("Delete Chain") ." ". $_GET['name'] ."?");
	    else {
	       if($_GET['idx']) 
		  $this->db->db_query("DELETE FROM ". MYSQL_PREFIX ."chains WHERE chain_idx='". $_GET['idx'] ."'");
	       $this->parent->goBack();
	    }
	    break;

	 case CHGSTATUS:
	    
	    if(isset($_GET['idx']) && isset($_GET['to'])) {

	       if($_GET['to'] == 1)
	          $this->db->db_query("UPDATE ". MYSQL_PREFIX ."chains SET chain_active='Y' WHERE chain_idx='". $_GET['idx'] ."'");
	       elseif($_GET['to'] == 0)
	          $this->db->db_query("UPDATE ". MYSQL_PREFIX ."chains SET chain_active='N' WHERE chain_idx='". $_GET['idx'] ."'");

	    }

	    $this->parent->goBack();
	    break;
      }

   } // showHtml()

}

?>
