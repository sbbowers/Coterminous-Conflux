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

		stream_set_blocking(STDIN, 0);
		error_reporting(E_ALL | E_STRICT);
		ob_implicit_flush(true);
		pcntl_signal(SIGINT, array($this, "handle_interupt"));
	}

	public function start()
	{
		while($this->running)
		{
			$this->get_command();

			if(!$this->command_ready())
				continue;

			echo Console::color('light red');
			unset($__ret);
			ob_start();
			try
			{
				$__ret = eval($this->command);
			}
			catch (Exception $e)
			{
				echo Console::text($e, 'dark red');
			}
			$__output = ob_get_contents();
			ob_end_clean();

			echo Console::color().preg_replace('/(?<=.)\n*$/', "\n", $__output);
			if(isset($__ret))
				echo $this->var_pretty($__ret)."\n";
			$this->command = '';
		}
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

		echo Console::text('php'.$this->current_nest().' ', 'light blue');
		do {
			pcntl_signal_dispatch();
			$this->line = fgets(STDIN);
			if(feof(STDIN) || ord($this->line) == 4)
			{
				echo "\n";
				exit;
			}
		} while(!$this->line);

		while(strlen($this->line))
			$this->command.= $this->get_tokens($this->line);

		if($this->command_ready() && $last_command != $this->command)
			$last_command = $this->command;

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
		if(strpos($this->command, '{') === false)
		{
			if(in_array($this->current_nest(),array('"','\'')))
				$this->command = $this->command."\n";
			if($this->current_nest() == '>')
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
		echo "\nphp".$this->current_nest().' ';
	}

	public function var_pretty($variable)
	{
		$fg = Console::color('light green');
		$bg = Console::color('dark green');
		$regex = array(
			'/\n\s*/' => ' ',
			'/\[("[^"]+")(:protected|:private|:public)?]/' => "[$fg\$1$bg]",
			'/\[(\d+)\]/' => "[$fg\$1$bg]",
			'/bool\((\w+)\)/' => "bool($fg\$1$bg)",
			'/string\((\d+)\) ("[^"]*")/' => "string(\$1) $fg\$2$bg",
			'/int\((\d+)\)/' => "int($fg\$1$bg)",
			'/float\((\d+\.?\d*)\)/' => "float($fg\$1$bg)",
			'/NULL/' => "{$fg}NULL$bg",
			);

		ob_start();
		var_dump($variable);
		$__output = ob_get_clean();

		return $bg.preg_replace(array_keys($regex), array_values($regex), $__output).Console::color();
	}
}
