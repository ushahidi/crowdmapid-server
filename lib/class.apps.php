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
		global $MySQL, $Response;

		if ( strlen($key) )
		{
			$this->data = $MySQL->Pull('SELECT * FROM applications WHERE secret="' . $MySQL->Clean($key) . '" LIMIT 1;');
		}

		// Invalid API key, or no API key was provided.
		if ( ! $this->data )
		{
			$Response->Send(401, RESP_ERR, array(
				'error' => 'Call requires a registered API key.'
			));
		}

		// Was this a free API call?
		if ( $free == false ) {

			// Check if we're dealing with a whitelisted application.
			if ( $this->data['ratelimit'] > 0)
			{
				header("X-RateLimit-Limit: {$this->data['ratelimit']}");

				// Delete expired hits.
				// TODO: Move this to a cron task. We can't have this running every single API call in production.
				$MySQL->Push('DELETE LOW_PRIORITY QUICK IGNORE FROM application_hits WHERE application="' . $MySQL->Clean($this->data['id']) . '" AND stamp < NOW() - ' . CFG_RATELIMIT_SEC);

				// Determine the number of hits in the last 24 hours.
				$this->hits = $MySQL->Pull('SELECT COUNT(*) FROM application_hits WHERE application="' . $MySQL->Clean($this->data['id']) . '" AND stamp > NOW() - ' . CFG_RATELIMIT_SEC . ' LIMIT 1;');
				$this->hits = (int)$this->hits['COUNT(*)'];
				$this->hits++;

				// Has the application exceeded it's API query limit for the day?
				if ( $this->hits > $this->data['ratelimit'] )
				{
					$Response->Send(509, RESP_ERR, array(
						'error' => 'Your application has made too many API calls recently. Please contact us to inquire about whitelisting.'
					));
				}

				// Record this request as a new hit.
				$MySQL->Push('INSERT INTO application_hits (application,method) VALUES ("' . $MySQL->Clean($this->data['id']) . '", "' . API_METHOD . '");');
				header('X-RateLimit-Remaining: ' . ($this->data['ratelimit'] - $this->hits));
			}
		} else {
				$this->hits = $MySQL->Pull('SELECT COUNT(*) FROM application_hits WHERE application="' . $MySQL->Clean($this->data['id']) . '" AND stamp > NOW() - ' . CFG_RATELIMIT_SEC . ' LIMIT 1;');
				$this->hits = (int)$this->hits['COUNT(*)'];
				header('X-RateLimit-Remaining: ' . ($this->data['ratelimit'] - $this->hits));
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
		global $MySQL;
		$ret = $MySQL->Push('DELETE FROM applications WHERE id=' . $MySQL->Clean($id) . ' LIMIT 1;');
		if ( $ret )
		{
			// Clear out any remaining API hits in the database.
			$MySQL->Push('DELETE FROM application_hits WHERE application=' . $MySQL->Clean($id) . ';');
		}
		return $ret;
	}

	// Get or assign the name of the application.
	public function Name($update = null)
	{
		if ( $update !== null )
		{
			global $MySQL;
			$update = $MySQL->Clean(trim(filter_var($update, FILTER_SANITIZE_STRING)));

			if ( $update !== null )
			{
				$ret = $MySQL->Push('UPDATE applications SET name="' . $update . '" WHERE id=' . $this->data['id'] . ' LIMIT 1;');
				if ( $ret )
				{
					$this->data['name'] = $update;
					return $this->data['name'];
				}
			}
		}
		else
		{
			return $this->data['name'];
		}
	}

	// Get or assign a user-facing address related to the application; i.e. http://crowdmap.com
	public function URL($update = null)
	{
		if ( $update !== null )
		{
			global $MySQL;
			$update = $MySQL->Clean(trim(filter_var($update, FILTER_SANITIZE_URL)));

			if ( $update !== null )
			{
				$ret = $MySQL->Push('UPDATE applications SET url="' . $update . '" WHERE id=' . $this->data['id'] . ' LIMIT 1;');
				if ( $ret )
				{
					$this->data['url'] = $update;
					return $this->data['url'];
				}
			}
		}
		else
		{
			return $this->data['url'];
		}
	}

	// Get or assign the secret for the application. Must be a string of 64 characters.
	public function Secret($update = null)
	{
		if ( $update !== null && strlen($update === 64) )
		{
			global $MySQL;
			$update = $MySQL->Clean(trim(filter_var($update, FILTER_SANITIZE_STRING)));

			if ( $update !== null )
			{
				$ret = $MySQL->Push('UPDATE applications SET secret="' . $update . '" WHERE id=' . $this->data['id'] . ' LIMIT 1;');
				if ( $ret )
				{
					$this->data['secret'] = $update;
					return $this->data['secret'];
				}
			}
		}
		else
		{
			return $this->data['secret'];
		}
	}

	// Get or assign the API hit cap for the application. This is X hits over CFG_RATELIMIT_SEC seconds. 0 will whitelist/exempt an app from this.
	public function rateLimit($update = null)
	{
		if ( $update !== null && is_numeric($update) )
		{
			global $MySQL;
			$update = $MySQL->Clean(trim(filter_var($update, FILTER_SANITIZE_NUMBER_INT)));

			if ( $update !== null && is_numeric($update) )
			{
				$ret = $MySQL->Push('UPDATE applications SET ratelimit="' . $update . '" WHERE id=' . $this->data['id'] . ' LIMIT 1;');
				if ( $ret )
				{
					$this->data['ratelimit'] = $update;
					return $this->data['ratelimit'];
				}
			}
		}
		else
		{
			return $this->data['ratelimit'];
		}
	}

	// Get the number of hits remaining before the application hits it's API cap.
	public function rateRemaining()
	{
		return $this->hits;
	}

	// Get or assign an internal note relating to the application. This should never, ever be exposed over the API.
	public function Note($update = null)
	{
		if ( $update !== null )
		{
			global $MySQL;
			$update = $MySQL->Clean(trim(filter_var($update, FILTER_SANITIZE_STRING)));

			if ( $update !== null )
			{
				$ret = $MySQL->Push('UPDATE applications SET note="' . $update . '" WHERE id=' . $this->data['id'] . ' LIMIT 1;');
				if ( $ret )
				{
					$this->data['note'] = $update;
					return $this->data['note'];
				}
			}
		}
		else
		{
			return $this->data['note'];
		}
	}

	// Get or assign the contact's email address. This should go to a Real Human(tm) in charge of the development of the assigned application, as we may need to send notices to this address later if there are problems with their API reaching hit cap, etc.
	public function adminEmail($update = null)
	{
		if ( $update !== null )
		{
			global $MySQL;
			$update = $MySQL->Clean(trim(filter_var($update, FILTER_SANITIZE_EMAIL)));

			if ( $update !== null )
			{
				$ret = $MySQL->Push('UPDATE applications SET admin_email="' . $update . '" WHERE id=' . $this->data['id'] . ' LIMIT 1;');
				if ( $ret )
				{
					$this->data['admin_email'] = $update;
					return $this->data['admin_email'];
				}
			}
		}
		else
		{
			return $this->data['admin_email'];
		}
	}

	// Get or assign the contact identity (individual's name, organization, etc.) in charge of the application.
	public function adminIdentity($update = null)
	{
		if ( $update !== null )
		{
			global $MySQL;
			$update = $MySQL->Clean(trim(filter_var($update, FILTER_SANITIZE_STRING)));

			if ( $update !== null )
			{
				$ret = $MySQL->Push('UPDATE applications SET admin_identity="' . $update . '" WHERE id=' . $this->data['id'] . ' LIMIT 1;');
				if ( $ret )
				{
					$this->data['admin_identity'] = $update;
					return $this->data['admin_identity'];
				}
			}
		}
		else
		{
			return $this->data['admin_identity'];
		}
	}

	// Toggle API debugging for this app; exposes additional diagnostic data with API returns.
	public function Debug($update = null)
	{
		if ( $update !== null )
		{
			// Accept boolean values.
			if ( $update === TRUE )
			{
				$update = 1;
			}
			elseif ( $update === FALSE )
			{
				$update = 0;
			}

			global $MySQL;
			$update = $MySQL->Clean(trim(filter_var($update, FILTER_SANITIZE_NUMBER_INT)));

			if ( $update > 1 )
			{
				$update = 1;
			}
			elseif ( $update < 1 )
			{
				$update = 0;
			}

			if ( is_numeric($update) )
			{
				$ret = $MySQL->Push('UPDATE applications SET debug="' . $update . '" WHERE id=' . $this->data['id'] . ' LIMIT 1;');
				if ( $ret )
				{
					$this->data['debug'] = $update;
					return $this->data['debug'];
				}
			}
		}
		else
		{
			return $this->data['debug'];
		}
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
