<?php
class DatabaseMysql extends Database
{
  protected 
    $context = null,
    $connection = null;

  public function __construct($config = null)
  {
    if($config === null)
      return;

    $this->connection = new mysqli($config['host'], $config['user'], $config['password'], $config['database']);

    if($this->connection->connect_error)
      throw new Exception('DB Connection Error: '.$this->connection->connect_errno.' '.$this->connection->connect_error);
  }

  public function exec($sql)
  {
    $this->context = $this->connection->query($sql);
    if(!$this->context)
      throw new Exception('Error Sending Query: '.$this->connection->connect_errno.' '.$this->connection->error);

    return new DatabaseResult($this, $this->context, $sql);
  }

  public function affected_rows()
  {
    return $this->connection->affected_rows;
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
        return "'".$this->connection->real_escape_string($value)."'";

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

