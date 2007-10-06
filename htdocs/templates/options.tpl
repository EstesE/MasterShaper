<form action="saveOptions()">
 <table style="width: 100%;" class="withborder2">
  <tr>
   <td colspan="3">
    <img src="{ $icon_options }" />&nbsp;MasterShaper Interface Options
   </td>
  </tr>
  <tr>
   <td style="white-space: nowrap;">Language</td>
   <td>
    <select name="language">
     <option value="en_GB.UTF8" { if $language == "en_GB.UTF8" } selected="selected" { /if }>English</option>
     <option value="de_DE.UTF8" { if $language == "de_DE.UTF8" } selected="selected" { /if }>German</option>
    </select>
   </td>
   <td>Select the MasterShaper GUI language.</td>
  </tr>
  <tr>
   <td colspan="3">
    <img src="{ $icon_options }" alt="option icon" />&nbsp;MasterShaper QoS Options
   </td>
  </tr>
  <tr>
   <td style="white-space: nowrap;">ACK packets:</td>
   <td style="white-space: nowrap;">
    <select name="ack_sl">
     <option value="0">Ignore</option>
     { service_level_list }
     <option value="{ $sl_idx }" { if $ack_sl == $sl_idx } selected="selected" { /if }>{ $sl_name }</option>
     { /service_level_list }
    </select>
   </td>
   <td>
    Should ACK- and other small packets (&lt;128byte) get a special service level? This is helpfull if you have a small upload bandwidth. There is no much needing for a high bandwidth for this (ex. 32kbit/s), but it should have a higher priority then other bulk traffic.<br />Be aware, that this may bypass some packets from later rules because smaller packets get matched here - so the traffic limits may not be strictly enforced.
   </td>
  </tr>
  <tr>
   <td style="white-space: nowrap;">
    Classifier:
   </td>
   <td>
    <select name="classifier">
     <option value="HTB" { if $classifier == "HTB" } selected="selected" { /if }>HTB</option>
     <option value="HFSC" { if $classifier == "HFSC" } selected="selected" { /if }>HFSC</option>
     <option value="CBQ" { if $classifier == "CBQ" } selected="selected" { /if }>CBQ</option>
   </td>
   <td>
    Choose HTB if you want to shape on base of maximum bandwidth rates, traffic bursts. Use HFSC for realtime application where network packets should not be delayed more such a specified value (VoIP). CBQ is the predecessor of HTB. Maybe on some systems you have only CBQ support.
   </td>
  </tr>
  <tr>
   <td style="white-space: nowrap;">
    Default Queuing Discipline:
   </td>
   <td>
   <select name="qdisc">
     <option value="SFQ" { if $qdisc == "SFQ" } selected="selected" { /if }>SFQ</option>
     <option value="ESFQ" { if $qdisc == "ESFQ" } selected="selected" { /if }>ESFQ</option>
     <option value="HFSC" { if $qdisc == "HFSC" } selected="selected" { /if }>HFSC</option>
    </select>
   </td>
   <td>
    This specifies the default qdisc for pipes. It's generally not a good idea to mix between different qdiscs. However, MasterShaper supports to specify different qdiscs for pipes.");
   </td>
  </tr>
  { if $qdisc == "ESFQ" }
  <tr>
   <td>
    _("ESFQ Perturb:
   </td>
   <td>
    <input type="text" name="esfq_default_perturb" value="{ $esfq_default_perturb }" size="28" />
   </td>
   <td>
    Default ESFQ perturb value. See Service Level for more informations.
   </td>
  </tr>
  <tr>
   <td>
     ESFQ Limit:
   </td>
   <td>
    <input type="text" name="esfq_default_limit" value="{ $esfq_default_limit }" size="28" />
    </td>
    <td>
    Default ESFQ limit value. See Service Level for more informations.
   </td>
  </tr>
  <tr>
   <td>
    ESFQ Depth:
   </td>
   <td>
    <input type="text" name="esfq_default_depth" value="{ $esfq_default_depth }" size="28" />
   </td>
   <td>
    Default ESFQ depth value. See Service Level for more informations.
   </td>
  </tr>
  <tr>
   <td>
    ESFQ Divisor:
   </td>
   <td>
    <input type="text" name="esfq_default_divisor" value="{ $esfq_default_divisor }" size="28" />
   </td>
   <td>
    Default ESFQ divisor value. See Service Level fore more informations.
   </td>
  </tr>
  <tr>
   <td>
    ESFQ Hash:
   </td>
   <td>
    <select name="esfq_default_hash">
     <option value="classic" { if $esfq_default_hash == "classic"} selected="selected" { /if }>Classic</option>
     <option value="src" { if $esfq_default_hash == "src"} selected="selected" { /if }>Src</option>
     <option value="dst" { if $esfq_default_hash == "dst"} selected="selected" { /if }>Dst</option>
     <option value="fwmark" { if $esfq_default_hash == "fwmark"} selected="selected" { /if }>Fwmark</option>
    <option value="src_direct" { if $esfq_default_hash == "src_direct"} selected="selected" { /if }>Src_direct</option>
     <option value="dst_direct" { if $esfq_default_hash == "dst_direct"} selected="selected" { /if }>Dst_direct</option>
     <option value="fwmark_direct" { if $esfq_default_hash == "fwmark_direct"} selected="selected" { /if }>Fwmark_direct</option>
    </select>
   </td>
   <td>
    Default ESFQ hash. See Service Level fore more informations.
   </td>
  </tr>
  { /if }
  <tr>
   <td colspan="3">
    <img src="{ $icon_options }" />&nbsp;MasterShaper Options
   </td>
  </tr>
  <tr>
   <td style="white-space: nowrap;">
    Traffic filter:
   </td>
   <td>
    <input type="radio" name="filter" value="tc" { if $filter == "tc"} checked="checked" { /if } />tc-filter
    <input type="radio" name="filter" value="ipt" { if $filter == "ipt"} checked="checked" { /if } />iptables
   </td>
   <td>
    Mechanism which filters your traffic. tc-filter is the tc-builtin filter technic. Good performance, but less options. iptables has many options for matching traffic, l7 protocols, and many more things. But this will add a second needed subsystem for shaping. Make tests if your Linux machine is powerful enough for this.
   </td>
  </tr>
  <tr>
   <td>
    Mode:
   </td>
   <td>
    <input type="radio" name="msmode" value="router" { if $msmode == "router" } checked="checked" { /if }/>Router
    <input type="radio" name="msmode" value="bridge" { if $msmode == "bridge" } checked="checked" { /if }/>Bridge
   </td>
   <td>
    This option tells MasterShaper if it is used on a router (between networks) or on a bridge (transparent in the network). This setting is very important if you use iptables as traffic filter to match network packets on the correct network interfaces.
   </td>
  </tr>
  <tr>
   <td>
    Authentication:
   </td>
   <td>
    <input type="radio" name="authentication" value="Y" { if $authentication == "Y"} checked=checked" { /if } />Yes
    <input type="radio" name="authentication" value="N" { if $authentication != "Y"} checked=checked" { /if } />No
   </td>
   <td>
    Enable or disable MasterShaper's authentication mechanism. If enabled you can configure user &amp; rights in the webinterface. If disabled, no permission management will be done per MasterShaper and everyone has full control in the webinterface.
   </td>
  </tr>
  <tr>
   <td colspan="3">
    &nbsp;
   </td>
  </tr>
  <tr>
   <td>&nbsp;</td>
   <td><input type="submit" value="Save" /></td>
   <td>Save your settings.</td>
  </tr>
 </table>
</form>
