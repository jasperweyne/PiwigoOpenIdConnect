<?php

defined('OIDC_PATH') or die('Hacking attempt!');

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

$template->assign($conf['OIDC']);

$template->set_filename('oidc_config', realpath(OIDC_PATH . 'template/config.tpl'));
$template->assign_var_from_handle('ADMIN_CONTENT', 'oidc_config');
?>