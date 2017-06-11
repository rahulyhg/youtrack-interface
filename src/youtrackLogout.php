<?php
/**
 * clear login cookie.
 */
namespace  Youtrackinterfacer;
use authenticationAndSecurity;

$authenticationAndSecurity = new authenticationAndSecurity();

$authenticationAndSecurity->removeCookies();
$authenticationAndSecurity->redirectBackToIndex();
