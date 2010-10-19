<?php

class Input extends Element
{
	protected static $form = '';
	protected 
	  $model,
	  $field;
	
	public static function set_form($name)
	{
		self::$form = $name;
	}

	public function __construct($model, $field, $default_value = null)
	{
	  $this->model = $model;
	  $this->field = $field
		$post_val = Request::post(self::$form, $name);
		parent::__construct();
		$this['name'] = Request::get_name(self::$form, $this->table_name, implode(',', $this->primary_key), $field);
    // id has slightly different format because you'd want to use jquery to select on it
		$this['id']   = self::$form.'_'.$this->table_name.'_'.$field.'_'.implode(',', $this->primary_key);
		$this['type'] = 'text';
		$this['value']= $post_val ? $post_val : $default_value;
	}
	
}
