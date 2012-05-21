<?php

// Start benchmark timer.
define('BENCHMARK', microtime(true));

// Ensure that libraries/modules are only loaded through safe means.
define('LOADED_SAFELY', true);

// The API version this installation supports.
define('API_VERSION', '1.1');

// Just a string representation of success or failure. The "status" JSON response.
define("RESP_ERR", false);
define("RESP_OK", true);

// Representations of the various request method types.
define('HTTP_METHOD_GET', 1);
define('HTTP_METHOD_POST', 2);
define('HTTP_METHOD_PUT', 3);
define('HTTP_METHOD_DELETE', 4);

// The request variable contains a breakdown of the incoming API call.
$request = array();
$struct = array();

require('./config.php');					// Import configuration.
require('./lib/class.respond.php');			// Response formatter.
require('./lib/class.apps.php');			// API Access Control
require('./lib/class.users.php');			// Users
require('./lib/class.security.php');		// Security
require('./lib/class.mysql.php');			// MySQL interaction.
require('./lib/class.mail.php');			// Email management.
//require('./lib/class.apigee.php');			// Apigee OAuth API, for Facebook and Twitter connectivity.
//require('./lib/class.plugins.php');		// Load any available plugins.

require('./lib/class.facebook.php');		// Facebook OAuth API

// Connect to our memcache server.
$cache = new Memcache;
@$cache->pconnect(CFG_MEMCACHED, 11211);
if ($cache === FALSE)
{
	$Response->Send(503, RESP_ERR, array(
		   'error' => 'Service is currently unavailable. (Memcache)'
	));
}

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
elseif ( $_SERVER['REQUEST_METHOD'] == 'PUT' )
{
	define('HTTP_METHOD', 'PUT');

	// WARNING: DELETE and PUT requests must be x-www-form-urlencoded!
	parse_str(file_get_contents("php://input"), $request);

	if(isset($request['method'])) {
		define('API_METHOD', $request['method']);
		unset($request['method']);
	}
}
elseif ( $_SERVER['REQUEST_METHOD'] == 'DELETE' )
{
	define('HTTP_METHOD', 'DELETE');

	// WARNING: DELETE and PUT requests must be x-www-form-urlencoded!
	parse_str(file_get_contents("php://input"), $request);

	if(isset($request['method'])) {
		define('API_METHOD', $request['method']);
		unset($request['method']);
	}
}
else
{
	// Request method unsupported.
	$Response->Send(501, RESP_ERR, array(
		'error' => 'Unsupported request method used. GET and POST, PUT and DELETE are supported.'
	));
}

// Use the request path as the method. (Preferred Method)
if(!isset($request['method']) && isset($_SERVER['REQUEST_URI']))
{
	$s = substr($_SERVER['REQUEST_URI'], 1);

	if (substr($s, 0, 4) == 'api/')
		$s = substr($s, 4);

	if (strpos($s, '?'))
		$s = substr($s, 0, strrpos($s, '?'));

	if (substr($s, -1) != '/')
		$s .= '/';

	while(strrpos($s, '//'))
		$s = str_replace('//', '/', $s);

	$s = substr($s, 0, strrpos($s, '/'));

	if (substr($s, 0, 1) == 'v' && is_numeric(substr($s, 1, 1))) {
		$request['api_version'] = substr($s, 1, strpos($s, '/') - 1);
		$s = substr($s, strlen($request['api_version']) + 2);
	}

	if($s)
		$struct = trim($s);
		if(strpos($struct, '/')) {
			$struct = explode('/', $struct);
			$s = $struct[0];
		} else {
			$struct = array($struct);
		}
		define('API_METHOD', $s);

	unset($s);
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

		if(isset($request['api_version']) && $request['api_version'] == '2') {
			// Targeting API v2.
			require('./lib/api.2-0.php');
		} else {
			// Default to the oldest version for now.
			require('./lib/api.1-0.php');
		}

	}
}

$Response->Send(400, RESP_ERR, array(
	'error' => 'No supported API methods were invoked.'
));

exit;

function validateString($string, $validation = FILTER_VALIDATE_EMAIL)
{
	if ( filter_var($string, $validation) )
	{
		return true;
	}

	return false;
}

function api_expectations($expected)
{
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

function array_keys_exist($array, $search)
{
	foreach($search as $s) {
		if(!isset($array[$s])) {
			return false;
		}
	}
}
