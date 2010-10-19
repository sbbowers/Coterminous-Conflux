<?php
class DefaultFooter extends Render
{
	public function logic()
	{
		$this['GoogleAnalytics'] = new GoogleAnalytics();
	}
}

