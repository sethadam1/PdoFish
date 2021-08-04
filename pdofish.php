<?php

/**
 * PdoFish, a wrapper for PDO
 * modeled after ActiveRecord and phpActiveRecord
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

	/**
	 * Connection details
	 *
	 * @param array $args
	 */
	public function __construct($args)
	{
		if (!isset($args['database'])) {
			throw new Exception('PdoFish requires database name');
		}

		if (!isset($args['username'])) {
			throw new Exception('PdoFish requires database username');
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
	}

	/**
	 * Gets the current table
	 *
	 * @return current table, defaults to null
	 */
	protected static function get_table()
	{
		return static::$table ?? static::$tbl;
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
		static::$db->query($sql);
	}

	/**
	 * Parse a SQL query, ActiveRecord style
	 *
	 * @param array $data
	 * @return stmt resource
	 */
	private static function process($data)
	{
		$static_table = static::get_table();
		if(!isset($data['from']) && isset($static_table)) {
			$data['from'] = $static_table;
		}
		$select = $data['select'] ?? "*";
		$sql = "SELECT ".$select." FROM ".$data['from'];

		if(isset($data['joins'])) { $sql .= " ".$data['joins']; }
		if(is_array($data['conditions'])) {
			$sql .= " WHERE ".$data['conditions'][0];
			foreach($data['conditions'] as $k => $c) {
				if(0 == $k) { continue; }
				$conditions[] = $c;
			}
		}

		if($data['order']) { $postsql .= " ORDER BY ".$data['order']; }
		if($data['limit']) { $postsql .= " LIMIT ".$data['limit']; }
		$stmt = static::$db->prepare($sql." ".$postsql);
		$stmt->execute($conditions);
		return $stmt;
	}

	public static function all($data, $return_type=PDO::FETCH_OBJ)
	{
		if(!in_array($return_type, [PDO::FETCH_ASSOC, PDO::FETCH_OBJ])) {
			$return_type = PDO::FETCH_OBJ;
		}
		$stmt = static::process($data);
		return $stmt->fetchAll($return_type);
	}

	public static function first($data, $return_type=PDO::FETCH_OBJ)
	{
		if(!in_array($return_type, [PDO::FETCH_ASSOC, PDO::FETCH_OBJ])) {
			$return_type = PDO::FETCH_OBJ;
		}
		$data['limit'] = 1;
		$stmt = static::process($data);
		return $stmt->fetch($return_type);
	}

	public static function find_by_sql($sql, $args=NULL, $return_type=PDO::FETCH_OBJ)
	{
		if(!in_array($return_type, [PDO::FETCH_ASSOC, PDO::FETCH_OBJ])) {
			$return_type = PDO::FETCH_OBJ;
		}
		$stmt = static::run($sql,$args);
		return $stmt->fetch($return_type);
	}

	public static function find_all_by_sql($sql, $args=NULL, $return_type=PDO::FETCH_OBJ)
	{
		if(!in_array($return_type, [PDO::FETCH_ASSOC, PDO::FETCH_OBJ])) {
			$return_type = PDO::FETCH_OBJ;
		}
		$stmt = static::run($sql,$args);
		return $stmt->fetchAll($return_type);
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
	 * @param  object $fetchMode 	set return mode, e.g. PDO::FETCH_OBJ or PDO::FETCH_ASSOC
	 * @return object/array			returns single record
	 */
	public static function find_by_pk($id, $fetchMode = PDO::FETCH_OBJ)
	{
		$sql = "SELECT * FROM ".static::get_table()." WHERE ".static::get_pk()."=?";
		$stmt = static::$db->prepare($sql);
		$stmt->execute([$id]);
		return $stmt->fetch($fetchMode);
	}

	/**
	 * find
	 *
	 * @param  integer $id			id of record
	 * @param  object $fetchMode 	set return mode, e.g. PDO::FETCH_OBJ or PDO::FETCH_ASSOC
	 * @return object/array			returns single record
	 */
	public static function find($id, $fetchMode = PDO::FETCH_OBJ)
	{
		return static::run("SELECT * FROM ".static::get_table()." WHERE id = ?", [$id])->fetch($fetchMode);
	}

	/**
	 * Get number of records
	 *
	 * @param  string $sql       sql query
	 * @param  array  $data      params
	 * @return integer           returns number of records
	 */
	public static function count($data)
	{
		return (int) static::process($sql, $args)->rowCount();
	}

	/**
	 * Get primary key of last inserted record
	 */
	public static function lastInsertId()
	{
		return static::$db->lastInsertId();
	}

	/**
	 * insert record
	 *
	 * @param  string $table table name
	 * @param  array $data  array of columns and values
	 */
	public static function insert($table, $data)
	{
		//add columns into comma seperated string
		$columns = implode(',', array_keys($data));

		//get values
		$values = array_values($data);

		$placeholders = array_map(function ($val) {
			return '?';
		}, array_keys($data));

		//convert array into comma seperated string
		$placeholders = implode(',', array_values($placeholders));

		static::run("INSERT INTO $table ($columns) VALUES ($placeholders)", $values);
		return static::lastInsertId();
	}

	/**
	 * update record
	 *
	 * @param  string $table table name
	 * @param  array $data  array of columns and values
	 * @param  array $where array of columns and values
	 */
	public static function update($table, $data, $where)
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

		$stmt = static::run("UPDATE $table SET $fieldDetails WHERE $whereDetails", $values);
		return $stmt->rowCount();
	}

	/**
	 * Delete records
	 *
	 * @param  string $table table name
	 * @param  array $where array of columns and values
	 * @param  integer $limit limit number of records
	 */
	public static function delete($table, $where, $limit = 1)
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

		$stmt = static::run("DELETE FROM $table WHERE $whereDetails $limit", $values);
		return $stmt->rowCount();
	}

	/**
	 * Delete all records records
	 *
	 * @param  string $table table name
	 */
	public static function deleteAll($table)
	{
		$stmt = static::run("DELETE FROM $table");
		return $stmt->rowCount();
	}

	/**
	 * Delete record by id
	 *
	 * @param  string $table table name
	 * @param  integer $id id of record
	 */
	public static function deleteById($table, $id)
	{
		$stmt = static::run("DELETE FROM $table WHERE id = ?", [$id]);
		return $stmt->rowCount();
	}

	/**
	 * Delete record by ids
	 *
	 * @param  string $table table name
	 * @param  string $column name of column
	 * @param  string $ids ids of records
	 */
	public static function deleteByIds(string $table, string $column, string $ids)
	{
		$stmt = static::run("DELETE FROM $table WHERE $column IN ($ids)");
		return $stmt->rowCount();
	}

	/**
	 * truncate table
	 *
	 * @param  string $table table name
	 */
	public static function truncate($table)
	{
		$stmt = static::run("TRUNCATE TABLE $table");
		return $stmt->rowCount();
	}

	static function startup($pdo_options) {
		if (static::$instance == null) {
			static::$instance = new PdoFish($pdo_options);
		}
		return(static::$instance);
	}
}

PdoFish::startup($pdo_options);
