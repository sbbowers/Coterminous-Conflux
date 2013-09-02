<?php
namespace C;

class BaseException extends \Exception
{
	public function get_controller()
	{
		$controller_name = Route::get_error();
		$controller = new $controller_name();
		$controller['Exception'] = $this;
		$controller->exec(null, null);
		return $controller;
	}

}
