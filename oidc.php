<?php
/*
   Copyright 2020-2021 Jasper Weyne

   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at

       http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.
*/

defined('PHPWG_ROOT_PATH') or die('Hacking attempt!');

require __DIR__ . '/vendor/autoload.php';

use Jumbojett\OpenIDConnectClient;

/**
 * Create an instance of OpenIDConnectClient with the configured settings
 */
function get_oidc_client() {
	global $conf;
	$config = $conf['OIDC'];
	
	// Create OIDC client
	$oidc = new OpenIDConnectClient(
		$config['issuer_url'] ?? '',
		$config['client_id'] ?? '',
		$config['client_secret'] ?? ''
	);

	// Set verification bits
	$oidc->setVerifyHost($config['verify_host']);
	$oidc->setVerifyPeer($config['verify_peer']);

	// Set HTTP proxy
	if (!empty($config['proxy'])) {
		$oidc->setHttpProxy($config['proxy']);
	}

	// Set auth params
	if ($array = json_decode($config['authparam'], true) !== null) {
		$oidc->addAuthParam($array);
	}

	// Set scopes
	$oidc->addScope(explode(' ', $config['scope']));

	return $oidc;
}

/**
 * Return whether the authorization code flow is enabled from config
 */
function can_authorization_grant() {
	global $conf;
	return $conf['OIDC']['authorization_code_flow'];
}

/**
 * Return whether the resource owner credentials flow is enabled from config
 */
function can_resource_owner_credentials_grant() {
	global $conf;
	return $conf['OIDC']['password_flow'];
}

/**
 * Redirect a user to the auth.php page if enabled
 * If disabled, returns false
 */ 
function redirect_auth()
{
	if (can_authorization_grant()) {
		redirect(OIDC_PATH . 'auth.php');
		return true;
	}

	return false;
}

/**
 * Get the preferred username of the resource owner
 * Resource owner is authenticated by the access token, stored in $oidc
 */
function get_preferred_username(OpenIDConnectClient $oidc) {
	global $conf;
	$config = $conf['OIDC'];

	// Note: this value must be unique, therefore we use sub
	$name = $oidc->requestUserInfo('sub');
	if (!empty($config['preferred_username'] && $preferred = $oidc->requestUserInfo($config['preferred_username']))) {
		$name = $preferred;
	}

	return $name;
}

/// Login/logout methods
/**
 * Retrieve Piwigo user associated with the current OIDC resource owner
 * Resource owner is authenticated by the access token, stored in $oidc
 */
function oidc_retrieve(OpenIDConnectClient $oidc, $force_registration = false) {
	global $conf;
	$config = $conf['OIDC'];

	// Fetch user data
	$sub = $oidc->requestUserInfo('sub');
	$email = $oidc->requestUserInfo('email');
	$name = get_preferred_username($oidc);

	// Try to find the resource owner in the OIDC user table
	$query = '
		SELECT `user_id` AS id
		FROM ' . OIDC_TABLE . '
		WHERE `sub` = \'' . pwg_db_real_escape_string($sub) . '\';';
	$row = pwg_db_fetch_assoc(pwg_query($query));

	// If the user is not found, try to register
	if (empty($row['id'])) {
		if ($config['register_new_users'] || $force_registration) {
			// check if user is member of allowed groups, if any is set
			$groups_claim = $config['groups_claim'] ?: 'groups';
			$groups = $oidc->requestUserInfo($groups_claim);
			$allowed_groups = $config['allowed_groups'];
			if(!empty($allowed_groups)) {
				if(empty($groups)){
					return null;
				}
				$allowed_groups_array = preg_split("/\s+/", $allowed_groups);
				$allowed = false;
				foreach ($allowed_groups_array as $allowed_group) {
					if (in_array($allowed_group, $groups)) {
						$allowed = true;
						break;
					}
				}
				if (!$allowed) {
					return null;
				}
			}
			// Registration is allowed, overwrite $row
			$errors = [];
			$row['id'] = register_user($name, random_pass(), $email, $config['notify_admins_on_register'], $errors, $config['notify_user_on_register']);
			single_insert(OIDC_TABLE, [
				'sub' => $sub,
				'user_id' => $row['id'],
			]);
		} else {
			// Registration is not allowed, fail
			return null;
		}
	}

	return $row['id'];
}

/**
 * Log the Piwigo user associated with the provided $token, through the current $oidc session
 */
function oidc_login(OpenIDConnectClient $oidc, $token, $remember_me)
{
	global $conf;

	// Find user in piwigo database
	$id = oidc_retrieve($oidc);
	if ($id === null) {
		return false;
	}

	$name = get_preferred_username($oidc);
	$email = $oidc->requestUserInfo('email');

	// Store access token in the session
    $encoded = array();
	if (isset($token->access_token)) {
		$encoded['access_token'] = $token->access_token;
	}
	if (isset($token->refresh_token)) {
		$encoded['refresh_token'] = $token->refresh_token;
	}
	if (isset($token->expires_in)) {
		$encoded['expires'] = time() + $token->expires_in;
	}
	$_SESSION[OIDC_SESSION] = json_encode($encoded);

	// check if user is member of allowed groups, if any is set
	$groups_claim = $oidc_config['groups_claim'] ?: 'groups';
	$groups = $oidc->requestUserInfo($groups_claim);
	$oidc_config = $conf['OIDC'];

	if (!empty($oidc_config['allowed_groups'])) {
		if (empty($groups)) {
			return false;
		}
		$allowed_groups_array = preg_split("/\s+/", $oidc_config['allowed_groups']);
		$allowed = false;
		foreach ($allowed_groups_array as $allowed_group) {
			if (in_array($allowed_group, $groups)) {
				$allowed = true;
				break;
			}
		}
		if (!$allowed) {
			return false;
		}
	}

	// Update user data from ID token data
	$fields = array($conf['user_fields']['email'], $conf['user_fields']['username']);

	$data = array();
	$data[$conf['user_fields']['id']] = $id;
	$data[$conf['user_fields']['email']] = $email;
	$data[$conf['user_fields']['username']] = $name;
	
	mass_updates(USERS_TABLE,
				array(
				'primary' => array($conf['user_fields']['id']),
				'update' => $fields
				),
				array($data));

	// Log the user in
	log_user($id, $remember_me);
	trigger_notify('login_success', stripslashes($name));

	return true;
}

/**
 * Log out the currently logged in user and redirect to the login page
 */
function oidc_logout()
{
	logout_user();
	redirect_auth() or redirect('identification.php');
}
?>
