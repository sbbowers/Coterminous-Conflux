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

	public function list_tables_sql()
	{
		return "SELECT table_schema||'.'||table_name as table_name
			FROM information_schema.tables 
			WHERE table_catalog=current_database() AND is_insertable_into='YES' 
			AND table_schema NOT IN ('pg_catalog', 'information_schema');";
	}

	public function table_sql($table_name)
	{
		return "SELECT column_name, is_nullable, data_type, column_default, 
			character_maximum_length, numeric_precision, numeric_precision_radix, 
			numeric_scale, is_updatable 
			FROM information_schema.columns
			WHERE table_catalog=current_database()
			AND table_schema NOT IN ('pg_catalog', 'information_schema')
			AND table_name=".self::format($table_name, 'text')."
			ORDER BY ordinal_position;";
	}
	
	public function pkey_sql($table_name)
	{
		return '
			SELECT key_column_usage.column_name
			FROM information_schema.key_column_usage
			JOIN information_schema.table_constraints USING (constraint_catalog, constraint_schema, constraint_name)
			WHERE table_constraints.table_name = '.self::format($table_name, 'text').'
			ORDER BY key_column_usage.ordinal_position;';
	}

	public function reference_sql($table_name)
	{
		return "
			SELECT fkey.constraint_name, use.table_schema, use.table_name, use.column_name, 
							col.table_schema||'.'||col.table_name||'.'||col.column_name as \"references\"
			FROM information_schema.key_column_usage as col
			JOIN information_schema.table_constraints as pkey ON (pkey.constraint_name=col.constraint_name)
			JOIN information_schema.referential_constraints as ref ON (ref.unique_constraint_name=pkey.constraint_name)
			JOIN information_schema.table_constraints as fkey ON (fkey.constraint_name=ref.constraint_name)
			JOIN information_schema.key_column_usage  as use on (use.constraint_name=fkey.constraint_name)
			WHERE 
			pkey.constraint_type='PRIMARY KEY'
			AND fkey.constraint_type='FOREIGN KEY'
			AND col.table_name=".self::format($table_name, 'text');
	}

	public function get_last_column_default($table_name, $column_name)
	{
		$desc = Db::desc_table($table_name);
		$sequence = $desc[$column_name]['column_default'];
		return str_replace('nextval(', 'currval(', $sequence);
	}

}

