<?php

class Regex
{
	public static function match($regex, $subject = null)
	{
		preg_match($regex, $subject, $matches);
		return $matches;
	}

	public static function select($regex, $subject = null)
	{
		return current(self::match($regex, $subject));
	}

	public static function match_all($regex, $subject = null)
	{
		preg_match_all($regex, $subject, $matches);
		return $matches;
	}
		
	public static function select_all($regex, $subject = null)
	{
		return current(self::match_all($regex, $subject));
	}
}