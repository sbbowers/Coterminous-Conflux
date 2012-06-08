<?php

class SqlContext
{
  protected 
    $default_subject = null, 
    $tables = array();

  public function float(SqlAlias $alias)
  {
    $id = $alias->id();
    $table = $this->tables[$id];
    unset($this->tables[$id]);
    $this->tables = array_reverse($this->tables, true);
    $this->tables[$id] = $table;
    $this->tables = array_reverse($this->tables, true);
  }

  // $table = "tablename" | "tablename as alias"
  public function set($table)
  {
    if(!isset($this->tables[$table]))
    {
      $table = new SqlAlias($table);
      $this->tables[$table->id()] = $table;
    }
    else
      $table = $this->tables[$table];

    $this->float($table);

    if(!$this->default_subject)
      $default_subject = $table;

    return $this;
  }

  // Directly modifies the latest table to redefine as an alias
  public function set_alias($alias)
  {
    $table = array_shift($this->tables);
    if($table->alias_defined())
      throw new SqlException("Attempt to redefine table '{$table->subject}' alias from '{$this->alias}'' to '{$alias}'");
    $table->alias = $alias;
    $this->tables[$table->id()] = $table;
    $this->float($table);
    return $this;
  }

  public function get()
  {
    return current($this->tables);
  }

  public function get_joins()
  {
    return array_keys($this->tables);
  }

  public function get_table($alias)
  {
    return $this->tables[$alias];
  }

}