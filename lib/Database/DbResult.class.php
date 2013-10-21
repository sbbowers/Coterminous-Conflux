<?php
namespace C\Database;

class DbResult implements \Countable, \Iterator, \Arrayaccess
{
	private
		$result,
		$service_class,
		$connection_name,
		$config,
		$ptr = 0,
		$last,
		$last_valid = false;

	public function __construct($result, $service_class, $connection_name, $config)
	{
		$this->result = $result;
		$this->service_class = $service_class;
		$this->connection_name = $connection_name;
		$this->config = $config;
	}

	public function __destruct()
	{
		$this->service_class->free_result($this->result);
	}

	public function fetch()
	{
		$this->last = $this->service_class->fetch($this->result);
		$this->last_valid = true;
		return $this->last;
	}

	public function fetch_last()
	{
		if(!$this->last_valid)
			$this->fetch();
		return $this->last;
	}

	public function count()
	{
		return $this->service_class->count($this->result);
	}

	public function current()
	{
		return $this->fetch_last();
	}

	public function key()
	{
		return $this->ptr;
	}

	public function next()
	{
		$this->seek($this->ptr+1);
		$this->ptr++;
	}

	public function rewind()
	{
	}

	public function seek($postion)
	{
		$seek_offset = $postion - $this->ptr;
		if($seek_offset == 0)
			return true;

		if($seek_offset < 0)
			return false;

		$this->last_valid = false;
		for($x=0; $x<$seek_offset; $x++)
		{
			$res = $this->fetch();
			if(!$res)
				return false;
		}
		return true;
	}

	public function valid()
	{
		return (bool)$this->fetch_last();
	}

	public function offsetSet($offset, $value)
	{
		throw new \Exception("Can't set data on a DbResult");
	}

	public function offsetUnset($offset)
	{
		throw new \Exception("Can't unset data on a DbResult");
	}

	public function offsetExists($offset)
	{
		return $this->seek($offset);
	}

	public function offsetGet($offset)
	{
		$this->seek($offset);
		return $this->fetch_last();
	}

}
