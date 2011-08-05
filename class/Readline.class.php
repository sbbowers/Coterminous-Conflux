<?php

class Readline
{
  public
    $last_line = '';
    
  protected 
    $prompt = '',
    $handle = null,
    $process = null,
    $history_file,
    $history = array(),
    $descriptorspec = array(
      0 => STDIN,  
      1 => STDOUT,  
      2 => array("pipe", "w"),
      3 => array("pipe", "r"), // read history from here
      4 => array("pipe", "r"), // read completion functions from here
    );


  public function __construct($prompt = '', $history_file = null)
  {
    $this->prompt = $prompt;
    $this->history_file = $history_file;

    $this->process = proc_open(dirname(__DIR__).'/bin/c++/c,read/c,read "'.$prompt.'"', $this->descriptorspec, $this->handle);

    if($this->history_file)
      $this->read_history();

    $this->load_autocompete();
  }

  public function read()
  {
    if(is_resource($this->process) && is_resource($this->handle[2]) && !feof($this->handle[2]))
    {
        $this->last_line = fgets($this->handle[2]);
        if($this->last_line)
          $this->history[] = rtrim($this->last_line);

        return $this->last_line;
    }
    return false;
  }

  public function save()
  {
    if($this->history_file)
      file_put_contents($this->history_file, implode("\n", array_slice($this->history, -1000)));

      print implode("\n", array_slice($this->history, -1000));
  }

  protected function read_history()
  {
    if($this->history_file)
    {
      $content = file_get_contents($this->history_file);
      fprintf($this->handle[3], $content);
      $this->history = explode("\n", $content);
    }
    fclose($this->handle[3]);
  }

  protected function load_autocompete()
  {
    $variables = array_keys($GLOBALS);
    $constants = array_keys(get_defined_constants());
    $funcions  = get_defined_functions();
    $tokens = array_merge($constants, $variables, $funcions['internal'], $funcions['user']);
    fprintf($this->handle[4], implode("\n", $tokens));
    fclose($this->handle[4]);
  }
}
