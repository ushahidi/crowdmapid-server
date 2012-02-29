<?php

if(!defined('LOADED_SAFELY')) die('You cannot access this file directly.');

$http_status_codes = array(

	/*
	100 => 'Continue',
	101 => 'Switching Protocols',
	102 => 'Processing',
	103 => 'Checkpoint',
	122 => 'Request-URI too long',
	*/

	200 => 'OK',
	201 => 'Created',
	202 => 'Accepted',
	203 => 'Non-Authoritative Information',
	204 => 'No Content',
	205 => 'Reset Content',
	206 => 'Partial Content',
	207 => 'Multi-Status',
	208 => 'Already Reported',
	226 => 'IM Used',

	/*
	300 => 'Multiple Choices',
	301 => 'Moved Permanently',
	302 => 'Found',
	303 => 'See Other',
	304 => 'Not Modified',
	305 => 'Use Proxy',
	306 => 'Switch Proxy',
	307 => 'Temporary Redirect',
	308 => 'Resume Incomplete',
	*/

	400 => 'Bad Request',
	401 => 'Unauthorized',
	402 => 'Payment Required',
	403 => 'Forbidden',
	404 => 'Not Found',
	405 => 'Method Not Allowed',
	406 => 'Not Acceptable',
	407 => 'Proxy Authentication Required',
	408 => 'Request Timeout',
	409 => 'Conflict',
	410 => 'Gone',
	411 => 'Length Required',
	412 => 'Precondition Failed',
	413 => 'Request Entity Too Large',
	414 => 'Request-URI Too Long',
	415 => 'Unsupported Media Type',
	416 => 'Requested Range Not Satisfiable',
	417 => 'Expectation Failed',
	418 => 'I\'m a teapot',
	420 => 'Enhance Your Calm',
	422 => 'Unprocessable Entity',
	423 => 'Locked',
	424 => 'Failed Dependency',
	425 => 'Unordered Collection',
	426 => 'Upgrade Required',
	428 => 'Precondition Required',
	429 => 'Too Many Requests',
	431 => 'Request Header Fields Too Large',
	444 => 'No Response',
	449 => 'Retry With',
	450 => 'Blocked by Windows Parental Controls',
	499 => 'Client Closed Request',

	500 => 'Internal Server Error',
	501 => 'Not Implemented',
	502 => 'Bad Gateway',
	503 => 'Service Unavailable',
	504 => 'Gateway Timeout',
	505 => 'HTTP Version Not Supported',
	506 => 'Variant Also Negotiates',
	507 => 'Insufficient Storage',
	508 => 'Loop Detected',
	509 => 'Bandwidth Limit Exceeded',
	510 => 'Not Extended',
	511 => 'Network Authentication Required',
	598 => 'Network read timeout error',
	599 => 'Network connect timeout error'
);

class Response {

	function Send($code, $success, $data) {
		global $Application, $http_status_codes, $request;

		if(isset($http_status_codes[$code])) {
			$status = $http_status_codes[$code];
		} else {
			$status = $http_status_codes[500];
		}

		header("HTTP/1.1 {$code} {$status}");
		header('Content-Type: text/javascript');

		$resp = array();
		$resp['status'] = (string)$success;

		if(defined('API_METHOD')) {
			$resp['method'] = API_METHOD;
		}

		$callback = null;
		if(isset($request)) {
			$resp['request'] = $request;
			if ( isset($resp['request']['api_secret']) )
			{
				unset($resp['request']['api_secret']);
			}

			if ( isset($resp['request']['callback']) )
			{
				$callback = $resp['request']['callback'];
				unset($resp['request']['callback']);
			}
		}

		$resp = array_merge($resp, $data);

		if(defined('BENCHMARK')) {
			$resp['benchmark'] = round((microtime() - BENCHMARK), 4);
		}

		$resp = json_encode($resp);
		if($callback) {
			$resp = "{$callback}({$resp})";
		}

		header('Content-Length: ' . strlen($resp));

		echo $resp;

		exit();
	}

}

$Response = new Response();
