<?php // defined('LOADED_SAFELY') or die('You cannot access this file directly.');

/**
 * CrowdmapID Cache Management Class
 *
 * @package    CrowdmapID
 * @author     Ushahidi Team <team@ushahidi.com>
 * @copyright  Ushahidi - http://www.ushahidi.com
 * @license    http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License (LGPL)
 */

class Cache {

	static private $connection;

	static function Connect() {
		if (Cache::$connection) return true;

		Cache::$connection = new Memcache;
		Cache::$connection->pconnect(CFG_MEMCACHED, 11211);

		if (Cache::$connection === FALSE) {
			global $Response;
			$Response->Send(503, RESP_ERR, array(
				   'error' => 'Service is currently unavailable. (Memcache)'
			));
		}

		Cache::$connection->setCompressThreshold(20000, 0.2);
	}

	static function Get($key, $default = false) {

		Cache::Connect(); // Ensure we are connected.
		if($data = Cache::$connection->get("CMID_{$key}")) return $data;
		return $default;

	}

	static function Set($key, $value, $expires = 30) {

		Cache::Connect();
		Cache::$connection->set("CMID_{$key}", $value, MEMCACHE_COMPRESSED, $expires);
		return true;

	}

	static function Delete($key) {

		Cache::Connect();
		Cache::$connection->delete("CMID_{$key}");
		return true;

	}

	static function Increment($key, $value = 1) {

		Cache::Connect();
		Cache::$connection->increment("CMID_{$key}", $value);
		return true;

	}

	static function Decrement($key, $value = 1) {

		Cache::Connect();
		Cache::$connection->decrement("CMID_{$key}", $value);
		return true;

	}

	static function Clear() {

		Cache::Connect();
		Cache::$connection->flush();
		return true;

	}

}
