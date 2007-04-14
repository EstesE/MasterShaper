<?php

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

define('MANAGE_POS_CHAINS', 1);
define('MANAGE_POS_PIPES', 2);
define('MANAGE_POS_NETPATHS', 3);

class MSOVERVIEW {

   var $db;
   var $parent;

   /* Class constructor */
   function MSOVERVIEW($parent)
   {
      $this->db = $parent->db;
      $this->parent = $parent;
   } //MSOVERVIEW()

   /* interface output */
   function showHtml()
   {
      /* If authentication is enabled, check permissions */
      if($this->parent->getOption("authentication") == "Y" &&
	 !$this->parent->checkPermissions("user_show_rules")) {

	 $this->parent->printError("<img src=\"". ICON_HOME ."\" alt=\"home icon\" />&nbsp;". _("MasterShaper Ruleset Overview"), _("You do not have enough permissions to access this module!"));
	 return 0;

      }

      if(!isset($this->parent->screen))
         $this->parent->screen = 0;


      switch($this->parent->screen) {

         default:

            if(!isset($_GET['saveit'])) {

               $pipe_counter = 0;
	       $chain_counter = 0;
 
               $this->parent->startTable("<img src=\"". ICON_HOME ."\" alt=\"home icon\" />&nbsp;". _("MasterShaper Ruleset Overview"));

?>
     <form action="<?php print $this->parent->self ."?mode=". $this->parent->mode ."&amp;screen=". $this->parent->screen ."&amp;saveit=1"; ?>" method="POST">
<?php
	       /* get a list of network paths */
	       $network_paths = $this->db->db_query("SELECT * FROM ". MYSQL_PREFIX ."network_paths WHERE netpath_active='Y' ORDER BY netpath_position");
	    
	       while($network_path = $network_paths->fetchRow()) {
?>
     <table style="width: 100%;">
      <tr>
       <td style="height: 15px;" />
      </tr>
      <tr>
       <td>
        &nbsp;
        <?php print _("Network Path") ." '". $network_path->netpath_name ."'"; ?>
        <a href="<?php print $this->parent->self ."?mode=". $this->parent->mode ."&amp;screen=". MANAGE_POS_NETPATHS ."&amp;netpath_idx=". $network_path->netpath_idx ."&amp;to=0"; ?>"><img src="<? print ICON_PIPES_ARROW_DOWN; ?>" alt="Move netpath down" /></a>
        <a href="<?php print $this->parent->self ."?mode=". $this->parent->mode ."&amp;screen=". MANAGE_POS_NETPATHS ."&amp;netpath_idx=". $network_path->netpath_idx ."&amp;to=1"; ?>"><img src="<? print ICON_PIPES_ARROW_UP; ?>" alt="Move netpath up" /></a>
       </td>
      </tr>
      <tr>
       <td style="height: 5px;" />
      </tr>
      <tr>
       <td>
        <table style="width: 100%;" class="withborder">
         <tr>
          <td class="colhead" colspan="2" style="width: 20%;">&nbsp;<?php print _("Name"); ?></td>
          <td class="colhead" style="text-align: center;"><?php print _("Service Level"); ?></td>
          <td class="colhead" style="text-align: center;"><?php print _("Fallback"); ?></td>
          <td class="colhead" style="text-align: center;"><?php print _("Source"); ?></td>
          <td class="colhead" style="text-align: center;"><?php print _("Direction"); ?></td>
          <td class="colhead" style="text-align: center;"><?php print _("Destination"); ?></td>
          <td class="colhead" style="text-align: center;"><?php print _("Action"); ?></td>
          <td class="colhead" style="text-align: center;"><?php print _("Position"); ?></td>
         </tr>
<?php
		  /* get a list of chains for the current netpath */
		  $chains = $this->db->db_query("SELECT * FROM ". MYSQL_PREFIX ."chains WHERE "
		     ."chain_netpath_idx='". $network_path->netpath_idx ."' AND "
		     ."chain_active='Y' ORDER BY chain_position ASC");

		  while($chain = $chains->fetchRow()) {

?>
         <tr onmouseover="setBackGrdColor(this, 'mouseover');" onmouseout="setBackGrdColor(this, 'mouseout');">
	  <td colspan="2">
           <input type="hidden" name="chains[<?php print $chain_counter; ?>]" value="<? print $chain->chain_idx; ?>" />
           <img src="<?php print ICON_CHAINS; ?>" alt="chain icon" />&nbsp;
           <a href="<?php print $this->parent->self ."?mode=". MS_CHAINS ."&amp;screen=". MANAGE ."&amp;idx=". $chain->chain_idx; ?>" title="Modify chain <? print $chain->chain_name; ?>" onmouseover="staticTip.show('tipChain<? print $chain->chain_idx; ?>');" onmouseout="staticTip.hide();"><? print $chain->chain_name; ?></a>
	  </td>
 	  <td style="text-align: center;">
	   <select name="chain_sl_idx[<?php print $chain->chain_idx; ?>]">
	    <option value="0">--- Ignore QoS ---</option>
<?php
		     $chain_sl = $this->db->db_query("SELECT * FROM ". MYSQL_PREFIX ."service_levels");

		     while($sl = $chain_sl->fetchRow()) {

			print "<option value=\"". $sl->sl_idx ."\"";

			if($sl->sl_idx == $chain->chain_sl_idx)
			   print " selected=\"selected\"";

			print ">". $sl->sl_name ."</option>\n";

		     }
?>
           </select>
	  </td>
<?php

                     if($chain->chain_sl_idx != 0) {
?>
	  <td style="text-align: center;">
	   <select name="chain_fallback_idx[<?php print $chain->chain_idx; ?>]">
	    <option value="0">--- No Fallback ---</option>
<?php
			$chain_sl = $this->db->db_query("SELECT * FROM ". MYSQL_PREFIX ."service_levels");

			while($sl = $chain_sl->fetchRow()) {

			   print "<option value=\"". $sl->sl_idx ."\"";

			   if($sl->sl_idx == $chain->chain_fallback_idx)
			      print " selected=\"selected\"";

			   print ">". $sl->sl_name ."</option>\n";

			}
?>
           </select>
	  </td>
<?php
		     }
		     else {
?>
	  <td>
           &nbsp;
	  </td>
<?php
                     }
?>
	  <td style="text-align: center;">
	   <select name="chain_src_target[<?php print $chain->chain_idx; ?>]">
	    <option value="0">any</option>
<?php
		     $targets = $this->db->db_query("SELECT * FROM ". MYSQL_PREFIX ."targets");

		     while($target = $targets->fetchRow()) {

			print "<option value=\"". $target->target_idx ."\"";

			if($target->target_idx == $chain->chain_src_target)
			   print " selected=\"selected\"";

			print ">". $target->target_name ."</option>\n";

		     }
?>	     
           </select>
 	  </td>
          <td style="text-align: center;">
           <select name="chain_direction[<?php print $chain->chain_idx; ?>]">
            <option value="1" <?php if($chain->chain_direction == 1) print "selected=\"selected\""; ?>>--&gt;</option>
            <option value="2" <?php if($chain->chain_direction == 2) print "selected=\"selected\""; ?>>&lt;-&gt;</option>
	   </select>
	  </td>
	  <td style="text-align: center;">
	   <select name="chain_dst_target[<?php print $chain->chain_idx; ?>]">
	    <option value="0">any</option>
<?php
		     $targets = $this->db->db_query("SELECT * FROM ". MYSQL_PREFIX ."targets");

		     while($target = $targets->fetchRow()) {

			print "<option value=\"". $target->target_idx ."\"";

			if($target->target_idx == $chain->chain_dst_target)
			   print " selected=\"selected\"";

			print ">". $target->target_name ."</option>\n";

		     }
?>	     
           </select>
	  </td>
	  <td style="text-align: center;">
           <select name="chain_action[<?php print $chain->chain_idx; ?>]">
	    <option value="accept" <?php if($chain->chain_action == "accept") print "selected=\"selected\""; ?>><? print _("Accept"); ?></option>
	    <option value="drop" <?php if($chain->chain_action == "drop") print "selected=\"selected\""; ?>><? print _("Drop"); ?></option>
	    <option value="reject" <?php if($chain->chain_action == "reject") print "selected=\"selected\""; ?>><? print _("Reject"); ?></option>
	   </select>
	  </td>
          <td style="text-align: center;">
           <a href="<?php print $this->parent->self."?mode=". $this->parent->mode ."&amp;screen=". MANAGE_POS_CHAINS ."&amp;chain_idx=". $chain->chain_idx ."&amp;to=0"; ?>"><img src="<? print ICON_CHAINS_ARROW_DOWN; ?>" alt="Move chain down" /></a>
           <a href="<?php print $this->parent->self."?mode=". $this->parent->mode ."&amp;screen=". MANAGE_POS_CHAINS ."&amp;chain_idx=". $chain->chain_idx ."&amp;to=1"; ?>"><img src="<? print ICON_CHAINS_ARROW_UP; ?>" alt="Move chain up" /></a>
          </td>
	 </tr>
<?php	

		     /* pipes are only available if the chain DOES NOT ignore QoS or DOES NOT use fallback service level */
		     if($chain->chain_sl_idx != 0 && $chain->chain_fallback_idx != 0) {
 
			$counter = 1;
			$pipes = $this->db->db_query("SELECT * FROM ". MYSQL_PREFIX ."pipes WHERE pipe_chain_idx='". $chain->chain_idx ."' "
			   ."AND pipe_active='Y' ORDER BY pipe_position ASC");

			while($pipe = $pipes->fetchRow()) {

			   $sl = $this->db->db_fetchSingleRow("SELECT * FROM ". MYSQL_PREFIX ."service_levels WHERE sl_idx='". $pipe->pipe_sl_idx ."'");
?>
         <input type="hidden" name="pipes[<?php print $pipe_counter; ?>]" value="<? print $pipe->pipe_idx; ?>" />
         <tr onmouseover="setBackGrdColor(this, 'mouseover');" onmouseout="setBackGrdColor(this, 'mouseout');">
          <td style="text-align: center;">
	   <?php print $counter; ?>
	  </td>
	  <td>
           <img src="<?php print ICON_PIPES; ?>" alt="pipes icon" />&nbsp;
   	   <a href="<?php print $this->parent->self ."?mode=2&amp;screen=". MANAGE ."&amp;idx=". $pipe->pipe_idx; ?>" title="Modify pipe <? print $pipe->pipe_name; ?>" onmouseover="staticTip.show('tipPipe<? print $pipe->pipe_idx; ?>');" onmouseout="staticTip.hide();"><? print $pipe->pipe_name; ?></a>
          </td>
          <td style="text-align: center;">
	   <select name="pipe_sl_idx[<?php print $pipe->pipe_idx; ?>]">
<?php
			   $pipe_sl = $this->db->db_query("SELECT * FROM ". MYSQL_PREFIX ."service_levels");

			   while($sl = $pipe_sl->fetchRow()) {

			      print "<option value=\"". $sl->sl_idx ."\"";

			      if($sl->sl_idx == $pipe->pipe_sl_idx)
				 print " selected=\"selected\"";

			      print ">". $sl->sl_name ."</option>\n";

			   }
?>
           </select>
	  </td>
	  <td>&nbsp;</td>
	  <td style="text-align: center;">
	   <select name="pipe_src_target[<?php print $pipe->pipe_idx; ?>]">
	    <option value="0">any</option>
<?php
			   $targets = $this->db->db_query("SELECT * FROM ". MYSQL_PREFIX ."targets");

			   while($target = $targets->fetchRow()) {

			      print "<option value=\"". $target->target_idx ."\"";

			      if($target->target_idx == $pipe->pipe_src_target)
				 print " selected=\"selected\"";

			      print ">". $target->target_name ."</option>\n";

			   }
?>	     
           </select>
 	  </td>
          <td style="text-align: center;">
           <select name="pipe_direction[<?php print $pipe->pipe_idx; ?>]">
            <option value="1" <?php if($pipe->pipe_direction == 1) print "selected=\"selected\""; ?>>--&gt;</option>
            <option value="2" <?php if($pipe->pipe_direction == 2) print "selected=\"selected\""; ?>>&lt;-&gt;</option>
	   </select>
	  </td>
	  <td style="text-align: center;">
	   <select name="pipe_dst_target[<?php print $pipe->pipe_idx; ?>]">
	    <option value="0">any</option>
<?php
			   $targets = $this->db->db_query("SELECT * FROM ". MYSQL_PREFIX ."targets");

			   while($target = $targets->fetchRow()) {

			      print "<option value=\"". $target->target_idx ."\"";

			      if($target->target_idx == $pipe->pipe_dst_target)
				 print " selected=\"selected\"";

			      print ">". $target->target_name ."</option>\n";

			   }
?>	     
           </select>
          </td>
	  <td style="text-align: center;">
           <select name="pipe_action[<?php print $pipe->pipe_idx; ?>]">
	    <option value="accept" <?php if($pipe->pipe_action == "accept") print "selected=\"selected\""; ?>><? print _("Accept"); ?></option>
	    <option value="drop" <?php if($pipe->pipe_action == "drop") print "selected=\"selected\""; ?>><? print _("Drop"); ?></option>
	    <option value="reject" <?php if($pipe->pipe_action == "reject") print "selected=\"selected\""; ?>><? print _("Reject"); ?></option>
	   </select>
	  </td>
          <td style="text-align: center;">
           <a href="<?php print $this->parent->self ."?mode=". $this->parent->mode ."&amp;screen=". MANAGE_POS_PIPES ."&amp;pipe_idx=". $pipe->pipe_idx ."&amp;to=0"; ?>"><img src="<? print ICON_PIPES_ARROW_DOWN; ?>" alt="Move pipe down" /></a>
           <a href="<?php print $this->parent->self ."?mode=". $this->parent->mode ."&amp;screen=". MANAGE_POS_PIPES ."&amp;pipe_idx=". $pipe->pipe_idx ."&amp;to=1"; ?>"><img src="<? print ICON_PIPES_ARROW_UP; ?>" alt="Move pipe up" /></a>
          </td>
         </tr>
<?php 
			  $filters = $this->db->db_query("SELECT a.filter_idx as filter_idx, a.filter_name as filter_name FROM ". MYSQL_PREFIX ."filters a, ". MYSQL_PREFIX ."assign_filters b WHERE b.apf_pipe_idx='". $pipe->pipe_idx ."' AND b.apf_filter_idx=a.filter_idx AND a.filter_active='Y'");
			  while($filter = $filters->fetchRow()) {
		
?>
         <tr onmouseover="setBackGrdColor(this, 'mouseover');" onmouseout="setBackGrdColor(this, 'mouseout');">
	  <td />
          <td colspan="7">
  	   <img src="images/tree_end.gif" alt="tree" />
   	   <img src="<?php print ICON_FILTERS; ?>" alt="filter icon" />&nbsp;
           <a href="<?php print $this->parent->self ."?mode=8&amp;screen=". MANAGE ."&amp;idx=". $filter->filter_idx; ?>" title="Modify filter <? print $filter->filter_name; ?>"><? print $filter->filter_name; ?></a>
          </td>
          <td> 
           &nbsp; 
          </td>
         </tr>
<?php
        
			   }

			   $counter++;
			   $pipe_counter++;

			}
		     }

                     $chain_counter++;

		  }
?>
        </table>
       </td>
      </tr>
      <tr>
      </tr>
     </table>
<?php
	       }

	       $this->parent->showSaveButton();
?>
     </form>
<?php

               $this->parent->closeTable();

	    }
	    else {

               if(isset($_POST['chains'])) {

	          foreach($_POST['chains'] as $chain_idx) {

		  $this->db->db_query("UPDATE ". MYSQL_PREFIX ."chains SET "
		     ."chain_sl_idx='". $_POST['chain_sl_idx'][$chain_idx] ."', "
		     ."chain_fallback_idx='". $_POST['chain_fallback_idx'][$chain_idx] ."', "
		     ."chain_src_target='". $_POST['chain_src_target'][$chain_idx] ."', "
		     ."chain_dst_target='". $_POST['chain_dst_target'][$chain_idx] ."', "
		     ."chain_direction='". $_POST['chain_direction'][$chain_idx] ."', "
		     ."chain_action='". $_POST['chain_action'][$chain_idx] ."' "
		     ."WHERE chain_idx='". $chain_idx ."'");

		  }
	       }

	       if(isset($_POST['pipes'])) {

	          foreach($_POST['pipes'] as $pipe_idx) {

		     $this->db->db_query("UPDATE ". MYSQL_PREFIX ."pipes SET "
		        ."pipe_sl_idx='". $_POST['pipe_sl_idx'][$pipe_idx] ."', "
			."pipe_src_target='". $_POST['pipe_src_target'][$pipe_idx] ."', "
			."pipe_dst_target='". $_POST['pipe_dst_target'][$pipe_idx] ."', "
			."pipe_direction='". $_POST['pipe_direction'][$pipe_idx] ."', "
			."pipe_action='". $_POST['pipe_action'][$pipe_idx] ."' "
			."WHERE pipe_idx='". $pipe_idx ."'");

                  }
               }

	       $this->parent->goBack();

	    }
	    break;

	 case MANAGE_POS_CHAINS:

            if($_GET['chain_idx']) {
					
               // get my current position
               $my_pos = $this->db->db_fetchSingleRow("SELECT chain_position FROM ". MYSQL_PREFIX ."chains WHERE chain_idx='". $_GET['chain_idx'] ."'");
               if($_GET['to'] == 1) 
                  $new_pos = $my_pos->chain_position - 1;
               else
                  $new_pos = $my_pos->chain_position + 1;

               $this->db->db_query("UPDATE ". MYSQL_PREFIX ."chains SET chain_position='". $my_pos->chain_position ."' WHERE chain_position='". $new_pos ."'");
               $this->db->db_query("UPDATE ". MYSQL_PREFIX ."chains SET chain_position='". $new_pos ."' WHERE chain_idx='". $_GET['chain_idx'] ."'");
            }

            $this->parent->goBack();
            break;

         case MANAGE_POS_PIPES:

            if($_GET['pipe_idx']) {

               $my_pos = $this->db->db_fetchSingleRow("SELECT pipe_position FROM ". MYSQL_PREFIX ."pipes WHERE pipe_idx='". $_GET['pipe_idx'] ."'");
               if($_GET['to'] == 1)
                  $new_pos = $my_pos->pipe_position - 1;
               else
                  $new_pos = $my_pos->pipe_position + 1;

               $this->db->db_query("UPDATE ". MYSQL_PREFIX ."pipes SET pipe_position='". $my_pos->pipe_position ."' WHERE pipe_position='". $new_pos ."'");
               $this->db->db_query("UPDATE ". MYSQL_PREFIX ."pipes SET pipe_position='". $new_pos ."' WHERE pipe_idx='". $_GET['pipe_idx'] ."'");

            }

            $this->parent->goBack();
            break;

         case MANAGE_POS_NETPATHS:

            if($_GET['netpath_idx']) {

               $my_pos = $this->db->db_fetchSingleRow("SELECT netpath_position FROM ". MYSQL_PREFIX ."network_paths WHERE netpath_idx='". $_GET['netpath_idx'] ."'");
               if($_GET['to'] == 1)
                  $new_pos = $my_pos->netpath_position - 1;
               else
                  $new_pos = $my_pos->netpath_position + 1;

               $this->db->db_query("UPDATE ". MYSQL_PREFIX ."network_paths SET netpath_position='". $my_pos->netpath_position ."' WHERE netpath_position='". $new_pos ."'");
               $this->db->db_query("UPDATE ". MYSQL_PREFIX ."network_paths SET netpath_position='". $new_pos ."' WHERE netpath_idx='". $_GET['netpath_idx'] ."'");

            }

            $this->parent->goBack();
      }


   } // showHtml()

}

?>
