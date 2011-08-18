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
      3 => array("pipe", "w"), // read history from here
      4 => array("pipe", "w"), // read completion functions from here
      5 => array("pipe", "r+"), // send read signal
      6 => array("pipe", "w+"), // recieve input
    );
  CONST SIG_PIPE = 5;
  CONST COM_PIPE = 6;


  public function __construct($prompt = '', $history_file = null)
  {
    $bin = Auto::$FPATH.'/bin/c++/readline_wrapper/readline_wrapper';
    $this->prompt = $prompt;
    $this->history_file = $history_file;

    $this->process = proc_open("$bin '$prompt'", $this->descriptorspec, $this->handle);

    if($this->history_file)
      $this->read_history();

    $this->load_autocompete();

    pcntl_signal(SIGINT, function(){});
  }

  public function read()
  {
    fprintf($this->handle[self::SIG_PIPE], "read\n");
    if(is_resource($this->process) && is_resource($this->handle[self::COM_PIPE]) && !feof($this->handle[self::COM_PIPE]))
    {
        $this->last_line = fgets($this->handle[self::COM_PIPE]);
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
  }

  protected function read_history()
  {
    if(file_exists($this->history_file))
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
