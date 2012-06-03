<?php

	$api = curl_init();

	define("API_ENDPOINT", 'http://crowdmapid.local/');
	define('API_KEY', 'F2C7CC1EF7A73C948504BB43F324050869DB5AAED0C923B2548EE3A2AA985E7E');

	define('EMAIL_PRIMARY', 'automated@unit.test');
	define('PASSWORD_PRIMARY', 'password1');

	define('EMAIL_SECONDARY', 'automated2@unit.test');
	define('PASSWORD_SECONDARY', 'password2');

	$session = array();
	$errors = array();
	$log = array();

	$methods = array(
		'about' => array(),
		'ping' => array(),
		'limit' => array(),

		'register' => array('email' => EMAIL_PRIMARY, 'password' => PASSWORD_PRIMARY),
		'registered' => array('email' => EMAIL_PRIMARY),

		'signin' => array('email' => EMAIL_PRIMARY, 'password' => PASSWORD_PRIMARY),
		'signedin' => array('user_id' => '%user_id%', 'session_id' => '%session_id%'),
		'sessions' => array('email' => EMAIL_PRIMARY, 'session_id' => '%session_id%'),

		'storage_put' => array('email' => EMAIL_PRIMARY, 'session_id' => '%session_id%', 'key' => 'unit_test', 'value' => 'yes'),
		'storage_get' => array('email' => EMAIL_PRIMARY, 'session_id' => '%session_id%', 'key' => 'unit_test'),

		'addusertosite' => array('email' => EMAIL_PRIMARY, 'session_id' => '%session_id%', 'url' => 'http://test'),
		'usersites' => array('email' => EMAIL_PRIMARY, 'session_id' => '%session_id%'),
		'removeuserfromsite' => array('email' => EMAIL_PRIMARY, 'session_id' => '%session_id%', 'url' => 'http://test'),

		'changeemail' => array('oldemail' => EMAIL_PRIMARY, 'newemail' => EMAIL_SECONDARY, 'password' => PASSWORD_PRIMARY, 'subject' => '[RiverID API v1.0 Test] - /changeemail successful.', 'mailbody' => 'You are receiving this email because someone has initiated an API test. Had this been a real email change request, you would use %token%.'),

		//'changepassword' => array('email' => EMAIL_PRIMARY, 'oldpassword' => PASSWORD_PRIMARY, 'newpassword' => PASSWORD_SECONDARY),
		'checkpassword' => array('email' => EMAIL_PRIMARY, 'password' => PASSWORD_PRIMARY),

		'signout' => array('email' => EMAIL_PRIMARY, 'session_id' => '%session_id%'),
		'deleteuser' => array('email' => EMAIL_PRIMARY, 'password' => PASSWORD_PRIMARY)
	);

	function APICall($method, $params = null)
	{
		global $session, $api;

		$params = array_merge(array(
			'api_secret' => API_KEY
		), $params);

		foreach($params as $key => $val) {
			if(strpos($val, '%') !== false) {
				foreach($session as $skey => $sval) {
					$val = @str_replace("%{$skey}%", $sval, $val);
				}
				$params[$key] = $val;
			}
		}

		curl_setopt($api, CURLOPT_URL, API_ENDPOINT . $method);

		if ($api)
		{
			curl_setopt($api, CURLOPT_POST, true);
			curl_setopt($api, CURLOPT_POSTFIELDS, $params);

			curl_setopt($api, CURLOPT_TIMEOUT, 3);

			curl_setopt($api, CURLOPT_HEADER, false);
			curl_setopt($api, CURLOPT_RETURNTRANSFER, true);

			$raw = curl_exec($api);
			if($resp = json_decode($raw)) {
				if(isset($resp->success)) {
					if($resp->success) {
						if($method == 'register') {
							$session['user_id'] = $resp->response;
						} elseif($method == 'signin') {
							$session['user_id'] = $resp->response->user_id;
							$session['session_id'] = $resp->response->session_id;
						}
					}
				}
			} else {
				$resp = (object)array(
					'success' => false,
					'error' => $raw
				);
			}

			return $resp;
		}

		die("APICall() failed. Couldn't open connection.");
	}

	function bool2string($boolean)
	{
		if($boolean === TRUE) return 'TRUE';
		return 'FALSE';
	}

?><html>

<head>
	<title>CrowdmapID API Test</title>

	<style type="text/css">

	body, html {
		margin: 0; padding: 0;
		font: 14px/16px arial, helvetica, sans-serif;
		background: #fff; color: #000;
		text-align: center;
	}

	h1 {
		margin: 50px 0;
	}

	#breakdown {
		margin: 50px auto;
		width: 650px;
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

	</style>
</head>

<body>

<h1>CrowdmapID API v1.0 Test</h1>

<table id="breakdown">
<thead>
	<tr>
		<th>Call</th>
		<th class="centered">Expected</th>
		<th class="centered">Received</th>
		<th class="centered">Time</th>
	</tr>
</thead>
<tbody>

<?php

$slowest = 0;
$slowestMethod = '';

foreach ($methods as $method => $params):
	$ret = APICall($method, $params);
	$success = 'TRUE';
	$benchmark = 'N/A';

	if (isset($ret->benchmark)) {
		if ($ret->benchmark < $slowest) {
			$slowest = $ret->benchmark;
			$slowestMethod = $method;
		}
		$benchmark = $ret->benchmark;
	}

	$color = 'background-color: green';
	if ( ! isset($ret->success) OR ! $ret->success) {
		$color = 'background-color: red';
		$success = "<a href=\"#error-{$method}\">FALSE</a>";
		$errors[$method] = $ret;
	}

?>

	<tr style="<?php echo($color); ?>">
		<td><?php echo $method ?></td>
		<td class="centered">TRUE</td>
		<td class="centered"><?php echo $success ?></td>
		<td class="centered"><?php echo $benchmark; ?></td>
	</tr>

<?php endforeach; ?>

</tbody>
</table>

<?php foreach($errors as $method => $log): ?>
<div id="error-<?php echo($method); ?>" class="error-log">
	<h2>Unexpected Response from /<?php echo($method) ?></h2>
	<p><pre><?php echo $log->error ?></pre></p>
</div>
<?php endforeach; ?>

</body></html>
