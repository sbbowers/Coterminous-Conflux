<?php

/*
	Class Schema - Used to look up relational information from the database
*/
class Schema
{
	protected static 
		$databases = array();

	// List all tables in a database
	public static function tables($database = null)
	{
		$database = Db::resolve_db_name($database);
		self::generate_schema($database);

		if(isset(self::$databases[$database]['tables']))
			return array_keys(self::$databases[$database]['tables']);
	}

	// Gets the model name for a table
	public static function model_name($table, $database = null)
	{
		return self::lookup('models', $table, $database);
	}

	// List all of the models 
	public static function models($database = null)
	{
		$database = Db::resolve_db_name($database);
		self::generate_schema($database);

		if(isset(self::$databases[$database]['models']))
			return self::$databases[$database]['models'];
	}

	// Returns a table definition
	public static function define($table, $database = null)
	{
		return self::lookup('tables', $table, $database);
	}

	// Returns a table definition
	public static function column_type($table, $column, $database = null)
	{
		$database = Db::resolve_db_name($database);
		self::generate_schema($database);

		if(isset(self::$databases[$database]['tables'][$table][$column]))
			return self::$databases[$database]['tables'][$table][$column]['type'];
	}	

	// Returns an array of columns that comprise the primary key of a table
	public static function primary_key($table, $database = null)
	{
		return self::lookup('pkeys', $table, $database);
	}	

	// Returns a list of tables => join clauses that this table belong to
	public static function parents($table, $database = null)
	{
		return self::lookup('parents', $table, $database);
	}	

	// Returns a list of tables => join clauses that belong to this table
	public static function children($table, $database = null)
	{
		return self::lookup('children', $table, $database);
	}

	// Helper
	protected static function lookup($type, $table, $database = null)
	{
		$database = Db::resolve_db_name($database);
		self::generate_schema($database);

		if(isset(self::$databases[$database][$type][$table]))
			return self::$databases[$database][$type][$table];
	}	

	// Builds all of the look up data
	protected static function generate_schema($database)
	{
		if(self::$databases)
			return;

		foreach(Db::enum() as $db)
		{
			try
			{
				$vendor = Db::get_vendor($db);

				$schema = Db::exec($vendor->schema_sql(), $db);
				foreach($schema as $column)
					self::$databases[$db]['tables'][$column['table']][$column['column']] = $column;

				$pkeys = Db::exec($vendor->pkey_sql(), $db);
				foreach($pkeys as $key)
					self::$databases[$db]['pkeys'][$key['table']][] = $key['column'];

				$fkeys = Db::exec($vendor->fkey_sql(), $db);
				foreach($fkeys as $key)
				{
					self::$databases[$db]['fkeys'][$key['table']][$key['column']] = array('table'=>$key['ref_table'], 'column'=>$key['ref_column']);
					self::$databases[$db]['relations'][$key['ref_table']][$key['ref_column']] = array('table'=>$key['table'], 'column'=>$key['column']);
					self::$databases[$db]['parents'][$key['table']][$key['ref_table']] = "{$key['table']}.{$key['column']} = {$key['ref_table']}.{$key['ref_column']}";
					self::$databases[$db]['children'][$key['ref_table']][$key['table']] = "{$key['table']}.{$key['column']} = {$key['ref_table']}.{$key['ref_column']}";
				}

				// Generate model names
				foreach(self::$databases as $db => $data)
				{
					foreach($data['tables'] as $table => $schema)
						self::$databases[$db]['models'][$table] = self::gen_model_name($table);
				}

			}
			catch (Exception $e) {}
		}
	}

	protected static function gen_model_name($table)
	{
		$tokens = preg_split('/(?<!^|[a-z])(?=[a-z])/', strtolower($table));
		$tokens = preg_replace('/[^a-z0-9]/', '', $tokens);
		if(!Regex::match('/^[a-zA-Z]/', $tokens[0]))
			$tokens[0] = 'm'.$tokens[0];
		return implode('', array_map(function($token){return ucwords($token);}, $tokens));
	}		
}
