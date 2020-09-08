<?php
/*
Plugin Name: OpenId Connect
Version: auto
Description: This plugin provides OpenID Connect integration.
Plugin URI: auto
Author: Jasper Weyne
*/

defined('PHPWG_ROOT_PATH') or die('Hacking attempt!');

/// Define plugin constants
define('OIDC_ID',      basename(dirname(__FILE__)));
define('OIDC_PATH' ,   PHPWG_PLUGINS_PATH . OIDC_ID . '/');
define('OIDC_ADMIN',   get_root_url() . 'admin.php?page=plugin-' . OIDC_ID);
define('OIDC_SESSION', OIDC_ID); 

require(OIDC_PATH . 'oidc.php');

/// Link event handlers
// The menubar is cached in the block manager before applying the full template
add_event_handler('plugins_loaded', 'oidc_init'); // earliest init possible
add_event_handler('load_profile_in_template', 'oidc_profile');
add_event_handler('blockmanager_apply', 'override_login_link');
add_event_handler('loc_begin_password', 'oidc_redirect');
add_event_handler('loc_begin_register', 'oidc_redirect');
add_event_handler('try_log_user', 'password_login', 0, 4);
add_event_handler('loc_begin_identification', 'oidc_identification');
add_event_handler('user_init', 'refresh_login');
add_event_handler('get_admin_plugin_menu_links', 'oidc_admin_link');

/// Utility methods
/**
 * Generate a random password
 * based on: https://stackoverflow.com/questions/4356289/php-random-string-generator/31107425#31107425
 */
function random_pass($length = 16, $keyspace = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_-=+;:,.?")
{
	$pieces = [];
    $max = mb_strlen($keyspace, '8bit') - 1;
    for ($i = 0; $i < $length; ++$i) {
        $pieces []= $keyspace[random_int(0, $max)];
    }
    return implode('', $pieces);
}

/**
 * Check whether an access token or OpenID token isn't expired.
 */
function is_token_unexpired($access_token): bool
{
	return isset($access_token->expires_at) && $access_token->expires_at >= time();
}

/// Event handlers
function oidc_init()
{
	global $conf;
	$conf['OIDC'] = safe_unserialize($conf['OIDC']);

	if (file_exists(OIDC_PATH . 'conf.php')) {
		$overrideConfig = include(OIDC_PATH . 'conf.php');
		$conf['OIDC'] = array_merge($conf['OIDC'], $overrideConfig);
	}
}

/**
 * Removes the Profile/Registration block.
 */
function oidc_profile()
{
	global $template;

	if (isset($_SESSION[OIDC_SESSION])) {
		$template->assign('SPECIAL_USER', true);
	}
}

/**
 * Rewrite the login link to link to the authorization code flow
 */
function override_login_link()
{
	global $template;

	// If U_LOGIN is present and authorization grant is enabled, replace U_LOGIN link
	if ($template->get_template_vars('U_LOGIN') !== null && can_authorization_grant())
		$template->assign('U_LOGIN', OIDC_PATH . 'auth.php');

	// If resource owner credentials grant is enabled, set U_REGISTER to registration url
	if (can_resource_owner_credentials_grant()) {
		if (!empty($conf['OIDC']['registration_url']))
			$template->assign('U_REGISTER', $conf['OIDC']['registration_url']);
		else
			$template->clear_assign('U_REGISTER');
	}
}

function oidc_identification()
{
	global $template;
	global $conf;

	redirect_auth();

	if (can_resource_owner_credentials_grant()) {
		if ($template->get_template_vars('U_LOST_PASSWORD') !== null) {
			// Password lost URL
			if (!empty($conf['OIDC']['password_reset_url']))
				$template->assign('U_LOST_PASSWORD', $conf['OIDC']['password_reset_url']);
			else
				$template->clear_assign('U_LOST_PASSWORD');

			// Registration URL
			if (!empty($conf['OIDC']['registration_url']))
				$template->assign('U_REGISTER', $conf['OIDC']['registration_url']);
			else
				$template->clear_assign('U_REGISTER');
		}
	}
}

function oidc_redirect()
{
	redirect_auth();

	if (can_resource_owner_credentials_grant()) {
		redirect(get_root_url() . 'identification.php');
	}
}

/**
 * Refresh the user, log out if expired
 */
function refresh_login($user)
{
	global $conf;

	// If disabled, don't attempt to refresh
	if (!can_authorization_grant() && !can_resource_owner_credentials_grant()) {
		return;
	}

	// If user is not logged in, don't attempt to refresh
	if ($user['id'] == $conf['guest_id']) {
		return;
	}

	// Retrieve access token for current session
	$json = $_SESSION[OIDC_SESSION];

	// If no access token was found, reject the refresh
	if (!$json) {
		oidc_logout();
	}

	$accessToken = json_decode($json);

	// If the token is not expired, refreshing isn't necessary
	if (is_token_unexpired($accessToken)) {
		return;
	}

	// Try to obtain refreshed access token
	try {
		$oidc = get_oidc_client();
		$_SESSION[OIDC_SESSION] = json_encode($oidc->refreshToken($accessToken->refresh_token));
	} catch (\Jumbojett\OpenIDConnectClientException $e) {
		// Log out if an unknown problem arises
		$page['errors'][] = $e->getMessage();
		oidc_logout();
	}
}

/**
 * Use the given input to begin the resource owner credentials flow
 * WARNING: This flow must ONLY be used when the Piwigo server can be trusted
 */
function password_login($success, $username, $password, $remember_me)
{
	// If user is logged in through another hook, skip
	if ($success === true) {
		return true;
	}

	// If resource owner credentials flow is disable, don't attempt
	if (!can_resource_owner_credentials_grant()) {
		return false;
	}

	// Begin login attempt
	$success = false;

	// Try to request token with auth params
	$oidc = get_oidc_client();
	$oidc->addAuthParam([
		'username' => $username,
		'password' => $password,
	]);
	$response = $oidc->requestResourceOwnerToken(true);
	if (isset($response->access_token)) {
		$success = oidc_login($oidc, $response, $remember_me);
	}

	// If login unsuccessful, trigger login_failure accordingly
	if (!$success) {
		trigger_notify('login_failure', stripslashes($username));
	}

	return $success;
}

function oidc_admin_link($menu) 
{
	$menu[] = [
    	'NAME' => 'OpenID Connect',
    	'URL' => OIDC_ADMIN,
	];
	return $menu;
}
?>
