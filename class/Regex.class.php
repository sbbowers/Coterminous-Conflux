<?php

class Regex
{
	public static function match($regex, $subject = null)
	{
		preg_match($regex, $subject, $matches);
		return $matches;
	}
}