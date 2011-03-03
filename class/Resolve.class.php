<?php
// Class Resolve - Resolve files and automatically load classes
//	Supports framework directory specified by FPATH environment variable
//	Supports application directory specified by APATH environment variable
//  Supports data directory specified by DPATH environment variable

class Resolve 
{	
	public static
		$APATH = null,
		$FPATH = null;
	private static
		$disable_cache_processing = false,
		$class_files = array(),
		$cached_files = array();

		
	// Reserved for implementing cached file existance to avoid hitting the filesystem
	public static function exists($file)
	{
		return file_exists($file);
	}

	// Search for files that match pattern and return all files recursively
	public static function glob($pattern, $dir = '.', $flags = null)
	{
		$dir = escapeshellcmd($dir);
		$files = glob("$dir/$pattern", $flags);

		foreach (glob("$dir/*", GLOB_ONLYDIR) as $subdir)
		{
			//$subfiles = ;
			$files = array_merge($files, Resolve::glob($pattern, $subdir, $flags));
		}

		return $files;
	}  

	// Set up initial framework search paths
	public static function startup()
	{
		self::$APATH = getenv('APATH');
		self::$FPATH = getenv('FPATH');

		// For CLI support
		if(!self::$FPATH)
			self::$FPATH = dirname(__DIR__);
	
		spl_autoload_register(array('Resolve', 'load'));

		if(!self::$disable_cache_processing)
			self::cache_files();
	}

	public static function class_name($class)
	{
		if(in_array($class, self::$class_files))
			return self::$class_files[$class];
			
		$class = str_replace('\\', '/', $class);
		$search = array(
			self::$APATH.'/class/'.$class.'.class.php',
			self::$FPATH.'/class/'.$class.'.class.php',
			self::$APATH.'/render/'.$class.'/'.$class.'.class.php',
			self::$FPATH.'/render/'.$class.'/'.$class.'.class.php',
			);
		return self::resolve_file($class, 'class', $search);
	}

	public static function config($yaml)
	{
		$search = array(
			self::$APATH.'/config/'.$yaml.'.yml',
			self::$FPATH.'/config/'.$yaml.'.yml',
			);
		return self::resolve_file($yaml, 'yaml', $search);
	}

	public static function scaffold($yaml)
	{
		$search = array(
			self::$FPATH.'/scaffold/'.$yaml.'.yml',
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
	private static function cache_files()
	{
		// Simple mutex to prevent recursion
		self::$disable_cache_processing = true;

		$search = array(
			self::$APATH.'/class',
			self::$FPATH.'/class',
			self::$APATH.'/render',
			self::$FPATH.'/render',
			);
		
		$files = array();
		foreach($search as $dir)
			$files = array_merge($files, self::glob('*.class.php', $dir));

		foreach($files as $file)
		{
			preg_match('/(\w+)\.class\.php$/', $file, $matches);
			if(isset($matches[1]))
				self::$cached_files['class'][$matches[1]] = $file;
		}
		self::$disable_cache_processing = false;
	}

	// Return the directory of a class
	public static function resolve_dir($class)
	{
		return dirname(self::class_name($class));
	}

	public static function load($class)
	{
		$file = self::class_name($class);
		if($file)
			require_once $file;
		
		// Call static autoload function if defined
		if(is_callable($class.'::__autoload'))
		  $class::__autoload();
	}
}

// Startup the autoloader
Resolve::startup();
