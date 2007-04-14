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

class MSABOUT {

   var $parent;

   function MSABOUT($parent)
   {
      $this->parent = $parent;
   } // MSABOUT()

   function showHtml()
   {

      $this->parent->startTable("<img src=\"". ICON_ABOUT ."\" alt=\"about icon\" />&nbsp;MasterShaper ". $this->parent->version);
?>
     <table style="width: 100%; text-align: center;">
      <tr>
       <td>
        <br />
        Websolution for Linux network traffic shaping and QoS with iproute2.<br />
        <a href="http://www.mastershaper.org">http://www.mastershaper.org</a><br />
	<br />
	<hr />
	<br />
	<b>Andreas Unterkircher
	<br />
	(<a href="mailto:andreas.unterkircher@netshadow.at?subject=MasterShaper">andreas.unterkircher@netshadow.at</a>)</b><br />
	<br />
	<i><b>Licensed under GPL2</b></i><br />
	(<a href="http://www.gnu.org/copyleft/gpl.html">http://www.gnu.org/copyleft/gpl.html</a>)<br />
	<br />
	Feedback is always appreciated! If you are willing please send some infos under which conditions<br />
	you are using MasterShaper. This informations will be presented on the main website.<br />
	<br />
	<hr />
	<br />
	tc is part of the iproute2 utilities (<a href="http://linux-net.osdl.org/index.php/Iproute2">osdl.org</a>).<br />
	MySQL is a product of MySQL AB (<a href="http://www.mysql.com">http://www.mysql.com</a>).<br />
	PHP is a product of The PHP Group (<a href="http://www.php.net">http://www.php.net</a>).<br />
	<br />
	<hr />
	<br />
	Silk icon set 1.2 by Mark James (<a href="http://www.famfamfam.com/lab/icons/silk/">http://www.famfamfam.com</a>)<br />
	licensed by Creative Commons Attribution 2.5 License.<br />
	<br />
	TwinHelix DHTML JavaScript Menu<br />
	(<a href="http://www.twinhelix.com/">DHTML / JavaScript Menu by TwinHelix</a>)<br />
	<br />
       </td>
      </tr>
     </table>
<?php
      $this->parent->closeTable();

   } // showHtml()

}

?>
