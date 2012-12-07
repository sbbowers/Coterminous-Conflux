<?php
class Error404 extends Controller
{
	public function exec_default()
	{
		//Search For Case Insentive Match of URL.
		$this->find_controller();

		if($this['Exception'])
			header("HTTP/1.1 ".$this['Exception']->getCode());
		return 'default';
	}

	private function find_controller()
	{
		$ret = array_pad(explode('/', $_GET['__route'], 3),3, null);
		list($controller, $action, $extra) = $ret;
		try
		{
			$new_controller = Auto::search_class_name($controller);
			//Checking if new controller found, and the case is different form what we already have
			if($new_controller && $new_controller != $controller)
			{
				$new_uri = $_SERVER['REDIRECT_URI_PREFIX'].'/'.$new_controller.'/';
				if($action)
					$new_uri.= $action.'/';
				if($extra)
					$new_uri.= $extra.'/';
				$params = $_GET;
				unset($params['__route']);
				$query_string = http_build_query($params);
				if($query_string)
					$new_uri.= "?$query_string";
				header( "HTTP/1.1 302 Found" );
				header( "Location: /$new_uri" );
				exit();
			}
		}
		catch(ReflectionException $e)
		{
		}
	}

	private function isearch_dir($path, $controller)
	{
		$files = scandir($path);
		foreach($files as $file)
		{
			if(strtoupper($file) == strtoupper($controller))
				return $file;
		}
		return null;
	}
}
