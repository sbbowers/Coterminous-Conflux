<?php

define('DB_ASSOC', 0);
define('DB_INDEX', 1);
define('DB_BOTH', 2);

class DatabaseResult extends Db implements Countable, Iterator
{
	protected 
		$result = null,
		$count = null,
		$sql = null,
		$ptr = 0,
		$database = null,
		$resource_id = null;

	// Constructor must be passed a result
	// $sql is for debuging purposes
	protected function __construct($resource_id, $sql, $database)
	{
		$this->resource_id = $resource_id;
		$this->sql = $sql;
		$this->database = $database;
	}

	private function wait()
	{
		if(!isset($this->result))
		{
			$res = parent::fetch_result($this->resource_id, $this->database);
			$this->result = $res['res'];
			$this->count = $res['count'];
		}
	}
	
	// Returns the contents of the current row and increments the row pointers
	// Allows you to do `while($row = $result->fetch())`
	// Also, you can do `while(list($field1, $field2) = $result->fetch(DB_INDEX))`
	public function fetch($format = DB_ASSOC, $index = null)
	{
		self::wait();
		switch($format)
		{
			default:
			case DB_BOTH:  return pg_fetch_array($this->result, $index);
			case DB_ASSOC: return pg_fetch_assoc($this->result, $index);
			case DB_INDEX: return pg_fetch_row($this->result, $index);
		}
	}

	// Returns the contents of an entire column
	// Column name is specefied as associative name of the column
	public function fetch_column($column_name)
	{
		self::wait();
		return pg_fetch_all_columns($this->result, pg_field_num($this->result, $column_name));
	}

  public function fetch_all()
  {
    self::wait();
    return pg_fetch_all($this->result);
  }

	// Return an array of the column names
	public function columns()
	{
		self::wait();
		$num = pg_num_fields($this->result);
		$ret = array();
		
		for($i = 0; $i < $num; $i++)
			$ret[] = pg_field_name($this->result, $i);
			
		return $ret;
	}

	public function export()
	{
		$ret = array();
		while($row = $this->fetch())
			$ret[] = $row;
		return $ret;
	}

	public function debug()
	{
		var_dump($this->export());	
	}

	// Implements Countable interface
	// Returns the number of rows in the result
	// Allows you to do `for($i = 0; $i < count($result); $i++)`
	public function count() { self::wait(); return $this->count; }

	// Implements Iterator interface
	// Allows you to do `foreach($result as $row)`
	public function current() { self::wait(); return $this->fetch(DB_ASSOC, $this->ptr); }
	public function key()     { self::wait(); return $this->ptr; }
	public function next()    { self::wait(); $this->ptr++; }
	public function rewind()  { self::wait(); $this->ptr = 0; pg_result_seek($this->result, 0); }
	public function valid()   { self::wait(); return $this->ptr >= 0 && $this->ptr < $this->count; }

}
