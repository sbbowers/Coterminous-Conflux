<?php
namespace C\DatabaseVendors;

class Cassandra extends \C\DatabaseVendors\Base
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
		echo "Creating New Connection\n";
		$connection_string = $this->get_connection_string();
		$connection = new \PDO($connection_string);

		if(array_key_exists('keyspace', $this->config))
			$this->exec("USE {$this->config['keyspace']}", $connection);

		if(!$connection)
			throw new \Exception("Could Not Connect to Cassandra Database: $this->connection_name");

		return $connection;
	}

	public function exec($sql, $connection)
	{
		$result = $connection->prepare($sql);
		$result->execute();
		$result->setFetchMode(\PDO::FETCH_ASSOC);
		return $result;
	}

	public function begin($connection)
	{
		$connection->beginTransaction();
	}

	public function commit($connection)
	{
		$connection->commit();
	}

	public function rollback($connection)
	{
		$connection->rollBack();
	}

	public function list_schemas($connection)
	{
		$sql = "SELECT keyspace_name as \"schema\", durable_writes, strategy_class FROM system.schema_keyspaces";
		$result = $this->exec($sql, $connection);
		$schemas = [];
		while($row = $this->fetch($result))
		{
			$schemas[] = $row;
		}
		return $schemas;
	}

	public function list_tables($keyspace, $connection)
	{
		if(is_null($keyspace))
			$keyspace = $this->config['keyspace'];

		$keyspace = preg_replace('/[^0-9a-zA-Z]+/', '', $keyspace);

		$sql = "SELECT
	keyspace_name as \"schema\",
	columnfamily_name as \"table\",
	bloom_filter_fp_chance,
	caching,
	column_aliases,
	comment,
	compaction_strategy_class,
	compaction_strategy_options,
	comparator,
	compression_parameters,
	default_time_to_live,
	dropped_columns,
	gc_grace_seconds,
	index_interval,
	key_aliases,
	key_validator,
	local_read_repair_chance,
	max_compaction_threshold,
	memtable_flush_period_in_ms,
	min_compaction_threshold,
	populate_io_cache_on_flush,
	read_repair_chance,
	replicate_on_write,
	speculative_retry,
	subcomparator,
	type,
	value_alias
FROM
	system.schema_columnfamilies
WHERE keyspace_name = '{$this->config['keyspace']}'";

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
		return $result->fetch();
	}

	private function get_connection_string()
	{
		$valid_options = array();
		$valid_options['host'] = true;
		$valid_options['port'] = true;
		$valid_options['cqlversion'] = true;

		$connection_string = 'cassandra:';

		foreach($this->config as $key => $value)
		{
			if(array_key_exists($key, $valid_options))
			$connection_string.= "$key=$value;";
		}

		return rtrim($connection_string, ';');
	}
}
