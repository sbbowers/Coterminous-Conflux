<?php

class Fragment implements ArrayAccess
{
	protected 
		$template = null,
		$parameters = array();

	public function __construct($parameters = array(), $template = null)
	{
		$this->template = $template ? $template : get_class($this);
		$this->parameters = (array) $parameters;
	}

	public function get_state()
	{
		return $this->parameters;
	}

	public function set_template($template)
	{
		$this->template = $template;
	}
	
	public function render()
	{
		extract($this->parameters);
		require Resolve::resolve_class($this->template.'.tmpl.php');
	}

	public function get()
	{
		ob_start();
		$this->render();
		return ob_end_clean();
	}
	
	// Implements ArrayAccess
	public function offsetExists($offset)
	{
		return isset($this->parameters[$offset]);
	}
	
	public function offsetGet($offset)
	{
		return $this->parameters[$offset];
	}
	
	public function offsetSet($offset, $value)
	{
		$this->parameters[$offset] = $value;
	}
	
	public function offsetUnset($offset)
	{
		unset($this->parameters[$offset]);
	}
}
