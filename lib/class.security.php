<?php defined('LOADED_SAFELY') or die('You cannot access this file directly.');

/**
 * CrowdmapID Security Class
 *
 * @package    CrowdmapID
 * @author     Ushahidi Team <team@ushahidi.com>
 * @copyright  Ushahidi - http://www.ushahidi.com
 * @license    http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License (LGPL)
 */

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
