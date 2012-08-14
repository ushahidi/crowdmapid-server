<?php defined('LOADED_SAFELY') or die('You cannot access this file directly.');

require_once(PLUGINS_DIRECTORY . "facebook/facebook-sdk-php/base_facebook.php");

class Facebook extends BaseFacebook
{
	public function __construct($config) {
		if (!session_id()) {
			session_start();
		}
		parent::__construct($config);
	}

	protected static $kSupportedKeys =
		array('state', 'code', 'access_token', 'user_id');

	protected function setPersistentData($key, $value) {
		if (!in_array($key, self::$kSupportedKeys)) {
			self::errorLog('Unsupported key passed to setPersistentData.');
			return;
		}

		$session_var_name = $this->constructSessionVariableName($key);

		global $User, $Application;

		if($User->assigned) {
			$User->Storage($Application->ID(), $session_var_name, $value);
		} else {
			Cache::Set(REQUEST_UNIQUE_ID . '_' . $session_var_name, $value, 600);
		}
	}

	protected function getPersistentData($key, $default = false) {
		if (!in_array($key, self::$kSupportedKeys)) {
			self::errorLog('Unsupported key passed to getPersistentData.');
			return $default;
		}

		$session_var_name = $this->constructSessionVariableName($key);

		global $User, $Application;

		if($User->assigned) {
			$ret = $User->Storage($Application->ID(), $session_var_name);
		} else {
			$ret = Cache::Get(REQUEST_UNIQUE_ID . '_' . $session_var_name);
		}

		return ($ret) ? $ret : $default;
	}

	protected function clearPersistentData($key) {
		if (!in_array($key, self::$kSupportedKeys)) {
			self::errorLog('Unsupported key passed to clearPersistentData.');
			return;
		}

		$session_var_name = $this->constructSessionVariableName($key);

		global $User, $Application;
		if($User->assigned) {
			$User->Storage($Application->ID(), $session_var_name, '');
		} else {
			Cache::Delete(REQUEST_UNIQUE_ID . '_' . $session_var_name);
		}
	}

	function clearAllPersistentData() {
		foreach (self::$kSupportedKeys as $key) {
			$this->clearPersistentData($key);
		}
	}

	protected function constructSessionVariableName($key) {
		return implode('_', array('fb',
			$this->getAppId(),
			$key));
	}
}
