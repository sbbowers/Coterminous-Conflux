<?php

// Replacement for getopt() that doesn't stop parsing when it sees an unexpected value

class GetOpt
{
	protected
		$args = array(),
		$short_opts = array(),
		$long_opts = array(),
		$values = array(),
		$unprocessed = array();

	public function __construct($short_opts = '', $long_opts = array())
	{
		global $argv;
		$this->args = array_slice($argv, 1);

		foreach(Regex::select_all('/[a-zA-Z]:*/', $short_opts) as $opt)
		{
				$key = Regex::select('/^[^:]*/', $opt);
				if($key)
					$this->short_opts[$key] = strlen(Regex::select('/:*$/', $opt));
		}
		foreach($long_opts as $opt)
		{
				$key = Regex::select('/^[^:]*/', $opt);
				if($key)
					$this->long_opts[$key] = strlen(Regex::select('/:*$/', $opt));
		}		

		$this->parse();
	}

	public function values()
	{
		return $this->values;
	}

	public function extra()
	{
		return $this->unprocessed;
	}

	protected function parse()
	{

		while($arg = array_shift($this->args))
		{
			if($this->parse_long($arg))
				continue;

			if($this->parse_short($arg))
				continue;

			$this->unprocessed[] = $arg;
		}
	}

	protected function parse_long($arg)
	{
		list(,$key, $value) = Regex::match('/--([a-zA-Z1-9_-]*)=?(.*)?/', $arg) + array(null,null,null);
		if(!$key)
			return;

		if(!isset($this->long_opts[$key]))
			return;

		if(!$this->accepts_value($key))
			$value = null;
		if($this->requires_value($key) && !$value)
			return;

		$this->values[$key] = $value;
		return true;
	}

	protected function parse_short($arg)
	{
		list(,$arg) = Regex::match('/-([a-zA-Z1-9]*)?/', $arg) + array(null,null,null);
		if(!$arg)
			return;

		if(strlen($arg) > 1)
		{
			$args = array_map(function($t){return '-'.$t;}, str_split($arg));
			$this->args = array_merge($args, $this->args);
			return true;
		}
		if(!isset($this->short_opts[$arg]))
			return;

		$value = null;
		if($this->accepts_value($arg) && count($this->args))
		{
			$value = array_shift($this->args);
			if($value[0] == '-')
			{
				array_unshift($this->args, $value);
				$value = null;
			}
		}
		if($this->requires_value($arg) && !$value)
			return;

		$this->values[$arg] = $value;
		return true;
	}

	protected function accepts_value($opt)
	{
		return @$this->long_opts[$opt] > 0 || @$this->short_opts[$opt] > 0;
	}

	protected function requires_value($opt)
	{
		return @$this->long_opts[$opt] > 1 || @$this->short_opts[$opt] > 1;
	}

}
