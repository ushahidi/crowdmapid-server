<?php defined('LOADED_SAFELY') or die('You cannot access this file directly.');

/**
 * CrowdmapID Google Authenticator Plugin
 *
 * @package    CrowdmapID
 * @author     Ushahidi Team <team@ushahidi.com>
 * @copyright  Ushahidi - http://www.ushahidi.com
 * @license    http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License (LGPL)
 */

if (!defined('SUPPORTS_TWO_FACTOR')) {
	define('SUPPORTS_TWO_FACTOR', TRUE);
	define('PLUGIN_GOOGLEAUTH', TRUE);
}

Plugins::registerEvent("method.private.password.get.succeed", function(&$activity) {

	global $User, $Application;
	$otp = (isset($_REQUEST['otp']) ? trim($_REQUEST['otp']) : FALSE);

	if($otp)
		$User->Storage($Application->ID(), 'googleauth_lastcode', $otp, true);

});

Plugins::registerEvent("method.private.password.get.pre", function(&$activity) {

	global $User, $Application;

	// Don't process if another plugin has already raised an error.
	if($activity['error']) return;

	// Does the user have a Google Authenticator paired?
	if($secret = $User->Storage($Application->ID(), 'googleauth_paired', null, true)) {

		// They do. Augment the authentication check.
		$activity['override'] = true;

		require_once(PLUGINS_DIRECTORY . 'googleauth/GoogleAuthenticator.php');
		$g = new GoogleAuthenticator();

		$otp = (isset($_REQUEST['otp']) ? trim($_REQUEST['otp']) : FALSE);

		if (!$otp) {

			$activity['error'] = 'Please provide a verification code.';
			return false;

		} else {

			if ($g->checkCode($secret, $otp)) {

				if($lastused = $User->Storage($Application->ID(), 'googleauth_lastcode', null, true)) {
					if($otp == $lastused) {
						Response::Send(500, RESP_ERR, array(
							'error' => 'That verification code has already been used. Please generate another.'
						));
						return false;
					}

				}

				// We're gold.
				return true;

			} else {

				$activity['error'] = 'That verification code is invalid or has expired. Please generate another.';
				return false;

			}
		}

	}

	return true;

});

Plugins::registerEvent("method.security", function($struct) {

	global $User;
	isSessionCleared($User->Hash(), true);

	if ($struct[3] == 'googleauth') {
		global $Application, $request;

		if(HTTP_METHOD == 'GET') {
			if($identity = $User->Storage($Application->ID(), 'googleauth_paired', null, true)) {
				Response::Send(200, RESP_OK, array(
					'paired' => true
				));

			} else {
				require_once(PLUGINS_DIRECTORY . 'googleauth/GoogleAuthenticator.php');
				$g = new GoogleAuthenticator();

				$secret = $g->generateSecret();
				$qrcode = $g->getUrl('evansims', 'crowdmap.com', $secret);
				$User->Storage($Application->ID(), 'googleauth_secret', $secret, true);

				/* Annoying, Google Charts will sometimes incorrectly respond with a 400 Bad Request while an image is being rendered.
				   Let's invoke an HTTP request to force the image rendering early, to try to avoid that. */
				$http_status = null; $timeout = time() + 5000;
				while($http_status != 200 && time() < $timeout) {
					$gcharts = curl_init();

					curl_setopt_array($gcharts, array(
						CURLOPT_URL            => $qrcode,
						CURLOPT_HEADER         => TRUE,
						CURLOPT_NOBODY         => TRUE,
						CURLOPT_RETURNTRANSFER => TRUE,
						CURLOPT_SSL_VERIFYPEER => FALSE,
						CURLOPT_FOLLOWLOCATION => TRUE,
						CURLOPT_MAXREDIRS      => 10,
						CURLOPT_AUTOREFERER    => TRUE,
						CURLOPT_CONNECTTIMEOUT => 5,
						CURLOPT_TIMEOUT        => 5,
						CURLOPT_USERAGENT      => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_3) AppleWebKit/537.31 (KHTML, like Gecko) Chrome/26.0.1410.65 Safari/537.31'
					));

					curl_exec($gcharts);
					$http_status = curl_getinfo($gcharts, CURLINFO_HTTP_CODE);
					curl_close($gcharts);
				}

				Response::Send(200, RESP_OK, array(
					'paired' => false,
					'secret' => $secret,
					'time'   => 30,
					'qrcode' => $qrcode
				));

			}

		} elseif(HTTP_METHOD == 'POST' || HTTP_METHOD == 'PUT') {
			$secret = $User->Storage($Application->ID(), 'googleauth_secret', null, true);

			if($User->Storage($Application->ID(), 'googleauth_paired', null, true)) {
				Response::Send(500, RESP_ERR, array(
					'error' => 'A Google Authenticator is already paired with this account.'
				));

			} else {
				api_expectations(array('otp'));

				require_once(PLUGINS_DIRECTORY . 'googleauth/GoogleAuthenticator.php');
				$g = new GoogleAuthenticator();

				if($g->checkCode($secret, $request['otp'])) {
					if($User->Storage($Application->ID(), 'googleauth_paired', $secret, true)) {
						Response::Send(200, RESP_OK, array(
							'paired' => true
						));
						return;
					}

				} else {
					Response::Send(500, RESP_ERR, array(
						'paired' => false,
						'error'  => 'That verification code is invalid or has expired.'
					));

				}

			}

		} elseif(HTTP_METHOD == 'DELETE') {

			if($User->Storage($Application->ID(), 'googleauth_paired', '', true)) {
				$User->Storage($Application->ID(), 'googleauth_secret', '', true);
				Response::Send(200, RESP_OK, array());

			}

		}

		Response::Send(404, RESP_ERR, array(
			'error' => 'Invalid request.'
		));

	}

});
