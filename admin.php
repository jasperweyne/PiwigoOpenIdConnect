<?php

defined('OIDC_PATH') or die('Hacking attempt!');

if (isset($_POST['save_config']))
{
	// $conf['OIDC'] = [
	// 	'enabled' => isset($_POST['enabled']),
	// ];

	// conf_update_param('OIDC', $conf['OIDC']);
	$page['infos'][] = l10n('Information data registered in database');
}


// $template->assign(array(
//   'OIDC' => $conf['OIDC'],
// ));


$template->set_filename('oidc_config', realpath(OIDC_PATH . 'template/config.tpl'));
$template->assign_var_from_handle('ADMIN_CONTENT', 'oidc_config');
?>