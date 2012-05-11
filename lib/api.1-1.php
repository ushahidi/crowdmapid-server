<?php

if(!defined('LOADED_SAFELY')) die('You cannot access this file directly.');

if ( API_METHOD == 'user/site' )
{
	if ( HTTP_METHOD == 'GET' )
	{
		// Check if a user has been added to a site.
		api_expectations(array('email', 'session_id'));
	}
	elseif ( HTTP_METHOD == 'POST' )
	{
		// Add a user to a site.
		api_expectations(array('email', 'session_id', 'url'));
	}
	elseif ( HTTP_METHOD == 'DELETE' )
	{
		// Remove a user from a site.
		api_expectations(array('email', 'session_id', 'url'));
	}
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
/*
elseif ( API_METHOD == 'user/password' )
{
	if ( HTTP_METHOD == 'GET' )
	{
		// Check a user's password.
	}
	elseif ( HTTP_METHOD == 'POST' )
	{
		// Set a user's password.
	}
	elseif ( HTTP_METHOD == 'PUT' )
	{
		// Update a user's password.
	}
}
*/
elseif ( API_METHOD == 'register' )
{
	api_expectations(array('email', 'password'));
}
elseif ( API_METHOD == 'registered' )
{
	api_expectations(array('email'));
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
	$Response->Send(200, RESP_OK, array(
		   'herp' => 'derp'
	));
}
elseif ( API_METHOD == 'signin' )
{
	api_expectations(array('email', 'password'));
}
elseif ( API_METHOD == 'signout' )
{
	api_expectations(array('email', 'session_id'));
}








// The following methods are not yet supported, but are planned:

elseif ( API_METHOD == 'two_factor_register' )
{
	// Register a two factor authentication method (i.e. YubiKey, Google Authenticator, etc.)
}
elseif ( API_METHOD == 'two_factor_unregister' )
{
	// Remove a two factor authentication method
}
elseif ( API_METHOD == 'badges' )
{
	// List badges a user has earned.
}
elseif ( API_METHOD == 'badge_grant' )
{
	// Grant a badge to a user.
}
elseif ( API_METHOD == 'badge_revoke' )
{
	// Revoke a player's badge.
}
elseif ( API_METHOD == 'profile' )
{
	// Get a user's public profile.
}
elseif ( API_METHOD == 'leaders' )
{
	/*
	Get a sorted list of the community leaders. "Leadership" is calculated
	by the number of sites the user is involved with, the number of incidents
	submitted, accepted and verified, and the number of badges the player has
	earned.
	*/
}