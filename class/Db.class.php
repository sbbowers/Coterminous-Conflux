<?php
/* 
	Db - Static Database class for easy access to all of your query needs
	To use, extend DatabaseVendor to work with your database and define 
	your database connection in default.yml

	There are two primary functions you should care about:
		Db::exec() - lets you execute a query
		Db::format() - Format your value based on a data type for sql injection

	Remember that all public methods have to filter the $database parameter with resolve_db_name()
*/ 

class Db
{
	protected static
		$con_pool = array(),
		$db_configs = array(),
		$res_pool = array();

	const FREE = 'free';
	const DIRTY = 'dirty';
	const MAX_CON = 1;
	
	// Execute an sql query on the database
	// $database refers to the identifier in your config file
	// if left null, database will default to the easy_connection alias
	public static function exec($sql, $database = null)
	{
		$database = self::resolve_db_name($database);
		$con_id = self::wait_for_free_con($database);
		self::$con_pool[$database][$con_id]['con']->exec_async($sql);
		self::$con_pool[$database][$con_id]['state'] = self::DIRTY;
		self::$con_pool[$database][$con_id]['sql'] = $sql;
		return new DatabaseResult($con_id, $sql, $database);
	}

	// Format your value based off of the type of column
	public static function format($value, $table, $column, $database = null)
	{
		$database = self::resolve_db_name($database);
		$vendor_type = self::get_vendor_type($database);
		$type = self::get_type($table, $column);
		return $vendor_type::format($value, $type);
	}

	protected static function get_type($table, $column)
	{
		$desc = self::desc_table($table);
		if(isset($desc[$column]))		
			return $desc[$column]['data_type'];
	}

	private static function resolve_db_name($database)
	{
		if(is_null($database))
			$database = 'easy_connection';

		if($database == 'easy_connection')
			$database = Config::get('connection', 'easy_connection');

		return strtolower($database);
	}

	private static function get_free_connection($database)
	{
		self::check_for_done_results();

		if(!isset(self::$con_pool[$database]))
			return self::new_con($database);

		foreach((array)self::$con_pool[$database] as $con)
		{
			if($con['state'] == self::FREE && !$con['con']->is_busy())
				return $con['con_id'];
		}

		if(count((array)self::$con_pool[$database]) < self::MAX_CON)
			return self::new_con($database);
		return null;
	}

	private static function check_for_done_results()
	{
		foreach(self::$con_pool as $cluster => $pool)
		{
			foreach($pool as $con_id => $con)
			{
				if($con['state'] == self::DIRTY && !pg_connection_busy($con['con']))
				{
					self::retrieve_result_from_con($con_id, $cluster);
				}
			}
		}
	}

	private static function retrieve_result_from_con($result_id, $database)
	{
		$con = self::$con_pool[$database][$result_id];
		$results = $con['con']->retrieve_results();
		self::$res_pool[$result_id]['res'] = $results;
		self::$res_pool[$result_id]['sql'] = $con['sql'];

		unset(self::$con_pool[$database][$result_id]);
		$new_con_id = self::get_new_con_id();
		self::$con_pool[$database][$new_con_id]['con'] = $con['con'];
		self::$con_pool[$database][$new_con_id]['con_id'] = $new_con_id;
		self::$con_pool[$database][$new_con_id]['state'] = self::FREE;
	}

	protected static function fetch_result($result_id, $database, $result_num = null)
	{
		if(!array_key_exists($result_id, self::$res_pool))
		{
			self::retrieve_result_from_con($result_id, $database);
		}
		if(is_null($result_num))
			$result_num = count(self::$res_pool[$result_id]['res'])-1;
		return self::$res_pool[$result_id]['res'][$result_num];

	}

	private static function wait_for_free_con($database)
	{
		$con_id = self::get_free_connection($database);
		while(is_null($con_id))
		{
			self::check_for_done_results();
			$con_id = self::get_free_connection($database);
			usleep(500);
		}
		return $con_id;
	}

	private static function new_con($database)
	{
		$vendor_config = self::get_db_config($database);
		$vendor_type = $vendor_config['db_vendor'];
		$con_id = self::get_new_con_id();
		$con = new $vendor_type($vendor_config, $con_id);
		self::$con_pool[$database][$con_id]['con'] = $con;
		self::$con_pool[$database][$con_id]['con_id'] = $con_id;
		self::$con_pool[$database][$con_id]['state'] = self::FREE;
		return $con_id;
	}

	private static function get_vendor_type($database = null)
	{
		$vendor_config = self::get_db_config($database);
    return $vendor_config['db_vendor'];
	}


	private static function get_new_con_id()
	{
		return uniqid();
	}

	private static function get_db_config($database)
	{
		if(array_key_exists($database, self::$db_configs))
			return self::$db_configs[$database];
		self::$db_configs[$database] = Config::find('connection', $database);
		return self::$db_configs[$database];
	}
	
	private static function get_vender_type($database)
	{
		$db_config = self::get_db_config($database);
		if(is_null($db_config))
			throw new Exception('Database '.$database.' Does Not Exist');
		if(!array_key_exists('db_vender', $db_config))
			throw new Exception('Databse Vender Not Defined For Database '.$database);
		return $db_config['db_vender'];
	}

	public static function list_tables($database = null)
	{
		$database = self::resolve_db_name($database);
		$vendor_type = self::get_vendor_type($database);
    $sql = $vendor_type::list_tables_sql();
    return Db::exec($sql, $database);
	}
	public static function desc_table($table_name, $database = null)
	{
		$database = self::resolve_db_name($database);
		$vendor_type = self::get_vendor_type($database);
		$sql = $vendor_type::table_sql($table_name);
		$desc = Db::exec($sql, $database)->fetch_all();

		$ret = array();
		foreach($desc as $column)
			$ret[$column['column_name']] = $column;

		return $ret;
	}
	public static function get_pkey($table_name, $database = null)
	{
		$database = self::resolve_db_name($database);
		$vendor_type = self::get_vendor_type($database);
    $sql = $vendor_type::pkey_sql($table_name);
    return Db::exec($sql, $database)->fetch_column('column_name');
	}

  public static function get_references($table_name, $database = null)
  {
		$database = self::resolve_db_name($database);
    $vendor_type = self::get_vendor_type($database);
    $sql = $vendor_type::reference_sql($table_name);
    return Db::exec($sql, $database);
  }

  public static function get_last_column_default($table_name, $column_name, $database = null)
  {
		$database = self::resolve_db_name($database);
    $vendor_type = self::get_vendor_type($database);
    return $vendor_type::get_last_column_default($table_name, $column_name);
  }
	
}

