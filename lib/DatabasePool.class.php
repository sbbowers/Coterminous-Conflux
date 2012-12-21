<?php
abstract class DatabasePool
{
	private static
		$private_pool = array(),
		$shared_pool = array(),
		$object_id_sequence = 0;

	protected
		$id = null,
		$database_name = null,
		$config = null;

	protected function __construct($database_name, $config)
	{
		$this->config = $config;
		$this->database_name = $database_name;
		$this->set_object_sequence();

		self::init_pool($this->database_name);
	}

	protected final static function init_pool($config_name)
	{
		if(!array_key_exists($config_name, self::$private_pool))
			self::$private_pool[$config_name] = array();
		if(!array_key_exists($config_name, self::$shared_pool))
			self::$shared_pool[$config_name] = array();
	}

	private function set_object_sequence()
	{
		if(is_null($this->id))
			$this->id = ++self::$object_id_sequence;
	}

	public function __destruct()
	{
		//This needs to be configured to close all the connections in the pool, not sure on the best way for this to know the right function to call
	}

	protected final function get_connection()
	{
		if(array_key_exists($this->id, self::$private_pool[$this->database_name]))
			return self::$private_pool[$this->database_name][$this->id];
		if(count(self::$shared_pool[$this->database_name]) == 0)
			self::$shared_pool[$this->database_name][] = $this->new_connection();
		return current(self::$shared_pool[$this->database_name]);
	}

	public final function start_private()
	{
		if(array_key_exists($this->id, self::$private_pool[$this->database_name]))
			return;
		if(count(self::$shared_pool[$this->database_name]) == 0)
			self::$shared_pool[$this->database_name][] = $this->new_connection();
		$connection = array_pop(self::$shared_pool[$this->database_name]);
		self::$private_pool[$this->database_name][$this->id] = $connection;
	}

	public final function stop_private()
	{
		if(array_key_exists($this->id, self::$private_pool[$this->database_name]))
		{
			self::$shared_pool[$this->database_name][] = self::$private_pool[$this->database_name][$this->id];
			unset(self::$private_pool[$this->database_name][$this->id]);
		}
	}

}

