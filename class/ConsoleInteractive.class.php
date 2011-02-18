<?php

class ConsoleInteractive extends Console
{
	protected
		$nesting_block = '',
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
	}

	public function start()
	{
		while($this->running)
		{
			$this->get_line();
			if(!$this->get_command())
				continue;

			ob_start();
			$__ret = eval($this->get_command());
			$__output = ob_get_contents();
			ob_end_clean();

			echo Console::color().preg_replace('/(?<=.)\n*$/', "\n", $__output);
			echo Console::color('light green');
			var_dump($__ret);
			echo Console::color();
		}
	}

	protected function current_nest()
	{
		return substr($this->nesting_block, -1) ?: '>';
	}

	// Read a line of input
	protected function get_line()
	{
		$line = readline(Console::color('blue')."php".$this->current_nest()." ".Console::color());
		if($line === false)
			echo "\n" && exit();

		if(strlen($line) == 0)
			return;

		if($line != $this->line) // Add to history if applicable
		{
			readline_add_history($line);
			$this->line = $line;
		}
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
			'"' => '/^([^\\\\]*|(\\\\{2})*|(\\\\"))*?\"/',
			"'" => '/^([^\\\\]*|(\\\\{2})*|(\\\\\'))*?\'/',
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

	// Looks to see if the current buffer contains a command and returns it
	protected function get_command()
	{
		static $command = '';

		if(!$this->line)
			return $command;

		if($this->current_nest() == '>')
			$command = '';

		while(strlen($this->line))
			$command.= $this->get_tokens($this->line);

		if($this->current_nest() != '>')
			return '';

		$command = $this->scrub_command($command);

		return $command;
	}

	// Sanitize a command for semi-colons and alter it to return a value from eval if appropriate
	protected function scrub_command($command)
	{
		$command = trim($command);
		if(strpos($command, '{') === false)
		{
			$command = preg_replace('/[\s;]*$/', '', $command).';';
			if(strpos($command, ';') == strlen($command) - 1 && !Regex::match('/^(echo|return)/', $command))
				$command = 'return '.$command;
		}

		return $command;
	}

	// Tab completion function for readline
	public function complete($line)
	{
		$variables = array_keys($GLOBALS);
		$constants = array_keys(get_defined_constants());
		$funcions  = get_defined_functions();
		return array_merge($constants, $variables, $funcions['internal'], $funcions['user']);
	}

}