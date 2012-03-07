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

	// Delete an application from the registry.
	public function Delete($id = null)
	{
		global $MySQL;
		$ret = $MySQL->Push('DELETE FROM users WHERE id=' . $MySQL->Clean($id) . ' LIMIT 1;');
		/*if ( $ret )
		{
			// Clear out any remaining API hits in the database.
			$MySQL->Push('DELETE FROM user_sessions WHERE user=' . $MySQL->Clean($id) . ';');
		}*/
		return $ret;
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
