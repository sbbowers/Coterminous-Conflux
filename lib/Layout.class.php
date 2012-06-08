<?php

class Layout extends Render
{
	public function import($data)
	{
		foreach($data as $key => $value)
			$this[$key] = $value;
	}
	
	public function render()
	{
		$this->pre_render($this, Config::find('max_pre_render_depth'));

		if(!$this->template)
			$this->set_template('default');

		include $this->template;
	}
	
}

