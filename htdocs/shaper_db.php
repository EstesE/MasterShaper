<?php

define('VERSION', '0.60');

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
   private $parent;
   private $is_connected;
   private $last_error;

   /**
    * MASTERSHAPER_DB class constructor
    *
    * This constructor initially connect to the database.
    */
   public function __construct($parent)
   {
      $this->parent = $parent;

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
      $this->db_disconnect();

   } // _destruct()

   /**
    * MASTERSHAPER_DB database connect
    *
    * This function will connect to the database via MDB2
    */
   private function db_connect()
   {
      $options = array(
         'debug' => 2,
         'portability' => 'DB_PORTABILITY_ALL'
      );

      $dsn = "mysql://". MYSQL_USER .":". MYSQL_PASS ."@". MYSQL_HOST ."/". MYSQL_DB;
      $this->db = MDB2::connect($dsn, $options);

      if(PEAR::isError($this->db)) {
         $this->throwError("Unable to connect to database: ". $this->db->getMessage());
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
      if($this->getConnStatus()) {

         $this->db->setFetchMode($mode);
         $result = $this->db->query($query);
			
         if(PEAR::isError($result))
            $this->throwError($result->getMessage());
	
         return $result;
      }
      else 
         $this->ThrowError("Can't execute query - we are not connected!");

   } // db_query()

   /**
    * MASTERSHAPER_DB fetch ONE row
    *
    * This function will execute the given but only return the
    * first result.
    */
   public function db_fetchSingleRow($query = "") 
   {
      if($this->getConnStatus()) {

         $result = $this->db_query($query);
         $row = $result->fetchRow();
         return $row;
      }
      else 
         $this->ThrowError("Can't fetch row - we are not connected!");
      
   } // db_fetchSingleRow()

   /**
    * MASTERSHAPER_DB number of affected rows
    *
    * This functions returns the number of affected rows but the
    * given SQL query.
    */
   public function db_getNumRows($query = "")
   {
      /* Execute query */
      $result = $this->db_query($query);

      /* Errors? */
      if(PEAR::isError($result)) 
         $this->throwError($result->getMessage());

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
      if($this->getConnStatus()) {
         $result = $this->db_query("SHOW TABLES");
         $tables_in = "Tables_in_". MYSQL_DB;
	
         while($row = $result->fetchRow()) {
            if($row->$tables_in == $table_name)
               return true;
         }
         return false;
      }
      else
         $this->ThrowError("Can't check table - we are not connected!");
	 
   } // db_check_table_exists()

   /**
    * MASTERSHAPER_DB rename table
    * 
    * This function will rename an database table
    * @param old_name, new_name
    */
   public function db_rename_table($old, $new)
   {
      if($this->db_check_table_exists($old)) {
         if(!$this->db_check_table_exists($new))
            $this->db_query("RENAME TABLE ". $old ." TO ". $new);
         else
            $this->ThrowError("Can't rename table ". $old ." - ". $new ." already exists!");
      }
	 
   } // db_rename_table()

   /**
    * MASTERSHAPER_DB drop table
    *
    * This function will delete the given table from database
    */
   public function db_drop_table($table_name)
   {
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
      if($this->db_check_table_exists($table_name)) {

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
      }

   } // db_alter_table()

   /**
    * MASTERSHAPER_DB get MasterShaper Version
    *
    * This functions returns the current MasterShaper (DB) version
    */
   public function getVersion()
   {
      if($this->db_check_table_exists(MYSQL_PREFIX ."settings")) {
         $result = $this->db_fetchSingleRow("
            SELECT setting_value 
            FROM ". MYSQL_PREFIX ."settings 
            WHERE setting_key LIKE 'version'
         ");
         return $result->setting_value;
      }
      else
         return 0;
	 
   } // getVersion()

   /**
    * MASTERSHAPER_DB set version
    *
    * This function sets the version name of MasterShaper (DB)
    */
   public function setVersion($version)
   {
      $this->db_query("
         REPLACE INTO ". MYSQL_PREFIX ."settings 
            (setting_key, setting_value)
         VALUES ('version', '". $version ."')
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

   /**
    * MASTERSHAPER_DB throw error
    *
    * This function shows up error messages and afterwards through exceptions.
    */
   private function ThrowError($string)
   {
      if(!defined('DB_NOERROR'))  {
         print "<br /><br />". $string ."<br /><br />\n";
         try {
            throw new Exception;
         }
         catch(Exectpion $e) {
         }
      }

      $this->last_error = $string;
	 
   } // ThrowError()

}

?>
