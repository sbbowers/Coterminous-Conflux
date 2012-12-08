<?php

// Defines database operations
// Class database uses the concept of a context to provide the basic operations to the DatabaseResult class

abstract class Database {

  protected
    $connection = null,
    $context = null,
    $name = null,
    $config = null;
  
  public static final function connect($db_name = 'default')
  {
    if($db_name == 'default')
      $db_name = Config::find('connection', 'default');

    if(!$db_name)
      throw new Exception("No default connection defined in config");

    if(!in_array($db_name, Config::find('connection', 'enabled')))
      throw new Exception("Connection for $db_name is not enabled");

    $config = Config::get('connection', 'available', $db_name);

    if(!isset($config['vendor']))
      throw new Exception('Cannot find vendor for connection '.$db_name);

    $class = 'Database'.ucwords(strtolower($config['vendor']));

    return new $class($db_name, $config);
  }

  public function get_name()
  {
    return $this->name;
  }

  // Sets the result context for retrieval methods
  public function set_context($context)
  {
    $this->context = $context;
  }

  // overridable
  protected function get_connection()
  {
    return $this->connection;
  }

  // Use the Database::connect() factory method to construct an instance
  // Extend this method in your driver class
  protected function __construct($name, $config)
  {
    if($config === null)
      throw new Exception("Database configuration missing for $name");
    $this->name = $name;
    $this->config = $config;
  }  

  // Execute some SQL and return a DatabaseResult
  public abstract function exec($sql); // return DatabaseResult

  // Return the number of rows affected by an update, insert, or delete
  public abstract function affected_rows();

  // Format a value for a query
  public abstract function format($value, $type = null);

  // The following methods require that the context be set first
  public abstract function num_rows();
  public abstract function seek($offset = 0);
  public abstract function fetch_assoc();
  public abstract function fetch_index();
  public abstract function fetch_both();
  public abstract function columns();

  // Transaction

  public function begin()
  {
    DatabasePool::acquire()->lock($this);
    //$this->exec('SET autocommit=0;');
    $this->exec('BEGIN');
    return $this;
  }

  public function commit()
  {
    $this->exec('COMMIT');
    //$this->exec('SET autocommit=1;');
    DatabasePool::acquire()->release($this);
    return $this;
  }

  public function rollback()
  {
    $this->exec('ROLLBACK');
    //$this->exec('SET autocommit=1;');
    DatabasePool::acquire()->release($this);
    return $this;
  }

  // Cleanup
  public abstract function free_result();

  // Schema reflection queries
  public abstract function schema_sql();
  public abstract function pkey_sql();
  public abstract function fkey_sql();
  public abstract function sequence_sql($column_id); /* tablename.columnname */

}

