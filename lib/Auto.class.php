<?php
namespace C;

// Class Auto - Autoloader class
//	Supports framework directory specified by FPATH environment variable
//	Supports application directory specified by APATH environment variable

class Auto 
{	
	public static
		$APATH = null,
		$FPATH = null;
	private static
		$classes = array();

	// Set up initial framework search paths
	public static function __autoload()
	{
		self::$FPATH = dirname(__DIR__);
		self::$APATH = self::detect_project();

		spl_autoload_register(array(__CLASS__, 'load'));

		self::cache_files();
		self::import_initializers();
	}

	public static function class_name($class)
	{
		if(isset(self::$classes[$class]))
			return self::$classes[$class];
	}

	public static function search_class_name($class)
	{
		$class = strtolower($class);
		foreach(self::$classes as $class_name => $file_path)
		{
			if(strtolower($class_name) == $class)
				return $class_name;
		}
		return false;
	}

	private static function cache_files()
	{
		$ns = $ns_file = null;
		$dirs = array(self::$FPATH);
		if(self::$APATH)
			$dirs[] = self::$APATH;
		array_unique($dirs);

		foreach($dirs as $dir)
		{
			$files = explode("\n", `find $dir |grep -P '\.php$' | xargs grep -P '^\s*class\s+\w+|^\s*namespace\s+\w+|^\s*interface\s+\w+|^\s*trait\s+\w+|^\s*abstract\s+class\s+\w+'`);
			foreach($files as $file)
			{
				if(preg_match('/(.*):\s*namespace\s+([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff\\\\]*)/', $file, $matches))
					list(,$ns_file,$ns) = $matches;

				if(preg_match('/(.*):\s*(class|interface|trait|abstract\s+class)\s+([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)/', $file, $matches))
				{
					list(, $c_file, ,$class) = $matches;
				
					if($c_file == $ns_file)
						$class = $ns.'\\'.$class;

					self::$classes[$class] = $c_file;
				}
			}
		}
	}

	// Return the directory of a class
	public static function resolve_class($class)
	{
		$class_name = is_object($class) ? get_class($class) : $class;
		if(isset(self::$classes[$class_name]))
			return self::$classes[$class_name];
	}

	public static function load($class)
	{
		if(isset(self::$classes[$class]))
			require_once self::$classes[$class];
		else
		{
			$callbacks = Config::find('autoload', 'callbacks');
			foreach($callbacks as $method)
			{
				$valid = false;
				if(is_callable($method))
					$valid = call_user_func($method, $class);
				if($valid)
					break;
			}
		}
		
		// Call static autoload function if defined
		if(is_callable($class.'::__autoload'))
		  $class::__autoload();
	}

	protected static function detect_project()
	{
		$paths = array(dirname(dirname(realpath($_SERVER["SCRIPT_FILENAME"]))), getcwd());
		
		foreach($paths as $path)
		{
			while($path != '/')
			{
				if(file_exists("$path/.coterminousconflux.project"))
					return $path;
				else 
					$path = dirname($path);
			}
		}

		// Needed to built in framework apps can access the application
		if(file_exists(getenv('APATH')))
			return getenv('APATH');
	}

	protected static function import_initializers()
	{
		$dir = self::$FPATH.'/lib/initializers';
		$files = explode("\n", trim(`find $dir -type f`));
		foreach($files as $file)
			require_once $file;
	}
}

// Startup the autoloader
Auto::__autoload();
