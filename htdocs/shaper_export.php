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

require_once "shaper.class.php";

class MASTERSHAPER_EXPORT {

   private $parent;
   private $db;
   private $config;
   private $root;

   /**
    * MASTERSHAPER_EXPORT constructor
    *
    * Initialize the MASTERSHAPER_EXPORT class
    */
   public function __construct()
   {
      $this->parent = new MASTERSHAPER;
      $this->db = $this->parent->db;

      if(!$this->parent->is_logged_in()) {
         print "You are not logged in.";
         exit(1);
      }

      $this->saveConfig();

   } // __construct()

   /**
    * export current MasterShaper ruleset & configuration
    *
    * Exports the whole MasterShaper ruleset & configuration into XML code
    * and sends it as downloadable file to the browser
    *    
    */
   private function saveConfig()
   {
      if($this->parent->getOption("authentication") == "Y" &&
         !$this->parent->checkPermissions("user_manage_options")) {

         print _("You do not have enough permissions to access this module!");
         return 0;

      }

      // start XML document
      $config = $this->startXml();
      $this->addElement('config');

      // Settings
      $settings = $this->addRootChild('settings');
      $result = $this->db->db_query("SELECT * FROM ". MYSQL_PREFIX ."settings");
      while($row = $result->fetchRow()) {
         $this->addValue($settings, $row->setting_key, $row->setting_value);
      }

      // Users
      $users = $this->addRootChild('users');
      $result = $this->db->db_query("
            SELECT *
            FROM ". MYSQL_PREFIX ."users
      ");

      while($row = $result->fetchRow(MDB2_FETCHMODE_ASSOC)) {
         $user = $this->addSubChild('user', $users);
         $keys = array_keys($row);
         foreach($keys as $key) {
            $this->addValue($user, htmlspecialchars($key), htmlspecialchars($row[$key]));
         }
      }

      // User definied protocols
      $protocols = $this->addRootChild('protocols');
      $result = $this->db->db_query("
         SELECT *
         FROM ". MYSQL_PREFIX ."protocols
         WHERE proto_user_defined='Y'
      ");

      while($row = $result->fetchRow(MDB2_FETCHMODE_ASSOC)) {
         $proto = $this->addSubChild('protocol', $protocols);
         $keys = array_keys($row);
         foreach($keys as $key) {
            $this->addValue($proto, htmlspecialchars($key), htmlspecialchars($row[$key]));
         }
      }

      // User definied ports 
      $ports = $this->addRootChild('ports');
      $result = $this->db->db_query("
         SELECT *
         FROM ". MYSQL_PREFIX ."ports 
         WHERE port_user_defined='Y'
      ");

      while($row = $result->fetchRow(MDB2_FETCHMODE_ASSOC)) {
         $port = $this->addSubChild('port', $ports);
         $keys = array_keys($row);
         foreach($keys as $key) {
            $this->addValue($port, htmlspecialchars($key), htmlspecialchars($row[$key]));
         }
      }

      // Service Levels 
      $result = $this->db->db_query("
         SELECT *
         FROM ". MYSQL_PREFIX ."service_levels
      ");

      $servicelevels = $this->addRootChild('servicelevels');
      while($row = $result->fetchRow(MDB2_FETCHMODE_ASSOC)) {
         $servicelevel = $this->addSubChild('servicelevel', $servicelevels);
         $keys = array_keys($row);
         foreach($keys as $key) {
            $this->addValue($servicelevel, htmlspecialchars($key), htmlspecialchars($row[$key]));
         }
      }

      // Targets, reverse order so groups are on the last position! 
      $targets = $this->addRootChild('targets');
      $result = $this->db->db_query("
         SELECT *
         FROM ". MYSQL_PREFIX ."targets
         ORDER BY target_match DESC
      ");

      while($row = $result->fetchRow(MDB2_FETCHMODE_ASSOC)) {
         $members = $this->db->db_query("
            SELECT t.target_name
            FROM ". MYSQL_PREFIX ."targets t
            INNER JOIN ". MYSQL_PREFIX ."assign_target_groups atg
               ON atg.atg_group_idx='". $row['target_idx'] ."'
            AND
               atg.atg_target_idx=t.target_idx");

         if($member = $members->fetchAll(MDB2_FETCHMODE_ORDERED)) {
            $row['target_members'] = implode('#', $member[0]);
         }

         $target = $this->addSubChild('target', $targets);
         $keys = array_keys($row);
         foreach($keys as $key) {
            $this->addValue($target, htmlspecialchars($key), htmlspecialchars($row[$key]));
         }
      }

      /* L7 Protocol definitions */
      $l7protocols = $this->addRootChild('l7protocols');
      $result = $this->db->db_query("
         SELECT *
         FROM ". MYSQL_PREFIX ."l7_protocols
         ORDER BY l7proto_name ASC
      ");

      while($row = $result->fetchRow(MDB2_FETCHMODE_ASSOC)) {
         $l7proto = $this->addSubChild('l7protocol', $l7protocols);
         $keys = array_keys($row);
         foreach($keys as $key) {
            $this->addValue($l7proto, htmlspecialchars($key), htmlspecialchars($row[$key]));
         }
      }

      /* filter definition */
      $filters = $this->addRootChild('filters');
      $result = $this->db->db_query("
         SELECT *
         FROM ". MYSQL_PREFIX ."filters
         ORDER BY filter_name ASC
      ");

      while($row = $result->fetchRow(MDB2_FETCHMODE_ASSOC)) {

         $row['filter_protocol_id'] = $this->parent->getProtocolNameById($row['filter_protocol_id']);

         $ports = $this->db->db_query("
            SELECT p.port_name
            FROM ". MYSQL_PREFIX ."ports p 
            INNER JOIN ". MYSQL_PREFIX ."assign_ports_to_filters afp
            ON
               p.port_idx=afp.afp_port_idx
            WHERE afp.afp_filter_idx='". $row['filter_idx'] ."'
         ");

         $l7protos = $this->db->db_query("
            SELECT l7.l7proto_name
            FROM ". MYSQL_PREFIX ."l7_protocols l7
            INNER JOIN ". MYSQL_PREFIX ."assign_l7_protocols_to_filters afl7
               ON l7.l7proto_idx=afl7.afl7_l7proto_idx
            WHERE afl7.afl7_filter_idx='". $row['filter_idx'] ."' 
         ");

         if($ports = $ports->fetchCol(0)) {
            $row['filter_ports'] = implode('#', $ports);
         }

         if($l7protos = $l7protos->fetchCol(0)) {
            $row['l7_protocols'] = implode('#', $l7protos);
         }

         $filter = $this->addSubChild('filter', $filters);
         $keys = array_keys($row);
         foreach($keys as $key) {
            $this->addValue($filter, htmlspecialchars($key), htmlspecialchars($row[$key]));
         }
 
      }

      // Chains 
      $chains = $this->addRootChild('chains');
      $result = $this->db->db_query("
         SELECT *
         FROM ". MYSQL_PREFIX ."chains
         ORDER BY chain_name
      ");

      while($row = $result->fetchRow(MDB2_FETCHMODE_ASSOC)) {

         $row['sl_name']  = $this->parent->getServiceLevelName($row['chain_sl_idx']);
         $row['fb_name']  = $this->parent->getServiceLevelName($row['chain_fallback_idx']);
         $row['src_name'] = $this->parent->getTargetName($row['chain_src_target']);
         $row['dst_name'] = $this->parent->getTargetName($row['chain_dst_target']);

         $chain = $this->addSubChild('chain', $chains);
         $keys = array_keys($row);
         foreach($keys as $key) {
            $this->addValue($chain, htmlspecialchars($key), htmlspecialchars($row[$key]));
         }
      }

      // Pipes 
      $pipes = $this->addRootChild('pipes');
      $result = $this->db->db_query("
         SELECT
            p.*,
            apc.apc_chain_idx as pipe_chain_idx
         FROM
            ". MYSQL_PREFIX ."pipes p
         LEFT JOIN
            ". MYSQL_PREFIX ."assign_pipes_to_chains apc
         ON
            p.pipe_idx=apc.apc_pipe_idx
         ORDER BY
            pipe_name ASC
      ");

      while($row = $result->fetchRow(MDB2_FETCHMODE_ASSOC)) {

         $filters = $this->db->db_query("
            SELECT f.filter_name
            FROM ". MYSQL_PREFIX ."filters f
            INNER JOIN ". MYSQL_PREFIX ."assign_filters_to_pipes apf
            ON f.filter_idx=apf.apf_filter_idx
            WHERE apf.apf_pipe_idx='". $row['pipe_idx'] ."'
         ");

         if($filters = $filters->fetchCol(0)) {
            $row['filters'] = implode('#', $filters);
         }

         $row['chain_name'] = $this->parent->getChainName($row['pipe_chain_idx']);
         $row['sl_name']    = $this->parent->getServiceLevelName($row['pipe_sl_idx']);

         $pipe = $this->addSubChild('pipe', $pipes);
         $keys = array_keys($row);
         foreach($keys as $key) {
            $this->addValue($pipe, htmlspecialchars($key), htmlspecialchars($row[$key]));
         }
      }
		
      /* create output */
      Header("Content-type: text/xml; charset=utf-8");
      //Header("Content-Type: application/octet-stream");
      Header("Content-Transfer-Encoding: binary\n");
      $user_agent = strtolower ($_SERVER["HTTP_USER_AGENT"]);
      /*if ((is_integer (strpos($user_agent, "msie"))) && (is_integer (strpos($user_agent, "win"))))
         Header("Content-Disposition: inline; filename=\"ms_config_". strftime("%Y%m%d") .".cfg\"");
      else
         Header("Content-Disposition: attachement; filename=\"ms_config_". strftime("%Y%m%d") .".cfg\"");*/
      Header("Content-Length: ". strlen($this->config->saveXML()));
      Header("Content-Description: MasterShaper XML Config Dump" );
      Header("Accept-Ranges: bytes");
      Header("Pragma: no-cache");
      Header("Cache-Control: no-cache, must-revalidate");
      Header("Cache-Control: post-check=0, pre-check=0", false);
      Header("Cache-Control: private");
      Header("Connection: close");

      print $this->config->saveXML();
      exit(0);

   } // saveConfig()

   private function startXml()
   {
      $this->config = new DOMDocument('1.0');
      $this->config->formatOutput = true;

      $comment = $this->config->createComment("MasterShaper configured, dumped on ". strftime("%Y-%m-%d %H:%M"));
      $this->config->appendChild($comment);

   } // startXml();

   private function addElement($name)
   {
      $this->root = $this->config->createElement($name);
      $this->config->appendChild($this->root);
   } // addElement()

   private function addRootChild($name)
   {
      $child = $this->config->createElement($name);
      $child = $this->root->appendChild($child);

      return $child;

   } // addRootChild()

   private function addValue(&$obj, $key, $value)
   {
      $temp = $this->config->createElement($key, $value);
      $obj->appendChild($temp);

   } // addValue()

   private function addSubChild($child, &$parent)
   {
      $temp = $this->config->createElement($child);
      $temp = $parent->appendChild($temp);

      return $temp;

   } // addSubChild()

} // class MASTERSHAPER_EXPORT

$obj = new MASTERSHAPER_EXPORT();
$obj->show();

?>
