<?php

/**
 * @package Travian
 * @author Jérémy "Korko" Lemesle <jeremy.lemesle@korko.fr>
 */
if( !defined('IN_PACKAGE') )
{
	die('Access Denied');
}

/**
 * Class cURL
 * Permits to communicate with websites like a browser
 */
class cURL
{
	const TEMP_DIR = './tmp';
	
	private $request=0; // How many request have been done ?
	private $handle=NULL; // Handle of cURL
	private $callback=NULL;
	private $last_url=NULL;
	private $cookie;
	
	public function __construct()
	{
		if( !function_exists('curl_init') )
		{
			throw new Exception('Please install cURL !');
		}
		
		$this->requests = 0;
		if( ($this->handle = curl_init()) === FALSE )
		{
			throw new Exception('Unable to initialize curl');
		}

		if( !is_dir(self::TEMP_DIR) )
		{
			mkdir(self::TEMP_DIR);
		}
		
		$this->setCookie(self::TEMP_DIR.'/'.md5(time())); // Create a temp cookie
		
		$this->setOpt(CURLOPT_HEADER, TRUE);
		$this->setOpt(CURLOPT_NOBODY, FALSE); // Return Body
		$this->setOpt(CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
		$this->setOpt(CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
		$this->setOpt(CURLOPT_CONNECTTIMEOUT, 60);
		$this->setOpt(CURLOPT_FOLLOWLOCATION, 1);
		$this->setOpt(CURLOPT_RETURNTRANSFER, 1);
	}
	
	public function __destruct()
	{
		curl_close($this->handle);
		unlink($this->cookie);
	}
	
	/**
	 * Change Cookie's name
	 * @param String cookie_name Cookie's name
	 */

	private function setCookie($cookie_name)
	{
		$this->cookie = $cookie_name;
		$this->setOpt(CURLOPT_COOKIEJAR, $cookie_name);
		$this->setOpt(CURLOPT_COOKIEFILE, $cookie_name);
	}
	
	private function returnHeaders($boolean)
	{
		$this->setOpt(CURLOPT_HEADER, $boolean);
	}
	
	private function setOpt($const, $value)
	{
		curl_setopt($this->handle, $const, $value);
	}
	
	public function setCallback($callback)
	{
		$this->callback = $callback;
	}
	
	public function get_url_content($url, $method='GET', $vars='')
	{	
		if (strtoupper($method) == 'POST') 
		{
			$this->setOpt(CURLOPT_POST, 1);
			$this->setOpt(CURLOPT_POSTFIELDS, $vars);
		}
		else // GET
		{
			$this->setOpt(CURLOPT_HTTPGET, 1);
			$url .= (!empty($vars)) ? '?'.$vars : '';
		}
		
		$this->setOpt(CURLOPT_REFERER, (!is_null($this->last_url)) ? $this->last_url : $url);
		$this->setOpt(CURLOPT_URL, $url);
		
		$data = curl_exec($this->handle);
		
		$this->request++;
		$this->last_url = $url; // Just fun for referer
		
		if( $data !== FALSE )
		{
			if( $this->callback !== NULL )
			{
				$data = call_user_func($this->callback, $data);
				$this->callback = NULL;
			}
		}
		else
		{
			throw new Exception('cURL : '.curl_errno($this->handle).' # '.curl_error($this->handle));
		}
		
		return $data;
	}
}

/* EOF */