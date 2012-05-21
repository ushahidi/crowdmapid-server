<?php

if(!defined('LOADED_SAFELY')) die('You cannot access this file directly.');

if (API_METHOD == 'addusertosite' )
{
	api_expectations(array('email', 'session_id', 'url'));

	if ($User->Set($request['email']) )
	{
		$session = $User->Sessions($Application->ID(), $request['session_id']);
		if( $session )
		{
			if( validateString($request['url'], FILTER_VALIDATE_URL) )
			{
				$Response->Send(200, RESP_OK, array(
					'response' => $User->Site($Application->ID(), $request['url'])
				));
			}

			$Response->Send(200, RESP_ERR, array(
				'error' => 'The submitted URL is not valid.'
			));
		}

		$Response->Send(200, RESP_ERR, array(
			'error' => 'Please provide a valid session identifier.'
		));
	}

	$Response->Send(200, RESP_ERR, array(
		'error' => 'The email address does not appear to be registered.'
	));
}
elseif (API_METHOD == 'removeuserfromsite' )
{
	api_expectations(array('email', 'session_id', 'url'));

	if ($User->Set($request['email']) )
	{
		$session = $User->Sessions($Application->ID(), $request['session_id']);
		if( $session )
		{
			if( validateString($request['url'], FILTER_VALIDATE_URL) )
			{
				$Response->Send(200, RESP_OK, array(
					'response' => $User->siteDelete($Application->ID(), $request['url'])
				));
			}

			$Response->Send(200, RESP_ERR, array(
				'error' => 'The submitted URL is not valid.'
			));
		}

		$Response->Send(200, RESP_ERR, array(
			'error' => 'Please provide a valid session identifier.'
		));
	}

	$Response->Send(200, RESP_ERR, array(
		'error' => 'The email address does not appear to be registered.'
	));
}
elseif (API_METHOD == 'usersites' )
{
	api_expectations(array('email', 'session_id'));

	if ($User->Set($request['email']) )
	{
		$session = $User->Sessions($Application->ID(), $request['session_id']);

		if( $session )
		{
			$Response->Send(200, RESP_OK, array(
				'response' => $User->Sites($Application->ID())
			));
		}

		$Response->Send(200, RESP_ERR, array(
			'error' => 'Please provide a valid session identifier.'
		));
	}

	$Response->Send(200, RESP_ERR, array(
		'error' => 'The email address does not appear to be registered.'
	));
}
elseif (API_METHOD == 'storage_put' )
{
	api_expectations(array('email', 'session_id', 'key', 'value'));

	if ($User->Set($request['email']) )
	{
		if($User->Sessions($Application->ID(), $request['session_id']))
		{
			$public = false; if(isset($request['public'])) $public = true;
			$expires = null; if(isset($request['expires'])) $expires = (int)$request['expires'];

			$User->Storage($Application->ID(), $request['key'], $request['value'], $public, $expires);

			$Response->Send(200, RESP_OK, array(
				'response' => TRUE
			));
		}

		$Response->Send(200, RESP_ERR, array(
			'error' => 'Please provide a valid session identifier.'
		));
	}

	$Response->Send(200, RESP_ERR, array(
		'error' => 'The email address does not appear to be registered.'
	));
}
elseif (API_METHOD == 'storage_get' )
{
	api_expectations(array('email', 'session_id', 'key'));

	if ($User->Set($request['email']) )
	{
		if ($User->Sessions($Application->ID(), $request['session_id']))
		{
			$public = false; if(isset($request['public'])) $public = true;
			$default = null; if(isset($request['default'])) $default = $request['default'];

			$ret = $User->Storage($Application->ID(), $request['key'], null, $public);

			if($ret) {
				$Response->Send(200, RESP_OK, array(
					'response' => $ret
				));
			} else {
				if($default) {
					$Response->Send(200, RESP_OK, array(
						'response' => $default
					));
				} else {
					$Response->Send(200, RESP_ERR, array(
						'error' => 'There is nothing stored for this user matching that query.'
					));
				}
			}
		}

		$Response->Send(200, RESP_ERR, array(
			'error' => 'Please provide a valid session identifier.'
		));
	}

	$Response->Send(200, RESP_ERR, array(
		'error' => 'The email address does not appear to be registered.'
	));
}
elseif (API_METHOD == 'changeemail' )
{
	api_expectations(array('oldemail', 'newemail', 'password', 'mailbody'));

	if ($User->Set($request['oldemail']) )
	{
		// Encode submitted password so we can compare.
		$password = $Security->Hash($request['password'], 64);

		if ($User->Password() === $password )
		{
			if( validateString($request['oldemail']) )
			{
				if ($request['oldemail'] === $request['newemail'] )
				{
					// Just nod our head if we're trying to change to the same address.
					$Response->Send(200, RESP_OK, array(
						'response' => true
					));
				}

				// Does the application have a custom mail_from set?
				$from = CFG_MAIL_FROM;
				if ($Application->mailFrom() )
				{
					$from = $Application->mailFrom();
				}

				// Generate a one-use token for authorizing this change.
				$token = strtoupper($Security->randHash(32));
				$User->Token(array(
					'token' => $token,
					'memory' => $request['newemail'],
					'expires' => CFG_TOKEN_EXPIRES
				));

				// Replace %token% in mailbody with the necessary authorization code.
				$request['mailbody'] = trim(filter_var($request['mailbody'], FILTER_SANITIZE_STRING));
				$request['mailbody'] = str_replace('%token%', $token, $request['mailbody']);

				if(isset($request['mailsubject'])) $request['subject'] = $request['mailsubject'];
				if(!isset($request['subject'])) $request['subject'] = 'Confirm Your ' . $Application->Name() . ' Email Address Change';
				$request['subject'] = trim(filter_var($request['subject'], FILTER_SANITIZE_STRING));

				// Notify user of the address change.
				$Mailing->Send($from, $request['newemail'], $request['subject'], $request['mailbody']);

				// API response
				$Response->Send(200, RESP_OK, array(
					'response' => true
				));
			}

			$Response->Send(200, RESP_ERR, array(
				'error' => 'The submitted email address is not valid.'
			));
		}
		else
		{
			$Response->Send(200, RESP_ERR, array(
				'error' => 'The password is incorrect for this user.'
			));
		}
	}

	$Response->Send(200, RESP_ERR, array(
		'error' => 'The email address does not appear to be registered.'
	));
}
elseif (API_METHOD == 'confirmemail' )
{
	api_expectations(array('email', 'token'));

	if ($User->Set($request['email']) && validateString($request['email']) )
	{
		$token = $User->Token();

		if ($token['token'] === $request['token'] )
		{
			if ($token['expires'] > time() )
			{
				// Set email address.
				$User->Email($token['memory']);

				// Reset token/memory.
				$User->ClearToken();

				// API response
				$Response->Send(200, RESP_OK, array(
					'response' => true
				));
			}
			else
			{
				$Response->Send(200, RESP_ERR, array(
					'error' => 'The submitted token has expired.'
				));
			}
		}
		else
		{
			$Response->Send(200, RESP_OK, array(
				'response' => false
			));
		}
	}

	$Response->Send(200, RESP_ERR, array(
		'error' => 'The email address does not appear to be registered.'
	));
}
elseif (API_METHOD == 'changepassword' )
{
	api_expectations(array('email', 'oldpassword', 'newpassword'));

	if ($User->Set($request['email']) )
	{
		// Encode submitted passwords so we can compare.
		$request['oldpassword'] = $Security->Hash($request['oldpassword'], 64);

		if ($User->Password() === $request['oldpassword'] )
		{
			if (strlen($request['newpassword']) < 5 || strlen($request['newpassword']) > 128 )
			{
				$Response->Send(200, RESP_ERR, array(
					'error' => 'Please provide a password between 5 and 128 characters in length.'
				));
			}

			$request['newpassword'] = $Security->Hash($request['newpassword'], 64);

			if ($request['newpassword'] === $request['oldpassword'] )
			{
				$Response->Send(200, RESP_ERR, array(
					'error' => 'You cannot reuse your password.'
				));
			}

			$User->Password($request['newpassword']);

			// API response
			$Response->Send(200, RESP_OK, array(
				'response' => true
			));
		}

		$Response->Send(200, RESP_ERR, array(
			'error' => 'The password is incorrect for this user.'
		));
	}

	$Response->Send(200, RESP_ERR, array(
		'error' => 'The email address does not appear to be registered.'
	));
}
elseif (API_METHOD == 'checkpassword' )
{
	api_expectations(array('email', 'password'));

	if ($User->Set($request['email']) )
	{
		$password = $Security->Hash($request['password'], 64);

		if ($User->Password() === $password )
		{
			$session = $User->Session($Application->ID());

			$Response->Send(200, RESP_OK, array(
				'response' => true
			));
		}
		else
		{
			$Response->Send(200, RESP_OK, array(
				'response' => false
			));
		}
	}

	$Response->Send(200, RESP_ERR, array(
		'error' => 'The email address does not appear to be registered.'
	));
}
elseif (API_METHOD == 'register' )
{
	api_expectations(array('email', 'password'));

	if (strlen($request['password']) < 5 || strlen($request['password']) > 128 )
	{
		$Response->Send(200, RESP_ERR, array(
			'error' => 'Please provide a password between 5 and 128 characters in length.'
		));
	}

	if ($User->Set($request['email']) )
	{
		$Response->Send(200, RESP_ERR, array(
			'error' => 'The given email address has already been registered.'
		));
	}

	$resp = $User->Create($request['email'], $request['password']);

	if ($resp )
	{
		$Response->Send(200, RESP_OK, array(
			'response' => $resp['hash']
		));
	}

	$Response->Send(200, RESP_ERR, array(
		'error' => 'We encountered a problem while attempting to register your address. Please try again shortly.'
	));
}
elseif (API_METHOD == 'registered' )
{
	api_expectations(array('email'));

	if ($User->Set($request['email']) )
	{
		$Response->Send(200, RESP_OK, array(
			'response' => true
		));
	}

	$Response->Send(200, RESP_OK, array(
		'response' => false
	));
}
elseif (API_METHOD == 'requestpassword' )
{
	// Reset Password, Part 1: Confirmation Email w/ Link.
	api_expectations(array('email', 'mailbody'));

	if ($User->Set($request['email']) )
	{
		// Does the application have a custom mail_from set?
		$from = CFG_MAIL_FROM;
		if ($Application->mailFrom() )
		{
			$from = $Application->mailFrom();
		}

		// Generate a one-use token for authorizing this change.
		$token = strtoupper($Security->randHash(32));
		$User->Token(array(
			'token' => $token,
			'memory' => 'RESET_PASSWORD',
			'expires' => CFG_TOKEN_EXPIRES
		));

		// Replace %token% in mailbody with the necessary authorization code.
		$request['mailbody'] = trim(filter_var($request['mailbody'], FILTER_SANITIZE_STRING));
		$request['mailbody'] = str_replace('%token%', $token, $request['mailbody']);

		if(isset($request['mailsubject'])) $request['subject'] = $request['mailsubject'];
		if(!isset($request['subject'])) $request['subject'] = 'Resetting Your ' . $Application->Name() . ' Password';
		$request['subject'] = trim(filter_var($request['subject'], FILTER_SANITIZE_STRING));

		// Notify user of the address change.
		$Mailing->Send($from, $request['email'], $request['subject'], $request['mailbody']);

		// API response
		$Response->Send(200, RESP_OK, array(
			'response' => true
		));
	}

	$Response->Send(200, RESP_ERR, array(
		'error' => 'The email address does not appear to be registered.'
	));
}
elseif (API_METHOD == 'setpassword' )
{
	// Reset Password, Part 2: Email Confirmed, Set Password
	api_expectations(array('email', 'token', 'password'));

	if ($User->Set($request['email']) )
	{
		$token = $User->Token();

		if ($token['token'] === $request['token'] )
		{
			if ($token['expires'] > time() )
			{
				if (strlen($request['password']) < 5 || strlen($request['password']) > 128 )
				{
					$Response->Send(200, RESP_ERR, array(
						'error' => 'Please provide a password between 5 and 128 characters in length.'
					));
				}

				$request['password'] = $Security->Hash($request['password'], 64);

				// Set password.
				$User->Password($request['password']);

				// Reset token/memory.
				$User->ClearToken();

				// API response
				$Response->Send(200, RESP_OK, array(
					'response' => true
				));
			}
			else
			{
				$Response->Send(200, RESP_ERR, array(
					'error' => 'The submitted token has expired.'
				));
			}
		}
		else
		{
			$Response->Send(200, RESP_ERR, array(
				'error' => 'The submitted token is invalid.'
			));
		}
	}

	$Response->Send(200, RESP_ERR, array(
		'error' => 'The email address does not appear to be registered.'
	));
}
elseif (API_METHOD == 'sessions' )
{
	api_expectations(array('email', 'session_id'));

	if ($User->Set($request['email']) )
	{
		$sessions = $User->Sessions($Application->ID());

		foreach ( $sessions as $session )
		{
			if ($session['id'] == $request['session_id'] )
			{
				$Response->Send(200, RESP_OK, array(
					'response' => $sessions
				));
			}
		}

		$Response->Send(200, RESP_ERR, array(
			'error' => 'Please provide a valid session identifier.'
		));
	}

	$Response->Send(200, RESP_ERR, array(
		'error' => 'The email address does not appear to be registered.'
	));
}
elseif (API_METHOD == 'signedin' )
{
	if (isset($_COOKIE['user_id']) && isset($_COOKIE['session_id']) && !isset($request['user_id']) && !isset($request['session_id']) )
	{
		$request['user_id'] = trim(substr($_COOKIE['user_id'], 0, 128));
		$request['session_id'] = trim(substr($_COOKIE['session_id'], 0, 64));
	}
	api_expectations(array('user_id', 'session_id'));

	if ($User->Set($request['user_id']) )
	{
		$session = $User->Sessions($Application->ID(), $request['session_id']);

		if( $session )
		{
			$Response->Send(200, RESP_OK, array(
				'response' => true
			));
		}

		$Response->Send(200, RESP_ERR, array(
			'response' => false
		));
	}

	$Response->Send(200, RESP_ERR, array(
		'error' => 'Please provide a valid user identifier.'
	));
}
elseif (API_METHOD == 'signin' )
{
	api_expectations(array('email', 'password'));
	checkUser($request['email']);

	$password = $Security->Hash($request['password'], 64);

	if ($User->Password() === $password )
	{
		$session = $User->Session($Application->ID());

		$Response->Send(200, RESP_OK, array(
			'response' => array(
				'user_id' => $User->Hash(),
				'session_id' => $session
			)
		));
	}
	else
	{
		$Response->Send(200, RESP_ERR, array(
			'error' => 'The password is incorrect for this user.'
		));
	}

}
elseif (API_METHOD == 'signout' )
{
	api_expectations(array('email', 'session_id'));
	checkUser($request['email']);
	checkSession($request['session_id']);

	$User->sessionDelete($Application->ID(), $request['session_id']);

	$Response->Send(200, RESP_OK, array(
		'response' => true
	));
}
elseif (API_METHOD == 'herp' )
{
	$Response->Send(200, RESP_OK, array(
		   'herp' => 'derp'
	));
}
elseif (API_METHOD == 'facebook_authorized' )
{
	// email,publish_actions
	api_expectations(array('email', 'session_id', 'fb_appid', 'fb_secret', 'fb_scope'));
	checkUser($request['email']);
	checkSession($request['session_id']);
	checkFacebookAuthorization();

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

	$Response->Send(200, RESP_OK, array(
		'response' => true
	));
}
elseif (API_METHOD == 'facebook_deauthorized' )
{
	// TODO
	file_put_contents('facebook.txt', print_r($_POST, true) . "\n\n" . print_r($_GET, true) . "\n\n");
}
elseif (API_METHOD == 'facebook_publish_action' )
{
	// fb_appid=335510229830429&fb_secret=682bfd4cc1d206dcb7622ff5bd6f0b2e&fb_scope=email,publish_actions&fb_namespace=ushahidi_crowdmap&fb_action=explore&fb_object=deployment&fb_object_url=http://testing.crdmp.com
	api_expectations(array('email', 'session_id', 'fb_appid', 'fb_secret', 'fb_scope', 'fb_namespace', 'fb_action', 'fb_object', 'fb_object_url'));
	checkUser($request['email']);
	checkSession($request['session_id']);
	checkFacebookAuthorization();

	$parent_deployment = '';
	if(isset($request['parent_deployment'])) {
		$parent_deployment = '&parent_deployment=' . $request['parent_deployment'];
	}

	try {
		$user_profile = $Facebook->api('/me/' . $request['fb_namespace'] . ':' .
			$request['fb_action'] . '?' .
			$request['fb_object'] . '=' .
			$request['fb_object_url'] .
			$parent_deployment
		, 'POST');
	} catch(FacebookApiException $e) {
		$Response->Send(200, RESP_ERR, array(
			'error' => 'There was a problem communicating with the Facebook API.',
			'response' => $e->getMessage()
		));
	}
}
/*
elseif (API_METHOD == 'apigee_smartkey' )
{
	api_expectations(array('email', 'session_id'));
	checkUser($request['email']);
	checkSession($request['session_id']);

	$smartKey = $User->apigeeSmartKey($Application->ID());

	if($smartKey) {
		$Response->Send(200, RESP_OK, array(
			'response' => $smartKey
		));
	} else {
		$Response->Send(200, RESP_ERR, array(
			'error' => 'Unable to collect Apigee smartkey for user. Try again later.'
		));
	}

}
elseif (API_METHOD == 'facebook_authorized' )
{
	api_expectations(array('email', 'session_id'));
	checkUser($request['email']);
	checkSession($request['session_id']);

	$resp = checkFacebookAuthorization();

	$Response->Send(200, RESP_OK, array(
		'response' => $resp
	));
}
elseif (API_METHOD == 'facebook_publish_stream' )
{
	api_expectations(array('email', 'session_id', 'action', 'object', 'url'));
	checkUser($request['email']);
	checkSession($request['session_id']);
	$facebookID = null;

	if($facebookID = checkFacebookAuthorization()) {
		var_dump(Facebook::doAction($facebookID, 'ushahidi_crowdmap', $request['action'], $request['object'], $request['url'], array(), HTTP_METHOD_POST));
	}
}
*/

function checkUser($email) {
	global $User, $Response;

	if(!$User->Set($email)) {
		$Response->Send(200, RESP_ERR, array(
			'error' => 'The email address does not appear to be registered.'
		));
	}
}

function checkSession($session) {
	global $User, $Application, $Response, $request;

	if (!$User->Sessions($Application->ID(), $request['session_id'])) {
		$Response->Send(200, RESP_ERR, array(
			'error' => 'Please provide a valid session identifier.'
		));
	}
}

function checkFacebookAuthorization() {
	global $Facebook, $Response, $request;

	$Facebook = new Facebook(array(
		'appId' => $request['fb_appid'],
		'secret' => $request['fb_secret'],
		'fileUpload' => false
	));

	if($user = $Facebook->getUser()) {
		try {
			$user_permissions = $Facebook->api('/me/permissions','GET');

			if(isset($user_permissions['data'][0]) &&
				isset($user_permissions['data'][0]['installed']) &&
				$user_permissions['data'][0]['installed'] == 1) {
				$user_permissions = $user_permissions['data'][0];

				if(strlen($request['fb_scope'])) {
					$app_permissions = explode(',', $request['fb_scope']);
					$valid = true;

					foreach($app_permissions as $app_permission) {
						if(!isset($user_permissions[$app_permission]) || $user_permissions[$app_permission] != 1) {
							$valid = false;
							break;
						}
					}

					if($valid) {
						// User has the app authenticated, installed and all requested permissions are available.
						return true;
					}
				}
			}
		} catch(FacebookApiException $e) {
			if($e->getType() == 'OAuthException' && strpos($e->getMessage(), 'has not authorized application')) {
				// User revoked application access.
				$Facebook->clearAllPersistentData();
			}
		}
	}

	if(API_METHOD == 'facebook_authorized' && isset($request['frontend']) && isset($request['error'])) {
		// User had a pop up window to authorize the app, but cancelled.
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

	$Response->Send(200, RESP_ERR, array(
		'error' => 'User has not authorized the associated Facebook application.',
		'response' => $Facebook->getLoginUrl(array('display' => 'popup', 'scope' => $request['fb_scope']))
	));
}

/*
function checkFacebookAuthorization() {
	global $Application, $User, $Response;

	$smartKey = $User->apigeeSmartKey($Application->ID());

	if($smartKey) {
		Facebook::$smartKey = $smartKey;

		$authorized = null; $User->Cache($Application->ID(), 'apigee_facebook_authorized');

		if($authorized) {
			return $authorized;
		} else {
			if(Facebook::isAuthorized()) {
				if($fbUser = Facebook::getMyProfile()) {
					if(isset($fbUser->id)) {
						$User->Cache($Application->ID(), 'apigee_facebook_authorized', $fbUser->id, 600);
						return $fbUser->id;
					}
				}
			}
		}

		$Response->Send(200, RESP_ERR, array(
			'error' => 'User has not authorized the associated Facebook application.',
			'response' => Facebook::getAuthorizationURL()
		));
	} else {
		$Response->Send(200, RESP_ERR, array(
			'error' => 'Unable to collect Apigee smartkey for user. Try again later.'
		));
	}
}
*/