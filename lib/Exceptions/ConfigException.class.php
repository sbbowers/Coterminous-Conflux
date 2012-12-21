<?php
class ConfigException extends BaseException
{
	public function __construct($message, $code = null, Exception $previous = null)
	{
		parent::__construct($message, $code, $previous);
	}
}
