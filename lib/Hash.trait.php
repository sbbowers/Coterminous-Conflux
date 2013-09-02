<?php
namespace C;

trait Hash // implements ArrayAccess, Iterator, Countable
{
	use BaseTrait;

	protected
		$Hash_data = array();

	// Basic array methods
	public function keys()
	{
		return array_keys($this->Hash_data);
	}

	public function values()
	{
		return array_values($this->Hash_data);
	}

	public function to_array()
	{
		return $this->Hash_data;
	}

	public function from_array($array)
	{
		if(is_array($array))
			return $this->Hash_data = $array;
		else if(is_a($array, 'Iterator'))
			return $this->Hash_data = iterator_to_array($array);
		else if(is_object($array))
			return $this->Hash_data = (array) $array;
	}

	// Implements ArrayAccess
	public function offsetExists($offset)
	{
		return isset($this->Hash_data[$offset]);
	}

	public function offsetGet($offset)
	{
		return $this->Hash_data[$offset];
	}

	public function offsetSet($offset, $value)
	{
		$this->Hash_data[$offset] = $value;
	}

	public function offsetUnset($offset)
	{
		unset($this->Hash_data[$offset]);
	}

	// Implements Iterator
	public function rewind()
	{
		reset($this->Hash_data);
	}

	public function current()
	{
		return current($this->Hash_data);
	}

	public function key()
	{
		return key($this->Hash_data);
	}

	public function next()
	{
		next($this->Hash_data);
	}

	public function valid() 
	{
		return $this->current() !== false;
	}

	// Implements Countable
	public function count()
	{
		return count($this->Hash_data);
	}

}
