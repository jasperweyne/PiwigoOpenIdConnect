<?php
defined('PHPWG_ROOT_PATH') or die('Hacking attempt!');

class OpenIdConnect_maintain extends PluginMaintain
{
	private $default_conf = [
		'issuer_url' => '',
		'client_id' => '',
		'client_secret' => '',
		'scope' => 'openid profile email',
		'preferred_username' => '',
		'proxy' => '',
		'verify_host' => true,
		'verify_peer' => true,
		'authparam' => '',
		'register_new_users' => true,
		'redirect_new_to_profile' => false,
		'notify_admins_on_register' => false,
		'notify_user_on_register' => false,
		'authorization_code_flow' => false,
		'password_flow' => false,
		'password_reset_url' => '',
		'registration_url' => '',
	];

	function install($plugin_version, &$errors=array())
	{
		global $conf;
		global $prefixeTable;

		if (empty($conf['OIDC']))
		{
			conf_update_param('OIDC', $this->default_conf, true);
		}

		$query="CREATE TABLE IF NOT EXISTS  `" . $prefixeTable . "oidc` (`sub` VARCHAR(255) CHARACTER SET utf8,`user_id` text CHARACTER SET utf8, UNIQUE KEY `sub` (`sub`)) ENGINE = MyISAM CHARSET=utf8 COLLATE utf8_general_ci;";
		pwg_query($query);
	}

	function activate($plugin_version, &$errors = array())
	{
		$this->install($plugin_version, $errors);
	}

	function update($old_version, $new_version, &$errors=array())
	{
		$this->install($new_version, $errors);
	}

	function uninstall()
	{
		conf_delete_param('OIDC');

		$query="DROP TABLE IF EXISTS `" . $prefixeTable . "oidc`;";
		pwg_query($query);
	}
}
?>