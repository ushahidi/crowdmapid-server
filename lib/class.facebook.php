<?php

	require_once("facebook-sdk-php/base_facebook.php");

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
			$User->Storage($Application->ID(), $session_var_name, $value);
			//$_SESSION[$session_var_name] = $value;
		}

		protected function getPersistentData($key, $default = false) {
			if (!in_array($key, self::$kSupportedKeys)) {
				self::errorLog('Unsupported key passed to getPersistentData.');
				return $default;
			}

			$session_var_name = $this->constructSessionVariableName($key);

			global $User, $Application;
			$ret = $User->Storage($Application->ID(), $session_var_name);
			return ($ret) ? $ret : $default;

			//return isset($_SESSION[$session_var_name]) ? $_SESSION[$session_var_name] : $default;
		}

		protected function clearPersistentData($key) {
			if (!in_array($key, self::$kSupportedKeys)) {
				self::errorLog('Unsupported key passed to clearPersistentData.');
				return;
			}

			$session_var_name = $this->constructSessionVariableName($key);

			global $User, $Application;
			$User->Storage($Application->ID(), $session_var_name, '');
			//unset($_SESSION[$session_var_name]);
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

	/*
	$Facebook = new Facebook(array(
		'appId' => '335510229830429',
		'secret' => '682bfd4cc1d206dcb7622ff5bd6f0b2e',
		'fileUpload' => false
	));

	$Facebook->clearAllPersistentData();
	$user = $Facebook->getUser();

	if(!$user) {
 		echo('Location: ' . $Facebook->getLoginUrl(array('scope' => 'email,publish_actions')));
	}

	echo "\n\n<hr />\n\n";
	var_dump($_SESSION);
	*/
