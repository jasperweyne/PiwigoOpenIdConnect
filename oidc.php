<?php

defined('PHPWG_ROOT_PATH') or die('Hacking attempt!');

require __DIR__ . '/vendor/autoload.php';

use Jumbojett\OpenIDConnectClient;

function get_oidc_client() {
	$oidc = new OpenIDConnectClient('https://id.provider.com');
	return $oidc;
}

function can_authorization_grant() {
	$oidc = get_oidc_client();
	return false;
}

function can_resource_owner_credentials_grant() {
	$oidc = get_oidc_client();
	return false;
}

// Redirect a user to the auth.php page, which handles authorization flow
function redirect_auth()
{
	if (can_authorization_grant()) {
		redirect(OIDC_PATH . 'auth.php');
		return true;
	}

	return false;
}

/// Login/logout methods
function oidc_login($oidc, $token, $remember_me)
{
	global $conf;

	// Fetch name
	// Note: this value must be unique, therefore we use sub
	$name = $oidc->requestUserInfo('sub');

	// Find user in piwigo database
	$query = '
		SELECT ' . $conf['user_fields']['id'] . ' AS id
		FROM ' . USERS_TABLE . '
		WHERE ' . $conf['user_fields']['username'] . ' = \'' . pwg_db_real_escape_string($name) . '\';';
	$row = pwg_db_fetch_assoc(pwg_query($query));

	// If the user is not found, try to register
	if (empty($row['id'])) {
		if ($conf['allow_user_registration']) {
			// Registration is allowed, overwrite $row
			$email = $oidc->requestUserInfo('email');
			$row['id'] = register_user($name, random_pass(), $email);
		} else {
			// Registration is not allowed, fail
			return false;
		}
	}

	// Store access token in the session
	$_SESSION[OIDC_SESSION] = json_encode($token);

	// Update user data from ID token data
	// ToDo

	// Log the user in
	log_user($row['id'], $remember_me);
	trigger_notify('login_success', stripslashes($name));

	return true;
}

function oidc_logout()
{
	logout_user();
	redirect_auth() or redirect('identification.php');
}
?>