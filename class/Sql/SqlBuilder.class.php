<?php

class SqlBuilder {
  const IDENTIFIER = '[a-zA-Z][a-zA-Z0-9_]*';
  const COLUMN = '[a-zA-Z][a-zA-Z0-9_]*\.[a-zA-Z][a-zA-Z0-9_]*';

  protected 
    $subject,
    $criteria,
    $joins,
    $set,
    $prev_command,
    $type,
    $commands, 
    $context;

  public function __construct($context, $commands)
  {
    $this->context = $context;
    $this->commands = $commands;
  }

  public function build($original_context = null)
  {
    $this->subject = $this->joins = $this->set = array();
    $this->criteria = new SqlCriteria();
    $this->prev_command = null;

    foreach($this->context->get_joins() as $table)
      $this->joins[$table] = new SqlCriteria();

    foreach($this->commands as $com)
    {
      list($alias, $command, $args) = $com;
      $handler = 'handle_'.$command;

      if(!method_exists($this, $handler))
        throw new SqlException("Unrecognized SQL reserve word '$command'");

      // reserve words handlers are prefixed with 'handle_'
      // $this->handle_{$command}($alias, $args)
      call_user_func_array(array($this, $handler), array($alias, $args));

      $this->prev_command = $command;
    }

    switch($this->type)
    {
      case 'select': return $this->build_select();
      case 'update': return $this->build_update();
      case 'delete': return $this->build_delete();
      default:       return $this->build_criteria();
    }
  }

  protected function build_criteria()
  {
    return sprintf('(%s)', $this->criteria->build());
  }

  protected function build_select()
  {
    $ret = 'SELECT '.implode(',', $this->subject);

    if($this->joins)
    {
      $joins = array();
      foreach($this->joins as $join => $criteria)
      {
        $table = $this->context->get_table($join);
        $criteria = $criteria->build($this->context);

        if($criteria)
          $joins[] = sprintf('%s ON (%s)', $table->build(), $criteria);
        else
          $joins[] = $table->build();

      }
      $ret.= ' FROM '.implode(' JOIN ', $joins);
    }

    if($this->criteria)
      $ret.= ' WHERE '.$this->criteria->build($this->context);


    return $ret;
  }

  protected function build_delete()
  {
    return sprintf('DELETE FROM `%s` WHERE %s', $this->subject[0], $this->$this->criteria->build());
  }

  protected function build_update()
  {
    return sprintf('UPDATE FROM `%s` WHERE %s', $this->subject[0], $this->$this->criteria->build());
  }

  public function handle_select($context_table, $args)
  {
    $this->set_type('select');

    foreach($args as $set)
      foreach(explode(',', $set) as $arg) // needs a better solution than explode; could get caught on functions
        $this->subject[] = new SqlAlias($arg);
  }

  public function handle_update($context_table, $args)
  {
    $this->set_type('update');

    if($this->subject)
      throw new SqlException("Can't update multiple tables");
    if(count($args) != 1)
      throw new SqlException("Update requires one parameter");

    $this->subject[] = $args[0];
  }

  public function handle_delete($context_table, $args)
  {
    $this->set_type('delete');

    if($this->subject)
      throw new SqlException("Can't delete multiple tables");
    if(count($args) != 1)
      throw new SqlException("Delete requires one parameter");

    $this->subject[] = $args[0];
  }

  protected function handle_as($context_table, $args)
  {
    if($prev_command == 'select')
    {
      list($alias) = $args;
      $obj = array_shift($this->subject);
      $obj->alias = $alias;
      array_unshift($this->subject, $obj);
    }
  }

  protected function handle_where($context_table, $args)
  {
    $this->handle_and($context_table, $args);
  }

  protected function handle_and($context_table, $args)
  {
    $this->criteria->add('AND', new SqlExpression($context_table, $args));
  }

  protected function handle_or($context_table, $args)
  {
    $this->criteria->add('OR', new SqlExpression($context_table, $args));
  }

  protected function handle_join($context_table, $args)
  {
    array_shift($args); // remove the table name. tables are already recorded in the context

    if($args) // Any remaining arguments follow the ON syntax
      $this->handle_on($context_table, $args);
  }

  protected function handle_on($context_table, $args)
  {
    $this->joins[$context_table->id()]->add('AND', new SqlExpression($context_table, $args));
  }


  protected function set_type($type)
  {
    if($this->type)
      throw new SqlException("Can't change SQL query type ({$this->type} to $type)");
    $this->type = $type;
  }

}