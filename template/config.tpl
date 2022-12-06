<div class="pageTitle">
	<h2>OpenID Connect</h2>
</div>

<form method="post" action="" id="test_auth" target="_blank"></form>
<form method="post" action="" id="test_pass"></form>


<div id="configContent">
<form method="post" action="" class="properties">
<fieldset id="mainConf">
    <legend>{'OpenID Connect settings'|translate}</legend>
	<ul>
		<li>
			<label for="issuer_url">{'Issuer URL'|translate}</label>
			<br />
			<input type="text" size=50 name="issuer_url" id="issuer_url" value="{$issuer_url}">
		</li>
		<li>
			<label for="client_id">{'Client ID'|translate}</label>
			<br />
			<input type="text" name="client_id" id="client_id" value="{$client_id}">
		</li>
		<li>
			<label for="client_secret">{'Client secret'|translate}</label>
			<br />
			<input type="text" name="client_secret" id="client_secret" value="{$client_secret}">
		</li>
	</ul>
	<i>{'Additional settings are detected through the .well-known/openid-configuration file from the issuer.'|translate}</i>
</fieldset>

<fieldset id="mainConf">
    <legend>{'Test OpenID connection'|translate}</legend>
	<ul>
		<li>
			<i style="display:inline-block;max-width:30rem;line-height:normal">{'Enabling a flow disables non-OpenID Connect account login. Please test your flow before enabling and make sure at least one OpenID Connect account has webmaster access rights. In case of failure, any config can be overridden by the conf.php found in the plugin folder.'|translate}</i>
		</li>
		<li>
			<input form="test_auth" type="submit" name="authorization_test" value="{'Test authorization code flow'|translate}">
			<input form="test_auth" type="submit" name="authorization_create" value="{'Create user with authorization code flow'|translate}">
		</li>
	</ul>
	<ul>
		<li>
			<label for="password_test_user">{'Username'|translate}</label>
			<br />
			<input form="test_pass" type="text" name="password_test_user" id="password_test_user" value="{$password_test_user}">
		</li>
		<li>
			<label for="password_test_pass">{'Password'|translate}</label>
			<br />
			<input form="test_pass" type="password" name="password_test_pass" id="password_test_pass" value="{$password_test_pass}">
		</li>
		<li>
			<input form="test_pass" type="submit" name="password_test" value="{'Test resource owner credentials flow'|translate}">
			<input form="test_pass" type="submit" name="password_create" value="{'Create user with resource owner credentials flow'|translate}">
		</li>
	</ul>
</fieldset>

<fieldset id="mainConf">
    <legend>{'Authorization Code Flow'|translate}</legend>
	<i style="display:inline-block;max-width:30rem">Please register '{$redirect_url}' as the redirect URL with your OpenID Provider.</i>
	<ul>
		<li>
			<input type="checkbox" name="authorization_code_flow" id="authorization_code_flow" {if $authorization_code_flow}checked="checked"{/if}>
			<label for="authorization_code_flow">{'Enable Authorization Code Flow'|translate}</label>
		</li>
	</ul>
</fieldset>

<fieldset id="mainConf">
    <legend>{'Resource Owner Credentials Flow'|translate}</legend>
	<i style="display:inline-block;max-width:30rem">WARNING: This flow is now generally considered unsafe, and it's usage is not recommended. However, for compatibility reasons, it's supported. Proceed with caution!</i>  
	<ul>
		<li>
			<input type="checkbox" name="password_flow" id="password_flow" {if $password_flow}checked="checked"{/if}>
			<label for="password_flow">{'Enable Resource Owner Credentials Flow'|translate}</label>
		</li>
		<br />
		<i>{'Leave these values empty to disable these links.'|translate}</i>
		<li>
			<label for="password_reset_url">{'Password reset URL'|translate}</label>
			<br />
			<input type="text" size=50 name="password_reset_url" id="password_reset_url" value="{$password_reset_url}">
		</li>
		<li>
			<label for="registration_url">{'Registration URL'|translate}</label>
			<br />
			<input type="text" size=50 name="registration_url" id="registration_url" value="{$registration_url}">
		</li>
	</ul>
</fieldset>

<fieldset id="mainConf">
    <legend>{'New users'|translate}</legend>
	<ul>
		<li>
			<input type="checkbox" name="register_new_users" id="register_new_users" {if $register_new_users}checked="checked"{/if}>
			<label for="register_new_users">{'Register new Piwigo users on succesful OpenID Connect authentication'|translate}</label>
		</li>
		<li>
			<input type="checkbox" name="redirect_new_to_profile" id="redirect_new_to_profile" {if $redirect_new_to_profile}checked="checked"{/if}>
			<label for="redirect_new_to_profile">{'Redirect new users to profile page'|translate}</label>
		</li>
		<li>
			<input type="checkbox" name="notify_admins_on_register" id="notify_admins_on_register" {if $notify_admins_on_register}checked="checked"{/if}>
			<label for="notify_admins_on_register">{'Admins are notified by mail about new users'|translate}</label>
		</li>
		<li>
			<input type="checkbox" name="notify_user_on_register" id="notify_user_on_register" {if $notify_user_on_register}checked="checked"{/if}>
			<label for="notify_user_on_register">{'Send registration mail to new users'|translate}</label>
		</li>
  </ul>
</fieldset>

<fieldset id="mainConf">
    <legend>{'Advanced OpenID Connect settings'|translate}</legend>
	<ul>
		<li>
			<input type="checkbox" name="verify_host" id="verify_host" {if $verify_host}checked="checked"{/if}>
			<label for="verify_host">{'Enable host verification'|translate}</label>
		</li>
		<li>
			<input type="checkbox" name="verify_peer" id="verify_peer" {if $verify_peer}checked="checked"{/if}>
			<label for="verify_peer">{'Enable SSL peer verification'|translate}</label>
		</li>
		<li>
			<input type="checkbox" name="openid_logout" id="openid_logout" {if $openid_logout}checked="checked"{/if}>
			<label for="openid_logout">{'Enable OpenID logout'|translate}</label>
		</li>
		<li>
			<label for="scope">{'Scopes'|translate}</label>
			<br />
			<input type="text" size=50 name="scope" id="scope" value="{$scope}">
		</li>
		<li>
			<label for="preferred_username">{'Preferred username'|translate}</label>
			<br />
			<input type="text" size=50 name="preferred_username" id="preferred_username" value="{$preferred_username}">
			<br />
			<i>{'This claim is used for local identification and must return a unique value for each user. Falls back to \'sub\'.'|translate}</i>
		</li>
		<li>
			<label for="proxy">{'HTTP Proxy'|translate}</label>
			<br />
			<input type="text" size=50 name="proxy" id="proxy" value="{$proxy}">
		</li>
		<li>
			<label for="authparam">{'Authentication parameters'|translate}</label>
			<br />
			<input type="text" size=50 name="authparam" id="authparam" value="{$authparam}">
			<br />
			<i>{'Please provide parameters in a JSON array.'|translate}</i>
		</li>
	</ul>
</fieldset>

<p style="text-align:left;"><input type="submit" name="save_config" value="{'Save Settings'|translate}"></p>
</form>
</div>

<div style="text-align:right;">
  Developed by Jasper Weyne
</div>

