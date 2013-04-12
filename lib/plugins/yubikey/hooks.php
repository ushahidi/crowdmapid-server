<?php defined('LOADED_SAFELY') or die('You cannot access this file directly.');

/**
 * CrowdmapID Yubikey Plugin
 *
 * @package    CrowdmapID
 * @author     Ushahidi Team <team@ushahidi.com>
 * @copyright  Ushahidi - http://www.ushahidi.com
 * @license    http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License (LGPL)
 */

Plugins::registerEvent("method.private.password.get.pre", function(&$activity) {

	// Is Yubikey support enabled?
	if (!defined('YUBIKEY_CLIENT_ID') || !defined('YUBIKEY_CLIENT_KEY')) return;

	global $User, $Application;

	// Does the user have a Yubikey paired?
	if($identity = $User->Storage($Application->ID(), 'yubikey_paired', null, true)) {

		// They do. Override standard password functionality.
		$activity['override'] = true;

		// Get the incoming key's identity, if it is infact a Yubikey.
		$incoming_identity = yubikeyGetIdentity($activity['raw']);

		if(!$incoming_identity) {
			$activity['error'] = 'This account requires a valid Yubikey to sign in.';
			return;
		}

		if($incoming_identity != $identity) {
			$activity['error'] = 'That Yubikey is not paired with this account.';
			return;
		}

		// Clean the OTP.
		$activity['raw'] = strtolower($activity['raw']);

		// Generate a once off.
		$nonce = md5(uniqid(rand()));

		$params = array(
					'id'     => YUBIKEY_CLIENT_ID,
					'nonce'  => $nonce,
					'otp'    => $activity['raw']
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

			if ($yubikey['nonce'] != $nonce) {
				$activity['error'] = 'The Yubico server responded in a suspicious manner. Try again later.';
			//} elseif ($yubikey['h'] != $nonce) { // TODO
			} elseif ($yubikey['status'] == 'REPLAYED_OTP') {
				$activity['error'] = 'That Yubikey passcode has already been used. Please generate another and try again.';
			} elseif ($yubikey['status'] == 'BAD_OTP') {
				$activity['error'] = 'That Yubikey passcode does not appeara to be valid. Please generate another and try again.';
			} elseif ($yubikey['status'] == 'OK') {
				// We're gold.
			} else {
				$activity['error'] = 'We are experiencing difficulties verifying your Yubikey with Yubico. Please try again shortly.';
			}

		} else {
			$activity['error'] = 'We are experiencing difficulties verifying your Yubikey with Yubico. Please try again shortly.';
		}

	}

});

Plugins::registerEvent("method.security", function($struct) {

	global $User;
	isSessionCleared($User->Hash(), true);

	if ($struct[3] == 'yubikey') {
		global $Application, $request;

		if(HTTP_METHOD == 'GET') {
			if($identity = $User->Storage($Application->ID(), 'yubikey_paired', null, true)) {
				Response::Send(200, RESP_OK, array(
					'paired' => $identity
				));
			} else {
				Response::Send(404, RESP_ERR, array(
					'error' => 'There are no Yubikeys paired with this account.'
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
						'error' => 'That doesn\'t appear to be a valid Yubikey.'
					));
				}

				if($User->Storage($Application->ID(), 'yubikey_paired', $identity, true)) {
					Response::Send(200, RESP_OK, array(
						'paired' => $identity
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
