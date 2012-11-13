<?php
class Error404 extends Controller
{
	public function exec_default()
	{
		//Search For Case Insentive Match of URL.
		if($this->find_controller())
			return 'default';
		return 'default';
	}

	private function find_controller()
	{
		$ret = array_pad(explode('/', $_GET['__route'], 3),3, null);
		$controller = $ret[0];
		$new_controller = Auto::search_class_name($controller);
		if($new_controller)
		{
			$new_uri = $_SERVER['REDIRECT_URI_PREFIX'].'/'.$new_controller.'/'.$ret[1];
			header( "HTTP/1.1 302 Moved Permanently" );
      header( "Location: /$new_uri" );
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
