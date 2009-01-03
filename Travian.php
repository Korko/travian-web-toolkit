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
 * Class Travian
 * Bridge between cURL lib and web applications
 * made for Travian
 */
abstract class Travian
{
		protected $handle;
		protected $connected=FALSE;
		
		public function __construct()
		{
			$this->handle = new cURL();
		}
		
		public abstract function connect($server, $login, $pass);
		public abstract function get_square($did, $cid='');
		public abstract function create_building($pos, $bid, $cid='');
}

/* EOF */
