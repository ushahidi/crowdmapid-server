<?php defined('LOADED_SAFELY') or die('You cannot access this file directly.');

/**
 * CrowdmapID API v2
 *
 * @package    CrowdmapID
 * @author     Ushahidi Team <team@ushahidi.com>
 * @copyright  Ushahidi - http://www.ushahidi.com
 * @license    http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License (LGPL)
 */

$api_collection   = (isset($struct[0]) ? $struct[0] : '');
$api_user         = (isset($struct[1]) ? $struct[1] : '');
$api_action       = (isset($struct[2]) ? $struct[2] : '');
$api_action_value = (isset($struct[3]) ? $struct[3] : '');

if($api_collection == 'user') {

	if($api_user) { // /user/:user_id

		if(!$User->Set($api_user)) {

			Response::Send(404, RESP_ERR, array(
				'error' => 'That user does not exist.'
			));

		}

		if($api_action) { // /user/:user_id/:action

			if($api_action == 'emails') { // /user/:user_id/email

				if($api_action_value) { // /user/:user_id/email/:email

					if(HTTP_METHOD == 'GET') {

						$emails = $User->Emails();

						foreach($emails as $email) {
							if($email['email'] == $api_action_value) {
								Response::Send(200, RESP_OK, array());
								break;
							}
						}

						Response::Send(404, RESP_ERR, array(
							'error' => 'That email address is not registered or does not belong to this user.'
						));

					} elseif(HTTP_METHOD == 'PUT') {

						isSessionCleared($User->Hash(), true);

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
								Response::Send(200, RESP_OK, array('emails' => $User->Emails()));
							} else {
								Response::Send(404, RESP_ERR, array('error' => $resp));
							}

						} else {

							Response::Send(404, RESP_ERR, array(
								'error' => 'That user does not exist.'
							));

						}

					} elseif(HTTP_METHOD == 'DELETE') {

						isSessionCleared($User->Hash(), true);

						// Delete the specified email address from the account.
						if($User->Set($api_user)) {

							$resp = $User->emailRemove($api_action_value);

							if($resp === TRUE) {
								Response::Send(200, RESP_OK, array('emails' => $User->Emails()));
							} else {
								Response::Send(404, RESP_ERR, array('error' => $resp));
							}

						} else {

							Response::Send(404, RESP_ERR, array(
								'error' => 'That user does not exist.'
							));

						}

					}

				} else { // /user/:user_id/email

					isSessionCleared($User->Hash(), true);

					if(HTTP_METHOD == 'GET') {

						// Get a list of email addresses associated with the account.
						if($User->Set($api_user)) {

							Response::Send(200, RESP_OK, array(
								'emails' => $User->Emails()
							));

						} else {

							Response::Send(404, RESP_ERR, array(
								'error' => 'That user does not exist.'
							));

						}

					} elseif(HTTP_METHOD == 'POST') {

						api_expectations(array('email'));

						$confirmed = (isset($request['confirmed']) ? true : false);
						$primary = (isset($request['primary']) ? true : false);

						if(filter_var($request['email'], FILTER_VALIDATE_EMAIL)) {
							if($User->Set($api_user)) {

								if($User->emailAdd($request['email'], $primary, $confirmed)) {
									Response::Send(200, RESP_OK, array('emails' => $User->Emails()));
								} else {
									Response::Send(400, RESP_ERR, array(
										'error' => 'This address has already been claimed.'
									));
								}

							} else {
								Response::Send(400, RESP_ERR, array(
									'error' => 'This address has already been claimed.'
								));
							}
						} else {
							Response::Send(400, RESP_ERR, array(
								'error' => 'That does not appear to be a properly formatted address.'
							));
						}

					}

				}

			} elseif($api_action == 'password') { // /user/:user_id/password

				if(HTTP_METHOD == 'GET') {

					if(isset($request['password'])) {

						$activity = array(
							'override' => false, 'error' => null,
							'user' => $User, 'raw' => $request['password'],
							'hash' => $Security->Hash($request['password'], 128));

						Plugins::raiseEvent("method.private.password.get.pre", $activity);

						if($activity['override']) {
							if($activity['error']) {
								Response::Send(500, RESP_ERR, array(
									'error' => $activity['error']
								));
							} else {
								Response::Send(200, RESP_OK, array('user_id' => $User->Hash(), 'session_id' => $User->Session($Application->ID())));
							}
						}

						if($User->Password() == $activity['hash']) {
							Response::Send(200, RESP_OK, array('user_id' => $User->Hash(), 'session_id' => $User->Session($Application->ID())));
						} else {
							$error = 'Incorrect password.';

							if (strlen($request['password']) && strtoupper($request['password']) == $request['password']) {
								$error .= ' You appear to have your caps lock on.';
							}

							Response::Send(500, RESP_ERR, array(
								'error' => $error
							));
						}

					} else {

						Response::Send(200, RESP_OK, array('lastChanged' => $User->passwordChanged()));

					}

				} elseif(HTTP_METHOD == 'POST' || HTTP_METHOD == 'PUT') {

					isSessionCleared($User->Hash(), true);

					api_expectations(array('password'));

					if (strlen($request['password']) < 5 || strlen($request['password']) > 128) {
						Response::Send(200, RESP_ERR, array(
							'error' => 'Please provide a password between 5 and 128 characters in length.'
						));
					}

					if($User->Password($request['password'])) {
						Response::Send(200, RESP_OK, array());
					}

					Response::Send(500, RESP_ERR, array(
						'error' => 'Password change failed.'
					));

				}

			} elseif($api_action == 'avatar') { // /user/:user_id/avatar

				if(HTTP_METHOD == 'GET') {

					$avatar = $User->Avatar();
					if(!$avatar) $avatar = 'http://www.gravatar.com/avatar/' . md5(strtolower(trim($User->Email())));

					Response::Send(200, RESP_OK, array(
						'avatar' => $avatar
					));

				} elseif(HTTP_METHOD == 'POST' || HTTP_METHOD == 'PUT') {

					isSessionCleared($User->Hash(), true);

					api_expectations(array('avatar'));

					$User->Avatar($request['avatar']);
					Response::Send(200, RESP_OK, array());

				}

			} elseif($api_action == 'phone') { // /user/:user_id/phone

				isSessionCleared($User->Hash(), true);

				if(defined('CFG_TWILIO_NUMBER') && strlen(CFG_TWILIO_NUMBER) &&
				  (defined('CFG_TWILIO_ID') && strlen(CFG_TWILIO_ID) &&
				  (defined('CFG_TWILIO_TOKEN') && strlen(CFG_TWILIO_TOKEN)) {

					require './lib/twilio/Twilio.php';
					$twilio = new Services_Twilio(CFG_TWILIO_ID, CFG_TWILIO_TOKEN);

					$phone = substr(trim(str_replace(array('+', '-'), '', filter_var($request['phone'], FILTER_SANITIZE_NUMBER_INT))), 0, 50);

					if(HTTP_METHOD == 'POST' && $api_action_value == 'confirm' && !$User->PhoneConfirmed()) {

						api_expectations(array('phone', 'code'));
						$submitted = strtoupper(substr(trim(filter_var($request['code'], FILTER_SANITIZE_STRING)), 0, 6));
						$code = strtoupper($Security->Hash("CONFIRM_PHONE_" . $User->Hash() . "_{$phone}", 6));

						if($submitted === $code) {
							$User->PhoneConfirmed(1);
							Response::Send(200, RESP_OK, array());
						} else {
							Response::Send(500, RESP_ERR, array(
								'error' => 'That confirmation code is incorrect.'
							));
						}

					} else {

						if(HTTP_METHOD == 'GET') {

							$avatar = $User->Phone();

							Response::Send(200, RESP_OK, array(
								'phone' => $User->Phone(),
								'confirmed' => $User->PhoneConfirmed()
							));

						} elseif(HTTP_METHOD == 'POST') {

							global $MySQL;

							if(strlen($phone)) {
								if($dup = $MySQL->Pull("SELECT phone FROM users WHERE phone='" . $MySQL->Clean($phone) . "' AND phone_confirmed=1 LIMIT 1;")) {
									Response::Send(500, RESP_ERR, array(
										'error' => 'That phone number has already been claimed by another user.'
									));
								}
							}

							if($phone != $User->Phone()) {
								api_expectations(array('phone'));
								$User->Phone($phone);
								$User->PhoneConfirmed(0);

								if(strlen($phone)) {
									$code = $Security->Hash("CONFIRM_PHONE_" . $User->Hash() . "_{$phone}", 6);
									$message = $twilio->account->sms_messages->create( CFG_TWILIO_NUMBER, $phone, "To confirm your phone number with " . $Application->Name() . ", please enter this code: " . $code);
								}
							}

							Response::Send(200, RESP_OK, array());

						}

					}

				} else {

					Response::Send(404, RESP_ERR, array('error' => 'Phone support is not enabled on this installation.'));

				}

			} elseif($api_action == 'sessions') { // /user/:user_id/session

				if(HTTP_METHOD == 'GET') {

					if($api_action_value) { // /user/:user_id/session/:session_id

						// Session valid, return OK.
						if(strlen($api_action_value) == 64 && $User->Sessions($Application->ID(), $api_action_value)) {
							Response::Send(200, RESP_OK);
						}

						// Session invalid; return 404.
						Response::Send(404, RESP_ERR);

					} else {

						isSessionCleared($User->Hash(), true);

						// Return current session hash.
						Response::Send(200, RESP_OK, array(
							'session' => $User->Session($Application->ID())
						));

					}

				} elseif(HTTP_METHOD == 'POST') {

					isSessionCleared($User->Hash(), true);

					// Sending a POST to /session will generate a new app session hash.
					$session = $User->Sessions($Application->ID(), $api_action_value);

					if($User->sessionDelete($Application->ID(), $session[0]['id'])) {

						Response::Send(200, RESP_OK, array(
							'session' => $User->Session($Application->ID())
						));

					}

				} elseif(HTTP_METHOD == 'PUT') {

					isSessionCleared($User->Hash(), true);

					// Refresh/extend the expiration date on a session.
					if($session = $User->Session($Application->ID(), TRUE)) {

						Response::Send(200, RESP_OK, array(
							'session' => $session
						));

					}

				}

			} elseif($api_action == 'badges') { // /user/:user_id/badge

				if($api_action_value) { // /user/:user_id/badge/:badge_id

					if(HTTP_METHOD == 'GET') {

						Response::Send(200, RESP_OK, array(
							'badge' => $User->Badge($Application->ID(), $api_action_value)
						));

					} elseif(HTTP_METHOD == 'DELETE') {

						isSessionCleared($User->Hash(), true);

						Response::Send(200, RESP_OK, array(
							'badge' => $User->Badge($Application->ID(), $api_action_value, 'DELETE')
						));

					} elseif(HTTP_METHOD == 'PUT') {

						isSessionCleared($User->Hash(), true);

						api_expectations(array('title','description','graphic','url','category'));

						$User->Badge($Application->ID(), $api_action_value, array(
							'title'       => $request['title'],
							'description' => $request['description'],
							'graphic'     => $request['graphic'],
							'url'         => $request['url'],
							'category'    => $request['category']
						));

						Response::Send(200, RESP_OK, array());

					}

				} else {

					if(HTTP_METHOD == 'GET') {

						$returnAll = (isset($request['all']) ? true : false);

						Response::Send(200, RESP_OK, array(
							'badges' => $User->Badges($Application->ID(), $returnAll)
						));

					} elseif(HTTP_METHOD == 'POST') {

						isSessionCleared($User->Hash(), true);

						api_expectations(array('badge','title','description','graphic','url','category'));

						$User->Badge($Application->ID(), $request['badge'], array(
							'title'       => $request['title'],
							'description' => $request['description'],
							'graphic'     => $request['graphic'],
							'url'         => $request['url'],
							'category'    => $request['category']
						));

						Response::Send(200, RESP_OK, array());

					}

				}

			} elseif($api_action == 'store') { // /user/:user_id/store

				isSessionCleared($User->Hash(), true);

				if($api_action_value) {

					if(HTTP_METHOD == 'GET') {

						Response::Send(200, RESP_OK, array(
							'response' => (string)$User->Storage($Application->ID(), $api_action_value)
						));

					} elseif(HTTP_METHOD == 'POST' OR HTTP_METHOD =='PUT') {

						api_expectations(array('value'));

						if($User->Storage($Application->ID(), $api_action_value, $request['value'])) {
							Response::Send(200, RESP_OK, array());
						} else {
							Response::Send(500, RESP_ERR, array(
								'error' => 'There was a problem storing your data.'
							));
						}

					} elseif(HTTP_METHOD == 'DELETE') {

						$User->Storage($Application->ID(), $api_action_value, false);
						Response::Send(200, RESP_OK, array());

					}

				}

			} elseif($api_action == 'security') { // /user/:user_id/security

				Plugins::raiseEvent("method.security", $struct);

				Response::Send(404, RESP_ERR, array(
					'error' => 'You did not target a supported security module.'
				));

			} elseif($api_action == 'social') { // /user/:user_id/social

				Plugins::raiseEvent("method.social", $struct);

				Response::Send(404, RESP_ERR, array(
					'error' => 'You did not target a supported social network.'
				));

			} elseif($api_action == 'challenge') { // /user/:user_id/challenge

				if($api_action_value == 'question') {

					if(HTTP_METHOD == 'GET') {

						// Return the question.
						Response::Send(200, RESP_OK, array(
							'question' => $User->Question()
						));

					} elseif(HTTP_METHOD == 'POST' OR HTTP_METHOD == 'PUT') {

						isSessionCleared($User->Hash(), true);

						api_expectations(array('question'));

						if(isset($request['question']) && strlen($request['question'])) {

							// Set the question.
							if($User->Question($request['question'])) {
								Response::Send(200, RESP_OK, array());
							}

							Response::Send(500, RESP_ERR, array(
								'error' => 'There was a problem setting your challenge question.'
							));

						} else {

							Response::Send(500, RESP_ERR, array(
								'error' => 'You must provide a challenge question.'
							));

						}

					}

				} elseif($api_action_value == 'answer') {

					isSessionCleared($User->Hash(), true);
					api_expectations(array('answer'));

					if(HTTP_METHOD == 'POST' OR HTTP_METHOD == 'PUT') {

						$request['answer'] = $Security->Hash(trim(strtolower($request['answer'])), 128);
						$User->Answer($request['answer']);
						Response::Send(200, RESP_OK, array());

					}

				} elseif($api_action_value) {

					// Convert to lowercase, trim and hash the answer attempt.
					$request['answer'] = $Security->Hash(trim(strtolower($api_action_value)), 128);

					// Compare the 128bit hash of the existing answer and the incoming attempt.
					if($User->Answer() == $request['answer']) {
						// Success!
						Response::Send(200, RESP_OK, array());
					}

					// Failure.
					Response::Send(404, RESP_ERR, array(
						'error' => 'Answer is incorrect.'
					));

				}

			}

		} else {

			if(HTTP_METHOD == 'GET') {

				$avatar = $User->Avatar();
				if(!$avatar) $avatar = 'http://www.gravatar.com/avatar/' . md5(strtolower(trim($User->Email())));

				if(isSessionCleared($User->Hash())) {
					// Get information about user.
					Response::Send(200, RESP_OK, array(
						'user' => array(
							'user_index'                  => $User->ID(),
							'user_id'                     => $User->Hash(),
							'session_id'                  => $User->Session($Application->ID()),
							'registered'                  => $User->Registered(),
							'password_last_changed'       => $User->passwordChanged(),
							'password_challenge_question' => $User->Question(),
							'avatar'                      => $avatar,
							'emails'                      => $User->Emails(),
							'phone'                       => array(
							                              'number' => $User->Phone(),
							                              'confirmed' => (boolean)$User->PhoneConfirmed()),
							'badges'                      => $User->Badges($Application->ID(), true),
							'storage'                     => $User->Storage($Application->ID())
						)
					));
				} else {
					// Get information about user.
					Response::Send(200, RESP_OK, array(
						'user' => array(
							'user_index'                  => $User->ID(),
							'user_id'                     => $User->Hash(),
							'registered'                  => $User->Registered(),
							'avatar'                      => $avatar,
							'badges'                      => $User->Badges($Application->ID(), true)
						)
					));
				}

			} elseif(HTTP_METHOD == 'DELETE') {

				isSessionCleared($User->Hash(), true);

				if($Application->adminAccess()) {

					$User->Delete();

					Response::Send(200, RESP_OK, array(
						'response' => true
					));

				} else {

					Response::Send(403, RESP_ERR, array(
						'error' => 'This application is not permitted to access this resource.'
					));

				}

			}

		}

	} else {

		if(HTTP_METHOD == 'GET') {

			isSessionCleared($User->Hash(), true);

			if($Application->adminAccess()) {

				// Get a list of users.
				Response::Send(200, RESP_OK, array(
					'users' => listUsers()
				));

			} else {

				Response::Send(403, RESP_ERR, array(
					'error' => 'This application is not permitted to access this resource.'
				));

			}

		} elseif(HTTP_METHOD == 'POST') {

			api_expectations(array('email', 'password'));

			if (strlen($request['password']) < 5 || strlen($request['password']) > 128) {

				Response::Send(200, RESP_ERR, array(
					'error' => 'Please provide a password between 5 and 128 characters in length.'
				));

			}

			if ($User->Set($request['email'])) {

				Response::Send(200, RESP_ERR, array(
					'error' => 'The given email address has already been registered.'
				));

			}

			if (isset($request['hash'])) {

				if ($User->Set($request['hash'])) {

					Response::Send(200, RESP_ERR, array(
						'error' => 'A user with that hash already exists.'
					));

				}

			} else {

				$request['hash'] = '';

			}

			$resp = $User->Create($request['email'], $request['password'], '', '', $request['hash']);

			if ($resp) {

				$User->Set($request['email']);
				$session = $User->Session($Application->ID());

				Response::Send(200, RESP_OK, array(
					'user_id'    => $resp['hash'],
					'session_id' => $session
				));

			}

			Response::Send(200, RESP_ERR, array(
				'error' => 'We encountered a problem while attempting to register your address. Please try again shortly.'
			));

		}
	}

} else {

	Plugins::raiseEvent("api.collection", $struct);

}
