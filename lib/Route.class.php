<?php

class Route
{
	protected static
		$default = null,
		$error = null;
	
	public static function set_default($route)
	{
		self::$default = $route;
	}

	public static function set_error($route)
	{
		self::$error = $route;
	}

	public static function get()
	{
		if(!$_REQUEST['__route'])
			return array(self::$default, null, null);
			
		$ret = array_pad(explode('/', $_GET['__route'], 3),3, null);

		if(class_exists($ret[0]) && in_array('Controller', class_parents($ret[0])))
			return $ret;

		return array(self::$error, null, null);	
	}

}

