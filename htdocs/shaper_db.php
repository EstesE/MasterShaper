<?php

define('VERSION', '0.60');
define('SCHEMA_VERSION', '15');

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

/* from pear "MDB2" package. use "pear install MDB2" if you don't have this! */
// require_once('MDB2.php');

class MASTERSHAPER_DB {

   private $db;
   private $is_connected;
   private $last_error;
   /* the _real_ schema version is defined as constant */
   /* that one just holds the "current" version number */
   /* during upgrades.                                 */
   private $schema_version;

   /**
    * MASTERSHAPER_DB class constructor
    *
    * This constructor initially connect to the database.
    */
   public function __construct()
   {
      /* We are starting disconnected */
      $this->setConnStatus(false);

      /* Connect to MySQL Database */
      $this->db_connect();

   } // __construct()
	 
   /**
    * MASTERSHAPER_DB class deconstructor
    *
    * This destructor will close the current database connection.
    */ 
   public function __destruct()
   {
      if($this->getConnStatus())
         $this->db_disconnect();

   } // _destruct()

   /**
    * MASTERSHAPER_DB database connect
    *
    * This function will connect to the database via MDB2
    */
   private function db_connect()
   {
      global $ms;

      $options = array(
         'debug' => 2,
         'portability' => 'DB_PORTABILITY_ALL'
      );

      if(!defined('MYSQL_USER') ||
         !defined('MYSQL_PASS') ||
         !defined('MYSQL_HOST') ||
         !defined('MYSQL_DB')) {

         $ms->throwError("Missing MySQL configuration");
      }

      $dsn = "mysql://". MYSQL_USER .":". MYSQL_PASS ."@". MYSQL_HOST ."/". MYSQL_DB;
      $this->db = MDB2::connect($dsn, $options);

      if(PEAR::isError($this->db)) {
         $ms->throwError("Unable to connect to database: ". $this->db->getMessage() .' - '. $this->db->getUserInfo());
         $this->setConnStatus(false);
      }

      $this->setConnStatus(true);

   } // db_connect()

   /**
    * MASTERSHAPER_DB database disconnect
    *
    * This function will disconnected an established database connection.
    */
   private function db_disconnect()
   {
      $this->db->disconnect();

   } // db_disconnect()

   /**
    * MASTERSHAPER_DB database query
    *
    * This function will execute a SQL query and return the result as
    * object.
    */
   public function db_query($query = "", $mode = MDB2_FETCHMODE_OBJECT)
   {
      global $ms;

      if(!$this->getConnStatus())
         $ms->throwError("Can't execute query - we are not connected!");

      $this->db->setFetchMode($mode);

      /* for manipulating queries use exec instead of query. can save
       * some resource because nothing has to be allocated for results.
       */
      if(preg_match('/^(update|insert|replace)i/', $query)) {
         $result = $this->db->exec($query);
      }
      else {
         $result = $this->db->query($query);
      }

      if(PEAR::isError($result))
         $ms->throwError($result->getMessage() .' - '. $result->getUserInfo());

      return $result;

   } // db_query()

   /**
    * MASTERSHAPER_DB database prepare query
    *
    * This function will prepare a SQL query to be executed
    *
    * @param string $query
    * @return mixed
    */
   public function db_prepare($query = "")
   {
      global $ms;

      if(!$this->getConnStatus())
         $ms->throwError("Can't prepare query - we are not connected!");

      $this->db->prepare($query);

      /* for manipulating queries use exec instead of query. can save
       * some resource because nothing has to be allocated for results.
       */
      if(preg_match('/^(update|insert|delete)i/', $query)) {
         $sth = $this->db->prepare($query, MDB2_PREPARE_MANIP);
      }
      else {
         $sth = $this->db->prepare($query, MDB2_PREPARE_RESULT);
      }

      if(PEAR::isError($sth))
         $ms->throwError($sth->getMessage() .' - '. $sth->getUserInfo());

      return $sth;

   } // db_prepare()

   /**
    * MASTERSHAPER_DB database execute a prepared query
    *
    * This function will execute a previously prepared SQL query
    *
    * @param mixed $sth
    * @param mixed $data
    */
   public function db_execute($sth, $data)
   {
      global $ms;

      if(!$this->getConnStatus())
         $ms->throwError("Can't prepare query - we are not connected!");

      $result = $sth->execute($data);

      if(PEAR::isError($result))
         $ms->throwError($result->getMessage() .' - '. $result->getUserInfo());

      return $result;

   } // db_execute()

   /**
    * MASTERSHAPER_DB fetch ONE row
    *
    * This function will execute the given but only return the
    * first result.
    */
   public function db_fetchSingleRow($query = "", $mode = MDB2_FETCHMODE_OBJECT)
   {
      global $ms;

      if(!$this->getConnStatus())
         $ms->throwError("Can't fetch row - we are not connected!");

      $row = $this->db->queryRow($query, array(), $mode);

      if(PEAR::isError($row))
         $ms->throwError($row->getMessage() .' - '. $row->getUserInfo());

      return $row;
      
   } // db_fetchSingleRow()

   /**
    * MASTERSHAPER_DB number of affected rows
    *
    * This functions returns the number of affected rows but the
    * given SQL query.
    */
   public function db_getNumRows($query = "")
   {
      global $ms;

      if(!$this->getConnStatus())
         $ms->throwError("Can't fetch row - we are not connected!");

      /* Execute query */
      $result = $this->db_query($query);

      /* Errors? */
      if(PEAR::isError($result)) 
         $ms->throwError($result->getMessage() .' - '. $result->getUserInfo());

      return $result->numRows();

   } // db_getNumRows()

   /**
    * MASTERSHAPER_DB get primary key
    *
    * This function returns the primary key of the last
    * operated insert SQL query.
    */
   public function db_getid()
   {
      global $ms;

      if(!$this->getConnStatus())
         $ms->throwError("Can't fetch row - we are not connected!");

      /* Get the last primary key ID from execute query */
      return mysql_insert_id($this->db->connection);
      
   } // db_getid()

   /**
    * MASTERSHAPER_DB check table exists
    *
    * This function checks if the given table exists in the
    * database
    * @param string, table name
    * @return true if table found otherwise false
    */
   public function db_check_table_exists($table_name = "")
   {
      global $ms;

      if(!$this->getConnStatus())
         $ms->throwError("Can't check table - we are not connected!");

      $result = $this->db_query("SHOW TABLES");
      $tables_in = "Tables_in_". MYSQL_DB;

      while($row = $result->fetchRow()) {
         if($row->$tables_in == $table_name)
            return true;
      }
      return false;

   } // db_check_table_exists()

   /**
    * MASTERSHAPER_DB rename table
    * 
    * This function will rename an database table
    * @param old_name, new_name
    */
   public function db_rename_table($old, $new)
   {
      global $ms;

      if(!$this->getConnStatus())
         $ms->throwError("Can't check table - we are not connected!");

      if(!$this->db_check_table_exists($old))
         $ms->throwError("Table ". $old ." does not exist!");

      if($this->db_check_table_exists($new))
         $ms->throwError("Can't rename table ". $old ." - ". $new ." already exists!");

      $this->db_query("RENAME TABLE ". $old ." TO ". $new);
	 
   } // db_rename_table()

   /**
    * MASTERSHAPER_DB drop table
    *
    * This function will delete the given table from database
    */
   public function db_drop_table($table_name)
   {
      global $ms;

      if(!$this->getConnStatus())
         $ms->throwError("Can't check table - we are not connected!");

      if($this->db_check_table_exists($table_name))
         $this->db_query("DROP TABLE ". $table_name);

   } // db_drop_table()

   /**
    * MASTERSHAPER_DB truncate table
    *
    * This function will truncate (reset) the given table
    */
   public function db_truncate_table($table_name)
   {
      global $ms;

      if(!$this->getConnStatus())
         $ms->throwError("Can't check table - we are not connected!");

      if($this->db_check_table_exists($table_name)) 
         $this->db_query("TRUNCATE TABLE ". $table_name);

   } // db_truncate_table()

   /**
    * MASTERSHAPER_DB check column exist
    *
    * This function checks if the given column exists within
    * the specified table.
    */
   public function db_check_column_exists($table_name, $column)
   {
      global $ms;

      if(!$this->getConnStatus())
         $ms->throwError("Can't check table - we are not connected!");

      $result = $this->db_query("DESC ". $table_name, MDB2_FETCHMODE_ORDERED);
      while($row = $result->fetchRow()) {
         if(in_array($column, $row))
            return 1;
      }
      return 0;

   } // db_check_column_exists()

   /**
    * MASTERSHAPER_DB check index exists
    *
    * This function checks if the given index can be found
    * within the specified table.
    */
   public function db_check_index_exists($table_name, $index_name)
   {
      global $ms;

      if(!$this->getConnStatus())
         $ms->throwError("Can't check table - we are not connected!");

      $result = $this->db_query("DESC ". $table_name, MDB2_FETCHMODE_ORDERED);

      while($row = $result->fetchRow()) {
         if(in_array("KEY `". $index_name ."`", $row))
            return 1;
      }

      return 0;

   } // db_check_index_exists()

   /**
    * MASTERSHAPER_DB alter table
    *
    * This function offers multiple methods to alter a table.
    * * add/modify/delete columns
    * * drop index
    */
   public function db_alter_table($table_name, $option, $column, $param1 = "", $param2 = "")
   {
      global $ms;

      if(!$this->getConnStatus())
         $ms->throwError("Can't check table - we are not connected!");

      if(!$this->db_check_table_exists($table_name))
         $ms->throwError("Table ". $table_name ." does not exist!");

      switch(strtolower($option)) {
	
         case 'add':
            if(!$this->db_check_column_exists($table_name, $column))
               $this->db_query("ALTER TABLE ". $table_name ." ADD ". $column ." ". $param1);
            break;

         case 'change':

            if($this->db_check_column_exists($table_name, $column))
               $this->db_query("ALTER TABLE ". $table_name ." CHANGE ". $column ." ". $param1);
            break;

         case 'drop':

            if($this->db_check_column_exists($table_name, $column))
               $this->db_query("ALTER TABLE ". $table_name ." DROP ". $column);
            break;

         case 'dropidx':

            if($this->db_check_index_exists($table_name, $column))
               $this->db_query("ALTER TABLE ". $table_name ." DROP INDEX ". $column);
            break;

      }

   } // db_alter_table()

   /**
    * MASTERSHAPER_DB get MasterShaper Version
    *
    * This functions returns the current MasterShaper (DB) version
    */
   public function getVersion()
   {
      global $ms;

      if(!$this->getConnStatus())
         $ms->throwError("Can't check table - we are not connected!");

      if(!$this->db_check_table_exists(MYSQL_PREFIX ."meta"))
         return false;

      $result = $this->db_fetchSingleRow("
         SELECT
            meta_value
         FROM
            ". MYSQL_PREFIX ."meta
         WHERE
            meta_key LIKE 'schema version'
      ");

      if(isset($result->meta_value))
         return $result->meta_value;

      return 0;
	 
   } // getVersion()

   /**
    * MASTERSHAPER_DB set version
    *
    * This function sets the version name of MasterShaper (DB)
    */
   private function setVersion($version)
   {
      global $ms;

      if(!$this->getConnStatus())
         $ms->throwError("Can't check table - we are not connected!");

      $this->db_query("
         REPLACE INTO ". MYSQL_PREFIX ."meta (
            meta_key, meta_value
         ) VALUES (
            'schema version',
            '". $version ."'
         )
      ");
      
   } // setVersion()

   /**
    * MASTERSHAPER_DB get connection status
    *
    * This function checks the internal state variable if already
    * connected to database.
    */
   private function setConnStatus($status)
   {
      $this->is_connected = $status;
      
   } // setConnStatus()

   /**
    * MASTERSHAPER_DB set connection status
    * This function sets the internal state variable to indicate
    * current database connection status.
    */
   private function getConnStatus()
   {
      return $this->is_connected;

   } // getConnStatus()

   public function install_schema()
   {
      $this->schema_version = $this->getVersion();

      if(!$this->db_check_table_exists(MYSQL_PREFIX . 'meta') ||
         $this->getVersion() < SCHEMA_VERSION) {

         $this->install_tables();
         return true;
      }

      $this->upgrade_schema();

      return true;

   } // install_schema()

   private function install_tables()
   {
      if(!$this->db_check_table_exists(MYSQL_PREFIX . 'assign_filters_to_pipes')) {
         $this->db_query("
            CREATE TABLE `". MYSQL_PREFIX . "assign_filters_to_pipes` (
              `apf_idx` int(11) NOT NULL auto_increment,
              `apf_pipe_idx` int(11) default NULL,
              `apf_filter_idx` int(11) default NULL,
              PRIMARY KEY  (`apf_idx`),
              KEY `apf_pipe_idx` (`apf_pipe_idx`),
              KEY `apf_filter_idx` (`apf_filter_idx`)
            ) ENGINE=MyISAM DEFAULT CHARSET=latin1;
         ");
      }
      if(!$this->db_check_table_exists(MYSQL_PREFIX . 'assign_l7_protocols_to_filters')) {
         $this->db_query("
            CREATE TABLE `". MYSQL_PREFIX ."assign_l7_protocols_to_filters` (
              `afl7_idx` int(11) NOT NULL auto_increment,
              `afl7_filter_idx` int(11) NOT NULL,
              `afl7_l7proto_idx` int(11) NOT NULL,
              PRIMARY KEY  (`afl7_idx`),
              KEY `afl7_filter_idx` (`afl7_filter_idx`),
              KEY `afl7_l7proto_idx` (`afl7_l7proto_idx`)
            ) ENGINE=MyISAM DEFAULT CHARSET=latin1;
         ");
      }
      if(!$this->db_check_table_exists(MYSQL_PREFIX . 'assign_ports_to_filters')) {
         $this->db_query("
            CREATE TABLE `". MYSQL_PREFIX ."assign_ports_to_filters` (
              `afp_idx` int(11) NOT NULL auto_increment,
              `afp_filter_idx` int(11) default NULL,
              `afp_port_idx` int(11) default NULL,
              PRIMARY KEY  (`afp_idx`),
              KEY `afp_filter_idx` (`afp_filter_idx`),
              KEY `afp_port_idx` (`afp_port_idx`)
            ) ENGINE=MyISAM DEFAULT CHARSET=latin1;
         ");
      }
      if(!$this->db_check_table_exists(MYSQL_PREFIX . 'assign_target_groups')) {
         $this->db_query("
            CREATE TABLE `". MYSQL_PREFIX ."assign_target_groups` (
              `atg_idx` int(11) NOT NULL auto_increment,
              `atg_group_idx` int(11) NOT NULL,
              `atg_target_idx` int(11) NOT NULL,
              PRIMARY KEY  (`atg_idx`)
            ) ENGINE=MyISAM DEFAULT CHARSET=latin1;
         ");
      }
      if(!$this->db_check_table_exists(MYSQL_PREFIX .'assign_pipes_to_chains')) {
         $this->db_query("
             CREATE TABLE `". MYSQL_PREFIX ."assign_pipes_to_chains` (
              `apc_idx` int(11) NOT NULL auto_increment,
              `apc_pipe_idx` int(11) NOT NULL,
              `apc_chain_idx` int(11) NOT NULL,
              `apc_sl_idx` int(11) NOT NULL,
              `apc_pipe_pos` int(11) DEFAULT NULL,
              PRIMARY KEY  (`apc_idx`),
              KEY `apc_pipe_to_chain`  (`apc_pipe_idx`,`apc_chain_idx`)
            ) ENGINE=MyISAM DEFAULT CHARSET=latin1;
         ");
      }
      if(!$this->db_check_table_exists(MYSQL_PREFIX . 'chains')) {
         $this->db_query("
            CREATE TABLE `". MYSQL_PREFIX ."chains` (
              `chain_idx` int(11) NOT NULL auto_increment,
              `chain_name` varchar(255) default NULL,
              `chain_active` char(1) default NULL,
              `chain_sl_idx` int(11) default NULL,
              `chain_src_target` int(11) default NULL,
              `chain_dst_target` int(11) default NULL,
              `chain_position` int(11) default NULL,
              `chain_direction` int(11) default NULL,
              `chain_fallback_idx` int(11) default NULL,
              `chain_action` varchar(16) default NULL,
              `chain_tc_id` varchar(16) default NULL,
              `chain_netpath_idx` int(11) default NULL,
              `chain_host_idx` int(11) default NULL,
              PRIMARY KEY  (`chain_idx`)
            ) ENGINE=MyISAM DEFAULT CHARSET=latin1;
         ");
      }
      if(!$this->db_check_table_exists(MYSQL_PREFIX . 'filters')) {
         $this->db_query("
            CREATE TABLE `". MYSQL_PREFIX ."filters` (
              `filter_idx` int(11) NOT NULL auto_increment,
              `filter_name` varchar(255) default NULL,
              `filter_protocol_id` int(11) default NULL,
              `filter_tos` varchar(4) default NULL,
              `filter_dscp` varchar(4) default NULL,
              `filter_tcpflag_syn` char(1) default NULL,
              `filter_tcpflag_ack` char(1) default NULL,
              `filter_tcpflag_fin` char(1) default NULL,
              `filter_tcpflag_rst` char(1) default NULL,
              `filter_tcpflag_urg` char(1) default NULL,
              `filter_tcpflag_psh` char(1) default NULL,
              `filter_packet_length` varchar(255) default NULL,
              `filter_p2p_edk` char(1) default NULL,
              `filter_p2p_kazaa` char(1) default NULL,
              `filter_p2p_dc` char(1) default NULL,
              `filter_p2p_gnu` char(1) default NULL,
              `filter_p2p_bit` char(1) default NULL,
              `filter_p2p_apple` char(1) default NULL,
              `filter_p2p_soul` char(1) default NULL,
              `filter_p2p_winmx` char(1) default NULL,
              `filter_p2p_ares` char(1) default NULL,
              `filter_time_use_range` char(1) default NULL,
              `filter_time_start` int(11) default NULL,
              `filter_time_stop` int(11) default NULL,
              `filter_time_day_mon` char(1) default NULL,
              `filter_time_day_tue` char(1) default NULL,
              `filter_time_day_wed` char(1) default NULL,
              `filter_time_day_thu` char(1) default NULL,
              `filter_time_day_fri` char(1) default NULL,
              `filter_time_day_sat` char(1) default NULL,
              `filter_time_day_sun` char(1) default NULL,
              `filter_match_ftp_data` char(1) default NULL,
              `filter_match_sip` char(1) default NULL,
              `filter_active` char(1) default NULL,
              PRIMARY KEY  (`filter_idx`)
            ) ENGINE=MyISAM DEFAULT CHARSET=latin1;
         ");
      }
      if(!$this->db_check_table_exists(MYSQL_PREFIX . 'interfaces')) {
         $this->db_query("
            CREATE TABLE `". MYSQL_PREFIX ."interfaces` (
              `if_idx` int(11) NOT NULL auto_increment,
              `if_name` varchar(255) default NULL,
              `if_speed` varchar(255) default NULL,
              `if_fallback_idx` int(11) default NULL,
              `if_ifb` char(1) default NULL,
              `if_active` char(1) default NULL,
              `if_host_idx` int(11) default NULL,
              PRIMARY KEY  (`if_idx`)
            ) ENGINE=MyISAM DEFAULT CHARSET=latin1;
         ");   
      }
      if(!$this->db_check_table_exists(MYSQL_PREFIX . 'l7_protocols')) {
         $this->db_query("
            CREATE TABLE `". MYSQL_PREFIX ."l7_protocols` (
              `l7proto_idx` int(11) NOT NULL auto_increment,
              `l7proto_name` varchar(255) default NULL,
              PRIMARY KEY  (`l7proto_idx`)
            ) ENGINE=MyISAM DEFAULT CHARSET=latin1;
         ");
      }
      if(!$this->db_check_table_exists(MYSQL_PREFIX . 'network_paths')) {
         $this->db_query("
            CREATE TABLE `". MYSQL_PREFIX ."network_paths` (
              `netpath_idx` int(11) NOT NULL auto_increment,
              `netpath_name` varchar(255) default NULL,
              `netpath_if1` int(11) default NULL,
              `netpath_if1_inside_gre` varchar(1) default NULL,
              `netpath_if2` int(11) default NULL,
              `netpath_if2_inside_gre` varchar(1) default NULL,
              `netpath_position` int(11) default NULL,
              `netpath_imq` varchar(1) default NULL,
              `netpath_active` varchar(1) default NULL,
              `netpath_host_idx` int(11) default NULL,
              PRIMARY KEY  (`netpath_idx`)
            ) ENGINE=MyISAM DEFAULT CHARSET=latin1;
         ");
      }
      if(!$this->db_check_table_exists(MYSQL_PREFIX . 'pipes')) {
         $this->db_query("
            CREATE TABLE `". MYSQL_PREFIX ."pipes` (
              `pipe_idx` int(11) NOT NULL auto_increment,
              `pipe_name` varchar(255) default NULL,
              `pipe_sl_idx` int(11) default NULL,
              `pipe_src_target` int(11) default NULL,
              `pipe_dst_target` int(11) default NULL,
              `pipe_direction` int(11) default NULL,
              `pipe_action` varchar(15) default NULL,
              `pipe_active` char(1) default NULL,
              `pipe_tc_id` varchar(16) default NULL,
              PRIMARY KEY  (`pipe_idx`)
            ) ENGINE=MyISAM DEFAULT CHARSET=latin1;
         ");
      }
      if(!$this->db_check_table_exists(MYSQL_PREFIX . 'ports')) {
         $this->db_query("
            CREATE TABLE `". MYSQL_PREFIX ."ports` (
              `port_idx` int(11) NOT NULL auto_increment,
              `port_name` varchar(255) default NULL,
              `port_desc` varchar(255) default NULL,
              `port_number` varchar(255) default NULL,
              `port_user_defined` char(1) default NULL,
              PRIMARY KEY  (`port_idx`)
            ) ENGINE=MyISAM DEFAULT CHARSET=latin1;
         ");
         $this->db_query(" 
            LOAD DATA LOCAL INFILE
               '". BASE_PATH ."/contrib/port-numbers.csv'
            IGNORE INTO TABLE
               ". MYSQL_PREFIX ."ports
            FIELDS TERMINATED BY ','
            ENCLOSED BY '\"' LINES
            TERMINATED BY '\r\n'
         ");
      }
      if(!$this->db_check_table_exists(MYSQL_PREFIX . 'protocols')) {
         $this->db_query("
            CREATE TABLE `". MYSQL_PREFIX ."protocols` (
              `proto_idx` int(11) NOT NULL auto_increment,
              `proto_number` varchar(255) default NULL,
              `proto_name` varchar(255) default NULL,
              `proto_desc` varchar(255) default NULL,
              `proto_user_defined` char(1) default NULL,
              PRIMARY KEY  (`proto_idx`)
            ) ENGINE=MyISAM DEFAULT CHARSET=latin1;
         ");
         $this->db_query("
            LOAD DATA LOCAL INFILE
               '". BASE_PATH ."/contrib/protocol-numbers.csv'
            IGNORE INTO TABLE
               ". MYSQL_PREFIX ."protocols
            FIELDS TERMINATED BY ','
            ENCLOSED BY '\"'
            LINES TERMINATED BY '\r\n'
         ");
      }
      if(!$this->db_check_table_exists(MYSQL_PREFIX . 'service_levels')) {
         $this->db_query("
            CREATE TABLE `". MYSQL_PREFIX ."service_levels` (
              `sl_idx` int(11) NOT NULL auto_increment,
              `sl_name` varchar(255) default NULL,
              `sl_htb_bw_in_rate` varchar(255) default NULL,
              `sl_htb_bw_in_ceil` varchar(255) default NULL,
              `sl_htb_bw_in_burst` varchar(255) default NULL,
              `sl_htb_bw_out_rate` varchar(255) default NULL,
              `sl_htb_bw_out_ceil` varchar(255) default NULL,
              `sl_htb_bw_out_burst` varchar(255) default NULL,
              `sl_htb_priority` varchar(255) default NULL,
              `sl_hfsc_in_umax` varchar(255) default NULL,
              `sl_hfsc_in_dmax` varchar(255) default NULL,
              `sl_hfsc_in_rate` varchar(255) default NULL,
              `sl_hfsc_in_ulrate` varchar(255) default NULL,
              `sl_hfsc_out_umax` varchar(255) default NULL,
              `sl_hfsc_out_dmax` varchar(255) default NULL,
              `sl_hfsc_out_rate` varchar(255) default NULL,
              `sl_hfsc_out_ulrate` varchar(255) default NULL,
              `sl_cbq_in_rate` varchar(255) default NULL,
              `sl_cbq_in_priority` varchar(255) default NULL,
              `sl_cbq_out_rate` varchar(255) default NULL,
              `sl_cbq_out_priority` varchar(255) default NULL,
              `sl_cbq_bounded` char(1) default NULL,
              `sl_qdisc` varchar(255) default NULL,
              `sl_sfq_perturb` varchar(255) default NULL,
              `sl_sfq_quantum` varchar(255) default NULL,
              `sl_netem_delay` varchar(255) default NULL,
              `sl_netem_jitter` varchar(255) default NULL,
              `sl_netem_random` varchar(255) default NULL,
              `sl_netem_distribution` varchar(255) default NULL,
              `sl_netem_loss` varchar(255) default NULL,
              `sl_netem_duplication` varchar(255) default NULL,
              `sl_netem_gap` varchar(255) default NULL,
              `sl_netem_reorder_percentage` varchar(255) default NULL,
              `sl_netem_reorder_correlation` varchar(255) default NULL,
              `sl_esfq_perturb` varchar(255) default NULL,
              `sl_esfq_limit` varchar(255) default NULL,
              `sl_esfq_depth` varchar(255) default NULL,
              `sl_esfq_divisor` varchar(255) default NULL,
              `sl_esfq_hash` varchar(255) default NULL,
              PRIMARY KEY  (`sl_idx`)
               ) ENGINE=MyISAM DEFAULT CHARSET=latin1;
         ");
      }
      if(!$this->db_check_table_exists(MYSQL_PREFIX . 'settings')) {
         $this->db_query("
            CREATE TABLE `". MYSQL_PREFIX ."settings` (
              `setting_key` varchar(255) NOT NULL default '',
              `setting_value` varchar(255) default NULL,
              PRIMARY KEY  (`setting_key`)
            ) ENGINE=MyISAM DEFAULT CHARSET=latin1;
         ");
      }
      if(!$this->db_check_table_exists(MYSQL_PREFIX . 'stats')) {
         $this->db_query("
            CREATE TABLE `". MYSQL_PREFIX ."stats` (
              `stat_time` int(11) NOT NULL default '0',
              `stat_data` text,
              `stat_host_idx` int(11) default NULL,
              PRIMARY KEY  (`stat_time`)
            ) ENGINE=MyISAM DEFAULT CHARSET=latin1;
         ");
      }
      if(!$this->db_check_table_exists(MYSQL_PREFIX . 'targets')) {
         $this->db_query("
            CREATE TABLE `". MYSQL_PREFIX ."targets` (
              `target_idx` int(11) NOT NULL auto_increment,
              `target_name` varchar(255) default NULL,
              `target_match` varchar(16) default NULL,
              `target_ip` varchar(255) default NULL,
              `target_mac` varchar(255) default NULL,
              PRIMARY KEY  (`target_idx`)
            ) ENGINE=MyISAM DEFAULT CHARSET=latin1;
         ");
      }
      if(!$this->db_check_table_exists(MYSQL_PREFIX . 'tc_ids')) {
         $this->db_query("
            CREATE TABLE `". MYSQL_PREFIX ."tc_ids` (
              `id_pipe_idx` int(11) default NULL,
              `id_chain_idx` int(11) default NULL,
              `id_if` varchar(255) default NULL,
              `id_tc_id` varchar(255) default NULL,
              `id_color` varchar(7) default NULL,
              `id_host_idx` int(11) default NULL,
              KEY `id_pipe_idx` (`id_pipe_idx`),
              KEY `id_chain_idx` (`id_chain_idx`),
              KEY `id_if` (`id_if`),
              KEY `id_tc_id` (`id_tc_id`),
              KEY `id_color` (`id_color`)
            ) ENGINE=MEMORY DEFAULT CHARSET=latin1;
         ");
      }
      if(!$this->db_check_table_exists(MYSQL_PREFIX . 'users')) {
         $this->db_query("
            CREATE TABLE `". MYSQL_PREFIX ."users` (
              `user_idx` int(11) NOT NULL auto_increment,
              `user_name` varchar(32) default NULL,
              `user_pass` varchar(32) default NULL,
              `user_manage_chains` char(1) default NULL,
              `user_manage_pipes` char(1) default NULL,
              `user_manage_filters` char(1) default NULL,
              `user_manage_ports` char(1) default NULL,
              `user_manage_protocols` char(1) default NULL,
              `user_manage_targets` char(1) default NULL,
              `user_manage_users` char(1) default NULL,
              `user_manage_options` char(1) default NULL,
              `user_manage_servicelevels` char(1) default NULL,
              `user_show_rules` char(1) default NULL,
              `user_load_rules` char(1) default NULL,
              `user_show_monitor` char(1) default NULL,
              `user_active` char(1) default NULL,
              PRIMARY KEY  (`user_idx`)
            ) ENGINE=MyISAM DEFAULT CHARSET=latin1;
         ");
         $this->db_query("
            INSERT INTO ". MYSQL_PREFIX ."users VALUES (
               NULL,
               'admin',
               MD5('admin'),
               'Y',
               'Y',
               'Y',
               'Y',
               'Y',
               'Y',
               'Y',
               'Y',
               'Y',
               'Y',
               'Y',
               'Y',
               'Y'
            )");
      }

      if(!$this->db_check_table_exists(MYSQL_PREFIX . 'host_profiles')) {
         $this->db_query("
            CREATE TABLE `". MYSQL_PREFIX ."host_profiles` (
              `host_idx` int(11) NOT NULL auto_increment,
              `host_name` varchar(32) default NULL,
              `host_active` char(1) default NULL,
              PRIMARY KEY  (`host_idx`)
            ) ENGINE=MyISAM DEFAULT CHARSET=latin1;
         ");
         $this->db_query("
            INSERT INTO ". MYSQL_PREFIX ."host_profiles VALUES (
               NULL,
               'Default Host',
               'Y'
            )");
      }

      if(!$this->db_check_table_exists(MYSQL_PREFIX . 'pages')) {
         $this->db_query("
            CREATE TABLE `". MYSQL_PREFIX ."pages` (
              `page_id` int(11) unsigned NOT NULL auto_increment,
              `page_name` varchar(255) NOT NULL,
              `page_uri` varchar(255) NOT NULL,
              `page_uri_pattern` varchar(255) NOT NULL,
              `page_includefile` varchar(255) NOT NULL,
              PRIMARY KEY  (`page_id`),
              UNIQUE KEY `idx_uri` (`page_uri_pattern`)
            ) ENGINE=MyISAM  DEFAULT CHARSET=utf8;
         ");
         $this->db_query("
            INSERT INTO `". MYSQL_PREFIX ."pages` (`page_id`, `page_name`, `page_uri`, `page_uri_pattern`, `page_includefile`) VALUES
            (1, 'Overview', 'overview.html', '^/overview.html$', 'overview.php'),
            (2, 'Manage', 'manage.html', '^/manage.html$', 'manage.php'),
            (3, 'Login', 'login.html', '^/login.html$', '[internal]'),
            (4, 'Logout', 'logout.html', '^/logout.html$', '[internal]'),
            (5, 'Chains List', 'chains/list.html', '^/chains/list.html$', 'chains.php'),
            (6, 'Pipes List', 'pipes/list.html', '^/pipes/list.html$', 'pipes.php'),
            (7, 'Filters List', 'filters/list.html', '^/filters/list.html$', 'filters.php'),
            (8, 'Chain Edit', 'chains/edit-[id].html', '^/chains/edit-([0-9]+).html$', 'chains.php'),
            (9, 'Chain New', 'chains/new.html', '^/chains/new.html$', 'chains.php'),
            (10, 'Filter New', 'filters/new.html', '^/filters/new.html$', 'filters.php'),
            (11, 'Filter Edit', 'filters/edit-[id].html', '^/filters/edit-([0-9]+).html$', 'filters.php'),
            (12, 'Pipe Edit', 'pipes/edit-[id].html', '^/pipes/edit-([0-9]+).html$', 'pipes.php'),
            (13, 'Pipe New', 'pipes/new.html', '^/pipes/new.html$', 'pipes.php'),
            (14, 'Settings', 'settings.html', '^/settings.html$', 'settings.php'),
            (15, 'Targets List', 'targets/list.html', '^/targets/list.html$', 'targets.php'),
            (16, 'Target New', 'targets/new.html', '^/targets/new.html$', 'targets.php'),
            (17, 'Target Edit', 'targets/edit-[id].html', '^/targets/edit-([0-9]+).html$', 'targets.php'),
            (18, 'Ports List', 'ports/list.html', '^/ports/list.html$', 'ports.php'),
            (19, 'Port New', 'ports/new.html', '^/ports/new.html$', 'ports.php'),
            (20, 'Port Edit', 'ports/edit-[id].html', '^/ports/edit-([0-9]+).html$', 'ports.php'),
            (21, 'Protocols List', 'protocols/list.html', '^/protocols/list.html$', 'protocols.php'),
            (22, 'Protocol New', 'protocols/new.html', '^/protocols/new.html$', 'protocols.php'),
            (23, 'Protocol Edit', 'protocols/edit-[id].html', '^/protocols/edit-([0-9]+).html$', 'protocols.php'),
            (24, 'Service Levels List', 'service-levels/list.html', '^/service-levels/list.html$', 'service_levels.php'),
            (25, 'Service Level New', 'service-levels/new.html', '^/service-levels/new.html$', 'service_levels.php'),
            (26, 'Service Level Edit', 'service-levels/edit-[id].html', '^/service-levels/edit-([0-9]+).html$', 'service_levels.php'),
            (27, 'Options', 'options.html', '^/options.html$', 'options.php'),
            (28, 'Users List', 'users/list.html', '^/users/list.html$', 'users.php'),
            (29, 'User New', 'users/new.html', '^/users/new.html$', 'users.php'),
            (30, 'User Edit', 'users/edit-[id].html', '^/users/edit-([0-9]+).html$', 'users.php'),
            (31, 'Interfaces List', 'interfaces/list.html', '^/interfaces/list.html$', 'interfaces.php'),
            (32, 'Interface New', 'interfaces/new.html', '^/interfaces/new.html$', 'interfaces.php'),
            (33, 'Interface Edit', 'interfaces/edit-[id].html', '^/interfaces/edit-([0-9]+).html$', 'interfaces.php'),
            (34, 'Network Paths List', 'network-paths/list.html', '^/network-paths/list.html$', 'network_paths.php'),
            (35, 'Network Path New', 'network-paths/new.html', '^/network-paths/new.html$', 'network_paths.php'),
            (36, 'Network Path Edit', 'network-paths/edit-[id].html', '^/network-paths/edit-([0-9]+).html$', 'network_paths.php'),
            (37, 'Rules', 'rules.html', '^/rules.html$', 'rules.php'),
            (38, 'Others', 'others.html', '^/others.html$', 'others.php'),
            (39, 'Rules Load', 'rules/load.html', '^/rules/load.html$', 'ruleset.php'),
            (40, 'Rules Load Debug', 'rules/load-debug.html', '^/rules/load-debug.html$', 'ruleset.php'),
            (41, 'Rules Unload', 'rules/unload.html', '^/rules/unload.html$', 'ruleset.php'),
            (42, 'Rules Show', 'rules/show.html', '^/rules/show.html$', 'ruleset.php'),
            (43, 'Others About', 'others/about.html', '^/others/about.html$', 'about.php'),
            (44, 'Monitoring Chains', 'monitoring/chains.html', '^/monitoring/chains.html$', 'monitor.php'),
            (46, 'Monitoring Pipes', 'monitoring/pipes.html', '^/monitoring/pipes.html$', 'monitor.php'),
            (48, 'Monitoring Bandwidth', 'monitoring/bandwidth.html', '^/monitoring/bandwidth.html$', 'monitor.php'),
            (50, 'Monitoring', 'monitoring/mode.html', '^/monitoring/mode.html$', 'monitor.php'),
            (51, 'RPC Call', 'rpc.html', 'rpc.html', '[internal]'),
            (52, 'Host Profiles List', 'host-profiles/list.html', '^/host-profiles/list.html$', 'host_profiles.php'),
            (53, 'Host Profile New', 'host-profiles/new.html', '^/host-profiles/new.html$', 'host_profiles.php'),
            (54, 'Host Profile Edit', 'host-profiles/edit-[id].html', '^/host-profiles/edit-([0-9]+).html$', 'host_profiles.php')
         ");
      }

      if(!$this->db_check_table_exists(MYSQL_PREFIX . 'meta')) {
         $this->db_query("
            CREATE TABLE `". MYSQL_PREFIX ."meta` (
               `meta_idx` int(11) NOT NULL auto_increment,
               `meta_key` varchar(255) default NULL,
               `meta_value` varchar(255) default NULL,
               PRIMARY KEY  (`meta_idx`),
               UNIQUE KEY `meta_key` (`meta_key`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8;
         ");
         $this->setVersion(SCHEMA_VERSION);
      }

   } // install_schema()

   private function upgrade_schema()
   {
      if($this->schema_version < 2) {

         $this->db_query("
            RENAME TABLE
               ". MYSQL_PREFIX ."assign_filters
            TO
               ". MYSQL_PREFIX ."assign_filters_to_pipes,
               ". MYSQL_PREFIX ."assign_l7_protocols
            TO
               ". MYSQL_PREFIX ."assign_l7_protocols_to_filters,
               ". MYSQL_PREFIX ."assign_ports
            TO
               ". MYSQL_PREFIX ."assign_ports_to_filters;
         ");

         $this->setVersion(2);
      }

      if($this->schema_version < 3) {

         $this->db_query("
            INSERT INTO
               ". MYSQL_PREFIX ."assign_pipes_to_chains
            SELECT
               pipe_idx, pipe_chain_idx
            FROM
               ". MYSQL_PREFIX ."pipes
         ");

         $this->db_query("
            ALTER TABLE
               ". MYSQL_PREFIX ."pipes
            DROP
               pipe_chain_idx
         ");

         $this->setVersion(3);

      }

      if($this->schema_version < 4) {

         $this->db_query("
            ALTER TABLE
               ". MYSQL_PREFIX ."assign_pipes_to_chains
            ADD
               apc_pipe_pos int(11) default NULL
         ");

         $this->setVersion(4);
      }

      if($this->schema_version < 5) {
         // introduce table MYSQL_PREFIX .'pages'
         $this->setVersion(5);
      }

      if($this->schema_version < 6) {

         /* correct incorrectly named logout-page */
         $this->db_query("
            UPDATE
               ". MYSQL_PREFIX ."pages
            SET
               page_name='Logout'
            WHERE
               page_id LIKE '4'
            AND
               page_name LIKE 'Login'
         ");

         $this->setVersion(6);

      }

      if($this->schema_version < 7) {

         $this->db_query("
            ALTER TABLE
               ". MYSQL_PREFIX ."filters
            ADD
              filter_dscp varchar(4) default NULL
            AFTER
               filter_tos
         ");

         $this->setVersion(7);

      }

      if($this->schema_version < 8) {

         $this->db_query("
            DELETE FROM
               `". MYSQL_PREFIX ."pages`
            WHERE (
                  page_id LIKE 45
               AND
                  page_name LIKE 'Monitoring Chains jqPlot'
            ) OR (
                  page_id LIKE 47
               AND
                  page_name LIKE 'Monitoring Pipes jqPlot'
            ) OR (
                  page_id LIKE 49
               AND
                  page_name LIKE 'Monitoring Bandwidth jqPlot'
            )
         ");

         $this->setVersion(8);

      }

      if($this->schema_version < 9) {

         $this->db_query("
            ALTER TABLE
               ". MYSQL_PREFIX ."interfaces
            ADD
               if_fallback_idx int(11) default NULL
            AFTER
               if_speed
         ");

         $this->setVersion(9);

      }

      if($this->schema_version < 10) {

         $this->db_query("
            ALTER TABLE
               ". MYSQL_PREFIX ."assign_pipes_to_chains
            ADD
               apc_sl_idx int(11) NOT NULL
            AFTER
               apc_chain_idx
         ");

         $this->db_query("
            UPDATE
               ". MYSQL_PREFIX ."assign_pipes_to_chains
            SET
               apc_sl_idx = '0'
         ");

         $this->setVersion(10);

      }

      if($this->schema_version < 11) {

         $this->db_query("
            ALTER TABLE
               ". MYSQL_PREFIX ."assign_pipes_to_chains
            ADD
               apc_pipe_active char(1) default NULL
            AFTER
               apc_sl_idx
         ");

         $this->db_query("
            UPDATE
               ". MYSQL_PREFIX ."assign_pipes_to_chains
            SET
               apc_pipe_active = 'Y'
         ");

         $this->setVersion(11);

      }

      if($this->schema_version < 12) {

         $this->db_query("
            ALTER TABLE
               ". MYSQL_PREFIX ."pipes
            DROP
              pipe_position
         ");

         $this->setVersion(12);

      }

      if($this->schema_version < 13) {

         $this->db_query("
            ALTER TABLE
               ". MYSQL_PREFIX ."assign_pipes_to_chains
            DROP PRIMARY KEY
         ");

         $this->db_query("
            ALTER TABLE
               ". MYSQL_PREFIX ."assign_pipes_to_chains
            ADD
              `apc_idx` int(11) NOT NULL auto_increment PRIMARY KEY
            FIRST
         ");

         $this->db_query("
            ALTER TABLE
               ". MYSQL_PREFIX ."assign_pipes_to_chains
            ADD
               KEY `apc_pipe_to_chain`  (`apc_pipe_idx`,`apc_chain_idx`)
         ");

         $this->setVersion(13);

      }

      if($this->schema_version < 14) {

         // install new tables
         $this->install_tables();

         $this->db_query("
            ALTER TABLE
               ". MYSQL_PREFIX ."chains
            ADD
              `chain_host_idx` int(11) default NULL
         ");

         $this->db_query("
            UPDATE
               ". MYSQL_PREFIX ."chains
            SET
               chain_host_idx=1
         ");

         $this->db_query("
            ALTER TABLE
               ". MYSQL_PREFIX ."interfaces
            ADD
              `if_host_idx` int(11) default NULL
         ");

         $this->db_query("
            UPDATE
               ". MYSQL_PREFIX ."interfaces
            SET
              if_host_idx=1
         ");

         $this->db_query("
            ALTER TABLE
               ". MYSQL_PREFIX ."network_paths
            ADD
              `netpath_host_idx` int(11) default NULL
         ");

         $this->db_query("
            UPDATE
               ". MYSQL_PREFIX ."network_paths
            SET
               netpath_host_idx=1
         ");

         $this->db_query("
            ALTER TABLE
               ". MYSQL_PREFIX ."stats
            ADD
              `stat_host_idx` int(11) default NULL
         ");

         $this->db_query("
            UPDATE
               ". MYSQL_PREFIX ."stats
            SET
               stat_host_idx=1
         ");

         $this->db_query("
            ALTER TABLE
               ". MYSQL_PREFIX ."tc_ids
            ADD
              `id_host_idx` int(11) default NULL
         ");

         $this->db_query("
            UPDATE
               ". MYSQL_PREFIX ."tc_ids
            SET
               id_host_idx=1
         ");

         $this->db_query("
            INSERT INTO ". MYSQL_PREFIX ."pages (
               page_id,
               page_name,
               page_uri,
               page_uri_pattern,
               page_includefile
            ) VALUES
               (52, 'Host Profiles List', 'host-profiles/list.html', '^/host-profiles/list.html$', 'host_profiles.php'),
               (53, 'Host Profile New', 'host-profiles/new.html', '^/host-profiles/new.html$', 'host_profiles.php'),
               (54, 'Host Profile Edit', 'host-profiles/edit-[id].html', '^/host-profiles/edit-([0-9]+).html$', 'host_profiles.php')
         ");

         $this->setVersion(14);

      }

      if($this->schema_version < 15) {

         $this->db_query("
            ALTER TABLE
               ". MYSQL_PREFIX ."service_levels
            ADD
               sl_sfq_perturb varchar(255) default NULL
            AFTER
               sl_qdisc,
            ADD
               sl_sfq_quantum varchar(255) default NULL
            AFTER
               sl_sfq_perturb
         ");

         $this->db_query("
            UPDATE
               ". MYSQL_PREFIX ."service_levels
            SET
               sl_sfq_perturb = '10',
               sl_sfq_quantum = '1532'
         ");

         $this->setVersion(15);

      }

   } // upgrade_schema()

   /**
    * quoting function
    *
    * uses MDB2 own quote function to _secure_ an object
    *
    * @param string $obj
    * @return $string
    */
   public function quote($obj)
   {
      if(is_numeric($obj))
         return $this->db->quote($obj, 'int');
      if(is_string($obj))
         return $this->db->quote($obj, 'text');

      return $this->db->quote($obj);

   } // quote()

}

?>
