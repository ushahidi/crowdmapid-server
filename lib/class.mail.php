<?php defined('LOADED_SAFELY') or die('You cannot access this file directly.');

/**
 * CrowdmapID Mail Management Class
 *
 * @package    CrowdmapID
 * @author     Ushahidi Team <team@ushahidi.com>
 * @copyright  Ushahidi - http://www.ushahidi.com
 * @license    http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License (LGPL)
 */

class Mailing {

	function Send($from, $to, $subject, $message, $bypass = false)
	{
		if( is_array($to) )
		{
			// TODO: Add support for multiple destination addresses.
		}
		else
		{
			if ( $bypass == false && defined('CFG_USE_SENDGRID') && CFG_USE_SENDGRID === true )
			{
				$params = array(
					'api_user' => CFG_SENDGRID_USER,
					'api_key'  => CFG_SENDGRID_KEY,
					'from'     => $from,
					'to'       => $to,
					'subject'  => $subject,
					'text'     => $message
				);

				$session = curl_init('https://sendgrid.com/api/mail.send.json');

				if ( $session )
				{
					curl_setopt($session, CURLOPT_POST, true);
					curl_setopt($session, CURLOPT_POSTFIELDS, $params);

					curl_setopt($session, CURLOPT_TIMEOUT, 3);

					curl_setopt($session, CURLOPT_HEADER, false);
					curl_setopt($session, CURLOPT_RETURNTRANSFER, true);

					$response = json_decode(curl_exec($session));
					$http_status = (int)curl_getinfo($session, CURLINFO_HTTP_CODE);
					@curl_close($session);

					if ( $http_status == 200 && $response->message !== 'error' )
					{
						// Send was successful.
						return true;
					}
					else
					{
						// Sendgrid call failed; try falling back to standard mail().
						return $this->Send($from, $to, $subject, $message, true);
					}
				}

				return false;
			}
			else
			{
				mail($to, $subject, $message, "From: {$from}");
			}
		}

	}

}

$Mailing = new Mailing();
