<?php
namespace C;

class DatabaseCassandra extends Database
{
	protected
		$config = null,
		$context = null,
		$result = null;

	public function __construct($database_name, $config)
	{
		parent::__construct($database_name, $config);
		
	}

	public function new_connection()
	{

		$connection_string = $this->get_connection_string();
		$connection = new \PDO($connection_string);
		if(array_key_exists('keyspace', $this->config))
			$connection->exec("USE {$this->config['keyspace']}");

		if($connection === false)
		{
			throw new Exception('Cassandra Connection Failed');
		}
		return $connection;
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
			{
				$connection_string.= "$key=$value;";
			}
		}
		return rtrim($connection_string, ';');
	}

	public function exec($sql)
	{
		$this->result = $this->get_connection()->prepare($sql);
		$this->result->execute();;
		var_dump($this->result);
		return new DatabaseResult($this, $this->result, $sql);
	}

	public function begin()
	{
		$this->get_connection()->beginTransaction();
	}

	public function commit()
	{
		$this->get_connection()->commit();
	}

	public function rollback()
	{
		$this->get_connection()->rollBack();
	}

	public function free_result()
	{
	}

	public function affected_rows()
	{
		return $this->result->rowCount();
	}

	public function num_rows()
	{
		return $this->result->rowCount();
	}

	public function seek($offset = 0)
	{
	}

	public function fetch_assoc()
	{
		$this->result->setFetchMode(\PDO::FETCH_ASSOC);
		return $this->result->fetch();
	}

	public function fetch_index()
	{
		$thits->result->setFetchMode(\PDO::FETCH_NUM);
		return $this->result->fetch();
	}

	public function fetch_both()
	{
		$thits->result->setFetchMode(\PDO::FETCH_BOTH);
		return $this->result->fetch();
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
				return "'".pg_escape_string($value)."'";

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

	public function get_columns()
	{
		$sql = "select keyspace_name as \"schema\", columnfamily_name as \"table\", column_name as \"column\" from system.schema_columns where keyspace_name = '{$this->config['keyspace']}'";
		$result = [];
		foreach($this->exec($sql) as $row)
		{
			$row['required'] = 1;
			$row['type'] = 'text';
			$row['text_length'] = null;
			$result[] = $row;
		}
		return $result;
	}

	public function pkey_sql()
	{
		return "SELECT table_constraints.table_schema as schema, table_constraints.table_name as table, key_column_usage.column_name as column
			FROM information_schema.key_column_usage
			JOIN information_schema.table_constraints USING (constraint_catalog, constraint_schema, constraint_name)
			WHERE constraint_type='PRIMARY KEY'
			ORDER BY key_column_usage.ordinal_position;";
	}

	public function fkey_sql()
	{
		return "SELECT 
				fkey.constraint_name as name, use.table_schema as schema, use.table_name as table, use.column_name as column, 
				col.table_schema as ref_schema, col.table_name as ref_table, col.column_name as ref_column
			FROM information_schema.key_column_usage as col
			JOIN information_schema.table_constraints as pkey ON (pkey.constraint_name=col.constraint_name)
			JOIN information_schema.referential_constraints as ref ON (ref.unique_constraint_name=pkey.constraint_name)
			JOIN information_schema.table_constraints as fkey ON (fkey.constraint_name=ref.constraint_name)
			JOIN information_schema.key_column_usage  as use ON (use.constraint_name=fkey.constraint_name)
			WHERE 
				pkey.constraint_type='PRIMARY KEY'
				AND fkey.constraint_type='FOREIGN KEY';";
	}

	public function sequence_sql($column_id)
	{
		//$desc = Db::desc_table($table_name);
		//$sequence = $desc[$column_name]['default'];
		//return str_replace('nextval(', 'currval(', $sequence);
	}


}
