<?php

if(!defined('LOADED_SAFELY')) die('You cannot access this file directly.');

// API Access Control

class Application {

	private $data, $hits;

	public function __construct()
	{
		$this->data = array();
		$this->hits = 0;
	}

	public function Set($key = '', $free = false)
	{
		global $MySQL, $Response, $cache;

		if ( strlen($key) )
		{
			$this->data = $cache->get('riverid_app_' . $key);

			if ($this->data)
			{
				if ($this->data == 'nein')
				{
					$this->data = array();
				}
				else
				{
					$this->data = unserialize(base64_decode($this->data));
				}
			}
			else
			{
				$this->data = $MySQL->Pull('SELECT * FROM applications WHERE secret="' . $MySQL->Clean($key) . '" LIMIT 1;');
				$cache->set('riverid_app_' . $key, base64_encode(serialize($this->data)), MEMCACHE_COMPRESSED, 30);
			}
		}

		// Invalid API key, or no API key was provided.
		if ( ! $this->data )
		{
			if (strlen($key))
			{
				$cache->set('riverid_app_' . $key, 'nein', MEMCACHE_COMPRESSED, 0);
			}

			$Response->Send(401, RESP_ERR, array(
				'error' => 'Call requires a registered API key.'
			));
		}

		// Determine the number of unexpired hits.
		$this->hits = $MySQL->Pull("SELECT cache_value FROM cache WHERE cache_name='app_hits_count_{$this->data['id']}' LIMIT 1;");
		$this->hits = $this->hits['cache_value'];
		header('X-RateLimit-Remaining: ' . ($this->data['ratelimit'] - $this->hits));

		// Was this a free API call?
		if ( $free == false AND $this->data['ratelimit'] > 0)
		{
			header("X-RateLimit-Limit: {$this->data['ratelimit']}");

			// Has the application exceeded it's API query limit for the day?
			if ( $this->hits >= $this->data['ratelimit'] )
			{
				$Response->Send(509, RESP_ERR, array(
					'error' => 'Your application has made too many API calls recently. Please contact us to inquire about whitelisting.'
				));
			}

			// Record this request as a new hit.
			$MySQL->Push('INSERT INTO application_hits (application,method,expires) VALUES ("' . $this->data['id'] . '", "' . API_METHOD . '", TIMESTAMPADD(SECOND, ' . CFG_RATELIMIT_SEC . ', NOW()));');
		}
	}

	// Register a new application with the system. Pass it (at least) the name and user-facing address of the app. If successful returns an array with the secret and row id of the new application. If unsuccessful returns false.
	public function Create($name, $url, $ratelimit = 5000, $admin_email = '', $admin_identity = '')
	{
		global $MySQL, $Security;

		$fields = array(
			'name' => $name,
			'url' => $url,
			'secret' => $Security->randHash(64),
			'ratelimit' => (int)$ratelimit,
			'admin_email' => $admin_email,
			'admin_identity' => $admin_identity
		);

		$ret = $MySQL->Push('INSERT INTO applications (' . implode(',', array_keys($fields)) . ') VALUES ("' . implode('","', array_map('mysql_real_escape_string', array_values($fields))) . '");');

		if ( $ret )
		{
			return(array('secret' => $fields['secret'], 'insertid' => $ret));
		}

		return false;
	}

	// Delete an application from the registry.
	public function Delete($id = null)
	{
		global $MySQL, $cache;
		$ret = $MySQL->Push('DELETE FROM applications WHERE id=' . $MySQL->Clean($id) . ' LIMIT 1;');
		$cache->delete('riverid_app_' . $this->Secret());
		return $ret;
	}

	private function __Property($var, $update = null, $filter = null)
	{
		if ( $update !== null )
		{
			global $MySQL, $cache;

			if ( ! $filter )
			{
				$filter = FILTER_SANITIZE_STRING;
			}

			$update = $MySQL->Clean(trim(filter_var($update, $filter)));

			if ( $update !== null )
			{
				$ret = $MySQL->Push('UPDATE applications SET ' . $var . '="' . $update . '" WHERE id=' . $this->data['id'] . ' LIMIT 1;');
				$cache->delete('riverid_app_' . $this->Secret());
				if ( $ret )
				{
					$this->data[$var] = $update;
					return $this->data[$var];
				}
			}
		}
		else
		{
			return $this->data[$var];
		}
	}

	// Get the application row ID.
	public function ID()
	{
		return $this->data['id'];
	}

	// Get or assign the name of the application.
	public function Name($update = null)
	{
		return $this->__Property('name', $update);
	}

	// Get or assign a user-facing address related to the application; i.e. http://crowdmap.com
	public function URL($update = null)
	{
		return $this->__Property('url', $update, FILTER_SANITIZE_URL);
	}

	// Get or assign the secret for the application. Must be a string of 64 characters.
	public function Secret($update = null)
	{
		return $this->__Property('secret', $update);
	}

	// Get or assign the API hit cap for the application. This is X hits over CFG_RATELIMIT_SEC seconds. 0 will whitelist/exempt an app from this.
	public function rateLimit($update = null)
	{
		return $this->__Property('ratelimit', $update, FILTER_SANITIZE_NUMBER_INT);
	}

	// Get the number of hits remaining before the application hits it's API cap.
	public function rateRemaining()
	{
		$limit = $this->rateLimit();
		if($limit > 0) return $limit - $this->hits;
		return $this->hits;
	}

	// Determine when the next hit in the database is set to expire. This is intended to indicate when the next API hit will theoretically free up if an app has reached it's limit.
	public function rateNextExpiration()
	{
		// Are we whitelisted?
		if ($this->rateLimit() === 0) return 0;

		global $MySQL;
		$expire = $MySQL->Pull("SELECT TIME_TO_SEC(expires - NOW()) as nextexpire FROM application_hits WHERE application={$this->data['id']} AND expires > NOW() ORDER BY expires ASC LIMIT 1;");

		// No expirations pending:
		if(!$expire) return 0;

		// Report next expiration, and tack on a 5 second margin of error (as the scheduler does not run every second)
		return (int)$expire['nextexpire'] + 5;
	}

	// Get or assign the email address mailings should be identified by; i.e. Crowdmap <support@crowdmap.com>
	public function mailFrom($update = null)
	{
		return $this->__Property('mail_from', $update, FILTER_SANITIZE_EMAIL);
	}

	// Get or assign an internal note relating to the application. This should never, ever be exposed over the API.
	public function Note($update = null)
	{
		return $this->__Property('note', $update);
	}

	// Get or assign the contact's email address. This should go to a Real Human(tm) in charge of the development of the assigned application, as we may need to send notices to this address later if there are problems with their API reaching hit cap, etc.
	public function adminEmail($update = null)
	{
		return $this->__Property('admin_email', $update, FILTER_SANITIZE_EMAIL);
	}

	// Get or assign the contact identity (individual's name, organization, etc.) in charge of the application.
	public function adminIdentity($update = null)
	{
		return $this->__Property('admin_identity', $update);
	}

/*
	// Get or assign an internal note relating to the application. This should never, ever be exposed over the API.
	public function apigeeUsername($update = null)
	{
		return $this->__Property('apigee_username', $update);
	}

	// Get or assign the contact's email address. This should go to a Real Human(tm) in charge of the development of the assigned application, as we may need to send notices to this address later if there are problems with their API reaching hit cap, etc.
	public function apigeePassword($update = null)
	{
		return $this->__Property('apigee_password', $update, FILTER_SANITIZE_EMAIL);
	}

	// Get or assign the contact identity (individual's name, organization, etc.) in charge of the application.
	public function apigeeApplicationID($update = null)
	{
		return $this->__Property('apigee_app_id', $update);
	}
*/

	// Toggle API debugging for this app; exposes additional diagnostic data with API returns.
	public function Debug($update = null)
	{
		if ( $update )
		{
			if ( $update === TRUE )
			{
				$update = 1;
			}
			elseif( $update === FALSE )
			{
				$update = 0;
			}
			elseif ( !is_numeric($update) )
			{
				$update = null;
			}
			elseif( $update < 1 )
			{
				$update = 0;
			}
			elseif( $update > 1 )
			{
				$update = 1;
			}
		}
		return $this->__Property('debug', $update, FILTER_SANITIZE_NUMBER_INT);
	}

	// Toggle administrative function access for this app?
	public function adminAccess($update = null)
	{
		if ( $update )
		{
			if ( $update === TRUE )
			{
				$update = 1;
			}
			elseif( $update === FALSE )
			{
				$update = 0;
			}
			elseif ( !is_numeric($update) )
			{
				$update = null;
			}
			elseif( $update < 1 )
			{
				$update = 0;
			}
			elseif( $update > 1 )
			{
				$update = 1;
			}
		}
		return $this->__Property('admin_access', $update, FILTER_SANITIZE_NUMBER_INT);
	}

	// Get the timestamp of when the application was registered.
	public function Registered($format = null)
	{
		$d = strtotime($this->data['registered']);

		if ( $format )
		{
			$d = date($format, $d);
		}

		return $d;
	}

}

$Application = new Application();
