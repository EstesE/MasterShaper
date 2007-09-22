{start_table icon="icons/home.gif" alt="home icon" title="MasterShaper Ruleset Overview" }
<form>

{ov_netpath }

<table style="width: 100%;">
 <tr>
  <td style="height: 15px;" />
 </tr>
 <tr>
  <td>
   &nbsp;
   Network Path { $netpath_name }
   <a href=""><img src="{ $icon_pipes_arrow_down }" alt="Move netpath down" /></a>
   <a href=""><img src="{ $icon_pipes_arrow_up }" alt="Move netpath up" /></a>
  </td>
 </tr>
 <tr>
  <td style="height: 5px;" />
 </tr>
 <tr>
  <td>
   <table style="width: 100%;" class="withborder">
    <tr>
     <td class="colhead" colspan="2" style="width: 20%;">&nbsp;Name</td>
     <td class="colhead" style="text-align: center;">Service Level</td>
     <td class="colhead" style="text-align: center;">Fallback</td>
     <td class="colhead" style="text-align: center;">Source</td>
     <td class="colhead" style="text-align: center;">Direction</td>
     <td class="colhead" style="text-align: center;">Destination</td>
     <td class="colhead" style="text-align: center;">Action</td>
     <td class="colhead" style="text-align: center;">Position</td>
    </tr>

 { ov_chain np_idx=$netpath_idx }

    <tr onmouseover="setBackGrdColor(this, 'mouseover');" onmouseout="setBackGrdColor(this, 'mouseout');">
     <td colspan="2">
      <input type="hidden" name="chains[{ $chain_cnt }]" value="{ $chain_idx }" />
      <img src="{ $icon_chains }" alt="chain icon" />&nbsp;
      <a href="" title="Modify chain { $chain_name }">{ $chain_name }</a>
     </td>
     <td style="text-align: center;">
      <select name="chain_sl_idx[{ $chain_idx }]">
       <option value="0">--- Ignore QoS ---</option>
       { sl_list idx=$chain_idx }
      </select>
     </td> 

    { if $chain_has_sl }
     <td style="text-align: center;">
      <select name="chain_fallback_idx[<?php print $chain->chain_idx; ?>]">
       <option value="0">--- No Fallback ---</option>
       { sl_list idx=$chain_idx }
      </select>
     </td>
    { else }
     <td>&nbsp;</td>
    { /if }

     <td style="text-align: center;">
      <select name="chain_src_target[<?php print $chain->chain_idx; ?>]">
       <option value="0">any</option>
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
           <a href="<?php print $this->parent->self."?mode=". $this->parent->mode ."&amp;screen=". MANAGE_POS_CHAINS ."&amp;chain_idx=". $chain->chain_idx ."&amp;to=0"; ?>"><img src="<? print ICON_CHAINS_ARROW_DOWN; ?>" alt="Move chain down" /></a>            <a href="<?php print $this->parent->self."?mode=". $this->parent->mode ."&amp;screen=". MANAGE_POS_CHAINS ."&amp;chain_idx=". $chain->chain_idx ."&amp;to=1"; ?>"><img src="<? print ICON_CHAINS_ARROW_UP; ?>" alt="Move chain up" /></a>           </td>     </tr> 


/* pipes are only available if the chain DOES NOT ignore QoS or DOES NOT use fallback service level */

         <input type="hidden" name="pipes[<?php print $pipe_counter; ?>]" value="<? print $pipe->pipe_idx; ?>" />
         <tr onmouseover="setBackGrdColor(this, 'mouseover');" onmouseout="setBackGrdColor(this, 'mouseout');">           <td style="text-align: center;">       <?php print $counter; ?>      </td>      <td>            <img src="<?php print ICON_PIPES; ?>" alt="pipes icon" />&nbsp;          <a href="<?php print $this->parent->self ."?mode=2&amp;screen=". MANAGE ."&amp;idx=". $pipe->pipe_idx; ?>" title="Modify pipe <? print $pipe->pipe_name; ?>" onmouseover="staticTip.show('tipPipe<? print $pipe->pipe_idx; ?>');" onmouseout="staticTip.hide();"><? print $pipe->pipe_name; ?></a>           </td>           <td style="text-align: center;">       <select name="pipe_sl_idx[<?php print $pipe->pipe_idx; ?>]"> 

           </select>
     </td>
     <td>&nbsp;</td>
     <td style="text-align: center;">
      <select name="pipe_src_target[<?php print $pipe->pipe_idx; ?>]">
       <option value="0">any</option>
           </select>      </td>
          <td style="text-align: center;">
           <select name="pipe_direction[<?php print $pipe->pipe_idx; ?>]">
            <option value="1" <?php if($pipe->pipe_direction == 1) print "selected=\"selected\""; ?>>--&gt;</option>
            <option value="2" <?php if($pipe->pipe_direction == 2) print "selected=\"selected\""; ?>>&lt;-&gt;</option>
      </select>
     </td>
     <td style="text-align: center;">
      <select name="pipe_dst_target[<?php print $pipe->pipe_idx; ?>]">
       <option value="0">any</option>
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
           <a href="<?php print $this->parent->self ."?mode=". $this->parent->mode ."&amp;screen=". MANAGE_POS_PIPES ."&amp;pipe_idx=". $pipe->pipe_idx ."&amp;to=0"; ?>"><img src="<? print ICON_PIPES_ARROW_DOWN; ?>" alt="Move pipe down" /></a>            <a href="<?php print $this->parent->self ."?mode=". $this->parent->mode ."&amp;screen=". MANAGE_POS_PIPES ."&amp;pipe_idx=". $pipe->pipe_idx ."&amp;to=1"; ?>"><img src="<? print ICON_PIPES_ARROW_UP; ?>" alt="Move pipe up" /></a>           </td>          </tr> 

         <tr onmouseover="setBackGrdColor(this, 'mouseover');" onmouseout="setBackGrdColor(this, 'mouseout');">      <td />           <td colspan="7">       <img src="images/tree_end.gif" alt="tree" />          <img src="<?php print ICON_FILTERS; ?>" alt="filter icon" />&nbsp;            <a href="<?php print $this->parent->self ."?mode=8&amp;screen=". MANAGE ."&amp;idx=". $filter->filter_idx; ?>" title="Modify filter <? print $filter->filter_name; ?>"><? print $filter->filter_name; ?></a>           </td>           <td>            &nbsp;           </td>          </tr> 


 {/ov_chain}
{/ov_netpath}
        </table>
       </td>
      </tr>
      <tr>
      </tr>
     </table>
         $this->parent->showSaveButton();

</form>


