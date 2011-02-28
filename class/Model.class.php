<?php

/*
	Base Class for Model abstraction of database tables
	Provides basic CRUD operations on any table with primary keys
	Extend your own to add functionality
*/

class Model extends Hash
{
	protected
		$retrieved_from_db = false,
		$database = null, 
		$table_name = null,
		$primary_key = null,
		$errors = array(),
		$filter_class = 'Filter',
		$validate_class = 'Validate';

	// Builds a model from a dataset.  Minimum dataset requirements is simply the primary key.
	// Syncronizes all other applicable fields in the dataset
	// Alternatively, $dataset can be the primary key value for single-column primary keys
	// Note that when you extend this class, the declaration is __construct($dataset = null, $database = null)
	public function __construct($table_name = null, $dataset = null,  $database = null)
	{
		// extended models will define $table_name in the class, so shift the parameters 
		if($this->table_name)
			return $this->__construct(null, $table_name, $dataset);

		if($table_name)
			$this->table_name = $table_name;

		if(!$this->table_name)
			throw new Exception('Cannot create model object; no table definition provided');

		$this->database = $database;

		if($dataset)
			$this->load($dataset);

		if(is_array($dataset) || is_object($dataset))
			foreach($dataset as $key => $value)
				$hash_data[$key] = $value;
	}

	// Takes a row of information and pulls out the primary key definition.
	// returns the primary key array.
	protected function set_pkey($primary_key)
	{
		$pkey_def = Db::get_pkey($this->table_name, $this->database);
		if(is_string($primary_key))
		{
			list(,$column_name) = each($pkey_def);
			$primary_key = array($column_name => $primary_key);
		}

		$ret = array();
		foreach($pkey_def as $column_name)
		{
			if(isset($primary_key[$column_name]))
				$ret[$column_name] = $primary_key[$column_name];
			else
				throw new Exception('Could not find primary key '.$column_name.' definition for table '.$this->table_name);
		}
	
		$this->primary_key = $ret;
		// Sorting is important for serialization of the key
		ksort($this->primary_key);
	}

	// Propagates the model with data selected by primary key
	public function load($primary_key = null)
	{
		if($primary_key)
			$this->set_pkey($primary_key);
		
		$sql = "SELECT * FROM ".$this->table_name." WHERE ".$this->get_where_clause();
		$res = Db::exec($sql, $this->database);

		if(count($res) != 1)
			throw new Exception('failed to load model for table '.$this->table_name);
	
		$this->hash_data = $res->fetch();
		$this->retrieved_from_db = true;
	}

	public function save()
	{
		if($this->retrieved_from_db) // use update
		{
			$data = $this->hash_data;
			foreach($this->primary_key as $key => $value)
				unset($data[$key]);

			$set = array();
			foreach($data as $key => $value)
				$set[] = $key."=".Db::format($value, $this->table_name, $key, $this->database);
			$set = implode(', ', $set);

			$sql = 'UPDATE '.$this->table_name.' SET '.$set.' WHERE '.$this->get_where_clause();
			Db::exec($sql, $this->database);
		}
		else // use insert
		{
			$set = array();
			foreach($this->hash_data as $key => $value)
				$set[$key] = Db::format($value, $this->table_name, $key, $this->database);
						
			$keys = implode(', ', array_keys($set));
			$values = implode(', ', array_values($set));
			$sql = 'INSERT INTO '.$this->table_name.' ('.$keys.') VALUES ('.$values.');';
			Db::exec($sql, $this->database);
			$this->load_last_insert();
		}
	}

	private function get_where_clause()
	{
		$where = array();
		foreach($this->primary_key as $key => $value)
				$where[] = $key."=".Db::format($value, $this->table_name, $key, $this->database);

		return implode(' AND ', $where);
	}

	// After an insert, load_last_insert is used to reload the data from sequence values if necessary
	private function load_last_insert()
	{
		$pkey_def = Db::get_pkey($this->table_name, $this->database);
		$desc = Db::desc_table($this->table_name, $this->database);

		$where = array();
		foreach($pkey_def as $key)
		{
			if(isset($this->hash_data[$key]))
				$where[] = $key.'='.Db::format($this->hash_data[$key], $this->table_name, $key, $this->database);
			else
				$where[] = $key.'='.Db::get_last_column_default($this->table_name, $key);
		}
		$sql = "SELECT * FROM ".$this->table_name." WHERE ".implode(', ', $where);
		$res = Db::exec($sql, $this->database);

		if(count($res) != 1)
			throw new Exception('failed to load model for table '.$this->table_name);
	
		$this->hash_data = $res->fetch();
		$this->retrieved_from_db = true;
	}

	// Validation and filters
	protected function filter($field, $method, $params = array(), $class = null)
	{
		$class = $class ?: $this->filter_class;
		$filter = new $class($this, $field);
		$this[$field] =	call_user_func_array(array($filter, $method), (array) $params);
	}

	protected function validate($field, $method, $params = array(), $class = null)
	{
		$class = $class ?: $this->filter_class;
		$filter = new $class($this, $field);
		call_user_func_array(array($filter, $method), (array) $params);
		return isset($this->errors[$field_name]);
	}

	protected function set_error($field_name, $message)
	{
		$this->errors[$field_name] = $message;
	}

	public function get_error($field_name)
	{
		if(isset($this->errors[$field_name]))
			return $this->errors[$field_name];
	}

	public function get_errors()
	{
		return $this->errors;
	}

	// Define this in your extended models and put all of your filters and validators in here
	protected function pre_save()
	{
		/*
			// Filter Example:
			$this->filter('name', 'required');             // Executes Filter::required() on $this['name']
			$this->filter('name', substring, array(2,6))); // Executes Filter::substring(2, 6) on $this['name']
			// Nothing stops you from doing:
			$this['name'] = substr($this['name'], 0, 64);

			// Validate Example:

			$this->validate('phone_number', 'phone')             // Executes Validate::phone()
			$this->validate('url', 'https', null, 'ValidateUrl') // Executes ValidateUrl::https()
			// You can also validate by hand:
			if(!preg_match('/\(\d{3}\)\d{3}-\d{3}/', $this['phone']))
				$this->set_error('phone', 'Your phone number is invalid');

			// Generally, filters update $this['fields'], and validators just set

		*/
	}

  protected function input_name($field)
  {
    return $this->table_name."[".implode(',', $this->primary_key).']['.$field.']';
  }

  public function input($field)
  {
    $input = new Element('text');
    $input->name = $this->input_name($field);
//    $input->value = 
    
    
//    new model('genres', array(id=>3, some=>yes)
    
//    genres[id:3,some:yes][field]=askj
    
    
  }

}
