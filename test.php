<?php

	define("API_ENDPOINT", 'http://10.0.1.9/');
	define('API_KEY', 'daa9dcce9f263bee648a7ba49f6b3f5d3415293127f697fca9e8c76a4b0775c9');

	define('EMAIL_PRIMARY', 'evansims@gmail.com');
	define('PASSWORD_PRIMARY', 'gibberish');

	define('EMAIL_SECONDARY', 'evan@ushahidi.com');
	define('PASSWORD_SECONDARY', 'gibberish2');

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

		'addusertosite' => array('email' => EMAIL_PRIMARY, 'session_id' => '%session_id%', 'url' => 'http://test'),
		'usersites' => array('email' => EMAIL_PRIMARY, 'session_id' => '%session_id%'),
		'removeuserfromsite' => array('email' => EMAIL_PRIMARY, 'session_id' => '%session_id%', 'url' => 'http://test'),

		'changeemail' => array('oldemail' => EMAIL_PRIMARY, 'newemail' => EMAIL_SECONDARY, 'password' => PASSWORD_PRIMARY, 'subject' => '[RiverID API v1.0 Test] - /changeemail successful.', 'mailbody' => 'You are receiving this email because someone has initiated an API test. Had this been a real email change request, you would use %token%.'),
		//'confirmemail' => array(),
		'changepassword' => array('email' => EMAIL_PRIMARY, 'oldpassword' => PASSWORD_PRIMARY, 'newpassword' => PASSWORD_SECONDARY),
		'checkpassword' => array('email' => EMAIL_PRIMARY, 'password' => PASSWORD_SECONDARY),

		//'requestpassword' => array(),
		//'setpassword' => array(),

		'signout' => array('email' => EMAIL_PRIMARY, 'session_id' => '%session_id%')
	);

	$reset = array(
		'changepassword' => array('email' => EMAIL_PRIMARY, 'oldpassword' => PASSWORD_SECONDARY, 'newpassword' => PASSWORD_PRIMARY),
	);

	function APICall($method, $params = null)
	{
		global $session;

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

		$api = curl_init(API_ENDPOINT . $method);

		if ( $api )
		{
			curl_setopt($api, CURLOPT_POST, true);
			curl_setopt($api, CURLOPT_POSTFIELDS, $params);

			curl_setopt($api, CURLOPT_TIMEOUT, 3);

			curl_setopt($api, CURLOPT_HEADER, false);
			curl_setopt($api, CURLOPT_RETURNTRANSFER, true);

			$raw = curl_exec($api);
			$resp = json_decode($raw);
			$http_status = (int)curl_getinfo($api, CURLINFO_HTTP_CODE);
			@curl_close($api);

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
			else
			{
				echo "Choked on $method w/ $http_status ...<br />";
				var_dump($raw);
				exit;
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
	<title>RiverID API Test</title>

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

<h1>RiverID API v1.0 Test</h1>

<table id="breakdown">
<thead>
	<tr>
		<th>Call</th>
		<th>Expected</th>
		<th>Received</th>
		<th>Time</th>
	</tr>
</thead>
<tbody>

<?php foreach($methods as $method => $params):
	$ret = APICall($method, $params);
	$success = 'TRUE';

	$color = 'background-color: green';
	if(!$ret->success) {
		$color = 'background-color: red';
		$success = '<a href="#error-' . $method . '">FALSE</a>';
		$errors[$method] = $ret;
	}
?>

	<tr style="<?php echo($color); ?>">
		<td><?php echo $method ?></td>
		<td>TRUE</td>
		<td><?php echo $success ?></td>
		<td><?php echo $ret->benchmark; ?></td>
	</tr>

<?php endforeach;

	foreach($reset as $method => $params) {
		$ret = APICall($method, $params);
	}
?>

</tbody>
</table>

<?php foreach($errors as $method => $log): ?>
<div id="error-<?php echo($method); ?>" class="error-log">
	<h2>Unexpected Response from /<?php echo($method) ?></h2>
	<p><pre><?php echo $log->error ?></pre></p>
</div>
<?php endforeach; ?>

</body></html>
