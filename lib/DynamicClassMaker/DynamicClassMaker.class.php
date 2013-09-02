<?php
namespace C;

class DynamicClassMaker
{
	private
		$base_name = null,
		$template = null,
		$map = array('CLASS_KEY_WORD' => 'class');

	public function __construct($base_name)
	{
		$this->base_name = $base_name;
		$this->template = file_get_contents(Auto::$FPATH."/lib/DynamicClassMaker/$base_name.skel");
	}

	public function add_values($values)
	{
		$this->map = array_merge($this->map, $values);
	}

	public function make_class()
	{
		$keys = array();
		$values = array();
		foreach($this->map as $key => $value)
		{
			$keys[] = "%%$key%%";
			$values[] = $value;
		}
		echo "Making Class: {$this->base_name}";
		$class_definition = str_replace($keys, $values, $this->template);
		eval($class_definition);
	}
}
