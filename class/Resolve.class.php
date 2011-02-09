<?php
// Class Auto -  Automatically load files and classes
//	Supports framework directory specified by FPATH environment variable
//	Supports application directory specified by APATH environment variable
//  Supports data directory specified by DPATH environment variable

class Auto 
{	
	public static
		$APATH = null,
		$FPATH = null,
		$DPATH = null;
	private static
		$cached_files = array();

		
	// Reserved for implementing cached file existance to avoid hitting the filesystem
	public static function exists($file)
	{
		return file_exists($file);
	}

	// Set up initial framework search paths
	public function startup()
	{
		self::$APATH = getenv('APATH');
		self::$FPATH = getenv('FPATH');
		self::$DPATH = getenv('DPATH');
	
		spl_autoload_register(array('Auto', 'load'));
	}

	public static function resolve_class($class)
	{
		$class = str_replace('\\', '/', $class);
		$search = array(
			self::$APATH.'/class/'.$class.'.class.php',
			self::$FPATH.'/class/'.$class.'.class.php',
			self::$APATH.'/render/'.$class.'/'.$class.'.class.php',
			self::$FPATH.'/render/'.$class.'/'.$class.'.class.php',
			);
		return self::resolve_file($class, 'class', $search);
	}

	public static function resolve_yaml($yaml)
	{
		$search = array(
			self::$APATH.'/config/'.$yaml.'.yml',
			self::$FPATH.'/config/'.$yaml.'.yml',
			);
		return self::resolve_file($yaml, 'yaml', $search);

	}

	private static function resolve_file($file_part, $type, $search_paths)
	{
		if(isset(self::$cached_files[$type][$file_part]))
			return self::$cached_files[$type][$file_part];

		foreach($search_paths as $file)
			if(self::exists($file))
			{
				self::$cached_files[$type][$file_part] = $file;			
				return $file;
			}
	}

	// Return the directory of a class
	public static function resolve_dir($class)
	{
		return dirname(self::resolve_class($class));
	}

	public static function load($class)
	{
		$file = self::resolve_class($class);
		if($file)
			require_once $file;
	}
}

// Startup the autoloader
Auto::startup();
