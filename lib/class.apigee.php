<?php

if(!defined('LOADED_SAFELY')) die('You cannot access this file directly.');

class Apigee {

	private static $api;

	public static function listUsers() {
		return Apigee::_call('/users.json');
	}

	public static function registerUser($email, $fullName = '') {
		global $Application;

		$ret = Apigee::_call('/users.json', array('userName' => $email, 'fullName' => $fullName, 'password' => $Application->apigeePassword()), HTTP_METHOD_POST);
		if(isset($ret->smartKey)) return $ret->smartKey;
		return false;
	}

	public static function getUser($email) {
		return Apigee::_call('/users/' . urlencode($email) . '.json');
	}

	public static function getUserSmartKey($email) {
		$ret = Apigee::_call('/smartkeys/me.json', array(), HTTP_METHOD_GET, $email);
		if(isset($ret->smartKey)) return $ret->smartKey;
		return false;
	}

	public static function updateUser($existingEmail, $email = null, $fullName = null) {
		if($email && $existingEmail == $email) $email = null;

		$changes = array();
		if($email) $changes['userName'] = $email;
		if($fullName) $changes['fullName'] = $fullName;

		if($changes) {
			$ret = Apigee::_call('/users/' . urlencode($existingEmail) . '.json', $changes, HTTP_METHOD_PUT, $existingEmail);
			if(isset($ret->userName)) return true;
		}

		return false;
	}

	public static function deleteUser($email) {
		$ret = Apigee::_call('/users/' . urlencode($email) . '.json', array(), HTTP_METHOD_DELETE);
		if(isset($ret->userName)) return true;
		return false;
	}

	public static function getCredentials($provider, $smartKey) {
		return Apigee::_call('/smartkeys/' . urlencode($smartKey) . '/providers/' . urlencode($provider) . '.json');
	}

	public static function queryProvider($provider, $method, $smartKey, $params = array(), $req_type = HTTP_METHOD_GET) {
		if(substr($method, 0, 1) == '/') $method = substr($method, 1);
		$params['smartkey'] = $smartKey;

		return Apigee::_call('/' . urlencode($provider) . '/' . $method, $params, $req_type);
	}

	private static function _call($method, $params = array(), $req_type = HTTP_METHOD_GET, $username = null, $critical = false) {

		global $Application;

		Apigee::$api = curl_init();
		$_req_type = '';

		if(!$username) $username = $Application->apigeeUsername();

		if ( Apigee::$api )
		{

			curl_setopt(Apigee::$api, CURLOPT_POSTFIELDS, NULL);

			if($req_type == HTTP_METHOD_GET) {
				$_req_type = 'GET';
				curl_setopt(Apigee::$api, CURLOPT_POST, false);
				if(count($params)) {
					$method .= '?';
					foreach($params as $p => $v) $method .= $p . '=' . urlencode($v) . '&';
					$method = rtrim($method, '&');
				}
			} elseif($req_type == HTTP_METHOD_POST) {
				$_req_type = 'POST';
				curl_setopt(Apigee::$api, CURLOPT_POST, true);
				if($params) curl_setopt(Apigee::$api, CURLOPT_POSTFIELDS, json_encode($params));
			} elseif($req_type == HTTP_METHOD_PUT) {
				$_req_type = 'PUT';
				curl_setopt(Apigee::$api, CURLOPT_POST, true);
				curl_setopt(Apigee::$api, CURLOPT_CUSTOMREQUEST, "PUT");
				if($params) curl_setopt(Apigee::$api, CURLOPT_POSTFIELDS, json_encode($params));
			} elseif($req_type == HTTP_METHOD_DELETE) {
				$_req_type = 'DELETE';
				curl_setopt(Apigee::$api, CURLOPT_POST, true);
				curl_setopt(Apigee::$api, CURLOPT_CUSTOMREQUEST, "DELETE");
				if($params) curl_setopt(Apigee::$api, CURLOPT_POSTFIELDS, json_encode($params));
			}

			curl_setopt(Apigee::$api, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Authorization: Basic ' . base64_encode($username . ':' . $Application->apigeePassword())));

			curl_setopt(Apigee::$api, CURLOPT_TIMEOUT, 10);

			curl_setopt(Apigee::$api, CURLOPT_HEADER, false);
			curl_setopt(Apigee::$api, CURLOPT_RETURNTRANSFER, true);
			curl_setopt(Apigee::$api, CURLOPT_SSL_VERIFYPEER, false);

			if(substr($method, 0, 1) != '/') $method = "/{$method}";
			curl_setopt(Apigee::$api, CURLOPT_URL, 'https://' . $Application->apigeeApplicationID() . '-api.apigee.com/v1' . $method);

			$raw = curl_exec(Apigee::$api);
			$resp = json_decode($raw);
			$http_status = (int)curl_getinfo(Apigee::$api, CURLINFO_HTTP_CODE);
			@curl_close(Apigee::$api);

			if($http_status != 400 && $http_status != 201 && $critical == true) {
				die("<p>Apigee API choked on {$method} w/ {$http_status}. Response:<br /><pre>{$raw}</pre></p>");
			}

			return $resp;
		}

	}

}

class Facebook {

	public static $smartKey;
	public static $oauthToken;

	public static function getAuthorizationURL($callback = '', $scope = 'email,publish_actions') {
		global $Application;

		if($callback) $callback = '&app_callback=' . urlencode($callback);
		if($scope) $scope = '&app_perms=' . $scope;
		return 'https://' . $Application->apigeeApplicationID() . '-api.apigee.com/v1/providers/facebook/authorize?smartkey=' . Facebook::$smartKey . $scope . $callback;
	}

	public static function isAuthorized() {
		$ret = Apigee::getCredentials('facebook', Facebook::$smartKey);
		if(isset($ret->oauthToken)) {
			Facebook::$oauthToken = $ret->oauthToken;
			return true;
		}
		return false;
	}

	public static function getMyProfile() {
		$ret = Apigee::queryProvider('facebook', '/me', Facebook::$smartKey);
		if(isset($ret->id)) return $ret;
		return false;
	}

	public static function doAction($userid, $app, $action, $object, $url, $params = array(), $type = HTTP_METHOD_GET) {
		//$params[$object] = $url;
		//$params['access_token'] = Facebook::$oauthToken;
		return Apigee::queryProvider('facebook', "/{$userid}/{$app}:{$action}?{$object}={$url}&expires_in=90&access_token=" . Facebook::$oauthToken, Facebook::$smartKey, $params, $type);
	}

}
