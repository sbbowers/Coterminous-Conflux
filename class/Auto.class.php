<?php
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
		self::$APATH = getenv('APATH');
		self::$FPATH = getenv('FPATH');

		// For CLI support
		if(!self::$FPATH)
			self::$FPATH = dirname(__DIR__);
	
		spl_autoload_register(array(__NAMESPACE__.'\\'.__CLASS__, 'load'));

		self::cache_files();
	}

	public static function class_name($class)
	{
		if(isset(self::$classes[$class]))
			return self::$classes[$class];
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
			$files = explode("\n", `find $dir |grep -P '\.php$' | xargs grep -P '^\s*class\s+\w+|^\s*namespace\s+\w+|^\s*interface\s+\w+|^\s*abstract\s+class\s+\w+'`);
			foreach($files as $file)
			{
				if(preg_match('/(.*):\s*namespace\s+(\w+)/', $file, $matches))
					list(,$ns_file,$ns) = $matches;

				if(preg_match('/(.*):\s*(class|interface|abstract\s+class)\s+(\w+)/', $file, $matches))
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
		if(isset(self::$classes[$class]))
			return self::$classes[$class];
	}

	public static function load($class)
	{
		if(isset(self::$classes[$class]))
			require_once self::$classes[$class];
		
		// Call static autoload function if defined
		if(is_callable($class.'::__autoload'))
		  $class::__autoload();
	}
}

// Startup the autoloader
Auto::__autoload();
