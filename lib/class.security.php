<?php

if(!defined('LOADED_SAFELY')) die('You cannot access this file directly.');

class Security
{

	public function Hash($string, $length = 512)
	{
		return substr(hash('sha512', (CFG_SALT . $string), false), 0, $length);
	}

	public function Generate($length)
	{
		$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
		$charLen = strlen($chars) - 1;
		$built = '';

		mt_srand((int)microtime(true));
		for($i = 0; $i < $length; $i++)
		{
			$built .= $chars[mt_rand(0, $charLen)];
		}

		return $built;
	}

	public function randHash($length = 512)
	{
		return $this->Hash($this->Generate($length), $length);
	}

}

$Security = new Security();
