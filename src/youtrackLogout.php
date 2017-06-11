<?php
/**
 * clear login cookie.
 */
namespace  Youtrackinterfacer;
use authenticationAndSecurity;

class youtrackLogout {
    function execute()
    {
        $authenticationAndSecurity = new authenticationAndSecurity();
        $authenticationAndSecurity->removeCookies();
        $authenticationAndSecurity->redirectBackToIndex();
    }
}