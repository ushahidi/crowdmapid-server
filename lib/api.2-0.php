<?php defined('LOADED_SAFELY') or die('You cannot access this file directly.');

$api_collection   = (isset($struct[0]) ? $struct[0] : '');
$api_user         = (isset($struct[1]) ? $struct[1] : '');
$api_action       = (isset($struct[2]) ? $struct[2] : '');
$api_action_value = (isset($struct[3]) ? $struct[3] : '');

if($api_collection == 'user') {

	if($api_user) { // /user/:user_id

		if(!$User->Set($api_user)) {
			$Response->Send(404, RESP_ERR, array(
				'error' => 'That user does not exist.'
			));
		}

		if($api_action) { // /user/:user_id/:action

			if($api_action == 'emails') { // /user/:user_id/email

				if($api_action_value) { // /user/:user_id/email/:email

					if(HTTP_METHOD == 'GET') {

						if($User->Set($api_user)) {
							$emails = $User->Emails();
							foreach($emails as $email) {
								if($email['email'] == $api_action_value) {
									$Response->Send(200, RESP_OK, array());
									break;
								}
							}

							$Response->Send(404, RESP_ERR, array(
								'error' => 'That email address is not registered or does not belong to this user.'
							));
						} else {
							$Response->Send(404, RESP_ERR, array(
								'error' => 'That user does not exist.'
							));
						}

					} elseif(HTTP_METHOD == 'PUT') {

						// Make the specified address the new master address for the account.
						if($User->Set($api_user)) {
							$resp = true;

							if(isset($request['primary'])) {
								$resp = $User->emailAssignMaster($api_action_value);
							}

							if($resp === TRUE && isset($request['confirmed'])) {
								$resp = $User->emailConfirm($api_action_value, (int)$request['confirmed']);
							}

							if($resp === TRUE) {
								$Response->Send(200, RESP_OK, array());
							} else {
								$Response->Send(404, RESP_ERR, array('error' => $resp));
							}
						} else {
							$Response->Send(404, RESP_ERR, array(
								'error' => 'That user does not exist.'
							));
						}

					} elseif(HTTP_METHOD == 'DELETE') {

						// Delete the specified email address from the account.
						if($User->Set($api_user)) {
							$resp = $User->emailRemove($api_action_value);
							if($resp === TRUE) {
								$Response->Send(200, RESP_OK, array());
							} else {
								$Response->Send(404, RESP_ERR, array('error' => $resp));
							}
						} else {
							$Response->Send(404, RESP_ERR, array(
								'error' => 'That user does not exist.'
							));
						}

					}

				} else { // /user/:user_id/email

					if(HTTP_METHOD == 'GET') {

						// Get a list of email addresses associated with the account.
						if($User->Set($api_user)) {
							$Response->Send(200, RESP_OK, array(
								'emails' => $User->Emails()
							));
						} else {
							$Response->Send(404, RESP_ERR, array(
								'error' => 'That user does not exist.'
							));
						}

					} elseif(HTTP_METHOD == 'POST') {

						api_expectations(array('email'));

						$confirmed = (isset($request['confirmed']) ? true : false);
						$primary = (isset($request['primary']) ? true : false);

						if($User->Set($api_user) && filter_var($request['email'], FILTER_VALIDATE_EMAIL)) {
							if($User->emailAdd($request['email'], $primary, $confirmed)) {
								$Response->Send(200, RESP_OK, array());
							}
						}

						$Response->Send(400, RESP_ERR, array(
							'error' => 'This email address is already registered to another account.'
						));

					}

				}

			} elseif($api_action == 'password') { // /user/:user_id/password

				if(HTTP_METHOD == 'POST' || HTTP_METHOD == 'PUT') {

					if (strlen($request['password']) < 5 || strlen($request['password']) > 128) {
						$Response->Send(200, RESP_ERR, array(
							'error' => 'Please provide a password between 5 and 128 characters in length.'
						));
					}

					if($User->Password($request['password'])) {
						$Response->Send(200, RESP_OK, array());
					}

					$Response->Send(500, RESP_ERR, array(
						'error' => 'Password change failed.'
					));
				}

			} elseif($api_action == 'avatar') { // /user/:user_id/avatar

				if(HTTP_METHOD == 'GET') {
					$avatar = $User->Avatar();
					if(!$avatar) $avatar = 'http://www.gravatar.com/avatar/' . md5(strtolower(trim($User->Email())));

					$Response->Send(200, RESP_OK, array(
						'avatar' => $avatar
					));
				} elseif(HTTP_METHOD == 'POST' || HTTP_METHOD == 'PUT') {
					$User->Avatar($request['avatar']);
					$Response->Send(200, RESP_OK, array());
				}

			} elseif($api_action == 'sessions') { // /user/:user_id/session

				if(HTTP_METHOD == 'GET') {
					if($api_action_value && strlen($api_action_value) == 64) { // /user/:user_id/session/:session_id

						// Session valid, return OK.
						if($User->Sessions($Application->ID(), $api_action_value)) {
							$Response->Send(200, RESP_OK, array());
						}

						// Session invalid; return 404.
						$Response->Send(404, RESP_ERR, array());

					} else {

						// Return current session hash.
						$Response->Send(200, RESP_OK, array(
							'session' => $User->Session($Application->ID())
						));

					}

				} elseif(HTTP_METHOD == 'POST') {
					// Sending a POST to /session will generate a new app session hash.
					$session = $User->Sessions($Application->ID(), $api_action_value);
					if($User->sessionDelete($Application->ID(), $session[0]['id'])) {
						$Response->Send(200, RESP_OK, array(
							'session' => $User->Session($Application->ID())
						));
					}
				} elseif(HTTP_METHOD == 'PUT') {
					// Refresh/extend the expiration date on a session.
					if($session = $User->Session($Application->ID(), TRUE)) {
						$Response->Send(200, RESP_OK, array(
							'session' => $session
						));
					}
				}

			} elseif($api_action == 'badges') { // /user/:user_id/badge

				if($api_action_value) { // /user/:user_id/badge/:badge_id

					if(HTTP_METHOD == 'GET') {

						$Response->Send(200, RESP_OK, array(
							'badge' => $User->Badge($Application->ID(), $api_action_value)
						));

					} elseif(HTTP_METHOD == 'DELETE') {

						$Response->Send(200, RESP_OK, array(
							'badge' => $User->Badge($Application->ID(), $api_action_value, 'DELETE')
						));

					} elseif(HTTP_METHOD == 'PUT') {

						api_expectations(array('title','description','graphic','url','category'));

						$User->Badge($Application->ID(), $api_action_value, array(
							'title'       => $request['title'],
							'description' => $request['description'],
							'graphic'     => $request['graphic'],
							'url'         => $request['url'],
							'category'    => $request['category']
						));

						$Response->Send(200, RESP_OK, array());

					}

				} else {

					if(HTTP_METHOD == 'GET') {

						$returnAll = (isset($request['all']) ? true : false);

						$Response->Send(200, RESP_OK, array(
							'badges' => $User->Badges($Application->ID(), $returnAll)
						));

					} elseif(HTTP_METHOD == 'POST') {

						api_expectations(array('badge','title','description','graphic','url','category'));

						$User->Badge($Application->ID(), $request['badge'], array(
							'title'       => $request['title'],
							'description' => $request['description'],
							'graphic'     => $request['graphic'],
							'url'         => $request['url'],
							'category'    => $request['category']
						));

						$Response->Send(200, RESP_OK, array());

					}

				}

			} elseif($api_action == 'store') { // /user/:user_id/store

				if($api_action_value) {

					if(HTTP_METHOD == 'GET') {

						$Response->Send(200, RESP_OK, array(
							'response' => (string)$User->Storage($Application->ID(), $api_action_value)
						));

					} elseif(HTTP_METHOD == 'POST' OR HTTP_METHOD =='PUT') {

						api_expectations(array('value'));

						if($User->Storage($Application->ID(), $api_action_value, $request['value'])) {
							$Response->Send(200, RESP_OK, array());
						} else {
							$Response->Send(500, RESP_ERR, array(
								'error' => 'There was a problem storing your data.'
							));
						}

					} elseif(HTTP_METHOD == 'DELETE') {

						$User->Storage($Application->ID(), $api_action_value, false);
						$Response->Send(200, RESP_OK, array());

					}

				}

			} elseif($api_action == 'security') { // /user/:user_id/security

				// TODO: Support two factor authentication through YubiKey and Google

				if($api_action_value == 'yubikey') { // /user/:user_id/security/yubikey

				} elseif($api_action_value == 'google') { // /user/:user_id/security/google

				}

			} elseif($api_action == 'social') { // /user/:user_id/social

				if($api_action_value == 'facebook') { // /user/:user_id/social/facebook

				} elseif($api_action_value == 'twitter') { // /user/:user_id/social/twitter

				} elseif($api_action_value == 'instagram') { // /user/:user_id/social/instagram

				} elseif($api_action_value == 'foursquare') { // /user/:user_id/social/foursquare

				} else {

					$Response->Send(404, RESP_ERR, array(
						'error' => 'You did not target a valid social network. Valid networks include Facebook, Twitter, Instagram and Foursquare.'
					));

				}

			} elseif($api_action == 'challenge') { // /user/:user_id/challenge

				if($api_action_value == 'question') {

					if(HTTP_METHOD == 'GET') {

						// Return the question.
						$Response->Send(200, RESP_OK, array(
							'question' => $User->Question()
						));

					} elseif(HTTP_METHOD == 'POST' OR HTTP_METHOD == 'PUT') {

						api_expectations(array('question'));

						if(isset($request['question']) && strlen($request['question'])) {

							// Set the question.
							if($User->Question($request['question'])) {
								$Response->Send(200, RESP_OK, array());
							}

							$Response->Send(500, RESP_ERR, array(
								'error' => 'There was a problem setting your challenge question.'
							));

						} else {

							$Response->Send(500, RESP_ERR, array(
								'error' => 'You must provide a challenge question.'
							));

						}

					}

				} elseif($api_action_value == 'answer') {

					api_expectations(array('answer'));

					if(HTTP_METHOD == 'POST' OR HTTP_METHOD == 'PUT') {

						$request['answer'] = $Security->Hash(trim(strtolower($request['answer'])), 128);
						$User->Answer($request['answer']);
						$Response->Send(200, RESP_OK, array());

					}

				} elseif($api_action_value) {

					// Convert to lowercase, trim and hash the answer attempt.
					$request['answer'] = $Security->Hash(trim(strtolower($api_action_value)), 128);

					// Compare the 128bit hash of the existing answer and the incoming attempt.
					if($User->Answer() == $request['answer']) {
						// Success!
						$Response->Send(200, RESP_OK, array());
					}

					// Failure.
					$Response->Send(404, RESP_ERR, array(
						'error' => 'Answer is incorrect.'
					));

				}

			}

		} else {

			if(HTTP_METHOD == 'GET') {

				$avatar = $User->Avatar();
				if(!$avatar) $avatar = 'http://www.gravatar.com/avatar/' . md5(strtolower(trim($User->Email())));

				// Get information about user.
				$Response->Send(200, RESP_OK, array(
					'user' => array(
						'user_id'                     => $User->Hash(),
						'session_id'                  => $User->Session($Application->ID()),
						'registered'                  => $User->Registered(),
						'password_last_changed'       => $User->passwordChanged(),
						'password_challenge_question' => $User->Question(),
						'avatar'                      => $avatar,
						'emails'                      => $User->Emails(),
						'badges'                      => $User->Badges($Application->ID(), true),
						'storage'                     => $User->Storage($Application->ID())
					)
				));

			} elseif(HTTP_METHOD == 'DELETE') {

				if($Application->adminAccess()) {
					// Delete the user.
				} else {
					$Response->Send(403, RESP_ERR, array(
						'error' => 'This application is not permitted to access this resource.'
					));
				}

			}

		}

	} else {

		if(HTTP_METHOD == 'GET') {

			if($Application->adminAccess()) {
				// Get a list of users.
				$Response->Send(200, RESP_OK, array(
					'users' => listUsers()
				));
			} else {
				$Response->Send(403, RESP_ERR, array(
					'error' => 'This application is not permitted to access this resource.'
				));
			}

		} elseif(HTTP_METHOD == 'POST') {

			api_expectations(array('email', 'password'));

			if (strlen($request['password']) < 5 || strlen($request['password']) > 128)
			{
				$Response->Send(200, RESP_ERR, array(
					'error' => 'Please provide a password between 5 and 128 characters in length.'
				));
			}

			if ($User->Set($request['email']))
			{
				$Response->Send(200, RESP_ERR, array(
					'error' => 'The given email address has already been registered.'
				));
			}

			$resp = $User->Create($request['email'], $request['password']);

			if ($resp)
			{
				$User->Set($request['email']);
				$session = $User->Session($Application->ID());

				$Response->Send(200, RESP_OK, array(
					'user_id'    => $resp['hash'],
					'session_id' => $session
				));
			}

			$Response->Send(200, RESP_ERR, array(
				'error' => 'We encountered a problem while attempting to register your address. Please try again shortly.'
			));

		}
	}

} elseif($api_collection = 'whatever') {

	// Reserved for future use.

}
