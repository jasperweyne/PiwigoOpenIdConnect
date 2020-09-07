<?php

defined('PHPWG_ROOT_PATH') or die('Hacking attempt!');

require __DIR__ . '/vendor/autoload.php';

use Jumbojett\OpenIDConnectClient;

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

function can_authorization_grant() {
	global $conf;
	return $conf['OIDC']['authorization_code_flow'];
}

function can_resource_owner_credentials_grant() {
	global $conf;
	return $conf['OIDC']['password_flow'];
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
		if ($conf['OIDC']['register_new_users']) {
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