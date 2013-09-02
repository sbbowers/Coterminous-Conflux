<?php
namespace C;

// Class Environemnt - Setup application contexts

class Environment
{

	public static function start() 
	{
		error_reporting(E_ALL);
		ini_set('display_errors', 1);

		set_exception_handler(array('Environment','exception_handler'));
		set_error_handler(array('Environment','error_handler'));
	}

	public static function exception_handler($e)
	{
		$info = array("Uncaught Exception: ".$e->getMessage(),
			"Stack: \n       ".str_replace("\n", "\n       ", $e->getTraceAsString()),
			"Request: ".var_export($_REQUEST, true),
			"Server: ".var_export($_SERVER, true),
			"Session: ".session_id(),
			"Session Data: ".var_export(isset($_SESSION) ? $_SESSION : '', true));
		
		foreach($info as $item)
		{
			if(php_sapi_name() == 'cli')
				echo $item."\n";
			else
			{
				list($title, $desc) = explode(':', $item, 2);
				echo "<div style=\"white-space:pre\"><strong>$title</strong>: $desc</div>\n";
			}
		}
	}

	public static function error_handler($error, $message, $file, $line, $context)
	{
		$end = php_sapi_name() == 'cli' ? "\n" : "<br />\n";
		switch($error)
		{
			case E_STRICT:
			case E_NOTICE:
			case E_USER_NOTICE:
				echo "NOTICE: $error $message (LN $line: $file)\n".print_r($context,true).$end;
				//throw new Exception("NOTICE: $error $message (LN $line: $file)");
				break;
			case E_WARNING:
			case E_USER_WARNING:
				echo "WARNING: $error $message (LN $line: $file)\n".print_r($context,true).$end;
		    //throw new Exception("WARNING: $error $message (LN $line: $file)");
				break;

			default: 
				//echo "ERROR: $error $message (LN $line: $file)\n".print_r($context,true).$end;
		    throw new Exception("ERROR $error $message (LN $line: $file)");
				break;
		}			
	}
}

