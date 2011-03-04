<?php

class ConsoleInteractive extends Console
{
	protected
		$nesting_block = '',
		$command = '',
		$line = '',
		$running = true;

	public function __construct()
	{
		if (version_compare(phpversion(), "4.3.0", "<"))
		{
			echo "PHP 4.3.0 or above is required.\n";
			exit(111);
		}

		if(!function_exists("readline_completion_function"))
			echo "Interactive Console requires readline\n" && exit();

		error_reporting(E_ALL | E_STRICT);
		ob_implicit_flush(true);
		readline_completion_function("ConsoleInteractive::complete");
		pcntl_signal(SIGINT, array($this, "handle_interupt"));
		readline_read_history(getenv('HOME').'/.coterminousconflux/console_history');
	}

	public function start()
	{
		while($this->running)
		{
			$this->get_command();

			if(!$this->command_ready())
				continue;

			ob_start();
			$__ret = eval($this->command);
			$__output = ob_get_contents();
			ob_end_clean();

			echo Console::color().preg_replace('/(?<=.)\n*$/', "\n", $__output);
			echo Console::color('light green');
			var_dump($__ret);
			echo Console::color();
			$this->command = '';
		}
	}
	public function __destruct()
	{
		$dir = getenv('HOME').'/.coterminousconflux';

		if(!file_exists($dir))
			mkdir($dir);
		readline_write_history($dir.'/console_history');

		$f = file($dir.'/console_history');
		$t = array_shift($f);
		$f = implode('', array_slice($f, -1000));
		file_put_contents($dir.'/console_history', $t.$f);
	}

	protected function current_nest()
	{
		return substr($this->nesting_block, -1) ?: '>';
	}
	protected function command_ready()
	{
		return $this->current_nest() == '>' && $this->command !== '';
	}

	// Read a line of input
	protected function get_command()
	{
		static $last_command = '';
		$this->line = readline(Console::color('blue')."php".$this->current_nest()." ".Console::color());
		pcntl_signal_dispatch();

		if($this->line === false)
		{
			print "\n";
			exit();
		}

		if(strlen($this->line) == 0)
			return;

		while(strlen($this->line))
			$this->command.= $this->get_tokens($this->line);

		if($this->command_ready() && $last_command != $this->command)
		{
			readline_add_history($this->command);
			$last_command = $this->command;
		}

		$this->scrub_command();
	}

	// Parse out tokens for determining if php is valid
	protected function get_tokens(&$str)
	{
		$nest_close = array(
			'{' => '}',
			'(' => ')',
			'"' => '"',
			"'" => "'",
			);
		$nest_regex = array(
			'>' => '/^.*?[({\'"]/', // starting level
			'{' => '/^.*?[({}\'"]/',
			'(' => '/^.*?[()\'"]/',
			'"' => '/^(([^\\\\])|([\\\\].))*?"/',
			"'" => '/^(([^\\\\])|([\\\\].))*?\'/',
			);

		$nest = $this->current_nest();
		list($match) = Regex::match($nest_regex[$nest], $str) + array(null);

		if($match == '') // No match
		{
			$match = $str;
			$str = '';
			return $match;
		}
		else if($nest != '>' && $nest_close[$nest] == substr($match, -1)) // Found closing character
		{
			$this->nesting_block = substr($this->nesting_block, 0, -1);
			$str = substr($str, strlen($match));
			return $match;
		}
		else // Found opening character
		{
			$this->nesting_block.= substr($match, -1);
			$str = substr($str, strlen($match));
			return $match;
		}
	}

	// Sanitize a command for semi-colons and alter it to return a value from eval if appropriate
	protected function scrub_command()
	{
		$this->command = trim($this->command);
		if(strpos($this->command, '{') === false)
		{
			$this->command = preg_replace('/[\s;]*$/', '', $this->command).';';
			if(strpos($this->command, ';') == strlen($this->command) - 1 && !Regex::match('/^(echo|return)/', $this->command))
				$this->command = 'return '.$this->command;
		}
	}

	// Tab completion function for readline
	public function complete($line)
	{
		$variables = array_keys($GLOBALS);
		$constants = array_keys(get_defined_constants());
		$funcions  = get_defined_functions();
		return array_merge($constants, $variables, $funcions['internal'], $funcions['user']);
	}

	// Inturupt handler
	public function handle_interupt()
	{
		$this->line = '';
		$this->command = '';
		$this->nesting_block = '';
	}

}
