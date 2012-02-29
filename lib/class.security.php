<?php

class Security
{

	function Hash($string, $length = 512)
	{
		return substr(hash('sha512', (CFG_SALT . $string), false), 0, $length);
	}

	function Generate($length)
	{
		$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
		$charLen = strlen($chars);
		$built = '';

		mt_srand((int)microtime(true));
		for($i = 0; $i < $length; $i++)
		{
			$built .= $chars[mt_rand(0, $charLen)];
		}

		return $built;
	}

	function randHash($length = 512)
	{
		return $this->Hash($this->Generate($length), $length);
	}

}

$Security = new Security();
