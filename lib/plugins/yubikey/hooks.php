<?php defined('LOADED_SAFELY') or die('You cannot access this file directly.');

/**
 * CrowdmapID Yubikey Plugin
 *
 * @package    CrowdmapID
 * @author     Ushahidi Team <team@ushahidi.com>
 * @copyright  Ushahidi - http://www.ushahidi.com
 * @license    http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License (LGPL)
 */

if (defined('YUBIKEY_CLIENT_ID') && defined('YUBIKEY_CLIENT_KEY') && !defined('SUPPORTS_TWO_FACTOR')) {
	define('SUPPORTS_TWO_FACTOR', TRUE);
	define('PLUGIN_YUBIKEY', TRUE);
}

Plugins::registerEvent("method.private.password.get.pre", function(&$activity) {

	// Don't process if another plugin has already raised an error.
	if($activity['error']) return;

	// Is Yubikey support enabled?
	if (!defined('YUBIKEY_CLIENT_ID') || !defined('YUBIKEY_CLIENT_KEY')) return;

	global $User, $Application;

	// Does the user have a Yubikey paired?
	if($identity = $User->Storage($Application->ID(), 'yubikey_paired', null, true)) {

		// They do. Augment the authentication check.
		$activity['override'] = true;

		$otp = (isset($_REQUEST['otp']) ? trim($_REQUEST['otp']) : FALSE);

		// Get the paired identity if the OTP provided is valid
		$incoming_identity = yubikeyGetIdentity($otp);

		// An invalid OTP was provided
		if(!$incoming_identity) {
			$activity['error'] = 'Please provide a valid one-time password.';
			return;
		}

		// The OTP's issuing device is paired with a different account.
		if($incoming_identity != $identity) {
			$activity['error'] = 'That device is not paired with this account.';
			return;
		}

		// Generate a once off.
		$nonce = md5(uniqid(rand()));

		$params = array(
					'id'     => YUBIKEY_CLIENT_ID,
					'nonce'  => $nonce,
					'otp'    => strtolower($otp)
				);
		ksort($params);

		$parameters = '';
		foreach($params as $p => $v)
			$parameters .= "&{$p}={$v}";
		$parameters = ltrim($parameters, '&');

		// TODO: Add client key signature checks.
		/*
		$signature = base64_encode(hash_hmac('sha1', $parameters, YUBIKEY_CLIENT_KEY, true));
		$signature = preg_replace('/\+/', '%2B', $signature);
		$parameters .= '&h=' . $signature;
		 */

		$verify = @file_get_contents('http://api2.yubico.com/wsapi/2.0/verify?' . $parameters);

		if ($verify) {
			$verify = explode("\n", trim(str_replace("\r", '', $verify)));
			if (!$verify) return;

			$yubikey = array();

			foreach($verify as $line) {
				if(!strpos($line, '=')) continue;
				$line = explode('=', $line);
				$yubikey[$line[0]] = $line[1];
			}

			if (!isset($yubikey['nonce']) || $yubikey['nonce'] != $nonce) {
				$activity['error'] = 'The Yubico server responded in a suspicious manner. Try again later.';
				return;

			//} elseif ($yubikey['h'] != $nonce) { // TODO

			} elseif ($yubikey['status'] == 'REPLAYED_OTP') {
				$activity['error'] = 'That YubiKey code has already been used. Please generate another.';
				return;

			} elseif ($yubikey['status'] == 'BAD_OTP') {
				$activity['error'] = 'That YubiKey code is not valid. Please try again.';
				return;

			} elseif ($yubikey['status'] == 'OK') {
				// We're gold.
				return true;

			} else {
				$activity['error'] = 'We are experiencing difficulties verifying your YubiKey. Please try again shortly.';
				return;

			}

		} else {
			$activity['error'] = 'We are experiencing difficulties verifying your YubiKey. Please try again shortly.';
			return;

		}

	}

});

Plugins::registerEvent("method.security", function($struct) {

	global $User;
	isSessionCleared($User->Hash(), true);

	if (isset($struct[3]) && $struct[3] == 'yubikey') {
		global $Application, $request;

		if(HTTP_METHOD == 'GET') {
			if($identity = $User->Storage($Application->ID(), 'yubikey_paired', null, true)) {
				Response::Send(200, RESP_OK, array(
					'paired' => (int)$identity
				));
			} else {
				Response::Send(404, RESP_ERR, array(
					'error' => 'There are no YubiKeys paired with this account.'
				));
			}
		} elseif(HTTP_METHOD == 'POST' || HTTP_METHOD == 'PUT') {
			if($identity = $User->Storage($Application->ID(), 'yubikey_paired', null, true)) {
				Response::Send(500, RESP_ERR, array(
					'error' => 'A Yubikey is already paired with this account.'
				));
			} else {
				api_expectations(array('otp'));
				$identity = yubikeyGetIdentity($request['otp']);

				if(!$identity) {
					Response::Send(500, RESP_ERR, array(
						'error' => 'That is not a recognizable YubiKeys code.'
					));
				}

				if($User->Storage($Application->ID(), 'yubikey_paired', $identity, true)) {
					Response::Send(200, RESP_OK, array(
						'paired' => (int)$identity
					));
				}
			}
		} elseif(HTTP_METHOD == 'DELETE') {
			if($User->Storage($Application->ID(), 'yubikey_paired', '', true)) {
				Response::Send(200, RESP_OK, array());
			}
		}

		Response::Send(404, RESP_ERR, array(
			'error' => 'Invalid request.'
		));

	}

});

function yubikeyGetIdentity($otp) {

	if(!$otp) return false;

	$ret = array();

	if (!preg_match("/^((.*)[:])?" . "(([cbdefghijklnrtuvCBDEFGHIJKLNRTUV]{0,16})" . "([cbdefghijklnrtuvCBDEFGHIJKLNRTUV]{32}))$/", $otp, $matches)) {
		return false;
	}

	if(!isset($matches[4]) || !$matches[4]) {
		return false;
	}

	require_once(PLUGINS_DIRECTORY . 'yubikey/modhex.php');
	$identity = base_convert(b64ToHex(modhexToB64($matches[4])), 16, 10);

	return $identity;

}
