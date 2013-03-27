<?php defined('LOADED_SAFELY') or die('You cannot access this file directly.');

/**
 * CrowdmapID Plugins Class
 *
 * @package    CrowdmapID
 * @author     Ushahidi Team <team@ushahidi.com>
 * @copyright  Ushahidi - http://www.ushahidi.com
 * @license    http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License (LGPL)
 */

define('PLUGINS_DIRECTORY', './lib/plugins/');

class Plugins {

	static private $hooks = array();

	static public function Initialize()
	{
		if($pluginsDirectory = opendir(PLUGINS_DIRECTORY)) {
			while (false !== ($pluginDirectory = readdir($pluginsDirectory))) {
				if ($pluginDirectory != "." && $pluginDirectory != ".." && is_dir(PLUGINS_DIRECTORY . $pluginDirectory)) {

					// Hooks are intended to register plugin callbacks which load complex code on demand.
					if(file_exists(PLUGINS_DIRECTORY . $pluginDirectory . "/hooks.php"))
						require(PLUGINS_DIRECTORY . $pluginDirectory . "/hooks.php");

					// Runtime code is intended to load at startup.
					if(file_exists(PLUGINS_DIRECTORY . $pluginDirectory . "/runtime.php"))
						require(PLUGINS_DIRECTORY . $pluginDirectory . "/runtime.php");

				}
			}
		}
	}

	static public function registerEvent($event, $callback)
	{
		Plugins::$hooks[$event][] = $callback;
	}

	static public function raiseEvent($event, &$data = array())
	{
		$success = false;

		if (isset(Plugins::$hooks[$event])) {
			foreach (Plugins::$hooks[$event] as $callback) {
				if (is_object($callback)) {
					$callback($data);
					$success = true;
				} elseif (is_string($callback) && function_exists($callback)) {
					call_user_func($callback, $data);
					$success = true;
				}
			}
		}

		return $success;
	}

}

Plugins::Initialize();
