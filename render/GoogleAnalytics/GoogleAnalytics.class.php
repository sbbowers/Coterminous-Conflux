<?php
class GoogleAnalytics extends Render
{
	public function logic()
	{
		$this['GoogleAnalyticsCode'] = Config::find('GoogleAnalyticsCode');
	}
}

