<?php
namespace C\Database\Vendor;

class Postgres extends \C\Database\Vendor\Base
{
	private
		$connection_name,
		$config,
		$last;

	public function __construct($connection_name, $config)
	{
		$this->connection_name = $connection_name;
		$this->config = $config;
	}

	// Database Connection Operations
	public function new_connection()
	{
		$connection_string = $this->get_connection_string();
		$connection = pg_connect($connection_string, PGSQL_CONNECT_FORCE_NEW);

		if($connection === false)
		{
			throw new \Exception('Postgres Connection Failed');
		}
		return $connection;
	}

	public function exec($sql, $connection)
	{
		$result = pg_query($connection, $sql);
		return $result;
	}

	public function begin($connection)
	{
		$this->exec('BEGIN', $connection);
	}

	public function commit($connection)
	{
		$this->exec('COMMIT', $connection);
	}

	public function rollback($connection)
	{
		$this->exec('ROLLBACK', $connection);
	}

	public function list_schemas($connection)
	{
		$sql = "SELECT
	catalog_name as database,
	schema_name as schema,
	schema_owner,
	default_character_set_catalog,
	default_character_set_schema,
	default_character_set_name,
	sql_path
FROM
	information_schema.schemata";
		$result = $this->exec($sql, $connection);
var_dump($result);
		$schemas = [];
		while($row = $this->fetch($result))
		{
			$schemas[] = $row;
		}
		return $schemas;
	}

	public function list_tables($schema, $connection)
	{
		if(is_null($keyspace))
			$keyspace = $this->config['keyspace'];

		$keyspace = preg_replace('/[^0-9a-zA-Z]+/', '', $keyspace);

		$sql = "SELECT
	table_catalog as database,
	table_schema as schema,
	table_name as table,
	table_type,
	self_referencing_column_name,
	reference_generation,
	user_defined_type_catalog,
	user_defined_type_schema,
	user_defined_type_name,
	is_insertable_into,
	is_typed,
	commit_action
FROM
	information_schema.tables
WHERE
	table_catalog=current_database()
	AND table_schema NOT IN ('pg_catalog', 'information_schema')";

		$result = $this->exec($sql, $connection);
		$tables = [];

		while($row = $this->fetch($result))
			$tables[] = $row;

		return $tables;
	}

	public function list_columns($table, $keyspace, $connection)
	{
		if(is_null($keyspace))
			$keyspace = $this->config['keyspace'];

		$keyspace = preg_replace('/[^0-9a-zA-Z]+/', '', $keyspace);
		$table = preg_replace('/[^0-9a-zA-Z]+/', '', $table);

		$sql = "SELECT
	keyspace_name as \"schema\",
	columnfamily_name as \"table\",
	column_name as \"column\",
	component_index,
	index_name,
	index_options,
	index_type,
	type as \"key_type\",
	validator
FROM
	system.schema_columns
WHERE
	keyspace_name = '$keyspace'
	AND columnfamily_name = '$table'";

		$result = $this->exec($sql, $connection);
		$columns = [];

		while($row = $this->fetch($result))
		{
			$matches = [];
			$type = null;
			if(preg_match('/.*?([^.]+)$/', $row['validator'], $matches))
			{
				if(array_key_exists(1, $matches))
					$type = $matches[1];
			}
			$row['type'] = $type;
			$columns[] = $row;
		}

		return $columns;
	}

	// Database Result Operations
	public function free_result($result)
	{

	}

	public function fetch($result)
	{
		return pg_fetch_assoc($result);
	}

	private function get_connection_string()
	{
		$valid_options = array();
		$valid_options['host'] = true;
		$valid_options['hostaddr'] = true;
		$valid_options['port'] = true;
		$valid_options['dbname'] = true;
		$valid_options['user'] = true;
		$valid_options['password'] = true;
		$valid_options['connect_timeout'] = true;
		$valid_options['options'] = true;
		$valid_options['sslmode'] = true;
		$valid_options['service'] = true;

		$connection_string = '';
		foreach($this->config as $key => $value)
		{
			if(array_key_exists($key, $valid_options))
			{
				$value = addslashes($value);
				$connection_string.= "$key='$value' ";
			}
		}
		return $connection_string;
	}
}
