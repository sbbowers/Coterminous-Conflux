<?php
namespace C;

class SqlExpression
{
  protected 
    $context,
    $table, 
    $args;

  public function __construct($table, $args)
  {
    $this->table = $table;
    $this->args = $args;
  }

  public function build(SqlContext $context)
  {
    $context->float($this->table);
    $this->context = $context;

    switch(count($this->args))
    {
      case 1:  return $this->build1($this->args[0]);
      case 2:  return $this->build2($this->args[0],$this->args[1]);
      default: return $this->build3($this->args[0],$this->args[1],$this->args[2]);
    }
  }

  protected function build1($sql)
  {
    return $sql;
  }

  protected function build2($loperand, $roperand)
  {
    return sprintf('%s = %s', $loperand, $roperand);
  }

  protected function build3($loperand, $operator, $roperand)
  {
    return sprintf('%s %s %s', $loperand, $operator, $roperand);
  }

}