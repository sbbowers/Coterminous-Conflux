<?php
class CMinify
{
	private static $original_base = null;

	public static function set_base_dir()
	{
		self::$original_base = getcwd();
		chdir(getenv('FPATH').'/extension/Minify/min/lib');
	}

	public static function serve($controller, $options)
	{
		self::set_base_dir();
		$tmp = Minify::serve($controller, $options);
		self::unset_base_dir();
		return $tmp;
	}

	public static function unset_base_dir()
	{
		chdir(self::$original_base);
	}
}

CMinify::set_base_dir();
require_once 'Minify.php';
CMinify::unset_base_dir();
