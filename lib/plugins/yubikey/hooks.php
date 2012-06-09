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

	// Is Yubikey support configured?
	if (!defined('YUBIKEY_CLIENT_ID') || !defined('YUBIKEY_CLIENT_KEY')) return;

	// Yubikey passcodes are 44 characters long.
	if (strlen($activity['raw']) != 44) return;

	$activity['raw'] = strtolower($activity['raw']);

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
			$activity['override'] = true;
			$activity['error'] = 'The Yubico server responded in a suspicious manner. Try again later.';
		//} elseif ($yubikey['h'] != $nonce) { // TODO
		} elseif ($yubikey['status'] == 'REPLAYED_OTP') {
			$activity['override'] = true;
			$activity['error'] = 'That Yubikey passcode has already been used. Please generate another and try again.';
		} elseif ($yubikey['status'] == 'BAD_OTP') {
			$activity['override'] = true;
			$activity['error'] = 'That Yubikey passcode does not appeara to be valid. Please generate another and try again.';
		} elseif ($yubikey['status'] == 'OK') {
			$activity['override'] = true;
		} else {
			$activity['override'] = true;
			$activity['error'] = 'We are currently experiencing difficulties verifying your Yubikey with Yubico. Please try again shortly.';
		}

	} else {
		$activity['override'] = true;
		$activity['error'] = 'We are currently experiencing difficulties verifying your Yubikey with Yubico. Please try again shortly.';
	}

});

