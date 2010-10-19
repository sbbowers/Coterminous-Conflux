<?php

class LayoutDefault extends Layout
{
	public function logic()
	{
		//Create All Renerable objects
		$this['navigation'] = new Navigation();
		$this['DefaultFooter'] = new DefaultFooter();
		$this['clouds'] = new Cloud();

		// This is important that it goes last so that it can process all css and javascript that was requested by other objects
		$this['includes'] = new WebIncludes();
	}
}
