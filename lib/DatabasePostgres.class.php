<?php
class DatabasePostgres extends Database
{
	protected
		$connection_method = null,
		$last_context = null;

	public function __construct($name, $config = null)
	{
	  parent::__construct($name, $config);

		$this->connection_method = 'sync';
		if(array_key_exists('query_mode', $this->config))
			$this->connection_method = $this->config['query_mode'];

		$this->connection = pg_connect($this->get_connection_string(), PGSQL_CONNECT_FORCE_NEW);

		if($this->connection === false)
			throw new Exception('Postgres Connection Failed');
	}

	private function get_connection_string()
	{
		$valid_options = array(
			'host' => true,
			'hostaddr' => true,
			'port' => true,
			'dbname' => true,
			'user' => true,
			'password' => true,
			'connect_timeout' => true,
			'options' => true,
			'sslmode' => true,
			'service' => true,
		);

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

	public function exec($sql)
	{
		if($this->connection_method == 'sync')
			$result = $this->exec_sync($sql);
		elseif($this->connection_method == 'async')
			$result = $this->exec_async($sql);
		else
			throw new Exception('Unknown Postgres Query Method: '.$this->connection_method);
		$this->last_context = new DatabasePostgresContext($result, $this->get_connection());
		return new DatabaseResult($this, $this->last_context, $sql);
	}

	private function exec_sync($sql)
	{
		$res = pg_query($this->get_connection(), $sql);
		if($res === false)
			throw new Exception('Error TODO: Update This!');
		return $res;
	}

	private function exec_async($sql)
	{
		if(!is_null($this->last_context))
			$this->last_context->result();
		$res = pg_send_query($this->get_connection(), $sql);
		if($res === false)
			throw new Exception('Error TODO: Update This!');
		return $res;
	}

	public function free_result()
	{
		pg_free_result($this->context->result());
	}

	public function affected_rows()
	{
		return pg_affected_rows($this->context->result());
	}

	public function num_rows()
	{
		return pg_num_rows($this->context->result());
	}

	public function seek($offset = 0)
	{
		return pg_result_seek($this->context->result(), $offset);
	}

	public function fetch_assoc()
	{
		return pg_fetch_assoc($this->context->result());
	}

	public function fetch_index()
	{
		return pg_fetch_array($this->context->result());
	}

	public function fetch_both()
	{
		return pg_fetch_array($this->context->result(), null, PGSQL_BOTH);
	}

	public function columns()
	{
		$field_count = pg_num_fields($this->context->result());
		$columns = array();
		for($x=1; $x < $field_count; $x++)
		{
			$columns[] = pg_field_name($this->context->result(), $x);
		}
		return $columns;
	}

	public function format($value, $type = null)
	{
		$type = strtolower($type);
		switch($type)
		{
			case 'text':
			case 'varchar':
			case 'character varying':
				return "E'".pg_escape_string($value)."'";

			case 'integer':
			case 'smallint':
			case 'bigint':
			case 'serial':
			case 'bigserial':
				return intval($value);

			case 'decimal':
			case 'numeric':
			case 'real':
			case 'double precision':
				return floatval($value);
		}
	}

	public function schema_sql()
  {
		return 'SELECT
	table_schema as schema,
	table_name as table,
	column_name as column,
	(is_nullable=\'NO\') as required,
	data_type as type, 
	column_default as "default", 
	character_maximum_length as "text_length"
FROM
	information_schema.columns';
	}

	public function pkey_sql()
	{
		return "SELECT table_schema as \"schema\", table_name as \"table\", column_name as \"column\"
      FROM information_schema.columns
      WHERE table_schema = database() AND (column_key = 'PRI')";
	}

	public function fkey_sql()
	{
		return '
      SELECT
        CONSTRAINT_NAME as "name",
        CONSTRAINT_SCHEMA as "schema",
        table_name as "table",
        column_name as "column",
        referenced_table_schema as "ref_schema",
        referenced_table_name as "ref_table",
        referenced_column_name as "ref_column"
      FROM information_schema.key_column_usage
      WHERE
        referenced_table_name IS NOT NULL
        AND CONSTRAINT_SCHEMA=database()
      ORDER BY table_name, ordinal_position';
	}

	public function sequence_sql($column_id)
	{

	}
}
