<?php

class Hash implements ArrayAccess, Iterator
{
	protected
		$hash_data = array();

	public function __construct($initial_array = array())
	{
		$this->hash_data = (array) $initial_array;
	}

	// Basic array methods
	public function keys()
	{
		return array_keys($this->hash_data);
	}

	public function values()
	{
		return array_values($this->hash_data);
	}

	public function as_array()
	{
		return $this->hash_data;
	}

	// Implements ArrayAccess
	public function offsetExists($offset)
	{
		return isset($this->hash_data[$offset]);
	}

	public function offsetGet($offset)
	{
		return $this->hash_data[$offset];
	}

	public function offsetSet($offset, $value)
	{
		$this->hash_data[$offset] = $value;
	}

	public function offsetUnset($offset)
	{
		unset($this->hash_data[$offset]);
	}

	// Implements Iterator
	public function rewind()
	{
		reset($this->hash_data);
	}

	public function current()
	{
		return current($this->hash_data);
	}

	public function key()
	{
		return key($this->hash_data);
	}

	public function next()
	{
		return next($this->hash_data);
	}

	public function valid() 
	{
		return $this->current() !== false;
	}

}
