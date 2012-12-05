<?php

// Defines database operations
// Class database uses the concept of a context to provide the basic operations to the DatabaseResult class

abstract class Database {

  protected 
    $connection = null, 
    $context = null,
    $config = null,
    $config_name = null,
    $id = null;
  private static 
    $pool = array(),
    $object_id_sequence = 0;
  
  public static final function connect($db_config_name = 'default')
  {
    if($db_config_name == 'default')
      $db_config_name = Config::find('connection', 'default');

    if(!$db_config_name)
      throw new Exception("No default connection defined in config");

    if(!in_array($db_config_name, Config::find('connection', 'enabled')))
      throw new Exception("Connection for $db_config_name is not enabled");

    $config = Config::get('connection', 'available', $db_config_name);

    if(!isset($config['vendor']))
      throw new Exception('Cannot find vendor for connection '.$db_config_name);

    $class = 'Database'.ucwords(strtolower($config['vendor']));

    self::init_pool($db_config_name);
    $db_object = new $class($config);
    $db_object->set_config($config);
    $db_object->set_config_name($db_config_name);
    $db_object->set_object_sequence();
    return $db_object;
  }

  private final static function init_pool($config_name)
  {
    if(!array_key_exists($config_name, self::$pool))
    {
      self::$pool[$config_name]['private'] = array();
      self::$pool[$config_name]['shared'] = array();
    }
  }

  private function set_object_sequence()
  {
    if(is_null($this->id))
      $this->id = ++self::$object_id_sequence;
  }

  protected function set_config($config)
  {
    $this->config = $config;
  }

  protected function set_config_name($db_config_name)
  {
    $this->config_name = $db_config_name;
  }

  // Sets the result context for retrieval methods
  public function set_context($context)
  {
    $this->context = $context;
  }

  public final function start_private()
  {
    if(count(self::$pool[$this->config_name]['shared']) == 0)
      self::$pool[$this->config_name]['shared'][] = $this->new_connection();
    $connection = array_pop(self::$pool[$this->config_name]['shared']);
    self::$pool[$this->config_name]['private'][$this->id] = $connection;
  }

  public final function stop_private()
  {
    self::$pool[$this->config_name]['shared'][] = self::$pool[$this->config_name]['private'][$this->id];
    unset(self::$pool[$this->config_name]['private'][$this->id]);
  }

  protected final function get_connection()
  {
    $config_name = $this->config_name;
    if(array_key_exists($this->id, self::$pool[$config_name]['private']))
      return self::$pool[$config_name]['private'][$this->id];
    if(count(self::$pool[$config_name]['shared']) == 0)
      self::$pool[$config_name]['shared'][] = $this->new_connection();
    return current(self::$pool[$config_name]['shared']);
  }

  // Use the Database::connect() factory method to construct an instance
  protected abstract function __construct($config_array); 
  protected function new_connection();

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
  public abstract function schema_sql();
  public abstract function pkey_sql();
  public abstract function fkey_sql();
  public abstract function sequence_sql($column_id); /* tablename.columnname */

}

