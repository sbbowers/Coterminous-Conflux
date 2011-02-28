<?php

class Render implements ArrayAccess, Iterator
{
	private
		$iterator_position = 0,
		$params = array();
	protected
		$template = null;	

	// Override this in child classes
	protected function logic() {}

	public function __construct()	{}

	public function set_template($template)
	{
	  $class = get_class($this);
	  do 
	  {
		  if(!is_file($template))
			  $template = Resolve::resolve_dir(get_class($this)).'/'.$template.'.php';
      $class = get_parent_class($class);
    } 
    while($class && !is_file($template));
    
		if(!is_file($template))
			throw new Exception(get_class($this).' set template to non-existant	'.$template);
			
		$this->template = $template;
	}
	
	public function add_css($file, $media = 'all')
	{
		$class_name = get_class($this);
		if(!is_file(Resolve::resolve_dir($class_name).'/'.$file))
			throw new Exception($class_name.' does not have a '.$file);
			
		$file = $class_name.'/'.$file;

		Config::register('css', $media, $file, $file);
	}

	public function add_js($file)
	{
		$class_name = get_class($this);
		if(!is_file(Resolve::resolve_dir($class_name).'/'.$file))
			throw new Exception($class_name. ' does not have a '.$file);

		$file = $class_name.'/'.$file;

		Config::register('js', $file, $file);
	}

	protected function pre_render($collection, $depth = null)
	{
		// You can pass depth to limit the levels of recursion; null will always fail here
		if($depth === 0)
			return;			
			
		if(is_object($collection) && is_subclass_of($collection, 'Render'))
			$collection->logic();
		else if(!is_array($collection))
			return;
			
		// Once here you're either a Render class or an array - no others allowed.
		foreach($collection as $index => $renderable)
			$this->pre_render($renderable, $depth - 1);
	}

	public function render()
	{

		if(!$this->template)
			$this->set_template('default');

		include $this->template;
	}

  // Implements ArrayAccess
  public function offsetExists($offset)
  {
    return isset($this->params[$offset]);
  }

  public function offsetGet($offset)
  {
    return $this->params[$offset];
  }

  public function offsetSet($offset, $value)
  {
    $this->params[$offset] = $value;
  }

  public function offsetUnset($offset)
  {
    unset($this->params[$offset]);
  }
	
	// Implements Iterator
	function rewind()
	{
		reset($this->params);
	}

	function current()
	{
		return current($this->params);
	}

	function key()
	{
		return key($this->params);
	}

	function next()
	{
		return next($this->params);
	}

	function valid() 
	{
		return $this->current() !== false;
	}
}

