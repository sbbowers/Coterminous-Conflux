<?php

class Recipe
{
	public static 
		$commands = array();
	protected
		$command = null, 
		$recipe_dir = null,
		$options = array(),
		$files = array(),
		$config = array();

	public static function __autoload()	
	{
		// Get list of commands
		foreach(glob(Auto::$FPATH.'/recipe/*', GLOB_ONLYDIR) as $command)
			self::$commands[] = basename($command);
	}

	public static function build()
	{
		$a = new ArgParse('Generate files of common programming tasks based off pre-defined recipes', false);
		$a->add_ordered_argument('recipe', 'Build a recipe');
		$a->add_flag_argument('help', 'Display help for the requested recipe');
		$opts = $a->parse(false);

		if(!isset($opts['recipe']))
		{
			$a->help();
			self::help();
			exit;
		}

		$config = self::get_recipe_config($opts['recipe']);
		$a = new ArgParse($config['description'], false);
		$a->add_ordered_argument('recipe', "The {$opts['recipe']} recipe");
		$a->add_flag_argument('help', "Display help for the {$opts['recipe']} recipe");
		foreach(@$config['options'] as $option => $data)
			$a->add_optional_argument($option, $data['description']);

		if(array_key_exists('help', $opts))
		{
			$a->help();
			exit;
		}

		$s = new self($opts['recipe'], $a->parse());
	}	

	protected function __construct($command = null, $options = array())
	{
		$this->command = $command;
		$this->config = $this->get_recipe_config($command);
		$this->options = $this->get_options($options);
		$this->recipe_dir = Auto::$FPATH.'/recipe/'.$command;

		if(!isset(Auto::$APATH) && $this->options['recipe']=='project' && $this->options['path'])
		{
			if(!is_dir($this->options['path']))
				mkdir($this->options['path'], 0755, true);
			Auto::$APATH = realpath($this->options['path']);
			chdir(AUTO::$APATH);
		}

		if(!isset(Auto::$APATH))
			die("Cannot locate the coterminous conflux application path\n");

		$this->files = Resolve::files($this->recipe_dir.'/files');
		$this->execute();
	}

	protected function execute()
	{
		Console::out('[generate] ','light white');
		Console::out("Processing pre-generation hooks\n", 'light green');
		if(file_exists($this->recipe_dir.'/preprocess.php'))
			include $this->recipe_dir.'/preprocess.php';

		foreach($this->files as $oname)
		{
			$fname = str_replace('|', '/', Auto::$APATH.'/'.$oname);
			$fname = str_replace('/./', '/', $fname);
			$oname = $this->recipe_dir.'/files/'.$oname;
			$content = file_get_contents($oname);
			foreach($this->options as $search => $replace)
			{
				$fname = str_replace("{%$search%}", $replace, $fname);
				$content = str_replace("{%$search%}", $replace, $content);
			}
			$perms = fileperms($oname);
			$hperms = substr(decoct($perms), -4);
			if(is_dir($oname) || substr($oname, -1) == '|')
			{
				Console::out('[generate] ','light white');
				Console::out("Creating directory $hperms $fname\n", 'light green');				
				@mkdir($fname, 0777 , true);
			}
			else
			{
				Console::out('[generate] ','light white');
				Console::out("Creating file      $hperms $fname\n", 'light green');
				@mkdir(dirname($fname), 0777 , true);
				file_put_contents($fname, $content);
			}
			chmod($fname, $perms);
		}

		Console::out('[generate] ','light white');
		Console::out("Processing post-generation hooks\n", 'light green');
		if(file_exists($this->recipe_dir.'/postprocess.php'))
			include $this->recipe_dir.'/postprocess.php';
	}


	protected static function get_recipe_config($command = null)
	{
		if($command)
			return Config::load(Auto::$FPATH."/recipe/$command/recipe.yml");
	}

	protected function get_options($options)
	{
		foreach((array)$this->config['options'] as $name => $opt)
		{
			if(isset($options[$name]))
				continue;
			$values = isset($opt['values']) ? ' ['.implode(', ', $opt['values']).']' : '';
			$default = isset($opt['values']) ? $opt['values'][0] : null;
			$desc = isset($opt['description']) ? ', '.$opt['description'] : '';
			$options[$name] = Console::prompt("Value for <$name>$desc$values :", $default);
		}
		$options['fpath'] = Auto::$FPATH;
		return $options;
	}

	protected static function help()
	{
		echo "\nAvailable Recipes:\n\n";
		$width = max(array_map(function($t){return strlen($t);}, self::$commands));
		$wrap = Console::get_width() - $width - 8;
		foreach(self::$commands as $command)
		{
			$config = self::get_recipe_config($command);
			printf("  %-{$width}s  %s\n", $command, wordwrap(@$config['description'], $wrap, "\n".str_pad('', $width + 4, ' ')));
		}
	}

}