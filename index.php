<?php

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
require('./lib/class.users.php');			// Users
require('./lib/class.security.php');		// Security
//require('./lib/startup.php');				// Make sure everything is in order.
require('./lib/class.mysql.php');			// MySQL interaction.
//require('./lib/class.plugins.php');		// Load any available plugins.

if ( $_SERVER['REQUEST_METHOD'] == 'GET' )
{
	define('HTTP_METHOD', 'GET');
	$request = $_GET;

	if(isset($request['method'])) {
		define('API_METHOD', $request['method']);
		unset($request['method']);
	}
}
elseif ( $_SERVER['REQUEST_METHOD'] == 'POST' )
{
	define('HTTP_METHOD', 'POST');
	$request = $_POST;

	if(isset($request['method'])) {
		define('API_METHOD', $request['method']);
		unset($request['method']);
	}
}
else
{
	// Request method unsupported.
	$Respond(501, RESP_ERR, array(
		'error' => 'Unsupported request method used. GET and POST, PUT and DELETE are supported.'
	));
}

// Use the request path as the method. (Preferred Method)
if(!isset($request['method']) && isset($_SERVER['PATH_INFO'])) {
	if($_SERVER['PATH_INFO'] != 'index.php' && $_SERVER['PATH_INFO'] != '/') {
		define('API_METHOD', trim($_SERVER['PATH_INFO'], '/'));
	}
}

// Permit cross domain requests.
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Expose-Headers: X-Frame-Options, X-RateLimit-Limit, X-RateLimit-Remaining");
header("Access-Control-Allow-Credentials: true");

// No soup for you, hackers.
header("X-Frame-Options: deny");

if ( defined('API_METHOD') )
{
	if ( API_METHOD == 'about' )
	{
		// Provide some basic information about this installation.
		$Response->Send(200, RESP_OK, array('response' => array(
			'info_url'	=> CFG_URL,
			'name'		=> CFG_NAME,
			'version'	=> (string)API_VERSION
		)));
	}
	elseif ( API_METHOD == 'ping' )
	{
		// Repor that we're OK.
		$Response->Send(200, RESP_OK, array(
			'response' => 'OK'
		));
	}
	elseif ( API_METHOD == 'limit' )
	{
		// Get an application's current hit cap and remaining hits. (This call does not count against an app's cap.)
		@$Application->Set($request['api_secret'], true);
		$Response->Send(200, RESP_OK, array('response' => array(
			'limit'				=> (int)$Application->rateLimit(), // Current hit cap.
			'remaining'			=> (int)$Application->rateRemaining(), // Hits remaining until cap.
			'next_expiration'	=> (int)$Application->rateNextExpiration() // How many seconds until the oldest registered hit is set to expire.
		)));
	}
	else
	{
		// Ensure the requesting application is registered, isn't over their hit limit, etc. and register this api hit against their limit.
		@$Application->Set($request['api_secret']);

		if(isset($request['api_version'])) {
			if($request['api_version'] == '1.1') {
				require('./lib/api.1-1.php');
			}
		} else {
			// Default to the oldest possible version.
			require('./lib/api.1-0.php');
		}

	}
}

$Response->Send(400, RESP_ERR, array(
	'error' => 'No supported API methods were invoked.'
));

exit;

function api_expectations($expected) {
	global $request, $Response;

	foreach($expected as $e)
	{
		if ( !isset($request[$e]) )
		{
			$Response->Send(400, RESP_ERR, array(
				'error' => 'JSON parameter missing. Expected: ' . implode(',', $expected)
			));
		}
	}
}

function array_keys_exist($array, $search) {
	foreach($search as $s) {
		if(!isset($array[$s])) {
			return false;
		}
	}
}
