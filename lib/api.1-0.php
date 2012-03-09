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
			$Response->Send(200, RESP_OK, array(
				'response' => $User->Site($Application->ID(), $request['url'])
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
			$Response->Send(200, RESP_OK, array(
				'response' => $User->siteDelete($Application->ID(), $request['url'])
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
	api_expectations(array('oldemail', 'newemail', 'password', 'mailbody', 'subject'));
}
elseif ( API_METHOD == 'confirmemail' )
{
	api_expectations(array('email', 'token'));
}
elseif ( API_METHOD == 'setpassword' )
{
	api_expectations(array('email', 'token', 'password'));
}
elseif ( API_METHOD == 'changepassword' )
{
	api_expectations(array('email', 'oldpassword', 'newpassword'));
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

	if ( strlen($request['password']) < 5 )
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
	api_expectations(array('email', 'mailbody', 'subject'));
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