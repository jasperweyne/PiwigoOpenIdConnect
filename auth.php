<?php

define('PHPWG_ROOT_PATH', '../../');

require_once(PHPWG_ROOT_PATH.'include/common.inc.php');
require_once(OIDC_PATH . 'oidc.php');

// Test or authentication flow preperations
if (isset($_SESSION[OIDC_SESSION . 'test'])) {
    // Disable test flow, in case of invalid access
    unset($_SESSION[OIDC_SESSION . 'test']);

    // Check if eligible to perform test
    check_status(ACCESS_ADMINISTRATOR);

    // Re-enable test flow
    $_SESSION[OIDC_SESSION . 'test'] = 'exec';
} else {
    // Check if authorization flow is enabled
    if (!can_authorization_grant()) {
        $_SESSION['page_errors'][] = l10n("OpenID Authorization flow is disabled.");
        redirect(get_root_url() . 'identification.php');
    }

    // Redirect if logged in uncorrectly
    check_status(ACCESS_FREE);
    if (!is_a_guest())
    {
        redirect(get_gallery_home_url());
    }
}

// Begin OpenID authorization flow
try {
    $oidc = get_oidc_client();
    $success = $oidc->authenticate();

    // Test flow
    if (isset($_SESSION[OIDC_SESSION . 'test'])) {
        if ($success) {
            echo "Successful!";
        } else {
            echo "Problem detected, check your settings.";
        }
        unset($_SESSION[OIDC_SESSION . 'test']);
        exit;
    }

    // Authentication flow
    if ($success) {
        oidc_login($oidc, $oidc->getAccessToken(), false);
    } else {
        $_SESSION['page_warnings'][] = l10n('Login not successful, try again.');
    }
} catch (\Exception $e) {
    // Test flow
    if (isset($_SESSION[OIDC_SESSION . 'test'])) {
        unset($_SESSION[OIDC_SESSION . 'test']);
        echo $e->getMessage();
        exit;
    }

    // Authentication flow
    $_SESSION['page_errors'][] = $e->getMessage();
}

redirect(get_gallery_home_url());
?>