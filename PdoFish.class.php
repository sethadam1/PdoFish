<?php

/**
 * PdoFish, a wrapper for PDO
 * PHP support for ActiveRecord-style syntax 
 * modeled after phpActiveRecord
 */

class PdoFish
{
	// database connection
	static $db;
	// class instance
	static private $instance = null;
	// current table
	static $tbl = null;
	// primary key, defaults to 'id'
	static $pk = 'id';
	// stores last SQL query 
	static $last_sql = null;
	// default return type, which defaults to object
	static $fetch_mode = PDO::FETCH_OBJ;

	/**
	 * Setup
	 *
	 * @param array $args
	 */
	public function __construct($args=null)
	{
		if(is_array($args)) {
			foreach($args as $k=>$v) {
				$this->$k = $v;
			}
		}
	}

	/**
	 * Connection details
	 *
	 * @param array $args
	 */
	public function initialize($args=null)
	{
		if (!isset($args['database'])) {
			throw new Exception('PdoFish requires a database name');
		}

		if (!isset($args['username'])) {
			throw new Exception('PdoFish requires a database username');
		}

		$type     = $args['type'] ?? 'mysql'; 		// default to mysql
		$host     = $args['host'] ?? 'localhost';	// default: localhost
		$charset  = $args['charset'] ?? 'utf8';		// default: utf-8
		$password = $args['password'] ?? '';
		$database = $args['database'];
		$username = $args['username'];
		$port     = isset($args['port']) ? 'port=' . $args['port'] . ';' : '';
		self::$db = new PDO("$type:host=$host;$port"."dbname=$database;charset=$charset", $username, $password);
		self::$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		if($args['model_path'] && !is_null($args['model_path'])) {
			self::private_load_models($args['model_path']);
		}
	}

	/**
	 * load_models
	 *
	 * @param string $path
	 */
	final public static function load_models(string $path)
	{
		self::private_load_models($path);
	}

	/**
	 * Gets the current table
	 *
	 * @return current table, defaults to null
	 */
	protected static function get_table()
	{
		if(isset(static::$table_name)) { return static::$table_name; }
		return static::$table ?? static::$tbl;
	}

	/**
	 * Returns data in the proper format
	 *
	 */
	public static function return_data($stmt, $fetch_mode=NULL)
	{
		if(is_null($fetch_mode)) { $fetch_mode=static::set_fetch_mode($fetch_mode); }
		if($fetch_mode != PDO::FETCH_OBJ) {
			return $stmt->fetch($fetch_mode);
		}
		return $stmt->fetchObject(get_called_class());
	}

	/**
	 * Set the PDO return type
	 *
	 * @return void
	 */
	public static function set_fetch_mode($mode)
	{
		if(!in_array($mode, [PDO::FETCH_ASSOC, PDO::FETCH_OBJ, PDO::FETCH_BOTH, PDO::FETCH_NUM, PDO::FETCH_NAMED, PDO::FETCH_LAZY])) {
			$mode = PDO::FETCH_OBJ;
		}
		static::$fetch_mode = $mode;
		return $mode;
	}

	/**
	 * Gets the current PDO return type
	 *
	 * @return current table, defaults to null
	 */
	public static function get_fetch_mode()
	{
		return static::$fetch_mode;
	}

	/**
	 * Gets the primary key
	 *
	 * @return current primary key, defaults to 'id'
	 */
	public static function get_pk()
	{
		return static::$primary_key ?? static::$pk;
	}

	/**
	 * Execute a sql query
	 *
	 * @param string $sql
	 * @return void
	 */
	public static function raw($sql)
	{
		static::$last_sql = $sql;
		static::$db->query($sql);
	}

	/**
	 * Parse a SQL query, ActiveRecord style
	 *
	 * @param array $data
	 * @return stmt resource
	 */
	private static function process(array $data)
	{
		$static_table = static::get_table();
		if(!isset($data['from']) && isset($static_table)) {
			$data['from'] = $static_table;
		}
		$select = $data['select'] ?? "*";
		$sql = "SELECT ".$select." FROM ".$data['from']."";

		if(isset($data['joins'])) { $sql .= " ".$data['joins']; }
		if(!empty($data['conditions'])) {
			$sql .= " WHERE ".$data['conditions'][0];
			foreach($data['conditions'] as $k => $c) {
				if(0 == $k) { continue; }
				$conditions[] = $c;
			}
		}
		if($data['group']) {
			$postsql .= " GROUP BY ".$data['group'];
		}
		if($data['having']) {
			$postsql .= " HAVING ".$data['having'];
		}
		if($data['order']) { $postsql .= " ORDER BY ".$data['order']; }
		if($data['limit']) { $postsql .= " LIMIT ".abs(intval($data['limit'])); }
		// uncomment next line for SQL debugger
		// error_log($sql." ".$postsql);
		static::$last_sql = $sql." ".$postsql;
		if(!empty($conditions)) {
			$stmt = static::$db->prepare($sql." ".$postsql);
			$stmt->execute($conditions);
		} else {
			$stmt = static::$db->query($sql." ".$postsql);
		}
		return $stmt;
	}

	public static function all($data=[], $fetch_mode=NULL)
	{
		if(is_null($fetch_mode)) {
			$fetch_mode = static::get_fetch_mode();
		}
		$stmt = static::process($data);
		return $stmt->fetchAll($fetch_mode);
	}

	public static function first($data=[], $fetch_mode=NULL)
	{
		$data['limit'] = 1;
		$stmt = static::process($data);
		return static::return_data($stmt,$fetch_mode);
	}
	
	public static function last($data=[], $fetch_mode=NULL)
	{
		$all = static::all($data); 
		return array_pop($all); 
	}

	public static function find_by_sql($sql, $args=NULL, $fetch_mode=NULL)
	{
		$stmt = static::run($sql,$args);
		return static::return_data($stmt,$fetch_mode);
	}
	
	public static function connection() : ?object 
	{
		//var_dump(self::$db);
		return self::$db;
	}
	
	public static function table() : ?object 
	{
		$h = new PdoFish();  
		$h->last_sql = static::$last_sql;
		return $h; 
	}

	public static function find_all_by_sql($sql, $args=NULL, $fetch_mode=NULL)
	{
		if(is_null($fetch_mode)) {
			$fetch_mode = static::get_fetch_mode();
		}
		$stmt = static::run($sql,$args);
		return $stmt->fetchAll($fetch_mode);
	}

	/**
	 * Run sql query
	 *
	 * @param  string $sql       sql query
	 * @param  array  $args      params
	 * @return object            returns a PDO object
	 */
	public static function run($sql, $args = [])
	{
		static::$last_sql = $sql;
		if (empty($args)) {
			return static::$db->query($sql);
		}
		$stmt = static::$db->prepare($sql);  
		$stmt->execute($args);

		return $stmt;
	}

	/**
	 * Get record by primary key
	 *
	 * @param  integer $id       	id or content of record
	 * @param  object $fetch_mode 	set return mode, e.g. PDO::FETCH_OBJ or PDO::FETCH_ASSOC
	 * @return object/array			returns single record
	 */
	public static function find_by_pk($id, $fetch_mode = NULL)
	{
		$sql = "SELECT * FROM `".static::get_table()."` WHERE ".static::get_pk()."=?";
		static::$last_sql = $sql;
		$stmt = static::$db->prepare($sql);
		$stmt->execute([$id]);
		return static::return_data($stmt,$fetch_mode);
	}

	/**
	 * find
	 *
	 * @param  integer $id			id of record
	 * @param  object $fetch_mode 	set return mode, e.g. PDO::FETCH_OBJ or PDO::FETCH_ASSOC
	 * @return object/array			returns single record
	 */
	public static function find($id, $fetch_mode = NULL)
	{
		if('all' == strtolower($id)) { return static::all($fetch_mode); }
		if('first' == strtolower($id)) { return static::first($fetch_mode); }
		if(is_null($fetch_mode)) { $fetch_mode=static::$fetch_mode; }
		$field = static::$primary_key ?? 'id'; 
		if($fetch_mode != PDO::FETCH_OBJ) {
			return static::run("SELECT * FROM `".static::get_table()."` WHERE ".$field." = ?", [$id])->fetch($fetch_mode);
		}
		return static::run("SELECT * FROM `".static::get_table()."` WHERE ".$field." = ?", [$id])->fetchObject(get_called_class());
	}

	/**
	 * Get number of records
	 *
	 * @param  string $sql       sql query
	 * @param  array  $data      params
	 * @return integer           returns number of records
	 */
	public static function count($data=[])
	{
		return (int) static::process($data)->rowCount();
	}

	/**
	 * Get primary key of last inserted record
	 */
	public static function lastInsertId()
	{
		return static::$db->lastInsertId();
	}

	/**
	 * create record // an alias
	 *
	 * @param  array $data - an array of column names and values
	 */
	public static function create($data)
	{
		return static::insert($data);
	}

	/**
	 * insert record
	 *
	 * @param  array $data - an array of column names and values
	 */
	public static function insert($data)
	{
		//add columns into comma separated string
		$columns = implode(',', array_keys($data));

		//get values
		$values = array_values($data);
		if(!is_array($values)) { $values = []; } 

		$placeholders = array_map(function ($val) {
			return '?';
		}, array_keys($data));

		//convert array into comma seperated string
		$placeholders = implode(',', array_values($placeholders));
		static::run("INSERT INTO `".static::get_table()."` ($columns) VALUES ($placeholders)", $values);
		return static::lastInsertId();
	}


	/**
	 * update record
	 *
	 * @param  array $data  array of columns and values
	 * @param  int $id 
	 */
	public static function update_by_id(array $data, int $id)
	{
		// collect the values from data
		$values = array_values($data);
		$values[] = $id;

		// fields to update
		$fieldDetails = null;
		foreach ($data as $key => $value) {
			$fieldDetails .= $key." = ?,";
		}
		$fieldDetails = rtrim($fieldDetails, ',');

		$stmt = static::run("UPDATE `".static::get_table()."` SET ".$fieldDetails." WHERE id=?", $values);
		return $stmt->rowCount();
	}


	/**
	 * Update record by pk
	 *
	 * @param  mixed $pk value of primary key
	 */
	public static function update_by_pk($data, $pk)
	{
		// collect the values from data
		$values = array_values($data);
		$values[] = $pk;

		// fields to update
		$fieldDetails = null;
		foreach ($data as $key => $value) {
			$fieldDetails .= $key." = ?,";
		}
		$fieldDetails = rtrim($fieldDetails, ',');

		$stmt = static::run("UPDATE `".static::get_table()."` SET ".$fieldDetails." WHERE ".static::get_pk()."=?", $values);
		return $stmt->rowCount();
	}

	/**
	 * update record
	 *
	 * @param  array $data  array of columns and values
	 * @param  array $where array of columns and values
	 */
	public static function update($data, $where)
	{
		//merge data and where together
		$collection = array_merge($data, $where);

		//collect the values from collection
		$values = array_values($collection);

		//setup fields
		$fieldDetails = null;
		foreach ($data as $key => $value) {
			$fieldDetails .= "$key = ?,";
		}
		$fieldDetails = rtrim($fieldDetails, ',');

		//setup where
		$whereDetails = null;
		$i = 0;
		foreach ($where as $key => $value) {
			$whereDetails .= $i == 0 ? "$key = ?" : " AND $key = ?";
			$i++;
		}
		$stmt = static::run("UPDATE `".static::get_table()."` SET $fieldDetails WHERE $whereDetails", $values);
		return $stmt->rowCount();
	}

	/**
	 * delete a new, active record style
	 *
	 * @param  array $data - an array of column names and values
	 */
	public function deleteRow()
	{
		if(isset($this->id)) {
			self::delete_by_id($this->id);
			return $this;
		}
		return (object) $this;
	}

	/**
	 * Delete records
	 *
	 * @param  array $where array of columns and values
	 * @param  integer $limit limit number of records
	 */
	public static function delete($where, $limit = NULL)
	{
		//collect the values from collection
		$values = array_values($where);

		//setup where
		$whereDetails = null;
		$i = 0;
		foreach ($where as $key => $value) {
			$whereDetails .= $i == 0 ? "$key = ?" : " AND $key = ?";
			$i++;
		}

		//if limit is a number use a limit on the query
		if (is_numeric($limit)) {
			$limit = "LIMIT $limit";
		}
		$stmt = static::run("DELETE FROM `".static::get_table()."` WHERE $whereDetails", $values);
		return $stmt->rowCount();
	}

	/**
	 * Delete multiple records
	 *
	 * @param  array $conditions
	 */
	public static function delete_all($data)  
	{
		$static_table = static::get_table();
		if(!isset($data['from']) && isset($static_table)) {
			$data['from'] = $static_table;
		}
		if(!isset($data['from'])) { return; } 
		$sql = "DELETE FROM ".$data['from']." ";
		if(!empty($data['conditions'])) {
			$sql .= " WHERE ".$data['conditions'][0];
			foreach($data['conditions'] as $k => $c) {
				if(0 == $k) { continue; }
				$conditions[] = $c;
			}
		}
		if($data['limit']) { $sql .= " LIMIT ".abs(intval($data['limit'])); }
		static::$last_sql = $sql;
		if(!empty($conditions)) {
			$stmt = static::$db->prepare($sql);
			$stmt->execute($conditions);
		} else {
			$stmt = static::$db->query($sql);
		}
		
	}


	/**
	 * Delete record by id
	 *
	 * @param  integer $id id of record
	 */
	public static function delete_by_id($id)
	{
		$stmt = static::run("DELETE FROM `".static::get_table()."` WHERE id = ?", [$id]);
		return $stmt->rowCount();
	}

	public static function deleteById($id) // camel-case alias of delete_by_id
	{
		return self::delete_by_id($id);
	}

	/**
	 * Delete record by pk
	 *
	 * @param  mixed $pk value of primary key
	 */
	public static function delete_by_pk($pk)
	{
		$stmt = static::run("DELETE FROM `".static::get_table()."` WHERE ".static::get_pk()." = ?", [$pk]);
		return $stmt->rowCount();
	}

	/**
	 * Delete multiple records by a single column
	 *
	 * @param  string $column name of column
	 * @param  string or array $ids ids of records
	 */
	public static function deleteMany(string $column, $ids)
	{
		$str = (is_array($ids)) ? implode(",", $ids) : $ids;
		$stmt = static::run("DELETE FROM `".static::get_table()."` WHERE $column IN (".$str.")");
		return $stmt->rowCount();
	}



	/**
	 * Delete multiple records by a single column
	 *
	 * @param  string $column name of column
	 * @param  string $val value of column
	 */
	public static function delete_by_column($column, $val) {
		return static::run("DELETE FROM `".static::get_table()."` WHERE ".$column." = ?", $val);
	}

	/**
	 * truncate table
	 *
	 * @param  string $table table name
	 * must be called via PdoFish class
	 */
	final public static function truncate($table)
	{
		if('PdoFish'!=get_called_class()) { return false; }
		$stmt = static::run("TRUNCATE TABLE `".$table."`");
		return $stmt->rowCount();
	}

	/**
	 * create a new record, active record style
	 *
	 * @param  array $data - an array of column names and values
	 */
	public function save($debug=NULL)
	{
		if(1 == $debug) { var_dump($this); return; }
		// next lines, updating a record with a PK that isn't ID
		if(isset(static::$primary_key)) { 
			$data = (array) $this;
			if(!is_array($data)) { return false; }
			$pk = static::$primary_key; 
			if(isset($data[$pk])) { 
				$pk_val = $data[$pk];
				unset($data[$pk]);
				self::update_by_pk($data,$pk_val);
				return (object) $data;
			}
		} 
		$data = (array) $this;
		if(!is_array($data)) { return false; }
		// next lines, updating a record with a PK of ID
		if($data['id']) {
			unset($data['id']);
			self::update_by_id($data,$this->id);
			return (object) $data;
		} 
		// otherwise, insert as new record
		static::insert($data);
		return (object) $data;
	}

	/**
	 * dynamic callable
	 *
	 * @param  string $table table name
	 * must be called via PdoFish class
	 */

	public static function __callStatic ( string $name , array $args )
	{
		# one record
		if (preg_match('/^find_by_(.+)/', $name, $matches)) {
			$var_name = $matches[1];
			$sql = "SELECT * FROM `".static::get_table()."` WHERE ".$var_name."=?";
			$stmt = static::$db->prepare($sql);
			$stmt->execute([ $args[0] ]);
			return static::return_data($stmt,$fetch_mode);
		}
		# multiple records
		if (preg_match('/^find_all_by_(.+)/', $name, $matches)) {
			$var_name = $matches[1];
			$sql = "SELECT * FROM `".static::get_table()."` WHERE ".$var_name."=?";
			$stmt = static::$db->prepare($sql);
			$stmt->execute([ $args[0] ]);
			return $stmt->fetchAll(static::get_fetch_mode());
		}
	}

	private static function private_load_models($path) {
		if('/' != substr($path,-1)) { $path .= "/"; }
		if(is_dir($path)) { 
			foreach(glob($path.'*.php') as $filename) {
				@include_once $filename;
			}
		}
		return;
	}

	static function startup($pdo_options) {
		if (static::$instance == null) {
			static::$instance = new PdoFish();
			static::$instance->initialize($pdo_options);
		}
		return(static::$instance);
	}
}
