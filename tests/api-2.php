<?php

	$api = curl_init();

	define("API_ENDPOINT", 'http://crowdmapid.local');
	define('API_KEY', 'F2C7CC1EF7A73C948504BB43F324050869DB5AAED0C923B2548EE3A2AA985E7E');

	define('EMAIL_PRIMARY', 'automated@unit.test');
	define('PASSWORD_PRIMARY', 'password1');

	define('EMAIL_SECONDARY', 'automated2@unit.test');
	define('PASSWORD_SECONDARY', 'password2');

	$session = array();
	$errors = array();
	$log = array();
	$repeat = (isset($_GET['repeat']) ? (int)$_GET['repeat'] : 1);

	$methods = array(

		array(
			'url' => '/about',
			'description' => 'Get information about the endpoint, such as API version.',
			'method' => 'GET',
			'parameters' => array()
			),
		array(
			'url' => '/ping',
			'description' => 'Determine if the endpoint is available.',
			'method' => 'GET',
			'parameters' => array()
			),
		array(
			'url' => '/limit',
			'description' => 'Determine the API hit limits of this application (based on the API key provided).',
			'method' => 'GET',
			'parameters' => array()
			),

		// List users.
		array(
			'url' => '/user',
			'description' => 'Get a list of users registered with the server. The application must have administrative access.',
			'method' => 'GET',
			'parameters' => array()
			),

		// Register the user.
		array(
			'url' => '/user',
			'description' => 'Register a user.',
			'method' => 'POST',
			'parameters' => array('email' => EMAIL_PRIMARY, 'password' => PASSWORD_PRIMARY)
			),
		// Get user details.
		array(
			'url' => '/user/:user_id',
			'description' => 'Get details about a user.',
			'method' => 'GET',
			'parameters' => array()
			),

		// Change user password.
		array(
			'url' => '/user/:user_id/password',
			'description' => 'Change the user password.',
			'method' => 'POST',
			'parameters' => array('password' => PASSWORD_SECONDARY)
			),
		// Validate the user password.
		array(
			'url' => '/user/:user_id/password',
			'description' => 'Compare the user password.',
			'method' => 'GET',
			'parameters' => array('password' => PASSWORD_SECONDARY)
			),

		// Register a new email address with the user.
		array(
			'url' => '/user/:user_id/emails',
			'description' => 'Add an email address to the user.',
			'method' => 'POST',
			'parameters' => array('email' => EMAIL_SECONDARY)
			),
		// Get email addresses registered with the user account.
		array(
			'url' => '/user/:user_id/emails',
			'description' => 'Get a list of email addresses registered to the user.',
			'method' => 'GET',
			'parameters' => array()
			),
		// Check if an email address is registered to an account.
		array(
			'url' => '/user/:user_id/emails/:example',
			'description' => 'Check if an email address is registered to an account.',
			'method' => 'GET',
			'parameters' => array('example' => EMAIL_PRIMARY)
			),
		// Make the specified email address the new primary address for the account.
		array(
			'url' => '/user/:user_id/emails/:example',
			'description' => 'Make the specified email address the new primary address for the account.',
			'method' => 'PUT',
			'parameters' => array('example' => EMAIL_SECONDARY, 'primary' => '1')
			),
		// Delete an email address associated with the account.
		array(
			'url' => '/user/:user_id/emails/:example',
			'description' => 'Delete an email address associated with the account.',
			'method' => 'DELETE',
			'parameters' => array('example' => EMAIL_PRIMARY)
			),

		// Store something.
		array(
			'url' => '/user/:user_id/store/:example',
			'description' => 'Store something in the user key-value storage.',
			'method' => 'PUT',
			'parameters' => array('value' => 'Excalibur!')
			),
		// Get something we've stored.
		array(
			'url' => '/user/:user_id/store/:example',
			'description' => 'Fetch something in the user key-value storage.',
			'method' => 'GET',
			'parameters' => array()
			),
		// Remove something we've stored.
		array(
			'url' => '/user/:user_id/store/:example',
			'description' => 'Remove something from the user key-value storage.',
			'method' => 'DELETE',
			'parameters' => array()
			),

		// Get user badges.
		array(
			'url' => '/user/:user_id/badges',
			'description' => 'Get a list of this users badges.',
			'method' => 'GET',
			'parameters' => array('all' => 'true')
			),
		// Award a user badge.
		array(
			'url' => '/user/:user_id/badges',
			'description' => 'Award a badge to a user.',
			'method' => 'POST',
			'parameters' => array(
					'badge'       => 'example_badge',
					'title'       => 'Example Badge',
					'description' => 'This is an example badge.',
					'graphic'     => 'http://www.gravatar.com/avatar/1ad51d608b61a8659bf07634cb6a38d5',
					'url'         => 'http://www.ushahidi.com',
					'category'    => 'Miscellaneous'
				)
			),
		// Get a specific user badge.
		array(
			'url' => '/user/:user_id/badges/:example',
			'description' => 'Get information about a user badge.',
			'method' => 'GET',
			'parameters' => array('example' => 'example_badge')
			),
		// Update a user badge.
		array(
			'url' => '/user/:user_id/badges/:example',
			'description' => 'Update a badge a user already has.',
			'method' => 'PUT',
			'parameters' => array(
					'example'     => 'example_badge',
					'badge'       => 'example',
					'title'       => 'Updated Example Badge',
					'description' => 'This is an updated example badge.',
					'graphic'     => 'http://www.gravatar.com/avatar/1ad51d608b61a8659bf07634cb6a38d5',
					'url'         => 'http://www.ushahidi.com',
					'category'    => 'Miscellaneous'
				)
			),
		// Revoke a user badge.
		array(
			'url' => '/user/:user_id/badges/:example',
			'description' => 'Revoke a badge a user has.',
			'method' => 'DELETE',
			'parameters' => array('example' => 'example_badge')
			),

		// Get the user session.
		array(
			'url' => '/user/:user_id/sessions',
			'description' => 'Get the current user session.',
			'method' => 'GET',
			'parameters' => array()
			),
		// Check if a specified user session is valid.
		array(
			'url' => '/user/:user_id/sessions/:session_id',
			'description' => 'Check if a specific user session is valid.',
			'method' => 'GET',
			'parameters' => array()
			),
		// Put the user session.
		array(
			'url' => '/user/:user_id/sessions',
			'description' => 'Refresh the current user session.',
			'method' => 'PUT',
			'parameters' => array()
			),
		// Generate a fresh session token.
		array(
			'url' => '/user/:user_id/sessions',
			'description' => 'Generate a new session token.',
			'method' => 'POST',
			'parameters' => array()
			),

		// Get the user avatar.
		array(
			'url' => '/user/:user_id/avatar',
			'description' => 'Get the current user avatar.',
			'method' => 'GET',
			'parameters' => array()
			),
		// Set the user avatar.
		array(
			'url' => '/user/:user_id/avatar',
			'description' => 'Get the current user avatar.',
			'method' => 'POST',
			'parameters' => array('avatar' => 'http://ushahidi.com/-/images/ushahidi-logo_small.png')
			),

		// Delete user.
		array(
			'url' => '/user/:user_id',
			'description' => 'Delete a user.',
			'method' => 'DELETE',
			'parameters' => array()
			)

	);

	function json_stylize($json) {

		$result      = '';
		$pos         = 0;
		$strLen      = strlen($json);
		$indentStr   = '  ';
		$newLine     = "\n";
		$prevChar    = '';
		$outOfQuotes = true;

		for ($i=0; $i<=$strLen; $i++) {

			$char = substr($json, $i, 1);

			if ($char == '"' && $prevChar != '\\') {
				$outOfQuotes = !$outOfQuotes;
			} else if(($char == '}' || $char == ']') && $outOfQuotes) {
				$result .= $newLine;
				$pos --;
				for ($j=0; $j<$pos; $j++) {
					$result .= $indentStr;
				}
			}

			$result .= $char;

			if (($char == ',' || $char == '{' || $char == '[') && $outOfQuotes) {
				$result .= $newLine;
				if ($char == '{' || $char == '[') {
					$pos ++;
				}

				for ($j = 0; $j < $pos; $j++) {
					$result .= $indentStr;
				}
			}

			$prevChar = $char;
		}

		return $result;
	}

	function APICall($url, $method, $params = null)
	{
		global $session;

		// global $api;
		// curl_setopt($api, CURLOPT_FORBID_REUSE, FALSE);

		$api = curl_init();
		if ($api)
		{
			$params = array_merge(array(
				'api_secret' => API_KEY,
				'api_version' => '2'
			), $params);

			if(strpos($url, ':') !== false) {
				foreach($session as $skey => $sval) {
					$url = @str_replace(":{$skey}", $sval, $url);
				}
			}

			if(strpos($url, ':example') !== false && isset($params['example'])) {
				$url = @str_replace(":example", $params['example'], $url);
				unset($params['example']);
			}

			foreach($params as $key => $val) {
				if(strpos($val, '%') !== false) {
					foreach($session as $skey => $sval) {
						$val = @str_replace("%{$skey}%", $sval, $val);
					}
					$params[$key] = $val;
				}
			}

			if($method == 'GET') {
				curl_setopt($api, CURLOPT_POST, false);
				curl_setopt($api, CURLOPT_POSTFIELDS, NULL);
				curl_setopt($api, CURLOPT_HTTPGET, true);

				if(count($params)) {
					$url .= '?';
					foreach($params as $p => $v) $url .= $p . '=' . urlencode($v) . '&';
					$url = rtrim($url, '&');
				}
			} elseif($method == 'POST') {
				curl_setopt($api, CURLOPT_POST, true);
				curl_setopt($api, CURLOPT_POSTFIELDS, $params);
			} elseif($method == 'PUT') {
				curl_setopt($api, CURLOPT_POST, false);
				curl_setopt($api, CURLOPT_POSTFIELDS, NULL);
				curl_setopt($api, CURLOPT_CUSTOMREQUEST, "PUT");

				if(count($params)) {
					$url .= '?';
					foreach($params as $p => $v) $url .= $p . '=' . urlencode($v) . '&';
					$url = rtrim($url, '&');
				}
			} elseif($method == 'DELETE') {
				curl_setopt($api, CURLOPT_POST, false);
				curl_setopt($api, CURLOPT_POSTFIELDS, NULL);
				curl_setopt($api, CURLOPT_CUSTOMREQUEST, "DELETE");

				if(count($params)) {
					$url .= '?';
					foreach($params as $p => $v) $url .= $p . '=' . urlencode($v) . '&';
					$url = rtrim($url, '&');
				}
			}

			curl_setopt($api, CURLOPT_URL, API_ENDPOINT . $url);
			//echo API_ENDPOINT . $url . '<br />'; // . print_r($params, true) . '<br />';

			curl_setopt($api, CURLOPT_TIMEOUT, 3);

			curl_setopt($api, CURLOPT_HEADER, false);
			curl_setopt($api, CURLOPT_RETURNTRANSFER, true);

			$raw = curl_exec($api);
			if($resp = json_decode($raw)) {
				if(isset($resp->success)) {
					if($resp->success) {
						if($url == '/user' && $method == 'POST') {
							$session['user_id'] = $resp->user_id;
							$session['session_id'] = $resp->session_id;

							if(!headers_sent()) {
								setcookie('unit_test_v2_user_id', $session['user_id']);
								setcookie('unit_test_v2_session_id', $session['session_id']);
							}
						}
					}
				}
			} else {
				$resp = (object)array(
					'success' => false,
					'error' => $raw
				);
			}

			$raw = json_stylize($raw);
			$raw = str_replace("\n", '<br />', addslashes($raw));

			$resp = (object)array_merge((array)$resp, array('raw' => $raw));
			return $resp;
		}

		die("APICall() failed. Couldn't open connection.");
	}

	function bool2string($boolean)
	{
		if($boolean === TRUE) return 'TRUE';
		return 'FALSE';
	}

	if(isset($_GET['step'])) {
		if(isset($_COOKIE['unit_test_v2_user_id'])) $session['user_id'] = $_COOKIE['unit_test_v2_user_id'];
		if(isset($_COOKIE['unit_test_v2_session_id'])) $session['session_id'] = $_COOKIE['unit_test_v2_session_id'];
	}

?><html>

<head>
	<title>CrowdmapID API Test</title>

	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js" type="text/javascript"></script>

	<style type="text/css">

	body, html {
		margin: 0; padding: 0;
		font: 14px/16px arial, helvetica, sans-serif;
		background: #fff; color: #000;
		text-align: center;
	}

	h1 {
		margin: 50px 0 20px 0;
	}

	h2 {
		margin: 40px auto 20px auto;
	}

	hr {
		margin: 10px auto;
		padding: 0;
		width: 750px; height: 1px;
		border: none;
		border-top: 1px solid rgba(0,0,0,0.25);
		background: none;
	}

	span.descriptive {
		border-bottom: 1px dashed rgba(255,255,255,0.5);
		cursor: help;
	}

	#breakdown {
		margin: 50px auto 10px auto;
		width: 750px;
		border-collapse: collapse;
		border-right: 1px solid #ccc;
		border-top: 1px solid #ccc;
		box-shadow: 0 2px 5px #f0f0f0;
		text-align: left;
		color: #fff;
	}

	#breakdown th,
	#breakdown td {
		padding: 5px;
		border-left: 1px solid #ccc;
		border-bottom: 1px solid #ccc;
		vertical-align: top;
	}

		#breakdown th.centered,
		#breakdown td.centered {
			text-align: center;
		}

		#breakdown th a,
		#breakdown td a {
			color: #fff;
		}

	#breakdown th {
		background: #f0f0f0;
		color: #000;
	}

	div.error-log {
		margin: 50px auto;
		width: 650px;
		text-align: left;
	}

		div.error-log h2 {
			margin: 0 0 1em 0;
			font-size: 14px;
			line-height: 16px;
			color: red;
			font-weight: bold;
		}

	#responses {
		display: none;
	}

		#responseData {
			margin: 0 auto;
			width: 750px;
			text-align: left;
			font-family: monospace;
			white-space: pre;
			font-size: 11px;
		}

	</style>

	<script type="text/javascript">

		var responseArray = new Array();

		function displayResponse(key) {
			if(!$("#responses:visible").length) {
				$("#responses").fadeIn("fast", function() {
					displayResponse(key);
				});
			} else {
				$("#responseData").html(responseArray[key]);
			}
		}

	</script>
</head>

<body>

<h1>CrowdmapID API v2.0 Test</h1>

<p>Running in <?php
	if(isset($_GET['step']) && is_numeric($_GET['step'])) {
		echo 'step-by-step mode. ';
		if($_GET['step'] < count($methods) - 1) {
			echo '<a href="./api-2.php?step=' . ($_GET['step'] + 1) . '">Proceed with next step ...</a>';
		} else {
			echo '<a href="./api-2.php?step=0">Restart from the beginning.</a>';
		}
		$methods = array($methods[(int)$_GET['step']]);
	} else {
		echo 'batch mode. <a href="/api-2.php?step=1">Switch to step-by-step mode.</a>';
	}
?></p>

<table id="breakdown">
<thead>
	<tr>
		<th></th>
		<th>Call</th>
		<th class="centered">Method</th>
		<th class="centered">Expected</th>
		<th class="centered">Received</th>
		<th class="centered">Time</th>
		<th class="centered">Response</th>
	</tr>
</thead>
<tbody>

<?php

$slowest = -1;
$slowestMethod = '';
$previousData = '';
$methodNumber = -1;
$benchmark = microtime(true);

for($r = 0; $r < $repeat; $r++):

foreach ($methods as $api_request):
	$methodNumber++;
	$ret = APICall($api_request['url'], $api_request['method'], $api_request['parameters']);
	$success = 'TRUE';
	$benchmark = 'N/A';

	if (isset($ret->benchmark)) {
		if ($ret->benchmark > $slowest) {
			$slowest = $ret->benchmark;
			$slowestMethod = $api_request['url'];
		}
		$benchmark = $ret->benchmark;
	}

	$color = 'background-color: green';
	if ( ! isset($ret->success) OR $ret->success !== TRUE) {
		$color = 'background-color: red';
		$success = "<a href=\"#error-{$api_request['url']}\">FALSE</a>";
		$errors[$api_request['url'] . ' (' . $api_request['method'] . ')'] = $ret;
	} else {
		$previousData = trim($ret->raw);
	}

	if(isset($_GET['step'])) $methodNumber = $_GET['step'];

?>

	<tr style="<?php echo($color); ?>">
		<td class="centered"><?php if($repeat == 1): ?><a href="./api-2.php?step=<?php echo($methodNumber); ?>"><?php echo($methodNumber); ?></a><?php endif; ?></td>
		<td><span class="descriptive" title="<?php echo $api_request['description']; ?>"><?php echo $api_request['url'] ?></span></td>
		<td class="centered"><?php echo $api_request['method']; ?></td>
		<td class="centered">TRUE</td>
		<td class="centered"><?php echo $success ?></td>
		<td class="centered"><?php echo $benchmark; ?></td>
		<td class="centered">
			<a href="#" onclick="displayResponse('<?php echo md5($api_request['url'] . $api_request['method']); ?>'); return false;">View</a>
			<script type="text/javascript">
			responseArray["<?php echo md5($api_request['url'] . $api_request['method']); ?>"] = "<?php echo trim($ret->raw); ?>";
			</script>
		</td>
	</tr>

<?php endforeach; ?>
<?php endfor; /* for($r = 0; $r < $repeat; $r++): */ ?>

</tbody>
</table>

<p>The slowest call was <?php echo $slowestMethod; ?> at <?php echo $slowest; ?>. Total process time was <?php echo round(microtime(false) - $benchmark, 4); ?></p>
<?php if($repeat > 1): ?><script type="text/javascript">alert("Total process time was <?php echo round(microtime(false) - $benchmark, 4); ?> seconds.");</script><?php endif; ?>

<div id="responses" <?php if(isset($_GET['step'])): ?>style="display: block !important"<?php endif; ?>>
	<h2>Response Data</h2>
	<hr />

	<div id="responseData"><?php if(isset($_GET['step'])) echo(stripslashes($previousData)); ?></div>
</div>

<?php foreach($errors as $url => $log): ?>
<h2>Errors</h2>
<hr />
<div id="error-<?php echo($url); ?>" class="error-log">
	<h2>Unexpected Response from <?php echo($url) ?></h2>
	<p><pre><?php echo stripslashes($log->raw) ?></pre></p>
</div>
<?php endforeach; ?>

</body></html>
