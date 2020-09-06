<?php

use Jumbojett\OpenIDConnectClientException;

define('PHPWG_ROOT_PATH', '../../');

require_once(PHPWG_ROOT_PATH.'include/common.inc.php');
require_once(OIDC_PATH . 'oidc.php');

// Check if authorization flow is enabled
if (!can_authorization_grant()) {
    $_SESSION['page_errors'][] = l10n("OpenID Authorization flow is disabled.");
    redirect('identification.php');
}

// If not logged in, authenticate user
check_status(ACCESS_FREE);
if (is_a_guest())
{
    try {
        $oidc = get_oidc_client();
        $success = $oidc->authenticate();
        if ($success) {
            oidc_login($oidc, $oidc->getAccessToken(), false);
        } else {
            $_SESSION['page_warnings'][] = l10n('Login not successful, try again.');
        }
    } catch (OpenIDConnectClientException $e) {
        $_SESSION['page_errors'][] = $e->getMessage();
    }
}

redirect(get_gallery_home_url());
?>