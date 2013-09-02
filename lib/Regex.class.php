<?php
namespace C;

class Regex
{
	// Wrapper for preg_match
	public static function match($regex, $subject = null, $pad = null)
	{
		preg_match($regex, $subject, $matches);

		if($pad !== null)
			return array_pad($matches, $pad, null);

		return $matches;
	}

	// Returns the first match from a preg_match
	public static function select($regex, $subject = null)
	{
		return current(self::match($regex, $subject));
	}

	// Returns everything *but* the first match from a preg_match
	// Basically returns what you capture using ()
	public static function match_only($regex, $subject = null)
	{
		return array_slice(self::match($regex, $subject), 1);
	}

	// Wrapper for preg_match_all
	public static function match_all($regex, $subject = null)
	{
		preg_match_all($regex, $subject, $matches);
		return $matches;
	}
		
	// Returns the first match from a preg_match_all
	public static function select_all($regex, $subject = null)
	{
		return current(self::match_all($regex, $subject));
	}
}