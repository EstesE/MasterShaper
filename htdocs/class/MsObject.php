<?php

/**
 * @package MasterShaper
 * @subpackage MsObject
 */

class MsObject {

   var $table_name;
   var $col_name;
   var $child_names;
   var $fields;

   public function __construct($id = null, $init_data)
   {
      global $ms;

      if(!is_array($init_data))
         $ms->throwError('require array as second __construct() parameter');

      if(!array_key_exists('table_name', $init_data))
         $ms->throwError('missing key table_name');

      if(!array_key_exists('col_name', $init_data))
         $ms->throwError('missing key col_name');

      if(!array_key_exists('fields', $init_data))
         $ms->throwError('missing key fields');

      $this->table_name = $init_data['table_name'];
      $this->col_name = $init_data['col_name'];
      $this->fields = $init_data['fields'];

      if(array_key_exists('child_names', $init_data))
         $this->child_names = $init_data['child_names'];

      if(isset($id)) {
         $this->id = $id;
         $this->load();
         return;
      }

   } // __construct()

   /**
    * load
    *
    */
   private function load()
   {  
      global $ms, $db;

      $sth = $db->db_prepare("
         SELECT
            *
         FROM
            ". MYSQL_PREFIX . $this->table_name ."
         WHERE 
            ". $this->col_name ."_idx LIKE ?
      ", array('integer'));

      $res = $db->db_execute($sth, array(
         $this->id,
      ));

      if($res->numRows() <= 0)
         $ms->throwError("No object with id ". $this->id);

      if(!$row = $res->fetchRow(MDB2_FETCHMODE_ASSOC))
         $ms->throwError("Unable to fetch SQL result for object id ". $this->id);

      foreach($row as $key => $value)
         $this->$key = $value;

   } // load();

   /**
    * update object variables via array
    *
    * @param mixed $data
    * @return bool
    */
   public function update($data)
   {
      if(!is_array($data))
         return false;

      foreach($data as $key => $value) {
         $this->$key = $value;
      }

      return true;

   } // update()

   /**
    * delete
    */
   public function delete()
   {
      global $db;

      if(!isset($this->id))
         return false;
      if(!is_numeric($this->id))
         return false;
      if(!isset($this->table_name))
         return false;
      if(!isset($this->col_name))
         return false;

      /* generic delete */
      $sth = $db->db_prepare("
         DELETE FROM
            ". MYSQL_PREFIX . $this->table_name ."
         WHERE
            ". $this->col_name ."_idx LIKE ?
      ");

      $db->db_execute($sth, array(
         $this->id
      ));

      if(method_exists($this, 'post_delete'))
         $this->post_delete();

      return true;

   } // delete()

   /* overloading PHP's __set() function */
   public function __set($name, $value)
   {
      global $ms;

      if(!isset($this->fields) || empty($this->fields))
         $ms->throwError("Fields error not set for class ". get_class($this));

      if(!array_key_exists($name, $this->fields) && $name != 'id')
         $ms->throwError("Unknown key in ". get_class($this) ."::__set(): ". $name);

      $this->$name = $value;

   } // __set()

   public function save()
   {
      global $ms, $db;
      
      if(!isset($this->fields) || empty($this->fields))
         $ms->throwError('fields not probably initialized');

      if(method_exists($this, 'pre_save'))
         $this->pre_save();

      /* new object */
      if(!isset($this->id)) {
         $sql = 'INSERT INTO ';
      }
      /* existing object */
      else
         $sql = 'UPDATE ';

      $sql.= MYSQL_PREFIX . $this->table_name .' SET ';

      $arr_values = Array();

      foreach(array_keys($this->fields) as $key) {
         $sql.= $key ." = ?, ";
         $arr_values[] = $this->$key;
      }
      $sql = substr($sql, 0, strlen($sql)-2) .' ';

      if(!isset($this->id)) {
         $idx_name = $this->col_name .'_idx';
         $this->$idx_name = 'NULL';
      }
      else {
         $sql.= 'WHERE '. $this->col_name .'_idx LIKE ?';
         $arr_values[] = $this->id;
      }

      $sth = $db->db_prepare($sql, array_values($this->fields));

      $db->db_execute($sth, $arr_values);

      if(!isset($this->id))
         $this->id = $db->db_getid();

      if(method_exists($this, 'post_save'))
         $this->post_save();

      return true;

   } // save()

   public function toggle_status($to)
   {
      global $db;

      if(!isset($this->id))
         return false;
      if(!is_numeric($this->id))
         return false;
      if(!isset($this->table_name))
         return false;
      if(!isset($this->col_name))
         return false;
      if(!in_array($to, Array('off', 'on')))
         return false;

      if($to == "on")
         $new_status = 'Y';
      elseif($to == "off")
         $new_status = 'N';

      $sth = $db->db_prepare("
         UPDATE
            ". MYSQL_PREFIX . $this->table_name ."
         SET
            ". $this->col_name ."_active = ?
         WHERE
            ". $this->col_name ."_idx LIKE ?
      ");

      $db->db_execute($sth, array(
         $new_status,
         $this->id
      ));

      return true;

   } // toggle_status()

   public function toggle_child_status($to, $child_obj, $child_id)
   {
      global $db, $ms;

      if(!isset($this->child_names)) {
         $ms->throwError("This object has no childs at all!");
         return false;
      }
      if(!isset($this->child_names[$child_obj])) {
         $ms->throwError("Requested child is not known to this object!");
         return false;
      }

      $prefix = $this->child_names[$child_obj];

      if(!($child_obj = $ms->load_class($child_obj, $child_id))) {
         $ms->throwError("unable to locate class for ". $child_obj);
         return false;
      }

      if(!isset($this->id))
         return false;
      if(!is_numeric($this->id))
         return false;
      if(!isset($this->table_name))
         return false;
      if(!isset($this->col_name))
         return false;
      if(!in_array($to, Array('off', 'on')))
         return false;

      if($to == "on")
         $new_status = 'Y';
      elseif($to == "off")
         $new_status = 'N';

      $sth = $db->db_prepare("
         UPDATE
            ". MYSQL_PREFIX ."assign_". $child_obj->table_name ."_to_". $this->table_name ."
         SET
            ". $prefix ."_". $child_obj->col_name ."_active = ?
         WHERE
            ". $prefix ."_". $this->col_name ."_idx LIKE ?
         AND
            ". $prefix ."_". $child_obj->col_name ."_idx LIKE ?
      ");

      $db->db_execute($sth, array(
         $new_status,
         $this->id,
         $child_id
      ));

      return true;

   } // toggle_child_status()

} // MsObject
