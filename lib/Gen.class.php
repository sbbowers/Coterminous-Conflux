<?php
/* Class Gen
	Generate an instance of a class
	Alternate way to (new ClassName), but supports chaining
	Solves call_user_func_array('someclass::__construct', $parameters)
	$m = new Model('...')->save(); // invalid
	$m = Gen::Model('...')->save(); // valid

	Can't support classes named 'instance', so don't do that
*/

class Gen
{
	// $t = new Hash($initial_array) // now becomes:
	// $t = Gen:Hash($init_array)
	public static function __callStatic($method, $args)
	{
		return self::instance($method, $args);
	}

	// Generate an instance using array parameters rather than listing them outright
	// $t = new Hash($initial_array) // now becomes:
	// $t = Gen:instance('Hash', array($init_array));
	public static function instance($method, $args)
	{
		if($method == '__autoload')
			return;

		print "instancing $method: \n";

		switch(count($args))
		{
			case 0: return new $method();
			case 1: return new $method($args[0]);
			case 2: return new $method($args[0], $args[1]);
			case 3: return new $method($args[0], $args[1], $args[2]);
			case 4: return new $method($args[0], $args[1], $args[2], $args[3]);
			case 5: return new $method($args[0], $args[1], $args[2], $args[3], $args[4]);
			case 6: return new $method($args[0], $args[1], $args[2], $args[3], $args[4], $args[5]);
			case 7: return new $method($args[0], $args[1], $args[2], $args[3], $args[4], $args[5], $args[6]);
			case 8: return new $method($args[0], $args[1], $args[2], $args[3], $args[4], $args[5], $args[6], $args[7]);
			case 9: return new $method($args[0], $args[1], $args[2], $args[3], $args[4], $args[5], $args[6], $args[7], $args[8]);
			case 10:return new $method($args[0], $args[1], $args[2], $args[3], $args[4], $args[5], $args[6], $args[7], $args[8], $args[9]);
			default:
				$arglist = array();
				foreach($args as $key -> $value)
					$arglist[] = '$args['.$key.']';
				return eval('return new '.$method.'('.implode(',',$arglist).');');
		}
	}

}