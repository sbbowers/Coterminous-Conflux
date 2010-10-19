<?php
class Element extends Render
{
	private 
		$attributes = array(),
		$contents = array();

	public function __construct($type, $attributes=null, $contents=null)
	{
		$this['type'] = strtolower($type);
		if(isset($attributes))
			$this->add_attributes($attributes);
		if(isset($content))
		{
			foreach((array)$contents as $content)
				$this->add_content($content);
		}
	}

	public function logic()
	{
		$this['attributes'] = $this->attributes;
		$this['contents'] = $this->contents;
	}

	public function __set($name, $value)
	{
		$name = strtolower($name);
		$this->attributes[$name] = $value;
	}

	public function attr($attributes)
	{
		$this->attributes = array_merge($this->attributes, (array)$attributes);
		return $this;
	}

	public function add_content($renderable)
	{
		$this->contents[] = $renderable;
		return $this;
	}

	public function render_open()
	{
		$this['render_part'] = 'open';
		$this->render();
		unset($this['render_part']);
	}

	public function render_close()
	{
		$this['render_part'] = 'close';
	}
}
