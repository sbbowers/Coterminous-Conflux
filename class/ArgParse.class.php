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
		$values = array(),
		$extra = array(),
		$shorts = array(),
		$arguments = array(),
		$ordered_args = array(),
		$description;

	public function __construct($description, $help = true)
	{
		$this->description = $description;
		if($help)
			$this->add_flag_argument('help', 'Display this help message');
	}

	public function add_flag_argument($param, $help = '')
	{
		$this->add_argument($param, $help, '');
	}

	public function add_optional_argument($param, $help = '')
	{
		$this->add_argument($param, $help, ':');
	}

	public function add_required_argument($param, $help = '')
	{
		$this->add_argument($param, $help, '::');
	}

	public function add_ordered_argument($param, $help = '')
	{
		$this->ordered_args[$param] = $help;
	}

	public function parse()
	{
		$long_opts = array_map(function($t){return $t[0];}, $this->arguments);
		$short_opts = array_map(function($t){return $t[1];}, $this->arguments);

		$gopt = new GetOpt(implode($short_opts), $long_opts);
		foreach($gopt->values() as $key => $value)
		{
			if(isset($this->shorts[$key]))
				$this->values[$this->shorts[$key]] = $value;
			else
				$this->values[$key] = $value;
		}
		$this->extra = $gopt->extra();

		if(array_key_exists('help', $this->values))
		{
			$this->help();
			exit();
		}

		return array_merge($this->values, $this->extra);
	}

	public function help()
	{
		global $argv;
		$name = Regex::select('/[^\/]+$/', $argv[0]);
		$argparam = array();

		print "Usage: $name";
		foreach($this->ordered_args as $arg => $help)
			print " <$arg>";

		foreach($this->arguments as $arg => $opts)
			switch(Regex::select('/:*$/', $opts[0]))
			{
				case '':   $argparam[$arg] = $arg; break;
				case ':':  $argparam[$arg] = $arg."[=<value>]"; break;
				case '::': $argparam[$arg] = $arg."=<value>"; break;
			}

		foreach($argparam as $param)
			print " [--$param]";

		print "\n\n$this->description\n\n";

		$width = max(array_map(function($t){return strlen($t);}, $argparam));

		foreach($argparam as $arg => $param)
		{
			$short = Regex::select('/[^:]/', $this->arguments[$arg][1]);
			if($short)
				$short="-$short,";
			printf("  %3s --%-{$width}s %s\n", $short, $param, $this->arguments[$arg][2]);
		}
	}


	protected function add_argument($param, $help, $type)
	{
		$short = $param[0].$type;
		if(isset($this->shorts[$short[0]]))
			$short = null;
		else
			$this->shorts[$short[0]] = $param;
			
		$this->arguments[$param] = array($param.$type, $short, $help);
	}
}
