<?php
class DatabasePostgres extends DatabaseVendor
{
	protected 
		$database = null,
		$config = null,
		$con = null;

//Mark Area
//1
//2
	public function __construct($db_config)
	{
		$this->config = $db_config;
		$con_string = $this->get_connection_string();
		$this->con = pg_connect($con_string, PGSQL_CONNECT_FORCE_NEW);
		if(!$this->con)
		{
			throw new Exception('DB Connection Error: '.pg_last_error($this->con));
		}
	}

	public function exec_async($sql)
	{
		if(pg_connection_busy($this->con))
			throw new Exception('Attempting To Query On Busy Connection');
		$res = pg_send_query($this->con, $sql);
		if(!$res)
			throw new Exception('Error Sending Query: '.pg_last_error($this->con));
	}

	public static function format($value, $type)
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

	public function retrieve_results()
	{
		$results = array();
		while($res = pg_get_result($this->con))
			$results[] = array('res' => $res, 'count' => pg_num_rows($res));
		return $results;
	}

	public function is_busy()
	{
		return pg_connection_busy($this->con);
	}

	protected function get_connection_string()
  {
    $ret = array();

    foreach(array('host', 'port', 'dbname', 'user', 'password') as $param)
      if(array_key_exists($param, $this->config))
        $ret[] = $param.'='.$this->config[$param];

    return implode(' ', $ret);
  }

	public function schema_sql()
	{
		return "
			SELECT table_schema as schema, table_name as table, column_name as column, (is_nullable='NO')::integer as required, 
				data_type as type, column_default as default, character_maximum_length as text_length
      FROM information_schema.columns
      WHERE table_catalog=current_database()
      AND table_schema NOT IN ('pg_catalog', 'information_schema')
      ORDER BY ordinal_position;";
	}
	
	public function pkey_sql()
	{
		return "
			SELECT table_constraints.table_schema as schema, table_constraints.table_name as table, key_column_usage.column_name as column
			FROM information_schema.key_column_usage
			JOIN information_schema.table_constraints USING (constraint_catalog, constraint_schema, constraint_name)
			WHERE constraint_type='PRIMARY KEY'
			ORDER BY key_column_usage.ordinal_position;";
	}

	public function fkey_sql()
	{
		return "
			SELECT fkey.constraint_name as name, use.table_schema as schema, use.table_name as table, use.column_name as column, 
				col.table_schema as ref_schema, col.table_name as ref_table, col.column_name as ref_column
			FROM information_schema.key_column_usage as col
			JOIN information_schema.table_constraints as pkey ON (pkey.constraint_name=col.constraint_name)
			JOIN information_schema.referential_constraints as ref ON (ref.unique_constraint_name=pkey.constraint_name)
			JOIN information_schema.table_constraints as fkey ON (fkey.constraint_name=ref.constraint_name)
			JOIN information_schema.key_column_usage  as use on (use.constraint_name=fkey.constraint_name)
			WHERE 
			pkey.constraint_type='PRIMARY KEY'
			AND fkey.constraint_type='FOREIGN KEY';";
	}

	public function get_last_column_default($table_name, $column_name)
	{
		$desc = Db::desc_table($table_name);
		$sequence = $desc[$column_name]['default'];
		return str_replace('nextval(', 'currval(', $sequence);
	}

}

