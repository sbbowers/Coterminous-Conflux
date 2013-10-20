<?php
namespace C;

/*
	Class Schema - Used to look up relational information from the database
*/
class Schema
{
	protected static 
		$__resolve = array(),
		$__data = array();

	protected
		$database = null,
		$data = null;

	public function __construct($schema = 'default')
	{
		if($schema === null || $schema == 'default')
			$schema = Config::find('connection', 'default');
		$this->database = self::$__resolve[$schema];
		//$this->data = self::$__data[$this->database];
	}

	public static function __callStatic($schema, $params = array())
	{
		if(!isset(self::$__resolve[$schema]))
			throw new Exception("Schema '$schema' not found");

		return new Schema($schema);
	}

	// Builds all of the look up data
	public static function __autoload()
	{
		if(self::$__data)
			return;

		foreach(Config::find('connection', 'enabled') as $db)
		{
			$con = Database::connect($db);

			self::$__resolve[$db] = Config::find('connection', 'available', $db, 'database');

			// Capture schema definitions
			foreach($con->get_columns()) as $row)
				self::$__data[$row['schema']]['schema'][$row['table']][$row['column']] = $row;

			// Primary keys
			foreach($con->exec($con->pkey_sql()) as $row)
				self::$__data[$row['schema']]['pkey'][$row['table']][] = $row['column'];

			foreach($con->exec($con->fkey_sql()) as $row)
			{
				// Build Foreign Keys
				self::$__data[$row['schema']]['fkey'][$row['table']][$row['column']] = array(
					'name' => $row['name'],
					'table' => $row['ref_table'],
					'column' => $row['ref_column']);

				// Build table references (reverse foreign keys)
				self::$__data[$row['schema']]['reference'][$row['ref_table']][$row['ref_column']] = array(
					'name' => $row['name'],
					'table' => $row['table'],
					'column' => $row['column']);

				// Compute join relations
				$join_key = self::join_key($row['ref_table'], $row['table']);
				self::$__data[$row['schema']]['join'][$join_key] = sprintf('%s.%s=%s.%s', $row['table'], $row['column'], $row['ref_table'], $row['ref_column']);
			}

			// Build model lookups
			foreach(self::$__data[$row['schema']]['schema'] as $table => $columns)
				self::$__data[$row['schema']]['model'][$table] = self::gen_model_name($table);
		}
	}

	// List all tables in a database
	public function tables()
	{
		return array_keys($this->data('schema'));
	}

	// List all of the models 
	public static function models($database = null)
	{
		return array_values($this->data('model'));
	}

	public function table($table)
	{
		if(isset($this->data('schema')[$table]))
			return $table;
		$key = array_search($table, $this->data('model'));
		if($key !== false)
			return $key;
	}

	public function model($table)
	{
		if(isset($this->data('schema')[$table]))
			return $this->data('schema')[$table];
		if(in_array($table, $this->data('schema')));
			return $table;
	}

	// Returns true is a table or table/column exists
	public function exists($table, $column = null)
	{
		print "$table, $column!\n";
		print_r($this->data('schema'));
		return $this->type($table, $column) || (!$column && in_array($this->table($table), $this->tables()));
	}

	public function database($database = 'default')
	{
		return $this->type($table, $column) || (!$column && in_array($this->table($table), $this->tables()));
	}

	// Returns a table definition
	public function define($table)
	{
		return (array) @$this->data('schema')[$this->table($table)];
	}

	// Returns a column type
	public function type($table, $column)
	{
		return @$this->data('schema')[$this->table($table)][$column]['type'];
	}	

	// Returns an array of columns that comprise the primary key of a table
	public function pkey($table)
	{
		return (array) @$this->data('pkey')[$this->table($table)];
	}	

	// Returns a list of foreign keys; tables that this table belongs to
	public function parents($table)
	{
		return (array) @$this->data('fkey')[$this->table($table)];
	}	

	// Returns a list of foreign keys; tables that belong to this table
	public function children($table)
	{
		return (array) @$this->data('reference')[$this->table($table)];
	}

	// Get Join criteria between two tables
	public function join($table1, $table2)
	{
		return (array) @$this->data('join')[self::join_key($table1, $table2)];
	}

	protected static function gen_model_name($table)
	{
		$tokens = preg_split('/(?<!^|[a-z])(?=[a-z])/', strtolower($table));
		$tokens = preg_replace('/[^a-z0-9]/', '', $tokens);
		if(!Regex::match('/^[a-zA-Z]/', $tokens[0]))
			$tokens[0] = 'm'.$tokens[0];
		return implode('', array_map(function($token){return ucwords($token);}, $tokens));
	}

	protected static function join_key($t1, $t2)
	{
		$ret = array($t1, $t2);
		sort($ret);
		return implode(',', $ret);
	}

	protected static function data($type)
	{
		return (array) @self::$__data[$this->database][$type];
	}
}
