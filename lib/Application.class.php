<?php
namespace C;

class Application
{
	private static $stats = array();

	public function __construct()
	{
		self::$stats['start'] = microtime(true);
		// Lookup the controller / action
		Route::set_default(Config::get('route', 'default'));
		Route::set_error(Config::get('route', 'error'));
		
		try
		{
			list($controller, $action, $extra) = Route::get();
			if(extension_loaded('newrelic'))
				newrelic_name_transaction("$controller/$action");
			
			$controller = new $controller();
			
			$controller->exec($action, $extra);
		}
		catch(RouteException $e)
		{
			$controller = $e->get_controller();
		}
		
		try
		{
			$handler = Config::get('auth', 'callback');
			$user_permissions = array_unique(call_user_func($handler));
			$required_permissions = array_unique($controller->get_permissions($action));
			if(count(array_intersect($user_permissions, $required_permissions)) != count($required_permissions))
				die('Permission Deneied'); // Replace this with some nice page!
		}
		catch(ConfigException $e)
		{

		}
		$layout = $controller->get_layout();
		$layout = new $layout();
		
		$layout->import($controller->export());
		$layout->render();
		echo "Render Time: ".(microtime(true) - self::$stats['start'])."<br/>";
	}

	//Deprecated Use Constructor
	public static function start()
	{
		$route = $_GET['__route'];
		list($class, $action, $extra) = explode('/', $route, 3);
		if(Resolve::resolve($class))
		{
			$route = new $class();
			if(is_subclass_of($route, 'Controller'))
			{
				$route->exec($action);
				$layout_name = $route->get_layout();
				
				$layout = new $layout_name();
				if(is_subclass_of($layout, 'Layout'))
				{
					$layout->import($route);
					$layout->render();
				}
			}
		}
		else
			echo "Not Valid Path\n";
	}

// pseudocode
/*

// process module/action

list($controller, $action) = new Routing::route();

// Tell it this is the parent controller

$controller = new $controller()
$controller->exec($action);
// This line should be factored into the exec()
// $layout['content'] = $controller;



$layout = $controller->get_layout();
$layout = new $layout();

foreach($controller->get_slots as $key => $obj)
	$layout[$key] = $obj;


$layout->render();
*/

}

