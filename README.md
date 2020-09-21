# Piwigo OpenID Connect plugin
OpenID Connect is a Piwigo plugin which allows single sign-on logins using the
OpenID Connect protocol. The protocol utilizes both the OpenID Connect Core and
the OpenID Connect Discovery specifications. The plugin supports both the
authorization code flow, as well as the (legacy) resource owner credentials
flow, otherwise known as the password flow. Although legacy and recommended
against, by enabling this flow, login through the Piwigo webservice API is
enabled.

Special thanks to jumbojett for the OpenID Connect library used in this plugin.

## License
This project is covered by the Apache 2.0 License, please refer to the LICENSE
file enclosed.

## Install
To install the plugin in a Piwigo installation, please download a release and
unzip it to the /plugins folder within your Piwigo installation. Enable the
plugin from Piwigo (it should be visible), and configure the plugin through the
admin. Test that your installation is functional before enabling a flow.

To install this repository, clone the repository or download its contents
manually. Note that at least, PHP 5.6 is required, and PHP 7.0 is recommended.
Also, Composer is required for downloading the dependencies, which can be done
by executing the shell command `composer install`.