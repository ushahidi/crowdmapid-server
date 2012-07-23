<?php defined('LOADED_SAFELY') or die('You cannot access this file directly.');

/**
 * CrowdmapID Facebook Plugin
 *
 * @package    CrowdmapID
 * @author     Ushahidi Team <team@ushahidi.com>
 * @copyright  Ushahidi - http://www.ushahidi.com
 * @license    http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License (LGPL)
 */

Plugins::registerEvent("method.social", function($struct) {

	if ($struct[3] == 'facebook') {

		if (isset($struct[4])) {

			switch($struct[4]) {

				case 'authorized':
					api_expectations(array('fb_appid', 'fb_secret', 'fb_scope'));
					facebookAuthorize();
					break;

				case 'publish_action':
					api_expectations(array('fb_appid', 'fb_secret', 'fb_scope', 'fb_namespace', 'fb_action', 'fb_object', 'fb_object_url'));
					facebookPublishAction();
					break;

			}

		}

		Response::Send(404, RESP_ERR, array(
			'error' => 'Supported methods are authorize, deauthorize and publish_action.'
		));

	}

});

function facebookAuthorize()
{
	require_once(PLUGINS_DIRECTORY . 'facebook/include.php');
	facebookCheckAuthorization();

	if(isset($request['frontend'])) {
		header("Content-Type: text/html");
		echo <<<CLOSE_DIALOG
<html>
<head><title>Authorization Complete</title></head>
<body><script type="text/javascript">
window.close();
</script>

<p style="text-align: center; top: 30%"><strong>Authorization Successful</strong><br />
You can now <a href="#" onclick="window.close(); return false;">close this window</a>.</p></body></html>
CLOSE_DIALOG;
		exit;
	}

	Response::Send(200, RESP_OK, array(
		'response' => true
	));
}

function facebookPublishAction()
{
	require_once(PLUGINS_DIRECTORY . 'facebook/include.php');
	facebookCheckAuthorization();

	$parent_deployment = '';
	if (isset($request['parent_deployment']))
		$parent_deployment = '&parent_deployment=' . $request['parent_deployment'];

	try {

		$user_profile = $Facebook->api('/me/' . $request['fb_namespace'] . ':' .
			$request['fb_action'] . '?' .
			$request['fb_object'] . '=' .
			$request['fb_object_url'] .
			$parent_deployment
		, 'POST');

	} catch(FacebookApiException $e) {

		Response::Send(200, RESP_ERR, array(
			'error' => 'There was a problem communicating with the Facebook API.',
			'response' => $e->getMessage()
		));

	}
}

function facebookCheckAuthorization()
{
	global $User, $request;
	$key = 'facebook_app_' . $request['fb_appid'] . '_' . $User->ID() . '_authorized';
	if(Cache::Get($key)) return true;



	require_once(PLUGINS_DIRECTORY . 'facebook/include.php');

	global $Facebook;

	$Facebook = new Facebook(array(
		'appId' => $request['fb_appid'],
		'secret' => $request['fb_secret'],
		'fileUpload' => false
	));

	if ($user = $Facebook->getUser()) {
		try {
			$user_permissions = $Facebook->api('/me/permissions','GET');

			if (isset($user_permissions['data'][0]) &&
				isset($user_permissions['data'][0]['installed']) &&
				$user_permissions['data'][0]['installed'] == 1) {
				$user_permissions = $user_permissions['data'][0];

				if (strlen($request['fb_scope'])) {
					$app_permissions = explode(',', $request['fb_scope']);
					$valid = true;

					foreach($app_permissions as $app_permission) {
						if(!isset($user_permissions[$app_permission]) || $user_permissions[$app_permission] != 1) {
							$valid = false;
							break;
						}
					}

					if ($valid) {
						Cache::Set($key, 'true', 10);
						return true;
					}
				}
			}
		} catch(FacebookApiException $e) {
			if ($e->getType() == 'OAuthException' && strpos($e->getMessage(), 'has not authorized application')) {
				// User revoked application access.
				$Facebook->clearAllPersistentData();
			}
		}
	}

	if (API_METHOD == 'facebook_authorized' && isset($request['frontend']) && isset($request['error'])) {
		// User had a pop up window to authorize the app, but cancelled.
		header("Content-Type: text/html");
		echo <<<CLOSE_DIALOG
<html>
<head><title>Authorization Complete</title></head>
<body><script type="text/javascript">
window.opener.location.reload();
window.close();
</script>

<p style="text-align: center; top: 30%"><strong>Authorization Successful</strong><br />
You can now <a href="#" onclick="window.close(); return false;">close this window</a>.</p></body></html>
CLOSE_DIALOG;
		exit;
	}

	Response::Send(200, RESP_ERR, array(
		'error' => 'User has not authorized the associated Facebook application.',
		'response' => $Facebook->getLoginUrl(array('display' => 'popup', 'scope' => $request['fb_scope']))
	));
}
