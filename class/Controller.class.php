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
	public function exec($action)
	{
		if(!$action)
			$action = $this->default_action;

		$action = 'exec_'.$action;

		$this->expose('content', $this);
		
		if(method_exists($this, $action))
			$this->set_template($this->$action());
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

