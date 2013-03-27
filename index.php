<?php

/**
 * CrowdmapID
 *
 * @package    CrowdmapID
 * @author     Ushahidi Team <team@ushahidi.com>
 * @copyright  Ushahidi - http://www.ushahidi.com
 * @license    http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License (LGPL)
 */

ob_start();

// Start benchmark timer.
define('BENCHMARK', microtime(true));
mt_srand(BENCHMARK);

// Generate a one time ID for this connection attempt.
if(isset($_REQUEST['req_uniq_id'])) {
	define('REQUEST_UNIQUE_ID', filter_var($_REQUEST['req_uniq_id'], FILTER_SANITIZE_STRING));
} else {
	define('REQUEST_UNIQUE_ID', uniqid("{$_SERVER['REMOTE_ADDR']}_", true));
}

// Ensure that libraries/modules are only loaded through safe means.
define('LOADED_SAFELY', true);

// The API version this installation supports.
define('API_VERSION', '1.1');

// Just a string representation of success or failure. The "status" JSON response.
define("RESP_ERR", false);
define("RESP_OK",   true);

// Representations of the various request method types.
define('HTTP_METHOD_GET',    1);
define('HTTP_METHOD_POST',   2);
define('HTTP_METHOD_PUT',    3);
define('HTTP_METHOD_DELETE', 4);

// The request variable contains a breakdown of the incoming API call.
$request = array();
$struct = array();

require('./config.php');             // Import configuration.
require('./lib/class.respond.php');  // Response formatter.
require('./lib/class.apps.php');     // API Access Control
require('./lib/class.users.php');    // Users
require('./lib/class.security.php'); // Security
require('./lib/class.mysql.php');    // MySQL interaction.
require('./lib/class.mail.php');     // Email management.
require('./lib/class.cache.php');    // Cache management.
require('./lib/class.plugins.php');  // Plugins.

Plugins::raiseEvent("core.startup");

if ($_SERVER['REQUEST_METHOD'] == 'GET')
{
	define('HTTP_METHOD', 'GET');
	$request = $_GET;

	if(isset($request['method'])) {
		define('API_METHOD', $request['method']);
		unset($request['method']);
	}
}
elseif ($_SERVER['REQUEST_METHOD'] == 'POST')
{
	define('HTTP_METHOD', 'POST');
	$request = $_POST;

	if(isset($request['method'])) {
		define('API_METHOD', $request['method']);
		unset($request['method']);
	}
}
elseif ($_SERVER['REQUEST_METHOD'] == 'PUT')
{
	define('HTTP_METHOD', 'PUT');

	// WARNING: DELETE and PUT requests must be x-www-form-urlencoded!
	parse_str(file_get_contents("php://input"), $request);
	$request = array_merge($_GET, $_POST, $request);

	if(isset($request['method'])) {
		define('API_METHOD', $request['method']);
		unset($request['method']);
	}
}
elseif ($_SERVER['REQUEST_METHOD'] == 'DELETE')
{
	define('HTTP_METHOD', 'DELETE');

	// WARNING: DELETE and PUT requests must be x-www-form-urlencoded!
	parse_str(file_get_contents("php://input"), $request);
	$request = array_merge($_GET, $_POST, $request);

	if(isset($request['method'])) {
		define('API_METHOD', $request['method']);
		unset($request['method']);
	}
}
else
{
	if( ! Plugins::raiseEvent("core.breakdown_request_method")) {
		// Request method unsupported.
		Response::Send(501, RESP_ERR, array(
			'error' => 'Unsupported request method used. GET, POST, PUT and DELETE are supported.'
		));
	}
}

// Use the request path as the method. (Preferred Method)
if( ! isset($request['method']) && isset($_SERVER['REQUEST_URI']))
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

	Plugins::raiseEvent("core.breakdown_request_path", $s);

	if ($s) $struct = trim($s);

	if (is_string($struct) && strpos($struct, '/')) {
		$struct = explode('/', $struct);
		$s = $struct[0];
	} else {
		$struct = array($struct);
	}

	define('API_METHOD', $s);

	unset($s);
}

Plugins::raiseEvent("core.preprocessing");

if (defined('API_METHOD'))
{
	if (API_METHOD == 'about')
	{
		$response = array(
			'info_url' => CFG_URL,
			'name'     => CFG_NAME,
			'version'  => (string)API_VERSION
		);
		Plugins::raiseEvent("method.public.about", $response);

		// Provide some basic information about this installation.
		Response::Send(200, RESP_OK, array('response' => $response));
	}
	elseif (API_METHOD == 'ping')
	{
		$response = 'OK';
		Plugins::raiseEvent("method.public.ping", $response);

		// Report that we're OK.
		Response::Send(200, RESP_OK, array(
			'response' => $response
		));
	}
	elseif (API_METHOD == 'limit')
	{
		// Get an application's current hit cap and remaining hits. (This call does not count against an app's cap.)
		@$Application->Set($request['api_secret'], true);

		$response = array(
			'limit'           => (int)$Application->rateLimit(), // Current hit cap.
			'remaining'       => (int)$Application->rateRemaining(), // Hits remaining until cap.
			'next_expiration' => (int)$Application->rateNextExpiration() // How many seconds until the oldest registered hit is set to expire.
		);
		Plugins::raiseEvent("method.private.limit", $response);

		Response::Send(200, RESP_OK, array('response' => $response));
	}
	else
	{
		// Ensure the requesting application is registered, isn't over their hit limit, etc. and register this api hit against their limit.
		@$Application->Set($request['api_secret']);

		if (isset($request['api_version']) && $request['api_version'] == '2') {
			// Targeting API v2.
			require('./lib/api.2-0.php');
		} elseif (HTTP_METHOD == 'GET' OR HTTP_METHOD == 'POST') {
			// Default to the oldest version for now.
			require('./lib/api.1-0.php');
		}

	}
}

if ( ! Plugins::raiseEvent("method.no_matches")) {
	Response::Send(404, RESP_ERR, array(
		'error' => 'No supported API methods were invoked.'
	));
}

exit;

function validateString($string, $validation = FILTER_VALIDATE_EMAIL)
{
	$temp = array($string, $validation);
	Plugins::raiseEvent("core.validatestring", $temp);
	$string = $temp[0];
	$validation = $temp[1];

	if (filter_var($string, $validation))
	{
		return true;
	}

	return false;
}

function api_expectations($expected)
{
	global $request;
	Plugins::raiseEvent("core.api_expectations", $expected);

	foreach($expected as $e)
	{
		if ( ! isset($request[$e]))
		{
			Response::Send(400, RESP_ERR, array(
				'error' => 'JSON parameter missing. Expected: ' . implode(',', $expected)
			));
		}
	}
}

function isSessionCleared($requested = null, $critical = false)
{
	global $Application, $request;

	   $user_id = (isset($request['user_id']) ? filter_var(substr($request['user_id'], 0, 128), FILTER_SANITIZE_STRING) : null);
	$session_id = (isset($request['session_id']) ? filter_var(substr($request['session_id'], 0, 64), FILTER_SANITIZE_STRING) : null);
	     $error = 'This call requires user authentication. You must provide a valid user_id and session_id.';

	if($user_id && $session_id) {
		$User = new User();
		if($User->Set($user_id)) {
			if($User->Session($Application->ID()) == $session_id) {
				if($User->Admin() || $user_id == $requested) {
					return true;
				} else {
					$error = 'The provided user_id or session_id is incorrect.';
				}
			} else {
				$error = 'The provided user_id or session_id is incorrect.';
			}
		} else {
			$error = 'The provided user_id or session_id is incorrect.';
		}
	}

	if($critical) {
		Response::Send(400, RESP_ERR, array(
			'error' => $error
		));
	} else {
		return false;
	}
}

function array_keys_exist($array, $search)
{
	Plugins::raiseEvent("core.array_keys_exist", array($array, $search));

	foreach($search as $s) {
		if( ! isset($array[$s])) {
			return false;
		}
	}
}
