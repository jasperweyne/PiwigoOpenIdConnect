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
function oidc_retrieve(OpenIDConnectClient $oidc, $force_registration = false) {
	global $conf;
	$config = $conf['OIDC'];

	// Fetch user data
	$sub = $oidc->requestUserInfo('sub');
	$email = $oidc->requestUserInfo('email');
	$name = get_preferred_username($oidc);

	$sub = $oidc->requestUserInfo('sub');
	$query = '
		SELECT `user_id` AS id
		FROM ' . OIDC_TABLE . '
		WHERE `sub` = \'' . pwg_db_real_escape_string($sub) . '\';';
	$row = pwg_db_fetch_assoc(pwg_query($query));

	$name = get_preferred_username($oidc);
	$email = $oidc->requestUserInfo('email');

	// If the user is not found, try to register
	if (empty($row['id'])) {
		if ($config['register_new_users'] || $force_registration) {
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
	$_SESSION[OIDC_SESSION] = json_encode($token);

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

function oidc_logout()
{
	logout_user();
	redirect_auth() or redirect('identification.php');
}
?>