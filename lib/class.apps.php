<?php

if(!defined('LOADED_SAFELY')) die('You cannot access this file directly.');

// API Access Control

class Application {

	var $data = array();
	var $hits = 0;

	function Validate($key = '', $free = false)
	{
		global $MySQL, $Response;

		if ( strlen($key) )
		{
			$this->data = $MySQL->Pull('SELECT name,url,secret,ratelimit FROM applications WHERE secret="' . $MySQL->Clean($key) . '" LIMIT 1;');
			$this->data['ratelimit'] = (int)$this->data['ratelimit'];
		}

		// Invalid API key, or no API key was provided.
		if ( ! $this->data )
		{
			$Response->Send(401, RESP_ERR, array(
				'response' => 'Call requires a registered API key.'
			));
		}

		// Was this a free API call?
		if ( $free == false ) {
			// Check if we're dealing with a whitelisted application.
			if ( $this->data['ratelimit'] > 0)
			{
				header("X-RateLimit-Limit: {$this->data['ratelimit']}");

				// Delete expired hits.
				$MySQL->Push('DELETE LOW_PRIORITY QUICK IGNORE FROM application_hits WHERE stamp <= ' . (time() - CFG_RATELIMIT_SEC));

				// Determine the number of hits in the last 24 hours.
				$this->hits = $MySQL->Pull('SELECT COUNT(*) FROM application_hits WHERE app="' . $MySQL->Clean($key) . '" AND stamp > ' . (time() - CFG_RATELIMIT_SEC) . ' LIMIT 1;');
				$this->hits = (int)$this->hits['COUNT(*)'];
				$this->hits++;

				// Has the application exceeded it's API query limit for the day?
				if ( $this->hits > $this->data['ratelimit'] )
				{
					$Response->Send(509, RESP_ERR, array(
						'response' => 'Your application has made too many API calls recently. Please contact us to inquire about whitelisting.'
					));
				}

				// Record this request as a new hit.
				$MySQL->Push('INSERT INTO application_hits (app,method,stamp) VALUES ("' . $MySQL->Clean($key) . '", "' . API_METHOD . '", ' . time() . ');');
				header('X-RateLimit-Remaining: ' . ($this->data['ratelimit'] - $this->hits));
			}
		}
	}

	/*
	----
	TODO: Build class interface for modifying applications.
	----
	function Create($name, $url) {}
	function Delete($key) {}
	function Name($update = null) {}
	function URL($update = null) {}
	function Secret($update = null) {}
	function Hits($update = null) {}
	function HitsLimit($update = null) {}
	*/

}

$Application = new Application();
