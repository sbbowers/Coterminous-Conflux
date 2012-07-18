<?php

class DatabaseResult extends Db implements Countable, Iterator, Arrayaccess
{

  const RESULT_ASSOC = 0;
  const RESULT_INDEX = 1;
  const RESULT_BOTH  = 2;
  const RESULT_MODEL = 3;

	protected 
		$database = null,
		$resource = null, 
		$count = null,
		$sql = null,
		$ptr = 0,
		$result_mode = self::RESULT_ASSOC;

	// Constructor must be passed a result
	// $sql is for debuging purposes
	public function __construct($database, $resource, $sql)
	{
		$this->resource = $resource;
		$this->sql = $sql;
		$this->database = $database;

		$this->database->set_context($resource);
		$this->count = $this->database->num_rows();
	}

  public function set_result($mode = Database::RESULT_ASSOC)
  {
    $this->result_mode = $mode;
    return $this;
  }

	// Returns the contents of the current row and increments the row pointers
	// Allows you to do `while($row = $result->fetch())`
	// Also, you can do `while(list($field1, $field2) = $result->set_result(DatabaseResult::RESULT_INDEX)->fetch(DB_INDEX))`
	public function fetch()
	{
		switch($this->result_mode)
		{
			default:
			case self::RESULT_INDEX: return $this->database()->fetch_index();
			case self::RESULT_ASSOC: return $this->database()->fetch_assoc();
			case self::RESULT_BOTH : return $this->database()->fetch_both();
			case self::RESULT_MODEL: throw new exception('models not implemented yet');
		}
	}

	// Return an array of the column names
	public function columns()
	{
		return $this->database()->columns();
	}

	public function export()
	{
		$ret = array();
		while($row = $this->fetch())
			$ret[] = $row;
		return $ret;
	}

	// Implements Countable interface
	// Returns the number of rows in the result
	// Allows you to do `for($i = 0; $i < count($result); $i++)`
	public function count() { return $this->count; }

	// Implements Iterator interface
	// Allows you to do `foreach($result as $row)`
	public function current() { return $this[$this->ptr]; }
	public function key()     { return $this->ptr; }
	public function next()    { $this->ptr++; }
	public function rewind()  { $this->ptr = 0; }
	public function valid()   { return isset($this[$this->ptr]); }

	// Implements Arrayaccess Interface
	public function offsetSet($offset, $value) { throw new Exception("Can't set data from a DatabaseResult"); }
	public function offsetUnset($offset) { throw new Exception("Can't unset data from a DatabaseResult"); }
	public function offsetExists($offset) 
	{
		return is_integer($offset) && $offset >= 0 && $offset < $this->count; 
	}
	public function offsetGet($offset) 
	{
		$this->database()->seek($offset); 
		return $this->fetch();
	}

	// Set the context on the database connection and returns the connection
	protected function database()
	{
		$this->database->set_context($this->resource);
		return $this->database;
	}

}
