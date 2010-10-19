<?php

class Request
{
	public static function get( /* Args */ )
	{
		$args = func_get_args();
		return self::resolve_class($_GET, $args);
	}
	
	public static function post( /* Args */ )
	{
		$args = func_get_args();
		return self::resolve_class($_POST, $args);
	}

	public static function cookie( /* Args */ )
	{
		$args = func_get_args();
		return self::resolve_class($_COOKIE, $args);
	}

  public static function get_name( /* Args */ )
  {
		$args = func_get_args();
    $ret = array_shift($args);
    if($args)
      $ret.= '['.implode('][', $args).']';
    return $ret;
  }
	
	// Helper function to get request parameters
	protected static function resolve_class($request_var, $params)
	{
		foreach($params as $param)
		{
			if(!isset($request_var[$param]))
				return null;
			$request_var = $request_var[$param];
		}

		return $request_var;
	}
	
}
