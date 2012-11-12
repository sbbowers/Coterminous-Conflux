<?php

function db($connection = 'default')
{
  return Database::connect($connection);
}

function sql()
{
  $args = func_get_args();
  $sql = new Sql();
  call_user_func_array(array($sql, 'where'), $args);
  return $sql;
}

function select()
{
  $args = func_get_args();
  $sql = new Sql();
  call_user_func_array(array($sql, 'select'), $args);
  return $sql;
}

function update()
{
  $args = func_get_args();
  $sql = new Sql();
  call_user_func_array(array($sql, 'update'), $args);
  return $sql;
}

function delete()
{
  $args = func_get_args();
  $sql = new Sql();
  call_user_func_array(array($sql, 'delete'), $args);
  return $sql;
}