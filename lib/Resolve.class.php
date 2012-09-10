<?php
// Class Resolve - Resolve common files types
// Searches Application and Framework directories and returns the appropriate file

class Resolve
{
	private static
		$cached_files = array();


	// Reserved for implementing cached file existance to avoid hitting the filesystem
	public static function exists($file)
	{
		return file_exists($file);
	}

	public static function first_cglob($pattern, $base_dir = '.', $flags = null)
	{
		$files = self::cglob($pattern, $base_dir = '.', $flags = null);
		return array_shift($files);
	}

	public static function cglob($pattern, $base_dir = '.', $flags = null)
	{
		$dirs = Array(Auto::$APATH, Auto::$FPATH);
		$files = Array();
		foreach($dirs as $dir)
		{
			$files = array_merge($files, self::glob($pattern, "$dir/$base_dir", $flags));
		}
		return $files;
	}

	// Search for files that match pattern and return all files recursively
	public static function files($dir = '.', $pattern = '.* *', $recursive = true)
	{
		$recursive = $recursive ? '' : '-maxdepth 1';
		$orig_dir = getcwd();
		chdir($dir);
		$files = explode("\n", trim(`find $pattern $recursive -not -path '..*' -not -path . -not -path './*'`));
		chdir($orig_dir);
		return $files;
	}

	public static function config($yaml)
	{
		$search = array(
			Auto::$APATH.'/config/'.$yaml.'.yml',
			Auto::$FPATH.'/config/'.$yaml.'.yml',
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

	// Return a class file
	public static function class_file($class)
	{
		return Auto::resolve_class($class);
	}

	// Return the directory of a class
	public static function class_dir($class)
	{
		return dirname(Auto::resolve_class($class));
	}
}

