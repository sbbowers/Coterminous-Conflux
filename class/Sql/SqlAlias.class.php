<?php

class SqlAlias
{
  public $subject, $alias;

  public function __construct($subject, $alias = null)
  {
    list(, $this->subject, $this->alias) = Regex::match('/(.*)(?:\s+as\s+('.SqlBuilder::IDENTIFIER.')\s*)/i', $subject, 4);
    if(!$this->subject)
      $this->subject = $subject;
    if($alias)
      $this->alias = $alias;
  }


  public function id()
  {
    return $this->alias ?: $this->subject;
  }


  public function alias_defined()
  {
    return $this->alias;
  }

  public function build()
  {
    if($this->alias)
      return sprintf('%s AS %s', $this->subject, $this->alias);
    else 
      return $this->subject;
  }

  public function __toString()
  {
    return $this->build();
  }
}