<?php
namespace C;

// Instantiable class wrapper for Hash trait
class HashClass implements \ArrayAccess, \Iterator, \Countable 
{ 
	use Hash; 

	// Constructor
	public function __construct($initial_array = array())
	{
		return $this->from_array($initial_array);
	}
}
