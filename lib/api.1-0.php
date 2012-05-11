<?php

if(!defined('LOADED_SAFELY')) die('You cannot access this file directly.');

if ( API_METHOD == 'addusertosite' )
{
	api_expectations(array('email', 'session_id', 'url'));

	if ( $User->Set($request['email']) )
	{
		$session = $User->Sessions($Application->ID(), $request['session_id']);
		if( $session )
		{
			if( validateString($request['url'], FILTER_VALIDATE_URL) )
			{
				$Response->Send(200, RESP_OK, array(
					'response' => $User->Site($Application->ID(), $request['url'])
				));
			}

			$Response->Send(200, RESP_ERR, array(
				'error' => 'The submitted URL is not valid.'
			));
		}

		$Response->Send(200, RESP_ERR, array(
			'error' => 'Please provide a valid session identifier.'
		));
	}

	$Response->Send(200, RESP_ERR, array(
		'error' => 'The email address does not appear to be registered.'
	));
}
elseif ( API_METHOD == 'removeuserfromsite' )
{
	api_expectations(array('email', 'session_id', 'url'));

	if ( $User->Set($request['email']) )
	{
		$session = $User->Sessions($Application->ID(), $request['session_id']);
		if( $session )
		{
			if( validateString($request['url'], FILTER_VALIDATE_URL) )
			{
				$Response->Send(200, RESP_OK, array(
					'response' => $User->siteDelete($Application->ID(), $request['url'])
				));
			}

			$Response->Send(200, RESP_ERR, array(
				'error' => 'The submitted URL is not valid.'
			));
		}

		$Response->Send(200, RESP_ERR, array(
			'error' => 'Please provide a valid session identifier.'
		));
	}

	$Response->Send(200, RESP_ERR, array(
		'error' => 'The email address does not appear to be registered.'
	));
}
elseif ( API_METHOD == 'usersites' )
{
	api_expectations(array('email', 'session_id'));

	if ( $User->Set($request['email']) )
	{
		$session = $User->Sessions($Application->ID(), $request['session_id']);

		if( $session )
		{
			$Response->Send(200, RESP_OK, array(
				'response' => $User->Sites($Application->ID())
			));
		}

		$Response->Send(200, RESP_ERR, array(
			'error' => 'Please provide a valid session identifier.'
		));
	}

	$Response->Send(200, RESP_ERR, array(
		'error' => 'The email address does not appear to be registered.'
	));
}
elseif ( API_METHOD == 'changeemail' )
{
	api_expectations(array('oldemail', 'newemail', 'password', 'mailbody'));

	if ( $User->Set($request['oldemail']) )
	{
		// Encode submitted password so we can compare.
		$password = $Security->Hash($request['password'], 64);

		if ( $User->Password() === $password )
		{
			if( validateString($request['oldemail']) )
			{
				if ( $request['oldemail'] === $request['newemail'] )
				{
					// Just nod our head if we're trying to change to the same address.
					$Response->Send(200, RESP_OK, array(
						'response' => true
					));
				}

				// Does the application have a custom mail_from set?
				$from = CFG_MAIL_FROM;
				if ( $Application->mailFrom() )
				{
					$from = $Application->mailFrom();
				}

				// Generate a one-use token for authorizing this change.
				$token = strtoupper($Security->randHash(32));
				$User->Token(array(
					'token' => $token,
					'memory' => $request['newemail'],
					'expires' => CFG_TOKEN_EXPIRES
				));

				// Replace %token% in mailbody with the necessary authorization code.
				$request['mailbody'] = trim(filter_var($request['mailbody'], FILTER_SANITIZE_STRING));
				$request['mailbody'] = str_replace('%token%', $token, $request['mailbody']);

				if(isset($request['mailsubject'])) $request['subject'] = $request['mailsubject'];
				if(!isset($request['subject'])) $request['subject'] = 'Confirm Your ' . $Application->Name() . ' Email Address Change';
				$request['subject'] = trim(filter_var($request['subject'], FILTER_SANITIZE_STRING));

				// Notify user of the address change.
				$Mailing->Send($from, $request['newemail'], $request['subject'], $request['mailbody']);

				// API response
				$Response->Send(200, RESP_OK, array(
					'response' => true
				));
			}

			$Response->Send(200, RESP_ERR, array(
				'error' => 'The submitted email address is not valid.'
			));
		}
		else
		{
			$Response->Send(200, RESP_ERR, array(
				'error' => 'The password is incorrect for this user.'
			));
		}
	}

	$Response->Send(200, RESP_ERR, array(
		'error' => 'The email address does not appear to be registered.'
	));
}
elseif ( API_METHOD == 'confirmemail' )
{
	api_expectations(array('email', 'token'));

	if ( $User->Set($request['email']) && validateString($request['email']) )
	{
		$token = $User->Token();

		if ( $token['token'] === $request['token'] )
		{
			if ( $token['expires'] > time() )
			{
				// Set email address.
				$User->Email($token['memory']);

				// Reset token/memory.
				$User->ClearToken();

				// API response
				$Response->Send(200, RESP_OK, array(
					'response' => true
				));
			}
			else
			{
				$Response->Send(200, RESP_ERR, array(
					'error' => 'The submitted token has expired.'
				));
			}
		}
		else
		{
			$Response->Send(200, RESP_OK, array(
				'response' => false
			));
		}
	}

	$Response->Send(200, RESP_ERR, array(
		'error' => 'The email address does not appear to be registered.'
	));
}
elseif ( API_METHOD == 'changepassword' )
{
	api_expectations(array('email', 'oldpassword', 'newpassword'));

	if ( $User->Set($request['email']) )
	{
		// Encode submitted passwords so we can compare.
		$request['oldpassword'] = $Security->Hash($request['oldpassword'], 64);

		if ( $User->Password() === $request['oldpassword'] )
		{
			if ( strlen($request['newpassword']) < 5 || strlen($request['newpassword']) > 128 )
			{
				$Response->Send(200, RESP_ERR, array(
					'error' => 'Please provide a password between 5 and 128 characters in length.'
				));
			}

			$request['newpassword'] = $Security->Hash($request['newpassword'], 64);

			if ( $request['newpassword'] === $request['oldpassword'] )
			{
				$Response->Send(200, RESP_ERR, array(
					'error' => 'You cannot reuse your password.'
				));
			}

			$User->Password($request['newpassword']);

			// API response
			$Response->Send(200, RESP_OK, array(
				'response' => true
			));
		}

		$Response->Send(200, RESP_ERR, array(
			'error' => 'The password is incorrect for this user.'
		));
	}

	$Response->Send(200, RESP_ERR, array(
		'error' => 'The email address does not appear to be registered.'
	));
}
elseif ( API_METHOD == 'checkpassword' )
{
	api_expectations(array('email', 'password'));

	if ( $User->Set($request['email']) )
	{
		$password = $Security->Hash($request['password'], 64);

		if ( $User->Password() === $password )
		{
			$session = $User->Session($Application->ID());

			$Response->Send(200, RESP_OK, array(
				'response' => true
			));
		}
		else
		{
			$Response->Send(200, RESP_OK, array(
				'response' => false
			));
		}
	}

	$Response->Send(200, RESP_ERR, array(
		'error' => 'The email address does not appear to be registered.'
	));
}
elseif ( API_METHOD == 'register' )
{
	api_expectations(array('email', 'password'));

	if ( strlen($request['password']) < 5 || strlen($request['password']) > 128 )
	{
		$Response->Send(200, RESP_ERR, array(
			'error' => 'Please provide a password between 5 and 128 characters in length.'
		));
	}

	if ( $User->Set($request['email']) )
	{
		$Response->Send(200, RESP_ERR, array(
			'error' => 'The given email address has already been registered.'
		));
	}

	$resp = $User->Create($request['email'], $request['password']);

	if ( $resp )
	{
		$Response->Send(200, RESP_OK, array(
			'response' => $resp['hash']
		));
	}

	$Response->Send(200, RESP_ERR, array(
		'error' => 'We encountered a problem while attempting to register your address. Please try again shortly.'
	));
}
elseif ( API_METHOD == 'registered' )
{
	api_expectations(array('email'));

	if ( $User->Set($request['email']) )
	{
		$Response->Send(200, RESP_OK, array(
			'response' => true
		));
	}

	$Response->Send(200, RESP_OK, array(
		'response' => false
	));
}
elseif ( API_METHOD == 'requestpassword' )
{
	// Reset Password, Part 1: Confirmation Email w/ Link.
	api_expectations(array('email', 'mailbody'));

	if ( $User->Set($request['email']) )
	{
		// Does the application have a custom mail_from set?
		$from = CFG_MAIL_FROM;
		if ( $Application->mailFrom() )
		{
			$from = $Application->mailFrom();
		}

		// Generate a one-use token for authorizing this change.
		$token = strtoupper($Security->randHash(32));
		$User->Token(array(
			'token' => $token,
			'memory' => 'RESET_PASSWORD',
			'expires' => CFG_TOKEN_EXPIRES
		));

		// Replace %token% in mailbody with the necessary authorization code.
		$request['mailbody'] = trim(filter_var($request['mailbody'], FILTER_SANITIZE_STRING));
		$request['mailbody'] = str_replace('%token%', $token, $request['mailbody']);

		if(isset($request['mailsubject'])) $request['subject'] = $request['mailsubject'];
		if(!isset($request['subject'])) $request['subject'] = 'Resetting Your ' . $Application->Name() . ' Password';
		$request['subject'] = trim(filter_var($request['subject'], FILTER_SANITIZE_STRING));

		// Notify user of the address change.
		$Mailing->Send($from, $request['email'], $request['subject'], $request['mailbody']);

		// API response
		$Response->Send(200, RESP_OK, array(
			'response' => true
		));
	}

	$Response->Send(200, RESP_ERR, array(
		'error' => 'The email address does not appear to be registered.'
	));
}
elseif ( API_METHOD == 'setpassword' )
{
	// Reset Password, Part 2: Email Confirmed, Set Password
	api_expectations(array('email', 'token', 'password'));

	if ( $User->Set($request['email']) )
	{
		$token = $User->Token();

		if ( $token['token'] === $request['token'] )
		{
			if ( $token['expires'] > time() )
			{
				if ( strlen($request['password']) < 5 || strlen($request['password']) > 128 )
				{
					$Response->Send(200, RESP_ERR, array(
						'error' => 'Please provide a password between 5 and 128 characters in length.'
					));
				}

				$request['password'] = $Security->Hash($request['password'], 64);

				// Set password.
				$User->Password($request['password']);

				// Reset token/memory.
				$User->ClearToken();

				// API response
				$Response->Send(200, RESP_OK, array(
					'response' => true
				));
			}
			else
			{
				$Response->Send(200, RESP_ERR, array(
					'error' => 'The submitted token has expired.'
				));
			}
		}
		else
		{
			$Response->Send(200, RESP_ERR, array(
				'error' => 'The submitted token is invalid.'
			));
		}
	}

	$Response->Send(200, RESP_ERR, array(
		'error' => 'The email address does not appear to be registered.'
	));
}
elseif ( API_METHOD == 'sessions' )
{
	api_expectations(array('email', 'session_id'));

	if ( $User->Set($request['email']) )
	{
		$sessions = $User->Sessions($Application->ID());

		foreach ( $sessions as $session )
		{
			if ( $session['id'] == $request['session_id'] )
			{
				$Response->Send(200, RESP_OK, array(
					'response' => $sessions
				));
			}
		}

		$Response->Send(200, RESP_ERR, array(
			'error' => 'Please provide a valid session identifier.'
		));
	}

	$Response->Send(200, RESP_ERR, array(
		'error' => 'The email address does not appear to be registered.'
	));
}
elseif ( API_METHOD == 'signedin' )
{
	if ( isset($_COOKIE['user_id']) && isset($_COOKIE['session_id']) && !isset($request['user_id']) && !isset($request['session_id']) )
	{
		$request['user_id'] = trim(substr($_COOKIE['user_id'], 0, 128));
		$request['session_id'] = trim(substr($_COOKIE['session_id'], 0, 64));
	}
	api_expectations(array('user_id', 'session_id'));

	if ( $User->Set($request['user_id']) )
	{
		$session = $User->Sessions($Application->ID(), $request['session_id']);

		if( $session )
		{
			$Response->Send(200, RESP_OK, array(
				'response' => true
			));
		}

		$Response->Send(200, RESP_ERR, array(
			'response' => false
		));
	}

	$Response->Send(200, RESP_ERR, array(
		'error' => 'Please provide a valid user identifier.'
	));
}
elseif ( API_METHOD == 'signin' )
{
	api_expectations(array('email', 'password'));

	if ( $User->Set($request['email']) )
	{
		$password = $Security->Hash($request['password'], 64);

		if ( $User->Password() === $password )
		{
			$session = $User->Session($Application->ID());

			$Response->Send(200, RESP_OK, array(
				'response' => array(
					'user_id' => $User->Hash(),
					'session_id' => $session
				)
			));
		}
		else
		{
			$Response->Send(200, RESP_ERR, array(
				'error' => 'The password is incorrect for this user.'
			));
		}
	}

	$Response->Send(200, RESP_ERR, array(
		'error' => 'The email address does not appear to be registered.'
	));
}
elseif ( API_METHOD == 'signout' )
{
	api_expectations(array('email', 'session_id'));

	if ( $User->Set($request['email']) )
	{
		if ( $User->Sessions($Application->ID(), $request['session_id']) )
		{
			$User->sessionDelete($Application->ID(), $request['session_id']);

			$Response->Send(200, RESP_OK, array(
				'response' => true
			));
		}

		$Response->Send(200, RESP_ERR, array(
			'error' => 'Please provide a valid session identifier.'
		));
	}

	$Response->Send(200, RESP_ERR, array(
		'error' => 'The email address does not appear to be registered.'
	));
}
elseif ( API_METHOD == 'herp' )
{
	$Response->Send(200, RESP_OK, array(
		   'herp' => 'derp'
	));
}