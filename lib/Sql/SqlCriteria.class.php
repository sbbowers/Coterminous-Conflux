<?php
namespace C;

class SqlCriteria 
{
  protected
    $criteria = array();

  public function add($type, $expression)
  {
    $this->criteria[] = array(strtoupper($type), $expression);
  }

  public function build(SqlContext $context)
  {
    $use_type = false;
    $ret = '';
    foreach($this->criteria as $criterion)
    {
      list($type, $expression) = $criterion;
      if($use_type)
        $ret.= ' '.$type.' ';

      $ret.= $expression->build($context);
      $use_type = false;
    }
    return $ret;
  }

  public function is_empty()
  {
    return !$this->criteria;
  }

}
