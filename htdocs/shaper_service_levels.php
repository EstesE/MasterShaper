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

class MASTERSHAPER_SERVICELEVELS {

   var $db;
   var $parent;
   var $tmpl;

   /* Class constructor */
   function MASTERSHAPER_SERVICELEVELS($parent)
   {
      $this->parent = &$parent;
      $this->db = &$parent->db;
      $this->tmpl = &$this->parent->tmpl;

   } // MASTERSHAPER_SERVICELEVELS()

   /* interface output */
   function show()
   {
      /* If authentication is enabled, check permissions */
      if($this->parent->getOption("authentication") == "Y" &&
         !$this->parent->checkPermissions("user_manage_servicelevels")) {

         $this->parent->printError("<img src=\"". ICON_SERVICELEVELS ."\" alt=\"service level icon\" />&nbsp;". _("Manage Service Levels"), _("You do not have enough permissions to access this module!"));
         return 0;
      }

      if(!isset($_GET['mode'])) {
         $_GET['mode'] = "show";
      }
      if(!isset($_GET['idx']) ||
         (isset($_GET['idx']) && !is_numeric($_GET['idx'])))
         $_GET['idx'] = 0;

      switch($_GET['mode']) {
         default:
         case 'show':
            $this->showList();
            break;
         case 'new':
         case 'edit':
            $this->showEdit($_GET['idx']);
            break;
      }
   
   } // show()

   /**
    * display all service levels
    */
   private function showList()
   {
      $this->avail_service_levels = Array();
      $this->service_levels = Array();

      $res_sl = $this->db->db_query("
         SELECT *
         FROM ". MYSQL_PREFIX ."service_levels
         ORDER BY sl_name ASC
      ");

      $cnt_sl = 0; 

      while($sl = $res_sl->fetchrow()) {
         $this->avail_service_levels[$cnt_sl] = $sl->sl_idx;
         $this->service_levels[$sl->sl_idx] = $sl;
         $cnt_sl++;
      }

      $this->tmpl->register_block("service_level_list", array(&$this, "smarty_sl_list"));
      $this->tmpl->show("service_levels_list.tpl");

   } // showList() 

   /**
    * display interface to create or edit service levels
    */
   function showEdit($idx)
   {
      /* If authentication is enabled, check permissions */
      if($this->parent->getOption("authentication") == "Y" &&
         !$this->parent->checkPermissions("user_manage_ports")) {

         $this->parent->printError("<img src=\"". ICON_HOME ."\" alt=\"home icon\" />&nbsp;". _("MasterShaper Ruleset Service Levels"), _("You do not have enough permissions to access this module!"));
         return 0;
      }

      if($idx != 0) {
         $sl = $this->db->db_fetchSingleRow("
            SELECT *
            FROM ". MYSQL_PREFIX ."service_levels
            WHERE
               sl_idx='". $idx ."'
         ");
      }
      else {
         /* preset values here */
         
      }

      if(!isset($_GET['classifier']))
         $this->tmpl->assign('classifier', $this->parent->getOption("classifier"));
      else
         $this->tmpl->assign('classifier', $_GET['classifier']);

      if(!isset($_GET['qdiscmode']))
         $this->tmpl->assign('qdiscmode', $sl->sl_qdisc);
      else
         $this->tmpl->assign('qdiscmode', $_GET['qdiscmode']);

      $this->tmpl->assign('sl_idx', $idx);
      $this->tmpl->assign('sl_name', $sl->sl_name);
      $this->tmpl->assign('sl_htb_bw_in_rate', $sl->sl_htb_bw_in_rate);
      $this->tmpl->assign('sl_htb_bw_out_rate', $sl->sl_htb_bw_out_rate);
      $this->tmpl->assign('sl_htb_priority', $this->parent->getPriorityName($sl->sl_htb_priority));
      $this->tmpl->assign('sl_hfsc_in_dmax', $sl->sl_hfsc_in_dmax);
      $this->tmpl->assign('sl_hfsc_in_rate', $sl->sl_hfsc_in_rate);
      $this->tmpl->assign('sl_hfsc_out_dmax', $sl->sl_hfsc_out_dmax);
      $this->tmpl->assign('sl_hfsc_out_rate', $sl->sl_hfsc_out_rate);
      $this->tmpl->assign('sl_cbq_in_rate', $sl->sl_cbq_in_rate);
      $this->tmpl->assign('sl_cbq_out_rate', $sl->sl_cbq_out_rate);
      $this->tmpl->assign('sl_cbq_in_priority', $sl->sl_cbq_in_priority);
      $this->tmpl->assign('sl_cbq_out_priority', $sl->sl_cbq_out_priority);
      $this->tmpl->show("service_levels_edit.tpl");

   } // showEdit()

   /**
    * template function which will be called from the target listing template
    */
   public function smarty_sl_list($params, $content, &$smarty, &$repeat)
   {
      $index = $this->tmpl->get_template_vars('smarty.IB.sl_list.index');
      if(!$index) {
         $index = 0;
      }

      if($index < count($this->avail_service_levels)) {

         $sl_idx = $this->avail_service_levels[$index];
         $sl =  $this->service_levels[$sl_idx];

         $this->tmpl->assign('classifier', $this->parent->getOption("classifier"));
         $this->tmpl->assign('sl_idx', $sl_idx);
         $this->tmpl->assign('sl_name', $sl->sl_name);
         $this->tmpl->assign('sl_htb_bw_in_rate', $sl->sl_htb_bw_in_rate);
         $this->tmpl->assign('sl_htb_bw_out_rate', $sl->sl_htb_bw_out_rate);
         $this->tmpl->assign('sl_htb_priority', $this->parent->getPriorityName($sl->sl_htb_priority));
         $this->tmpl->assign('sl_hfsc_in_dmax', $sl->sl_hfsc_in_dmax);
         $this->tmpl->assign('sl_hfsc_in_rate', $sl->sl_hfsc_in_rate);
         $this->tmpl->assign('sl_hfsc_out_dmax', $sl->sl_hfsc_out_dmax);
         $this->tmpl->assign('sl_hfsc_out_rate', $sl->sl_hfsc_out_rate);
         $this->tmpl->assign('sl_cbq_in_rate', $sl->sl_cbq_in_rate);
         $this->tmpl->assign('sl_cbq_out_rate', $sl->sl_cbq_out_rate);
         $this->tmpl->assign('sl_cbq_in_priority', $this->parent->getPriorityName($sl->sl_cbq_in_priority));
         $this->tmpl->assign('sl_cbq_out_priority', $this->parent->getPriorityName($sl->sl_cbq_out_priority));

         $index++;
         $this->tmpl->assign('smarty.IB.sl_list.index', $index);
         $repeat = true;
      }
      else {
         $repeat =  false;
      }

      return $content;

   } // smarty_sl_list

   public function edit()
   {

            if(!isset($_GET['saveit'])) {

               if(!isset($_GET['classifiermode'])) 
                  $_GET['classifiermode'] = $this->parent->getOption("classifier");

	       if(isset($_GET['new'])) {

		  if(!isset($_GET['qdiscmode']))
		     $_GET['qdiscmode'] = $this->parent->getOption("qdisc");
		  
		  $this->parent->startTable("<img src=\"". ICON_SERVICELEVELS ."\" alt=\"servicelevel icon\" />&nbsp;". _("Create a new Service Level"));

		  $form_url = $this->parent->self ."?mode=". $this->parent->mode ."&amp;screen=". $this->parent->screen ."&amp;classifiermode=". $_GET['classifiermode'] ."&amp;saveit=1&amp;new=1";
		  $onchange_url = $this->parent->self ."?mode=". $this->parent->mode ."&amp;screen=". $this->parent->screen ."&amp;new=". $_GET['new'] ."&amp;qdiscmode=";

		  $current->sl_name = "";

               }
	       else {

		  $current = $this->db->db_fetchSingleRow("SELECT * FROM ". MYSQL_PREFIX ."service_levels WHERE sl_idx='". $_GET['idx'] ."'");

		  if(!isset($_GET['qdiscmode'])) 
		     $_GET['qdiscmode'] = $current->sl_qdisc;

		  $this->parent->startTable("<img src=\"". ICON_SERVICELEVELS ."\" alt=\"servicelevel icon\" />&nbsp;". _("Modify Service Level") ." ". $this->parent->getClassVar($current, 'sl_name'));

		  $form_url = $this->parent->self ."?mode=". $this->parent->mode ."&amp;screen=". $this->parent->screen ."&amp;classifiermode=". $_GET['classifiermode'] ."&amp;saveit=1&amp;idx=". $_GET['idx'] ."&amp;namebefore=". urlencode($this->parent->getClassVar($current, 'sl_name'));

		  $onchange_url = $this->parent->self ."?mode=". $this->parent->mode ."&amp;screen=". $this->parent->screen ."&amp;idx=". $_GET['idx'] ."&amp;qdiscmode=";

               }
?>
  <form action="<?php print $form_url; ?>" method="post" name="sl">
   <table style="width: 100%;" class="withborder2">
    <tr>
     <td colspan="3">
      <img src="<?php print ICON_SERVICELEVELS; ?>" alt="servicelevel icon" />&nbsp;<? print _("General"); ?>
     </td>
    </tr>
    <tr>
     <td><?php print _("Name:"); ?></td>
     <td><input type="text" name="sl_name" size="30" value="<?php print $this->parent->getClassVar($current, 'sl_name'); ?>" /></td>
     <td><?php print _("Name of the service level"); ?>.</td>
    </tr>
    <tr>
     <td>
      <?php print _("Classifier:"); ?>
     </td>
     <td>
      <select name="classifier" onchange="location.href='<?php print $onchange_url ."&amp;classifiermode="; ?>'+(document.sl.classifier.options[document.sl.classifier.selectedIndex].value)+'&qdiscmode='+(document.sl.sl_qdisc.options[document.sl.sl_qdisc.selectedIndex].value);">
       <option value="HTB"  <?php if($_GET['classifiermode'] == "HTB") print "selected=\"selected\""; ?>>HTB</option>
       <option value="HFSC" <?php if($_GET['classifiermode'] == "HFSC") print "selected=\"selected\""; ?>>HFSC</option>
       <option value="CBQ"  <?php if($_GET['classifiermode'] == "CBQ") print "selected=\"selected\""; ?>>CBQ</option>
      </select>
     </td>
     <td>
      <?php print _("Save your service level settings first before you change the classifier."); ?>
     </td>
    </tr>
<?php

               switch($_GET['classifiermode']) {

                  default:
                  case 'HTB':
?>
    <tr>
     <td colspan="3">
      <img src="<?php print ICON_SERVICELEVELS; ?>" alt="servicelevel icon" />&nbsp;Interface 1 -&gt; Interface 2
     </td>
    </tr>
    <tr>
     <td style="white-space: nowrap;"><?php print _("Bandwidth:"); ?></td>
     <td style="white-space: nowrap;"><input type="text" name="sl_htb_bw_in_rate" size="25" value="<?php print $this->parent->getClassVar($current, 'sl_htb_bw_in_rate'); ?>" />&nbsp;kbit/s</td>
     <td><?php print _("Bandwidth rate. This is the guaranteed bandwidth."); ?></td>
    </tr>
    <tr>
     <td style="white-space: nowrap;"><?php print _("Bandwidth ceil:"); ?></td>
     <td style="white-space: nowrap;"><input type="text" name="sl_htb_bw_in_ceil" size="25" value="<?php print $this->parent->getClassVar($current, 'sl_htb_bw_in_ceil'); ?>" />&nbsp;kbit/s</td>
     <td><?php print _("If the chain has bandwidth to spare, this is the maximum rate which can be lend to this service. The default value is the bandwidth rate which implies no borrowing from the chain."); ?></td>
    </tr>
    <tr>
     <td style="white-space: nowrap;"><?php print _("Bandwidth burst:"); ?></td>
     <td style="white-space: nowrap;"><input type="text" name="sl_htb_bw_in_burst" size="25" value="<?php print $this->parent->getClassVar($current, 'sl_htb_bw_in_burst'); ?>" />&nbsp;kbit/s</td>
     <td><?php print _("Amount of kbit/s that can be burst at ceil speed, in excess of the configured rate. Should be at least as high as the highest burst of all children. This is useful for interactive traffic."); ?></td>
    </tr>
    <tr>
     <td colspan="3">
      <img src="<?php print ICON_SERVICELEVELS; ?>" alt="servicelevel icon" />&nbsp;Interface 2 -&gt; Interface 1
     </td>
    </tr>
    <tr>
     <td style="white-space: nowrap;"><?php print _("Bandwidth:"); ?></td>
     <td style="white-space: nowrap;"><input type="text" name="sl_htb_bw_out_rate" size="25" value="<?php print $this->parent->getClassVar($current, 'sl_htb_bw_out_rate'); ?>" />&nbsp;kbit/s</td>
     <td><?php print _("Bandwidth rate. This is the guaranteed bandwidth."); ?></td>
    </tr>
    <tr>
     <td style="white-space: nowrap;"><?php print _("Bandwidth ceil:"); ?></td>
     <td style="white-space: nowrap;"><input type="text" name="sl_htb_bw_out_ceil" size="25" value="<?php print $this->parent->getClassVar($current, 'sl_htb_bw_out_ceil'); ?>" />&nbsp;kbit/s</td>
     <td><?php print _("If the chain has bandwidth to spare, this is the maximum rate which can be lend to this service. The default value is the bandwidth rate which implies no borrowing from the chain."); ?></td>
    </tr>
    <tr>
     <td style="white-space: nowrap;"><?php print _("Bandwidth burst:"); ?></td>
     <td style="white-space: nowrap;"><input type="text" name="sl_htb_bw_out_burst" size="25" value="<?php print $this->parent->getClassVar($current, 'sl_htb_bw_out_burst'); ?>" />&nbsp;kbit/s</td>
     <td><?php print _("Amount of kbit/s that can be burst at ceil speed, in excess of the configured rate. Should be at least as high as the highest burst of all children. This is useful for interactive traffic."); ?></td>
    </tr>
    <tr>
     <td colspan="3">
      <img src="<?php print ICON_SERVICELEVELS; ?>" alt="servicelevel icon" />&nbsp;<? print _("Parameters"); ?>
     </td>
    </tr>
    <tr>
     <td><?php print _("Priority:"); ?></td>
     <td>
      <select name="sl_htb_priority">
       <option value="1" <?php if($this->parent->getClassVar($current, 'sl_htb_priority') == 1) print "selected=\"selected\"";?>>Highest (1)</option>
       <option value="2" <?php if($this->parent->getClassVar($current, 'sl_htb_priority') == 2) print "selected=\"selected\"";?>>High (2)</option>
       <option value="3" <?php if($this->parent->getClassVar($current, 'sl_htb_priority') == 3) print "selected=\"selected\"";?>>Normal (3)</option>
       <option value="4" <?php if($this->parent->getClassVar($current, 'sl_htb_priority') == 4) print "selected=\"selected\"";?>>Low (4)</option>
       <option value="5" <?php if($this->parent->getClassVar($current, 'sl_htb_priority') == 5) print "selected=\"selected\"";?>>Lowest (5)</option>
       <option value="0" <?php if($this->parent->getClassVar($current, 'sl_htb_priority') == 0) print "selected=\"selected\"";?>>Ignore</option>
      </select>
     </td>
     <td><?php print _("The service levels with a higher priority are favoured by the scheduler. Also pipes with service levels with a higher priority can lean more unused bandwidth from their chains. If priority is specified without in- or outbound rate, the maximum interface bandwidth can be used."); ?></td>
    </tr>
<?php
                     break;

                  case 'HFSC':

?>
    <tr>
     <td colspan="3">
      <img src="<?php print ICON_SERVICELEVELS; ?>" alt="servicelevel icon" />&nbsp;Interface 1 -&gt; Interface 2
     </td>
    </tr>
    <tr>
     <td style="white-space: nowrap;"><?php print _("Work-Unit:"); ?></td>
     <td style="white-space: nowrap;"><input type="text" name="sl_hfsc_in_umax" size="25" value="<?php print $this->parent->getClassVar($current, 'sl_hfsc_in_umax'); ?>" />&nbsp;bytes</td>
     <td><?php print _("Maximum unit of work. A value around your MTU (ex. 1500) is a good value."); ?></td>
    </tr>
    <tr>
     <td style="white-space: nowrap;"><?php print _("Max-Delay:"); ?></td>
     <td style="white-space: nowrap;"><input type="text" name="sl_hfsc_in_dmax" size="25" value="<?php print $this->parent->getClassVar($current, 'sl_hfsc_in_dmax'); ?>" />&nbsp;ms</td>
     <td><?php print _("Maximum delay of a packet within this Qdisc in milliseconds (ms)"); ?></td>
    </tr>
    <tr>
     <td style="white-space: nowrap;"><?php print _("Rate:"); ?></td>
     <td style="white-space: nowrap;"><input type="text" name="sl_hfsc_in_rate" size="25" value="<?php print $this->parent->getClassVar($current, 'sl_hfsc_in_rate'); ?>" />&nbsp;kbit/s</td>
     <td><?php print _("Guaranteed rate of bandwidth in kbit/s"); ?></td>
    </tr>
    <tr>
     <td style="white-space: nowrap;"><?php print _("ul-Rate:"); ?></td>
     <td style="white-space: nowrap;"><input type="text" name="sl_hfsc_in_ulrate" size="25" value="<?php print $this->parent->getClassVar($current, 'sl_hfsc_in_ulrate'); ?>" />&nbsp;kbit/s</td>
     <td><?php print _("Maximum rate of bandwidth in kbit/s"); ?></td>
    </tr>
    <tr>
     <td colspan="3">
      <img src="<?php print ICON_SERVICELEVELS; ?>" alt="servicelevel icon" />&nbsp;Interface 2 -&gt; Interface 1
     </td>
    </tr>
    <tr>
     <td style="white-space: nowrap;"><?php print _("Work-Unit:"); ?></td>
     <td style="white-space: nowrap;"><input type="text" name="sl_hfsc_out_umax" size="25" value="<?php print $this->parent->getClassVar($current, 'sl_hfsc_out_umax'); ?>" />&nbsp;bytes</td>
     <td><?php print _("Maximum unit of work. A value around your MTU (ex. 1500) is a good value."); ?></td>
    </tr>
    <tr>
     <td style="white-space: nowrap;"><?php print _("Max-Delay:"); ?></td>
     <td style="white-space: nowrap;"><input type="text" name="sl_hfsc_out_dmax" size="25" value="<?php print $this->parent->getClassVar($current, 'sl_hfsc_out_dmax'); ?>" />&nbsp;ms</td>
     <td><?php print _("Maximum delay of a packet within this Qdisc in milliseconds (ms)"); ?></td>
    </tr>
    <tr>
     <td style="white-space: nowrap;"><?php print _("Rate:"); ?></td>
     <td style="white-space: nowrap;"><input type="text" name="sl_hfsc_out_rate" size="25" value="<?php print $this->parent->getClassVar($current, 'sl_hfsc_out_rate'); ?>" />&nbsp;kbit/s</td>
     <td><?php print _("Guaranteed rate of bandwidth in kbit/s"); ?></td>
    </tr>
    <tr>
     <td style="white-space: nowrap;"><?php print _("ul-Rate:"); ?></td>
     <td style="white-space: nowrap;"><input type="text" name="sl_hfsc_out_ulrate" size="25" value="<?php print $this->parent->getClassVar($current, 'sl_hfsc_out_ulrate'); ?>" />&nbsp;kbit/s</td>
     <td><?php print _("Maximum rate of bandwidth in kbit/s"); ?></td>
    </tr>
<?php
                     break;

		  case 'CBQ':

?>
    <tr>
     <td><?php print _("Bounded:"); ?></td>
     <td>
      <input type="radio" name="sl_cbq_bounded" value="Y" <?php if($this->parent->getClassVar($current, 'sl_cbq_bounded') == "Y") print "checked=\"checked\""; ?> /><? print _("Yes"); ?>
      <input type="radio" name="sl_cbq_bounded" value="N" <?php if($this->parent->getClassVar($current, 'sl_cbq_bounded') != "Y") print "checked=\"checked\""; ?> /><? print _("No"); ?>
     </td>
     <td>
      <?php print _("If the CBQ class is bounded, it will not borrow unused bandwidth from it parent classes. If disabled the maximum rates are probably not enforced."); ?>
     </td>
    </tr> 
    <tr>
     <td colspan="3">
      <img src="<?php print ICON_SERVICELEVELS; ?>" alt="servicelevel icon" />&nbsp;Interface 1 -&gt; Interface 2
     </td>
    </tr>
    <tr>
     <td style="white-space: nowrap;"><?php print _("Bandwidth:"); ?></td>
     <td style="white-space: nowrap;"><input type="text" name="sl_cbq_in_rate" size="25" value="<?php print $this->parent->getClassVar($current, 'sl_cbq_in_rate'); ?>" />&nbsp;kbit/s</td>
     <td><?php print _("Maximum rate a chain or pipe can send at."); ?></td>
    </tr>
    <tr>
     <td style="white-space: nowrap;"><?php print _("Priority:"); ?></td>
     <td style="white-space: nowrap;">
      <select name="sl_cbq_in_priority">
       <option value="1" <?php if($this->parent->getClassVar($current, 'sl_cbq_in_priority') == 1) print "selected=\"selected\""; ?>><? print _("Highest (1)"); ?></option>
       <option value="2" <?php if($this->parent->getClassVar($current, 'sl_cbq_in_priority') == 2) print "selected=\"selected\""; ?>><? print _("High (2)"); ?></option>
       <option value="3" <?php if($this->parent->getClassVar($current, 'sl_cbq_in_priority') == 3) print "selected=\"selected\""; ?>><? print _("Normal (3)"); ?></option>
       <option value="4" <?php if($this->parent->getClassVar($current, 'sl_cbq_in_priority') == 4) print "selected=\"selected\""; ?>><? print _("Low (4)"); ?></option>
       <option value="5" <?php if($this->parent->getClassVar($current, 'sl_cbq_in_priority') == 5) print "selected=\"selected\""; ?>><? print _("Lowest (5)"); ?></option>
      </select>
     </td>
     <td><?php print _("In the round-robin process, classes with the lowest priority field are tried for packets first."); ?></td>
    </tr>
    <tr>
     <td colspan="3">
      <img src="<?php print ICON_SERVICELEVELS; ?>" alt="servicelevel icon" />&nbsp;Interface 2 -&gt; Interface 1
     </td>
    </tr>
    <tr>
     <td style="white-space: nowrap;"><?php print _("Bandwidth:"); ?></td>
     <td style="white-space: nowrap;"><input type="text" name="sl_cbq_out_rate" size="25" value="<?php print $this->parent->getClassVar($current, 'sl_cbq_out_rate'); ?>" />&nbsp;kbit/s</td>
     <td><?php print _("Maximum rate a chain or pipe can send at."); ?></td>
    </tr>
    <tr>
     <td style="white-space: nowrap;"><?php print _("Priority:"); ?></td>
     <td style="white-space: nowrap;">
      <select name="sl_cbq_out_priority">
       <option value="1" <?php if($this->parent->getClassVar($current, 'sl_cbq_out_priority') == 1) print "selected=\"selected\""; ?>><? print _("Highest (1)"); ?></option>
       <option value="2" <?php if($this->parent->getClassVar($current, 'sl_cbq_out_priority') == 2) print "selected=\"selected\""; ?>><? print _("High (2)"); ?></option>
       <option value="3" <?php if($this->parent->getClassVar($current, 'sl_cbq_out_priority') == 3) print "selected=\"selected\""; ?>><? print _("Normal (3)"); ?></option>
       <option value="4" <?php if($this->parent->getClassVar($current, 'sl_cbq_out_priority') == 4) print "selected=\"selected\""; ?>><? print _("Low (4)"); ?></option>
       <option value="5" <?php if($this->parent->getClassVar($current, 'sl_cbq_out_priority') == 5) print "selected=\"selected\""; ?>><? print _("Lowest (5)"); ?></option>
      </select>
     </td>
     <td><?php print _("In the round-robin process, classes with the lowest priority field are tried for packets first."); ?></td>
    </tr>
<?php
                  break;

	    }
?>
    <tr>
     <td colspan="3">
      <img src="<?php print ICON_SERVICELEVELS; ?>" alt="servicelevel icon" />&nbsp;<? print _("Queuing Discipline"); ?>
     </td>
    </tr>
    <tr>
     <td>
      <?php print _("Queuing Discipline:"); ?>
     </td>
     <td>
      <select name="sl_qdisc"  onchange="location.href='<?php print $onchange_url ."&amp;qdiscmode="; ?>'+(document.sl.sl_qdisc.options[document.sl.sl_qdisc.selectedIndex].value)+'&classifiermode='+(document.sl.classifier.options[document.sl.classifier.selectedIndex].value);">
       <option value="SFQ" <?php if($_GET['qdiscmode'] == "SFQ") print "selected=\"selected\""; ?>>SFQ</option>
       <option value="ESFQ" <?php if($_GET['qdiscmode'] == "ESFQ") print "selected=\"selected\""; ?>>ESFQ</option>
       <option value="HFSC" <?php if($_GET['qdiscmode'] == "HFSC") print "selected=\"selected\""; ?>>HFSC</option>
       <option value="NETEM" <?php if($_GET['qdiscmode'] == "NETEM") print "selected=\"selected\""; ?>>NETEM</option>
      </select>
     </td>
     <td>
      <?php print _("Select the to be used Queuing Discipline."); ?>
     </td>
    </tr>
<?php

               switch($_GET['qdiscmode']) {

	          case 'SFQ':
		  case 'HFSC':
		     break;

		  case 'ESFQ':
?>
    <tr>
     <td>
      <?php print _("Perturb:"); ?>
     </td>
     <td>
      <input type="text" name="sl_esfq_perturb" size="25" value="<?php print $this->parent->getClassVar($current, 'sl_esfq_perturb'); ?>" />
     </td>
     <td>
      <?php print _("Causes the flows to be redistributed so there are no collosions on sharing a queue. Default is 0. Recommeded 10."); ?>
     </td> 
    </tr>
    <tr>
     <td>
      <?php print _("Limit:"); ?>
     </td>
     <td>
      <input type="text" name="sl_esfq_limit" size="25" value="<?php print $this->parent->getClassVar($current, 'sl_esfq_limit'); ?>" />
     </td>
     <td>
      <?php print _("The total number of packets that will be queued by this ESFQ before packets start getting dropped.  Limit must be less than or equal to depth. Default is 128."); ?>
     </td>
    </tr>
    <tr>
     <td>
      <?php print _("Depth:"); ?>
     </td>
     <td>
      <input type="text" name="sl_esfq_depth" size="25" value="<?php print $this->parent->getClassVar($current, 'sl_esfq_depth'); ?>" />
     </td>
     <td>
      <?php print _("No description available. Set like Limit."); ?>
     </td>
    </tr>
    <tr>
     <td>
      <?php print _("Divisor:"); ?>
     </td>
     <td>
      <input type="text" name="sl_esfq_divisor" size="25" value="<?php print $this->parent->getClassVar($current, 'sl_esfq_divisor'); ?>" />
     </td>
     <td>
      <?php print _("Divisor sets the number of bits to use for the hash table. A larger hash table decreases the likelihood of collisions but will consume more memory."); ?>
     </td>
    </tr>
    <tr>
     <td>
      Hash:
     </td>
     <td>
      <select name="sl_esfq_hash">
       <option value="classic" <?php if($this->parent->getClassVar($current, 'sl_esfq_hash') == "classic") print "selected=\"selected\""; ?>>Classic</option>
       <option value="src" <?php if($this->parent->getClassVar($current, 'sl_esfq_hash') == "src") print "selected=\"selected\""; ?>>Src</option>
       <option value="dst" <?php if($this->parent->getClassVar($current, 'sl_esfq_hash') == "dst") print "selected=\"selected\""; ?>>Dst</option>
       <option value="fwmark" <?php if($this->parent->getClassVar($current, 'sl_esfq_hash') == "fwmark") print "selected=\"selected\""; ?>>Fwmark</option>
       <option value="src_direct" <?php if($this->parent->getClassVar($current, 'sl_esfq_hash') == "src_direct") print "selected=\"selected\""; ?>>Src_direct</option>
       <option value="dst_direct" <?php if($this->parent->getClassVar($current, 'sl_esfq_hash') == "dst_direct") print "selected=\"selected\""; ?>>Dst_direct</option>
       <option value="fwmark_direct" <?php if($this->parent->getClassVar($current, 'sl_esfq_hash') == "fwmark_direct") print "selected=\"selected\""; ?>>Fwmark_direct</option>
      </select>
     </td>
     <td>
      <?php print _("Howto seperate traffic into queues. Classisc equals to SFQ handling. Src and Dst per direction. Fwmark uses the connection mark which can be set by iptables. If less then 16384 (2^14) simultaneous connections occurs use one of the _direct sibling which uses an fast algorithm."); ?>
     </td>
    </tr>
<?php
		     break;

	       case 'NETEM':

?>
    <tr>
     <td colspan="3">
      <img src="<?php print ICON_SERVICELEVELS; ?>" alt="servicelevel icon" />&nbsp;Network delays
     </td>
    </tr>
    <tr>
     <td style="white-space: nowrap;">Delay:</td>
     <td style="white-space: nowrap;"><input type="text" name="sl_netem_delay" size="25" value="<?php print $this->parent->getClassVar($current, 'sl_netem_delay'); ?>" />&nbsp;ms</td>
     <td><?php print _("Fixed amount of delay to all packets."); ?></td>
    </tr>
    <tr>
     <td style="white-space: nowrap;">Jitter:</td>
     <td style="white-space: nowrap;"><input type="text" name="sl_netem_jitter" size="25" value="<?php print $this->parent->getClassVar($current, 'sl_netem_jitter'); ?>" />&nbsp;ms</td>
     <td><?php print _("Random variation around the delay value (= delay &#177; Jitter)."); ?>
    </tr>
    <tr>
     <td style="white-space: nowrap;">Correlation:</td>
     <td style="white-space: nowrap;"><input type="text" name="sl_netem_random" size="25" value="<?php print $this->parent->getClassVar($current, 'sl_netem_random'); ?>" />&nbsp;&#37;</td>
     <td><?php print _("Limits the randomness to simulate a real network. So the next packets delay will be within % of the delay of the packet before."); ?></td>
    </tr>
    <tr>
     <td style="white-space: nowrap;">Distribution:</td>
     <td style="white-space: nowrap;">
      <select name="sl_netem_distribution">
       <option value="ignore" <?php if($this->parent->getClassVar($current, 'sl_netem_distribution') == "ignore") print "selected=\"selected\""; ?>>Ignore</option>
       <option value="normal" <?php if($this->parent->getClassVar($current, 'sl_netem_distribution') == "normal") print "selected=\"selected\""; ?>>normal</option>
       <option value="pareto" <?php if($this->parent->getClassVar($current, 'sl_netem_distribution') == "pareto") print "selected=\"selected\""; ?>>pareto</option>
       <option value="paretonormal" <?php if($this->parent->getClassVar($current, 'sl_netem_distribution') == "paretonormal") print "selected=\"selected\""; ?>>paretonormal</option>
      </select>
     </td>
     <td><?php print _("How the delays are distributed over a longer delay periode."); ?></td>
    </tr>
    <tr>
     <td colspan="3">
      <img src="<?php print ICON_SERVICELEVELS; ?>" alt="servicelevel icon" />&nbsp;Others functions
     </td>
    </tr>
    <tr>
     <td style="white-space: nowrap;">Packetloss:</td>
     <td style="white-space: nowrap;"><input type="text" name="sl_netem_loss" size="25" value="<?php print $this->parent->getClassVar($current, 'sl_netem_loss'); ?>" />&nbsp;&#37;</td>
     <td><?php print _("Packetloss in percent. Smallest value is .0000000232% ( = 1 / 2^32)."); ?>
    </tr>
    <tr>
     <td style="white-space: nowrap;">Duplication:</td>
     <td style="white-space: nowrap;"><input type="text" name="sl_netem_duplication" size="25" value="<?php print $this->parent->getClassVar($current, 'sl_netem_duplication'); ?>" />&nbsp;&#37;</td>
     <td><?php print _("Duplication in percent. Smallest value is .0000000232% ( = 1 / 2^32)."); ?>
    </tr>
    <tr>
     <td colspan="3">
      <img src="<?php print ICON_SERVICELEVELS; ?>" alt="servicelevel icon" />&nbsp;Re-Ordering
     </td>
    </tr>
    <tr>
     <td style="white-space: nowrap;">Gap:</td>
     <td style="white-space: nowrap;"><input type="text" name="sl_netem_gap" size="25" value="<?php print $this->parent->getClassVar($current, 'sl_netem_gap'); ?>" /></td>
     <td><?php print _("Packet re-ordering causes 1 out of N packets to be delayed. For a value of 5 every 5th (10th, 15th, ...) packet will get delayed by 10ms and the others will pass straight out."); ?></td>
    </tr>
    <tr>
    <tr>
     <td style="white-space: nowrap;">Reorder percentage:</td>
     <td style="white-space: nowrap;"><input type="text" name="sl_netem_reorder_percentage" size="25" value="<?php print $this->parent->getClassVar($current, 'sl_netem_reorder_percentage'); ?>" />&nbsp;&#37;</td>
     <td><?php print _("Percentage of packets the get reordered."); ?></td>
    </tr>
    <tr>
     <td style="white-space: nowrap;">Reorder correlation:</td>
     <td style="white-space: nowrap;"><input type="text" name="sl_netem_reorder_correlation" size="25" value="<?php print $this->parent->getClassVar($current, 'sl_netem_reorder_correlation'); ?>" />&nbsp;&#37;</td>
     <td><?php print _("Percentage of packets the are correlate each others."); ?></td>
    </tr>
<?php
                     break;
               }
?>
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

	       $is_numeric = 1;
	       $error = 0;

               if(!isset($_POST['sl_name']) || $_POST['sl_name'] == "") {

		  $this->parent->printError("<img src=\"". ICON_SERVICELEVELS ."\" alt=\"servicelevel icon\" />&nbsp;". _("Service Level"), _("Please enter a service level name!"));
		  $error = 1;

               }

	       if(!$error && isset($_GET['new']) && $this->db->db_fetchSingleRow("SELECT sl_idx FROM ". MYSQL_PREFIX ."service_levels WHERE sl_name LIKE BINARY '". $_POST['sl_name'] ."'")) {

		  $this->parent->printError("<img src=\"". ICON_PORTS ."\" alt=\"port icon\" />&nbsp;". _("Manage Port"), _("A service level with that name already exists!"));
		  $error = 1;

	       }

	       if(!$error && !isset($_GET['new']) && $_GET['namebefore'] != $_POST['sl_name'] && $this->db->db_fetchSingleRow("SELECT sl_idx FROM ". MYSQL_PREFIX ."service_levels WHERE sl_name LIKE BINARY '". $_POST['sl_name'] ."'")) {

		  $this->parent->printError("<img src=\"". ICON_PORTS ."\" alt=\"port icon\" />&nbsp;". _("Manage Port"), _("A service level with that name already exists!"));
		  $error = 1;

	       }

	       if(!$error) {

                  switch($_GET['classifiermode']) {

                     case 'HTB':

                        if($_POST['sl_htb_priority'] == 0 && $_POST['sl_htb_bw_in_rate'] == "" && $_POST['sl_htb_bw_out_rate'] == "") {

                           $this->parent->printError("<img src=\"". ICON_SERVICELEVELS ."\" alt=\"servicelevel icon\" />&nbsp;". _("Service Level"), _("A service level which ignores priority AND not specified inbound or outbound rate is not possible!"));
			   return 0;

                        }
                        else {
								
                           if($_POST['sl_htb_bw_in_rate'] != "" && !is_numeric($_POST['sl_htb_bw_in_rate']))
                              $is_numeric = 0;
		 	
                           if($_POST['sl_htb_bw_out_rate'] != "" && !is_numeric($_POST['sl_htb_bw_out_rate']))
                              $is_numeric = 0;
										
                           if($_POST['sl_htb_bw_in_ceil'] != "" && !is_numeric($_POST['sl_htb_bw_in_ceil']))
                              $is_numeric = 0;
			
                           if($_POST['sl_htb_bw_in_burst'] != "" && !is_numeric($_POST['sl_htb_bw_in_burst']))
                              $is_numeric = 0;
			
                           if($_POST['sl_htb_bw_out_ceil'] != "" && !is_numeric($_POST['sl_htb_bw_out_ceil']))
                              $is_numeric = 0;

                           if($_POST['sl_htb_bw_out_burst'] != "" && !is_numeric($_POST['sl_htb_bw_out_burst']))
                              $is_numeric = 0;
		
                        }
                        break;

                     case 'HFSC':
								
                        /* If umax is specifed, also umax is necessary */
			if(($_POST['sl_hfsc_in_umax'] != "" && $_POST['sl_hfsc_in_dmax'] == "") ||
			   ($_POST['sl_hfsc_out_umax'] != "" && $_POST['sl_hfsc_out_dmax'] == "")) {

			   $this->parent->printError("<img src=\"". ICON_SERVICELEVELS ."\" alt=\"servicelevel icon\" />&nbsp;". _("Service Level"), _("Please enter a \"Max-Delay\" value if you have defined a \"Work-Unit\" value!"));
			   return 0;

                        }
			else {

			   if($_POST['sl_hfsc_in_umax'] != "" && !is_numeric($_POST['sl_hfsc_in_umax']))
			      $is_numeric = 0;

			   if($_POST['sl_hfsc_in_dmax'] != "" && !is_numeric($_POST['sl_hfsc_in_dmax']))
			      $is_numeric = 0;

			   if($_POST['sl_hfsc_in_rate'] != "" && !is_numeric($_POST['sl_hfsc_in_rate']))
			      $is_numeric = 0;

			   if($_POST['sl_hfsc_in_ulrate'] != "" && !is_numeric($_POST['sl_hfsc_in_ulrate']))
			      $is_numeric = 0;

			   if($_POST['sl_hfsc_out_umax'] != "" && !is_numeric($_POST['sl_hfsc_out_umax']))
			      $is_numeric = 0;

			   if($_POST['sl_hfsc_out_dmax'] != "" && !is_numeric($_POST['sl_hfsc_out_dmax']))
			      $is_numeric = 0;

			   if($_POST['sl_hfsc_out_rate'] != "" && !is_numeric($_POST['sl_hfsc_out_rate']))
			      $is_numeric = 0;

			   if($_POST['sl_hfsc_out_ulrate'] != "" && !is_numeric($_POST['sl_hfsc_out_ulrate']))
			      $is_numeric = 0;

                        }
			break;

                     case 'CBQ':

			if($_POST['sl_cbq_in_rate'] == "" || $_POST['sl_cbq_out_rate'] == "") {

			   $this->parent->printError("<img src=\"". ICON_SERVICELEVELS ."\" alt=\"servicelevel icon\" />&nbsp;". _("Service Level"), _("Please enter a input and output rate!"));
			   return 0;

			}
			else {

			   if($_POST['sl_cbq_in_rate'] != "" && !is_numeric($_POST['sl_cbq_in_rate']))
			      $is_numeric = 0;

			   if($_POST['sl_cbq_out_rate'] != "" && !is_numeric($_POST['sl_cbq_out_rate']))
			      $is_numeric = 0;

			}

			break;

                  }
               }

	       if(!$error && !$is_numeric) {

		     $this->parent->printError("<img src=\"". ICON_SERVICELEVELS ."\" alt=\"servicelevel icon\" />&nbsp;". _("Service Level"), _("Please enter only numerical values for bandwidth parameters!"));
		     $error = 1;

	       }

	       if(!$error) {

		  if(isset($_GET['new'])) {

		     $this->db->db_query("INSERT INTO ". MYSQL_PREFIX ."service_levels (sl_name, sl_htb_bw_in_rate,"
			."sl_htb_bw_in_ceil, sl_htb_bw_in_burst, sl_htb_bw_out_rate, "
			."sl_htb_bw_out_ceil, sl_htb_bw_out_burst, sl_htb_priority, "
			."sl_hfsc_in_umax, sl_hfsc_in_dmax, sl_hfsc_in_rate, sl_hfsc_in_ulrate, "
			."sl_hfsc_out_umax, sl_hfsc_out_dmax, sl_hfsc_out_rate, sl_hfsc_out_ulrate, "
			."sl_cbq_in_rate, sl_cbq_in_priority, sl_cbq_out_rate, sl_cbq_out_priority, "
			."sl_cbq_bounded, sl_qdisc, sl_netem_delay, sl_netem_jitter, sl_netem_random, "
			."sl_netem_distribution, sl_netem_loss, sl_netem_duplication, "
			."sl_netem_gap, sl_netem_reorder_percentage, sl_netem_reorder_correlation, "
			."sl_esfq_perturb, sl_esfq_limit, sl_esfq_depth, sl_esfq_divisor, sl_esfq_hash) "
			."VALUES ('". $_POST['sl_name'] ."', "
			."'". $_POST['sl_htb_bw_in_rate'] ."', "
			."'". $_POST['sl_htb_bw_in_ceil'] ."', "
			."'". $_POST['sl_htb_bw_in_burst'] ."', "
			."'". $_POST['sl_htb_bw_out_rate'] ."', "
			."'". $_POST['sl_htb_bw_out_ceil'] ."', "
			."'". $_POST['sl_htb_bw_out_burst'] ."', "
			."'". $_POST['sl_htb_priority'] ."', "
			."'". $_POST['sl_hfsc_in_umax'] ."', "
			."'". $_POST['sl_hfsc_in_dmax'] ."', "
			."'". $_POST['sl_hfsc_in_rate'] ."', "
			."'". $_POST['sl_hfsc_in_ulrate'] ."', "
			."'". $_POST['sl_hfsc_out_umax'] ."', "
			."'". $_POST['sl_hfsc_out_dmax'] ."', "
			."'". $_POST['sl_hfsc_out_rate'] ."', "
			."'". $_POST['sl_hfsc_out_ulrate'] ."', "
			."'". $_POST['sl_cbq_in_rate'] ."', "
			."'". $_POST['sl_cbq_in_priority'] ."', "
			."'". $_POST['sl_cbq_out_rate'] ."', "
			."'". $_POST['sl_cbq_out_priority'] ."', "
			."'". $_POST['sl_cbq_bounded'] ."', "
			."'". $_POST['sl_qdisc'] ."', "
			."'". $_POST['sl_netem_delay'] ."', "
			."'". $_POST['sl_netem_jitter'] ."', "
			."'". $_POST['sl_netem_random'] ."', "
			."'". $_POST['sl_netem_distribution'] ."', "
			."'". $_POST['sl_netem_loss'] ."', "
			."'". $_POST['sl_netem_duplication'] ."', "
			."'". $_POST['sl_netem_gap'] ."', "
			."'". $_POST['sl_netem_reorder_percentage']."', "
			."'". $_POST['sl_netem_reorder_correlation'] ."', "
			."'". $_POST['sl_esfq_perturb'] ."', "
			."'". $_POST['sl_esfq_limit'] ."', "
			."'". $_POST['sl_esfq_depth'] ."', "
			."'". $_POST['sl_esfq_divisor'] ."', "
			."'". $_POST['sl_esfq_hash'] ."')");

		  }
		  else {

		     $this->db->db_query("UPDATE ". MYSQL_PREFIX ."service_levels SET "
			."sl_name='". $_POST['sl_name'] ."', "
			."sl_htb_bw_in_rate='". $_POST['sl_htb_bw_in_rate'] ."', "
			."sl_htb_bw_in_ceil='". $_POST['sl_htb_bw_in_ceil'] ."', "
			."sl_htb_bw_in_burst='". $_POST['sl_htb_bw_in_burst'] ."', "
			."sl_htb_bw_out_rate='". $_POST['sl_htb_bw_out_rate'] ."', "
			."sl_htb_bw_out_ceil='". $_POST['sl_htb_bw_out_ceil'] ."', "
			."sl_htb_bw_out_burst='". $_POST['sl_htb_bw_out_burst'] ."', "
			."sl_htb_priority='". $_POST['sl_htb_priority'] ."', "
			."sl_hfsc_in_umax='". $_POST['sl_hfsc_in_umax'] ."', "
			."sl_hfsc_in_dmax='". $_POST['sl_hfsc_in_dmax'] ."', "
			."sl_hfsc_in_rate='". $_POST['sl_hfsc_in_rate'] ."', "
			."sl_hfsc_in_ulrate='". $_POST['sl_hfsc_in_ulrate'] ."', "
			."sl_hfsc_out_umax='". $_POST['sl_hfsc_out_umax'] ."', "
			."sl_hfsc_out_dmax='". $_POST['sl_hfsc_out_dmax'] ."', "
			."sl_hfsc_out_rate='". $_POST['sl_hfsc_out_rate'] ."', "
			."sl_hfsc_out_ulrate='". $_POST['sl_hfsc_out_ulrate'] ."', "
			."sl_cbq_in_rate='". $_POST['sl_cbq_in_rate'] ."', "
			."sl_cbq_in_priority='". $_POST['sl_cbq_in_priority'] ."', "
			."sl_cbq_out_rate='". $_POST['sl_cbq_out_rate'] ."', "
			."sl_cbq_out_priority='". $_POST['sl_cbq_out_priority'] ."', "
			."sl_cbq_bounded='". $_POST['sl_cbq_bounded'] ."', "
			."sl_qdisc='". $_POST['sl_qdisc'] ."', "
			."sl_netem_delay='". $_POST['sl_netem_delay'] ."', "
			."sl_netem_jitter='". $_POST['sl_netem_jitter'] ."', "
			."sl_netem_random='". $_POST['sl_netem_random'] ."', "
			."sl_netem_distribution='". $_POST['sl_netem_distribution'] ."', "
			."sl_netem_loss='". $_POST['sl_netem_loss'] ."', "
			."sl_netem_duplication='". $_POST['sl_netem_duplication'] ."', "
			."sl_netem_gap='". $_POST['sl_netem_gap'] ."', "
			."sl_netem_reorder_percentage='". $_POST['sl_netem_reorder_percentage']."', "
			."sl_netem_reorder_correlation='". $_POST['sl_netem_reorder_correlation'] ."', "
			."sl_esfq_perturb='". $_POST['sl_esfq_perturb'] ."', "
			."sl_esfq_limit='". $_POST['sl_esfq_limit'] ."', "
			."sl_esfq_depth='". $_POST['sl_esfq_depth'] ."', "
			."sl_esfq_divisor='". $_POST['sl_esfq_divisor'] ."', "
			."sl_esfq_hash='". $_POST['sl_esfq_hash'] ."' "
			."WHERE sl_idx='". $_GET['idx'] ."'");

		  }

		  $this->parent->goBack();

	       }

	    }
   } // edit()

   public function delete()
   {

	    if(!isset($_GET['doit']))

	       $this->parent->printYesNo("<img src=\"". ICON_SERVICELEVELS ."\" alt=\"servicelevel icon\" />&nbsp;". _("Service Level"), _("Really delete Service Level") ." ". $_GET['name'] ."?");

	    else {

	       $this->db->db_query("DELETE FROM ". MYSQL_PREFIX ."service_levels WHERE sl_idx='". $_GET['idx'] ."'");
	       $this->parent->goBack();

	    }

   } // delete()

   // End of class definition
}

?>
