<?php

class WebIncludes extends Render
{
	public function logic()
	{
		//Create All Renerable objects
		$this['css'] = (array) Config::find('registry', 'css');
		$this['js'] = (array) Config::find('registry', 'js');
		$this['base_path'] = 'http://'.$_SERVER['HTTP_HOST'].'/'.$_SERVER['URI_PREFIX'].'/';
	}
}
