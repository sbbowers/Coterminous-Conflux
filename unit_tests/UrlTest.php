<?php
namespace Test\C;

include_once getenv('FPATH').'/lib/Auto.class.php';

class UrlTest extends \PHPUnit_Framework_TestCase
{

	public function test_get_0()
	{
		$url = \C\Url::get('favicon.ico');
		$this->assertContains('favicon.ico', $url);
	}

	public function test_http_build_url_0()
	{
		$to_build = [];
		$to_build['scheme'] = 'https';
		$to_build['host'] = 'mytest.com';
		$url = \C\Url::http_build_url($to_build);
		$this->assertEquals('https://mytest.com/', $url);
	}

	public function test_http_build_url_2()
	{
		$to_build = [];
		$to_build['scheme'] = 'http';
		$to_build['host'] = 'mytest.com';
		$url = \C\Url::http_build_url($to_build);
		$this->assertEquals('http://mytest.com/', $url);
	}

	public function test_http_build_url_3()
	{
		$to_build = [];
		$to_build['scheme'] = 'http';
		$to_build['host'] = 'mytest.com';
		$to_build['fragment'] = 'IDPATH';
		$url = \C\Url::http_build_url($to_build);
		$this->assertEquals('http://mytest.com/#IDPATH', $url);
	}

	public function test_http_build_url_4()
	{
		$to_build = [];
		$to_build['scheme'] = 'http';
		$to_build['host'] = 'mytest.com';
		$to_build['path'] = 'dig/my/path';
		$url = \C\Url::http_build_url($to_build);
		$this->assertEquals('http://mytest.com/dig/my/path', $url);
	}

	public function test_http_build_url_5()
	{
		$to_build = [];
		$to_build['scheme'] = 'http';
		$to_build['host'] = 'mytest.com';
		$to_build['path'] = 'dig/my/path';
		$to_build['fragment'] = 'IDPATH';
		$url = \C\Url::http_build_url($to_build);
		$this->assertEquals('http://mytest.com/dig/my/path#IDPATH', $url);
	}

	public function test_http_build_url_6()
	{
		$to_build = [];
		$to_build['scheme'] = 'https';
		$to_build['host'] = 'mytest.com';
		$to_build['path'] = 'myname/is/testerton';
		$to_build['query'] = 'url=true';
		$url = \C\Url::http_build_url($to_build);
		$this->assertEquals('https://mytest.com/myname/is/testerton?url=true', $url);
	}

	public function test_add_url_parameter_0()
	{
		$to_build = [];
		$to_build['scheme'] = 'https';
		$to_build['host'] = 'mytest.com';
		$to_build['path'] = 'myname/is/testerton';
		$to_build['query'] = 'url=true';
		$to_build = \C\Url::add_url_parameter('more', 'things', $to_build);
		$this->assertEquals('url=true&more=things', $to_build['query']);
	}

	public function test_add_url_parameter_1()
	{
		$to_build = [];
		$to_build['scheme'] = 'https';
		$to_build['host'] = 'mytest.com';
		$to_build['path'] = 'myname/is/testerton';
		$to_build['query'] = 'url=true';
		$to_build = \C\Url::add_url_parameter('more', 'thingsi&#$<', $to_build);
		$this->assertEquals('url=true&more=thingsi%26%23%24%3C', $to_build['query']);
	}


}
