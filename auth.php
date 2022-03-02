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

define('PHPWG_ROOT_PATH', '../../');

require_once(PHPWG_ROOT_PATH.'include/common.inc.php');
require_once(OIDC_PATH . 'oidc.php');

// Test or authentication flow preperations
if (isset($_SESSION[OIDC_SESSION . '_auth'])) {
    $value = $_SESSION[OIDC_SESSION . '_auth'];

    // Disable test flow, in case of invalid access
    unset($_SESSION[OIDC_SESSION . '_auth']);

    // Check if eligible to perform test
    check_status(ACCESS_ADMINISTRATOR);

    // Re-enable test flow
    $_SESSION[OIDC_SESSION . '_auth'] = $value;
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
    if (isset($_SESSION[OIDC_SESSION . '_auth'])) {
        // Create flow
        if ($success && $_SESSION[OIDC_SESSION . '_auth'] === 'create') {
            $success = oidc_retrieve($oidc, true) !== null;
        }

        if ($success && $oidc->requestUserInfo('sub') !== null) {
            echo "Successful!";
        } else {
            echo "Problem detected, check your settings.";
        }
        unset($_SESSION[OIDC_SESSION . '_auth']);
        exit;
    }

    // Authentication flow
    if ($success) {
        oidc_login($oidc, $oidc->getTokenResponse(), false);
    } else {
        $_SESSION['page_warnings'][] = l10n('Login not successful, try again.');
    }
} catch (\Exception $e) {
    // Test flow
    if (isset($_SESSION[OIDC_SESSION . '_auth'])) {
        unset($_SESSION[OIDC_SESSION . '_auth']);
        echo $e->getMessage();
        exit;
    }

    // Authentication flow
    $_SESSION['page_errors'][] = $e->getMessage();
}

redirect(get_gallery_home_url());
?>
