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
	 * @param array|null $args
	 * @return void
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
	 * @param array|null $args
	 * @return void
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
		if(isset($args['model_path']) && $args['model_path'] !== null) {
			self::private_load_models($args['model_path']);
		}
	}

	/**
	 * Load model files from a directory
	 *
	 * @param string $path
	 * @return void
	 */
	final public static function load_models(string $path)
	{
		self::private_load_models($path);
	}

	/**
	 * Gets the current table name
	 *
	 * @return string|null
	 */
	protected static function get_table()
	{
		if(isset(static::$table_name)) { return static::$table_name; }
		return static::$table ?? static::$tbl;
	}

	/**
	 * Returns a single row in the configured fetch mode
	 *
	 * @param \PDOStatement $stmt
	 * @param int|null $fetch_mode
	 * @return object|array|false
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
	 * Set the PDO fetch mode for subsequent queries
	 *
	 * @param int|null $mode  e.g. PDO::FETCH_OBJ or PDO::FETCH_ASSOC; null resets to PDO::FETCH_OBJ
	 * @return int            the resolved fetch mode
	 */
	public static function set_fetch_mode(?int $mode)
	{
		if(!in_array($mode, [PDO::FETCH_ASSOC, PDO::FETCH_OBJ, PDO::FETCH_BOTH, PDO::FETCH_NUM, PDO::FETCH_NAMED, PDO::FETCH_LAZY])) {
			$mode = PDO::FETCH_OBJ;
		}
		static::$fetch_mode = $mode;
		return $mode;
	}

	/**
	 * Gets the current PDO fetch mode
	 *
	 * @return int
	 */
	public static function get_fetch_mode()
	{
		return static::$fetch_mode;
	}

	/**
	 * Gets the primary key column name
	 *
	 * @return string  defaults to 'id'
	 */
	public static function get_pk()
	{
		return static::$primary_key ?? static::$pk;
	}

	/**
	 * Execute a raw SQL query without returning results
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
	 * Build and execute a SELECT query from an ActiveRecord-style array
	 *
	 * @param array $data  query options: select, from, joins, conditions, group, having, order, limit
	 * @return \PDOStatement
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
		$conditions = [];
		if(!empty($data['conditions'])) {
			$sql .= " WHERE ".$data['conditions'][0];
			foreach($data['conditions'] as $k => $c) {
				if(0 == $k) { continue; }
				$conditions[] = $c;
			}
		}
		$postsql = '';
		if(!empty($data['group'])) {
			$postsql .= " GROUP BY ".$data['group'];
		}
		if(!empty($data['having'])) {
			$postsql .= " HAVING ".$data['having'];
		}
		if(!empty($data['order'])) { $postsql .= " ORDER BY ".$data['order']; }
		if(!empty($data['limit'])) { $postsql .= " LIMIT ".abs(intval($data['limit'])); }
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

	/**
	 * Return all rows matching a query
	 *
	 * @param array $data       query options: select, from, joins, conditions, group, having, order, limit
	 * @param int|null $fetch_mode
	 * @return array
	 */
	public static function all($data=[], $fetch_mode=NULL)
	{
		if(is_null($fetch_mode)) {
			$fetch_mode = static::get_fetch_mode();
		}
		$stmt = static::process($data);
		return $stmt->fetchAll($fetch_mode);
	}

	/**
	 * Return the first row matching a query
	 *
	 * @param array $data       query options: select, from, joins, conditions, group, having, order, limit
	 * @param int|null $fetch_mode
	 * @return object|array|false
	 */
	public static function first($data=[], $fetch_mode=NULL)
	{
		$data['limit'] = 1;
		$stmt = static::process($data);
		return static::return_data($stmt,$fetch_mode);
	}

	/**
	 * Return the last row matching a query
	 *
	 * @param array $data       query options: select, from, joins, conditions, group, having, order, limit
	 * @param int|null $fetch_mode
	 * @return object|array|false
	 */
	public static function last($data=[], $fetch_mode=NULL)
	{
		$all = static::all($data);
		return array_pop($all);
	}

	/**
	 * Return a single row from a raw SQL query
	 *
	 * @param string $sql
	 * @param array|null $args      bound parameters
	 * @param int|null $fetch_mode
	 * @return object|array|false
	 */
	public static function find_by_sql($sql, $args=NULL, $fetch_mode=NULL)
	{
		$stmt = static::run($sql,$args);
		return static::return_data($stmt,$fetch_mode);
	}

	/**
	 * Returns the underlying PDO connection
	 *
	 * @return \PDO|null
	 */
	public static function connection() : ?object
	{
		//var_dump(self::$db);
		return self::$db;
	}

	/**
	 * Returns a PdoFish instance exposing last_sql
	 *
	 * @return \PdoFish
	 */
	public static function table() : ?object
	{
		$h = new PdoFish();
		$h->last_sql = static::$last_sql;
		return $h;
	}

	/**
	 * Return all rows from a raw SQL query
	 *
	 * @param string $sql
	 * @param array|null $args      bound parameters
	 * @param int|null $fetch_mode
	 * @return array
	 */
	public static function find_all_by_sql(string $sql, $args=NULL, $fetch_mode=NULL)
	{
		if(is_null($fetch_mode)) {
			$fetch_mode = static::get_fetch_mode();
		}
		$stmt = static::run($sql,$args);
		return $stmt->fetchAll($fetch_mode);
	}

	/**
	 * Prepare and execute a SQL query
	 *
	 * @param  string $sql
	 * @param  array|null $args   bound parameters
	 * @return \PDOStatement
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
	 * Get a single record by primary key
	 *
	 * @param  mixed $id            value of the primary key
	 * @param  int|null $fetch_mode e.g. PDO::FETCH_OBJ or PDO::FETCH_ASSOC
	 * @return object|array|false
	 */
	public static function find_by_pk($id, $fetch_mode = NULL)
	{
		$sql = "SELECT * FROM `".static::get_table()."` WHERE `".static::get_pk()."`=?";
		static::$last_sql = $sql;
		$stmt = static::$db->prepare($sql);
		$stmt->execute([$id]);
		return static::return_data($stmt,$fetch_mode);
	}

	/**
	 * Find a record by primary key, or pass 'all'/'first' for legacy PHPAR-style calls
	 *
	 * @param  int|string $id                  primary key value, or 'all' / 'first'
	 * @param  array|int|null $fetch_mode      fetch mode constant, or a query array when using
	 *                                         legacy syntax: find('all', ['conditions'=>...])
	 * @return object|array|false
	 */
	public static function find($id, array|int|null $fetch_mode = NULL)
	{
		if('all' == strtolower($id)) { return static::all($fetch_mode); }
		if('first' == strtolower($id)) { return static::first($fetch_mode); }
		if(is_null($fetch_mode)) { $fetch_mode=static::$fetch_mode; }
		$field = static::$primary_key ?? 'id';
		if($fetch_mode != PDO::FETCH_OBJ) {
			return static::run("SELECT * FROM `".static::get_table()."` WHERE `".$field."` = ?", [$id])->fetch($fetch_mode);
		}
		return static::run("SELECT * FROM `".static::get_table()."` WHERE `".$field."` = ?", [$id])->fetchObject(get_called_class());
	}

	/**
	 * Get number of rows matching a query
	 *
	 * @param  array $data  query options: select, from, joins, conditions, group, having, order, limit
	 * @return int
	 */
	public static function count($data=[])
	{
		return (int) static::process($data)->rowCount();
	}

	/**
	 * Get the primary key of the last inserted record
	 *
	 * @return string
	 */
	public static function lastInsertId()
	{
		return static::$db->lastInsertId();
	}

	/**
	 * Insert a record (alias for insert())
	 *
	 * @param  array $data  column => value pairs
	 * @return string       last insert ID
	 */
	public static function create($data)
	{
		return static::insert($data);
	}

	/**
	 * Insert a record
	 *
	 * @param  array $data  column => value pairs
	 * @return string       last insert ID
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
	 * Update a record by its primary key (uses 'id' column)
	 *
	 * @param  array $data  column => value pairs to update
	 * @param  int $id      value of the id column
	 * @return int          number of affected rows
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

		$stmt = static::run("UPDATE `".static::get_table()."` SET ".$fieldDetails." WHERE `".static::get_pk()."`=?", $values);
		return $stmt->rowCount();
	}


	/**
	 * Update a record by its configured primary key
	 *
	 * @param  array $data  column => value pairs to update
	 * @param  mixed $pk    value of the primary key
	 * @return int          number of affected rows
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

		$stmt = static::run("UPDATE `".static::get_table()."` SET ".$fieldDetails." WHERE `".static::get_pk()."`=?", $values);
		return $stmt->rowCount();
	}

	/**
	 * Update records matching a WHERE clause
	 *
	 * @param  array $data   column => value pairs to update
	 * @param  array $where  column => value pairs for the WHERE clause
	 * @return int           number of affected rows
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
	 * Delete the current model instance by its id property
	 *
	 * @return static
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
	 * Delete records matching a WHERE clause
	 *
	 * @param  array $where     column => value pairs for the WHERE clause
	 * @param  int|null $limit  limit number of records deleted
	 * @return int              number of affected rows
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
	 * Delete records using an ActiveRecord-style query array
	 *
	 * @param  array $data  query options: from, conditions, limit
	 * @return void
	 */
	public static function delete_all($data)
	{
		$static_table = static::get_table();
		if(!isset($data['from']) && isset($static_table)) {
			$data['from'] = $static_table;
		}
		if(!isset($data['from'])) { return; }
		$sql = "DELETE FROM ".$data['from']." ";
		$conditions = [];
		if(!empty($data['conditions'])) {
			$sql .= " WHERE ".$data['conditions'][0];
			foreach($data['conditions'] as $k => $c) {
				if(0 == $k) { continue; }
				$conditions[] = $c;
			}
		}
		if(!empty($data['limit'])) { $sql .= " LIMIT ".abs(intval($data['limit'])); }
		static::$last_sql = $sql;
		if(!empty($conditions)) {
			$stmt = static::$db->prepare($sql);
			$stmt->execute($conditions);
		} else {
			$stmt = static::$db->query($sql);
		}

	}


	/**
	 * Delete a record by the 'id' column
	 *
	 * @param  mixed $id  value of the id column
	 * @return int        number of affected rows
	 */
	public static function delete_by_id($id)
	{
		$stmt = static::run("DELETE FROM `".static::get_table()."` WHERE id = ?", [$id]);
		return $stmt->rowCount();
	}

	/**
	 * Delete a record by the 'id' column (camelCase alias of delete_by_id())
	 *
	 * @param  mixed $id  value of the id column
	 * @return int        number of affected rows
	 */
	public static function deleteById($id)
	{
		return self::delete_by_id($id);
	}

	/**
	 * Delete a record by the configured primary key
	 *
	 * @param  mixed $pk  value of the primary key
	 * @return int        number of affected rows
	 */
	public static function delete_by_pk($pk)
	{
		$stmt = static::run("DELETE FROM `".static::get_table()."` WHERE `".static::get_pk()."` = ?", [$pk]);
		return $stmt->rowCount();
	}

	/**
	 * Delete multiple records where a column's value is in a list
	 *
	 * @param  string $column        column name
	 * @param  string|array $ids     comma-separated string or array of values
	 * @return int                   number of affected rows
	 */
	public static function deleteMany(string $column, $ids)
	{
		$idArray = is_array($ids) ? array_values($ids) : array_map('trim', explode(',', $ids));
		$placeholders = implode(',', array_fill(0, count($idArray), '?'));
		$stmt = static::run("DELETE FROM `".static::get_table()."` WHERE `$column` IN ($placeholders)", $idArray);
		return $stmt->rowCount();
	}



	/**
	 * Delete records where a single column equals a value
	 *
	 * @param  string $column  column name
	 * @param  mixed $val      value to match
	 * @return \PDOStatement
	 */
	public static function delete_by_column($column, $val) {
		return static::run("DELETE FROM `".static::get_table()."` WHERE `".$column."` = ?", [$val]);
	}

	/**
	 * Truncate a table — must be called via the PdoFish class, not a child model
	 *
	 * @param  string $table  table name
	 * @return int|false      number of affected rows, or false if called on a child class
	 */
	final public static function truncate($table)
	{
		if('PdoFish'!=get_called_class()) { return false; }
		$stmt = static::run("TRUNCATE TABLE `".$table."`");
		return $stmt->rowCount();
	}

	/**
	 * Insert or update the current object as a record (ActiveRecord-style)
	 *
	 * @param  int|null $debug  pass 1 to var_dump the object instead of saving
	 * @return object|false|void
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
		if(!empty($data['id'])) {
			unset($data['id']);
			self::update_by_id($data,$this->id);
			return (object) $data;
		}
		// otherwise, insert as new record
		static::insert($data);
		return (object) $data;
	}

	/**
	 * Handle dynamic find_by_[field]() and find_all_by_[field]() calls
	 *
	 * @param  string $name   method name
	 * @param  array $args    method arguments; $args[0] is the value to match
	 * @return object|array|false|null
	 */
	public static function __callStatic ( string $name , array $args )
	{
		# one record
		if (preg_match('/^find_by_(.+)/', $name, $matches)) {
			$var_name = $matches[1];
			$sql = "SELECT * FROM `".static::get_table()."` WHERE `".$var_name."`=?";
			$stmt = static::$db->prepare($sql);
			$stmt->execute([ $args[0] ]);
			return static::return_data($stmt, null);
		}
		# multiple records
		if (preg_match('/^find_all_by_(.+)/', $name, $matches)) {
			$var_name = $matches[1];
			$sql = "SELECT * FROM `".static::get_table()."` WHERE `".$var_name."`=?";
			$stmt = static::$db->prepare($sql);
			$stmt->execute([ $args[0] ]);
			return $stmt->fetchAll(static::get_fetch_mode());
		}
		return null;
	}

	/**
	 * Include all PHP model files from a directory
	 *
	 * @param  string $path
	 * @return void
	 */
	private static function private_load_models($path) {
		if('/' != substr($path,-1)) { $path .= "/"; }
		if(is_dir($path)) {
			foreach(glob($path.'*.php') as $filename) {
				@include_once $filename;
			}
		}
		return;
	}

	/**
	 * Initialize the singleton connection and return the PdoFish instance
	 *
	 * @param  array $pdo_options  same keys as initialize()
	 * @return static
	 */
	static function startup($pdo_options) {
		if (static::$instance == null) {
			static::$instance = new PdoFish();
			static::$instance->initialize($pdo_options);
		}
		return(static::$instance);
	}
}
