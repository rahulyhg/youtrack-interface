<?php
require_once __DIR__ . '/authenticationAndSecurity.php';
$authenticationAndSecurity = new authenticationAndSecurity;

$authenticationAndSecurity->removeCookies();
$authenticationAndSecurity->redirectBackToIndex();