<?php

class Console
{
	public static function shell()
	{
		$a = new ArgParse('Interactive Shell for Coterminous Conflux');
		$a->add_optional_argument('symfony_dir', 'Load symfony environment defined by "dir:app:env:debug"; if <value> left blank, load from env variable $COTERMINOUS_SYMFONY');
		$opts = $a->parse();

		Console::out(`php -v | head -1`, 'gray');
		self::detect_symfony($opts);
		$c = new ConsoleInteractive();
		$c->start();
	}	

	public static function prompt($question, $default_answer = null)
	{
		print $question.' ';
		if($default_answer)
			print "($default_answer) ";

		$response = fgets(STDIN);

		if($default_answer !== null && $response != '')
			return $default_answer;

		return str_replace("\n", '', $response);
	}

	public static function out($text, $color_desc = null)
	{
		print Console::text($text, $color_desc);
	}

	public static function text($text, $color_desc = null)
	{
		if($color_desc)
			return Console::color($color_desc).$text.Console::color();
		else
			return $text;
	}

	public static function color($color_desc = 'white on transparent')
	{
		static $fore_colors = array(
			'dark black'   => '30',
			'dark red'     => '31',
			'dark green'   => '32',
			'dark yellow'  => '33',
			'dark blue'    => '34',
			'dark purple'  => '35',
			'dark cyan'    => '36',
			'dark white'   => '37',
			'light black'  => '1;30',
			'light red'    => '1;31',
			'light green'  => '1;32',
			'light yellow' => '1;33',
			'light blue'   => '1;34',
			'light purple' => '1;35',
			'light cyan'   => '1;36',
			'light white'  => '1;37',
			// gray is nice to have too
			'dark gray'    => '1;30',
			'light gray'   => '37',
			'dark grey'    => '1;30',
			'light grey'   => '37');

		static $back_colors = array(
			'dark black'   => '40',
			'dark red'     => '41',
			'dark green'   => '42',
			'dark yellow'  => '43',
			'dark blue'    => '44',
			'dark purple'  => '45',
			'dark cyan'    => '46',
			'dark white'   => '47',
			'light black'  => '100',
			'light red'    => '101',
			'light green'  => '102',
			'light yellow' => '103',
			'light blue'   => '104',
			'light purple' => '105',
			'light cyan'   => '106',
			'light white'  => '107',
			// gray is nice to have too
			'dark gray'    => '100',
			'light gray'   => '47',
			'dark grey'    => '100',
			'light grey'   => '47',
			// And of course transparent
			'light transparent'  => '0',
			'dark transparent'   => '0',
			'light clear'  => '0',
			'dark clear'   => '0',
			);   
			 		
		list($fore, $back) = explode(' on ', strtolower(trim($color_desc))) + array(null, null);
		list($fore, $fore_mod) = array_reverse(preg_split('/\s+/',$fore)) + array(null, null);
		list($back, $back_mod) = array_reverse(preg_split('/\s+/',$back)) + array(null, null);
		$fore = in_array($fore_mod, array('light', 'bright', 'very')) ? "light $fore" : "dark $fore";
		$back = in_array($back_mod, array('light', 'bright', 'very')) ? "light $back" : "dark $back";
		$fore = isset($fore_colors[$fore]) ? $fore_colors[$fore] : '0;37';
		$back = isset($back_colors[$back]) ? $back_colors[$back] : '0';

		return chr(27)."[{$back};{$fore}m";
	}

	public static function get_width()
	{
		preg_match_all("/columns.([0-9]+)/", exec('stty -a |grep columns'), $output);
		if($output)
			return $output[1][0];
	}

	public static function get_height()
	{
		preg_match_all("/rows.([0-9]+)/", exec('stty -a |grep rows'), $output);
		if($output)
			return $output[1][0];
	}

	protected static function detect_symfony($opts)
	{
		$config = isset($opts['symfony_dir']) ? $opts['symfony_dir'] : getenv('COTERMINOUS_SYMFONY');
		if(!$config)
			return;

		list($root, $app, $env, $debug) = explode(':', $config) + array(null, null, null, 1);

		if($root && $app && $env) // integrate with symfony
		{
			define('SF_ROOT_DIR',    $root);
			define('SF_APP',         $app);
			define('SF_ENVIRONMENT', $env);
			define('SF_DEBUG',       $debug);

			$file = SF_ROOT_DIR.DIRECTORY_SEPARATOR.'apps'.DIRECTORY_SEPARATOR.SF_APP.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'config.php';

			if(file_exists($file))
			{
				try
				{
					require_once($file);
					// initialize database manager
					$databaseManager = new sfDatabaseManager();
					$databaseManager->initialize();
					Console::out('Symfony environment detected: '.$config."\n", 'gray');
					return;
				}
				catch (Exception $e) { echo $e; }
			}
			else
				return Console::out('Failed to load Symfony environment "'.$config.'". Does the symfony directory '.$root.' exist?'."\n", 'red');
		}
		
		Console::out('Failed to load Symfony environment "'.$config.'". Did you follow the correct format of "dir:app:env:debug"?'."\n", 'red');
	}

}
