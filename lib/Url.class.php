<?php
namespace C;

class Url
{
	public static function get($url, $options = array())
	{
		$url_parts = parse_url($url);
		$file_info = pathinfo($url_parts['path']);

		if(isset($file_info['extension']))
		{
			$ver = self::get_renderable_modified($url_parts['path']);
			if(!$ver)
				$ver = Util::get_version();
			$url_parts = self::add_url_parameter('ver', $ver, $url_parts);
		}
		return self::http_build_url($url_parts);
	}

	public static function get_renderable_modified($url)
	{
		$local_path = self::renderable_exists($url);
		$ver = null;
		if($local_path)
		{
			$stats = stat($local_path);
			$ver = $stats['mtime'];
		}
		return $ver;
	}

	public static function renderable_exists($relative_path)
	{
		if(is_file(Auto::$APATH.'/render/'.$relative_path))
			return Auto::$APATH.'/render/'.$relative_path;
		if(is_file(Auto::$FPATH.'/render/'.$relative_path))
			return Auto::$FPATH.'/render/'.$relative_path;
		return null;
	}

	public static function http_build_url($url_parts)
	{
		$url = '';
		if(isset($url_parts['scheme']))
			$url.= $url_parts['scheme'].'://';
		if(isset($url_parts['username']) && isset($url_parts['password']))
			$url.= $url_parts['username'].':'.$url_parts['password'].'@';
		if(isset($url_parts['host']))
			$url.= $url_parts['host'].'/';
		if(isset($url_parts['path']))
			$url.= $url_parts['path'];
		if(isset($url_parts['query']))
			$url.= '?'.$url_parts['query'];
		if(isset($url_parts['fragment']))
			$url.= '#'.$url_parts['fragment'];
		return $url;
	}

	public static function add_url_parameter($name, $value, $url_parts)
	{
		if(isset($url_parts['query']))
			$url_parts['query'].= '&';
		else
			$url_parts['query'] = '';
		$url_parts['query'].= urlencode($name).'='.urlencode($value);
		return $url_parts;
	}
}
