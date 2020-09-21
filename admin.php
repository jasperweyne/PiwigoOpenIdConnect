<?php
/*
   Copyright 2020 Jasper Weyne

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

defined('OIDC_PATH') or die('Hacking attempt!');

// Authorization flow forms
if (isset($_POST['authorization_create']))
{
	$_SESSION[OIDC_SESSION . '_auth'] = 'create';
	redirect(OIDC_PATH . 'auth.php'); 
}

if (isset($_POST['authorization_test']))
{
	$_SESSION[OIDC_SESSION . '_auth'] = 'test';
	redirect(OIDC_PATH . 'auth.php'); 
}

// Password flow forms
if (isset($_POST['password_test']) || isset($_POST['password_create']))
{
	// Build a token request
	$oidc = get_oidc_client();
	$oidc->addAuthParam([
		'username' => $_POST['password_test_user'],
		'password' => $_POST['password_test_pass'],
	]);

	// Try to perform token request
	try {
		$response = $oidc->requestResourceOwnerToken(true);
		if (!isset($response->access_token)) {
			$page['errors'][] = $response->error . (isset($response->error_description) ? ': ' . $response->error_description : '');
		} else {
			// Token request succeeded, store token in $oidc
			$oidc->setAccessToken($response->access_token);

			// If userinfo point is inaccessible, show error
			if (null === $oidc->requestUserInfo('sub')) {
				$page['errors'][] = l10n('Server did not return user info');
			} else if (isset($_POST['password_create'])) {
				// User creation flow
				$user_id = oidc_retrieve($oidc, true);
				if ($user_id !== null) {
					$page['infos'][] = l10n('User added to Piwigo!');
				} else {
					$page['errors'][] = l10n('A problem occurred during user registration.');
				}
			} else {
				$page['infos'][] = l10n('Resource owner credentials flow successful!');
			}
		}
	} catch (\Exception $e) {
		// Catch all
		$page['errors'][] = $e->getMessage();
	}
}

// Save settings form
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

// General warnings
if (!$conf['allow_user_registration'] && $conf['OIDC']['register_new_users']) {
	$page['warnings'][] = l10n('User registration is disabled in the Piwigo settings. This behaviour will be overridden by this plugin.');
}

// Render page
$template->assign($conf['OIDC']);
$template->assign(['redirect_url' => embellish_url(get_absolute_root_url() . OIDC_PATH . 'auth.php')]);

$template->set_filename('oidc_config', realpath(OIDC_PATH . 'template/config.tpl'));
$template->assign_var_from_handle('ADMIN_CONTENT', 'oidc_config');
?>