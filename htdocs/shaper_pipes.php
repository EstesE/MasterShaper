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

class MSPIPES {

   var $db;
   var $parent;

   /* Class constructor */
   function MSPIPES($parent)
   {
      $this->parent = $parent;
      $this->db = $parent->db;
   }

   /* interface output */
   function showHtml()
   {

      /* If authentication is enabled, check permissions */
      if($this->parent->getOption("authentication") == "Y" &&
         !$this->parent->checkPermissions("user_manage_pipes")) {

         $this->parent->printError(
            "<img src=\"". ICON_PIPES ."\" alt=\"pipe icon\" />&nbsp;". _("Manage Pipes"),
            _("You do not have enough permissions to access this module!")
         );

         return 0;

      }

      if(!isset($this->parent->screen)) {

         $this->parent->screen = 0;

      }

      switch($this->parent->screen) {

         default:
         case 0:
	    
            $this->parent->startTable(
               "<img src=\"". ICON_PIPES ."\" alt=\"pipe icon\" />&nbsp;". _("Manage Pipes")
            );

?>
  <table style="width: 100%;" class="withborder"> 
   <tr>
<?php
            if(isset($_GET['saved'])) {

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
    <td style="text-align: center;" colspan="4">
     <img src="<?php print ICON_NEW; ?>" alt="new icon" />
     <a href="<?php print $this->parent->self ."?mode=". $this->parent->mode ."&amp;screen=". MANAGE ."&amp;new=1"; ?>"><? print _("Create a new Pipe"); ?></a>
    </td>
   </tr>
   <tr>
    <td colspan="4">&nbsp;</td>
   </tr>
   <tr>
    <td><img src="<?php print ICON_PIPES; ?>" alt="pipe icon" />&nbsp;<i><? print _("Pipes"); ?></i></td>
    <td><img src="<?php print ICON_CHAINS; ?>" alt="chain icon" />&nbsp;<i><? print _("Chains"); ?></i></td>
    <td><img src="<?php print ICON_FILTERS; ?>" alt="filter icon" />&nbsp;<i><? print _("Filters"); ?></i></td>
    <td style="text-align: center;"><i><?php print _("Options"); ?></i></td>
   </tr>
<?php

            $result = $this->db->db_query("
               SELECT * FROM ". MYSQL_PREFIX ."pipes
               ORDER BY pipe_chain_idx ASC, pipe_name ASC
            ");
	
            while($row = $result->fetchrow()) {

               $chain = $this->db->db_fetchSingleRow("
                  SELECT chain_name FROM ". MYSQL_PREFIX ."chains 
                  WHERE chain_idx='". $row->pipe_chain_idx ."'
               ");

?>
   <tr onmouseover="setBackGrdColor(this, 'mouseover');" onmouseout="setBackGrdColor(this, 'mouseout');">
    <td>
     <img src="<?php print ICON_PIPES; ?>" alt="pipe icon" />
     <a href="<?php print $this->parent->self ."?mode=". $this->parent->mode ."&amp;screen=". MANAGE ."&amp;idx=". $row->pipe_idx; ?>">
      <?php print $row->pipe_name; ?>
     </a>
    </td>
    <td>
     <img src="<?php print ICON_CHAINS; ?>" alt="chain icon" />
     <a href="<?php print $this->parent->self ."?mode=". MS_CHAINS ."&amp;screen=". MANAGE ."&amp;idx=". $row->pipe_chain_idx; ?>">
      <?php print $chain->chain_name; ?>
     </a>
    </td>
    <td>
<?php
               $filters = $this->db->db_query("
                  SELECT filter_idx, filter_name FROM ". MYSQL_PREFIX ."filters f
                  INNER JOIN ". MYSQL_PREFIX ."assign_filters apf
                  ON apf.apf_filter_idx=f.filter_idx
                  WHERE apf.apf_pipe_idx='". $row->pipe_idx ."'
               ");

               while($filter = $filters->fetchRow()) {

?>
     <img src="<?php print ICON_FILTERS; ?>" alt="filter icon" />
     <a href="<?php print $this->parent->self ."?mode=8&amp;screen=2&amp;idx=". $filter->filter_idx; ?>">
      <?php print $filter->filter_name; ?>
     </a>
<?php
               }
?>
    </td>
    <td style="text-align: center;">
     <a href="<?php print $this->parent->self ."?mode=". $this->parent->mode ."&amp;screen=". DELETE ."&amp;idx=". $row->pipe_idx ."&amp;name=". urlencode($row->pipe_name); ?>">
      <img src="<?php print ICON_DELETE; ?>" alt="delete icon" />
     </a>
<?php

               if($row->pipe_active == "Y") {

?>
     <a href="<?php print $this->parent->self ."?mode=". $this->parent->mode ."&amp;screen=". CHGSTATUS ."&amp;idx=". $row->pipe_idx ."&amp;to=0"; ?>" title="Disable pipe <? print $row->pipe_name; ?>">
      <img src="<?php print ICON_ACTIVE; ?>" alt="status icon" />
     </a>
<?php

               } else {

?>
     <a href="<?php print $this->parent->self ."?mode=". $this->parent->mode ."&amp;screen=". CHGSTATUS ."&amp;idx=". $row->pipe_idx ."&amp;to=1"; ?>" title="Enable pipe <? print $row->pipe_name; ?>">
      <img src="<?php print ICON_INACTIVE; ?>" alt="status icon" />
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

                  // preselect some values
                  $this->parent->startTable("<img src=\"". ICON_PIPES ."\" alt=\"pipe icon\" />&nbsp;". _("Create a new Pipe"));
                  $form_url = $this->parent->self ."?mode=". $this->parent->mode ."&amp;screen=". $this->parent->screen ."&amp;saveit=1&amp;new=1";

                  $current->pipe_direction = 2;
                  $current->pipe_active = 'Y';

               }
               else {

                  $current = $this->db->db_fetchSingleRow("
                     SELECT * FROM ". MYSQL_PREFIX ."pipes
                     WHERE pipe_idx='". $_GET['idx'] ."'
                  ");

                  $this->parent->startTable("<img src=\"". ICON_PIPES ."\" alt=\"pipe icon\" />&nbsp;". _("Modfiy pipe") ." ". $this->parent->getClassVar($current, 'pipe_name'));
                  $form_url = $this->parent->self ."?mode=". $this->parent->mode ."&amp;screen=". $this->parent->screen ."&amp;idx=". $_GET['idx'] ."&amp;namebefore=". urlencode($this->parent->getClassVar($current, 'pipe_name')) ."&amp;chainbefore=". urlencode($this->parent->getClassVar($current, 'pipe_chain_idx')) ."&amp;saveit=1";

               }
?>
  <form id="pipes" action="<?php print $form_url; ?>" method="post">
   <table style="width: 100%;" class="withborder2">
    <tr>
     <td colspan="3">
      <img src="<?php print ICON_PIPES; ?>" alt="pipe icon" />&nbsp;<? print _("General"); ?>
     </td>
    </tr>
    <tr>
     <td><?php print _("Name:"); ?></td>
     <td><input type="text" name="pipe_name" size="30" value="<?php print $this->parent->getClassVar($current, 'pipe_name'); ?>" /></td>
     <td><?php print _("Specify a name for the pipe."); ?></td>
    </tr>
    <tr>
     <td><?php print _("Status:"); ?></td>
     <td>
      <input type="radio" name="pipe_active" value="Y" <?php if($this->parent->getClassVar($current, 'pipe_active') == "Y") print "checked=\"checked\""; ?> /><? print _("Active"); ?>
      <input type="radio" name="pipe_active" value="N" <?php if($this->parent->getClassVar($current, 'pipe_active') != "Y") print "checked=\"checked\""; ?> /><? print _("Inactive"); ?>
     </td>
     <td><?php print _("With this option the status of this chain is specified. Disabled pipes are ignored when reloading the ruleset."); ?></td>
    </tr>
    <tr>
     <td colspan="3">
      <img src="<?php print ICON_PIPES; ?>" alt="pipe icon" />&nbsp;<? print _("Parameters"); ?>
     </td>
    </tr>
    <tr>
     <td><?php print _("Chain:"); ?></td>
     <td>
      <select name="pipe_chain_idx">
<?php

               $result = $this->db->db_query("SELECT * FROM ". MYSQL_PREFIX ."chains");

               while($row = $result->fetchrow()) {

                  print "<option value='". $row->chain_idx ."'";

                  if($row->chain_idx == $this->parent->getClassVar($current, 'pipe_chain_idx')) {

                     print " selected=\"selected\"";

                  }

                  print ">". $row->chain_name ."</option>\n";
               }

?>
      </select>
     </td>
     <td><?php print _("Select a chain which the pipe will be assigned to. Only chains which use fallback service levels are able to contain pipes."); ?></td>
    </tr>
    <tr>
     <td style="white-space: nowrap;">
      <?php print _("Target:"); ?>
     </td>
     <td>
      <table class="noborder">
       <tr>
        <td><?php print _("Source"); ?></td>
        <td>&nbsp;</td>
        <td style="text-align: right;"><?php print _("Destination"); ?></td>
       </tr>
       <tr>
        <td>
         <select name="pipe_src_target">
          <option value="0">any</option>
<?php

               $result = $this->db->db_query("
                  SELECT target_idx, target_name FROM ". MYSQL_PREFIX ."targets
                  ORDER BY target_name
               ");

               while($row = $result->fetchRow()) {

                  print "<option value=\"". $row->target_idx ."\" ";

                  if($this->parent->getClassVar($current, 'pipe_src_target') == $row->target_idx) {

                     print " selected=\"selected\"";

                  }

                  print ">". $row->target_name ."</option>\n";

               }

?>
         </select>
        </td>
        <td>
         <select name="pipe_direction">
          <option value="1" <?php if($this->parent->getClassVar($current, 'pipe_direction') == 1) print "selected=\"selected\""; ?>>--&gt;</option>
          <option value="2" <?php if($this->parent->getClassVar($current, 'pipe_direction') == 2) print "selected=\"selected\""; ?>>&lt;-&gt;</option>
         </select>
        </td>
        <td>
         <select name="pipe_dst_target">
          <option value="0">any</option>
<?php

               $result = $this->db->db_query("
                  SELECT target_idx, target_name FROM ". MYSQL_PREFIX ."targets
                  ORDER BY target_name
               ");

               while($row = $result->fetchRow()) {

                  print "<option value=\"". $row->target_idx ."\" ";

                  if($this->parent->getClassVar($current, 'pipe_dst_target') == $row->target_idx) {

                     print "selected=\"selected\"";

                  }

                  print ">". $row->target_name ."</option>\n";

               }

?>
               </select>
           </td>
          </tr>
      </table>
     </td>
     <td>
      <?php print _("Match a source and destination targets."); ?>
     </td>
    </tr>
    <tr>
     <td><?php print _("Filters:"); ?></td>
     <td>
      <table class="noborder">
       <tr>
        <td>
	 <select size="10" name="avail[]" multiple="multiple">
	  <option value="">********* <?php print _("Unused"); ?> *********</option>
<?php

               $unused_filters = $this->db->db_query("
                  SELECT DISTINCT f.filter_idx, f.filter_name FROM ". MYSQL_PREFIX ."filters f
                  INNER JOIN (SELECT apf_filter_idx FROM ". MYSQL_PREFIX ."assign_filters
                  WHERE apf_pipe_idx!='". $_GET['idx'] ."') apf
                  ON apf.apf_filter_idx=f.filter_idx
               ");
         
               while($filter = $unused_filters->fetchrow()) {

?>
       <option value="<?php print $filter->filter_idx; ?>"><? print $filter->filter_name; ?></option>
<?php

               }

?>
         </select>
	</td>
        <td>&nbsp;</td>
	<td>
	 <input type="button" value="&gt;&gt;" onclick="moveOptions(document.forms['pipes'].elements['avail[]'], document.forms['pipes'].elements['used[]']);" /><br />
	 <input type="button" value="&lt;&lt;" onclick="moveOptions(document.forms['pipes'].elements['used[]'], document.forms['pipes'].elements['avail[]']);" />
	</td>
	<td>&nbsp;</td>
	<td>
         <select size="10" name="used[]" multiple="multiple">
          <option value="">********* <?php print _("Used"); ?> *********</option>
<?php
               $used_filters = $this->db->db_query("
                  SELECT DISTINCT f.filter_idx, f.filter_name FROM ". MYSQL_PREFIX ."filters f
                  INNER JOIN (SELECT apf_filter_idx FROM ". MYSQL_PREFIX ."assign_filters
                  WHERE apf_pipe_idx='". $_GET['idx'] ."') apf
                  ON apf.apf_filter_idx=f.filter_idx
               ");
         
               while($filter = $used_filters->fetchrow()) {

?>
       <option value="<?php print $filter->filter_idx; ?>"><? print $filter->filter_name; ?></option>
<?php

               }
?>
         </select>
        </td>
       </tr>
      </table>
     </td>
     <td><?php print _("Select the filters this pipe will shape."); ?></td>
    </tr>
    <tr>
     <td colspan="3">
      <img src="<?php print ICON_PIPES; ?>" alt="pipe icon" />&nbsp;<? print _("Bandwidth"); ?>
     </td>
    </tr>
    <tr>
     <td><?php print _("Service-Level:"); ?></td>
     <td>
      <select name="pipe_sl_idx">
<?php

               $result = $this->db->db_query("
                  SELECT * FROM ". MYSQL_PREFIX ."service_levels
                  ORDER BY sl_name ASC
               ");

               while($row = $result->fetchRow()) {

                  print "<option value=\"". $row->sl_idx ."\"";

                  if($this->parent->getClassVar($current, 'pipe_sl_idx') == $row->sl_idx) {

                     print " selected=\"selected\"";	

                  }

                  print ">". $row->sl_name ."</option>\n";

               }

?>
      </select>
     </td>
     <td><?php print _("Bandwidth limit for this pipe."); ?></td>
    </tr>
    <tr>
     <td colspan="3">&nbsp;</td>
    </tr>
    <tr>
     <td style="text-align: center;"><a href="<?php print $this->parent->self ."?mode=". $this->parent->mode; ?>" title="Back"><img src="<? print ICON_ARROW_LEFT; ?>" alt="arrow left icon" /></a></td>
     <td><input type="submit" value="<?php print _("Save"); ?>" onclick="selectAll(document.forms['pipes'].elements['used[]']);" /></td>
     <td><?php print _("Save settings."); ?></td>
    </tr>
   </table>
  </form>
<?php
               $this->parent->closeTable();

            }
            else {
	       
               $error = 0;

               if(!isset($_POST['pipe_name']) ||
                  $_POST['pipe_name'] == "") {

                  $this->parent->printError("<img src=\"". ICON_PIPES ."\""
                     ." alt=\"pipe icon\" />&nbsp;". _("Manage pipe"),
                     _("Please enter a pipe name!")
                  );

                  $error = 1;

               }

               if(!$error &&
                  isset($_GET['new']) &&
                   $this->db->db_fetchSingleRow("
                        SELECT pipe_idx FROM ". MYSQL_PREFIX ."pipes
                        WHERE pipe_name LIKE BINARY '". $_POST['pipe_name'] ."'
                        AND pipe_chain_idx='". $_POST['pipe_chain_idx'] ."'")
               ) {

                  $this->parent->printError("<img src=\"". ICON_PIPES ."\""
                     ." alt=\"pipe icon\" />&nbsp;". _("Manage pipe"),
                     _("A pipe with that name already exists for that chain!")
                  );

                  $error = 1;

               }

               if(!$error &&
                  !isset($_GET['new']) &&
                  $_GET['namebefore'] != $_POST['pipe_name'] &&
                  $this->db->db_fetchSingleRow("
                     SELECT pipe_idx FROM ". MYSQL_PREFIX ."pipes
                     WHERE pipe_name LIKE BINARY '". $_POST['pipe_name'] ."'
                     AND pipe_chain_idx='". $_POST['pipe_chain_idx'] ."'")
               ) {

                  $this->parent->printError("<img src=\"". ICON_PIPES ."\""
                     ." alt=\"pipe icon\" />&nbsp;". _("Manage pipe"),
                     _("A pipe with that name already exists for that chain!")
                  );
               
                  $error = 1;

               }

               if(!$error &&
                  !isset($_GET['new']) &&
                  $_GET['chainbefore'] != $_POST['pipe_chain_idx'] &&
                  $this->db->db_fetchSingleRow("SELECT pipe_idx FROM ". MYSQL_PREFIX ."pipes
                     WHERE pipe_name LIKE BINARY '". $_POST['pipe_name'] ."'
                     AND pipe_chain_idx='". $_POST['pipe_chain_idx'] ."'")
               ) {

                  $this->parent->printError("<img src=\"". ICON_PIPES ."\""
                     ." alt=\"pipe icon\" />&nbsp;". _("Manage pipe"),
                     _("A pipe with that name already exists for that chain!")
                  );

                  $error = 1;

               }

               if(!$error) {

                  if(isset($_GET['new'])) {

                     $max_pos = $this->db->db_fetchSingleRow("
                        SELECT MAX(pipe_position) as pos FROM ". MYSQL_PREFIX ."pipes 
                        WHERE pipe_chain_idx='". $_POST['pipe_chain_idx'] ."'
                     ");

                     $this->db->db_query("
                        INSERT INTO ". MYSQL_PREFIX ."pipes 
                        (pipe_name, pipe_chain_idx, pipe_sl_idx, pipe_position,
                        pipe_src_target, pipe_dst_target, pipe_direction,
                        pipe_active)
                        VALUES (
                        '". $_POST['pipe_name'] ."', 
                        '". $_POST['pipe_chain_idx'] ."', 
                        '". $_POST['pipe_sl_idx'] ."', 
                        '". ($max_pos->pos+1) ."', 
                        '". $_POST['pipe_src_target'] ."', 
                        '". $_POST['pipe_dst_target'] ."', 
                        '". $_POST['pipe_direction'] ."', 
                        '". $_POST['pipe_active'] ."')
                     ");

                     $_GET['idx'] = $this->db->db_getid();

                  }
                  else {

                     $this->db->db_query("
                        UPDATE ". MYSQL_PREFIX ."pipes SET 
                        pipe_name='". $_POST['pipe_name'] ."', 
                        pipe_chain_idx='". $_POST['pipe_chain_idx'] ."', 
                        pipe_sl_idx='". $_POST['pipe_sl_idx'] ."', 
                        pipe_src_target='". $_POST['pipe_src_target'] ."', 
                        pipe_dst_target='". $_POST['pipe_dst_target'] ."', 
                        pipe_direction='". $_POST['pipe_direction'] ."', 
                        pipe_active='". $_POST['pipe_active'] ."' 
                        WHERE pipe_idx='". $_GET['idx'] ."'
                     ");

                  }

                  $this->db->db_query("
                     DELETE FROM ". MYSQL_PREFIX ."assign_filters
                     WHERE apf_pipe_idx='". $_GET['idx'] ."'
                  ");
			
                  if($_POST['used']) {

                     foreach($_POST['used'] as $use) {

                        if($use != "") {
                     
                           $this->db->db_query("
                              INSERT INTO ". MYSQL_PREFIX ."assign_filters
                              (apf_pipe_idx, apf_filter_idx)
                              VALUES
                              ('". $_GET['idx'] ."', '". $use ."')
                           ");

                        }
                     }
                  }

                  $this->parent->goBack();

               }
            }
            break;

         case DELETE:

            if(!isset($_GET['doit'])) {

               $this->parent->printYesNo("<img src=\"". ICON_PIPES ."\""
                  ." alt=\"pipe icon\" />&nbsp;". _("Delete Pipe"),
                  _("Delete pipe") ." ". $_GET['name'] ."?");

            } 
            else {

               if($_GET['idx']) {

                  $this->db->db_query("
                     DELETE FROM ". MYSQL_PREFIX ."pipes
                     WHERE pipe_idx='". $_GET['idx'] ."'
                  ");
                  $this->db->db_query("DELETE FROM ". MYSQL_PREFIX ."assign_filters
                     WHERE apf_pipe_idx='". $_GET['idx'] ."'
                  ");

               }

               $this->parent->goBack();

            }
            break;

         case CHGSTATUS:

            if(isset($_GET['idx'])) {

               if($_GET['to'] == 1) {

                  $this->db->db_query("UPDATE ". MYSQL_PREFIX ."pipes
                     SET pipe_active='Y'
                     WHERE pipe_idx='". $_GET['idx'] ."'
                  ");

               }

               if($_GET['to'] == 0) {

                  $this->db->db_query("UPDATE ". MYSQL_PREFIX ."pipes
                     SET pipe_active='N' 
                     WHERE pipe_idx='". $_GET['idx'] ."'
                  ");

               }

            }

            $this->parent->goBack();
            break;

      }

   } // showHtml()

}

?>
