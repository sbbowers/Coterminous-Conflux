<?php
namespace C\Database;

class Db
{
	private static
		$private_pool = [],
		$shared_pool = [],
		$object_id_sequence = 0;

	protected
		$connection_name,
		$config,
		$service_class_name,
		$id;

	public function __construct($connection_name = 'default')
	{
		if($connection_name == 'default')
			$connection_name = \C\Config::find('connection', 'default');

		if(!$connection_name)
			throw new \Exception("No default connection defined in config");

		$this->connection_name = $connection_name;

		if(!in_array($connection_name, \C\Config::find('connection', 'enabled')))
			throw new \Exception("Connection for $connection_name is not enabled");

		$this->config = \C\Config::get('connection', 'available', $connection_name);

		if(!isset($this->config['vendor']))
			throw new \Exception("Cannot find vendor for connection $connection_name");

		$this->service_class_name = '\\C\\Database\\Vendor\\'.ucwords(strtolower($this->config['vendor']));
		$this->service_class = new $this->service_class_name($connection_name, $this->config);

		$this->set_object_sequence();
		self::init_pool($this->connection_name);
	}

	public function exec($sql)
	{
		$result = $this->service_class->exec($sql, $this->get_connection());
		return new DbResult($result, $this->service_class, $this->connection_name, $this->config);
	}

	public function begin()
	{
		$this->start_private();
		return $this->service_class->begin($this->get_connection());
	}

	public function commit()
	{
		$result = $this->service_class->commit($this->get_connection());
		$this->stop_private();
		return $result;
	}

	public function rollback()
	{
		$result = $this->service_class->rollback($this->get_connection());
		$this->stop_private();
		return $result;
	}

	public function list_schemas()
	{
		return $this->service_class->list_schemas($this->get_connection());
	}

	public function list_tables($schema = null)
	{
		return $this->service_class->list_tables($schema, $this->get_connection());
	}

	public function list_columns($table, $schema = null)
	{
		return $this->service_class->list_columns($table, $schema, $this->get_connection());
	}

	private function set_object_sequence()
	{
		if(is_null($this->id))
			$this->id = ++self::$object_id_sequence;
	}

	// Pooling Code
	private static function init_pool($config_name)
	{
		if(!array_key_exists($config_name, self::$private_pool))
			self::$private_pool[$config_name] = [];

		if(!array_key_exists($config_name, self::$shared_pool))
			self::$shared_pool[$config_name] = [];
	}

	protected final function get_connection()
	{
		if(array_key_exists($this->id, self::$private_pool[$this->connection_name]))
			return self::$private_pool[$this->connection_name][$this->id];

		if(count(self::$shared_pool[$this->connection_name]) == 0)
			self::$shared_pool[$this->connection_name][] = $this->service_class->new_connection();

		return current(self::$shared_pool[$this->connection_name]);
	}

	public final function start_private()
	{
		if(array_key_exists($this->id, self::$private_pool[$this->connection_name]))
			return;

		if(count(self::$shared_pool[$this->connection_name]) == 0)
			self::$shared_pool[$this->connection_name][] = $this->service_class->new_connection();

		$connection = array_pop(self::$shared_pool[$this->connection_name]);
		self::$private_pool[$this->connection_name][$this->id] = $connection;
	}

	public final function stop_private()
	{
		if(array_key_exists($this->id, self::$private_pool[$this->connection_name]))
		{
			self::$shared_pool[$this->connection_name][] = self::$private_pool[$this->connection_name][$this->id];
			unset(self::$private_pool[$this->connection_name][$this->id]);
		}
	}

}
