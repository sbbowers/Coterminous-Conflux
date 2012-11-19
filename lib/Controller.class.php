<?php

class Controller extends Render
{
	protected
		$default_action = 'default', // override in child class
		$layout = null,
		$expose_data = array();

	public function __construct()
	{
		$this->layout = Config::get('default_layout');
	}

	// Lookup the action function and execute it; The action is responsible 
  // for setting up 
	public function exec($action, $extra)
	{
		if(!$action)
			$action = $this->default_action;
		$base_action = $action;

		$this->expose('content', $this);

		//Checking for request method specific methods
		$type_specific_action = strtolower($_SERVER['REQUEST_METHOD']).'_'.$action;
		$action = 'exec_'.$action;
		if(method_exists($this, $type_specific_action))
			$action = $type_specific_action;

		//Processing additional parameters
		if($extra == '')
			$extra_parameters = Array();
		else
			$extra_parameters = explode('/', $extra);
		$extra_parameters_count = count($extra_parameters);

		//Checking for existing action
		$r_class = new ReflectionClass($this);
		try
		{
			$r_method = $r_class->getMethod($action);
		}
		catch (ReflectionException  $e)
		{
			throw new RouteException("$base_action Does Not Exist", 404);
		}

		//Checking if action is public
		if(!$r_method->isPublic())
			throw new RouteException('Function is Not Public', 404);

		//Checking if too many parameters were passed by the client
		if($r_method->getNumberOfParameters() < $extra_parameters_count)
			throw new RouteException("Too Many Parameters", 404);

		//Checking if enough parameters were passed by the client
		$r_parameters = array_slice($r_method->getParameters(), $extra_parameters_count);
		foreach($r_parameters as $r_parameter)
		{
			if(!$r_parameter->isOptional())
				throw new RouteException("Parameter {$r_parameter->getName()} is required", 404);
		}

		//Calling action
		$template = $r_method->invokeArgs($this, $extra_parameters);
		$this->set_template($template);
	}

	// Public accessor method for exposed data
	public function export($id = null)
	{
		if(!isset($id))
			return $this->expose_data;

		if(isset($this->expose_data[$id]))
			return $this->expose_data[$id];
	}

	// Expose information (objects) to the application
	// Mostly used for providing content to a layout
	protected function expose($id, $value)
	{
		$this->expose_data[$id] = $value;
	}

	protected function set_layout($layout)
	{
		$this->layout = $layout;
	}
	
	public function get_layout()
	{
		return $this->layout;	
	}
}

