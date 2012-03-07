<?php

if(!defined('LOADED_SAFELY')) die('You cannot access this file directly.');

if ( ( API_METHOD == 'addusertosite' || API_METHOD == 'add_user_to_site' ) )
{
	api_expectations(array('email', 'session_id', 'url'));
	//
}
elseif ( ( API_METHOD == 'usersites' || API_METHOD == 'user_sites' ) )
{
	api_expectations(array('email', 'session_id'));
}
elseif ( ( API_METHOD == 'changeemail' || API_METHOD == 'change_email' ) )
{
	api_expectations(array('oldemail', 'newemail', 'password', 'mailbody', 'subject'));
}
elseif ( ( API_METHOD == 'confirmemail' || API_METHOD == 'confirm_email' ) )
{
	api_expectations(array('email', 'token'));
}
elseif ( ( API_METHOD == 'setpassword' || API_METHOD == 'set_password' ) )
{
	api_expectations(array('email', 'token', 'password'));
}
elseif ( ( API_METHOD == 'changepassword' || API_METHOD == 'change_password' ) )
{
	api_expectations(array('email', 'oldpassword', 'newpassword'));
}
elseif ( ( API_METHOD == 'checkpassword' || API_METHOD == 'check_password' ) )
{
	api_expectations(array('email', 'password'));
}
elseif ( API_METHOD == 'register' )
{
	api_expectations(array('email', 'password'));

	if ( $User->Set($request['email']) )
	{
		$Response->Send(200, RESP_ERR, array(
			'error' => 'This email address is already in use.'
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
elseif ( ( API_METHOD == 'requestpassword' || API_METHOD == 'request_password' ) )
{
	api_expectations(array('email', 'mailbody'));
}
elseif ( API_METHOD == 'sessions' )
{
	api_expectations(array('email', 'session_id'));
}
elseif ( API_METHOD == 'signedin' )
{

}
elseif ( API_METHOD == 'signin' )
{
	api_expectations(array('email', 'password'));
}
elseif ( API_METHOD == 'signout' )
{
	api_expectations(array('email', 'session_id'));
}
elseif ( API_METHOD == 'herp' )
{
	$Response->Send(200, RESP_OK, array(
		   'herp' => 'derp'
	));
}