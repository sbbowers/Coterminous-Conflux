<?php
class Application
{
	private static $stats = array();

	public function __construct()
	{
		self::$stats['start'] = microtime(true);
		// Lookup the controller / action
		Route::set_default(Config::get('route', 'default'));
		Route::set_error(Config::get('route', 'error'));
		
		list($controller, $action, $extra) = Route::get();
		
		$controller = new $controller();
		
		$controller->exec($action);
		
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
		if(Auto::resolve($class))
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

