<?php
// DatabasePool is a singleton class that is used to manage your database connections
class DatabasePool
{
	protected 
		$private_pool = array(),
		$shared_pool = array(),
		$close_function = null;

	public static function acquire()
	{
		static $instance = null;
		if(!$instance)
			$instance = new DatabasePool();

		return $instance;
	}

	protected function __construct()
	{
	}

	public function __destruct()
	{
		//This needs to be configured to close all the connections in the pool, not sure on the best way for this to know the right function to call
	}

	public static function connect($db_name)
	{
		foreach(self::acquire()->shared_pool as $conn)
		{
			if($conn->get_name() == $db_name)
				return $conn;
		}
		
		return self::acquire()->shared_pool[] = Database::connect($db_name);
	}

	public function has_lock(Database $connection)
	{
		$key = array_search($connection, $this->shared_pool, true);
		if($key!==false)
		{
			unset($this->shared_pool[$key]);
			$this->private_pool[] = $connection;
		}
		else if(array_search($connection, $this->private_pool, true) !== false)
			throw new Exception("Connection {$connection->get_name()} already locked");

		return $connection;
	}

	public function lock(Database $connection)
	{
		$key = array_search($connection, $this->shared_pool, true);
		if($key!==false)
		{
			unset($this->shared_pool[$key]);
			$this->private_pool[] = $connection;
		}
		else if(array_search($connection, $this->private_pool, true) !== false)
			throw new Exception("Connection {$connection->get_name()} already locked");

		return $connection;
	}

	public function release(Database $connection)
	{
		$key = array_search($connection, $this->private_pool, true);
		if($key!==false)
		{
			unset($this->private_pool[$key]);
			$this->shared_pool[] = $connection;
		}
		else if(array_search($connection, $this->shared_pool, true) !== false)
			throw new Exception("Connection {$connection->get_name()} not locked");

		return $connection;
	}

}

