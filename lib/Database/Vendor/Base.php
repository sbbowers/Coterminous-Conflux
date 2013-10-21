<?php
namespace C\Database\Vendor;

abstract class Base
{
	public abstract function __construct($connection_name, $config);

	// Database Connection Operations
	public abstract function new_connection();
	public abstract function exec($sql, $connection);
	public abstract function begin($connection);
	public abstract function commit($connection);
	public abstract function rollback($connection);
	public abstract function list_schemas($connection);
	public abstract function list_tables($schema,$connection);
	public abstract function list_columns($table, $schema, $connection);

	// Database Result Operations
	public abstract function free_result($result);
	public abstract function fetch($result);
}
