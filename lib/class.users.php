<?php

if(!defined('LOADED_SAFELY')) die('You cannot access this file directly.');

class User
{

	private $data;
	public $assigned = false;

	public function Set($id)
	{
		global $MySQL;

		Plugins::raiseEvent("user.set.pre", $id);

		$_id = $MySQL->Clean($id);
		  $r = null;

		if (strlen($id) === 128)
		{
			// Passing a user hash.

			// Check if the user has been merged (technically, redirected) to another hash.
			if ($r = $MySQL->Pull("SELECT user FROM user_aliases WHERE hash='{$_id}' LIMIT 1;")) {
				$_id = $r['user'];
			}

			$r = $MySQL->Pull("SELECT * FROM users WHERE hash='{$_id}' LIMIT 1;");
		}
		elseif (strlen($id) === 64)
		{
			// Passing a session hash.
			if ($r = $MySQL->Pull("SELECT user FROM user_sessions WHERE hash='{$_id}' LIMIT 1;")) {
				$r = $MySQL->Pull("SELECT * FROM users WHERE id='{$r['user']}' LIMIT 1;");
			}
		}
		elseif (strpos($id, '@') !== FALSE && strpos($id, '.') !== FALSE && filter_var($id, FILTER_VALIDATE_EMAIL))
		{
			// Passing an email address.
			if ($r = $MySQL->Pull("SELECT user FROM user_addresses WHERE LOWER(email)=LOWER('{$_id}') LIMIT 1;")) {
				$r = $MySQL->Pull("SELECT * FROM users WHERE id='{$r['user']}' LIMIT 1;");
			}
		}
		elseif (is_numeric($id))
		{
			if($r = $MySQL->Pull("SELECT * FROM users WHERE phone='{$_id}' AND phone_confirmed=1 LIMIT 1;")) {
				// We'll probably want to do something here.
			} else {
				// Passing a record id.
				$r = $MySQL->Pull("SELECT * FROM users WHERE id={$_id} LIMIT 1;");
			}
		}

		if ($r && isset($r['id']))
		{
			Plugins::raiseEvent("user.set.succeed", $r);
			$this->data = $r;
			$this->assigned = true;
			return true;
		}

		Plugins::raiseEvent("user.set.fail");
		return false;
	}

	public function Create($email, $password, $question = '', $answer = '', $hash = '')
	{
		global $MySQL, $Security;

		$answer =   (strlen($answer) ?          $Security->Hash($answer, 128) : '');
		$password = (strlen($password) == 128 ? $password : $Security->Hash($password, 128));
		$hash =     (strlen($hash) == 128 ?     $hash : $Security->randHash(128));

		$fields = array(
			'hash' => $hash,
			'password' => $password,
			'question' => $question,
			'answer' => $answer
		);

		$exists = $MySQL->Pull("SELECT COUNT(*) FROM user_addresses WHERE email='" . $MySQL->Clean($email) . "' LIMIT 1;");

		if ( ! (int)$exists['COUNT(*)']) {

			// Add account to users table.
			$ret = $MySQL->Push('INSERT INTO users (' . implode(',', array_keys($fields)) . ') VALUES ("' . implode('","', array_map('mysql_real_escape_string', array_values($fields))) . '");');

			// Add email address to users table.
			$MySQL->Push('INSERT INTO user_addresses (user,email,master) VALUES (' . $ret . ',"' . $MySQL->Clean($email) . '",1);');

			$MySQL->Push('UPDATE users SET password_changed=CURRENT_TIMESTAMP() WHERE id=' . $ret . ' LIMIT 1;');

			if ($ret)
			{
				$this->Set($ret);
				return(array('hash' => $fields['hash'], 'insertid' => $ret));
			}

		}

		return false;
	}

	public function Delete()
	{
		global $MySQL;
		$ret = $MySQL->Push('DELETE FROM users WHERE id=' . $this->data['id'] . ' LIMIT 1;');
		return $ret;
	}

	public function Sites($appid, $url = null)
	{
		global $MySQL;

		if ($url)
		{
			$url = filter_var(urldecode($url), FILTER_SANITIZE_URL);
			return $MySQL->Pull('SELECT url FROM user_sites WHERE application=' . $MySQL->Clean($appid) . ' AND user=' . $this->data['id'] . ' AND url="' . $MySQL->Clean($url) . '" LIMIT 1;');
		}
		else
		{
			return $MySQL->Pull('SELECT url FROM user_sites WHERE application=' . $MySQL->Clean($appid) . ' AND user=' . $this->data['id'] . ';');
		}
	}

	public function Site($appid, $url)
	{
		global $MySQL;

		if ( ! $this->Sites($appid, $url))
		{
			$url = filter_var(urldecode($url), FILTER_SANITIZE_URL);
			$MySQL->Push('INSERT INTO user_sites (user,application,url) VALUES (' . $this->data['id'] . ', ' . $MySQL->Clean($appid) .  ', "' . $MySQL->Clean($url) . '");');
		}

		return true;
	}

	public function siteDelete($appid, $url)
	{
		global $MySQL;
		$url = filter_var(urldecode($url), FILTER_SANITIZE_URL);
		return $MySQL->Push('DELETE FROM user_sites WHERE application=' . $MySQL->Clean($appid) . ' AND user=' . $this->data['id'] . ' AND url="' . $MySQL->Clean($url) . '" LIMIT 1;');
	}

	public function Sessions($appid, $session = null)
	{
		global $MySQL;

		if ($session)
		{
			return $MySQL->Pull('SELECT session as id, expire as expires FROM user_sessions WHERE application=' . $MySQL->Clean($appid) . ' AND user=' . $this->data['id'] . ' AND session="' . $MySQL->Clean($session) . '" LIMIT 1;');
		}
		else
		{
			return $MySQL->Pull('SELECT session as id, expire as expires FROM user_sessions WHERE application=' . $MySQL->Clean($appid) . ' AND user=' . $this->data['id'] . ';');
		}
	}

	public function Session($appid, $extend = false)
	{
		global $Security, $MySQL;

		// If the application already has an active session, just return that id.
		$session = $MySQL->Pull("SELECT * FROM user_sessions WHERE user='{$this->data['id']}' AND application='{$appid}' LIMIT 1;");
		if ($session) {
			if ($extend) {
				$MySQL->Push("UPDATE user_sessions SET expire=TIMESTAMPADD(SECOND, " . CFG_USER_SESSION_EXPIRES . ', NOW()) WHERE application="' . $MySQL->Clean($appid) . '" AND id="' . $MySQL->Clean($session['id']) . '" LIMIT 1;');
			}
			return $session['session'];
		}

		$dupe = 1;
		while ($dupe)
		{
			$session = $Security->randHash(64);
			$dupe = $MySQL->Pull("SELECT COUNT(*) FROM user_sessions WHERE session='{$session}' LIMIT 1;");
			$dupe = (int)$dupe['COUNT(*)'];
		}

		$MySQL->Push('INSERT INTO user_sessions (user,application,session,expire) VALUES (' . $this->data['id'] . ', ' . $MySQL->Clean($appid) .  ', "' . $MySQL->Clean($session) . '", TIMESTAMPADD(SECOND, ' . CFG_USER_SESSION_EXPIRES . ', NOW()));');
		return $session;
	}

	public function sessionDelete($appid, $session)
	{
		if ( ! $session OR !is_string($session) OR strlen($session) != 64)
		{
			return false;
		}

		global $MySQL;
		return $MySQL->Push('DELETE FROM user_sessions WHERE application=' . $MySQL->Clean($appid) . ' AND user=' . $this->data['id'] . ' AND session="' . $MySQL->Clean($session) . '" LIMIT 1;');
	}

	private function __Property($var, $update = null, $filter = null)
	{
		if ($update !== null)
		{
			global $MySQL;

			if ( ! $filter)
			{
				$filter = FILTER_SANITIZE_STRING;
			}

			$update = $MySQL->Clean(trim(filter_var($update, $filter)));

			if ($update !== null)
			{
				$ret = $MySQL->Push('UPDATE users SET ' . $var . '="' . $update . '" WHERE id=' . $this->data['id'] . ' LIMIT 1;');
				if ($ret)
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

	public function ID()
	{
		return $this->data['id'];
	}

	public function Hash($update = null)
	{
		return $this->__Property('hash', $update);
	}

	public function Emails()
	{
		global $MySQL;

		// Return all associated email addresses for the account.
		$emails = $MySQL->Pull("SELECT email,master,confirmed,registered FROM user_addresses WHERE user=" . $this->data['id'] . " ORDER BY master DESC, registered ASC");
		return $emails;
	}

	public function emailAdd($address, $primary = false, $confirmed = false)
	{
		global $MySQL;

		if($address = filter_var($address, FILTER_SANITIZE_EMAIL)) {
			$exists = $MySQL->Pull("SELECT COUNT(*),user FROM user_addresses WHERE email='" . $MySQL->Clean($address) . "' LIMIT 1;");
			if ( ! (int)$exists['COUNT(*)']) {
				// All clear.
				return $MySQL->Push('INSERT INTO user_addresses (user,email,master,confirmed) VALUES (' . $this->data['id'] . ', "' . $MySQL->Clean($address) . '",' . (int)$primary . ',' . (int)$confirmed . ');');
			} elseif($exists['user'] == $this->data['id']) {
				// User already has this address registered to them. Just nod politely.
				return true;
			}
		}
		return false;
	}

	public function emailRemove($address)
	{
		global $MySQL;

		if ($address = filter_var($address, FILTER_SANITIZE_EMAIL)) {
			$exists = $MySQL->Pull("SELECT id,master FROM user_addresses WHERE user=" . $MySQL->Clean($this->data['id']) . " AND email='" . $MySQL->Clean($address) . "' LIMIT 1;");
			if ($exists) {
				if ($exists['master']) {
					return 'You cannot remove your primary email address.';
				} else {
					if ($MySQL->Push('DELETE FROM user_addresses WHERE id=' . $exists['id'] . ' LIMIT 1;')) {
						return true;
					}
				}
			}
		}

		return 'There was a problem deleting this account.';
	}

	public function emailConfirm($address)
	{
		global $MySQL;

		if ($address = filter_var($address, FILTER_SANITIZE_EMAIL)) {
			$exists = $MySQL->Pull("SELECT id,confirmed FROM user_addresses WHERE user=" . $MySQL->Clean($this->data['id']) . " AND email='" . $MySQL->Clean($address) . "' LIMIT 1;");
			if ($exists) {
				if ($exists['confirmed']) {
					// Already confirmed. Nod politely.
					return true;
				} else {
					// Update address' confirmed flag.
					if ($MySQL->Push('UPDATE user_addresses SET confirmed=1 WHERE id=' . $exists['id'] . ' LIMIT 1;')) {
						return true;
					}
				}
			}
		}

		return 'There was a problem updating this account.';
	}

	public function emailAssignMaster($address)
	{
		global $MySQL;

		if ($address = filter_var($address, FILTER_SANITIZE_EMAIL)) {
			$exists = $MySQL->Pull("SELECT id,master FROM user_addresses WHERE user=" . $MySQL->Clean($this->data['id']) . " AND email='" . $MySQL->Clean($address) . "' LIMIT 1;");
			if ($exists) {
				if ($exists['master']) {
					// This account is already the primary.
					return true;
				} else {
					// Remove master account flag from other addresses.
					$MySQL->Push('UPDATE user_addresses SET master=0 WHERE master=1 AND user=' . $MySQL->Clean($this->data['id']) . ' LIMIT 1;');

					// Assign this address as the new master.
					$MySQL->Push('UPDATE user_addresses SET master=1 WHERE master=0 AND id=' . $MySQL->Clean($exists['id']) . ' LIMIT 1;');

					return true;
				}
			}
		}

		return 'There was a problem removing this email address.';
	}

	public function Email()
	{
		global $MySQL;

		$email = $MySQL->Pull("SELECT email FROM user_addresses WHERE user=" . $MySQL->Clean($this->data['id']) . " AND master=1 LIMIT 1;");
		if($email) return $email['email'];

		return false;

		// Get/set the primary email address associated with the account.
		//return $this->__Property('email', $update, FILTER_SANITIZE_EMAIL);
	}

	public function Password($update = null)
	{
		if ($update) {
			global $Security;
			$update = $Security->Hash($update, 128);
			$this->passwordChanged(true);
		}

		return $this->__Property('password', $update);
	}

	public function passwordChanged($update = null)
	{
		global $MySQL;

		if ($update) {
			return $MySQL->Pull("UPDATE users SET password_changed=CURRENT_TIMESTAMP() WHERE id=" . $MySQL->Clean($this->data['id']) . " LIMIT 1;");
		} else {
			$r = $MySQL->Pull("SELECT password_changed FROM users WHERE id=" . $MySQL->Clean($this->data['id']) . " LIMIT 1;");
			return $r['password_changed'];
		}

	}

	public function Phone($update = null)
	{
		return $this->__Property('phone', $update);
	}

	public function PhoneConfirmed($update = null)
	{
		return (boolean)$this->__Property('phone_confirmed', $update);
	}

	public function Avatar($update = null)
	{
		return $this->__Property('avatar', $update);
	}

	public function Question($update = null)
	{
		return $this->__Property('question', $update);
	}

	public function Answer($update = null)
	{
		return $this->__Property('answer', $update);
	}

	public function Registered()
	{
		return $this->__Property('registered');
	}

	public function Badge($appid, $badge, $update = null)
	{
		global $MySQL;
		$badge = strtolower($badge);

		if (is_array($update) && isset($update['title']) && isset($update['description']) && isset($update['graphic']) && isset($update['url']) && isset($update['category'])) {
			if ($resp = $this->Badge($appid, $badge)) {
				return $MySQL->Push("UPDATE user_badges SET title='" . $MySQL->Clean($update['title']) . "', description='" . $MySQL->Clean($update['description']) . "', graphic='" . $MySQL->Clean($update['graphic']) . "', url='" . $MySQL->Clean($update['url']) . "', category='" . $MySQL->Clean($update['category']) . "' WHERE application='" . $MySQL->Clean($appid) . "' AND user='" . $MySQL->Clean($this->data['id']) . "' AND badge='" . $MySQL->Clean($badge) . "' LIMIT 1;");
			} else {
				return $MySQL->Push("INSERT INTO user_badges (application,user,badge,title,description,graphic,url,category) VALUES ('" . $MySQL->Clean($appid) . "', '" . $MySQL->Clean($this->data['id']) . "', '" . $MySQL->Clean($badge) . "', '" . $MySQL->Clean($update['title']) . "', '" . $MySQL->Clean($update['description']) . "', '" . $MySQL->Clean($update['graphic']) . "', '" . $MySQL->Clean($update['url']) . "', '" . $MySQL->Clean($update['category']) . "');");
			}
		} elseif($update === 'DELETE') {
			return $MySQL->Push("DELETE FROM user_badges WHERE application='" . $MySQL->Clean($appid) . "' AND user='" . $MySQL->Clean($this->data['id']) . "' AND badge='" . $MySQL->Clean($badge) . "' LIMIT 1;");
		}

		return $MySQL->Pull("SELECT badge,description,graphic,url,category,awarded FROM user_badges WHERE application='" . $MySQL->Clean($appid) . "' AND user='" . $MySQL->Clean($this->data['id']) . "' AND badge='" . $MySQL->Clean($badge) . "' LIMIT 1;");
	}

	public function Badges($appid, $returnAll = true)
	{
		global $MySQL;

		if ($returnAll == true) {
			return $MySQL->Pull("SELECT badge,description,graphic,url,category,awarded FROM user_badges WHERE user='" . $this->data['id'] . "' ORDER BY badge;"); //" GROUP BY category;");
		} else {
			return $MySQL->Pull("SELECT badge,description,graphic,url,category,awarded FROM user_badges WHERE user='" . $this->data['id'] . "' AND application='" . $MySQL->Clean($appid) . "' ORDER BY badge;"); //" GROUP BY category;");
		}
	}

	public function Cache($appid, $key, $value = null, $expires = 30)
	{
		$key = md5('user_cache_' . $this->data['id'] . '_' . $appid . '_' . $key);

		if ($value) {
			Cache::Set($key, $value, $expires);
			return true;
		} else {
			return Cache::Get($key);
		}
	}

	public function Storage($appid, $key = null, $update = null, $public = false, $expires = null)
	{
		global $MySQL;

		if ($key !== null) {
			if ($update !== null) {
				// Purge any existing copies of this storage.
				if($public) {
					$MySQL->Push('DELETE FROM user_storage WHERE user=' . $this->data['id'] . ' AND storage_public=1 AND storage_key="' . $MySQL->Clean($key) . '";');
				} else {
					$MySQL->Push('DELETE FROM user_storage WHERE user=' . $this->data['id'] . ' AND application=' . $MySQL->Clean($appid) . ' AND storage_public=0 AND storage_key="' . $MySQL->Clean($key) . '";');
				}

				if (strlen($update)) {
					$update = str_replace('%NOW%', time(), $update);

					if ($expires && is_numeric($expires)) {
						$expires = $MySQL->Pull("SELECT TIMESTAMPADD(SECOND, {$expires}, NOW()) as expires LIMIT 1;");
						$expires = $expires['expires'];
					} else {
						$expires = 'NULL';
					}

					// Store the new data.
					return $MySQL->Push('INSERT INTO user_storage (user,application,storage_public,storage_key,storage_value,storage_expires) VALUES (' .
						$this->data['id'] . ', ' .
						$MySQL->Clean($appid) . ', ' .
						(int)$public . ', "' .
						$MySQL->Clean($key) .  '", "' .
						$MySQL->Clean($update) . '", "' .
						$expires . '");'
					);
				} else {
					return true;
				}
			} else {
				if ($public) {
					$storage = $MySQL->Pull('SELECT storage_value FROM user_storage WHERE user=' . $this->data['id'] . ' AND storage_public=1 AND storage_key="' . $MySQL->Clean($key) . '" LIMIT 1;');
				} else {
					$storage = $MySQL->Pull('SELECT storage_value FROM user_storage WHERE user=' . $this->data['id'] . ' AND application=' . $MySQL->Clean($appid) . ' AND storage_public=0 AND storage_key="' . $MySQL->Clean($key) . '" LIMIT 1;');
				}

				if (isset($storage['storage_value'])) return $storage['storage_value'];
				return false;
			}
		} else {
			return $MySQL->Pull('SELECT storage_public AS store_public,storage_key AS store_key, storage_value AS store_value FROM user_storage WHERE application="' . $MySQL->Clean($appid) . '" AND user="' . $MySQL->Clean($this->data['id']) . '";');
		}
	}

	public function Token($update = null)
	{
		if ($update && is_array($update))
		{
			if (isset($update['token']))
			{
				$this->__Property('token', $update['token']);
			}

			if (isset($update['memory']))
			{
				$this->__Property('token_memory', $update['memory']);
			}

			if (isset($update['expires']))
			{
				global $MySQL;
				$stamp = $MySQL->Pull("SELECT TIMESTAMPADD(SECOND, {$update['expires']}, NOW()) as expires LIMIT 1;");
				$this->__Property('token_expires', $stamp['expires']);
			}
		}
		else
		{
			$ret = array();
			$ret['token'] = $this->__Property('token');
			$ret['memory'] = $this->__Property('token_memory');
			$ret['expires'] = strtotime($this->__Property('token_expires'));
			return $ret;
		}
	}

	public function ClearToken()
	{
		global $MySQL;
		return $MySQL->Push("UPDATE users SET token = NULL, token_memory = NULL, token_expires = NULL WHERE id={$this->data['id']} LIMIT 1;");
	}

	public function Admin($update = null)
	{
		if ($update)
		{
			if ($update === TRUE)
			{
				$update = 1;
			}
			elseif ($update === FALSE)
			{
				$update = 0;
			}
			elseif ( ! is_numeric($update))
			{
				$update = null;
			}
			elseif ($update < 1)
			{
				$update = 0;
			}
			elseif ($update > 1)
			{
				$update = 1;
			}
		}
		return $this->__Property('admin', $update, FILTER_SANITIZE_NUMBER_INT);
	}

}

$User = new User();

function listUsers() {
	global $MySQL;
	return $MySQL->Pull("SELECT hash,registered FROM users");
}

