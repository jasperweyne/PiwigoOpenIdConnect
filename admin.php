<?php

defined('OIDC_PATH') or die('Hacking attempt!');

if (isset($_POST['authorization_test']))
{
	$_SESSION[OIDC_SESSION . 'test'] = 'init';
	redirect(OIDC_PATH . 'auth.php'); 
}

if (isset($_POST['password_test']))
{
	$oidc = get_oidc_client();
	$oidc->addAuthParam([
		'username' => $_POST['password_test_user'],
		'password' => $_POST['password_test_pass'],
	]);

	try {
		$response = $oidc->requestResourceOwnerToken(true);
		if (!isset($response->access_token)) {
			$page['errors'][] = $response->error . (isset($response->error_description) ? ': ' . $response->error_description : '');
		}

		$oidc->setAccessToken($response->access_token);
		$sub = $oidc->requestUserInfo('sub');
		if ($sub === null) {
			$page['errors'][] = l10n('Server did not return user info');
		}

		$page['infos'][] = l10n('Resource owner credentials flow successful!');
	} catch (\Exception $e) {
		$page['errors'][] = $e->getMessage();
	}
}

if (isset($_POST['save_config']))
{
	$conf['OIDC'] = [
		'issuer_url' => $_POST['issuer_url'], 
		'client_id' => $_POST['client_id'], 
		'client_secret' => $_POST['client_secret'], 
		'scope' => $_POST['scope'], 
		'preferred_username' => $_POST['preferred_username'], 
		'proxy' => $_POST['proxy'], 
		'verify_host' => isset($_POST['verify_host']), 
		'verify_peer' => isset($_POST['verify_peer']), 
		'authparam' => $_POST['authparam'], 
		'register_new_users' => isset($_POST['register_new_users']), 
		'redirect_new_to_profile' => isset($_POST['redirect_new_to_profile']), 
		'notify_admins_on_register' => isset($_POST['notify_admins_on_register']),
		'notify_user_on_register' => isset($_POST['notify_user_on_register']), 
		'authorization_code_flow' => isset($_POST['authorization_code_flow']), 
		'password_flow' => isset($_POST['password_flow']), 
		'password_reset_url' => $_POST['password_reset_url'], 
		'registration_url' => $_POST['registration_url'],
	];

	conf_update_param('OIDC', $conf['OIDC']);
	$page['infos'][] = l10n('Settings saved.');
}

if (!$conf['allow_user_registration'] && $conf['OIDC']['register_new_users']) {
	$page['warnings'][] = l10n('User registration is disabled in the Piwigo settings. This behaviour will be overridden by this plugin.');
}

$template->assign($conf['OIDC']);
$template->assign(['redirect_url' => embellish_url(get_absolute_root_url() . OIDC_PATH . 'auth.php')]);

$template->set_filename('oidc_config', realpath(OIDC_PATH . 'template/config.tpl'));
$template->assign_var_from_handle('ADMIN_CONTENT', 'oidc_config');
?>