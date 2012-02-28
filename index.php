<?php

	/*
		RIVERID-PHP
	*/

	// Start benchmark timer.
	define('BENCHMARK', microtime());

	// Ensure that libraries/modules are only loaded through safe means.
	define('LOADED_SAFELY', true);

	// The API version this installation supports.
	define('API_VERSION', '1.1');

	// Just a string representation of success or failure. The "status" JSON response.
	define("RESP_ERR", "false");
	define("RESP_OK", "true");

	// The request variable contains a breakdown of the incoming API call.
	$request = array();

	require('./config.php');					// Import configuration.
	require('./lib/class.respond.php');			// Response formatter.
	require('./lib/class.apps.php');			// API Access Control
	//require('./lib/class.sites.php');			// Sites
	//require('./lib/class.user.php');			// Users
	//require('./lib/class.security.php');		// Security
	//require('./lib/class.validation.php');		// Validation
	//require('./lib/startup.php');				// Make sure everything is in order.
	require('./lib/class.mysql.php');			// MySQL interaction.
	//require('./lib/class.plugins.php');			// Load any available plugins.

	if ( $_SERVER['REQUEST_METHOD'] == 'GET' )
	{
		// GET calls retrieve entries in a read-only state. Calls will never modify data.
		define('HTTP_METHOD', 'GET');
		$request = $_GET;

		if(isset($request['method'])) {
			define('API_METHOD', $request['method']);
			unset($request['method']);
		}
	}
	elseif ( $_SERVER['REQUEST_METHOD'] == 'POST' )
	{
		// POST calls create new entries. These always required authorization.
		define('HTTP_METHOD', 'POST');
		$request = json_decode(http_get_request_body(), true);
	}
	elseif ( $_SERVER['REQUEST_METHOD'] == 'PUT' )
	{
		// PUT calls update existing entries. These always required authorization.
		define('HTTP_METHOD', 'PUT');
	}
	elseif ( $_SERVER['REQUEST_METHOD'] == 'DELETE' )
	{
		// DELETE calls delete existing entries. These always required authorization.
		define('HTTP_METHOD', 'DELETE');
	}
	else
	{
		// Request method unsupported.
		$Respond(501, RESP_ERR, array(
			'error' => 'Unsupported request method used. GET and POST, PUT and DELETE are supported.'
		));
	}

	// Permit cross domain requests.
	header("Access-Control-Allow-Origin: *");
	header("Access-Control-Allow-Headers: Authorization, X-Requested-With");
	header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
	header("Access-Control-Expose-Headers: X-RateLimit-Limit, X-RateLimit-Remaining");
	header("Access-Control-Allow-Credentials: true");

	if ( defined('API_METHOD') )
	{
		if ( API_METHOD == 'about' )
		{
			$Response->Send(200, RESP_OK, array(
				'info_url' => CFG_URL,
				    'name' => CFG_NAME,
				 'version' => API_VERSION,
			));
		}
		elseif ( API_METHOD == 'ping' )
		{
			$Response->Send(200, RESP_OK, array(
				'response' => "OK"
			));
		}
		elseif ( API_METHOD == 'ratelimit' )
		{
			// Get an application's current hit cap and remaining hits.
			// This call does not count against an app's cap.
			@$Application->Validate($request['api_secret'], true);
			$Response->Send(200, RESP_OK, array(
				   'limit' => $Application->data['ratelimit'],
			   'remaining' => ($Application->data['ratelimit'] - $Application->hits)
			));
		}
		else
		{
			// Ensure the requesting application is registered, isn't over their hit limit, etc. and register this api hit against their limit.
			@$Application->Validate($request['api_secret']);

			if ( ( API_METHOD == 'addusertosite' || API_METHOD == 'add_user_to_site' ) )
			{
				api_expectations(array('email', 'session_id', 'url'));
			}
			elseif ( ( API_METHOD == 'changeemail' || API_METHOD == 'change_email' ) )
			{
				api_expectations(array('oldemail', 'newemail', 'password', 'mailbody', 'subject'));
			}
			elseif ( ( API_METHOD == 'changepassword' || API_METHOD == 'change_password' ) )
			{
				api_expectations(array('email', 'oldpassword', 'newpassword'));
			}
			elseif ( ( API_METHOD == 'checkpassword' || API_METHOD == 'check_password' ) )
			{
				api_expectations(array('email', 'password'));
			}
			elseif ( ( API_METHOD == 'confirmemail' || API_METHOD == 'confirm_email' ) )
			{
				api_expectations(array('email', 'token'));
			}
			elseif ( API_METHOD == 'register' )
			{
				api_expectations(array('email', 'password'));
			}
			elseif ( API_METHOD == 'registered' )
			{
				api_expectations(array('email'));
			}
			elseif ( ( API_METHOD == 'requestpassword' || API_METHOD == 'request_password' ) )
			{
				api_expectations(array('email', 'mailbody'));
			}
			elseif ( API_METHOD == 'sessions' )
			{
				api_expectations(array('email', 'session_id'));
			}
			elseif ( ( API_METHOD == 'setpassword' || API_METHOD == 'set_password' ) )
			{
				api_expectations(array('email', 'token', 'password'));
			}
			elseif ( API_METHOD == 'signedin' )
			{
				$Response->Send(200, RESP_OK, array(
					   'herp' => 'derp'
				));
			}
			elseif ( API_METHOD == 'signin' )
			{
				api_expectations(array('email', 'password'));
			}
			elseif ( API_METHOD == 'signout' )
			{
				api_expectations(array('email', 'session_id'));
			}
			elseif ( ( API_METHOD == 'usersites' || API_METHOD == 'user_sites' ) )
			{
				api_expectations(array('email', 'session_id'));
			}








			// The following methods are not yet supported, but are planned:

			elseif ( API_METHOD == 'two_factor_register' )
			{
				// Register a two factor authentication method (i.e. YubiKey, Google Authenticator, etc.)
			}
			elseif ( API_METHOD == 'two_factor_unregister' )
			{
				// Remove a two factor authentication method
			}
			elseif ( API_METHOD == 'badges' )
			{
				// List badges a user has earned.
			}
			elseif ( API_METHOD == 'badge_grant' )
			{
				// Grant a badge to a user.
			}
			elseif ( API_METHOD == 'badge_revoke' )
			{
				// Revoke a player's badge.
			}
			elseif ( API_METHOD == 'profile' )
			{
				// Get a user's public profile.
			}
			elseif ( API_METHOD == 'leaders' )
			{
				/*
				Get a sorted list of the community leaders. "Leadership" is calculated
				by the number of sites the user is involved with, the number of incidents
				submitted, accepted and verified, and the number of badges the player has
				earned.
				*/
			}
		}
	}

	$Response->Send(400, RESP_ERR, array(
		'error' => 'No supported API methods were invoked.'
	));

	exit;

	function api_expectations($expected) {
		global $request, $Response;

		if ( ! array_keys_exist($request, $expected) )
		{
			$Response->Send(400, RESP_ERR, array(
				'error' => 'JSON parameter missing. Expected: ' . implode(',', $expected)
			));
		}
	}

	function array_keys_exist($array, $search) {
		foreach($search as $s) {
			if(!isset($array[$s])) {
				return false;
			}
		}
	}
