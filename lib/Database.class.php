<?php
namespace C;

// Defines database operations
// Class database uses the concept of a context to provide the basic operations to the DatabaseResult class

abstract class Database extends DatabasePool{

  protected 
    $connection = null, 
    $context = null;
  
  public static final function connect($db_config_name = 'default')
  {
    if($db_config_name == 'default')
      $db_config_name = Config::find('connection', 'default');

    if(!$db_config_name)
      throw new \Exception("No default connection defined in config");

    if(!in_array($db_config_name, Config::find('connection', 'enabled')))
      throw new \Exception("Connection for $db_config_name is not enabled");

    $config = Config::get('connection', 'available', $db_config_name);

    if(!isset($config['vendor']))
      throw new \Exception('Cannot find vendor for connection '.$db_config_name);

    $class = '\C\Database'.ucwords(strtolower($config['vendor']));

    return new $class($db_config_name, $config);
  }

  // Sets the result context for retrieval methods
  public function set_context($context)
  {
    $this->context = $context;
  }

  // Use the Database::connect() factory method to construct an instance
  public abstract function new_connection();

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
  public abstract function begin();
  public abstract function commit();
  public abstract function rollback();

  // Cleanup
  public abstract function free_result();

  // Schema reflection queries
  public abstract function get_columns();
  public abstract function pkey_sql();
  public abstract function fkey_sql();
  public abstract function sequence_sql($column_id); /* tablename.columnname */

}

