<?php

class Scaffold
{
	public static 
		$commands = array();
	protected
		$command = null, 
		$config = array();

	public static function __autoload()	
	{
		// Get list of commands
		foreach(glob(Resolve::$FPATH.'/scaffold/*', GLOB_ONLYDIR) as $command)
			self::$commands[] = basename($command);
	}

	public static function build($command)
	{
		$s = new Scaffold($command);
		$s->help();
	}	

	public function __construct($command = null)
	{
		$this->command = $command;

		foreach(self::$commands as $command)
		{
			$this->config[$command] = Config::load(Resolve::$FPATH."/scaffold/$command/$command.scaffold.yml");

			if(isset($this->config[$command]['options']))
			{
				$opts = array();

				foreach(array_reverse(array_keys($this->config[$command]['options'])) as $opt)
					$opts[$opt[0]] = $opt;
			
				foreach($opts as $k => $opt)
					$this->config[$command]['options'][$opt]['short_opt'] = $k;
			}
		}

		if($command)
		{
			$opts = $this->get_options($command);
			$gopt = new GetOpt(implode($opts), array_keys($opts));
			var_dump($gopt->values());
			var_dump($gopt->extra());			
		}




	}

	protected function get_recipe_config($command = null)
	{
		return $this->config[$command];
	}

	protected function get_options($command)
	{
		$config = $this->get_recipe_config($command);
		if(!$config)
			return;

		$opts = array_merge(array('help'), array_keys($config['options']));

		$ret = $used = array();
		foreach($opts as $opt)
		{
			list($value, $req) = Regex::match_only('/(.)[^:]*([:]*)/', $opt);
			$ret[$opt] = in_array($value, $used) ? null : $value.$req;
			$used[] = $value;
		}

		return $ret;
	}


	public function help()
	{
		echo "c,create - Create files of common programming tasks based off recipes\n\n";
		echo "Usage: c,create <recipe> [-h|--help] [[--<option>=<value>|-<o>=<v>]...]\n\n";
		echo "Available Recipes:\n";

		foreach(self::$commands as $command)
		{
			$config = $this->get_recipe_config($command);
			printf("  %-24s %s\n", $command, @$config['description']);

			if(isset($config['options']))
			{
				foreach($config['options'] as $var => $info)
					print str_pad('',27).sprintf("%3s--%-16s %s\n", isset($info['short_opt']) ? "-$info[short_opt]|":'', $var, @$info['description'] ?: $info['prompt']);
			}
		}
		exit;
	}

}