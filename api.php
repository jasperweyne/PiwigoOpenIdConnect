<?php
/*
   Copyright 2021 Jasper Weyne

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

defined('PHPWG_ROOT_PATH') or die('Hacking attempt!');

require_once(PHPWG_ROOT_PATH . 'include/common.inc.php');
require_once(OIDC_PATH . 'oidc.php');

/**
 * API method
 * Tries to login the user using an OIDC token 
 * @param mixed[] $params
 *    @option string access_token
 */
function api_login($params, &$service)
{
    // Create OIDC service object
    $oidc = get_oidc_client();
    $oidc->setAccessToken($params['access_token']);

    // Create token object from params
    $tokens = new stdClass();
    $tokens->access_token = $params['access_token'];
    if (oidc_login($oidc, $tokens, false))
    {
        return true;
    }
    return new PwgError(999, 'Invalid access token');
}

?>