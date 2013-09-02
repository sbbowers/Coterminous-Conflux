<?php
namespace C;

class ValidateBase extends FilterBase
{
	protected function set_error($message)
	{
		$this->model->set_error($this->field, sprintf('The %s field %s.', $this->field, $message));
	}

	public function required()
	{
		if(!$this->value)
			$this->set_error('is a required field');
	}

	public function number($decimal = null)
	{
		if(!preg_match('/\d{1,3}(,?\d{1,3})*(\.\d+)/', $this->value))
			$this->set_error('should be a numeric');
	}

	public function preg($regex)
	{
		if(!preg_match($regex, $this->value))
			$this->set_error('has unexpected input');
	}

}

