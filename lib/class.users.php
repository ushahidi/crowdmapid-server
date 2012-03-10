<?php

if(!defined('LOADED_SAFELY')) die('You cannot access this file directly.');

class User
{

	private $data;

	public function Set($id)
	{
		global $MySQL;

		$_id = $MySQL->Clean($id);
		  $r = null;

		if ( strlen($id) === 128 )
		{
			// Passing a user hash.
			$r = $MySQL->Pull("SELECT * FROM users WHERE hash='{$_id}' LIMIT 1;");
		}
		elseif ( strlen($id) === 64 )
		{
			// Passing a session hash.
			$r = $MySQL->Pull("SELECT user FROM user_sessions WHERE hash='{$_id}' LIMIT 1;");
			if ( $r )
			{
				$r = $MySQL->Pull("SELECT * FROM users WHERE id='{$r['user']}' LIMIT 1;");
			}
		}
		elseif ( strpos($id, '@') !== FALSE && strpos($id, '.') !== FALSE )
		{
			// Passing an email address.
			$r = $MySQL->Pull("SELECT * FROM users WHERE email='{$_id}' LIMIT 1;");
		}
		elseif ( is_numeric($id) )
		{
			// Passing a record id.
			$r = $MySQL->Pull("SELECT * FROM users WHERE id={$_id} LIMIT 1;");
		}

		if ( $r )
		{
			$this->data = $r;
			return true;
		}

		return false;
	}

	public function Create($email, $password, $question = '', $answer = '')
	{
		global $MySQL, $Security;

		if ( $answer )
		{
			$answer = $Security->Hash($answer, 128);
		}

		$fields = array(
			'hash' => $Security->randHash(128),
			'email' => $email,
			'password' => $Security->Hash($password, 64),
			'question' => $question,
			'answer' => $answer
		);

		$ret = $MySQL->Push('INSERT INTO users (' . implode(',', array_keys($fields)) . ') VALUES ("' . implode('","', array_map('mysql_real_escape_string', array_values($fields))) . '");');

		if ( $ret )
		{
			return(array('hash' => $fields['hash'], 'insertid' => $ret));
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

		if ( $url )
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

		if ( !$this->Sites($appid, $url) )
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

		if ( $session )
		{
			return $MySQL->Pull('SELECT session as id, expire as expires FROM user_sessions WHERE application=' . $MySQL->Clean($appid) . ' AND user=' . $this->data['id'] . ' AND session="' . $MySQL->Clean($session) . '" LIMIT 1;');
		}
		else
		{
			return $MySQL->Pull('SELECT session as id, expire as expires FROM user_sessions WHERE application=' . $MySQL->Clean($appid) . ' AND user=' . $this->data['id'] . ';');
		}
	}

	public function Session($appid)
	{
		global $Security, $MySQL;

		$session = null;
		$dupe = 1;

		while ( $dupe )
		{
			$session = $Security->randHash(64);
			$dupe = $MySQL->Pull("SELECT COUNT(*) FROM user_sessions WHERE session='{$session}' LIMIT 1;");
			$dupe = (int)$dupe['COUNT(*)'];
		}

		$ret = $MySQL->Push('INSERT INTO user_sessions (user,application,session,expire) VALUES (' . $this->data['id'] . ', ' . $MySQL->Clean($appid) .  ', "' . $MySQL->Clean($session) . '", TIMESTAMPADD(SECOND, ' . CFG_USER_SESSION_EXPIRES . ', NOW()));');
		return $session;
	}

	public function sessionDelete($appid, $session)
	{
		if ( !$session OR strlen($session) != 64 )
		{
			return false;
		}

		global $MySQL;
		return $MySQL->Push('DELETE FROM user_sessions WHERE application=' . $MySQL->Clean($appid) . ' AND user=' . $this->data['id'] . ' AND session="' . $MySQL->Clean($session) . '" LIMIT 1;');
	}

	private function __Property($var, $update = null, $filter = null)
	{
		if ( $update !== null )
		{
			global $MySQL;

			if ( ! $filter )
			{
				$filter = FILTER_SANITIZE_STRING;
			}

			$update = $MySQL->Clean(trim(filter_var($update, $filter)));

			if ( $update !== null )
			{
				$ret = $MySQL->Push('UPDATE users SET ' . $var . '="' . $update . '" WHERE id=' . $this->data['id'] . ' LIMIT 1;');
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

	public function Hash($update = null)
	{
		return $this->__Property('hash', $update);
	}

	public function Email($update = null)
	{
		return $this->__Property('email', $update, FILTER_SANITIZE_EMAIL);
	}

	public function Password($update = null)
	{
		return $this->__Property('password', $update);
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

	public function Accessed($update = null)
	{
		return $this->__Property('accessed', $update);
	}

	public function Token($update = null)
	{
		return $this->__Property('token', $update);
	}

	public function Admin($update = null)
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
		return $this->__Property('admin', $update, FILTER_SANITIZE_NUMBER_INT);
	}

}

$User = new User();
