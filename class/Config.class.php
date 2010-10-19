<?php

class Config
{
	public static $config = array();

	// search the configuration to find a value
	// No Parameters returns the entire config
	public static function get( /* $parm1, $param2, ... */)
	{
		$args = func_get_args();
		$ret = self::$config;

		foreach($args as $lookup)
		{
			if(!is_array($ret) || !array_key_exists($lookup, $ret))
				throw new Exception("Searching for bad configuration value [".implode('][',$args)."]");
			$ret = $ret[$lookup];
		}
		return $ret;
	}

	public static function find(/* $parm1, $param2, ... */)
	{
		try
		{
			$args = func_get_args();
			return call_user_func_array(array('Config', 'get'), $args);
		}
		catch(Exception $e)
		{
			//Debug:: Config Key Not Found
			return null;
		}
	}

  // Sets a value into the config['registry']; uses set_helper with pass-by-reference
  // the registry branch is the only place to programatically include values
  public function register(/* $index1 [, $index2 ...], $value */)
  {
    $args = func_get_args();
    $value = array_pop($args);

    if(!isset(self::$config['registry']))
    	self::$config['registry'] = array();
    	
    self::set_helper(self::$config['registry'], $args, $value);   
  }

	// Load a YAML file and return the result
	public static function load($config_file)
	{
		if(is_file($config_file))
			return Symfony\YAML::load(file_get_contents($config_file));
		return array();
	}

	// Import a config file into the static Config class
	public static function import($config_file)
	{
		$new_conf = self::load($config_file);
		$imports = array();
		if(array_key_exists('_imports', $new_conf))
			$imports = (array)$new_conf['_imports'];
		unset($new_conf['_imports']);
		self::$config = (array)self::load($config_file) + (array)self::$config;
		foreach($imports as $import)
		{
			self::import(Auto::resolve_yaml($import));
		}
	}

	// Loads the default yaml file
	public static function autoload()
	{
		$framework = Auto::resolve_yaml('framework');
		self::import($framework);
	}

  // Private helper function used to set a value in an arbitrary location in an array.
  protected static function set_helper(&$data, $indexes, $value)
  {
    $index = array_shift($indexes);
    
    if($index === null)
      $data = $value;
    else
    {   
      if(!is_array($data))
        $data = array();
      self::set_helper($data[$index], $indexes, $value);
    }   
  }

	
}

Config::autoload();

