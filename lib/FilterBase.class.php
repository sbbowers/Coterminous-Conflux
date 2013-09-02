<?php
namespace C;

class Filter 
{
	protected
		$model, 
		$field,
		$value;

	public function __construct($model, $field)
	{
		$this->model = $model;
		$this->field = $field;
		$this->value = $this->model[$this->field];
	}

	public function trim($characters = " \t\n\r\0\x0B")
	{
		return trim($this->value, $characters);
	}

	public function number($decimal = null)
	{
		return number_format($this->value, $decimal);
	}

}

