<?php
class StaticContent
{

	public static function fetch_and_cache($url)
	{
		if(!$url)
			return;
	
		$exclude_ext = Config::get('static_content', 'exclude_ext');
		$path_info = pathinfo($url);

		if(!isset($path_info['extension']))
			return;

		if(array_search($path_info['extension'], $exclude_ext) !== false)
			return;
		$path = self::resolve_relative($path_info['dirname']).'/'.$path_info['basename'];
		$app_path = getenv('APATH').'/render/'.$path;
		$frame_path = getenv('FPATH').'/render/'.$path;
		$static_path = getenv('DPATH').'/'.$path;
		$found_path = false;
		if(is_file($app_path))
			$found_path = $app_path;
		else if(is_file($frame_path))
			$found_path = $frame_path;
		else if(is_file($static_path))
			$found_path = $static_path;
		if($found_path)
		{
			$content = null;
			if(in_array($path_info['extension'], array('js', 'css')))
			{ 
				$minify = CMinify::serve('Files', array('quiet' => true, 'encodeMethod' => '', 'rewriteCssUris' => false, 'files' => array($found_path)));
				$content = $minify['content'];

				$ver = Url::get_renderable_modified($url);
	      if(!$ver)
  	      $ver = Util::get_version();

				$content = preg_replace('/url[( \'"]*([^\'") ]*)[\'") ][) ]*/', 'url(\'$1?ver='.$ver.'\')', $content);
			}
			//Start Cache Method
			$cache_dir = substr($_SERVER['SCRIPT_FILENAME'], 0, (strlen($_SERVER['SCRIPT_FILENAME'])-9));
			$cache_path = $cache_dir.$path;
			if(!getenv('DISABLE_FILE_CACHE') && !file_exists($cache_path))
			{
				umask(0);
				$cache_info = pathinfo($cache_path);
				if(!is_dir($cache_info['dirname']))
				{
					$dir_made = mkdir($cache_info['dirname'], 0777, true);
					if(!$dir_made)
						throw new Exception('Could Not Make Cache Dir: '.$cache_info['dirname']);
				}
				$cache_tmp_path = $cache_path.'.'.getmypid();
				if(is_null($content))
					copy($found_path, $cache_tmp_path);
				else
				{
					$tmp_cache = fopen($cache_tmp_path, 'w');
					fwrite($tmp_cache, $content);
				}
				if(!file_exists($cache_path))
				{
					$move_res = rename($cache_tmp_path, $cache_path);
					if(!$move_res)
						unlink($cache_tmp_path);
					else
						chmod($cache_path, 0666);
				}
				else
					unlink($cache_tmp_path);
			}

			$info = apache_lookup_uri($_SERVER['REQUEST_URI']);
			$content_type = $info->content_type;
			$in = fopen($found_path, 'r');
			$file_size = filesize($found_path);
			header("Content-Length: $file_size");
    	header('Content-Transfer-Encoding: binary');
			header("Content-Type: $content_type");
			header("Cache-Control: public, max-age=3153600");
			header("Expires: ".date('D, d M Y G:i:s e', strtotime('+1 Year')));
			header("Connection: close");
			if(isset($content))
				echo $content;
			else
				fpassthru($in);
			flush();
			die();
		}
	}

	public static function refresh_cache()
	{
		if(!getenv('DEV'))
			return;
		$script_path = $_SERVER['SCRIPT_FILENAME'];
		$root_path = rtrim(substr($script_path, 0, -9), '/');
		$cache = self::timestamp_dir($root_path);
		$a_path = self::timestamp_dir(getenv('APATH').'/render');
		$f_path = self::timestamp_dir(getenv('FPATH').'/render');
		$a_update = self::find_new_files($cache, $a_path);
		$f_update = self::find_new_files($cache, $f_path);
		self::clear_outdated_cache($a_update, $root_path);
		self::clear_outdated_cache($f_update, $root_path);
	}

	private static function clear_outdated_cache($files, $base_path)
	{
		foreach($files as $file => $data)
		{
			if(is_array($data))
				self::clear_outdated_cache($data, $base_path.'/'.$file);
			else
				unlink("$base_path/$file");
		}
	}

	private static function find_new_files($cache, $static)
	{
		$refresh = array();
		foreach($cache as $file => $data)
		{
			if(array_key_exists($file, $static))
			{
				if(is_array($data))
					$refresh[$file] = self::find_new_files($cache[$file], $static[$file]);
				else
				{
					if($static[$file] > $cache[$file])
						$refresh[$file] = $file;
				}
			}
		}
		return $refresh;
	}

	private static function timestamp_dir($path)
	{
		if(!file_exists($path))
		{
			return false;
		}
		if(is_file($path))
		{
			$file_detail = stat($path);
			$modified = $file_detail['mtime'];
			return $modified;
		}
		if(is_dir($path))
		{
			$dir_object = opendir($path);
			$result = array();
			while($dir_file = readdir($dir_object))
			{
				if($dir_file{0} == '.')
					continue;
				$file_path = $path.'/'.$dir_file;
				$result[$dir_file] = self::timestamp_dir($file_path);
			}
			return $result;
		}
	}
	

	public static function resolve_relative($dir_path)
	{
		$dir_path = str_replace('\\', '/', $dir_path);
		$parts = explode('/',$dir_path);
		$new_path = array();
		foreach($parts as $dir)
		{
			if($dir == '..')
			{
				if(is_null(array_pop($new_path)))
					throw new Exception ('Invalid Relative Path');
			}
			else
				array_push($new_path, $dir);
		}
		return implode('/', $new_path);
	}

}
