<?php

// Utility namespace
class Util
{

	public static function is_dev_env()
	{
		return getenv('DEV_ENV') == true;
	}

	public static function get_version()
	{
		if(is_file(Resolve::$FPATH.'/.version'))
			$f_ver = file_get_contents(Resolve::$FPATH.'/.version');
		else
			$f_ver = self::get_svn_version(Resolve::$FPATH);
		if(is_file(Resolve::$APATH.'/.version'))
			$a_ver = file_get_contents(Resolve::$APATH.'/.version');
		else
			$a_ver = self::get_svn_version(Resolve::$APATH);

		$ver = $f_ver.'-'.$a_ver;
		if(!$f_ver && !$a_ver)
			$ver = null;
		return $ver;
	}

	public static function get_svn_version($path)
	{
		$revision = null;
		if(is_file($path.'/.svn/entries'))
		{
			$svn_info = file($path.'/.svn/entries');
			$revision = trim($svn_info[3]);
		}
		return $revision;
	}
}

