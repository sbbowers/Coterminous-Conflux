<?php
/* 
ArgParse - Argument parsing class for the command line

Usage:
	$a = new ArgParse('This application controls startup and shutdown of the service');
	$a->add_ordered_argument('command');
	$a->add_flag_argument('daemon', "Run as a daemon");
	$a->add_optional_argument('port', "Port to listen on; default 80");
	$a->add_required_argument('cluster');
	print_r($a->parse());

Run as: `myApplication start -d --port=12345 -c mycluster`:
	Array
	(
	    [daemon] => 
	    [port] => 12345
	    [cluster] => mycluster
	    [0] => start
	)

Run as: `myApplication --help`:
	Usage: myApplication <command> [--help] [--daemon] [--port[=<value>]] [--cluster=<value>]

	This application controls startup and shutdown of the service

	  -h, --help            Display this help message
	  -d, --daemon          Run as a daemon
	  -p, --port[=<value>]  Port to listen on; default 80
	  -c, --cluster=<value> 
*/

class ArgParse
{
	protected
		$argv = array(),
		$shorts = '',
		$arguments = array(),
		$description;

	public function __construct($description, $help = true)
	{
		$this->description = $description;
		$this->set_argv($GLOBALS['argv']);
		if($help)
			$this->add_flag_argument('help', 'Display this help message');
	}

	public function set_argv($argv)
	{
		$this->argv = $argv;
	}

	public function add_flag_argument($param, $help = '')
	{
		$this->add_argument($param, $help, ArgParseArgument::FLAG);
	}

	public function add_optional_argument($param, $help = '')
	{
		$this->add_argument($param, $help, ArgParseArgument::OPTIONAL);
	}

	public function add_required_argument($param, $help = '')
	{
		$this->add_argument($param, $help, ArgParseArgument::REQIRED);
	}

	public function add_ordered_argument($param, $help = '')
	{
		$this->add_argument($param, $help, ArgParseArgument::ORDERED);
	}

	public function parse($exit_help = true)
	{
		$ret = array();
		$long_opts = array_filter(array_map(function($a){return $a->get_long();}, $this->arguments), 'trim');
		$short_opts = array_filter(array_map(function($a){return $a->get_short();}, $this->arguments), 'trim');
		$ordered_opts = array_filter($this->arguments, function($a){return $a->type == ArgParseArgument::ORDERED;});
		$ordered_opts = array_map(function($a){return $a->name;}, $ordered_opts);	

		$gopt = new GetOpt(implode($short_opts), $long_opts);
		foreach($gopt->values() as $key => $value)
		{
			foreach($this->arguments as $arg)
			{
				$tmp = $arg->matches($key);
				if($tmp)
				{
					$ret[$tmp] = $value;
					break;
				}
			}
		}
		foreach($gopt->extra() as $value)
		{
			if(!$ordered_opts)
				break;
			$ret[array_shift($ordered_opts)] = $value;
		}

		if($exit_help && array_key_exists('help', $ret))
		{
			$this->help();
			exit();
		}

		return $ret;
	}

	public function help()
	{
		print "Usage: ".basename($this->argv[0])." ".implode(' ', array_map(function($a){return $a->get_inline_format();}, $this->arguments));
		print "\n\n$this->description\n\n";

		$width = max(array_map(function($a){return strlen($a->get_option_format());}, $this->arguments));
		$wrap = Console::get_width() - $width - 8;
		foreach($this->arguments as $arg)
			printf("  %-{$width}s  %s\n", $arg->get_option_format(), wordwrap($arg->description, $wrap, "\n".str_pad('', $width + 4, ' ')));
	}

	protected function add_argument($param, $help, $type)
	{
		$this->arguments[] = new ArgParseArgument($param, $type, $help, $this->shorts);
	}

}

class ArgParseArgument {
	const 
		ORDERED = -1,
		FLAG = 0,
		OPTIONAL = 1, 
		REQIRED = 2;	
	public 
		$name = null, 
		$short = null,
		$type = null,
		$description = null;

	public function __construct($name, $type, $description, & $used_shorts)
	{
		static $placeholders = '0123456789abcdefghijklmnopqrstuvwxyz';
		$this->name = $name;
		$this->type = $type;
		$this->description = $description;
		if($this->type == self::ORDERED)
			return;
		//Find the best 1 character placeholder
		foreach(str_split($name.$placeholders) as $letter)
		{
			if(strpos($used_shorts, $letter) === false)
				$this->short = $letter;
			else if(strpos($used_shorts, strtoupper($letter)) === false)
				$this->short = strtoupper($letter);
			if($this->short)
				break;
		}
		$used_shorts.=  $this->short;
	}
	function get_short()
	{
		if($this->type != self::ORDERED)
			return $this->short.str_pad('', $this->type, ':');
	}
	function get_long()
	{
		if($this->type != self::ORDERED)
			return $this->name.str_pad('', $this->type, ':');
	}
	function matches($arg)
	{
		if($arg == $this->name || $arg == $this->short)
			return $this->name;
	}

	function get_option_format()
	{
		switch($this->type)
		{
			case ArgParseArgument::ORDERED:  
				return sprintf('<%s>', $this->name);
			case ArgParseArgument::FLAG:
				return sprintf('-%s, --%s', $this->short, $this->name);
			case ArgParseArgument::OPTIONAL:
				return sprintf('-%s [<value>], --%s[=<value>]', $this->short, $this->name);
			case ArgParseArgument::REQIRED:
				return sprintf('-%s <value>, --%s=<value>', $this->short, $this->name);
		}
	}

	function get_inline_format()
	{
		switch($this->type)
		{
			case ArgParseArgument::ORDERED:  
				return sprintf('<%s>', $this->name);
			case ArgParseArgument::FLAG:
				return sprintf('[--%s]', $this->name);
			case ArgParseArgument::OPTIONAL:
				return sprintf('[--%s[=<value>]]', $this->name);
			case ArgParseArgument::REQIRED:
				return sprintf('[--%s=<value>]', $this->name);
		}
	}	
}