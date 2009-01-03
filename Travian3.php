<?php

define('IN_PACKAGE', TRUE);

/**
 * @package Travian
 * @author Jérémy "Korko" Lemesle <jeremy.lemesle@korko.fr>
 */
require_once('Travian.php');
require_once('cURL.php');

/**
 * Class Travian3
 * Bridge between cURL lib and web applications
 * made for Travian version 3
 */
class Travian3 extends Travian
{
	private $server_host;
	
	/**
	 * Connect to the server
	 *
	 * @param String $server URL of the server
	 * @param String $login Login
	 * @param String $pass Password
	 */
	public function connect($server, $login, $pass)
	{
		if( strrpos($server, '/') !== strlen($server) )
		{
			$server .= '/';
		}
		
		$content = $this->handle->get_url_content($server."login.php", "get");
		
		// 12 oct 2008 : e484950
		// 13 oct 2008 : e05e51e
		// 15 oct 2008 : e775ecc
		preg_match('#<input.* type="text" name="([0-9a-f]{7})#is', $content, $match);
		$login_name = $match[1];
		
		// 12 oct 2008 : e37fae3
		// 13 oct 2008 : e5bdb82
		// 15 oct 2008 : e823d6a
		preg_match('#<input.* type="password" name="([0-9a-f]{7})#is', $content, $match);
		$pass_name = $match[1];
		
		// 12 oct 2008 : e42908a = 81453378a0
		// 13 oct 2008 : ec7dbec = 81453378a0
		// 15 oct 2008 : e0a9bbf = 81453378a0
		preg_match('#<input.* type="hidden" name="([0-9a-f]{7})#is', $content, $match);
		$hidden_name = $match[1];
		
		$vars  = "w=1440%3A900"; // Size of the screen (imagine 1440x900)
		$vars .= "&s1.x=20&s1.y=10"; // Coords
		$vars .= "&login=".(time()-30); // Suppose 30s to submit the form. It's reasonable !
		$vars .= "&".$login_name."=".str_replace(' ', '+', $login);
		$vars .= "&".$pass_name."=".$pass;
		$vars .= "&".$hidden_name."=81453378a0";
		$vars .= "&s1=login"; // Submit button
		
		$content = $this->handle->get_url_content($server."dorf1.php", "post", $vars);
		
		if( preg_match('#<span class="e f7">(.+?)</span>#', $content) ) // Is there an error ?
		{
			throw new Exception('Access denied for user \''.$login.'\'@\''.$server.'\'');
		}
		
		// Returned to the login page ?
		$this->server_host = $server;
		$this->connected = TRUE;
		
		return $content;
	}
	
	public function map()
	{
		if( !$this->connected )
		{
			throw new Exception('Can\'t access this page until you are connected');
		}
		
		return $this->handle->get_url_content($this->server_host.'karte.php');
	}
	
	/**
	 * Return the informations about the square
	 *
	 * @param int $did karte.php?d=
	 * @param int $cid karte.php?c=
	 */
	public function get_square($did, $cid='')
	{
		if( !$this->connected )
		{
			throw new Exception('Can\'t access this page until you are connected');
		}
		
		// If $c is not given, found it !
		if( strlen($cid) == 0 )
		{
			$cid = $this->get_square_key($did);
		}
		
		// Now get the square !
		$content = $this->handle->get_url_content($this->server_host.'karte.php?d='.$did.'&c='.$cid);
		
		return $this->parse_square($did, $content);

		return $return;
	}

	/**
	 * Create a building
	 *
	 * @param int $pos build.php?id=
	 * @param int $bid dorf2.php?a=
	 * @param int $cid dorf2.php?c=
	 */
	public function create_building($pos, $bid, $cid='')
	{
		if( !$this->connected )
		{
			throw new Exception('Can\'t access this page until you are connected');
		}

		// If $c is not given, found it !
		if( strlen($c) == 0 )
		{
			$c = $this->get_building_key($pos);
		}

		// Check if the building can be build and if, http://s5.travian.fr/dorf2.php?a=10&id=29&c=38b
		// get 2 times the same page...
	}














	/******* Parse Functions ************/
	/**
	 * Parse a square and return informations about it
	 *
	 * @param int $d karte.php?id=
	 * @param string $content Content of the page
	 * @return array did => Identifier of the square, 'ressources' => List of the ressources in the square, 'animals' => Only if an oasis with animals, Array of animals, 'owner' => If there is an Owner, his identifier
	 */
	private function parse_square($d, $content)
	{
		$animals = $this->get_animals($content);
		$owner = $this->get_owner($content);

		$return = array(
			'did' => $d,
			'ressources' => $this->get_res($content),
		);

		if( !empty($animals) )
			$return['animals'] = $animals;

		if( !empty($owner) )
			$return['owner'] = $owner;

		return $return;
	}
	
	/******* Get Functions **************/
	/**
	 * Get the key of the square
	 *
	 * @param int $z Identifier of the square
	 */
	private function get_square_key($z)
	{
		$content = $this->handle->get_url_content($this->server_host.'karte.php?z='.$z);
		
		preg_match('#d='.$z.'&c=([a-f0-9]+)#', $content, $match);

		return $match[1];
	}

	/**
	 * Get the key to build a building
	 *
	 * @param int $pos Identifier of the place to build
	 */
	private function get_building_key($pos)
	{
		$content = $this->handle->get_url_content($this->server_host.'build.php?id='.$pos);

		preg_match('#id='.$pos.'&c=([a-f0-9]{3})#', $content, $match);

		return $match[1];
	}

	// By the picture always works. By table don't work when the square is occuped
	private function get_res($square_content)
	{
		$res = array();
		
		// Determine the type of the square
		// Wood, Clay, Iron, Crops
		// <div id="f1"
		$fields_types = array(
			"1" => array(3,3,3,9),
			"2" => array(3,4,5,6),
			"3" => array(4,4,4,6),
			"4" => array(4,5,3,6),
			"5" => array(5,3,4,6),
			"6" => array(1,1,1,15),
			// Deactivated
			//"7" => array(),
			//"8" => array(),
			//"9" => array(),
			//"10" => array(),
		);
		
		// Determine the type of the oasis
		// Wood, Clay, Iron, Crops
		// <img src="img/un/m/w1.jpg
		$oasis_types = array(
			"1" => array(1,0,0,0),
			"2" => array(1,0,0,0),
			"3" => array(1,0,0,1),
			"4" => array(0,1,0,0),
			"5" => array(0,1,0,0),
			"6" => array(0,1,0,1),
			"7" => array(0,0,1,0),
			"8" => array(0,0,1,0),
			"9" => array(0,0,1,1),
			"10" => array(0,0,0,1),
			"11" => array(0,0,0,1),
			"12" => array(0,0,0,2)
		);
		
		if( ! preg_match("#<div id=\"f(\d{1,2})#", $square_content, $match) )
		{
			if( preg_match("#<img src=\"img/un/m/w(\d{1,2}).jpg#", $square_content, $match))
			{
				$res = $oasis_types[$match[1]];
			}
		}
		else
		{
			$res = $fields_types[$match[1]];
		}
		
		return $res;
	}
	
	private function get_animals($square_content)
	{
		// ID 1,2,3,4
		// <tr>
		// <td><img class="res" src="img/un/r/1.gif"></td>
		// <td class="s7 b">3</td><td> Bois</td>
		// </tr>
		preg_match_all('#<img class="unit" src="img/un/u/(\d+)\.gif" border="0">.*?(\d+)#', $square_content, $matches);

		return (!empty($matches[1]) ) ? array_combine($matches[1], $matches[2]) : array();
	}
	
	private function get_owner($square_content)
	{
		$owner = array();
		
		if( preg_match('#<a href="spieler\.php\?uid=(\d+)"> <b>(.+?)</b>#s', $square_content, $match) )
		{
			$owner['uid'] = $match[1];
			$owner['name'] = trim($match[2]);
		}
		
		if( preg_match('#<a href="allianz\.php\?aid=([1-9]\d+)">(.*?)</a>#s', $square_content, $match) )
		{
			$owner['ally']['aid'] = $match[1];
			$owner['ally']['name'] = trim($match[2]);
		}
		
		// Oasis Only !
		if( preg_match('#<a href="karte\.php\?d=(\d+)&c=\d+">(.+?)</a>#s', $square_content, $match) )
		{
			$owner['village']['d'] = $match[1];
			$owner['village']['name'] = trim($match[2]);
		}
		
		return $owner;
	}
}

/* EOF */
