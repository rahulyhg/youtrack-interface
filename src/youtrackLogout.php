<?php
/**
 * clear login cookie.
 */
namespace  Youtrackinterfacer;


class youtrackLogout {
    function execute()
    {
        $authenticationAndSecurity = new authenticationAndSecurity();
        $authenticationAndSecurity->removeCookies();
        $authenticationAndSecurity->redirectBackToIndex();
    }
}