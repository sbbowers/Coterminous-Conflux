<?php
namespace C;

class DatabaseMysql extends Database
{
  protected 
    $context = null;

  public function __construct($database_name, $config)
  {
    if($config === null)
      return;

    parent::__construct($database_name, $config);
  }

	public function new_connection()
	{
		$connection = new \mysqli($this->config['host'], $this->config['user'], $this->config['password'], $this->config['dbname']);

    if($connection->connect_error)
      throw new Exception('DB Connection Error: '.$connection->connect_errno.' '.$connection->connect_error);

		return $connection;
	}

  public function exec($sql)
  {
    $connection = $this->get_connection();
    $this->context = $connection->query($sql);
    if(!$this->context)
      throw new Exception('Error Sending Query: '.$connection->connect_errno.' '.$connection->error);

    return new DatabaseResult($this, $this->context, $sql);
  }

  public function affected_rows()
  {
    return $this->get_connection()->affected_rows;
  }


	public function begin()
	{
		$this->start_private();
		$this->get_connection()->autocommit(false);
	}

  public function commit()
	{
		$this->get_connection()->commit();
		$this->get_connection()->autocommit(true);
		$this->stop_private();
	}

  public function rollback()
	{
		$this->get_connection()->rollback();
		$this->get_connection()->autocommit(true);
		$this->stop_private();
	}

	public function free_result()
	{
		$this->context->close();
	}

  public function num_rows()
  {
    return $this->context->num_rows;
  }

  public function seek($offset = 0)
  {
    $this->context->data_seek($offset);
  }

  public function fetch_assoc()
  {
    return $this->context->fetch_array(MYSQLI_ASSOC);
  }

  public function fetch_index()
  {
    return $this->context->fetch_array(MYSQLI_NUM);
  }

  public function fetch_both()
  {
    return $this->context->fetch_array(MYSQLI_BOTH);
  }

  public function columns()
  {
    $ret = array();
    foreach($this->context->fetch_fields() as $field)
      $ret[] = $field->name;
    return $ret;
  }

  public function format($value, $type = null)
  {
    $type = strtolower($type);
    switch($type)
    {
      default:
      case 'text':
      case 'varchar':
      case 'longvarchar':
      case 'character varying':
        return "'".$this->get_connection()->real_escape_string($value)."'";

      case 'integer':
      case 'smallint':
      case 'bigint':
      case 'serial':
      case 'bigserial':
        return intval($value);

      case 'decimal':
      case 'numeric':
      case 'real':
      case 'double precision':
        return floatval($value);    
    }
  }

  public function schema_sql()
  {
    return '
      SELECT table_schema as "schema", table_name as "table", column_name as "column", (is_nullable=\'NO\') as required, data_type as type, column_default as "default", character_maximum_length as "text_length"
      FROM information_schema.columns
      WHERE table_schema = database()
      ORDER BY ordinal_position;';
  }
  
  public function pkey_sql()
  {
    return "
      SELECT table_schema as \"schema\", table_name as \"table\", column_name as \"column\"
      FROM information_schema.columns
      WHERE table_schema = database() AND (column_key = 'PRI');";
  }

  public function fkey_sql()
  {
    return '
      SELECT 
        CONSTRAINT_NAME as "name", 
        CONSTRAINT_SCHEMA as "schema", 
        table_name as "table", 
        column_name as "column", 
        referenced_table_schema as "ref_schema",
        referenced_table_name as "ref_table",
        referenced_column_name as "ref_column"
      FROM information_schema.key_column_usage 
      WHERE 
        referenced_table_name IS NOT NULL 
        AND CONSTRAINT_SCHEMA=database()
      ORDER BY table_name, ordinal_position;';
  }

  public function sequence_sql($column_id)
  {
    //$desc = Db::desc_table($table_name);
    //$sequence = $desc[$column_name]['default'];
    //return str_replace('nextval(', 'currval(', $sequence);
  }

}

