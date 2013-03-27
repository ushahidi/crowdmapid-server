<?php

	define('LOADED_SAFELY', TRUE);
	define('API_SECRET', 'F2C7CC1EF7A73C948504BB43F324050869DB5AAED0C923B2548EE3A2AA985E7E');

	require('../config.php');
	require('../lib/class.mysql.php');

	$mongo = new Mongo("mongodb://50.56.193.4", array("persist" => "x")) or die("Connection to MongoDB failed.");
	$db = $mongo->riverid;
	$users = new MongoCollection($db, 'user');

	$userCount = 0;
	$api = curl_init();

	$cursor = $users->find();
	foreach ($cursor as $user) {
		if( ! isset($user['password']) OR ! strlen($user['password'])) continue;

		$apiResponse = apiCall("/user/{$user['id']}");

		if ( ! $apiResponse->success) {
			$apiResponse = apiCall("/user/", array(
				'hash' => $user['id'],
				'email' => $user['email'],
				'password' => $user['password']
			));

			if($apiResponse->success) {
				//echo "({$user['email']}) imported successfully.<br />";
				echo "{$user['email']}\n";
			} else {
				//echo "<span style='color: red; font-weight: bold'>ERROR: Import of {$user['email']} failed.</span><br />";
				//echo '<pre>' . print_r($apiResponse, true) . '</pre><br />';
				echo "\n\n" . print_r($apiResponse, true);
				exit;
			}

		} else {
			//echo "<span style='color: red; font-weight: bold'>({$user['email']}) already exists.</span><br />";
		}

		$userCount++;
	}

	echo "<p>{$userCount} RiverID users.</p>";









	function apiCall($method, $params = array()) {

		global $api;

		if ($api)
		{

			curl_setopt($api, CURLOPT_FORBID_REUSE, FALSE);
			curl_setopt($api, CURLOPT_POSTFIELDS, NULL);

			curl_setopt($api, CURLOPT_POST, true);
			curl_setopt($api, CURLOPT_POSTFIELDS, array_merge(array('api_secret' => API_SECRET), $params));

			curl_setopt($api, CURLOPT_TIMEOUT, 10);

			curl_setopt($api, CURLOPT_HEADER, false);
			curl_setopt($api, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($api, CURLOPT_SSL_VERIFYPEER, false);

			curl_setopt($api, CURLOPT_URL, "http://crowdmapid.local/v2{$method}");

			$raw = curl_exec($api);
			$resp = json_decode($raw);
			$http_status = (int)curl_getinfo($api, CURLINFO_HTTP_CODE);

			return $resp;
		}
	}
