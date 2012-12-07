<?php
class DatabasePool
{
	private 
		$private_pool = array(),
		$shared_pool = array(),
		$close_function = null;

	public function __construct()
	{
	}

	public function __destruct()
	{
		//This needs to be configured to close all the connections in the pool, not sure on the best way for this to know the right function to call
	}

	public function get_connection($object)
	{
		$id = $object->id();
		if(array_key_exists($id, $this->private_pool))
			return $this->private_pool[$id];
		if(count($this->shared_pool) == 0)
			$this->shared_pool[] = $object->new_connection();
		return current($this->shared_pool);
	}

	public function start_private($object)
	{
		$id = $object->id();
		if(array_key_exists($id, $this->private_pool))
			return;
		if(count($this->shared_pool) == 0)
			$this->shared_pool[] = $object->new_connection();
		$connection = array_pop($this->shared_pool);
		$this->private_pool[$id] = $connection;
	}

	public function stop_private($object)
	{
		$id = $object->id();
		if(array_key_exists($id, $this->private_pool))
		{
			$this->shared_pool[] = $this->private_pool[$id];
			unset($this->private_pool[$id]);
		}
	}

}

