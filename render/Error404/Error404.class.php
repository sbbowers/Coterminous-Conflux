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
		$search = array();
		$search[] = Auto::$APATH.'/render/';
		$search[] = Auto::$FPATH.'/render/';
		foreach($search as $path)
		{
			$found = $this->isearch_dir($path, $controller);
			if($found)
			{
				$file_found = $this->isearch_dir($path.$found, $ret[1].'.php');
				if($file_found)
				{
					$file_found = substr($file_found, 0, -4);
					$new_uri = $_SERVER['REDIRECT_URI_PREFIX'].'/'.$found.'/'.$file_found;
					header( "HTTP/1.1 301 Moved Permanently" );
					header( "Location: /$new_uri" ); 
					break;
				}
			}
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
